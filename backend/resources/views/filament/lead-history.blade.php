<div class="space-y-4">
    @forelse ($events as $event)
        <div class="rounded-lg border p-3">
            <p class="font-semibold">{{ $event->actor?->name ?? 'Tài khoản đã xóa' }} · {{ $event->created_at?->format('H:i d/m/Y') }}</p>
            <ul class="mt-2 list-disc pl-5 text-sm">
                @foreach ($event->changes as $field => $change)
                    <li>
                        @switch($field)
                            @case('status') Trạng thái: {{ \App\Filament\Resources\Leads\LeadResource::STATUSES[$change['from']] ?? $change['from'] }} → {{ \App\Filament\Resources\Leads\LeadResource::STATUSES[$change['to']] ?? $change['to'] }} @break
                            @case('assigned_to') Phân công: {{ $change['from_name'] ?? 'Chưa phân công' }} → {{ $change['to_name'] ?? 'Chưa phân công' }} @break
                            @case('follow_up_at') Hẹn theo dõi: {{ $change['from'] ?? '—' }} → {{ $change['to'] ?? '—' }} @break
                            @case('notes_changed') Đã cập nhật ghi chú xử lý @break
                        @endswitch
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <p>Chưa có thay đổi xử lý.</p>
    @endforelse
</div>
