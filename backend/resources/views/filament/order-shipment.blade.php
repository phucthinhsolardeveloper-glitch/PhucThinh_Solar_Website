@php($labels = App\Domain\Shipping\NhatTin\NtlStatus::LABELS)
@if ($shipment)
    <div class="pts-queue__list">
        <div>
            <strong>{{ $labels[$shipment->status] ?? $shipment->status }}</strong>
            @if ($shipment->bill_code) · vận đơn {{ $shipment->bill_code }}@endif
            @if ($shipment->environment === 'mock') · <em>mô phỏng</em>@endif
        </div>
        <span class="pts-attention__sub">
            Cước {{ $shipment->fee === null ? '—' : number_format((float) $shipment->fee, 0, ',', '.').'đ' }}
            · thu hộ {{ number_format((float) $shipment->cod_amount, 0, ',', '.') }}đ · {{ (float) $shipment->weight_kg }} kg
        </span>
        @if ($shipment->error)<span class="pts-attention__sub">Lỗi: {{ $shipment->error }}</span>@endif
        <ul class="pts-queue__list">
            @forelse ($shipment->events()->limit(8)->get() as $event)
                <li><span class="pts-attention__sub">{{ $event->happened_at?->format('H:i d/m') }}</span> {{ $event->status_name }}</li>
            @empty
                <li class="pts-attention__sub">Chưa có hành trình.</li>
            @endforelse
        </ul>
    </div>
@endif
