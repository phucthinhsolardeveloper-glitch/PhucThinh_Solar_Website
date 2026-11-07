<!doctype html>
<html lang="vi">
<body style="margin:0;padding:24px 12px;background:#eef2f7;font-family:Arial,Helvetica,sans-serif;color:#15284b">
<table role="presentation" align="center" cellpadding="0" cellspacing="0" width="640" style="width:100%;max-width:640px;background:#ffffff">
    <tr><td style="padding:32px 28px 18px">
        <h1 style="margin:0 0 12px;color:#09285e;font-size:24px;line-height:32px">{{ $copy['heading'] }}</h1>
        <p style="margin:0;color:#405270;font-size:15px;line-height:24px">{!! nl2br(e($copy['intro'])) !!}</p>
    </td></tr>
    <tr><td style="padding:0 28px 24px">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;background:#f7f9fc">
            @foreach ($fields as $label => $value)
                <tr>
                    <td style="width:35%;padding:11px 14px;border-bottom:1px solid #e5eaf2;color:#60708b;font-size:13px;font-weight:bold;vertical-align:top">{{ $label }}</td>
                    <td style="padding:11px 14px;border-bottom:1px solid #e5eaf2;font-size:14px;line-height:21px;vertical-align:top">{!! nl2br(e($value)) !!}</td>
                </tr>
            @endforeach
        </table>
    </td></tr>
    @if ($copy['closing'] !== '')
        <tr><td style="padding:0 28px 30px;color:#405270;font-size:13px;line-height:21px">{!! nl2br(e($copy['closing'])) !!}</td></tr>
    @endif
</table>
</body>
</html>
