@php($items = $this->items())
<x-filament-widgets::widget>
    <x-filament::section heading="Cần xử lý" :description="$items === [] ? 'Không có việc tồn đọng.' : null">
        @if ($items !== [])
            <ul class="pts-attention">
                @foreach ($items as $item)
                    <li>
                        <span @class(['pts-attention__dot', 'is-'.$item['tone']]) aria-hidden="true"></span>
                        <div>
                            @if ($item['url'] !== '')
                                <a href="{{ $item['url'] }}" class="pts-attention__link">{{ $item['text'] }}</a>
                            @else
                                <span>{{ $item['text'] }}</span>
                            @endif
                            @if (! empty($item['items']))
                                <div class="pts-attention__sub">
                                    @foreach ($item['items'] as $sub)
                                        <a href="{{ $sub['url'] }}">{{ $sub['name'] }}</a>@if (! $loop->last)<span aria-hidden="true"> · </span>@endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
