<?php

namespace App\Filament\Pages;

use App\Domain\Catalog\Bulk\BulkApplier;
use App\Domain\Catalog\Bulk\BulkCatalog;
use App\Domain\Catalog\Bulk\BulkExporter;
use App\Domain\Catalog\Bulk\BulkImages;
use App\Domain\Catalog\Bulk\BulkPlanner;
use App\Domain\Catalog\Bulk\BulkReader;
use App\Domain\Catalog\Bulk\BulkSession;
use App\Domain\Catalog\MerchantFeed;
use App\Domain\Catalog\ProductSalesReadiness;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\Fields;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/** One place to take the catalogue out to Excel, bring it back safely, add pictures in bulk and feed Google. */
class ProductBulkPage extends Page
{
    protected static ?string $title = 'Nhập / xuất Excel';

    protected static ?string $navigationLabel = 'Nhập / xuất Excel';

    protected static ?string $slug = 'nhap-xuat-san-pham';

    protected static string|UnitEnum|null $navigationGroup = 'Sản phẩm';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected string $view = 'filament.pages.product-bulk';

    public ?string $token = null;

    /** @var array<string, bool> */
    public array $overwrite = [];

    /** @var array<string, string> */
    public array $brandChoices = [];

    /** @var array<string, string> */
    public array $productChoices = [];

    public string $show = 'issues';

    public int $rowsPage = 1;

    /** @var array<string, mixed>|null */
    public ?array $images = null;

    public static function canAccess(): bool
    {
        return ProductResource::canBulkImport();
    }

    /** @return array<string, mixed>|null */
    public function plan(): ?array
    {
        return once(fn () => BulkSession::plan($this->token));
    }

    /** @return array{rows: list<array<string, mixed>>, total: int, pages: int} rows matching the chosen view, one page */
    public function visibleRows(): array
    {
        $rows = collect($this->plan()['rows'] ?? [])->filter(fn (array $row) => match ($this->show) {
            'all' => true,
            'changes' => $row['changes'] !== [] || $row['action'] === 'archive',
            default => $row['errors'] !== [] || $row['conflicts'] !== [] || $row['warnings'] !== [],
        })->values();
        $pages = max(1, (int) ceil($rows->count() / 50));

        return ['rows' => $rows->forPage(min($this->rowsPage, $pages), 50)->values()->all(), 'total' => $rows->count(), 'pages' => $pages];
    }

    public function showRows(string $view): void
    {
        $this->show = $view;
        $this->rowsPage = 1;
    }

    public function overwriteAll(bool $on): void
    {
        $this->overwrite = [];
        foreach ($this->plan()['rows'] ?? [] as $row) {
            foreach ($row['conflicts'] as $conflict) {
                $this->overwrite[$conflict['key']] = $on;
            }
        }
    }

    /** Applies the decisions made on screen to the preview. */
    public function replan(): void
    {
        $read = BulkSession::get($this->token, (int) auth('admin')->id());
        if ($read === null) {
            $this->expired();

            return;
        }
        BulkSession::putPlan((string) $this->token, (new BulkPlanner(new BulkCatalog, $this->choices()))->plan($read['rows'], $read['baseline']));
    }

