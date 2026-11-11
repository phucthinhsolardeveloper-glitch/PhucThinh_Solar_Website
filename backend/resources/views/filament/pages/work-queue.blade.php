@php($sections = $this->sections())
<x-filament-panels::page>
    <div class="pts-queue__bar">
        <x-filament::tabs>
            <x-filament::tabs.item :active="! $mine" wire:click="$set('mine', false)">Tất cả</x-filament::tabs.item>
            <x-filament::tabs.item :active="$mine" wire:click="$set('mine', true)">Của tôi và chưa phân công</x-filament::tabs.item>
        </x-filament::tabs>
    </div>

    @if ($sections === [])
        <x-filament::section>
            <p class="text-sm text-gray-600 dark:text-gray-300">Không có việc nào đang chờ.</p>
        </x-filament::section>
    @endif

    <div class="pts-queue">
        @foreach ($sections as $section)
            <x-filament::section>
                <x-slot name="heading">
                    <span class="pts-queue__heading">
                        <span @class(['pts-attention__dot', 'is-'.$section['tone']]) aria-hidden="true"></span>
                        {{ $section['title'] }}
                        <x-filament::badge :color="$section['tone'] === 'danger' ? 'danger' : ($section['tone'] === 'warning' ? 'warning' : 'gray')">{{ $section['count'] }}</x-filament::badge>
                    </span>
                </x-slot>
                <ul class="pts-queue__list">
                    @foreach ($section['items'] as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="pts-attention__link">{{ $item['label'] }}</a>
                            <span class="pts-attention__sub">{{ $item['meta'] }}</span>
                        </li>
                    @endforeach
                </ul>
                @if ($section['more'] && $section['count'] > count($section['items']))
                    <a href="{{ $section['more'] }}" class="pts-queue__more">Xem cả {{ $section['count'] }} mục</a>
                @endif
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
