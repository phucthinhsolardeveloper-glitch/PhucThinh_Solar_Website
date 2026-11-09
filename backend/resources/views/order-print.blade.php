<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Đơn hàng {{ $order->code }}</title>
    <style>
        body { font: 14px/1.5 system-ui, sans-serif; color: #111827; margin: 0 auto; padding: 32px; max-width: 820px; background: #fff; }
        h1 { font-size: 20px; margin: 20px 0 12px; } .co b { font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; } th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; vertical-align: top; }
        .n { text-align: right; white-space: nowrap; } .info td:first-child { width: 150px; font-weight: 600; }
        .bar { margin-bottom: 16px; } button { padding: 8px 16px; border: 0; border-radius: 6px; background: #0b2a5b; color: #fff; cursor: pointer; font: inherit; }
        @media print { .bar { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="bar"><button onclick="window.print()">In đơn hàng</button></div>
    <div class="co">
        <b>{{ $settings->siteName() }}</b><br>
        Hotline: {{ $settings->get('hotline') }} · Email: {{ $settings->get('email') }}<br>
        Địa chỉ: {{ $settings->get('address') }}
    </div>
    <h1>Đơn hàng {{ $order->code }}</h1>
    <table class="info">
        <tr><td>Ngày đặt</td><td>{{ $order->created_at?->format('H:i d/m/Y') }}</td></tr>
        <tr><td>Tình trạng</td><td>{{ \App\Filament\Resources\Orders\OrderResource::STATUSES[$order->status] ?? $order->status }}</td></tr>
        <tr><td>Thanh toán</td><td>{{ \App\Filament\Resources\Orders\OrderResource::PAYMENT[$order->payment_status] ?? $order->payment_status }}@if ($order->paymentMethod) · {{ $order->paymentMethod->name }}@endif</td></tr>
        <tr><td>Khách hàng</td><td>{{ $order->customer_name }}</td></tr>
        <tr><td>Điện thoại</td><td>{{ $order->phone }}</td></tr>
        @if ($order->email)<tr><td>Email</td><td>{{ $order->email }}</td></tr>@endif
        <tr><td>Địa chỉ giao</td><td>{{ \App\Domain\Commerce\OrderExporter::address($order) }}</td></tr>
        @if ($order->requirements)<tr><td>Yêu cầu</td><td>{{ $order->requirements }}</td></tr>@endif
    </table>
    <table>
        <tr><th>STT</th><th>Sản phẩm</th><th class="n">Đơn giá</th><th class="n">SL</th><th class="n">Thành tiền</th></tr>
        @foreach ($order->items as $index => $item)
            @php($price = (float) ($item->sale_price ?: $item->regular_price))
            <tr><td>{{ $index + 1 }}</td><td>{{ $item->name }} {{ trim($item->color.' '.$item->size) }}</td><td class="n">{{ number_format($price, 0, ',', '.') }}đ</td><td class="n">{{ $item->quantity }}</td><td class="n">{{ number_format($price * $item->quantity, 0, ',', '.') }}đ</td></tr>
        @endforeach
        <tr><td colspan="4" class="n">Tạm tính</td><td class="n">{{ number_format((float) $order->subtotal, 0, ',', '.') }}đ</td></tr>
        <tr><td colspan="4" class="n">Phí vận chuyển</td><td class="n">{{ number_format((float) $order->shipping_fee, 0, ',', '.') }}đ</td></tr>
        <tr><td colspan="4" class="n"><b>Tổng cộng</b></td><td class="n"><b>{{ number_format((float) $order->total, 0, ',', '.') }}đ</b></td></tr>
    </table>
</body>
</html>