    public function cancel(): void
    {
        BulkSession::forget($this->token);
        $this->reset('token', 'overwrite', 'brandChoices', 'productChoices', 'show', 'rowsPage');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('export')->label('Xuất sản phẩm')->icon(Heroicon::OutlinedArrowDownTray)
                    ->modalHeading('Xuất sản phẩm')->modalSubmitActionLabel('Tải về')
                    ->schema([
                        Select::make('scope')->label('Sản phẩm')->default('all')->required()->native(false)
                            ->options(['all' => 'Tất cả', 'visible' => 'Đang hiển thị', 'hidden' => 'Đang ẩn'] + ProductSalesReadiness::FILTERS + ProductSalesReadiness::DATA_FILTERS),
                        Select::make('format')->label('Định dạng')->default('xlsx')->required()->options(['xlsx' => 'Excel (khuyên dùng)', 'csv' => 'CSV']),
                    ])
                    ->action(fn (array $data) => $this->download((string) $data['scope'], (string) $data['format'])),
                Action::make('template')->label('Tải mẫu trống')->icon(Heroicon::OutlinedDocument)
                    ->action(fn () => app(BulkExporter::class)->xlsx(new BulkCatalog(collect()), 'mau-nhap-san-pham')),
            ])->label('Xuất')->icon(Heroicon::OutlinedArrowDownTray)->button()->color('gray'),
            ActionGroup::make([
                Action::make('zipImages')->label('Thêm ảnh từ tệp ZIP')->icon(Heroicon::OutlinedArchiveBox)
                    ->modalDescription('Đặt tên ảnh theo mã sản phẩm: MA-01.jpg là ảnh chính, MA-02.jpg trở đi vào thư viện. Ảnh chính đang có không bị thay.')
                    ->schema([self::upload('zip', 'Tệp ZIP (tối đa 200 ảnh, 20 MB)', ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'], 20 * 1024)])
                    ->action(fn (array $data) => $this->previewImages($data)),
                Action::make('uploadImages')->label('Tải nhiều ảnh')->icon(Heroicon::OutlinedPhoto)
                    ->modalDescription('Tải trước rồi ghi tên tệp vào cột ảnh trong Excel. Có thể chọn tối đa 20 ảnh mỗi lần.')
                    ->schema([Fields::image('images', 'product', 'Ảnh sản phẩm')->multiple()->maxFiles(20)->required()])
                    ->action(function (array $data): void {
                        Notification::make()->success()->title('Đã tải '.count($data['images']).' ảnh')->body(implode("\n", $data['images']))->persistent()->send();
                    }),
            ])->label('Ảnh')->icon(Heroicon::OutlinedPhoto)->button()->color('gray'),
            Action::make('feed')->label('Feed Google Shopping')->icon(Heroicon::OutlinedShoppingBag)->color('gray')
                ->action(fn () => $this->downloadFeed()),
            Action::make('previewFile')->label('Nhập tệp')->icon(Heroicon::OutlinedArrowUpTray)
                ->modalHeading('Chọn tệp để xem trước')->modalDescription('Chưa có gì được ghi cho tới khi bạn bấm Áp dụng.')->modalSubmitActionLabel('Xem trước')
                ->schema([self::upload('file', 'Tệp Excel hoặc CSV (tối đa 10 MB)', [
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv', 'text/plain', 'application/csv',
                ], 10 * 1024)])
                ->action(fn (array $data) => $this->previewFile($data)),
            Action::make('apply')->label('Áp dụng')->icon(Heroicon::OutlinedCheckCircle)->color('success')
                ->visible(fn () => $this->token !== null && ($this->plan()['errors'] ?? 1) === 0 && $this->hasWork())
                ->requiresConfirmation()->modalHeading('Áp dụng thay đổi')
                ->modalDescription(fn () => $this->applySummary())
                ->schema(fn () => $this->hiddenCount() > 0 ? [Toggle::make('hidden_ok')->label('Tôi xác nhận '.$this->hiddenCount().' sản phẩm sẽ ở trạng thái ẩn')->accepted()->required()] : [])
                ->action(fn (array $data) => $this->applyFile((bool) ($data['hidden_ok'] ?? false))),
            Action::make('applyImages')->label('Thêm ảnh')->icon(Heroicon::OutlinedCheckCircle)->color('success')
                ->visible(fn () => ($this->images['counts']['ready'] ?? 0) > 0 && ($this->images['counts']['error'] ?? 0) === 0)
                ->requiresConfirmation()->modalDescription(fn () => 'Thêm '.($this->images['counts']['ready'] ?? 0).' ảnh vào sản phẩm.')
                ->action(fn () => $this->applyImages()),
        ];
    }

    private static function upload(string $name, string $label, array $types, int $maxKb): FileUpload
    {
        return FileUpload::make($name)->label($label)->required()->disk('local')->directory('bulk-uploads')->visibility('private')
            ->acceptedFileTypes($types)->maxSize($maxKb)->storeFileNamesIn($name.'_name');
    }

    private function download(string $scope, string $format)
    {
        $products = Product::query()->with(['seo', 'images', 'contentSection'])
            ->when($scope === 'visible', fn (Builder $q) => $q->where('is_visible', true))
            ->when($scope === 'hidden', fn (Builder $q) => $q->where('is_visible', false))
            ->when(! in_array($scope, ['all', 'visible', 'hidden'], true), fn (Builder $q) => app(ProductSalesReadiness::class)->filter($q, $scope))
            ->orderBy('id')->get();
        if ($products->isEmpty()) {
            Notification::make()->warning()->title('Không có sản phẩm nào khớp lựa chọn')->send();

            return null;
        }
        $name = 'san-pham'.($scope !== 'all' ? '-'.$scope : '').'-'.date('Ymd-His');
        $catalog = new BulkCatalog($products);

        return $format === 'csv' ? app(BulkExporter::class)->csv($catalog, $name) : app(BulkExporter::class)->xlsx($catalog, $name);
    }

    private function downloadFeed()
    {
        $feed = app(MerchantFeed::class);
        if (! $feed->pricesArePublic()) {
            Notification::make()->warning()->title('Chưa xuất được feed')->body('Website đang ẩn giá. Bật hiển thị giá trước để giá trên feed khớp trang sản phẩm.')->persistent()->send();

            return null;
        }
        $result = $feed->build((string) config('site.canonical_origin'));
        if ($result['rows'] === []) {
            Notification::make()->warning()->title('Chưa có sản phẩm đủ điều kiện')->body('Cần mã sản phẩm, giá hợp lệ và ảnh Shopping từ 500×500 px.')->persistent()->send();

            return null;
        }
        Notification::make()->success()->title('Feed gồm '.count($result['rows']).' sản phẩm')->body('Bỏ qua '.$result['skipped'].' sản phẩm chưa đủ điều kiện.')->send();

        return $feed->download($result['rows']);
    }

    private function previewFile(array $data): void
    {
        $disk = Storage::disk('local');
        try {
            $read = app(BulkReader::class)->read($disk->path($data['file']), (string) $data['file_name']);
        } catch (ValidationException $error) {
            $this->fail(collect($error->errors())->flatten()->implode(' '));

            return;
        } finally {
            $disk->delete($data['file']);
        }
        BulkSession::forget($this->token);
        $this->reset('overwrite', 'brandChoices', 'productChoices', 'rowsPage', 'images');
        $this->token = BulkSession::put($read, (int) auth('admin')->id());
        BulkSession::putPlan($this->token, (new BulkPlanner(new BulkCatalog))->plan($read['rows'], $read['baseline']));
        $this->show = 'issues';
    }

    private function previewImages(array $data): void
    {
        $disk = Storage::disk('local');
        try {
            $this->images = app(BulkImages::class)->preview($disk->path($data['zip']), (string) $data['zip_name']);
        } catch (ValidationException $error) {
            $this->fail(collect($error->errors())->flatten()->implode(' '));
        } finally {
            $disk->delete($data['zip']);
        }
    }

    private function applyImages(): void
    {
        try {
            $added = app(BulkImages::class)->apply((array) $this->images);
            $this->images = null;
            Notification::make()->success()->title('Đã thêm '.$added.' ảnh')->send();
        } catch (ValidationException $error) {
            $this->fail(collect($error->errors())->flatten()->implode(' '));
        }
    }

    private function applyFile(bool $hiddenConfirmed): void
    {
        $read = BulkSession::get($this->token, (int) auth('admin')->id());
        if ($read === null) {
            $this->expired();

            return;
        }
        try {
            $applier = app(BulkApplier::class);
            $counts = $applier->apply($read['rows'], $read['baseline'], $this->choices(), $hiddenConfirmed);
        } catch (ValidationException $error) {
            $this->fail(collect($error->errors())->flatten()->implode(' '));
            $this->replan();

            return;
        }
        $this->cancel();
        Notification::make()->success()->title('Đã áp dụng')->body('Tạo mới '.$counts['create'].', cập nhật '.$counts['update'].', ẩn '.$counts['archive'].', thương hiệu mới '.$counts['brand_create'].'.')->persistent()->send();
        if ($applier->heldBack !== []) {
            Notification::make()->warning()->persistent()->title(count($applier->heldBack).' sản phẩm đã nhập nhưng CHƯA bật hiển thị')
                ->body("Cần sửa trước khi lên website:\n· ".implode("\n· ", array_slice($applier->heldBack, 0, 15)).(count($applier->heldBack) > 15 ? "\n… và ".(count($applier->heldBack) - 15).' sản phẩm khác' : ''))->send();
        }
    }

    /** @return array{overwrite: array<string, bool>, brands: array<string, string>, products: array<string, string>} */
    private function choices(): array
    {
        return ['overwrite' => array_filter($this->overwrite), 'brands' => array_filter($this->brandChoices), 'products' => array_filter($this->productChoices)];
    }

    private function hasWork(): bool
    {
        $counts = $this->plan()['counts'] ?? [];

        return (int) ($counts['create'] ?? 0) + (int) ($counts['update'] ?? 0) + (int) ($counts['archive'] ?? 0) > 0;
    }

    private function hiddenCount(): int
    {
        $counts = $this->plan()['counts'] ?? [];

        return (int) ($counts['draft_create'] ?? 0) + (int) ($counts['hide_update'] ?? 0) + (int) ($counts['archive'] ?? 0);
    }

    private function applySummary(): string
    {
        $counts = $this->plan()['counts'] ?? [];

        return 'Tạo mới '.($counts['create'] ?? 0).', cập nhật '.($counts['update'] ?? 0).', ẩn '.($counts['archive'] ?? 0).'. Toàn bộ được ghi cùng lúc; lỗi ở một dòng thì không dòng nào được ghi.';
    }

    private function fail(string $message): void
    {
        Notification::make()->danger()->title('Chưa thực hiện được')->body($message)->persistent()->send();
    }

    private function expired(): void
    {
        $this->cancel();
        $this->fail('Bản xem trước đã hết hạn (30 phút). Hãy tải tệp lên lại.');
    }
}
