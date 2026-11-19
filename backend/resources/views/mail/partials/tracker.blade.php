{{-- Where the order stands: received now, then confirmation, shipping and delivery. --}}
<tr>
    <td class="px" style="padding:26px 32px 4px">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                @foreach (['Đã tiếp nhận', 'Xác nhận đơn', 'Giao hàng', 'Hoàn tất'] as $i => $step)
                    <td width="25%" valign="top" style="padding:0 {{ $i < 3 ? '5px' : '0' }} 0 {{ $i > 0 ? '5px' : '0' }}">
                        <div style="height:5px;line-height:5px;font-size:0;border-radius:3px;background:{{ $i === 0 ? '#2e9b57' : '#dde5f1' }}">&nbsp;</div>
                        <div style="margin-top:8px;font-size:12.5px;line-height:17px;color:{{ $i === 0 ? '#1e6b3a' : '#7c8aa5' }};font-weight:{{ $i === 0 ? 'bold' : 'normal' }}">{{ $i === 0 ? '✓ ' : '' }}{{ $step }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    </td>
</tr>
