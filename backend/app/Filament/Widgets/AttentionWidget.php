<?php

namespace App\Filament\Widgets;

use App\Domain\Catalog\Health\CatalogHealth;
use App\Domain\Media\MediaInventory;
use App\Domain\Outbox\Outbox;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\MediaFiles\MediaFileResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Models\Review;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Only what is waiting for someone: each line links to where it is handled. Nothing waiting, one short line. */
class AttentionWidget extends Widget
{
    protected string $view = 'filament.widgets.attention';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    /** @return list<array{tone: string, text: string, url: string, items?: list<array{name: string, url: string}>}> */
    public function items(): array
    {
        $items = [];
        if (OrderResource::canViewAny()) {
            $orders = OrderResource::getUrl();
            $new = DB::table('orders')->where('status', 'new')->count();
            $reported = DB::table('orders')->whereNotNull('transfer_reported_at')->where('payment_status', 'unpaid')->where('status', '!=', 'cancelled')->count();
            if ($reported > 0) {
                $items[] = ['tone' => 'danger', 'text' => $reported.' đơn khách báo đã chuyển khoản, chờ đối chiếu sao kê', 'url' => $orders];
            }
            if ($new > 0) {
                $items[] = ['tone' => 'warning', 'text' => $new.' đơn hàng mới chưa xử lý', 'url' => $orders.'?tableFilters[status][value]=new'];
            }
        }
        if (LeadResource::canViewAny() && ($leads = DB::table('leads')->where('status', 'new')->count()) > 0) {
            $items[] = ['tone' => 'warning', 'text' => $leads.' liên hệ / yêu cầu tư vấn chưa gọi lại', 'url' => LeadResource::getUrl().'?tableFilters[status][value]=new'];
        }
        if (ReviewResource::canViewAny() && ($reviews = Review::query()->whereNull('parent_id')->where('status', 'pending')->count()) > 0) {
            $items[] = ['tone' => 'info', 'text' => $reviews.' đánh giá chờ duyệt', 'url' => ReviewResource::getUrl()];
        }
        $mail = Outbox::health();
        if (($failed = $mail['failed'] + $mail['failed_jobs']) > 0) {
            $items[] = ['tone' => 'danger', 'text' => $failed.' email thông báo gửi thất bại, kiểm tra cấu hình mail trên server', 'url' => ''];
        } elseif ($mail['retrying'] > 0) {
            $items[] = ['tone' => 'warning', 'text' => $mail['retrying'].' email đang gửi lại', 'url' => ''];
        }
        if (MediaFileResource::canViewAny() && ($broken = MediaInventory::health()['broken']) > 0) {
            $items[] = ['tone' => 'danger', 'text' => $broken.' chỗ trên website trỏ tới ảnh không còn', 'url' => MediaFileResource::getUrl().'?tableFilters[on_disk][value]=0'];
        }
        if (ProductResource::canViewAny()) {
            $issues = Cache::remember('catalog-health', 600, fn () => app(CatalogHealth::class)->run());
            foreach (CatalogHealth::CHECKS as $key => [$severity, $label]) {
                if ($severity === 'info' || $issues[$key] === []) {
                    continue;
                }
                $items[] = ['tone' => $severity === 'error' ? 'danger' : 'warning', 'text' => count($issues[$key]).' sản phẩm: '.mb_lcfirst($label), 'url' => '',
                    'items' => array_map(fn ($p) => ['name' => $p['name'], 'url' => ProductResource::getUrl('edit', ['record' => $p['id']])], array_slice($issues[$key], 0, 4))];
            }
        }
        $order = ['danger' => 0, 'warning' => 1, 'info' => 2];
        usort($items, fn ($a, $b) => $order[$a['tone']] <=> $order[$b['tone']]);

        return $items;
    }
}
