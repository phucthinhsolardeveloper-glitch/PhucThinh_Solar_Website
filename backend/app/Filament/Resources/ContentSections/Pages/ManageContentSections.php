<?php

namespace App\Filament\Resources\ContentSections\Pages;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Content\SectionHistory;
use App\Filament\Resources\ContentSections\ContentSectionResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ContentSection;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\DB;

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
            Action::make('restoreDeleted')->label('Khôi phục khối đã xóa')->icon('heroicon-o-arrow-uturn-left')->color('gray')
                ->schema([Select::make('version')->label('Khối đã xóa')->required()->searchable()->options(fn () => $this->deleted())])
                ->action(function (array $data): void {
                    SectionHistory::restore((int) $data['version']);
                    Notification::make()->success()->title('Đã khôi phục khối mô tả')->send();
                }),
        ];
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
