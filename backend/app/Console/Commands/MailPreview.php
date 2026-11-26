<?php

namespace App\Console\Commands;

use App\Domain\Commerce\OrderSheet;
use App\Mail\AdminNotification;
use App\Mail\MailActions;
use App\Mail\MailTemplates;
use App\Mail\OrderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Writes every transactional email as HTML (and plain text) from synthetic data, so a redesign can be judged on the
 * rendered page. Touches no order, lead or mail queue and sends nothing.
 */
class MailPreview extends Command
{
    protected $signature = 'pts:mail-preview {dir=storage/app/mail-preview : folder to write into}';

    protected $description = 'Render the email templates with sample data into a folder (no mail is sent)';

    public function handle(MailTemplates $templates, MailActions $actions, OrderSheet $sheets): int
    {
        $dir = (string) $this->argument('dir');
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            $this->error('Không tạo được thư mục '.$dir);

            return self::FAILURE;
        }
        $values = ['customer_name' => 'Nguyễn Văn An', 'customer_phone' => '0912 345 678', 'company' => 'Công ty TNHH Cơ điện An Phát', 'contact_subject' => 'Báo giá hệ thống đèn đường NLMT cho khu dân cư', 'order_code' => 'PTS-2611-0042'];
        $lead = ['Họ tên' => $values['customer_name'], 'Điện thoại' => $values['customer_phone'], 'Email' => 'an.nguyen@example.com', 'Địa chỉ' => 'Phường Hội Phú, Gia Lai',
            'Chủ đề' => $values['contact_subject'], 'Nội dung' => "Khu dân cư có 40 trụ đèn, cần đèn năng lượng mặt trời 100W.\nAnh/chị gửi giúp báo giá và thời gian lắp đặt."];
        $order = ['Mã đơn hàng' => $values['order_code'], 'Họ tên' => $values['customer_name'], 'Điện thoại' => $values['customer_phone'], 'Email' => 'an.nguyen@example.com',
            'Địa chỉ' => '15 Nguyễn Huệ, Phường Hội Phú, Gia Lai', 'Thanh toán' => 'Chuyển khoản ngân hàng', 'Yêu cầu thêm' => 'Giao giờ hành chính, gọi trước khi giao.'];
        $items = [
            ['name' => 'Đèn pha năng lượng mặt trời BLUE CARBON BCT-WR20 100W', 'option' => '', 'quantity' => 2, 'price' => 1187000.0, 'total' => 2374000.0],
            ['name' => 'Bộ điều khiển sạc MPPT Blue Carbon 30A', 'option' => 'Điện áp 12/24V', 'quantity' => 1, 'price' => 890000.0, 'total' => 890000.0],
            ['name' => 'Cần đèn & giá đỡ đèn đường 6m', 'option' => '', 'quantity' => 4, 'price' => 650000.0, 'total' => 2600000.0],
        ];
        $totals = ['subtotal' => 5864000.0, 'shipping' => 120000.0, 'total' => 5984000.0];
        // Real product photos give the receipt its thumbnails; without any, the template shows grey placeholders.
        $origin = rtrim((string) config('site.canonical_origin'), '/');
        foreach (DB::table('products')->whereNotNull('image')->where('image', '!=', '')->orderBy('id')->limit(count($items))->pluck('image')->values() as $i => $file) {
            $items[$i]['image'] = $origin.'/upload/product/'.rawurlencode((string) $file);
        }

        $made = [];
        foreach (array_keys(MailTemplates::LABELS) as $key) {
            $copy = $templates->copy($key, $values);
            $buttons = $copy['audience'] === 'customer' ? $actions->customer() : $actions->staff('/admin/leads', $values['customer_phone']);
            $mail = str_starts_with($key, 'order_') ? new OrderNotification($copy, $order, $items, $totals, actions: $buttons) : new AdminNotification($copy, $lead, actions: $buttons);
            if ($key === 'order_customer' && $mail instanceof OrderNotification) {
                $mail->withSheet($sheets->sample());
                file_put_contents($dir.'/'.OrderSheet::filename($mail->sheet), $sheets->pdf($mail->sheet));
            }
            file_put_contents($dir.'/'.$key.'.html', $mail->render());
            $made[] = $key;
        }
        $this->info('Đã ghi '.count($made).' mẫu vào '.$dir.': '.implode(', ', $made));

        return self::SUCCESS;
    }
}
