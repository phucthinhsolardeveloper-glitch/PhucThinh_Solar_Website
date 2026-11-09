<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sample orders, albums, videos, reviews and enquiries so every Admin screen can be tried on a local
 * copy. Every row is marked (code "DEMO-…", phone 0900000xxx, "@example.invalid", slug "demo-…"),
 * nothing is mailed, and --remove deletes exactly those rows. Refuses to run outside APP_ENV=local.
 */
class DemoDataCommand extends Command
{
    protected $signature = 'pts:demo-data {--remove : Delete the sample rows instead of creating them}';

    protected $description = 'Create (or remove) clearly marked sample data for local testing.';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('Chỉ chạy trên máy local (APP_ENV=local).');

            return self::FAILURE;
        }
        DB::transaction(fn () => $this->remove());
        if ($this->option('remove')) {
            $this->info('Đã xóa dữ liệu mẫu.');

            return self::SUCCESS;
        }
        DB::transaction(fn () => $this->create());
        $this->info('Đã tạo dữ liệu mẫu: 6 đơn hàng, 2 album, 3 video, 6 đánh giá, 4 liên hệ.');

        return self::SUCCESS;
    }

    private function remove(): void
    {
        DB::table('orders')->where('code', 'like', 'DEMO-%')->delete();
        DB::table('albums')->where('slug', 'like', 'demo-%')->delete();
        DB::table('videos')->where('title', 'like', '[Mẫu]%')->delete();
        DB::table('reviews')->where('email', 'like', '%@example.invalid')->delete();
        DB::table('leads')->where('email', 'like', '%@example.invalid')->delete();
    }

    private function create(): void
    {
        $now = now();
        $products = DB::table('products')->where('is_visible', true)->whereNotNull('image')->where('image', '!=', '')
            ->where('regular_price', '>', 0)->orderBy('id')->limit(12)->get(['id', 'name', 'image', 'regular_price', 'sale_price']);
        $ward = DB::table('wards')->join('provinces', 'provinces.id', '=', 'wards.province_id')->where('provinces.name', 'Gia Lai')
            ->select('wards.id', 'wards.province_id')->first();
        $payment = DB::table('payment_methods')->orderBy('id')->value('id');
        $states = [['new', 'unpaid'], ['confirmed', 'unpaid'], ['shipping', 'paid'], ['done', 'paid'], ['cancelled', 'unpaid'], ['new', 'paid']];
        foreach ($states as $i => [$status, $paid]) {
            $lines = $products->slice($i * 2, 2)->values();
            $subtotal = $lines->sum(fn ($p) => (float) ($p->sale_price ?: $p->regular_price) * ($i % 2 + 1));
            $orderId = DB::table('orders')->insertGetId([
                'code' => 'DEMO-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), 'customer_name' => 'Khách mẫu '.($i + 1), 'phone' => '090000010'.$i,
                'email' => 'khach'.($i + 1).'@example.invalid', 'address' => ($i + 10).' Đường Mẫu', 'province_id' => $ward?->province_id, 'ward_id' => $ward?->id,
                'payment_method_id' => $payment, 'payment_code' => $i === 5 ? 'vnpay' : 'manual', 'payment_status' => $paid, 'subtotal' => $subtotal,
                'shipping_fee' => 30000, 'total' => $subtotal + 30000, 'requirements' => 'Giao giờ hành chính', 'notes' => null, 'status' => $status,
                'created_at' => $now->copy()->subDays(6 - $i), 'updated_at' => $now,
            ]);
            foreach ($lines as $p) {
                DB::table('order_items')->insert(['order_id' => $orderId, 'product_id' => $p->id, 'name' => $p->name, 'image' => $p->image,
                    'regular_price' => $p->regular_price, 'sale_price' => $p->sale_price, 'quantity' => $i % 2 + 1, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        foreach (['Công trình mẫu Gia Lai', 'Kho ảnh mẫu sản phẩm'] as $a => $title) {
            $albumId = DB::table('albums')->insertGetId(['title' => $title, 'slug' => 'demo-album-'.($a + 1), 'summary' => 'Album mẫu để thử màn quản trị.',
                'image' => $products[$a]->image ?? null, 'is_visible' => false, 'sort_order' => $a, 'created_at' => $now, 'updated_at' => $now]);
            foreach ($products->slice($a * 4, 4)->values() as $k => $p) {
                DB::table('album_images')->insert(['album_id' => $albumId, 'path' => $p->image, 'caption' => 'Ảnh mẫu '.($k + 1), 'is_visible' => true, 'sort_order' => $k, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        foreach (['Hướng dẫn lắp đèn', 'Giới thiệu biến tần', 'Thi công áp mái'] as $v => $title) {
            DB::table('videos')->insert(['title' => '[Mẫu] '.$title, 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXc'.$v, 'is_visible' => false, 'sort_order' => 90 + $v, 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach (['pending', 'pending', 'approved', 'approved', 'hidden', 'pending'] as $r => $status) {
            DB::table('reviews')->insert(['product_id' => $products[$r]->id ?? null, 'stars' => 5 - $r % 3, 'title' => 'Đánh giá mẫu '.($r + 1),
                'content' => 'Nội dung đánh giá mẫu để thử duyệt, ẩn và trả lời.', 'name' => 'Người mua mẫu '.($r + 1), 'phone' => '090000020'.$r,
                'email' => 'review'.($r + 1).'@example.invalid', 'by_staff' => false, 'verified_purchase' => $r % 2 === 0, 'status' => $status,
                'created_at' => $now->copy()->subHours($r), 'updated_at' => $now]);
        }
        foreach (['contact', 'consultation', 'dealer', 'consultation'] as $l => $kind) {
            DB::table('leads')->insert(['kind' => $kind, 'name' => 'Liên hệ mẫu '.($l + 1), 'phone' => '090000030'.$l, 'email' => 'lead'.($l + 1).'@example.invalid',
                'message' => 'Yêu cầu mẫu để thử phân công và theo dõi.', 'product_id' => $kind === 'consultation' ? ($products[$l]->id ?? null) : null,
                'province_id' => $ward?->province_id, 'ward_id' => $ward?->id, 'source_url' => '/san-pham/demo', 'status' => $l === 2 ? 'contacted' : 'new',
                'created_at' => $now->copy()->subHours($l * 5), 'updated_at' => $now]);
        }
    }
}
