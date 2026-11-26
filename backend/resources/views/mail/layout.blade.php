{{-- Shared frame of every transactional email. Tables and inline styles only (Outlook ignores stylesheets); the <style> block only adds phone tweaks and long-word wrapping where clients allow it. --}}
@php
    $tone = $copy['audience'] ?? 'staff';
    $tel = fn (string $number) => preg_replace('/[^+\d]/', '', $number);
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $copy['subject'] }}</title>
    <style>
        /* A name, address or e-mail typed without spaces wraps instead of widening the whole mail. */
        td, div, p, strong, span, a { word-break: break-word; }
        @media only screen and (max-width: 520px) {
            .px { padding-left: 20px !important; padding-right: 20px !important; }
            .col { display: block !important; width: 100% !important; box-sizing: border-box; }
            .gap-m { padding-top: 14px !important; }
            .btn-cell { display: block !important; width: 100% !important; padding: 0 0 10px 0 !important; }
            .btn-table { width: 100% !important; }
            .btn-cell a { display: block !important; text-align: center; }
            .hide-m { display: none !important; }
            .h1 { font-size: 22px !important; line-height: 30px !important; }
            .qr-cell { padding: 0 0 16px 0 !important; text-align: center; }
            .tl-m { text-align: left !important; }
        }
    </style>
</head>
<body style="word-wrap:break-word;overflow-wrap:break-word;margin:0;padding:0;background:#eef2f7;font-family:-apple-system,'Segoe UI',Roboto,Arial,Helvetica,sans-serif;color:#15284b;-webkit-font-smoothing:antialiased;-webkit-text-size-adjust:100%">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all">{{ \Illuminate\Support\Str::limit($copy['intro'], 120) }}&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background:#eef2f7">
    <tr>
        <td align="center" style="padding:28px 12px 36px" valign="top">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="width:100%;max-width:600px;background:#ffffff;border-radius:14px;border:1px solid #dfe6f0;border-collapse:separate;overflow:hidden">
                {{-- Brand bar: logo, and the hotline for customers --}}
                <tr>
                    <td class="px" style="padding:18px 32px;background:#09285e;border-bottom:3px solid #d9a326" valign="middle">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td valign="middle">
                                    <a href="{{ $brand['home'] }}" style="display:inline-block;text-decoration:none" target="_blank">
                                        @if ($brand['logo'] !== '')
                                            <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}" width="156" style="display:block;width:156px;max-width:100%;height:auto;border:0">
                                        @else
                                            <span style="color:#ffffff;font-size:18px;font-weight:bold">{{ $brand['name'] }}</span>
                                        @endif
                                    </a>
                                </td>
                                @if ($brand['hotline'] !== '' && $tone === 'customer')
                                    <td align="right" valign="middle" class="hide-m" style="color:#b9cbec;font-size:12px;line-height:16px">
                                        Hotline <a href="tel:{{ $tel($brand['hotline']) }}" style="color:#f1c75b;font-size:15px;font-weight:bold;text-decoration:none">{{ $brand['hotline'] }}</a>
                                    </td>
                                @endif
                            </tr>
                        </table>
                    </td>
                </tr>
                {{-- What happened, in one status line, a heading and a short paragraph --}}
                <tr>
                    <td class="px" style="padding:30px 32px 6px" valign="top">
                        @if (($copy['badge'] ?? '') !== '')
                            <div style="margin:0 0 10px;color:{{ $tone === 'customer' ? '#1e7a46' : '#9a6b0e' }};font-size:13px;line-height:18px;font-weight:bold"><span style="display:inline-block;width:8px;height:8px;margin-right:7px;border-radius:4px;background:{{ $tone === 'customer' ? '#2e9b57' : '#d9a326' }};vertical-align:1px"></span>{{ $copy['badge'] }}</div>
                        @endif
                        <h1 class="h1" style="margin:0 0 10px;color:#09285e;font-size:24px;line-height:32px;font-weight:bold;text-wrap:balance">{{ $copy['heading'] }}</h1>
                        <p style="margin:0;color:#475775;font-size:15px;line-height:24px">{!! nl2br(e($copy['intro'])) !!}</p>
                    </td>
                </tr>
                @yield('content')
                {{-- Footer --}}
                <tr>
                    <td class="px" style="padding:22px 32px 24px;background:#f6f8fb;border-top:1px solid #e3e9f2;color:#5b6a84;font-size:12px;line-height:19px">
                        <strong style="color:#09285e;font-size:13px">{{ $brand['name'] }}</strong><br>
                        @if ($brand['address'] !== ''){{ $brand['address'] }}<br>@endif
                        @if ($brand['hotline'] !== '')Bán hàng <a href="tel:{{ $tel($brand['hotline']) }}" style="color:#09285e;font-weight:bold;text-decoration:none">{{ $brand['hotline'] }}</a>@endif
                        @if ($brand['technicalHotline'] !== '') &nbsp;·&nbsp; Kỹ thuật <a href="tel:{{ $tel($brand['technicalHotline']) }}" style="color:#09285e;font-weight:bold;text-decoration:none">{{ $brand['technicalHotline'] }}</a>@endif
                        <br>{{ $brand['workingHours'] }} &nbsp;·&nbsp; <a href="{{ $brand['home'] }}" style="color:#09285e;text-decoration:none" target="_blank">{{ preg_replace('~^https?://~', '', rtrim($brand['home'], '/')) }}</a>
                        @if ($brand['social'] !== [])
                            &nbsp;·&nbsp; @foreach ($brand['social'] as $index => $account)@if ($index > 0) · @endif<a href="{{ $account['href'] }}" style="color:#5b6a84;text-decoration:underline" target="_blank">{{ $account['label'] }}</a>@endforeach
                        @endif
                        <div style="margin-top:12px;color:#8592a8;font-size:11px;line-height:17px">Email gửi tự động từ website. Vui lòng không chia sẻ thông tin đơn hàng cho người không liên quan.</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
