<?php

namespace App\Filament\Resources\Products\Pages;

use App\Domain\Catalog\ProductDuplicator;
use App\Filament\Concerns\ReportsSavedChanges;
use App\Filament\Concerns\SavesAndStays;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\PublicLink;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditProduct extends EditRecord
{
    use ReportsSavedChanges, SavesAndStays;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        // Products with orders or reviews are kept for history; hide them instead.
        return [PublicLink::action(),
            Action::make('duplicate')->label('Tạo bản sao')->icon('heroicon-o-square-2-stack')
                ->requiresConfirmation()->modalDescription('Bản sao sẽ ẩn trên website và bỏ mã sản phẩm. Ảnh, tài liệu, SEO và khối nội dung được sao chép để bạn kiểm tra trước khi bật hiển thị.')
                ->action(function (Product $record) {
                    $copy = app(ProductDuplicator::class)->duplicate($record);

                    return redirect(ProductResource::getUrl('edit', ['record' => $copy]));
                }),
            DeleteAction::make()->hidden(fn (Product $record) => $record->reviews()->exists() || DB::table('order_items')->where('product_id', $record->id)->exists()),
        ];
    }
}
