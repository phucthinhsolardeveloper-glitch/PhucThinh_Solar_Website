{{-- Shared frame of every transactional email: header, content, footer. Tables and inline styles only (Outlook ignores stylesheets); the <style> block only adds phone tweaks where clients allow it. --}}
@php($tone = $copy['audience'] ?? 'staff')
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $copy['subject'] }}</title>
    <style>
        @media only screen and (max-width: 520px) {
            .px { padding-left: 18px !important; padding-right: 18px !important; }
            .col { display: block !important; width: 100% !important; box-sizing: border-box; }
            .gap-m { padding-top: 12px !important; }
            .btn-cell { display: block !important; width: 100% !important; padding: 0 0 10px 0 !important; }
            .btn-table { width: 100% !important; }
            .btn-cell a { display: block !important; text-align: center; }
            .hide-m { display: none !important; }
            .h1 { font-size: 21px !important; line-height: 28px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#eaeff6;font-family:-apple-system,'Segoe UI',Roboto,Arial,Helvetica,sans-serif;color:#15284b;-webkit-font-smoothing:antialiased;-webkit-text-size-adjust:100%">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all">{{ \Illuminate\Support\Str::limit($copy['intro'], 120) }}&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background:#eaeff6">
    <tr>
        <td align="center" style="padding:24px 12px 32px" valign="top">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="640" style="width:100%;max-width:640px;background:#ffffff;border-radius:16px;border:1px solid #dde4ef;border-collapse:separate;overflow:hidden">
                {{-- Header: logo on navy, gold rule --}}
                <tr>
                    <td class="px" style="padding:22px 32px;background:#09285e" valign="middle">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td valign="middle">
                                    <a href="{{ $brand['home'] }}" style="display:inline-block;text-decoration:none" target="_blank">
                                        @if ($brand['logo'] !== '')
                                            <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}" width="188" style="display:block;width:188px;max-width:100%;height:auto;border:0">
                                        @else
                                            <span style="color:#ffffff;font-size:20px;font-weight:bold">{{ $brand['name'] }}</span>
                                        @endif
                                    </a>
                                </td>
                                @if ($brand['hotline'] !== '' && $tone === 'customer')
                                    <td align="right" valign="middle" class="hide-m" style="color:#dbe7ff;font-size:12px;line-height:18px">
                                        Hotline<br><a href="tel:{{ preg_replace('/[^+\d]/', '', $brand['hotline']) }}" style="color:#f1c75b;font-size:16px;font-weight:bold;text-decoration:none">{{ $brand['hotline'] }}</a>
                                    </td>
                                @endif
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr><td style="height:4px;line-height:4px;font-size:0;background:#e0a92e;background-image:linear-gradient(90deg,#f1c75b,#d9a326)">&nbsp;</td></tr>
                @yield('content')
                {{-- Footer --}}
                <tr>
                    <td class="px" style="padding:24px 32px 26px;background:#f4f7fb;border-top:1px solid #e1e8f2;color:#52627f;font-size:12px;line-height:19px">
                        <strong style="color:#09285e;font-size:13px">{{ $brand['name'] }}</strong><br>
                        @if ($brand['address'] !== ''){{ $brand['address'] }}<br>@endif
                        @if ($brand['hotline'] !== '')Tư vấn bán hàng <a href="tel:{{ preg_replace('/[^+\d]/', '', $brand['hotline']) }}" style="color:#09285e;font-weight:bold;text-decoration:none">{{ $brand['hotline'] }}</a>@endif
                        @if ($brand['technicalHotline'] !== '') &nbsp;·&nbsp; Kỹ thuật <a href="tel:{{ preg_replace('/[^+\d]/', '', $brand['technicalHotline']) }}" style="color:#09285e;font-weight:bold;text-decoration:none">{{ $brand['technicalHotline'] }}</a>@endif
                        <br>{{ $brand['workingHours'] }} &nbsp;·&nbsp; <a href="{{ $brand['home'] }}" style="color:#09285e;text-decoration:none" target="_blank">{{ preg_replace('~^https?://~', '', rtrim($brand['home'], '/')) }}</a>
                        @if ($brand['social'] !== [])
                            <br>@foreach ($brand['social'] as $index => $account)@if ($index > 0) &nbsp;·&nbsp; @endif<a href="{{ $account['href'] }}" style="color:#52627f;text-decoration:underline" target="_blank">{{ $account['label'] }}</a>@endforeach
                        @endif
                        <div style="margin-top:14px;padding-top:12px;border-top:1px solid #dde5f0;color:#7c8aa5;font-size:11px;line-height:17px">Email này được gửi tự động từ website. Vui lòng không chia sẻ thông tin đơn hàng hoặc tài khoản cho người không liên quan.</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
