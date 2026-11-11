<?php

namespace App\Console\Commands;

use App\Domain\Admin\DataQuality;
use App\Domain\Outbox\Outbox;
use App\Mail\AdminNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Monday 08:00 summary of last week for the owner (MAIL_ADMIN_TO): sales, enquiries, visits, what is waiting. */
class WeeklyReportCommand extends Command
{
    protected $signature = 'pts:weekly-report {--dry-run : Print it instead of sending}';

    protected $description = 'Email the owner last week\'s numbers and what is waiting.';

    public function handle(DataQuality $quality): int
    {
        $from = now()->subWeek()->startOfWeek();
        $to = $from->copy()->endOfWeek();
        $before = [$from->copy()->subWeek(), $to->copy()->subWeek()];
        $money = fn (float $v) => number_format($v, 0, ',', '.').'đ';
        $orders = fn (Carbon $a, Carbon $b) => DB::table('orders')->whereBetween('created_at', [$a, $b])->where('status', '!=', 'cancelled')
            ->selectRaw('count(*) as n, coalesce(sum(total), 0) as t')->first();
        [$now, $prev] = [$orders($from, $to), $orders(...$before)];
        $leads = DB::table('leads')->whereBetween('created_at', [$from, $to])->where('status', '!=', 'spam')->count();
        $leadsPrev = DB::table('leads')->whereBetween('created_at', $before)->where('status', '!=', 'spam')->count();
        $views = DB::table('page_views')->whereBetween('day', [$from->toDateString(), $to->toDateString()])->count();
        $change = fn (float $a, float $b) => $b > 0 ? ' ('.(($p = (int) round(($a - $b) / $b * 100)) >= 0 ? '+' : '').$p.'% so với tuần trước)' : '';
        $top = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->whereBetween('orders.created_at', [$from, $to])
            ->where('orders.status', '!=', 'cancelled')->selectRaw('order_items.name, sum(order_items.quantity) as q')->groupBy('order_items.name')->orderByDesc('q')->limit(3)->get();
        $problems = collect($quality->report())->where('severity', 'error')->sum('count');

        $fields = [
            'Doanh số' => $money((float) $now->t).$change((float) $now->t, (float) $prev->t),
            'Đơn hàng' => $now->n.' đơn'.$change((float) $now->n, (float) $prev->n),
            'Khách để lại liên hệ' => $leads.$change($leads, $leadsPrev),
            'Lượt xem website' => number_format($views, 0, ',', '.'),
            'Bán chạy' => $top->map(fn ($r) => $r->q.' × '.$r->name)->implode('; ') ?: '—',
            'Đang chờ xử lý' => DB::table('orders')->where('status', 'new')->count().' đơn mới · '.DB::table('leads')->where('status', 'new')->count().' liên hệ chưa gọi',
            'Dữ liệu cần sửa' => $problems > 0 ? $problems.' lỗi (Admin → Chất lượng dữ liệu)' : 'Không có lỗi',
        ];
        $copy = ['subject' => '[Phúc Thịnh Solar] Báo cáo tuần '.$from->format('d/m').'–'.$to->format('d/m'),
            'heading' => 'Tuần qua trên website', 'intro' => 'Số liệu từ thứ Hai '.$from->format('d/m').' đến Chủ nhật '.$to->format('d/m/Y').'.',
            'closing' => 'Chi tiết và việc cần làm có trong Admin.'];

        if ($this->option('dry-run')) {
            $this->line($copy['subject']);
            foreach ($fields as $label => $value) {
                $this->line($label.': '.$value);
            }

            return self::SUCCESS;
        }
        $to = (string) config('mail.admin_to');
        if ($to === '') {
            $this->warn('MAIL_ADMIN_TO chưa đặt: không gửi.');

            return self::SUCCESS;
        }
        Outbox::queue('weekly-report:'.$from->toDateString(), 'weekly_report', $to, new AdminNotification($copy, $fields));
        $this->info('Đã xếp hàng gửi tới '.$to);

        return self::SUCCESS;
    }
}
