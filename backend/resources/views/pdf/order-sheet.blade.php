@php
    $money = fn ($v) => number_format((float) $v, 0, ',', '.').'đ';
    $company = $sheet['company'];
    $customer = $sheet['customer'];
    $totals = $sheet['totals'];
    $pending = (float) $totals['shipping'] <= 0;
    $discount = max(0.0, (float) $totals['subtotal'] + (float) $totals['shipping'] - (float) $totals['total']);
    $date = fn (string $value) => \Illuminate\Support\Carbon::parse($value)->format('H:i d/m/Y');
@endphp
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<title>Phiếu đơn hàng {{ $sheet['code'] }}</title>
{{-- Rendered by dompdf: tables for columns, no flex or grid. --}}
<style>
    @page { margin: 34px 36px 52px; }
    body { margin: 0; font-family: 'DejaVu Sans'; font-size: 9.5pt; line-height: 1.4; color: #15284b; }
    table { width: 100%; border-collapse: collapse; }
    td, th { vertical-align: top; }
    .band { background: #09285e; color: #fff; }
    .band td { padding: 16px 18px; vertical-align: middle; }
    .band img { width: 230px; }
    .band .name { font-size: 14pt; font-weight: bold; color: #f1c75b; }
    .title { text-align: right; }
    .title .kind { font-size: 15pt; font-weight: bold; }
    .title .code { font-size: 12pt; font-weight: bold; color: #f1c75b; }
    .title .date { font-size: 8.5pt; color: #c7d6f2; }
    .rule { height: 4px; background: #d9a326; }
    .company { margin: 12px 0 14px; font-size: 8.5pt; color: #4b5a77; }
    .company strong { display: block; font-size: 10pt; color: #09285e; }
    .parties td.box { width: 50%; border: 1px solid #d8dfea; padding: 10px 12px; }
    .parties h2 { margin: 0 0 5px; font-size: 9.5pt; color: #09285e; }
    .parties .muted { color: #5d6b84; font-size: 8.5pt; }
    .items { margin-top: 16px; }
    .items th { background: #09285e; color: #fff; font-size: 8.5pt; text-align: left; padding: 7px 8px; }
    .items td { border-bottom: 1px solid #d8dfea; padding: 8px; }
    .items tr { page-break-inside: avoid; }
    .items .sub { color: #5d6b84; font-size: 8pt; }
    .n { text-align: right; white-space: nowrap; }
    .items th.n { text-align: right; }
    .c { text-align: center; }
    .items th.c { text-align: center; }
    .totals { margin-top: 10px; page-break-inside: avoid; }
    .totals td { padding: 4px 8px; }
    .totals .label { color: #5d6b84; text-align: right; }
    .totals .sum td { border-top: 2px solid #09285e; padding-top: 8px; font-size: 12pt; font-weight: bold; color: #09285e; }
    .totals .sum .label { color: #09285e; }
    .note { margin-top: 14px; padding: 9px 12px; background: #fdf7e8; border-left: 3px solid #d9a326; page-break-inside: avoid; }
    .foot { position: fixed; bottom: -34px; left: 0; right: 0; border-top: 1px solid #d8dfea; padding-top: 6px; font-size: 7.5pt; color: #5d6b84; }
</style>
</head>
<body>
    <div class="foot">Phiếu ghi nhận đơn đặt hàng, không thay hóa đơn giá trị gia tăng hoặc chứng từ thanh toán. Mã đối chiếu {{ $reference }}</div>

    <table class="band">
        <tr>
            <td style="width:56%">
                @if ($company['logo'] !== '')<img src="{{ $company['logo'] }}" alt="">@else<span class="name">{{ $company['name'] }}</span>@endif
            </td>
            <td class="title">
                <div class="kind">PHIẾU ĐƠN HÀNG</div>
                <div class="code">{{ $sheet['code'] }}</div>
                <div class="date">Đặt lúc {{ $date($sheet['placed_at']) }}</div>
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    <div class="company">
        <strong>{{ $company['name'] }}</strong>
        @if ($company['address'] !== ''){{ $company['address'] }}<br>@endif
        {{ implode('  ·  ', array_filter([$company['hotline'] !== '' ? 'Hotline '.$company['hotline'] : '', $company['email'], $company['website']])) }}
        @if (! empty($sheet['rebuilt_at']))<br>Phiếu lập lại từ dữ liệu đơn hàng ngày {{ $date($sheet['rebuilt_at']) }}.@endif
    </div>

    <table class="parties">
        <tr>
            <td class="box">
                <h2>Khách hàng</h2>
                <strong>{{ $customer['name'] }}</strong><br>
                {{ $customer['phone'] }}
                @if ($customer['email'] !== '')<br>{{ $customer['email'] }}@endif
            </td>
            <td class="box">
                <h2>Giao hàng và thanh toán</h2>
                {{ $customer['address'] }}
                @if ($sheet['payment'] !== '')<br><span class="muted">Hình thức thanh toán:</span> {{ $sheet['payment'] }}@endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr><th class="c" style="width:26px">STT</th><th>Sản phẩm</th><th class="n" style="width:84px">Đơn giá</th><th class="c" style="width:28px">SL</th><th class="n" style="width:94px">Thành tiền</th></tr>
        </thead>
        <tbody>
            @foreach ($sheet['items'] as $index => $item)
                <tr>
                    <td class="c">{{ $index + 1 }}</td>
                    <td>{{ $item['name'] }}
                        @php($detail = implode('  ·  ', array_filter([$item['code'] !== '' ? 'Mã '.$item['code'] : '', $item['option']])))
                        @if ($detail !== '')<div class="sub">{{ $detail }}</div>@endif
                    </td>
                    <td class="n">{{ $money($item['price']) }}</td>
                    <td class="c">{{ $item['quantity'] }}</td>
                    <td class="n"><strong>{{ $money($item['total']) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="label">Tạm tính</td><td class="n" style="width:130px">{{ $money($totals['subtotal']) }}</td></tr>
        <tr><td class="label">Phí vận chuyển</td><td class="n">{{ $pending ? 'Chờ xác nhận' : $money($totals['shipping']) }}</td></tr>
        @if ($discount > 0)<tr><td class="label">Giảm giá</td><td class="n">−{{ $money($discount) }}</td></tr>@endif
        <tr class="sum"><td class="label">{{ $pending ? 'Tổng cộng, chưa gồm phí vận chuyển' : 'Tổng cộng' }}</td><td class="n">{{ $money($totals['total']) }}</td></tr>
    </table>

    @if (trim((string) $sheet['note']) !== '')
        <div class="note"><strong>Yêu cầu của khách:</strong> {{ $sheet['note'] }}</div>
    @endif
</body>
</html>
