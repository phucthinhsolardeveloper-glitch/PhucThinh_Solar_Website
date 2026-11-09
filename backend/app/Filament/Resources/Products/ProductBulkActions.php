<?php

namespace App\Filament\Resources\Products;

use App\Domain\Catalog\Bulk\BulkCatalog;
use App\Domain\Catalog\Bulk\BulkExporter;
use App\Domain\Catalog\ProductBulkUpdate;
use App\Filament\Support\Options;
use App\Models\Brand;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/** What can be done to many ticked products at once on the product list. */
final class ProductBulkActions
{
    /** @return list<BulkActionGroup> */
    public static function groups(): array
    {
        return [
            BulkActionGroup::make([
                self::flag('quoteOn', 'Chuyển sang Liên hệ báo giá', Heroicon::OutlinedChatBubbleLeftRight, ['quote_only' => true], 'Giá và nút mua của các sản phẩm đã chọn sẽ ẩn, thay bằng Liên hệ báo giá. Giá đã nhập vẫn được giữ.'),
                self::flag('quoteOff', 'Hiện giá trở lại', Heroicon::OutlinedCurrencyDollar, ['quote_only' => false]),
                self::flag('show', 'Hiện trên website', Heroicon::OutlinedEye, ['is_visible' => true]),
                self::flag('hide', 'Ẩn khỏi website', Heroicon::OutlinedEyeSlash, ['is_visible' => false]),
                self::flag('bestsellerOn', 'Đánh dấu Bán chạy', Heroicon::OutlinedFire, ['is_bestseller' => true]),
                self::flag('bestsellerOff', 'Bỏ Bán chạy', Heroicon::OutlinedMinusCircle, ['is_bestseller' => false]),
            ])->label('Trạng thái')->icon(Heroicon::OutlinedAdjustmentsHorizontal),
            BulkActionGroup::make([
                self::base('adjustPrices', 'Tăng / giảm giá theo %', Heroicon::OutlinedArrowTrendingUp)
                    ->modalDescription('Áp dụng cho giá gốc và giá khuyến mãi. Sản phẩm chưa có giá được bỏ qua.')
                    ->schema([
                        TextInput::make('percent')->label('Phần trăm thay đổi')->numeric()->required()->minValue(-90)->maxValue(500)->suffix('%')
                            ->helperText('Số dương để tăng, số âm để giảm. Ví dụ -10 là giảm 10%.'),
                        self::rounding(),
                    ])
                    ->action(fn (Collection $records, array $data) => self::priceResult(app(ProductBulkUpdate::class)->adjustPrices($records, (float) $data['percent'], (int) $data['round_to']))),
                self::base('setDiscount', 'Đặt giá khuyến mãi theo %', Heroicon::OutlinedReceiptPercent)
                    ->modalDescription('Giá khuyến mãi = giá gốc trừ đi phần trăm này.')
                    ->schema([
                        TextInput::make('percent')->label('Giảm so với giá gốc')->numeric()->required()->minValue(1)->maxValue(90)->suffix('%'),
                        self::rounding(),
                    ])
                    ->action(fn (Collection $records, array $data) => self::priceResult(app(ProductBulkUpdate::class)->setDiscount($records, (float) $data['percent'], (int) $data['round_to']))),
                self::flag('clearDiscount', 'Xóa giá khuyến mãi', Heroicon::OutlinedXCircle, ['sale_price' => 0]),
            ])->label('Giá')->icon(Heroicon::OutlinedBanknotes),
            BulkActionGroup::make([
                self::base('moveCategory', 'Chuyển danh mục', Heroicon::OutlinedFolderArrowDown)
                    ->schema([Select::make('category_id')->label('Danh mục mới')->options(fn () => Options::categoryPaths())->searchable()->required()])
                    ->action(fn (Collection $records, array $data) => self::changed(app(ProductBulkUpdate::class)->set($records, ['category_id' => (int) $data['category_id']]))),
                self::base('setBrand', 'Đổi thương hiệu', Heroicon::OutlinedTag)
                    ->schema([Select::make('brand_id')->label('Thương hiệu')->options(fn () => Brand::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->placeholder('Bỏ thương hiệu')])
                    ->action(fn (Collection $records, array $data) => self::changed(app(ProductBulkUpdate::class)->set($records, ['brand_id' => $data['brand_id'] ?: null]))),
                self::base('exportSelected', 'Xuất Excel các sản phẩm đã chọn', Heroicon::OutlinedArrowDownTray)
                    ->action(fn (Collection $records) => app(BulkExporter::class)->xlsx(new BulkCatalog(Product::query()->with(['seo', 'images', 'contentSection'])->whereKey($records->pluck('id'))->orderBy('id')->get()), 'san-pham-da-chon-'.date('Ymd-His'))),
                self::base('deleteMany', 'Xóa sản phẩm', Heroicon::OutlinedTrash)->color('danger')->requiresConfirmation()
                    ->modalDescription('Sản phẩm đã có đánh giá hoặc đơn hàng được giữ lại. Nên dùng Ẩn nếu còn muốn xem lại sau.')
                    ->action(function (Collection $records): void {
                        $result = app(ProductBulkUpdate::class)->delete($records);
                        Notification::make()->success()->title('Đã xóa '.$result['deleted'].' sản phẩm')
                            ->body($result['kept'] > 0 ? 'Giữ lại '.$result['kept'].' sản phẩm có đánh giá hoặc đơn hàng.' : null)->send();
                    }),
            ])->label('Phân loại & xóa')->icon(Heroicon::OutlinedFolder),
        ];
    }

    private static function base(string $name, string $label, string|BackedEnum $icon): BulkAction
    {
        return BulkAction::make($name)->label($label)->icon($icon)
            ->visible(fn () => ProductResource::canBulkImport())
            ->authorize(fn () => ProductResource::canBulkImport())
            ->modalHeading(fn (BulkAction $action) => $label.' ('.$action->getSelectedRecords()->count().' sản phẩm)')
            ->deselectRecordsAfterCompletion();
    }

    /** @param array<string, mixed> $values */
    private static function flag(string $name, string $label, string|BackedEnum $icon, array $values, ?string $description = null): BulkAction
    {
        return self::base($name, $label, $icon)->requiresConfirmation()->modalDescription($description)
            ->action(fn (Collection $records) => self::changed(app(ProductBulkUpdate::class)->set($records, $values)));
    }

    private static function rounding(): Select
    {
        return Select::make('round_to')->label('Làm tròn đến')->required()->default(1000)
            ->options([1 => '1 đồng', 1000 => '1.000 đ', 10000 => '10.000 đ', 100000 => '100.000 đ']);
    }

    private static function changed(int $count): void
    {
        Notification::make()->success()->title($count > 0 ? 'Đã cập nhật '.$count.' sản phẩm' : 'Không có sản phẩm nào cần đổi')->send();
    }

    /** @param array{changed: int, skipped: int} $result */
    private static function priceResult(array $result): void
    {
        Notification::make()->success()->title('Đã đổi giá '.$result['changed'].' sản phẩm')
            ->body($result['skipped'] > 0 ? 'Bỏ qua '.$result['skipped'].' sản phẩm chưa có giá.' : null)->send();
    }
}
