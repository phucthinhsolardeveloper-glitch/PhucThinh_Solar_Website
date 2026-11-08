<x-filament-panels::page>
    <p class="text-sm text-gray-600 dark:text-gray-300">Trạng thái cấu hình trên server. Màn này không hiện hoặc sửa khóa bí mật. Phí giao hàng hiện lấy từ bảng Phường/Xã.</p>
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($this->statuses() as $item)
            <x-filament::section :heading="$item['label']">
                <p class="font-semibold">{{ $item['state'] }}</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $item['detail'] }}</p>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
