@php($start = $this->start())
@php($labels = \App\Domain\Content\EditorialBoard::STATES)
@php($totals = $this->totals())
@php($publisher = \App\Filament\Support\AdminActor::current()->canPerform('content_publish'))
<x-filament-panels::page>
    <div class="pts-cal__stats">
        @foreach (['waiting' => 'Đã viết, bấm vào để đọc và đăng', 'scheduled' => 'Sẽ tự lên đúng giờ', 'late' => 'Đã qua ngày mà chưa có bài', 'todo' => 'Đề tài sắp tới'] as $key => $hint)
            <button type="button" class="pts-cal__stat is-{{ $key }}" wire:click="filter('{{ $key }}')" aria-pressed="{{ $this->state === $key ? 'true' : 'false' }}">
                <b>{{ $totals[$key] }}</b><span>{{ $labels[$key] }}</span><small>{{ $hint }}</small>
            </button>
        @endforeach
        <button type="button" class="pts-cal__stat is-live" wire:click="filter('live')" aria-pressed="{{ $this->state === 'live' ? 'true' : 'false' }}">
            <b>✓</b><span>{{ $labels['live'] }}</span><small>Lọc bài đang trên website</small>
        </button>
    </div>

    <div class="pts-cal__bar">
        <x-filament::button color="gray" size="sm" wire:click="shift(-1)" icon="heroicon-m-chevron-left">Tháng trước</x-filament::button>
        <strong class="pts-cal__month">Tháng {{ $start->format('m/Y') }}</strong>
        <x-filament::button color="gray" size="sm" wire:click="shift(1)" icon="heroicon-m-chevron-right" icon-position="after">Tháng sau</x-filament::button>
        <x-filament::button color="gray" size="sm" wire:click="today">Hôm nay</x-filament::button>
        <span class="pts-cal__hint">Bấm vào một ô để đọc và đăng. Kéo thả để dời ngày (bài đang hiện thì giữ nguyên).</span>
    </div>

    <div class="pts-cal" role="table" aria-label="Lịch bài viết tháng {{ $start->format('m/Y') }}" x-data="{ drag: null }">
        @foreach (['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'] as $dayName)
            <div class="pts-cal__head" role="columnheader">{{ $dayName }}</div>
        @endforeach
        @foreach ($this->weeks() as $week)
            @foreach ($week as $day)
                <div @class(['pts-cal__day', 'is-out' => ! $day['inMonth'], 'is-today' => $day['date']->isToday(), 'is-empty' => $day['items'] === []]) role="cell"
                    wire:key="day-{{ $day['date']->toDateString() }}"
                    x-on:dragover.prevent="$el.classList.add('is-drop')" x-on:dragleave="$el.classList.remove('is-drop')"
                    x-on:drop.prevent="$el.classList.remove('is-drop'); if (drag) { $wire.move(drag.type, drag.id, '{{ $day['date']->toDateString() }}'); drag = null }">
                    <span class="pts-cal__date">{{ $day['date']->format('j') }}</span>
                    @foreach ($day['items'] as $item)
                        <button type="button" class="pts-cal__post is-{{ $item['state'] }}" wire:key="{{ $item['type'] }}-{{ $item['id'] }}"
                            wire:click="show('{{ $item['type'] }}', {{ $item['id'] }})"
                            title="{{ $labels[$item['state']] }} · {{ $item['title'] }}" aria-label="{{ $labels[$item['state']] }}, {{ $item['at']->format('H:i d/m') }}, {{ $item['title'] }}"
                            @if ($item['movable']) draggable="true" x-on:dragstart="drag = { type: '{{ $item['type'] }}', id: {{ $item['id'] }} }; $event.dataTransfer.setData('text/plain', '{{ $item['id'] }}')" @endif>
                            <span class="pts-cal__text"><span class="pts-cal__time">{{ $item['at']->format('H:i') }}</span> {{ $item['title'] }}</span>
                        </button>
                    @endforeach
                </div>
            @endforeach
        @endforeach
    </div>

    @if ($record = $this->selected())
        <div class="pts-cal__shade" wire:click="close"></div>
        <aside class="pts-cal__panel" role="dialog" aria-modal="true" aria-label="{{ $record->title }}" x-on:keydown.escape.window="$wire.close()">
            @if ($record instanceof \App\Models\Post)
                @php($state = app(\App\Domain\Content\EditorialBoard::class)->stateOf($record))
                @php($resource = \App\Filament\Resources\PostRevisions\PostRevisionResource::ownerResource($record->kind))
                <header>
                    <span class="pts-cal__tag is-{{ $state }}">{{ $labels[$state] }}@if ($record->published_at) · {{ \Illuminate\Support\Carbon::parse($record->published_at)->format('H:i d/m/Y') }}@endif</span>
                    <h2>{{ $record->title }}</h2>
                </header>
                <div class="pts-cal__read">
                    @if ($record->summary)<p><strong>{{ $record->summary }}</strong></p>@endif
                    @if (trim(strip_tags((string) $record->body)) === '')<p>Bài chưa có nội dung.</p>@else{!! str((string) $record->body)->sanitizeHtml() !!}@endif
                </div>
                <footer>
                    @error('when')<p class="pts-cal__error">{{ $message }}</p>@enderror
                    @if ($publisher && $state !== 'live')
                        <div class="pts-cal__acts">
                            <input type="datetime-local" class="pts-cal__when" wire:model="when" aria-label="Giờ hẹn đăng">
                            <x-filament::button wire:click="schedule" icon="heroicon-m-clock">{{ $state === 'scheduled' ? 'Đổi giờ hẹn' : 'Hẹn giờ đăng' }}</x-filament::button>
                            <x-filament::button color="success" wire:click="publishNow" wire:confirm="Đưa bài này lên website ngay bây giờ?">Đăng ngay</x-filament::button>
                        </div>
                    @endif
                    <div class="pts-cal__acts">
                        <x-filament::button color="gray" tag="a" :href="$resource::getUrl('edit', ['record' => $record])" icon="heroicon-m-pencil-square">Sửa bài</x-filament::button>
                        @if ($publisher && in_array($state, ['live', 'scheduled'], true))
                            <x-filament::button color="danger" outlined wire:click="hidePost" wire:confirm="Ẩn bài này khỏi website?">Ẩn bài</x-filament::button>
                        @endif
                        <x-filament::button color="gray" wire:click="close">Đóng</x-filament::button>
                    </div>
                </footer>
            @else
                @php($state = $record->scheduled_for->isPast() ? 'late' : 'todo')
                <header>
                    <span class="pts-cal__tag is-{{ $state }}">{{ $labels[$state] }} · {{ $record->scheduled_for->format('H:i d/m/Y') }}</span>
                    <h2>{{ $record->title }}</h2>
                </header>
                <div class="pts-cal__read">
                    @if ($record->angle)<p><strong>Góc viết:</strong> {{ $record->angle }}</p>@endif
                    @if ($record->primary_source_url)<p><strong>Nguồn:</strong> <a href="{{ $record->primary_source_url }}" target="_blank" rel="noopener noreferrer">{{ $record->primary_source_name ?: $record->primary_source_url }}</a></p>@endif
                    <p>Đề tài này chưa có bài. Website chưa hiện gì cho đến khi bài được viết và đăng.</p>
                </div>
                <footer>
                    <div class="pts-cal__acts">
                        <x-filament::button wire:click="write" icon="heroicon-m-pencil-square">Tạo bài và viết</x-filament::button>
                        <x-filament::button color="gray" tag="a" :href="\App\Filament\Resources\EditorialPlanItems\EditorialPlanItemResource::getUrl('edit', ['record' => $record])">Sửa đề tài</x-filament::button>
                        <x-filament::button color="gray" wire:click="close">Đóng</x-filament::button>
                    </div>
                </footer>
            @endif
        </aside>
    @endif

    @if (($drafts = $this->drafts()) !== [])
        <x-filament::section heading="Bản sửa chưa đăng" description="Chỉnh sửa của bài đã có, chờ xuất bản.">
            <ul class="pts-queue__list">
                @foreach ($drafts as $draft)
                    <li>
                        <a href="{{ $draft['url'] }}" class="pts-attention__link">{{ $draft['title'] }}</a>
                        <span class="pts-attention__sub">{{ $draft['state'] }}@if ($draft['note'] !== '') · {{ $draft['note'] }}@endif</span>
                    </li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif
</x-filament-panels::page>
