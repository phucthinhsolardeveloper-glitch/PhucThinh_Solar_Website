{{ $copy['heading'] }}

{{ $copy['intro'] }}

@foreach ($fields as $label => $value)
{{ $label }}: {{ $value }}
@endforeach

SẢN PHẨM ĐÃ ĐẶT
@foreach ($items as $item)
- {{ $item['name'] }}{{ $item['option'] !== '' ? ' ('.$item['option'].')' : '' }} x {{ $item['quantity'] }}: {{ \App\Mail\OrderNotification::money($item['total']) }}
@endforeach
Tạm tính: {{ \App\Mail\OrderNotification::money($totals['subtotal']) }}
Phí vận chuyển: {{ $totals['shipping'] > 0 ? \App\Mail\OrderNotification::money($totals['shipping']) : 'Chờ xác nhận' }}
Tổng cộng: {{ \App\Mail\OrderNotification::money($totals['total']) }}
@if ($copy['closing'] !== '')

{{ $copy['closing'] }}
@endif

--
{{ $brand['name'] }}
@if ($brand['hotline'] !== '')Tư vấn bán hàng: {{ $brand['hotline'] }}
@endif
@if ($brand['technicalHotline'] !== '')Hỗ trợ kỹ thuật: {{ $brand['technicalHotline'] }}
@endif
Thời gian làm việc: {{ $brand['workingHours'] }}
{{ $brand['home'] }}
Email này được gửi tự động từ website.
