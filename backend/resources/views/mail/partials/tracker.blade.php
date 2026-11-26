{{-- Where the order stands: received, confirmation, shipping, delivery. --}}
@php($reached = (int) ($copy['step'] ?? 0))
<tr>
    <td class="px" style="padding:22px 32px 2px">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                @foreach (['Đã tiếp nhận', 'Xác nhận đơn', 'Giao hàng', 'Hoàn tất'] as $i => $step)
                    <td width="25%" valign="top" style="padding:0 {{ $i < 3 ? '4px' : '0' }} 0 {{ $i > 0 ? '4px' : '0' }}">
                        <div style="height:4px;line-height:4px;font-size:0;border-radius:2px;background:{{ $i <= $reached ? '#2e9b57' : '#e1e7f0' }}">&nbsp;</div>
                        <div style="margin-top:7px;font-size:12px;line-height:16px;color:{{ $i <= $reached ? '#1e6b3a' : '#8a97ad' }};font-weight:{{ $i <= $reached ? 'bold' : 'normal' }}">{{ $step }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    </td>
</tr>
