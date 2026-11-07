{{ $copy['heading'] }}

{{ $copy['intro'] }}

@foreach ($fields as $label => $value)
{{ $label }}: {{ $value }}
@endforeach

{{ $copy['closing'] }}
