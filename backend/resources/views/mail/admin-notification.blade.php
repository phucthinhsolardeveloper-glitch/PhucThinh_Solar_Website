{{-- Internal notice of a new lead. Tables and inline styles only: Outlook ignores stylesheets and flexbox. --}}
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
                    <td style="padding:8px 28px 24px">
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
                @if ($copy['closing'] !== '')
                    <tr>
                        <td style="padding:0 28px 28px">
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
