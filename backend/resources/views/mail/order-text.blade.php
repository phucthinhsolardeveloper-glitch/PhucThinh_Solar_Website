{{ $copy['heading'] }}

{{ $copy['intro'] }}

@foreach ($fields as $label => $value)
{{ $label }}: {{ $value }}
@endforeach

@foreach ($items as $item)
- {{ $item['name'] }}{{ $item['option'] !== '' ? ' ('.$item['option'].')' : '' }} x {{ $item['quantity'] }}: {{ \App\Mail\OrderNotification::money($item['total']) }}
@endforeach
Tạm tính: {{ \App\Mail\OrderNotification::money($totals['subtotal']) }}
Phí vận chuyển: {{ $totals['shipping'] > 0 ? \App\Mail\OrderNotification::money($totals['shipping']) : 'Chờ xác nhận' }}
Tổng cộng: {{ \App\Mail\OrderNotification::money($totals['total']) }}

{{ $copy['closing'] }}
