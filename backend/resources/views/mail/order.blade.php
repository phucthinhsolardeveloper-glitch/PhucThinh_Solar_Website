@php($money = fn ($v) => \App\Mail\OrderNotification::money((float) $v))
{{-- Order mail, to the shop and to the buyer. --}}
@extends('mail.layout')
@section('content')
    @include('mail.partials.hero')
    {{-- Summary strip: what the order is and how much --}}
    <tr>
        <td class="px" style="padding:20px 32px 4px">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:separate;border-spacing:0;border:1px solid #c9d6ea;border-radius:12px;background:#f2f6fc">
                <tr>
                    <td class="col" width="34%" valign="top" style="padding:14px 18px">
                        <div style="color:#6a7a95;font-size:12px;line-height:17px;font-weight:bold">Mã đơn hàng</div>
                        <div style="margin-top:3px;color:#09285e;font-size:17px;line-height:24px;font-weight:bold">{{ $fields['Mã đơn hàng'] ?? '—' }}</div>
                    </td>
                    <td class="col gap-m" width="33%" valign="top" style="padding:14px 18px">
                        <div style="color:#6a7a95;font-size:12px;line-height:17px;font-weight:bold">Thanh toán</div>
                        <div style="margin-top:3px;color:#15284b;font-size:14.5px;line-height:24px">{{ $fields['Thanh toán'] ?? '—' }}</div>
                    </td>
                    <td class="col gap-m" width="33%" valign="top" style="padding:14px 18px">
                        <div style="color:#6a7a95;font-size:12px;line-height:17px;font-weight:bold">Tổng cộng</div>
                        <div style="margin-top:3px;color:#09285e;font-size:20px;line-height:24px;font-weight:bold">{{ $money($totals['total']) }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @include('mail.partials.fields', ['fields' => $fields, 'skip' => ['Mã đơn hàng', 'Thanh toán'], 'title' => 'Người nhận và giao hàng'])
    {{-- Items --}}
    <tr>
        <td class="px" style="padding:22px 32px 4px">
            <div style="margin:0 0 8px;color:#09285e;font-size:14px;line-height:20px;font-weight:bold">Sản phẩm đã đặt ({{ count($items) }})</div>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse">
                @foreach ($items as $item)
                    <tr>
                        <td width="64" valign="top" style="padding:12px 12px 12px 0;border-top:1px solid #e2e8f2">
                            @if (! empty($item['image']))
                                <img src="{{ $item['image'] }}" alt="" width="56" height="56" style="display:block;width:56px;height:56px;object-fit:contain;background:#f6f8fc;border:1px solid #e2e8f2;border-radius:8px">
                            @else
                                <div style="width:56px;height:56px;background:#f6f8fc;border:1px solid #e2e8f2;border-radius:8px">&nbsp;</div>
                            @endif
                        </td>
                        <td valign="top" style="padding:12px 8px 12px 0;border-top:1px solid #e2e8f2;color:#15284b;font-size:14px;line-height:21px">
                            <strong style="font-weight:600">{{ $item['name'] }}</strong>
                            @if ($item['option'] !== '')<br><span style="color:#6a7a95;font-size:12.5px">{{ $item['option'] }}</span>@endif
                            <br><span style="color:#6a7a95;font-size:12.5px">{{ $money($item['price']) }} × {{ $item['quantity'] }}</span>
                        </td>
                        <td align="right" valign="top" style="padding:12px 0;border-top:1px solid #e2e8f2;white-space:nowrap;color:#09285e;font-size:14.5px;line-height:21px;font-weight:bold">{{ $money($item['total']) }}</td>
                    </tr>
                @endforeach
            </table>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="right" width="300" style="width:100%;max-width:300px;margin-top:4px;border-top:2px solid #09285e">
                <tr>
                    <td style="padding:10px 0 4px;color:#6a7a95;font-size:13.5px">Tạm tính</td>
                    <td align="right" style="padding:10px 0 4px;color:#15284b;font-size:13.5px;white-space:nowrap">{{ $money($totals['subtotal']) }}</td>
                </tr>
                <tr>
                    <td style="padding:2px 0 10px;color:#6a7a95;font-size:13.5px">Phí vận chuyển</td>
                    <td align="right" style="padding:2px 0 10px;color:#15284b;font-size:13.5px;white-space:nowrap">{{ $totals['shipping'] > 0 ? $money($totals['shipping']) : 'Chờ xác nhận' }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0 2px;border-top:1px solid #dde4ef;color:#09285e;font-size:15px;font-weight:bold">Tổng cộng</td>
                    <td align="right" style="padding:10px 0 2px;border-top:1px solid #dde4ef;color:#09285e;font-size:20px;font-weight:bold;white-space:nowrap">{{ $money($totals['total']) }}</td>
                </tr>
            </table>
            <div style="clear:both;height:1px;line-height:1px;font-size:0">&nbsp;</div>
        </td>
    </tr>
    @include('mail.partials.actions')
    @include('mail.partials.closing')
@endsection
