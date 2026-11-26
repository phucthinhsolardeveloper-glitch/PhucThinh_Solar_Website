<?php

namespace App\Domain\Commerce;

use App\Mail\MailBrand;
use App\Models\Order;
use App\Support\LogSafe;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * The order sheet sent to a buyer and printed by staff. It records what was ordered; it is neither a VAT invoice
 * nor a receipt. The data is stored once, when the order is placed, so a later change to products or company
 * settings never rewrites a sheet already sent. No file is kept: the PDF is rendered from the stored data.
 */
final class OrderSheet
{
    private const LOGO_WIDTH = 520;

    public function __construct(private readonly MailBrand $brand) {}

    /** Stores the sheet of a new order, once. A failure is logged and never stops the order. */
    public function capture(int $orderId): void
    {
        try {
            $data = $this->build($orderId);
            if ($data === null) {
                return;
            }
            $json = self::encode($data);
            DB::table('order_documents')->insertOrIgnore(['order_id' => $orderId, 'snapshot' => $json, 'sha256' => hash('sha256', $json), 'created_at' => now()]);
        } catch (Throwable $e) {
            Log::error('Order sheet could not be stored', ['order' => $orderId, 'error' => LogSafe::error($e)]);
        }
    }

    /** @return array<string, mixed>|null the stored sheet, or null when there is none or it was altered */
    public function stored(int $orderId): ?array
    {
        $row = DB::table('order_documents')->where('order_id', $orderId)->first(['snapshot', 'sha256']);
        if ($row === null) {
            return null;
        }
        if (! hash_equals((string) $row->sha256, hash('sha256', (string) $row->snapshot))) {
            Log::error('Stored order sheet does not match its checksum', ['order' => $orderId]);

            return null;
        }
        $data = json_decode((string) $row->snapshot, true);

        return is_array($data) ? $data : null;
    }

    /** @return array<string, mixed>|null the sheet from what the database holds now */
    public function build(int $orderId): ?array
    {
        $order = Order::query()->with(['ward', 'province', 'paymentMethod'])->find($orderId);
        if ($order === null) {
            return null;
        }
        $lines = DB::table('order_items')->where('order_id', $orderId)->orderBy('id')->get();
        $codes = DB::table('products')->whereIn('id', $lines->pluck('product_id')->filter()->all())->pluck('code', 'id');
        $items = [];
        foreach ($lines as $item) {
            $price = (float) ($item->sale_price ?: $item->regular_price);
            $items[] = ['name' => (string) $item->name, 'option' => trim($item->color.' '.$item->size), 'code' => (string) ($codes[$item->product_id] ?? ''),
                'quantity' => (int) $item->quantity, 'price' => $price, 'total' => $price * (int) $item->quantity];
        }

        return $this->sheet([
            'code' => (string) $order->code,
            'placed_at' => ($order->created_at ?? now())->format('Y-m-d H:i:s'),
            'customer' => ['name' => (string) $order->customer_name, 'phone' => (string) $order->phone, 'email' => (string) $order->email, 'address' => OrderExporter::address($order)],
            'payment' => (string) ($order->paymentMethod->name ?? ''),
            'items' => $items,
            'totals' => ['subtotal' => (float) $order->subtotal, 'shipping' => (float) $order->shipping_fee, 'total' => (float) $order->total],
            'note' => (string) $order->requirements,
        ]);
    }

