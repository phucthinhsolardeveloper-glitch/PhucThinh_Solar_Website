{{-- Top of every transactional email: navy band, logo, gold rule, one line of what we do. --}}
<tr>
    <td align="center" style="padding:0 12px" valign="top">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="640" style="width:100%;max-width:640px;background:#09285e;border-radius:14px 14px 0 0">
            <tr>
                <td style="padding:24px 28px 20px;border-bottom:4px solid #d9a326">
                    <a href="{{ $brand['home'] }}" style="display:inline-block;text-decoration:none" target="_blank">
                        @if ($brand['logo'] !== '')
                            <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}" width="210" style="display:block;width:210px;max-width:100%;height:auto;border:0">
                        @else
                            <span style="color:#ffffff;font-size:20px;font-weight:bold;letter-spacing:.01em">{{ $brand['name'] }}</span>
                        @endif
                    </a>
                </td>
            </tr>
            <tr>
                <td style="padding:11px 28px;color:#dbe7ff;font-size:12px;line-height:18px;letter-spacing:.2px">{{ $brand['tagline'] }}</td>
            </tr>
        </table>
    </td>
</tr>
