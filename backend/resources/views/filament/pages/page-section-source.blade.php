<div class="pts-section-source">
    <p class="text-gray-600 dark:text-gray-300">{{ $note }}</p>
    @if ($links !== [] || $liveUrl)
        <ul class="pts-section-source__links">
            @foreach ($links as $link)
                <li>
                    @if ($link['url'])
                        <x-filament::link :href="$link['url']" icon="heroicon-m-pencil-square" target="_blank">{{ $link['label'] }}</x-filament::link>
                    @else
                        <span class="text-gray-500 dark:text-gray-400">{{ $link['label'] }} (tài khoản chưa có quyền)</span>
                    @endif
                </li>
            @endforeach
            @if ($liveUrl)
                <li><x-filament::link :href="$liveUrl" color="gray" icon="heroicon-m-arrow-top-right-on-square" target="_blank">Xem trên trang đang chạy</x-filament::link></li>
            @endif
        </ul>
    @endif
</div>
