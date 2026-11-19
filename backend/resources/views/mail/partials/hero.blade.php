{{-- Title block: a small status chip, the heading and the opening paragraph. --}}
@php($palette = ($copy['audience'] ?? 'staff') === 'customer' ? ['bg' => '#e6f4ea', 'fg' => '#1e6b3a', 'dot' => '#2e9b57'] : ['bg' => '#fdf1cf', 'fg' => '#7a5605', 'dot' => '#d9a326'])
<tr>
    <td class="px" style="padding:30px 32px 6px">
        @if (($copy['badge'] ?? '') !== '')
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin:0 0 14px">
                <tr>
                    <td style="background:{{ $palette['bg'] }};border-radius:999px;padding:5px 13px 5px 11px;color:{{ $palette['fg'] }};font-size:12px;line-height:16px;font-weight:bold">
                        <span style="color:{{ $palette['dot'] }};font-size:10px">&#9679;</span>&nbsp;{{ $copy['badge'] }}
                    </td>
                </tr>
            </table>
        @endif
        <h1 class="h1" style="margin:0 0 10px;color:#09285e;font-size:25px;line-height:33px;font-weight:bold">{{ $copy['heading'] }}</h1>
        <p style="margin:0;color:#44546f;font-size:15px;line-height:25px">{!! nl2br(e($copy['intro'])) !!}</p>
    </td>
</tr>
