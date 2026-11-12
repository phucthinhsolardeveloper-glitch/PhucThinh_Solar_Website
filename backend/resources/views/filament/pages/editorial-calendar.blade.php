@php($start = $this->start())
<x-filament-panels::page>
    <div class="pts-cal__bar">
        <x-filament::button color="gray" size="sm" wire:click="shift(-1)" icon="heroicon-m-chevron-left">Tháng trước</x-filament::button>
        <strong class="pts-cal__month">Tháng {{ $start->format('m/Y') }}</strong>
        <x-filament::button color="gray" size="sm" wire:click="shift(1)" icon="heroicon-m-chevron-right" icon-position="after">Tháng sau</x-filament::button>
        <a class="text-sm font-semibold underline" href="{{ \App\Filament\Resources\EditorialPlanItems\EditorialPlanItemResource::getUrl() }}">Quản lý kế hoạch 180 bài</a>
        <span class="pts-cal__legend">
            <x-filament::badge color="success">Đang hiện</x-filament::badge>
            <x-filament::badge color="info">Hẹn giờ</x-filament::badge>
            <x-filament::badge color="gray">Đang ẩn</x-filament::badge>
            <x-filament::badge color="warning">Bài trong kế hoạch</x-filament::badge>
        </span>
    </div>

    <div class="pts-cal" role="table" aria-label="Lịch biên tập tháng {{ $start->format('m/Y') }}">
        @foreach (['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'] as $dayName)
            <div class="pts-cal__head" role="columnheader">{{ $dayName }}</div>
        @endforeach
        @foreach ($this->weeks() as $week)
            @foreach ($week as $day)
                <div @class(['pts-cal__day', 'is-out' => ! $day['inMonth'], 'is-today' => $day['date']->isToday()]) role="cell">
                    <span class="pts-cal__date">{{ $day['date']->format('j') }}</span>
                    @foreach ($day['posts'] as $post)
                        <a href="{{ $post['url'] }}" @class(['pts-cal__post', 'is-'.$post['status']['key']]) title="{{ $post['kind'] }} · {{ $post['status']['label'] }}">
                            <span class="pts-cal__time">{{ $post['time'] }}</span> {{ $post['title'] }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        @endforeach
    </div>

    <x-filament::section heading="Bản nháp chưa đăng" description="Bản nháp chưa có ngày trên lịch: đang viết, chờ duyệt hoặc bị trả lại.">
        @php($drafts = $this->drafts())
        @if ($drafts === [])
            <p class="text-sm text-gray-600 dark:text-gray-300">Không có bản nháp nào.</p>
        @else
            <ul class="pts-queue__list">
                @foreach ($drafts as $draft)
                    <li>
                        <a href="{{ $draft['url'] }}" class="pts-attention__link">{{ $draft['title'] }}</a>
                        <span class="pts-attention__sub">{{ $draft['state'] }}@if ($draft['note'] !== '') · {{ $draft['note'] }}@endif</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-panels::page>
