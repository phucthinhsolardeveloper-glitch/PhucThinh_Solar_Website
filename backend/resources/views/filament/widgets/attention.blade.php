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
                        </div>
                    </li>
                @endforeach
            </ul>
            <a href="{{ App\Filament\Pages\WorkQueuePage::getUrl() }}" class="pts-queue__more">Mở "Việc cần làm" để xử lý từng mục</a>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
