@if (($copy['closing'] ?? '') !== '')
    <tr>
        <td class="px" style="padding:20px 32px 32px">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><td style="padding:14px 18px;background:#fdf7e6;border-left:4px solid #d9a326;border-radius:0 10px 10px 0;color:#5a4a1f;font-size:13.5px;line-height:22px">{!! nl2br(e($copy['closing'])) !!}</td></tr>
            </table>
        </td>
    </tr>
@else
    <tr><td style="height:26px;line-height:26px;font-size:0">&nbsp;</td></tr>
@endif
