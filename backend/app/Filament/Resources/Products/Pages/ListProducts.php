<?php

namespace App\Filament\Resources\Products\Pages;

use App\Domain\Catalog\ProductUndo;
use App\Filament\Concerns\HasInlineEditMode;
use App\Filament\Pages\ProductBulkPage;
use App\Filament\Resources\Products\ProductBulkActions;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    use HasInlineEditMode;

    protected function getHeaderActions(): array
    {
        return [
            $this->inlineEditAction(ProductResource::canBulkImport()),
            Action::make('bulk')->label('Nhập / xuất Excel')->icon('heroicon-o-table-cells')->color('gray')
                ->visible(fn () => ProductResource::canBulkImport())->url(ProductBulkPage::getUrl()),
            CreateAction::make(),
        ];
    }

    /**
     * One tap shows one kind of product. Each count follows the category and brand filters, so "Đang ẩn 7" under a
     * category means seven hidden in that category.
     */
    public function getTabs(): array
    {
        $tabs = [
            'all' => ['Tất cả', null, fn (Builder $query) => $query],
            'visible' => ['Đang hiện', Heroicon::OutlinedEye, fn (Builder $query) => $query->where('is_visible', true)],
            'hidden' => ['Đang ẩn', Heroicon::OutlinedEyeSlash, fn (Builder $query) => $query->where('is_visible', false)],
            // What the visitor sees as "Liên hệ báo giá": price switched off, or no price entered.
            'quote' => ['Báo giá', Heroicon::OutlinedChatBubbleLeftRight, fn (Builder $query) => $query->where(fn (Builder $q) => $q->where('quote_only', true)
                ->orWhere(fn (Builder $q) => $q->whereRaw('COALESCE(regular_price, 0) <= 0')->whereRaw('COALESCE(sale_price, 0) <= 0')))],
            'featured' => ['Nổi bật', Heroicon::OutlinedFire, fn (Builder $query) => $query->where('is_bestseller', true)],
        ];

        return array_map(fn (array $tab) => Tab::make($tab[0])->icon($tab[1])->modifyQueryUsing($tab[2])
            ->badge(fn () => $tab[2]($this->scopedProducts())->count()), $tabs);
    }

    /** @return Builder<Product> */
    private function scopedProducts(): Builder
    {
        $query = Product::query();
        foreach (['category_id', 'brand_id'] as $filter) {
            $value = $this->getTableFilterState($filter)['value'] ?? null;
            if (filled($value)) {
                $query->where($filter, $value);
            }
        }

        return $query;
    }

    #[On(ProductBulkActions::UNDO_EVENT)]
    public function undoProducts(string $token): void
    {
        abort_unless(ProductResource::canBulkImport(), 403);
        $result = app(ProductUndo::class)->restore($token, (int) auth('admin')->id());
        if ($result === null) {
            Notification::make()->warning()->title('Không hoàn tác được')->body('Thao tác này đã được hoàn tác hoặc đã quá 30 phút.')->send();

            return;
        }
        Notification::make()->success()->title('Đã hoàn tác '.$result['restored'].' sản phẩm')
            ->body($result['skipped'] > 0 ? $result['skipped'].' sản phẩm đã được sửa tiếp sau đó nên giữ giá trị mới.' : null)->send();
    }
}
