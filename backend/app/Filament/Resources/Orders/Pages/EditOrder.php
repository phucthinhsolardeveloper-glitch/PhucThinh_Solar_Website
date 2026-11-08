<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Domain\Commerce\ManualPayment;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\AdminActor;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /** Only the handling fields are written; the order as placed never changes here. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return array_intersect_key($data, array_flip(['status', 'notes']));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirmManualPayment')->label('Xác nhận đã nhận tiền')->icon('heroicon-o-banknotes')
                ->visible(fn (Order $record) => $record->payment_code === 'manual' && $record->payment_status === 'unpaid'
                    && AdminActor::current()->canPerform('orders_payment'))
                ->schema([TextInput::make('reference')->label('Mã giao dịch / phiếu thu')->required()->maxLength(120)])
                ->requiresConfirmation()
                ->modalDescription('Chỉ dùng khi đã kiểm tra tiền hoặc phiếu thu thực tế. Thao tác sẽ lưu lịch sử giao dịch của đơn.')
                ->action(function (Order $record, array $data) {
                    app(ManualPayment::class)->confirm($record, AdminActor::current(), $data['reference']);
                    $this->refreshFormData(['payment_status', 'payment_reference']);
                }),
            Action::make('recordManualRefund')->label('Ghi nhận đã hoàn tiền')->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn (Order $record) => $record->payment_code === 'manual' && $record->payment_status === 'paid'
                    && AdminActor::current()->canPerform('orders_payment'))
                ->schema([TextInput::make('reference')->label('Mã giao dịch / phiếu hoàn tiền')->required()->maxLength(120)])
                ->requiresConfirmation()
                ->modalDescription('Chỉ ghi nhận sau khi đã hoàn đủ tiền bên ngoài hệ thống. Thao tác này không chuyển tiền và không tự hoàn VNPay.')
                ->action(function (Order $record, array $data) {
                    app(ManualPayment::class)->recordRefund($record, AdminActor::current(), $data['reference']);
                    $this->refreshFormData(['payment_status']);
                }),
        ];
    }
}
