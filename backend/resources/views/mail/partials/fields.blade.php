{{-- Label/value details. Short values sit two to a row, long ones (address, message) take the whole row. --}}
@php
    $skip = $skip ?? [];
    $rows = [];
    $pending = null;
    foreach ($fields as $label => $value) {
        $value = trim((string) $value);
        if (in_array($label, $skip, true) || $value === '') {
            continue;
        }
        $long = mb_strlen($value) > 42 || str_contains($value, "\n");
        if ($long) {
            if ($pending !== null) { $rows[] = [$pending]; $pending = null; }
            $rows[] = [[$label, $value]];
        } elseif ($pending === null) {
            $pending = [$label, $value];
        } else {
            $rows[] = [$pending, [$label, $value]];
            $pending = null;
        }
    }
    if ($pending !== null) { $rows[] = [$pending]; }
    $cell = fn (array $f, bool $mail = false) => ['label' => $f[0], 'value' => $f[1], 'isEmail' => $mail || filter_var($f[1], FILTER_VALIDATE_EMAIL), 'isPhone' => preg_match('/^[+\d][\d\s.\-]{7,}$/', $f[1])];
@endphp
@if ($rows !== [])
    <tr>
        <td class="px" style="padding:26px 32px 4px">
            @if (! empty($title))<div style="margin:0 0 10px;color:#09285e;font-size:14px;line-height:20px;font-weight:bold">{{ $title }}</div>@endif
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:separate;border-spacing:0;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px">
                @foreach ($rows as $r => $pair)
                    @php($border = $r < count($rows) - 1 ? 'border-bottom:1px solid #e2e8f2;' : '')
                    <tr>
                        @foreach ($pair as $i => $f)
                            @php($c = $cell($f))
                            <td class="col" width="{{ count($pair) === 1 ? '100%' : '50%' }}" colspan="{{ count($pair) === 1 ? 2 : 1 }}" valign="top" style="padding:13px 18px 12px;{{ $border }}">
                                <div style="color:#6a7a95;font-size:12px;line-height:17px;font-weight:bold;letter-spacing:.2px">{{ $c['label'] }}</div>
                                <div style="margin-top:3px;color:#15284b;font-size:14.5px;line-height:22px;word-break:break-word">
                                    @if ($c['isEmail'])<a href="mailto:{{ $c['value'] }}" style="color:#09285e;text-decoration:none">{{ $c['value'] }}</a>
                                    @elseif ($c['isPhone'])<a href="tel:{{ preg_replace('/[^+\d]/', '', $c['value']) }}" style="color:#09285e;text-decoration:none;font-weight:bold">{{ $c['value'] }}</a>
                                    @else{!! nl2br(e($c['value'])) !!}@endif
                                </div>
                            </td>
                        @endforeach
                        @if (count($pair) === 1 && false)<td></td>@endif
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
@endif
