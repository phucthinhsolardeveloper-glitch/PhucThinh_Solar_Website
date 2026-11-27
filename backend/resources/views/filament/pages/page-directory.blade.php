<div class="pts-section-source">
    @foreach ($pages as $page)
        <section class="pts-page-directory__page">
            <h3 class="font-semibold">{{ $page['label'] }}</h3>
            <dl>
                @foreach ($page['blocks'] as $block)
                    <div class="pts-page-directory__block">
                        <dt class="text-gray-600 dark:text-gray-300">{{ $block['label'] }}</dt>
                        <dd class="pts-section-source__links">
                            @foreach ($block['links'] as $link)
                                @if ($link['url'])
                                    <x-filament::link :href="$link['url']">{{ $link['label'] }}</x-filament::link>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">{{ $link['label'] }} (tài khoản chưa có quyền)</span>
                                @endif
                            @endforeach
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endforeach
</div>
