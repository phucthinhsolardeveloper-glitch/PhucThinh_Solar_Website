@php($rows = $this->rows())
<x-filament-widgets::widget>
    <x-filament::section heading="Trang được xem nhiều" description="30 ngày qua">
        @if ($rows === [])
            <p class="text-sm text-gray-500">Chưa có lượt xem nào được ghi.</p>
        @else
            <table class="pts-top-pages">
                <thead><tr><th>Trang</th><th>Lượt xem</th><th>Người xem</th></tr></thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                <a href="{{ $row['url'] }}" target="_blank" rel="noopener" title="{{ $row['path'] }}">{{ $row['title'] !== '' ? $row['title'] : $row['path'] }}</a>
                                <span class="pts-top-pages__bar" style="width: {{ $row['share'] }}%"></span>
                            </td>
                            <td>{{ number_format($row['views'], 0, ',', '.') }}</td>
                            <td>{{ number_format($row['visitors'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
