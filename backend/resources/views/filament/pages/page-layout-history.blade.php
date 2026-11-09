@if ($versions === [])
    <p class="text-sm text-gray-500">Chưa xuất bản phiên bản nào: trang đang dùng bố cục gốc.</p>
@else
    <ul class="divide-y divide-gray-200 text-sm dark:divide-white/10">
        @foreach ($versions as $v)
            <li class="flex flex-wrap items-center gap-x-4 gap-y-1 py-2">
                <span class="font-semibold">Bản {{ $v['version'] }}</span>
                <span @class(['text-primary-600 font-medium' => $v['state'] === 'Đang dùng', 'text-gray-500' => $v['state'] !== 'Đang dùng'])>{{ $v['state'] }}</span>
                <span class="text-gray-500">{{ $v['at'] }}{{ $v['by'] !== '' ? ' · '.$v['by'] : '' }}</span>
                <span class="text-gray-500">{{ $v['hidden'] !== '' ? 'Ẩn: '.$v['hidden'] : 'Hiện đủ các khối' }}</span>
                @if ($canRestore && $v['state'] !== 'Đang dùng')
                    <button type="button" wire:click="restore({{ $v['id'] }})" class="ms-auto font-medium text-primary-600 hover:underline">Mở lại thành bản nháp</button>
                @endif
            </li>
        @endforeach
    </ul>
@endif
