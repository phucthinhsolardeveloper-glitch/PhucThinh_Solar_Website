{{ $copy['heading'] }}

{{ $copy['intro'] }}

@foreach ($fields as $label => $value)
{{ $label }}: {{ $value }}
@endforeach
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
