@php
    $money = fn ($v) => \App\Mail\OrderNotification::money((float) $v);
        $who = array_filter(array_intersect_key($fields, array_flip(['Họ tên', 'Điện thoại', 'Email'])), fn ($v) => trim((string) $v) !== '');
        $where = array_filter(array_intersect_key($fields, array_flip(['Địa chỉ', 'Yêu cầu thêm'])), fn ($v) => trim((string) $v) !== '');
        $other = array_diff_key($fields, array_flip(['Mã đơn hàng', 'Thanh toán', 'Họ tên', 'Điện thoại', 'Email', 'Địa chỉ', 'Yêu cầu thêm']));
@endphp
{{-- Order mail, to the shop and to the buyer. --}}
@extends('mail.layout')
@section('content')
    @if (($copy['audience'] ?? 'staff') === 'customer' && ($copy['step'] ?? 0) >= 0)
        @include('mail.partials.tracker')
    @endif
    {{-- Summary: what the order is and how much --}}
    <tr>
        <td class="px" style="padding:22px 32px 4px">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:separate;border-spacing:0;border:1px solid #d5e0f0;border-radius:14px;background:#f5f8fd">
                <tr>
                    <td class="col" width="36%" valign="top" style="padding:16px 20px">
                        <div style="color:#6a7a95;font-size:12.5px;line-height:17px">Mã đơn hàng</div>
                        <div style="margin-top:3px;color:#09285e;font-size:18px;line-height:25px;font-weight:bold">{{ $fields['Mã đơn hàng'] ?? '—' }}</div>
                    </td>
                    <td class="col gap-m" width="32%" valign="top" style="padding:16px 20px">
                        <div style="color:#6a7a95;font-size:12.5px;line-height:17px">Thanh toán</div>
                        <div style="margin-top:3px;color:#15284b;font-size:15px;line-height:25px">{{ $fields['Thanh toán'] ?? '—' }}</div>
                    </td>
                    <td class="col gap-m" width="32%" valign="top" style="padding:16px 20px">
                        <div style="color:#6a7a95;font-size:12.5px;line-height:17px">Tổng cộng</div>
                        <div style="margin-top:3px;color:#09285e;font-size:20px;line-height:25px;font-weight:bold">{{ $money($totals['total']) }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    {{-- Who gets it and where: one card, two halves, so both always have the same height --}}
    <tr>
        <td class="px" style="padding:20px 32px 4px">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:separate;border-spacing:0;border:1px solid #e2e8f2;border-top:3px solid #d9a326;border-radius:12px">
                <tr>
                    <td class="col px-card" width="50%" valign="top" style="padding:16px 20px 18px;border-right:1px solid #e9eef6">
                        <div style="margin-bottom:8px;color:#09285e;font-size:14px;line-height:20px;font-weight:bold">Người nhận</div>
                        @foreach ($who as $label => $value)
                            <div style="margin-top:{{ $loop->first ? '0' : '6px' }};color:#15284b;font-size:{{ $loop->first ? '16px' : '14.5px' }};line-height:22px;{{ $loop->first ? 'font-weight:bold;' : '' }}">
                                @if ($label === 'Điện thoại')<a href="tel:{{ preg_replace('/[^+\d]/', '', $value) }}" style="color:#09285e;text-decoration:none;font-weight:bold">{{ $value }}</a>
                                @elseif ($label === 'Email')<a href="mailto:{{ $value }}" style="color:#44546f;text-decoration:none;word-break:break-all">{{ $value }}</a>
                                @else{{ $value }}@endif
                            </div>
                        @endforeach
                    </td>
                    <td class="col gap-m" width="50%" valign="top" style="padding:16px 20px 18px">
                        <div style="margin-bottom:8px;color:#09285e;font-size:14px;line-height:20px;font-weight:bold">Giao đến</div>
                        @foreach ($where as $label => $value)
                            @if ($label === 'Yêu cầu thêm')<div style="margin-top:10px;color:#6a7a95;font-size:12.5px;line-height:17px">Yêu cầu thêm</div>@endif
                            <div style="margin-top:{{ $label === 'Yêu cầu thêm' ? '2px' : '0' }};color:#15284b;font-size:14.5px;line-height:22px">{!! nl2br(e($value)) !!}</div>
                        @endforeach
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @if ($other !== [])
        @include('mail.partials.fields', ['fields' => $other])
    @endif
    {{-- Items --}}
    <tr>
        <td class="px" style="padding:26px 32px 4px">
            <div style="margin:0 0 6px;color:#09285e;font-size:15px;line-height:21px;font-weight:bold">Sản phẩm đã đặt ({{ count($items) }})</div>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse">
                @foreach ($items as $item)
                    <tr>
                        <td width="76" valign="top" style="padding:14px 14px 14px 0;border-bottom:1px solid #e9eef6">
                            @if (! empty($item['image']))
                                <img src="{{ $item['image'] }}" alt="" width="62" height="62" style="display:block;width:62px;height:62px;object-fit:contain;background:#f5f8fd;border:1px solid #e2e8f2;border-radius:10px">
                            @else
                                <div style="width:62px;height:62px;background:#f5f8fd;border:1px solid #e2e8f2;border-radius:10px">&nbsp;</div>
                            @endif
                        </td>
                        <td valign="top" style="padding:14px 10px 14px 0;border-bottom:1px solid #e9eef6;color:#15284b;font-size:14.5px;line-height:21px">
                            <strong style="font-weight:600">{{ $item['name'] }}</strong>
                            @if ($item['option'] !== '')<br><span style="color:#6a7a95;font-size:12.5px">{{ $item['option'] }}</span>@endif
                            <br><span style="color:#6a7a95;font-size:13px">{{ $money($item['price']) }} × {{ $item['quantity'] }}</span>
                        </td>
                        <td align="right" valign="top" style="padding:14px 0;border-bottom:1px solid #e9eef6;white-space:nowrap;color:#09285e;font-size:15px;line-height:21px;font-weight:bold">{{ $money($item['total']) }}</td>
                    </tr>
                @endforeach
            </table>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top:6px">
                <tr>
                    <td style="padding:10px 4px 2px;color:#6a7a95;font-size:14px">Tạm tính</td>
                    <td align="right" style="padding:10px 4px 2px;color:#15284b;font-size:14px;white-space:nowrap">{{ $money($totals['subtotal']) }}</td>
                </tr>
                <tr>
                    <td style="padding:2px 4px 12px;color:#6a7a95;font-size:14px">Phí vận chuyển</td>
                    <td align="right" style="padding:2px 4px 12px;color:#15284b;font-size:14px;white-space:nowrap">{{ $totals['shipping'] > 0 ? $money($totals['shipping']) : 'Chờ xác nhận' }}</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding:0">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:separate;background:#09285e;background-image:linear-gradient(135deg,#082457,#0b3b86);border-radius:12px">
                            <tr>
                                <td style="padding:16px 20px;color:#dbe6fb;font-size:15px;font-weight:bold">Tổng cộng</td>
                                <td align="right" style="padding:16px 20px;color:#f1c75b;font-size:23px;line-height:28px;font-weight:bold;white-space:nowrap">{{ $money($totals['total']) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @if ($sheetAttached ?? false)
        <tr>
            <td class="px" style="padding:16px 32px 0">
                <div style="padding:12px 16px;border:1px solid #e6d3a0;border-radius:12px;background:#fdf7e8;color:#15284b;font-size:13.5px;line-height:20px">Phiếu đơn hàng (PDF) được đính kèm email này.</div>
            </td>
        </tr>
    @endif
    @include('mail.partials.actions')
    @include('mail.partials.closing')
@endsection
