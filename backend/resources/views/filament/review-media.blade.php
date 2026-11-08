<div class="grid gap-4 sm:grid-cols-2">
    @forelse ($media as $item)
        @if ($item->kind === 'photo')
            <img src="{{ url('/upload/photo/'.rawurlencode(basename($item->path))) }}" alt="Ảnh khách gửi cùng đánh giá" class="max-h-80 w-full rounded-lg border object-contain" loading="lazy">
        @elseif ($item->kind === 'video')
            <video controls preload="metadata" class="max-h-80 w-full rounded-lg border" @if ($item->poster) poster="{{ url('/upload/photo/'.rawurlencode(basename($item->poster))) }}" @endif>
                <source src="{{ url('/upload/video/'.rawurlencode(basename($item->path))) }}" type="{{ strtolower(pathinfo($item->path, PATHINFO_EXTENSION)) === 'mkv' ? 'video/x-matroska' : 'video/mp4' }}">
            </video>
        @endif
    @empty
        <p>Đánh giá không có ảnh hoặc video.</p>
    @endforelse
</div>
