@php
    use App\Domain\Commerce\OrderExporter;
    use App\Filament\Resources\Orders\OrderResource;

    $money = fn ($value) => number_format((float) $value, 0, ',', '.').'đ';
    $brand = app(App\Mail\MailBrand::class)->data();
    // One order (route order.print) or several (order.print-many), each on its own A4 page.
    $orders = isset($orders) ? $orders : collect([$order]);
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $orders->count() === 1 ? 'Đơn hàng '.$orders->first()->code : 'In '.$orders->count().' đơn hàng' }}</title>
    <style>
        /* One A4 page for a normal order; a long one breaks between rows, never inside one. */
        @page { size: A4; margin: 12mm 10mm; }
        :root { --navy: #09285e; --gold: #d9a326; --line: #d8dfea; --muted: #5d6b84; }
        * { box-sizing: border-box; }
        body { margin: 0 auto; padding: 24px; max-width: 820px; background: #fff; color: #15284b;
               font: 13.5px/1.55 "Segoe UI", system-ui, -apple-system, Arial, sans-serif; }
        .bar { display: flex; gap: 10px; margin-bottom: 18px; }
        .bar button { padding: 9px 18px; border: 0; border-radius: 8px; background: var(--navy); color: #fff;
                      cursor: pointer; font: inherit; font-weight: 600; }
        .bar button.ghost { background: #eef2f7; color: var(--navy); }

        header { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px;
                 padding-bottom: 14px; border-bottom: 3px solid var(--navy); }
        .co-logo { height: 46px; width: auto; display: block; margin-bottom: 8px; }
        .co strong { display: block; font-size: 14px; line-height: 1.35; color: var(--navy); }
        .co span { display: block; color: var(--muted); font-size: 12px; }
        .doc { text-align: right; flex: none; }
        .doc h1 { margin: 0; font-size: 22px; letter-spacing: -.01em; color: var(--navy); text-transform: uppercase; }
        .doc .code { margin-top: 4px; font-size: 17px; font-weight: 700; letter-spacing: .02em; }
        .doc .date { color: var(--muted); font-size: 12px; }

        .tags { display: flex; flex-wrap: wrap; gap: 6px; justify-content: flex-end; margin-top: 8px; }
        .tag { padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: .02em;
               background: #eef2f7; color: var(--navy); }
        .tag.paid { background: #e7f6ec; color: #17683a; }
        .tag.wait { background: #fdf3e0; color: #8a5a06; }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin: 16px 0; }
        .card { border: 1px solid var(--line); border-radius: 10px; padding: 12px 14px; }
        .card h2 { margin: 0 0 8px; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
        .card dl { display: grid; grid-template-columns: 92px 1fr; gap: 4px 10px; margin: 0; }
        .card dt { color: var(--muted); }
        .card dd { margin: 0; font-weight: 600; }

        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        thead th { background: var(--navy); color: #fff; font-size: 11px; letter-spacing: .06em; text-transform: uppercase;
                   padding: 9px 10px; text-align: left; }
        tbody td { padding: 9px 10px; border-bottom: 1px solid var(--line); vertical-align: top; }
        tbody tr:nth-child(even) td { background: #f9fbfd; }
        tr { break-inside: avoid; }
        .n { text-align: right; white-space: nowrap; }
        .c { text-align: center; white-space: nowrap; }
        .opt { display: block; color: var(--muted); font-size: 12px; }

        .totals { margin-left: auto; margin-top: 12px; width: min(330px, 100%); }
        .totals div { display: flex; justify-content: space-between; gap: 18px; padding: 5px 0; }
        .totals .sum { margin-top: 6px; padding-top: 10px; border-top: 2px solid var(--navy);
                       font-size: 17px; font-weight: 800; color: var(--navy); }

        .note { margin-top: 14px; padding: 10px 14px; border-left: 3px solid var(--gold); background: #fdf7e8;
                border-radius: 0 8px 8px 0; font-size: 12.5px; }
        .sign { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 34px; text-align: center;
                break-inside: avoid; }
        .sign strong { display: block; font-size: 12.5px; }
        .sign span { display: block; color: var(--muted); font-size: 11.5px; }
        .sign i { display: block; margin-top: 52px; border-top: 1px dotted #9aa8bf; }
        footer { margin-top: 22px; padding-top: 10px; border-top: 1px solid var(--line);
                 color: var(--muted); font-size: 11.5px; text-align: center; }

        .sheet + .sheet { margin-top: 48px; padding-top: 24px; border-top: 2px dashed var(--line); }
        @media print { .bar { display: none; } body { padding: 0; max-width: none; }
                       .sheet + .sheet { break-before: page; margin-top: 0; padding-top: 0; border-top: 0; } }
    </style>
</head>
<body>
    <div class="bar">
        <button type="button" onclick="window.print()">{{ $orders->count() === 1 ? 'In đơn hàng' : 'In '.$orders->count().' đơn hàng' }}</button>
        <button type="button" class="ghost" onclick="window.close()">Đóng</button>
    </div>
@foreach ($orders as $order)
    @php($discount = max(0.0, (float) $order->subtotal + (float) $order->shipping_fee - (float) $order->total))
    <section class="sheet">

    <header>
        <div class="co">
            @if ($brand['logo'] !== '')
                <img class="co-logo" src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}">
            @endif
            <strong>{{ $settings->siteName() }}</strong>
            @if ($brand['address'] !== '')<span>{{ $brand['address'] }}</span>@endif
            <span>
                @if ($brand['hotline'] !== '')Hotline: {{ $brand['hotline'] }}@endif
                @if ($brand['email'] !== '') · {{ $brand['email'] }}@endif
            </span>
            <span>{{ preg_replace('~^https?://~', '', rtrim($brand['home'], '/')) }}</span>
        </div>
        <div class="doc">
            <h1>Phiếu đơn hàng</h1>
            <div class="code">{{ $order->code }}</div>
            <div class="date">Ngày đặt: {{ $order->created_at?->format('H:i d/m/Y') }}</div>
            <div class="tags">
                <span class="tag">{{ OrderResource::STATUSES[$order->status] ?? $order->status }}</span>
                <span class="tag {{ $order->payment_status === 'paid' ? 'paid' : 'wait' }}">{{ OrderResource::PAYMENT[$order->payment_status] ?? $order->payment_status }}</span>
            </div>
        </div>
    </header>

    <div class="grid">
        <section class="card">
            <h2>Khách hàng</h2>
            <dl>
                <dt>Họ tên</dt><dd>{{ $order->customer_name }}</dd>
                <dt>Điện thoại</dt><dd>{{ $order->phone }}</dd>
                @if ($order->email)<dt>Email</dt><dd>{{ $order->email }}</dd>@endif
            </dl>
        </section>
        <section class="card">
            <h2>Giao hàng &amp; thanh toán</h2>
            <dl>
                <dt>Địa chỉ</dt><dd>{{ OrderExporter::address($order) }}</dd>
                <dt>Hình thức</dt><dd>{{ $order->paymentMethod->name ?? 'Chưa chọn' }}</dd>
            </dl>
        </section>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:38px">STT</th>
                <th>Sản phẩm</th>
                <th class="n" style="width:110px">Đơn giá</th>
                <th class="c" style="width:52px">SL</th>
                <th class="n" style="width:120px">Thành tiền</th>
            </tr>
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

    <div class="totals">
        <div><span>Tạm tính</span><span>{{ $money($order->subtotal) }}</span></div>
        <div><span>Phí vận chuyển</span><span>{{ (float) $order->shipping_fee > 0 ? $money($order->shipping_fee) : 'Chờ xác nhận' }}</span></div>
        @if ($discount > 0)
            <div><span>Giảm giá</span><span>−{{ $money($discount) }}</span></div>
        @endif
        <div class="sum"><span>Tổng cộng</span><span>{{ $money($order->total) }}</span></div>
    </div>

    @if ($order->requirements)
        <p class="note"><strong>Yêu cầu của khách:</strong> {{ $order->requirements }}</p>
    @endif

    <div class="sign">
        <div><strong>Người lập phiếu</strong><span>(Ký, ghi rõ họ tên)</span><i></i></div>
        <div><strong>Người nhận hàng</strong><span>(Ký, ghi rõ họ tên)</span><i></i></div>
    </div>

    <footer>{{ $settings->siteName() }} · Phiếu in ngày {{ now()->format('H:i d/m/Y') }}</footer>
    </section>
@endforeach
</body>
</html>
