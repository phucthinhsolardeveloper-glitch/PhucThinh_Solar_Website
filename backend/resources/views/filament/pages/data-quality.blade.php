@php($groups = $this->groups())
@php($weekAgo = $this->weekAgo())
<x-filament-panels::page>
    @if ($groups === [])
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-300">Không phát hiện vấn đề nào.</p>
        </x-filament::section>
    @endif
    @foreach ($groups as $group => $checks)
        <x-filament::section :heading="$group">
            <div class="pts-dq">
                @foreach ($checks as $check)
                    <details class="pts-dq__check">
                        <summary>
                            <span @class(['pts-attention__dot', 'is-'.($check['severity'] === 'error' ? 'danger' : ($check['severity'] === 'warning' ? 'warning' : 'info'))]) aria-hidden="true"></span>
                            <span class="pts-dq__label">{{ $check['label'] }}</span>
                            <x-filament::badge :color="$check['severity'] === 'error' ? 'danger' : ($check['severity'] === 'warning' ? 'warning' : 'gray')">{{ $check['count'] }}</x-filament::badge>
                            @isset($weekAgo[$check['key']])
                                @php($delta = $check['count'] - $weekAgo[$check['key']])
                                <span @class(['pts-dq__trend', 'is-better' => $delta < 0, 'is-worse' => $delta > 0])>
                                    7 ngày trước: {{ $weekAgo[$check['key']] }}@if ($delta !== 0) ({{ $delta > 0 ? '+' : '' }}{{ $delta }})@endif
                                </span>
                            @endisset
                        </summary>
                        <ul class="pts-dq__items">
                            @foreach ($check['items'] as $item)
                                <li>@if ($item['url'] !== '')<a href="{{ $item['url'] }}">{{ $item['name'] }}</a>@else{{ $item['name'] }}@endif</li>
                            @endforeach
                            @if ($check['count'] > count($check['items']))
                                <li class="pts-attention__sub">… và {{ $check['count'] - count($check['items']) }} mục khác</li>
                            @endif
                        </ul>
                    </details>
                @endforeach
            </div>
        </x-filament::section>
    @endforeach
</x-filament-panels::page>
