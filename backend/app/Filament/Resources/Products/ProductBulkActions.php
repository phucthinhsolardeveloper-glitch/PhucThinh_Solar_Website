<?php

namespace App\Filament\Resources\Products;

use App\Domain\Catalog\Bulk\BulkCatalog;
use App\Domain\Catalog\Bulk\BulkExporter;
use App\Domain\Catalog\ProductBulkUpdate;
use App\Domain\Catalog\ProductUndo;
use App\Domain\Content\PublishChecks;
use App\Domain\Shipping\Warehouses;
use App\Filament\Support\Options;
use App\Models\Brand;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/** What can be done to many ticked products at once on the product list. */
final class ProductBulkActions
{
    /** Browser event the "Hoàn tác" button raises; the product list answers it. */
    public const UNDO_EVENT = 'pts-product-undo';

    /** @return list<BulkActionGroup> */
    public static function groups(): array
    {
        return [
            BulkActionGroup::make([
                self::base('show', 'Hiện trên website', Heroicon::OutlinedEye)->color('success')->requiresConfirmation()
                    ->modalDescription(fn (BulkAction $action) => self::preview($action, 'is_visible', true, 'đang ẩn sẽ được hiện', 'đang hiện sẵn')
                        .' Sản phẩm chưa đủ điều kiện (thiếu ảnh, chưa có danh mục, giá sai…) được giữ ẩn và liệt kê lý do.')
                    ->modalSubmitActionLabel(fn (BulkAction $action) => 'Hiện '.self::pending($action, 'is_visible', true).' sản phẩm')
                    ->modalSubmitAction(fn (Action $action, BulkAction $bulk) => self::pending($bulk, 'is_visible', true) > 0 ? $action : false)
                    ->action(fn (Collection $records) => self::showReady($records)),
                self::flag('hide', 'Ẩn khỏi website', Heroicon::OutlinedEyeSlash, 'is_visible', false,
                    ['đang hiện sẽ bị ẩn', 'đang ẩn sẵn'], 'Khách mở link sẽ thấy "sản phẩm đã ngừng hiển thị"; sản phẩm rời danh sách, tìm kiếm và Google Shopping.',
                    'Ẩn %d sản phẩm', 'Đã ẩn %d sản phẩm khỏi website')->color('danger'),
                BulkActionGroup::make([
                    self::flag('bestsellerOn', 'Đánh dấu Nổi bật', Heroicon::OutlinedFire, 'is_bestseller', true,
                        ['sẽ được gắn nhãn Nổi bật', 'đã Nổi bật sẵn'], 'Chỉ nên chọn một ít sản phẩm để nhãn còn ý nghĩa.',
                        'Đánh dấu %d sản phẩm', 'Đã đánh dấu Nổi bật %d sản phẩm'),
                    self::flag('bestsellerOff', 'Bỏ Nổi bật', Heroicon::OutlinedMinusCircle, 'is_bestseller', false,
                        ['sẽ bỏ nhãn Nổi bật', 'không có nhãn sẵn'], '', 'Bỏ nhãn %d sản phẩm', 'Đã bỏ Nổi bật %d sản phẩm'),
                ])->dropdown(false),
            ])->label('Hiện / Ẩn')->icon(Heroicon::OutlinedEye)->dropdownWidth(Width::ExtraSmall),
            BulkActionGroup::make([
                self::flag('quoteOn', 'Ẩn giá (Liên hệ báo giá)', Heroicon::OutlinedChatBubbleLeftRight, 'quote_only', true,
                    ['sẽ ẩn giá', 'đang ẩn giá sẵn'], 'Giá và nút mua đổi thành "Liên hệ báo giá", sản phẩm rời Google Shopping. Giá đã nhập vẫn được giữ.',
                    'Ẩn giá %d sản phẩm', 'Đã ẩn giá %d sản phẩm'),
                self::flag('quoteOff', 'Hiện giá cho khách', Heroicon::OutlinedCurrencyDollar, 'quote_only', false,
                    ['sẽ hiện giá', 'đang hiện giá sẵn'], 'Sản phẩm chưa nhập giá vẫn hiện "Liên hệ báo giá".',
                    'Hiện giá %d sản phẩm', 'Đã hiện giá %d sản phẩm'),
                BulkActionGroup::make([
                    self::base('adjustPrices', 'Tăng / giảm giá theo %', Heroicon::OutlinedArrowTrendingUp)
                        ->modalDescription('Áp dụng cho giá gốc và giá khuyến mãi. Sản phẩm chưa có giá được bỏ qua.')
                        ->schema([
                            TextInput::make('percent')->label('Phần trăm thay đổi')->numeric()->required()->minValue(-90)->maxValue(500)->suffix('%')
                                ->helperText('Số dương để tăng, số âm để giảm. Ví dụ -10 là giảm 10%.'),
                            self::rounding(),
                        ])
                        ->action(fn (Collection $records, array $data) => self::priceChange($records,
                            fn () => app(ProductBulkUpdate::class)->adjustPrices($records, (float) $data['percent'], (int) $data['round_to']))),
                    self::base('setDiscount', 'Đặt giá khuyến mãi theo %', Heroicon::OutlinedReceiptPercent)
                        ->modalDescription('Giá khuyến mãi = giá gốc trừ đi phần trăm này.')
                        ->schema([
                            TextInput::make('percent')->label('Giảm so với giá gốc')->numeric()->required()->minValue(1)->maxValue(90)->suffix('%'),
                            self::rounding(),
                        ])
                        ->action(fn (Collection $records, array $data) => self::priceChange($records,
                            fn () => app(ProductBulkUpdate::class)->setDiscount($records, (float) $data['percent'], (int) $data['round_to']))),
                    self::base('clearDiscount', 'Xóa giá khuyến mãi', Heroicon::OutlinedXCircle)->requiresConfirmation()
                        ->action(fn (Collection $records) => self::undoable($records, ['sale_price'],
                            fn () => app(ProductBulkUpdate::class)->set($records, ['sale_price' => 0]), 'Đã xóa giá khuyến mãi %d sản phẩm')),
                ])->dropdown(false),
            ])->label('Giá')->icon(Heroicon::OutlinedBanknotes)->dropdownWidth(Width::ExtraSmall),
            BulkActionGroup::make([
                self::base('moveCategory', 'Chuyển danh mục', Heroicon::OutlinedFolderArrowDown)
                    ->schema([Select::make('category_id')->label('Danh mục mới')->options(fn () => Options::categoryPaths())->searchable()->required()])
                    ->action(fn (Collection $records, array $data) => self::undoable($records, ['category_id'],
                        fn () => app(ProductBulkUpdate::class)->set($records, ['category_id' => (int) $data['category_id']]), 'Đã chuyển danh mục %d sản phẩm')),
                self::base('setBrand', 'Đổi thương hiệu', Heroicon::OutlinedTag)
                    ->schema([Select::make('brand_id')->label('Thương hiệu')->options(fn () => Brand::query()->orderBy('name')->pluck('name', 'id')->all())->searchable()->placeholder('Bỏ thương hiệu')])
                    ->action(fn (Collection $records, array $data) => self::undoable($records, ['brand_id'],
                        fn () => app(ProductBulkUpdate::class)->set($records, ['brand_id' => $data['brand_id'] ?: null]), 'Đã đổi thương hiệu %d sản phẩm')),
                self::base('exportSelected', 'Xuất Excel các sản phẩm đã chọn', Heroicon::OutlinedArrowDownTray)
                    ->action(fn (Collection $records) => app(BulkExporter::class)->xlsx(new BulkCatalog(Product::query()->with(['seo', 'images', 'contentSection'])->whereKey($records->pluck('id'))->orderBy('id')->get()), 'san-pham-da-chon-'.date('Ymd-His'))),
                self::base('deleteMany', 'Xóa sản phẩm', Heroicon::OutlinedTrash)->color('danger')->requiresConfirmation()
                    ->modalDescription('Sản phẩm đã có đánh giá hoặc đơn hàng được giữ lại. Nên dùng Ẩn nếu còn muốn xem lại sau.')
                    ->action(function (Collection $records): void {
                        $result = app(ProductBulkUpdate::class)->delete($records);
                        Notification::make()->success()->title('Đã xóa '.$result['deleted'].' sản phẩm')
                            ->body($result['kept'] > 0 ? 'Giữ lại '.$result['kept'].' sản phẩm có đánh giá hoặc đơn hàng.' : null)->send();
                    }),
                self::base('setWarehouse', 'Gán kho giữ hàng', Heroicon::OutlinedBuildingStorefront)
                    ->modalDescription('Kho này sẽ là nơi xuất hàng khi khách đặt các sản phẩm đã chọn. Chọn các sản phẩm cùng một kho rồi gán một lần.')
                    ->schema([Select::make('warehouse_id')->label('Kho giữ hàng')->options(fn () => app(Warehouses::class)->options())->placeholder('Bỏ kho (chưa gán)')])
                    ->action(fn (Collection $records, array $data) => self::undoable($records, ['warehouse_id'],
                        fn () => app(ProductBulkUpdate::class)->set($records, ['warehouse_id' => $data['warehouse_id'] ?: null]), 'Đã gán kho %d sản phẩm')),
                self::base('setWeight', 'Điền cân nặng đóng gói', Heroicon::OutlinedScale)
                    ->modalDescription('Dùng để tính phí giao hàng lúc khách đặt và cân nặng vận đơn. Chọn các sản phẩm cùng cỡ (ví dụ cùng dòng đèn 100W) rồi điền một lần.')
                    ->schema([
                        TextInput::make('kg')->label('Cân nặng khi đóng gói (kg / sản phẩm)')->numeric()->required()->minValue(0.01)->maxValue(5000),
                        Select::make('only_empty')->label('Áp dụng cho')->required()->default('empty')
                            ->options(['empty' => 'Chỉ sản phẩm chưa có cân nặng', 'all' => 'Tất cả sản phẩm đã chọn (ghi đè)']),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        self::undoable($records, ['weight_kg'], function () use ($records, $data): void {
                            foreach ($records as $product) {
                                if ($product instanceof Product && ($data['only_empty'] === 'all' || (float) $product->weight_kg <= 0)) {
                                    $product->update(['weight_kg' => (float) $data['kg']]);
                                }
                            }
                        }, 'Đã điền cân nặng %d sản phẩm');
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

    /**
     * A yes/no switch for the ticked products: the dialog says how many will really change, the notice says what
     * happened and offers to take it back.
     *
     * @param  array{0: string, 1: string}  $counts  what to say about the ones that change and the ones already so
     */
    private static function flag(string $name, string $label, string|BackedEnum $icon, string $column, bool $value, array $counts, string $effect, string $submit, string $done): BulkAction
    {
        return self::base($name, $label, $icon)->requiresConfirmation()
            ->modalDescription(fn (BulkAction $action) => trim(self::preview($action, $column, $value, ...$counts).' '.$effect))
            ->modalSubmitActionLabel(fn (BulkAction $action) => sprintf($submit, self::pending($action, $column, $value)))
            ->modalSubmitAction(fn (Action $action, BulkAction $bulk) => self::pending($bulk, $column, $value) > 0 ? $action : false)
            ->action(fn (Collection $records) => self::undoable($records, [$column],
                fn () => app(ProductBulkUpdate::class)->set($records, [$column => $value]), $done,
                $records->filter(fn (Product $product) => (bool) $product->{$column} === $value)->count(), $counts[1],
                $name === 'quoteOff' ? self::unpriced($records) : null));
    }

    /** How many of the ticked products the switch would really change. */
    private static function pending(BulkAction $action, string $column, bool $value): int
    {
        return $action->getSelectedRecords()->filter(fn (Product $product) => (bool) $product->{$column} !== $value)->count();
    }

    private static function preview(BulkAction $action, string $column, bool $value, string $changing, string $already): string
    {
        $total = $action->getSelectedRecords()->count();
        $pending = self::pending($action, $column, $value);

        return $pending === 0
            ? 'Không có gì để đổi: cả '.$total.' sản phẩm đã chọn '.$already.'.'
            : $pending.' sản phẩm '.$changing.($total > $pending ? ', '.($total - $pending).' '.$already.' (giữ nguyên)' : '').'.';
    }

    private static function unpriced(Collection $records): ?string
    {
        $count = $records->filter(fn (Product $product) => (float) $product->regular_price <= 0 && (float) $product->sale_price <= 0)->count();

        return $count > 0 ? $count.' sản phẩm chưa có giá nên vẫn hiện "Liên hệ báo giá".' : null;
    }

    /** @param list<string> $columns */
    private static function undoable(Collection $records, array $columns, \Closure $change, string $done, int $same = 0, string $already = '', ?string $note = null): void
    {
        $step = app(ProductUndo::class)->record($records, $columns, $change, (int) auth('admin')->id());
        if ($step['token'] === null) {
            Notification::make()->info()->title('Không có sản phẩm nào cần đổi')->body($note)->send();

            return;
        }
        $body = implode(' ', array_filter([$same > 0 ? $same.' sản phẩm '.$already.'.' : null, $note]));
        self::notice(sprintf($done, $step['changed']), $body, $step['token'])->success()->send();
    }

    private static function notice(string $title, string $body, ?string $token): Notification
    {
        return Notification::make()->title($title)->body($body === '' ? null : $body)->seconds(20)
            ->actions($token === null ? [] : [Action::make('undo')->label('Hoàn tác')->button()->color('gray')
                ->icon(Heroicon::OutlinedArrowUturnLeft)->dispatch(self::UNDO_EVENT, ['token' => $token])->close()]);
    }

    /** Bulk "Hiện": the same publish checks as one product; the ones not ready stay hidden and are named. */
    private static function showReady(Collection $records): void
    {
        $held = [];
        $already = $records->filter(fn (Product $product) => (bool) $product->is_visible)->count();
        $step = app(ProductUndo::class)->record($records, ['is_visible'], function () use ($records, &$held): void {
            foreach ($records as $product) {
                if (! $product instanceof Product || $product->is_visible) {
                    continue;
                }
                $errors = app(PublishChecks::class)->product($product)['errors'];
                if ($errors !== []) {
                    $held[] = $product->name.': '.implode(', ', $errors);

                    continue;
                }
                $product->update(['is_visible' => true]);
            }
        }, (int) auth('admin')->id());

        $body = $already > 0 ? $already.' sản phẩm đang hiện sẵn.' : '';
        if ($held !== []) {
            $body .= "\n".count($held)." sản phẩm chưa hiện được:\n· ".implode("\n· ", array_slice($held, 0, 12)).(count($held) > 12 ? "\n…" : '');
        }
        $note = self::notice('Đã hiện '.$step['changed'].' sản phẩm', trim($body), $step['token'])->{$held === [] ? 'success' : 'warning'}();
        if ($held !== []) {
            $note->persistent();
        }
        $note->send();
    }

    private static function rounding(): Select
    {
        return Select::make('round_to')->label('Làm tròn đến')->required()->default(1000)
            ->options([1 => '1 đồng', 1000 => '1.000 đ', 10000 => '10.000 đ', 100000 => '100.000 đ']);
    }

    /** @param \Closure(): array{changed: int, skipped: int} $change */
    private static function priceChange(Collection $records, \Closure $change): void
    {
        $skipped = 0;
        $step = app(ProductUndo::class)->record($records, ['regular_price', 'sale_price'], function () use ($change, &$skipped): void {
            $skipped = $change()['skipped'];
        }, (int) auth('admin')->id());
        $hidden = $records->filter(fn (Product $product) => (bool) $product->quote_only)->count();
        $body = implode(' ', array_filter([
            $skipped > 0 ? 'Bỏ qua '.$skipped.' sản phẩm chưa có giá.' : null,
            $hidden > 0 ? $hidden.' sản phẩm đã chọn đang ẩn giá, khách chưa thấy giá mới.' : null,
        ]));
        self::notice('Đã đổi giá '.$step['changed'].' sản phẩm', $body, $step['token'])->success()->send();
    }
}
