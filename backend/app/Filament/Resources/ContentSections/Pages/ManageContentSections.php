<?php

namespace App\Filament\Resources\ContentSections\Pages;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Content\SectionHistory;
use App\Domain\Content\SectionTransfer;
use App\Filament\Resources\ContentSections\ContentSectionResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ContentSection;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ManageContentSections extends ManageRecords
{
    protected static string $resource = ContentSectionResource::class;

    /** How many public products show a description, and where it comes from. */
    public function getSubheading(): ?string
    {
        $visible = Product::query()->where('is_visible', true);
        $total = (clone $visible)->count();
        $filled = fn ($q) => $q->where('is_visible', true)->where('blocks', '!=', '[]');
        $own = (clone $visible)->whereHas('contentSection', fn ($q) => $filled($q)->whereIn('mode', ['own', 'both']))->count();
        $hidden = (clone $visible)->whereHas('contentSection', fn ($q) => $q->where('mode', 'hide'))->count();
        $brands = DB::table('content_sections')->where('owner_type', Brand::class)->where('is_visible', true)->where('blocks', '!=', '[]')->pluck('owner_id');
        $categories = DB::table('content_sections')->where('owner_type', Category::class)->where('is_visible', true)->where('blocks', '!=', '[]')->pluck('owner_id');
        $none = (clone $visible)->whereDoesntHave('contentSection', fn ($q) => $filled($q)->whereIn('mode', ['own', 'both']))
            ->whereDoesntHave('contentSection', fn ($q) => $q->where('mode', 'hide'))
            ->whereNotIn('brand_id', $brands->all() ?: [0])->whereNotIn('category_id', $this->withDescendants($categories->all()) ?: [0])->count();

        return "{$total} sản phẩm đang hiện: {$own} có khối riêng, ".max(0, $total - $own - $hidden - $none)." nhận từ thương hiệu hoặc danh mục, {$hidden} tắt khối, {$none} chưa có khối nào.";
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('export')->label('Xuất JSON thương hiệu & danh mục')->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => app(SectionTransfer::class)->export()),
                Action::make('import')->label('Nhập JSON')->icon('heroicon-o-arrow-up-tray')
                    ->modalSubmitActionLabel('Áp dụng')
                    ->steps([
                        Step::make('Chọn tệp')->schema([
                            FileUpload::make('file')->label('Tệp JSON (tối đa 3 MB)')->required()->storeFiles(false)
                                ->acceptedFileTypes(['application/json', 'text/plain'])->maxSize(3 * 1024),
                        ]),
                        Step::make('Xem trước')->schema([
                            Text::make(fn (Get $get) => new HtmlString(self::previewHtml($this->preview($get('file'))))),
                        ]),
                    ])
                    ->action(function (array $data): void {
                        $preview = $this->preview($data['file'] ?? null);
                        $written = app(SectionTransfer::class)->apply($preview);
                        Notification::make()->success()->title('Đã nhập '.$written.' khối mô tả')->send();
                    }),
                Action::make('restoreDeleted')->label('Khôi phục khối đã xóa')->icon('heroicon-o-arrow-uturn-left')
                    ->schema([Select::make('version')->label('Khối đã xóa')->required()->searchable()->options(fn () => $this->deleted())])
                    ->action(function (array $data): void {
                        SectionHistory::restore((int) $data['version']);
                        Notification::make()->success()->title('Đã khôi phục khối mô tả')->send();
                    }),
            ])->label('Nhập / xuất')->icon('heroicon-o-arrows-up-down')->button()->color('gray'),
        ];
    }

    /** @return array{rows: list<array<string, mixed>>, counts: array<string, int>} */
    private function preview(mixed $file): array
    {
        $file = is_array($file) ? reset($file) : $file;
        if (! $file instanceof TemporaryUploadedFile) {
            return ['rows' => [], 'counts' => []];
        }

        return once(fn () => app(SectionTransfer::class)->preview((string) file_get_contents($file->getRealPath())));
    }

    private static function previewHtml(array $preview): string
    {
        if ($preview['rows'] === []) {
            return '<p>Chưa có dữ liệu để xem trước.</p>';
        }
        $c = $preview['counts'];
        $labels = ['create' => 'Tạo mới', 'update' => 'Cập nhật', 'unchanged' => 'Không đổi', 'skip' => 'Bỏ qua', 'error' => 'Lỗi'];
        $html = '<p><b>'.$c['create'].'</b> tạo mới · <b>'.$c['update'].'</b> cập nhật · '.$c['unchanged'].' không đổi · '.$c['skip'].' bỏ qua · <b style="color:#dc2626">'.$c['error'].'</b> lỗi · '.$c['missing_images'].' mục thiếu ảnh</p>';
        $html .= '<table style="width:100%;font-size:13px;margin-top:8px;border-collapse:collapse">';
        foreach ($preview['rows'] as $row) {
            $html .= '<tr style="border-top:1px solid rgb(128 128 128 / .2)"><td style="padding:4px">'.e($row['name'] ?: '#'.$row['line']).'</td><td style="padding:4px">'
                .e($labels[$row['status']] ?? $row['status']).'</td><td style="padding:4px">'.e($row['message']).'</td></tr>';
        }

        return $html.'</table><p style="margin-top:8px">Mục lỗi và bỏ qua không được ghi. Khối đang có được giữ trong lịch sử trước khi thay.</p>';
    }

    /** @return array<int, string> latest deleted state of owners that have no description now */
    private function deleted(): array
    {
        $rows = DB::table('content_section_versions as v')->where('v.reason', 'delete')
            ->whereNotExists(fn ($q) => $q->from('content_sections as s')->whereColumn('s.owner_type', 'v.owner_type')->whereColumn('s.owner_id', 'v.owner_id'))
            ->orderByDesc('v.id')->limit(200)->get(['v.id', 'v.owner_type', 'v.owner_id', 'v.created_at', 'v.blocks']);
        $out = [];
        foreach ($rows->unique(fn ($v) => $v->owner_type.':'.$v->owner_id) as $v) {
            $section = new ContentSection(['owner_type' => $v->owner_type, 'owner_id' => $v->owner_id]);
            $out[(int) $v->id] = (ContentSectionResource::OWNERS[$v->owner_type] ?? '').': '.ContentSectionResource::ownerName($section)
                .' · xóa lúc '.date('H:i d/m/Y', strtotime((string) $v->created_at)).' · '.count(json_decode((string) $v->blocks, true) ?: []).' khối';
        }

        return $out;
    }

    /** @param list<int> $ids @return list<int> */
    private function withDescendants(array $ids): array
    {
        $tree = app(CategoryTree::class);

        return array_values(array_unique(array_merge(...array_map(fn ($id) => [(int) $id, ...$tree->descendantIds((int) $id)], $ids ?: [0]))));
    }
}
