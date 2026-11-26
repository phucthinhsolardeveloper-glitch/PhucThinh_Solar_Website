{{-- Buttons: the first is the main action (gold), the others are outlined. --}}
@if (! empty($actions))
    <tr>
        <td class="px" style="padding:22px 32px 0">
            <table class="btn-table" role="presentation" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    @foreach ($actions as $action)
                        <td class="btn-cell" style="padding:0 10px 10px 0">
                            <a href="{{ $action['href'] }}" target="_blank" style="display:inline-block;padding:12px 22px;border-radius:10px;font-size:14px;line-height:20px;font-weight:bold;text-decoration:none;white-space:nowrap;{{ $loop->first ? 'background:#e8b53a;background-image:linear-gradient(180deg,#f1c75b,#d9a326);color:#09285e;border:1px solid #d9a326' : 'background:#ffffff;color:#09285e;border:1px solid #c9d4e6' }}">{{ $action['label'] }}</a>
                        </td>
                    @endforeach
                </tr>
            </table>
        </td>
    </tr>
@endif
