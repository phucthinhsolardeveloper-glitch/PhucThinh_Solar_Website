@php
    use App\Domain\Commerce\OrderExporter;
    use App\Filament\Resources\Orders\OrderResource;

    $money = fn ($value) => number_format((float) $value, 0, ',', '.').'đ';
    $discount = max(0.0, (float) $order->subtotal + (float) $order->shipping_fee - (float) $order->total);
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Phiếu đơn hàng {{ $order->code }}</title>
    <style>
        @page { size: A4; margin: 14mm 12mm; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { color: #15284b; font-size: 10.5px; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: top; }
        .head { border-bottom: 2.5px solid #09285e; margin-bottom: 12px; }
        .head .co { padding-bottom: 10px; }
        .co strong { font-size: 12px; color: #09285e; }
        .muted { color: #5d6b84; }
        .logo { height: 34px; margin-bottom: 6px; }
        .doc { text-align: right; padding-bottom: 10px; }
        .doc h1 { margin: 0; font-size: 17px; color: #09285e; }
        .doc .code { font-size: 14px; font-weight: bold; color: #b5821a; margin-top: 2px; }
        .tag { display: inline-block; padding: 2px 7px; border-radius: 9px; background: #eef2f7; font-size: 9.5px; font-weight: bold; }
        .tag.paid { background: #e3f4ea; color: #1b6b3a; }
        .tag.wait { background: #fdf3dd; color: #8a5a00; }
        .cards td { width: 50%; vertical-align: top; padding: 0; }
        .card { border: 1px solid #d8dfea; border-radius: 6px; padding: 8px 10px; }
        .card h2 { margin: 0 0 5px; font-size: 11px; color: #09285e; }
        .card table td { padding: 1.5px 0; vertical-align: top; }
        .card .k { width: 70px; color: #5d6b84; }
        .items { margin-top: 12px; }
        .items th { background: #09285e; color: #fff; font-size: 9.5px; text-align: left; padding: 6px 7px; }
        .items td { padding: 6px 7px; border-bottom: 1px solid #d8dfea; vertical-align: top; }
        .items tr:nth-child(even) td { background: #f6f8fb; }
        .n { text-align: right; white-space: nowrap; }
        .c { text-align: center; }
        .opt { display: block; color: #5d6b84; font-size: 9.5px; }
        .totals { width: 46%; margin: 10px 0 0 auto; }
        .totals td { padding: 3px 0; }
        .totals .sum td { border-top: 2px solid #09285e; padding-top: 7px; font-size: 13px; font-weight: bold; color: #09285e; }
        .note { margin-top: 12px; padding: 7px 10px; border-left: 3px solid #d9a326; background: #fdf7e8; }
        .sign { margin-top: 28px; }
        .sign td { width: 50%; text-align: center; vertical-align: top; }
        .sign .line { margin: 48px 30px 0; border-top: 1px dotted #9aa8bf; }
        .foot { margin-top: 18px; padding-top: 7px; border-top: 1px solid #d8dfea; color: #5d6b84; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td class="co">
                @if ($logo !== '')<img class="logo" src="{{ $logo }}" alt=""><br>@endif
                <strong>{{ $settings->siteName() }}</strong><br>
                @if (($brand['address'] ?? '') !== '')<span class="muted">{{ $brand['address'] }}</span><br>@endif
                <span class="muted">
                    @if (($brand['hotline'] ?? '') !== '')Hotline: {{ $brand['hotline'] }}@endif
                    @if (($brand['email'] ?? '') !== '') · {{ $brand['email'] }}@endif
                </span>
            </td>
            <td class="doc">
                <h1>PHIẾU ĐƠN HÀNG</h1>
                <div class="code">{{ $order->code }}</div>
                <div class="muted">Ngày đặt: {{ $order->created_at?->format('H:i d/m/Y') }}</div>
                <div style="margin-top:4px">
                    <span class="tag">{{ OrderResource::STATUSES[$order->status] ?? $order->status }}</span>
                    <span class="tag {{ $order->payment_status === 'paid' ? 'paid' : 'wait' }}">{{ OrderResource::PAYMENT[$order->payment_status] ?? $order->payment_status }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="cards">
        <tr>
            <td style="padding-right:6px">
                <div class="card">
                    <h2>Khách hàng</h2>
                    <table>
                        <tr><td class="k">Họ tên</td><td>{{ $order->customer_name }}</td></tr>
                        <tr><td class="k">Điện thoại</td><td>{{ $order->phone }}</td></tr>
                        @if ($order->email)<tr><td class="k">Email</td><td>{{ $order->email }}</td></tr>@endif
                    </table>
                </div>
            </td>
            <td style="padding-left:6px">
                <div class="card">
                    <h2>Giao hàng &amp; thanh toán</h2>
                    <table>
                        <tr><td class="k">Địa chỉ</td><td>{{ OrderExporter::address($order) }}</td></tr>
                        <tr><td class="k">Hình thức</td><td>{{ $order->paymentMethod->name ?? 'Chưa chọn' }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr><th style="width:26px" class="c">STT</th><th>Sản phẩm</th><th class="n" style="width:82px">Đơn giá</th><th class="c" style="width:34px">SL</th><th class="n" style="width:92px">Thành tiền</th></tr>
        </thead>
        <tbody>
            @foreach ($order->items as $index => $item)
                @php($price = (float) ($item->sale_price ?: $item->regular_price))
                @php($option = trim($item->color.' '.$item->size))
                <tr>
                    <td class="c">{{ $index + 1 }}</td>
                    <td>{{ $item->name }}@if ($option !== '')<span class="opt">{{ $option }}</span>@endif</td>
                    <td class="n">{{ $money($price) }}</td>
                    <td class="c">{{ $item->quantity }}</td>
                    <td class="n"><strong>{{ $money($price * $item->quantity) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Tạm tính</td><td class="n">{{ $money($order->subtotal) }}</td></tr>
        <tr><td>Phí vận chuyển</td><td class="n">{{ (float) $order->shipping_fee > 0 ? $money($order->shipping_fee) : 'Chờ xác nhận' }}</td></tr>
        @if ($discount > 0)<tr><td>Giảm giá</td><td class="n">−{{ $money($discount) }}</td></tr>@endif
        <tr class="sum"><td>Tổng cộng</td><td class="n">{{ $money($order->total) }}</td></tr>
    </table>

    @if ($order->requirements)
        <div class="note"><strong>Yêu cầu của khách:</strong> {{ $order->requirements }}</div>
    @endif

    <table class="sign">
        <tr>
            <td><strong>Người lập phiếu</strong><br><span class="muted">(Ký, ghi rõ họ tên)</span><div class="line"></div></td>
            <td><strong>Người nhận hàng</strong><br><span class="muted">(Ký, ghi rõ họ tên)</span><div class="line"></div></td>
        </tr>
    </table>

    <div class="foot">{{ $settings->siteName() }} · Phiếu tạo ngày {{ now()->format('H:i d/m/Y') }}</div>
</body>
</html>
