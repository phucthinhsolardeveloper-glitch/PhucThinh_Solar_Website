<?php

namespace App\Filament\Resources\Products\Pages;

use App\Domain\Catalog\ProductDuplicator;
use App\Domain\Content\SectionTransfer;
use App\Filament\Resources\Products\ProductResource;
use App\Models\ContentSection;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        // Products with orders or reviews are kept for history; hide them instead.
        return [
            Action::make('duplicate')->label('Tạo bản sao')->icon('heroicon-o-square-2-stack')
                ->requiresConfirmation()->modalDescription('Bản sao sẽ ẩn trên website và bỏ mã sản phẩm. Ảnh, tài liệu, SEO và khối nội dung được sao chép để bạn kiểm tra trước khi bật hiển thị.')
                ->action(function (Product $record) {
                    $copy = app(ProductDuplicator::class)->duplicate($record);

                    return redirect(ProductResource::getUrl('edit', ['record' => $copy]));
                }),
            Action::make('htmlToBlocks')->label('Chuyển nội dung thành khối')->icon('heroicon-o-squares-plus')->color('gray')
                ->visible(fn (Product $record) => trim(strip_tags((string) $record->body)) !== '')
                ->modalDescription(fn (Product $record) => self::conversionSummary($record))
                ->schema([Radio::make('placement')->label('Khối mô tả đang có')->required()->default('append')
                    ->options(['append' => 'Thêm vào cuối', 'replace' => 'Thay thế (bản cũ vẫn khôi phục được)'])])
                ->action(function (Product $record, array $data): void {
                    $converted = SectionTransfer::htmlToBlocks((string) $record->body)['blocks'];
                    $section = ContentSection::query()->firstOrNew(['owner_type' => Product::class, 'owner_id' => $record->id]);
                    $current = $data['placement'] === 'append' ? (json_decode((string) ($section->getRawOriginal('blocks') ?: '[]'), true) ?: []) : [];
                    $section->setRawAttributes(array_merge($section->getAttributes(), ['owner_type' => Product::class, 'owner_id' => $record->id,
                        'mode' => in_array($section->getRawOriginal('mode'), ['both'], true) ? 'both' : 'own', 'is_visible' => true,
                        'blocks' => json_encode([...$current, ...$converted], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]));
                    $section->save();
                    Notification::make()->success()->title('Đã thêm '.count($converted).' khối')->send();
                    $this->redirect(ProductResource::getUrl('edit', ['record' => $record]));
                }),
            DeleteAction::make()->hidden(fn (Product $record) => $record->reviews()->exists() || DB::table('order_items')->where('product_id', $record->id)->exists()),
        ];
    }

    private static function conversionSummary(Product $record): string
    {
        $result = SectionTransfer::htmlToBlocks((string) $record->body);
        $types = array_count_values(array_column($result['blocks'], 'type'));
        $labels = ['heading' => 'tiêu đề', 'text' => 'đoạn văn', 'list' => 'danh sách', 'image' => 'ảnh'];
        $parts = array_map(fn ($type, $count) => $count.' '.($labels[$type] ?? $type), array_keys($types), $types);

        return 'Nội dung chi tiết sẽ thành '.count($result['blocks']).' khối ('.implode(', ', $parts).').'
            .($result['skipped_images'] > 0 ? ' Bỏ qua '.$result['skipped_images'].' ảnh không nằm trong kho ảnh sản phẩm.' : '');
    }
}
