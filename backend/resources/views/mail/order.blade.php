@php($money = fn ($v) => \App\Mail\OrderNotification::money((float) $v))
<!doctype html>
<html lang="vi">
<body style="margin:0;padding:24px 12px;background:#eef2f7;font-family:Arial,Helvetica,sans-serif;color:#15284b">
<table role="presentation" align="center" cellpadding="0" cellspacing="0" width="640" style="width:100%;max-width:640px;background:#ffffff">
    <tr><td style="padding:32px 28px 18px">
        <h1 style="margin:0 0 12px;color:#09285e;font-size:24px;line-height:32px">{{ $copy['heading'] }}</h1>
        <p style="margin:0;color:#405270;font-size:15px;line-height:24px">{!! nl2br(e($copy['intro'])) !!}</p>
    </td></tr>
    <tr><td style="padding:0 28px 20px">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;background:#f7f9fc">
            @foreach ($fields as $label => $value)
                <tr>
                    <td style="width:35%;padding:10px 14px;border-bottom:1px solid #e5eaf2;color:#60708b;font-size:13px;font-weight:bold;vertical-align:top">{{ $label }}</td>
                    <td style="padding:10px 14px;border-bottom:1px solid #e5eaf2;font-size:14px;line-height:21px;vertical-align:top">{!! nl2br(e($value)) !!}</td>
                </tr>
            @endforeach
        </table>
    </td></tr>
    <tr><td style="padding:0 28px 24px">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;font-size:14px">
            <tr style="background:#09285e;color:#ffffff">
                <th align="left" style="padding:10px">Sản phẩm</th><th align="center" style="padding:10px">SL</th><th align="right" style="padding:10px">Đơn giá</th><th align="right" style="padding:10px">Thành tiền</th>
            </tr>
            @foreach ($items as $item)
                <tr>
                    <td style="padding:10px;border-bottom:1px solid #e5eaf2">{{ $item['name'] }}@if ($item['option'] !== '')<br><span style="color:#60708b;font-size:12px">{{ $item['option'] }}</span>@endif</td>
                    <td align="center" style="padding:10px;border-bottom:1px solid #e5eaf2">{{ $item['quantity'] }}</td>
                    <td align="right" style="padding:10px;border-bottom:1px solid #e5eaf2">{{ $money($item['price']) }}</td>
                    <td align="right" style="padding:10px;border-bottom:1px solid #e5eaf2">{{ $money($item['total']) }}</td>
                </tr>
            @endforeach
            <tr><td colspan="3" align="right" style="padding:8px 10px;color:#60708b">Tạm tính</td><td align="right" style="padding:8px 10px">{{ $money($totals['subtotal']) }}</td></tr>
            <tr><td colspan="3" align="right" style="padding:8px 10px;color:#60708b">Phí vận chuyển</td><td align="right" style="padding:8px 10px">{{ $totals['shipping'] > 0 ? $money($totals['shipping']) : 'Chờ xác nhận' }}</td></tr>
            <tr><td colspan="3" align="right" style="padding:8px 10px;font-weight:bold">Tổng cộng</td><td align="right" style="padding:8px 10px;font-weight:bold;color:#09285e">{{ $money($totals['total']) }}</td></tr>
        </table>
    </td></tr>
    @if ($copy['closing'] !== '')
        <tr><td style="padding:0 28px 30px;color:#405270;font-size:13px;line-height:21px">{!! nl2br(e($copy['closing'])) !!}</td></tr>
    @endif
</table>
</body>
</html>
