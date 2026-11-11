<?php

namespace App\Filament\Resources\Orders;

use App\Domain\Commerce\BankTransfer;
use App\Domain\Commerce\CustomerHistory;
use App\Domain\Commerce\OrderExporter;
use App\Domain\Commerce\OrderLifecycle;
use App\Domain\Shipping\NhatTin\NtlException;
use App\Domain\Shipping\NhatTin\NtlStatus;
use App\Domain\Shipping\ShipmentAutomation;
use App\Domain\Shipping\Shipments;
use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\AdminActor;
use App\Models\Order;
use App\Models\Shipment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;
use UnitEnum;

/** Orders placed on the site. Staff change the status and notes; what the customer ordered stays as placed. */
class OrderResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'orders';

    protected static ?string $model = Order::class;

    protected static ?string $modelLabel = 'đơn hàng';

    protected static ?string $pluralModelLabel = 'Đơn hàng';

    protected static ?string $recordTitleAttribute = 'code';

    protected static string|UnitEnum|null $navigationGroup = 'Bán hàng';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public const STATUSES = ['new' => 'Mới', 'confirmed' => 'Đã xác nhận', 'shipping' => 'Đang giao', 'done' => 'Hoàn tất', 'cancelled' => 'Đã hủy'];

    public const PAYMENT = ['unpaid' => 'Chưa thanh toán', 'paid' => 'Đã thanh toán', 'refunded' => 'Đã hoàn tiền'];

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'customer_name', 'phone', 'email'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Order $record */
        return ['Khách' => $record->customer_name.' · '.$record->phone, 'Trạng thái' => self::STATUSES[$record->status] ?? $record->status];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $new = Order::query()->where('status', 'new')->count();

        return $new > 0 ? (string) $new : null;
    }

    private static function money(mixed $value): string
    {
        return number_format((float) $value, 0, ',', '.').'đ';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Xử lý')->columnSpan(1)->schema([
                Select::make('status')->label('Trạng thái')->required()
                    ->options(fn (?Order $record) => $record ? OrderLifecycle::statusChoices((string) $record->status, self::STATUSES) : self::STATUSES),
                Select::make('payment_status')->label('Thanh toán')->options(self::PAYMENT)->disabled()->dehydrated(false)
                    ->helperText('Xác nhận thanh toán thủ công bằng thao tác riêng. VNPay chỉ cập nhật qua callback hợp lệ.'),
                Textarea::make('notes')->label('Ghi chú nội bộ')->rows(4),
            ]),
            Section::make('Khách hàng')->columnSpan(2)->columns(2)->schema([
                TextInput::make('code')->label('Mã đơn')->disabled(),
                TextInput::make('created_at')->label('Đặt lúc')->disabled()->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('H:i d/m/Y') : ''),
                TextInput::make('customer_name')->label('Họ tên')->disabled(),
                TextInput::make('phone')->label('Điện thoại')->disabled(),
                TextInput::make('email')->label('Email')->disabled(),
                TextInput::make('address')->label('Địa chỉ giao')->disabled(),
                TextInput::make('payment_method')->label('Hình thức thanh toán')->disabled()->dehydrated(false)
                    ->formatStateUsing(fn (?Order $record) => $record?->paymentMethod->name ?? ($record?->payment_code === 'vnpay' ? 'VNPay' : '')),
                TextInput::make('payment_reference')->label('Mã giao dịch')->disabled(),
                TextInput::make('transfer')->label('Chuyển khoản cần nhận')->disabled()->dehydrated(false)->columnSpanFull()
                    ->visible(fn (?Order $record) => $record && app(BankTransfer::class)->active((int) $record->id) !== null)
                    ->formatStateUsing(function (?Order $record) {
                        $i = $record ? app(BankTransfer::class)->active((int) $record->id) : null;

                        return $i ? self::money($i->amount).' · nội dung '.$i->reference.' · '.$i->bank_name.' '.$i->account_no
                            .($record->transfer_reported_at ? ' · khách báo đã chuyển lúc '.Carbon::parse($record->transfer_reported_at)->format('H:i d/m') : '') : '';
                    }),
                Textarea::make('requirements')->label('Yêu cầu của khách')->disabled()->columnSpanFull(),
            ]),
            Section::make('Sản phẩm')->columnSpanFull()->schema([
                Repeater::make('items')->hiddenLabel()->relationship()->disabled()->addable(false)->deletable(false)->reorderable(false)
                    ->schema([
                        TextInput::make('name')->label('Sản phẩm')->columnSpan(3),
                        TextInput::make('quantity')->label('SL'),
                        TextInput::make('unit')->label('Đơn giá')->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record ? self::money($record->sale_price ?: $record->regular_price) : ''),
                        TextInput::make('options')->label('Màu / cỡ')->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record ? trim(($record->color ?? '').' '.($record->size ?? '')) : ''),
                    ])->columns(6),
                TextInput::make('totals')->label('Tạm tính · phí giao · tổng cộng')->disabled()->dehydrated(false)
                    ->formatStateUsing(fn (?Order $record) => $record ? self::money($record->subtotal).' · '.self::money($record->shipping_fee).' · '.self::money($record->total) : ''),
            ]),
            Section::make('Vận chuyển')->columnSpanFull()->visible(fn (?Order $record) => $record?->shipments()->exists() ?? false)->schema([
                Repeater::make('shipments')->hiddenLabel()->relationship()->disabled()->addable(false)->deletable(false)->reorderable(false)
                    ->schema([
                        TextInput::make('bill_code')->label('Mã vận đơn')->formatStateUsing(fn ($state, $record) => ($state ?: '—').($record?->environment === 'mock' ? ' (mô phỏng)' : '')),
                        TextInput::make('status')->label('Trạng thái')->formatStateUsing(fn ($state) => NtlStatus::LABELS[$state] ?? $state),
                        TextInput::make('fee')->label('Cước (đ)')->formatStateUsing(fn ($state) => $state === null ? '—' : self::money($state)),
                        TextInput::make('cod_amount')->label('Thu hộ (đ)')->formatStateUsing(fn ($state) => self::money($state)),
                        TextInput::make('ref_code')->label('Mã tham chiếu'),
                        TextInput::make('error')->label('Lỗi')->visible(fn ($state) => filled($state))->columnSpanFull(),
                        TextInput::make('history')->label('Hành trình')->columnSpanFull()->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record ? $record->events()->limit(5)->get()->map(fn ($e) => ($e->happened_at?->format('H:i d/m') ?? '').' '.$e->status_name)->implode(' · ') : ''),
                    ])->columns(5),
            ]),
            Section::make('Lịch sử thanh toán')->columnSpanFull()->collapsed()->schema([
                Repeater::make('payments')->hiddenLabel()->relationship()->disabled()->addable(false)->deletable(false)->reorderable(false)
                    ->schema([
                        TextInput::make('status')->label('Trạng thái'),
                        TextInput::make('provider')->label('Nguồn'),
                        TextInput::make('provider_reference')->label('Mã tham chiếu'),
                        TextInput::make('amount')->label('Số tiền (đ)'),
                        TextInput::make('created_at')->label('Ghi nhận lúc'),
                    ])->columns(5),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('latestShipment'))
            ->columns([
                TextColumn::make('code')->label('Mã đơn')->searchable()->weight('bold'),
                TextColumn::make('created_at')->label('Ngày đặt')->dateTime('H:i d/m/Y')->sortable(),
                TextColumn::make('customer_name')->label('Khách hàng')->searchable(['customer_name', 'phone', 'email'])
                    ->description(fn (Order $record) => trim($record->phone.(($h = app(CustomerHistory::class)->label($record->phone, exceptOrder: (int) $record->id)) !== '' ? ' · '.$h : ''))),
                TextColumn::make('total')->label('Tổng tiền')->formatStateUsing(fn ($state) => self::money($state))->sortable(),
                TextColumn::make('status')->label('Trạng thái')->badge()->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'new' => 'warning', 'done' => 'success', 'cancelled' => 'gray', default => 'info'
                    })
                    ->icon(fn (Order $record) => (OrderLifecycle::STATUS_TRANSITIONS[$record->status] ?? []) !== [] ? 'heroicon-m-pencil-square' : null)->iconPosition('after')
                    ->tooltip(fn (Order $record) => (OrderLifecycle::STATUS_TRANSITIONS[$record->status] ?? []) !== [] ? 'Bấm để đổi trạng thái' : null)
                    ->action(self::quickStatusAction('statusFromColumn')),
                TextColumn::make('shipping')->label('Vận chuyển')->badge()
                    ->state(fn (Order $record) => $record->latestShipment ? (NtlStatus::LABELS[$record->latestShipment->status] ?? $record->latestShipment->status)
                        : (in_array($record->status, ['new', 'confirmed'], true) ? 'Tạo vận đơn' : null))
                    ->placeholder('—')
                    ->description(fn (Order $record) => $record->latestShipment?->bill_code)
                    ->color(fn (Order $record) => match ($record->latestShipment?->status) {
                        'delivered' => 'success', 'failed', 'returning', 'returned', 'unknown' => 'danger', 'cancelled' => 'gray', null => 'primary', default => 'info',
                    })
                    ->icon(fn (Order $record) => $record->latestShipment ? 'heroicon-m-truck' : 'heroicon-m-plus')
                    ->tooltip('Bấm để tạo vận đơn hoặc xem hành trình')
                    ->action(self::shippingAction()),
                TextColumn::make('payment_status')->label('Thanh toán')->badge()->formatStateUsing(fn ($state) => self::PAYMENT[$state] ?? $state)
                    ->color(fn ($state) => $state === 'paid' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(self::STATUSES),
                SelectFilter::make('payment_status')->label('Thanh toán')->options(self::PAYMENT),
                SelectFilter::make('shipping')->label('Vận chuyển')->options([
                    'none' => 'Chưa có vận đơn (đơn mới / đã xác nhận)', 'moving' => 'Đang trên đường', 'trouble' => 'Có sự cố / hoàn / chưa rõ', 'delivered' => 'Đã giao',
                ])->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                    'none' => $query->whereIn('status', ['new', 'confirmed'])->whereDoesntHave('shipments', fn (Builder $q) => $q->whereIn('status', Shipments::ACTIVE)),
                    'moving' => $query->whereHas('shipments', fn (Builder $q) => $q->whereIn('status', ['draft', 'waiting_pickup', 'in_transit', 'out_for_delivery'])),
                    'trouble' => $query->whereHas('shipments', fn (Builder $q) => $q->whereIn('status', ['failed', 'returning', 'returned', 'unknown'])),
                    'delivered' => $query->whereHas('shipments', fn (Builder $q) => $q->where('status', 'delivered')),
                    default => $query,
                }),
                SelectFilter::make('province_id')->label('Tỉnh / thành')->relationship('province', 'name')->searchable()->preload(),
                Filter::make('created')->columns(2)->columnSpan(2)->schema([
                    DatePicker::make('from')->label('Từ ngày'),
                    DatePicker::make('until')->label('Đến ngày'),
                ])->query(fn (Builder $query, array $data) => $query
                    ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                EditAction::make()->label('Xem / xử lý'),
                Action::make('print')->label('In')->icon(Heroicon::OutlinedPrinter)->iconButton()->tooltip('In đơn hàng')
                    ->url(fn (Order $record) => route('order.print', $record), true),
                Action::make('excel')->label('Excel')->icon(Heroicon::OutlinedArrowDownTray)->iconButton()->tooltip('Tải phiếu đơn hàng (Excel)')
                    ->action(fn (Order $record) => app(OrderExporter::class)->single($record)),
            ])
            ->toolbarActions([
                BulkAction::make('confirmSelected')->label('Xác nhận các đơn mới đã chọn')->icon(Heroicon::OutlinedCheckCircle)
                    ->requiresConfirmation()->modalDescription('Chỉ đơn đang "Mới" được chuyển sang "Đã xác nhận"; đơn khác giữ nguyên.')
                    ->action(function (Collection $records) {
                        $done = 0;
                        foreach ($records as $order) {
                            if ($order instanceof Order && $order->status === 'new') {
                                app(OrderLifecycle::class)->update($order, 'confirmed', $order->notes);
                                $done++;
                            }
                        }
                        Notification::make()->success()->title('Đã xác nhận '.$done.' đơn')->send();
                    })->deselectRecordsAfterCompletion(),
                BulkAction::make('shipSelected')->label('Tạo vận đơn Nhất Tín cho các đơn đã chọn')->icon(Heroicon::OutlinedTruck)
                    ->modalDescription('Mỗi đơn một vận đơn nháp. Cân nặng tính từ "Cân nặng khi đóng gói" của sản phẩm; dòng chưa có cân nặng dùng số kg bên dưới. Tiền thu hộ tự tính: đơn COD chưa thanh toán thu đúng tổng đơn. Đơn đã có vận đơn, đã hủy hoặc đã xong được bỏ qua.')
                    ->schema([
                        Select::make('service_id')->label('Dịch vụ')->required()->default('91')->options(config('shipping.nhattin.services')),
                        Select::make('payer_id')->label('Ai trả cước')->required()->default('10')->options(config('shipping.nhattin.payers')),
                        TextInput::make('fallback_kg')->label('Cân nặng mỗi sản phẩm chưa có cân nặng (kg)')->numeric()->minValue(0.1)->default(1)->required(),
                        TextInput::make('note')->label('Ghi chú cho bưu tá')->maxLength(200),
                    ])
                    ->action(fn (Collection $records, array $data) => self::shipMany($records, $data))
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('trackSelected')->label('Cập nhật hành trình các đơn đã chọn')->icon(Heroicon::OutlinedArrowPath)
                    ->action(function (Collection $records) {
                        $count = 0;
                        foreach ($records->filter(fn ($o) => $o instanceof Order) as $order) {
                            foreach (Shipment::query()->where('order_id', $order->getKey())->whereNotNull('bill_code')->whereIn('status', Shipments::ACTIVE)->get() as $shipment) {
                                try {
                                    app(Shipments::class)->refresh($shipment);
                                    $count++;
                                } catch (NtlException) {
                                    // Reported below as not refreshed.
                                }
                            }
                        }
                        Notification::make()->success()->title('Đã cập nhật '.$count.' vận đơn')->body('Đơn hàng tự chuyển "Đang giao" / "Hoàn tất" theo vận đơn.')->send();
                    }),
                BulkAction::make('printSelected')->label('In các đơn đã chọn')->icon(Heroicon::OutlinedPrinter)
                    ->action(fn (Collection $records, $livewire) => $livewire->js('window.open('.json_encode(route('order.print-many', ['ids' => $records->pluck('id')->implode(',')])).', "_blank")')),
                BulkAction::make('exportSelected')->label('Xuất Excel các đơn đã chọn')->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (Collection $records) => app(OrderExporter::class)->list(Order::query()->whereKey($records->pluck('id'))))
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    /** Status and internal notes from the list, through the same lifecycle rules and audit log as the edit page. */
    public static function quickStatusAction(string $name): Action
    {
        return Action::make($name)->label('Đổi trạng thái')->icon(Heroicon::OutlinedArrowPathRoundedSquare)->tooltip('Đổi trạng thái nhanh')
            ->visible(fn (Order $record) => (OrderLifecycle::STATUS_TRANSITIONS[$record->status] ?? []) !== [])
            ->modalHeading(fn (Order $record) => 'Đơn '.$record->code)
            ->modalDescription(fn (Order $record) => $record->customer_name.' · '.$record->phone.' · '.self::money($record->total)
                .(($h = app(CustomerHistory::class)->label($record->phone, exceptOrder: (int) $record->id)) !== '' ? ' · '.$h : ''))
            ->modalWidth('md')->modalSubmitActionLabel('Lưu')
            ->fillForm(fn (Order $record) => ['status' => $record->status, 'notes' => $record->notes])
            ->schema([
                Select::make('status')->label('Trạng thái')->required()->selectablePlaceholder(false)
                    ->options(fn (Order $record) => OrderLifecycle::statusChoices((string) $record->status, self::STATUSES)),
                Textarea::make('notes')->label('Ghi chú nội bộ')->rows(3),
            ])
            ->action(function (Order $record, array $data): void {
                app(OrderLifecycle::class)->update($record, (string) $data['status'], $data['notes'] ?? null);
                Notification::make()->success()->title('Đơn '.$record->code.': '.(self::STATUSES[$record->status] ?? $record->status))->send();
            });
    }

    /** "Vận chuyển" cell: create the waybill when there is none, otherwise show the journey and refresh it. */
    public static function shippingAction(): Action
    {
        return Action::make('shipping')->label('Vận chuyển')
            ->visible(fn (Order $record) => $record->latestShipment !== null || in_array($record->status, ['new', 'confirmed'], true))
            ->modalHeading(fn (Order $record) => 'Vận chuyển đơn '.$record->code)
            ->modalDescription(fn (Order $record) => $record->customer_name.' · '.$record->phone.' · '.OrderExporter::address($record->loadMissing('ward', 'province')))
            ->modalWidth('lg')
            ->modalSubmitActionLabel(fn (Order $record) => self::activeShipment($record) ? 'Cập nhật hành trình' : 'Tạo vận đơn nháp')
            ->modalContent(fn (Order $record) => self::activeShipment($record) || $record->latestShipment ? view('filament.order-shipment', ['shipment' => $record->latestShipment]) : null)
            ->fillForm(function (Order $record) {
                $weight = app(ShipmentAutomation::class)->weight($record);

                return ['weight_kg' => $weight['kg'], 'service_id' => '91', 'payer_id' => '10', 'cod_amount' => app(Shipments::class)->codFor($record)];
            })
            ->schema(fn (Order $record) => self::activeShipment($record) ? [] : [
                Grid::make(2)->schema([
                    TextInput::make('weight_kg')->label('Cân nặng (kg)')->numeric()->required()->minValue(0.1)->maxValue(1000)
                        ->helperText(fn () => app(ShipmentAutomation::class)->weight($record)['known'] ? 'Tính từ cân nặng sản phẩm.' : 'Có sản phẩm chưa nhập cân nặng: đang tính 1 kg mỗi cái, sửa nếu cần.'),
                    TextInput::make('cod_amount')->label('Tiền thu hộ (đ)')->numeric()->minValue(0),
                    Select::make('service_id')->label('Dịch vụ')->required()->options(config('shipping.nhattin.services')),
                    Select::make('payer_id')->label('Ai trả cước')->required()->options(config('shipping.nhattin.payers')),
                ]),
                TextInput::make('note')->label('Ghi chú cho bưu tá')->maxLength(200),
            ])
            ->action(function (Order $record, array $data): void {
                try {
                    if ($active = self::activeShipment($record)) {
                        $shipment = app(Shipments::class)->refresh($active);
                        Notification::make()->success()->title(NtlStatus::LABELS[$shipment->status] ?? $shipment->status)->send();

                        return;
                    }
                    $shipment = app(Shipments::class)->create($record, $data, AdminActor::current());
                    Notification::make()->success()->title('Đã tạo vận đơn '.($shipment->bill_code ?? $shipment->ref_code))->send();
                } catch (NtlException $error) {
                    Notification::make()->danger()->title('Nhất Tín chưa trả lời được')->body($error->getMessage())->persistent()->send();
                }
            });
    }

    private static function activeShipment(Order $order): ?Shipment
    {
        $latest = $order->latestShipment;

        return $latest && in_array($latest->status, Shipments::ACTIVE, true) ? $latest : null;
    }

    /** @param Collection<int, Order> $records */
    private static function shipMany(Collection $records, array $data): void
    {
        $made = [];
        $skipped = [];
        $failed = [];
        foreach ($records as $order) {
            if (! in_array($order->status, ['new', 'confirmed'], true) || $order->shipments()->whereIn('status', Shipments::ACTIVE)->exists()) {
                $skipped[] = $order->code;

                continue;
            }
            $weight = app(ShipmentAutomation::class)->weight($order, (float) $data['fallback_kg']);
            try {
                $shipment = app(Shipments::class)->create($order, ['weight_kg' => $weight['kg'], 'service_id' => $data['service_id'], 'payer_id' => $data['payer_id'],
                    'cod_amount' => app(Shipments::class)->codFor($order), 'note' => $data['note'] ?? null], AdminActor::current());
                $made[] = $order->code.' → '.($shipment->bill_code ?? $shipment->ref_code).($weight['known'] ? '' : ' (cân nặng ước '.$weight['kg'].' kg)');
            } catch (Throwable $error) {
                $failed[] = $order->code.': '.Str::limit($error->getMessage(), 120);
            }
        }
        $body = implode("\n", array_filter([
            $made ? 'Đã tạo: '.implode('; ', $made) : null,
            $skipped ? 'Bỏ qua (đã có vận đơn hoặc không còn cần giao): '.implode(', ', $skipped) : null,
            $failed ? 'Lỗi: '.implode('; ', $failed) : null,
        ]));
        Notification::make()->title('Tạo '.count($made).'/'.$records->count().' vận đơn')->body($body)
            ->{$failed ? 'warning' : 'success'}()->persistent()->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
