@php($money = fn ($v) => \App\Mail\OrderNotification::money((float) $v))
{{-- Order mail, to the shop and to the buyer. Tables and inline styles only: Outlook ignores stylesheets. --}}
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $copy['subject'] }}</title>
</head>
<body style="margin:0;padding:0;background:#eef2f7;font-family:-apple-system,'Segoe UI',Arial,Helvetica,sans-serif;color:#15284b;-webkit-font-smoothing:antialiased">
<div style="display:none;max-height:0;overflow:hidden;opacity:0">{{ \Illuminate\Support\Str::limit($copy['intro'], 120) }}</div>
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background:#eef2f7">
    <tr><td style="height:24px;line-height:24px">&nbsp;</td></tr>
    @include('mail.partials.head')
    <tr>
        <td align="center" style="padding:0 12px" valign="top">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="640" style="width:100%;max-width:640px;background:#ffffff">
                <tr>
                    <td style="padding:30px 28px 16px">
                        <h1 style="margin:0 0 12px;color:#09285e;font-size:23px;line-height:31px;font-weight:bold">{{ $copy['heading'] }}</h1>
                        <p style="margin:0;color:#405270;font-size:15px;line-height:25px">{!! nl2br(e($copy['intro'])) !!}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px 28px 20px">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:separate;border-spacing:0;background:#f7f9fc;border:1px solid #e5eaf2;border-radius:10px">
                            @foreach ($fields as $label => $value)
                                <tr>
                                    <td style="width:36%;padding:12px 16px;{{ $loop->last ? '' : 'border-bottom:1px solid #e5eaf2;' }}color:#60708b;font-size:13px;font-weight:bold;vertical-align:top">{{ $label }}</td>
                                    <td style="padding:12px 16px;{{ $loop->last ? '' : 'border-bottom:1px solid #e5eaf2;' }}font-size:14px;line-height:22px;vertical-align:top">{!! nl2br(e($value)) !!}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 8px">
                        <h2 style="margin:0 0 10px;color:#09285e;font-size:15px;line-height:22px">Sản phẩm đã đặt</h2>
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;font-size:14px">
                            <tr style="background:#09285e;color:#ffffff">
                                <th align="left" style="padding:11px 12px;font-size:12px;letter-spacing:.3px;text-transform:uppercase;border-radius:8px 0 0 0">Sản phẩm</th>
                                <th align="center" style="padding:11px 8px;font-size:12px;letter-spacing:.3px;text-transform:uppercase">SL</th>
                                <th align="right" style="padding:11px 12px;font-size:12px;letter-spacing:.3px;text-transform:uppercase">Đơn giá</th>
                                <th align="right" style="padding:11px 12px;font-size:12px;letter-spacing:.3px;text-transform:uppercase;border-radius:0 8px 0 0">Thành tiền</th>
                            </tr>
                            @foreach ($items as $item)
                                <tr style="background:{{ $loop->index % 2 === 0 ? '#ffffff' : '#f9fbfd' }}">
                                    <td style="padding:12px;border-bottom:1px solid #e5eaf2;line-height:21px">{{ $item['name'] }}@if ($item['option'] !== '')<br><span style="color:#60708b;font-size:12px">{{ $item['option'] }}</span>@endif</td>
                                    <td align="center" style="padding:12px 8px;border-bottom:1px solid #e5eaf2;white-space:nowrap">{{ $item['quantity'] }}</td>
                                    <td align="right" style="padding:12px;border-bottom:1px solid #e5eaf2;white-space:nowrap">{{ $money($item['price']) }}</td>
                                    <td align="right" style="padding:12px;border-bottom:1px solid #e5eaf2;white-space:nowrap;font-weight:bold">{{ $money($item['total']) }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="2" style="padding:10px 12px"></td>
                                <td align="right" style="padding:10px 12px;color:#60708b">Tạm tính</td>
                                <td align="right" style="padding:10px 12px;white-space:nowrap">{{ $money($totals['subtotal']) }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding:0 12px 10px"></td>
                                <td align="right" style="padding:0 12px 10px;color:#60708b">Phí vận chuyển</td>
                                <td align="right" style="padding:0 12px 10px;white-space:nowrap">{{ $totals['shipping'] > 0 ? $money($totals['shipping']) : 'Chờ xác nhận' }}</td>
                            </tr>
                            <tr style="background:#f7f9fc">
                                <td colspan="2" style="padding:13px 12px;border-top:2px solid #09285e"></td>
                                <td align="right" style="padding:13px 12px;border-top:2px solid #09285e;font-weight:bold;color:#09285e">Tổng cộng</td>
                                <td align="right" style="padding:13px 12px;border-top:2px solid #09285e;white-space:nowrap;font-weight:bold;font-size:17px;color:#09285e">{{ $money($totals['total']) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @if ($copy['closing'] !== '')
                    <tr>
                        <td style="padding:18px 28px 28px">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr><td style="padding:14px 16px;background:#fdf7e8;border-left:3px solid #d9a326;border-radius:0 8px 8px 0;color:#5a4a1f;font-size:13px;line-height:21px">{!! nl2br(e($copy['closing'])) !!}</td></tr>
                            </table>
                        </td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
    @include('mail.partials.foot')
</table>
</body>
</html>