    /** @return array<string, mixed> a sheet with made-up buyer and goods, for previews */
    public function sample(): array
    {
        return $this->sheet([
            'code' => 'MAU-001',
            'placed_at' => '2026-01-15 09:30:00',
            'customer' => ['name' => 'Nguyễn Thị Mẫu', 'phone' => '0900000000', 'email' => 'khach.mau@example.com', 'address' => '15 Đường Thử Nghiệm, Phường Hội Phú, Gia Lai'],
            'payment' => 'Thanh toán bằng chuyển khoản',
            'items' => [
                ['name' => 'Đèn pha năng lượng mặt trời mẫu 100W, chống nước IP66, kèm tấm pin rời và điều khiển từ xa', 'option' => 'Ánh sáng trắng', 'code' => 'MAU-DEN-100', 'quantity' => 2, 'price' => 1187000.0, 'total' => 2374000.0],
                ['name' => 'Bộ điều khiển sạc mẫu 30A', 'option' => '', 'code' => 'MAU-SAC-30', 'quantity' => 1, 'price' => 890000.0, 'total' => 890000.0],
            ],
            'totals' => ['subtotal' => 3264000.0, 'shipping' => 0.0, 'total' => 3264000.0],
            'note' => 'Giao giờ hành chính, gọi trước khi giao.',
        ]);
    }

    public static function filename(array $sheet): string
    {
        return 'phieu-don-hang-'.preg_replace('/[^A-Za-z0-9-]/', '', (string) ($sheet['code'] ?? '')).'.pdf';
    }

    /** @param array<string, mixed> $sheet */
    public function pdf(array $sheet): string
    {
        // Per system user: the web server and the queue worker may run as different users.
        $cache = sys_get_temp_dir().'/pts-dompdf-'.getmyuid();
        if (! is_dir($cache) && ! mkdir($cache, 0700, true) && ! is_dir($cache)) {
            throw new RuntimeException('No folder for the PDF font cache');
        }
        // Nothing is fetched while rendering: the logo travels inside the data, fonts ship with the library.
        $pdf = new Dompdf(new Options(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false,
            'defaultFont' => 'DejaVu Sans', 'fontCache' => $cache, 'tempDir' => $cache, 'chroot' => $cache]));
        $pdf->loadHtml(view('pdf.order-sheet', ['sheet' => $sheet, 'reference' => substr(hash('sha256', self::encode($sheet)), 0, 12)])->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        $canvas = $pdf->getCanvas();
        $placed = 'D:'.preg_replace('/\D/', '', (string) ($sheet['placed_at'] ?? ''));
        $canvas->add_info('Title', 'Phiếu đơn hàng '.($sheet['code'] ?? ''));
        $canvas->add_info('CreationDate', $placed);
        $canvas->add_info('ModDate', $placed);
        if ($canvas->get_page_count() > 1) {
            $canvas->page_text(505, 818, 'Trang {PAGE_NUM}/{PAGE_COUNT}', $pdf->getFontMetrics()->getFont('DejaVu Sans'), 7.5, [0.36, 0.42, 0.52]);
        }

        return (string) $pdf->output();
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    private function sheet(array $order): array
    {
        $brand = $this->brand->data();

        return ['version' => 1] + $order + ['company' => [
            'name' => $brand['name'], 'address' => $brand['address'], 'hotline' => $brand['hotline'], 'email' => $brand['email'],
            'website' => (string) preg_replace('~^https?://~', '', rtrim($brand['home'], '/')), 'logo' => $this->logo($brand['logo']),
        ]];
    }

    /** The header logo as a small data URI, '' when there is none to read. */
    private function logo(string $url): string
    {
        $file = $url === '' ? '' : rtrim((string) config('site.upload_path'), '/').'/photo/'.basename($url);
        if ($file === '' || ! is_file($file) || ! function_exists('imagecreatefromstring')) {
            return '';
        }
        $image = @imagecreatefromstring((string) file_get_contents($file));
        if ($image === false) {
            return '';
        }
        $width = min(self::LOGO_WIDTH, imagesx($image));
        $height = max(1, (int) round(imagesy($image) * $width / imagesx($image)));
        $small = imagecreatetruecolor($width, $height);
        imagealphablending($small, false);
        imagesavealpha($small, true);
        imagecopyresampled($small, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
        ob_start();
        imagepng($small, null, 9);

        return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
    }

    /** @param array<string, mixed> $data */
    private static function encode(array $data): string
    {
        return (string) json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }
}
