@if ($changes === [])
    <p style="padding: 12px 0;">Phiên bản này giống hệt trang đang hiển thị.</p>
@else
    <div style="display: grid; gap: 16px;">
        @foreach ($changes as $row)
            <div style="border: 1px solid rgb(0 0 0 / 0.1); border-radius: 8px; overflow: hidden;">
                <div style="padding: 8px 12px; font-weight: 600; background: rgb(0 0 0 / 0.04);">{{ $row['label'] }}</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                    <div style="padding: 10px 12px; border-right: 1px solid rgb(0 0 0 / 0.08);">
                        <div style="font-size: 12px; opacity: 0.6; margin-bottom: 4px;">Đang hiển thị</div>
                        <div style="white-space: pre-line; overflow-wrap: anywhere;">{{ $row['live'] }}</div>
                    </div>
                    <div style="padding: 10px 12px; background: rgb(34 197 94 / 0.06);">
                        <div style="font-size: 12px; opacity: 0.6; margin-bottom: 4px;">Phiên bản này</div>
                        <div style="white-space: pre-line; overflow-wrap: anywhere;">{{ $row['revision'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
