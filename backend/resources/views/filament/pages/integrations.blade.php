<x-filament-panels::page>
    <p class="text-sm font-semibold">Môi trường đang xem: {{ app()->environment() }}</p>
    <p class="text-sm text-gray-600 dark:text-gray-300">Trạng thái tích hợp trên server này; không hiển thị hoặc chỉnh sửa khóa bí mật. Khi Nhất Tín chưa hoạt động cho checkout, phí giao hàng dùng bảng Phường/Xã. Các trạng thái “Cần kiểm thử” chưa xác nhận luồng giao dịch thực tế.</p>
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($this->statuses() as $item)
            <x-filament::section :heading="$item['label']">
                <p class="font-semibold">{{ $item['state'] }}</p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $item['detail'] }}</p>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
