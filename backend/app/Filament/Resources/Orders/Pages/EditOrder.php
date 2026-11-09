<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Domain\Commerce\BankTransfer;
use App\Domain\Commerce\ManualPayment;
use App\Domain\Commerce\OrderLifecycle;
use App\Domain\Shipping\NhatTin\NtlClient;
use App\Domain\Shipping\NhatTin\NtlException;
use App\Domain\Shipping\NhatTin\NtlStatus;
use App\Domain\Shipping\Shipments;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\AdminActor;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Model;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /** Only status and notes change here, through the lifecycle rules and the audit log. */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Order $record */
        app(OrderLifecycle::class)->update($record, (string) $data['status'], $data['notes'] ?? null);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirmManualPayment')->label('Xác nhận đã nhận tiền')->icon('heroicon-o-banknotes')
                ->visible(fn (Order $record) => $record->payment_code === 'manual' && $record->payment_status === 'unpaid'
                    && AdminActor::current()->canPerform('orders_payment'))
                ->schema([
                    TextInput::make('reference')->label('Mã giao dịch / phiếu thu')->required()->maxLength(120),
                    TextInput::make('amount')->label('Số tiền đã nhận (đ)')->numeric()->required()
                        ->default(fn (Order $record) => (int) $record->total)
                        ->helperText('Lấy từ sao kê tài khoản hoặc phiếu thu, không lấy từ ảnh khách gửi.'),
                ])
                ->requiresConfirmation()
                ->modalDescription(fn (Order $record) => 'Chỉ xác nhận khi đã thấy tiền vào tài khoản hoặc phiếu thu thực tế.'
                    .(($i = app(BankTransfer::class)->active((int) $record->id)) ? ' Đối chiếu nội dung chuyển khoản: '.$i->reference.'.' : ''))
                ->action(function (Order $record, array $data) {
                    app(ManualPayment::class)->confirm($record, AdminActor::current(), $data['reference'], (int) $data['amount']);
                    $this->refreshFormData(['payment_status', 'payment_reference']);
                }),
            ActionGroup::make([
                Action::make('ntlQuote')->label('Báo giá Nhất Tín')->icon('heroicon-o-calculator')
                    ->schema(fn () => self::parcelFields())
                    ->modalSubmitActionLabel('Xem cước')
                    ->action(function (Order $record, array $data): void {
                        try {
                            $quotes = app(Shipments::class)->quote($record, $data);
                        } catch (NtlException $error) {
                            Notification::make()->danger()->title('Chưa báo giá được')->body($error->getMessage())->persistent()->send();

                            return;
                        }
                        $mode = app(NtlClient::class)->mode();
                        Notification::make()->info()->persistent()->title('Cước Nhất Tín'.($mode === 'mock' ? ' (mô phỏng, không phải giá thật)' : ''))
                            ->body(collect($quotes)->map(fn ($q) => $q['service'].': '.number_format($q['fee'], 0, ',', '.').'đ'.($q['lead_time'] ? ' · '.$q['lead_time'] : ''))->implode("\n") ?: 'Không có phương án.')->send();
                    }),
                Action::make('ntlCreate')->label('Tạo vận đơn Nhất Tín')->icon('heroicon-o-truck')
                    ->visible(fn (Order $record) => ! $record->shipments()->whereIn('status', Shipments::ACTIVE)->exists())
                    ->modalDescription(fn (Order $record) => (app(NtlClient::class)->mode() === 'mock' ? 'Chế độ mô phỏng: không gửi sang Nhất Tín. ' : '')
                        .'Tạo vận đơn nháp cho đơn '.$record->code.'. Kiểm tra cân nặng, kích thước và tiền thu hộ trước khi gửi.')
                    ->schema(fn (Order $record) => [
                        ...self::parcelFields(),
                        Select::make('service_id')->label('Dịch vụ')->required()->default('91')->options(config('shipping.nhattin.services')),
                        TextInput::make('cod_amount')->label('Tiền thu hộ (đ)')->numeric()->minValue(0)->default(fn () => app(Shipments::class)->codFor($record))
                            ->helperText('Đơn thu tiền khi nhận hàng và chưa thanh toán: thu đúng tổng đơn. Đơn đã chuyển khoản: 0.'),
                        TextInput::make('note')->label('Ghi chú cho bưu tá')->maxLength(200),
                    ])
                    ->action(function (Order $record, array $data): void {
                        app(Shipments::class)->create($record, $data, AdminActor::current());
                        Notification::make()->success()->title('Đã tạo vận đơn')->send();
                        $this->redirect(OrderResource::getUrl('edit', ['record' => $record]));
                    }),
                Action::make('ntlTrack')->label('Cập nhật hành trình')->icon('heroicon-o-arrow-path')
                    ->visible(fn (Order $record) => $record->shipments()->whereNotNull('bill_code')->exists())
                    ->action(function (Order $record): void {
                        try {
                            $shipment = app(Shipments::class)->refresh($record->shipments()->whereNotNull('bill_code')->firstOrFail());
                            Notification::make()->success()->title(NtlStatus::LABELS[$shipment->status] ?? $shipment->status)->send();
                        } catch (NtlException $error) {
                            Notification::make()->danger()->title('Chưa lấy được hành trình')->body($error->getMessage())->send();
                        }
                        $this->redirect(OrderResource::getUrl('edit', ['record' => $record]));
                    }),
                Action::make('ntlReconcile')->label('Đối chiếu vận đơn chưa rõ')->icon('heroicon-o-question-mark-circle')->color('warning')
                    ->visible(fn (Order $record) => $record->shipments()->whereNull('bill_code')->whereIn('status', ['unknown', 'create_pending'])->exists())
                    ->modalDescription(fn (Order $record) => 'Nhất Tín không trả lời kịp nên chưa biết vận đơn đã được tạo hay chưa. Tìm mã tham chiếu '
                        .$record->shipments()->whereNull('bill_code')->whereIn('status', ['unknown', 'create_pending'])->value('ref_code')
                        .' trên cổng Nhất Tín. Có vận đơn: nhập mã vận đơn. Không có: để trống để đóng lại và tạo vận đơn mới.')
                    ->schema([TextInput::make('bill_code')->label('Mã vận đơn trên cổng Nhất Tín')->maxLength(40)])
                    ->action(function (Order $record, array $data): void {
                        $shipment = app(Shipments::class)->reconcile($record->shipments()->whereNull('bill_code')->whereIn('status', ['unknown', 'create_pending'])->firstOrFail(),
                            $data['bill_code'] ?? null, AdminActor::current());
                        Notification::make()->success()->title($shipment->bill_code ? 'Đã gắn vận đơn '.$shipment->bill_code : 'Đã đóng yêu cầu, có thể tạo vận đơn mới')->send();
                        $this->redirect(OrderResource::getUrl('edit', ['record' => $record]));
                    }),
                Action::make('ntlCancel')->label('Hủy vận đơn')->icon('heroicon-o-x-circle')->color('danger')->requiresConfirmation()
                    ->visible(fn (Order $record) => $record->shipments()->whereIn('status', ['draft', 'waiting_pickup', 'failed'])->exists())
                    ->action(function (Order $record): void {
                        app(Shipments::class)->cancel($record->shipments()->whereIn('status', ['draft', 'waiting_pickup', 'failed'])->firstOrFail(), AdminActor::current());
                        Notification::make()->success()->title('Đã hủy vận đơn')->send();
                        $this->redirect(OrderResource::getUrl('edit', ['record' => $record]));
                    }),
            ])->label('Vận chuyển')->icon('heroicon-o-truck')->button()->color('gray'),
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

    /** @return array<int, mixed> */
    private static function parcelFields(): array
    {
        return [
            TextInput::make('weight_kg')->label('Cân nặng (kg)')->numeric()->required()->minValue(0.1)->maxValue(1000)->default(1),
            Grid::make(3)->schema([
                TextInput::make('length_cm')->label('Dài (cm)')->numeric()->minValue(0)->default(0),
                TextInput::make('width_cm')->label('Rộng (cm)')->numeric()->minValue(0)->default(0),
                TextInput::make('height_cm')->label('Cao (cm)')->numeric()->minValue(0)->default(0),
            ]),
            Select::make('payer_id')->label('Ai trả cước')->required()->default('10')->options(config('shipping.nhattin.payers')),
        ];
    }
}
