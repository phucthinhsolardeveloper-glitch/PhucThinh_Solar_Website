@if (($copy['closing'] ?? '') !== '')
    <tr>
        <td class="px" style="padding:18px 32px 30px;color:#5b6a84;font-size:14px;line-height:22px">{!! nl2br(e($copy['closing'])) !!}</td>
    </tr>
@else
    <tr><td style="height:24px;line-height:24px;font-size:0">&nbsp;</td></tr>
@endif
