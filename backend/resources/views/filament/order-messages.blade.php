@php
    use App\Domain\Commerce\CustomerOrderMail;
    use App\Mail\MailTemplates;
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\DB;

    $labels = ['pending' => 'Đang chờ gửi', 'sending' => 'Đang gửi', 'retrying' => 'Đang thử lại', 'sent' => 'Máy chủ mail đã nhận', 'failed' => 'Gửi không được', 'cancelled' => 'Đã hủy'];
    $rows = $order ? DB::table('outbox_messages')->where('dedupe_key', 'like', 'order:'.$order->id.':%')->orderBy('id')->get(['kind', 'status', 'created_at', 'sent_at']) : collect();
    $stored = $order ? DB::table('order_documents')->where('order_id', $order->id)->value('created_at') : null;
    $automatic = collect(CustomerOrderMail::SWITCHES)->keys()->filter(fn ($key) => CustomerOrderMail::enabled($key));
@endphp
@if ($order)
    <div class="pts-order-items">
        <table>
            <thead><tr><th>Email</th><th>Tình trạng</th><th>Xếp gửi lúc</th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ MailTemplates::LABELS[$row->kind] ?? $row->kind }}</td>
                        <td>{{ $labels[$row->status] ?? $row->status }}@if ($row->sent_at)<span class="pts-attention__sub">{{ Carbon::parse($row->sent_at)->format('H:i d/m') }}</span>@endif</td>
                        <td>{{ Carbon::parse($row->created_at)->format('H:i d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Đơn này chưa có email nào được xếp gửi.</td></tr>
                @endforelse
            </tbody>
        </table>
        <p class="pts-attention__sub">
            {{ $stored ? 'Phiếu đơn hàng được lưu lúc '.Carbon::parse($stored)->format('H:i d/m/Y').'.' : 'Đơn này không có phiếu lưu sẵn: nút Phiếu PDF lập từ dữ liệu hiện tại.' }}
            {{ $automatic->isEmpty() ? 'Email báo trạng thái và đính kèm phiếu đang tắt (Hệ thống → Mẫu email).' : '' }}
        </p>
    </div>
@endif
