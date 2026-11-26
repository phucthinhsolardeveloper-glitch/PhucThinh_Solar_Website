@php
    $money = fn ($v) => \App\Mail\OrderNotification::money((float) $v);
    $tel = fn (string $number) => preg_replace('/[^+\d]/', '', $number);
    $customer = ($copy['audience'] ?? 'staff') === 'customer';
    $field = fn (string $label) => trim((string) ($fields[$label] ?? ''));
    $other = array_filter(array_diff_key($fields, array_flip(['Mã đơn hàng', 'Thanh toán', 'Họ tên', 'Điện thoại', 'Email', 'Địa chỉ', 'Yêu cầu thêm'])), fn ($v) => trim((string) $v) !== '');
    $label = 'color:#6a7a95;font-size:12.5px;line-height:18px';
    // Two full-width rows: who (name, phone, email) and where (address, request). A narrow column broke an address or a
    // request one word from its end; four separate rows made the mail long on a phone.
    $who = trim($field('Họ tên').($field('Điện thoại') !== '' ? ' · '.$field('Điện thoại') : ''));
@endphp
{{-- Order mail, to the shop and to the buyer. --}}
@extends('mail.layout')
@section('content')
    @if ($customer && ($copy['step'] ?? 0) >= 0)
        @include('mail.partials.tracker')
    @endif
    {{-- Summary: which order, how it is paid, how much --}}
    <tr>
        <td class="px" style="padding:22px 32px 0">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:separate;border-spacing:0;background:#f5f8fc;border-radius:12px">
                <tr>
                    <td class="col" valign="top" style="padding:16px 20px 4px">
                        <div style="{{ $label }}">Mã đơn hàng</div>
                        <div style="margin-top:2px;color:#09285e;font-size:19px;line-height:26px;font-weight:bold;letter-spacing:.5px">{{ $field('Mã đơn hàng') ?: '—' }}</div>
                    </td>
                    {{-- A transfer order shows the amount once, in the transfer block below. --}}
                    @if (empty($transfer))
                    <td class="col gap-m tl-m" align="right" valign="top" style="padding:16px 20px 4px;text-align:right">
                        <div style="{{ $label }}">Tổng cộng</div>
                        <div style="margin-top:2px;color:#09285e;font-size:21px;line-height:26px;font-weight:bold;white-space:nowrap">{{ $money($totals['total']) }}</div>
                    </td>
                    @endif
                </tr>
                <tr>
                    <td colspan="2" style="padding:6px 20px 16px;color:#475775;font-size:14px;line-height:21px">Thanh toán: <strong style="color:#15284b">{{ $field('Thanh toán') ?: '—' }}</strong></td>
                </tr>
            </table>
        </td>
    </tr>
    @if (! empty($transfer))
        @include('mail.partials.transfer')
    @endif
    {{-- Who receives it and where --}}
    <tr>
        <td class="px" style="padding:18px 32px 0">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:separate;border-spacing:0;border:1px solid #e2e8f0;border-radius:12px">
                @if ($who !== '')
                    <tr>
                        <td style="padding:12px 18px">
                            <div style="{{ $label }}">Người nhận</div>
                            <div style="margin-top:2px;color:#15284b;font-size:14.5px;line-height:22px;font-weight:bold">{{ $field('Họ tên') }}@if ($field('Điện thoại') !== '') · <a href="tel:{{ $tel($field('Điện thoại')) }}" style="color:#09285e;text-decoration:none">{{ $field('Điện thoại') }}</a>@endif</div>
                            @if ($field('Email') !== '')<div style="color:#475775;font-size:13.5px;line-height:20px"><a href="mailto:{{ $field('Email') }}" style="color:#475775;text-decoration:none">{{ $field('Email') }}</a></div>@endif
                        </td>
                    </tr>
                @endif
                @if ($field('Địa chỉ') !== '' || $field('Yêu cầu thêm') !== '')
                    <tr>
                        <td style="padding:12px 18px;{{ $who !== '' ? 'border-top:1px solid #edf1f6;' : '' }}">
                            <div style="{{ $label }}">Giao đến</div>
                            <div style="margin-top:2px;color:#15284b;font-size:14.5px;line-height:22px">{!! nl2br(e($field('Địa chỉ') ?: '—')) !!}</div>
                            @if ($field('Yêu cầu thêm') !== '')<div style="margin-top:4px;color:#475775;font-size:13.5px;line-height:20px">Yêu cầu: {!! nl2br(e($field('Yêu cầu thêm'))) !!}</div>@endif
                        </td>
                    </tr>
                @endif
            </table>
        </td>
    </tr>
    @if ($other !== [])
        @include('mail.partials.fields', ['fields' => $other])
    @endif
    {{-- Items --}}
    <tr>
        <td class="px" style="padding:24px 32px 0">
            <div style="margin:0 0 4px;color:#09285e;font-size:15px;line-height:21px;font-weight:bold">Sản phẩm đã đặt ({{ count($items) }})</div>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse">
                @foreach ($items as $item)
                    <tr>
                        <td width="68" valign="top" style="padding:14px 12px 14px 0;border-bottom:1px solid #edf1f6">
                            @if (! empty($item['image']))
                                <img src="{{ $item['image'] }}" alt="" width="56" height="56" style="display:block;width:56px;height:56px;object-fit:contain;background:#f5f8fc;border:1px solid #e6ecf3;border-radius:10px">
                            @else
                                <div style="width:56px;height:56px;background:#f5f8fc;border:1px solid #e6ecf3;border-radius:10px">&nbsp;</div>
                            @endif
                        </td>
                        <td valign="top" style="padding:14px 10px 14px 0;border-bottom:1px solid #edf1f6;color:#15284b;font-size:14.5px;line-height:21px">
                            <strong style="font-weight:600">{{ $item['name'] }}</strong>
                            @if ($item['option'] !== '')<br><span style="color:#6a7a95;font-size:12.5px">{{ $item['option'] }}</span>@endif
                            <br><span style="color:#6a7a95;font-size:13px">{{ $money($item['price']) }} × {{ $item['quantity'] }}</span>
                        </td>
                        <td align="right" valign="top" style="padding:14px 0;border-bottom:1px solid #edf1f6;white-space:nowrap;color:#09285e;font-size:14.5px;line-height:21px;font-weight:bold">{{ $money($item['total']) }}</td>
                    </tr>
                @endforeach
            </table>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top:4px">
                <tr>
                    <td style="padding:10px 0 3px;color:#6a7a95;font-size:14px">Tạm tính</td>
                    <td align="right" style="padding:10px 0 3px;color:#15284b;font-size:14px;white-space:nowrap">{{ $money($totals['subtotal']) }}</td>
                </tr>
                <tr>
                    <td style="padding:3px 0 12px;color:#6a7a95;font-size:14px">Phí vận chuyển</td>
                    <td align="right" style="padding:3px 0 12px;color:#15284b;font-size:14px;white-space:nowrap">{{ $totals['shipping'] > 0 ? $money($totals['shipping']) : 'Chờ xác nhận' }}</td>
                </tr>
                <tr>
                    <td style="padding:12px 0 0;border-top:2px solid #09285e;color:#09285e;font-size:15px;font-weight:bold">Tổng cộng</td>
                    <td align="right" style="padding:12px 0 0;border-top:2px solid #09285e;color:#09285e;font-size:21px;line-height:26px;font-weight:bold;white-space:nowrap">{{ $money($totals['total']) }}</td>
                </tr>
            </table>
            @if ($sheetAttached ?? false)
                <div style="margin-top:16px;color:#5b6a84;font-size:13px;line-height:20px">Phiếu đơn hàng (PDF) được đính kèm email này.</div>
            @endif
        </td>
    </tr>
    @include('mail.partials.actions')
    @include('mail.partials.closing')
@endsection
