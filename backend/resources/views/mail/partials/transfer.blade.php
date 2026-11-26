{{-- How to pay an order by bank transfer: the order's own code (amount and note inside) and the same facts as text. --}}
@php
    $png = \App\Domain\Commerce\VietQr::png((string) $transfer['payload']);
    // Sent mail carries the picture inline (cid), which mail clients show; a preview has no message, so a data URI.
    $qr = isset($message) ? $message->embedData($png, 'ma-qr-'.$transfer['note'].'.png', 'image/png') : 'data:image/png;base64,'.base64_encode($png);
    $row = 'padding:5px 0;color:#6a7a95;font-size:13px;line-height:19px;white-space:nowrap';
    $value = 'padding:5px 0 5px 12px;color:#15284b;font-size:14px;line-height:19px;font-weight:bold';
@endphp
<tr>
    <td class="px" style="padding:18px 32px 0">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:separate;border-spacing:0;border:1px solid #c9d6ea;border-radius:12px">
            <tr>
                <td colspan="2" style="padding:14px 18px 4px;color:#09285e;font-size:15px;line-height:21px;font-weight:bold">Chuyển khoản để hoàn tất đơn</td>
            </tr>
            <tr>
                <td class="col qr-cell" width="170" valign="top" style="padding:8px 0 16px 18px">
                    <img src="{{ $qr }}" alt="Mã VietQR chuyển khoản {{ \App\Mail\OrderNotification::money((float) $transfer['amount']) }}" width="150" height="150" style="display:inline-block;width:150px;height:150px;border:1px solid #e2e8f0;border-radius:8px">
                    <div style="margin-top:6px;color:#6a7a95;font-size:11.5px;line-height:16px">Quét bằng app ngân hàng</div>
                </td>
                <td class="col" valign="top" style="padding:8px 18px 14px">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr><td style="{{ $row }}">Ngân hàng</td><td style="{{ $value }}">@if (($transfer['logo'] ?? '') !== '')<img src="{{ $transfer['logo'] }}" alt="{{ $transfer['bank'] }}" height="20" style="display:block;height:20px;width:auto;border:0">@else{{ $transfer['bank'] }}@endif</td></tr>
                        <tr><td style="{{ $row }}">Số tài khoản</td><td style="{{ $value }};letter-spacing:.5px">{{ $transfer['account'] }}</td></tr>
                        <tr><td style="{{ $row }}">Chủ tài khoản</td><td style="{{ $value }};font-weight:normal;font-size:13px">{{ $transfer['name'] }}</td></tr>
                        <tr><td style="{{ $row }}">Số tiền</td><td style="{{ $value }};color:#09285e;font-size:16px">{{ \App\Mail\OrderNotification::money((float) $transfer['amount']) }}</td></tr>
                        <tr><td style="{{ $row }}">Nội dung</td><td style="{{ $value }};color:#7c5c1b;letter-spacing:.5px">{{ $transfer['note'] }}</td></tr>
                    </table>
                    <div style="margin-top:8px;color:#6a7a95;font-size:12px;line-height:18px">Giữ đúng nội dung để kế toán nhận ra đơn. Đơn được xác nhận khi tiền vào tài khoản công ty.</div>
                </td>
            </tr>
        </table>
    </td>
</tr>
