<?php

namespace App\Filament\Resources\Products\Pages;

use App\Domain\Catalog\ProductPriceCsv;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPrices')->label('Xuất CSV giá')->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => app(ProductPriceCsv::class)->export()),
            Action::make('previewPrices')->label('Nhập CSV giá')->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('Xem trước cập nhật giá sản phẩm')
                ->modalDescription('Xuất CSV mẫu, chỉnh Giá bán, Giá khuyến mãi, Chỉ báo giá hoặc Hiển thị. Ô trống giữ nguyên; nhập XÓA ở Giá khuyến mãi để bỏ giá này. ID, mã và tên chỉ để đối chiếu.')
                ->schema([
                    FileUpload::make('file')->label('Tệp CSV')->disk('local')->directory('bulk-product-prices')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->maxSize(2048)->required(),
                ])
                ->action(function (array $data): void {
                    abort_unless(ProductResource::canViewAny(), 403);
                    $relative = $data['file'];
                    $path = $this->csvPath($relative);
                    $result = app(ProductPriceCsv::class)->preview($path);
                    $previous = session('product_price_csv_preview.path');
                    if (is_string($previous) && $previous !== $relative && preg_match('#^bulk-product-prices/[A-Za-z0-9._-]+\.csv$#', $previous)) {
                        Storage::disk('local')->delete($previous);
                    }
                    session()->put('product_price_csv_preview', [
                        'path' => $relative, 'hash' => hash_file('sha256', $path),
                        'fingerprint' => $result['fingerprint'], 'at' => now()->timestamp,
                        'total' => $result['total'], 'changed' => $result['changed'], 'skipped' => $result['skipped'],
                        'errors' => $result['errors'],
                    ]);
                    $message = "{$result['total']} dòng: {$result['changed']} cần sửa, {$result['skipped']} không đổi, ".count($result['errors']).' lỗi.';
                    if ($result['errors'] !== []) {
                        $message .= ' '.collect($result['errors'])->take(5)->map(fn ($error, $line) => "Dòng {$line}: {$error}")->implode(' ');
                    }
                    $notification = Notification::make()->title('Kết quả xem trước CSV')->body($message)->persistent();
                    ($result['errors'] === [] ? $notification->success() : $notification->danger())->send();
                }),
            Action::make('downloadPricePreview')->label('Tải kết quả xem trước')->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    abort_unless(ProductResource::canViewAny(), 403);
                    $preview = session('product_price_csv_preview');
                    if (! is_array($preview) || now()->timestamp - ($preview['at'] ?? 0) > 900) {
                        throw ValidationException::withMessages(['file' => 'Bản xem trước đã hết hạn. Hãy nhập CSV lại.']);
                    }
                    $path = $this->csvPath($preview['path'] ?? null);
                    if (! is_file($path) || ! hash_equals($preview['hash'], hash_file('sha256', $path))) {
                        throw ValidationException::withMessages(['file' => 'CSV đã thay đổi. Hãy nhập lại.']);
                    }

                    return app(ProductPriceCsv::class)->report(app(ProductPriceCsv::class)->preview($path));
                }),
            Action::make('applyPrices')->label('Áp dụng CSV đã xem trước')->icon('heroicon-o-check-circle')
                ->color('warning')->requiresConfirmation()
                ->modalHeading('Xác nhận cập nhật giá sản phẩm')
                ->modalDescription(function (): string {
                    $preview = session('product_price_csv_preview');
                    if (! is_array($preview)) {
                        return 'Chưa có CSV hợp lệ. Hãy dùng Nhập CSV giá trước.';
                    }

                    return "{$preview['total']} dòng: {$preview['changed']} cần sửa, {$preview['skipped']} không đổi, ".count($preview['errors']).' lỗi. Chỉ áp dụng nếu không có lỗi và dữ liệu chưa thay đổi.';
                })
                ->action(function (): void {
                    abort_unless(ProductResource::canViewAny(), 403);
                    $preview = session('product_price_csv_preview');
                    if (! is_array($preview) || now()->timestamp - ($preview['at'] ?? 0) > 900 || ($preview['errors'] ?? []) !== []) {
                        throw ValidationException::withMessages(['file' => 'Bản xem trước không hợp lệ hoặc đã quá 15 phút. Hãy nhập CSV lại.']);
                    }
                    $relative = $preview['path'] ?? null;
                    $path = $this->csvPath($relative);
                    if (! is_file($path) || ! hash_equals($preview['hash'], hash_file('sha256', $path))) {
                        throw ValidationException::withMessages(['file' => 'CSV đã thay đổi. Hãy nhập lại.']);
                    }
                    $result = app(ProductPriceCsv::class)->apply($path, $preview['fingerprint']);
                    Storage::disk('local')->delete($relative);
                    session()->forget('product_price_csv_preview');
                    Notification::make()->title("Đã cập nhật {$result['changed']} sản phẩm")
                        ->body("{$result['skipped']} sản phẩm giữ nguyên.")->success()->send();
                }),
            CreateAction::make(),
        ];
    }

    private function csvPath(mixed $relative): string
    {
        if (! is_string($relative) || ! preg_match('#^bulk-product-prices/[A-Za-z0-9._-]+\.csv$#', $relative)) {
            throw ValidationException::withMessages(['file' => 'Đường dẫn CSV không hợp lệ.']);
        }

        return Storage::disk('local')->path($relative);
    }
}
