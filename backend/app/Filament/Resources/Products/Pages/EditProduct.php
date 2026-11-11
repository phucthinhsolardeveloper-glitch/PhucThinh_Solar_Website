<?php

namespace App\Filament\Resources\Products\Pages;

use App\Domain\Catalog\ProductDetail\ProductDetailAssembler;
use App\Domain\Catalog\ProductDuplicator;
use App\Domain\Content\PublishChecks;
use App\Filament\Concerns\ReportsSavedChanges;
use App\Filament\Concerns\SavesAndStays;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\PublicLink;
use App\Filament\Support\PublishGate;
use App\Filament\Support\TypedSpecsForm;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditProduct extends EditRecord
{
    use ReportsSavedChanges, SavesAndStays;

    protected static string $resource = ProductResource::class;

    protected bool $wasVisible = false;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return TypedSpecsForm::fill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        return TypedSpecsForm::save($data, $record instanceof Product ? $record : null);
    }

    protected function beforeSave(): void
    {
        $this->wasVisible = (bool) $this->getRecord()->getOriginal('is_visible');
    }

    protected function afterSave(): void
    {
        PublishGate::afterSave($this->getRecord(), $this->wasVisible);
    }

    /** Under the title: is this product ready for the website, and what is missing (the publish checks). */
    public function getSubheading(): ?string
    {
        $record = $this->getRecord();
        if (! $record instanceof Product) {
            return null;
        }
        $checks = app(PublishChecks::class)->product($record);
        $state = $record->is_visible ? 'Đang hiện trên website' : 'Đang ẩn';
        if ($checks['errors'] !== []) {
            return $state.' · Chưa đủ điều kiện hiện: '.implode(', ', $checks['errors']).'.'.($checks['warnings'] !== [] ? ' Nên bổ sung: '.implode(', ', array_slice($checks['warnings'], 0, 3)).'.' : '');
        }

        return $state.' · '.($checks['warnings'] === [] ? 'Đủ dữ liệu.' : 'Đủ điều kiện hiện. Nên bổ sung: '.implode(', ', array_slice($checks['warnings'], 0, 3)).'.');
    }

    protected function getHeaderActions(): array
    {
        // Products with orders or reviews are kept for history; hide them instead.
        return [PublicLink::action(),
            Action::make('preview')->label('Xem trước')->icon('heroicon-o-eye')->color('gray')
                ->tooltip('Trang sản phẩm theo bản đã lưu, kể cả khi đang ẩn. Liên kết dùng được 15 phút.')
                ->url(fn (Product $record) => ProductDetailAssembler::previewUrl((int) $record->getKey()), shouldOpenInNewTab: true),
            ActionGroup::make([
                Action::make('duplicate')->label('Tạo bản sao')->color('gray')->icon('heroicon-o-square-2-stack')
                    ->requiresConfirmation()->modalDescription('Bản sao sẽ ẩn trên website và bỏ mã sản phẩm. Ảnh, tài liệu, SEO và khối nội dung được sao chép để bạn kiểm tra trước khi bật hiển thị.')
                    ->action(function (Product $record) {
                        $copy = app(ProductDuplicator::class)->duplicate($record);

                        return redirect(ProductResource::getUrl('edit', ['record' => $copy]));
                    }),
                DeleteAction::make()->hidden(fn (Product $record) => $record->reviews()->exists() || DB::table('order_items')->where('product_id', $record->id)->exists()),
            ])->label('Thêm')->icon('heroicon-m-ellipsis-horizontal')->button()->color('gray'),
        ];
    }
}
