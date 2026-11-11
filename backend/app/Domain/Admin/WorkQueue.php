<?php

namespace App\Domain\Admin;

use App\Domain\Catalog\Health\CatalogHealth;
use App\Domain\Shipping\NhatTin\NtlStatus;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\PostRevisions\PostRevisionResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Everything waiting for someone, by kind, each row linking to where it is handled. Only what the user may see;
 * "mine" keeps enquiries assigned to them (or nobody) and drafts they wrote.
 */
final class WorkQueue
{
    /**
     * @return list<array{key: string, title: string, tone: string, count: int, more: ?string, items: list<array{label: string, meta: string, url: string}>, refs?: list<string>}>
     */
    public function sections(User $user, bool $mine = false): array
    {
        $sections = [];
        if ($user->hasPermission('orders')) {
            $transfer = Order::query()->whereNotNull('transfer_reported_at')->where('payment_status', 'unpaid')->where('status', '!=', 'cancelled');
            $sections[] = $this->section('transfer', 'Khách báo đã chuyển khoản, chờ đối chiếu', 'danger', $transfer->clone()->oldest('transfer_reported_at'),
                fn (Order $o) => [$o->code.' · '.$o->customer_name, $this->money($o->total).' · báo lúc '.$this->when($o->transfer_reported_at), OrderResource::getUrl('edit', ['record' => $o])],
                OrderResource::getUrl());
            $sections[] = $this->section('shipment_trouble', 'Vận đơn có sự cố, đang hoàn hoặc chưa rõ kết quả', 'danger',
                Order::query()->whereHas('latestShipment', fn ($q) => $q->whereIn('status', ['failed', 'returning', 'unknown']))->with('latestShipment')->oldest('updated_at'),
                fn (Order $o) => [$o->code.' · '.$o->customer_name.' · '.$o->phone,
                    (NtlStatus::LABELS[$o->latestShipment->status] ?? '').' · '.($o->latestShipment->bill_code ?? $o->latestShipment->ref_code), OrderResource::getUrl('edit', ['record' => $o])],
                OrderResource::getUrl().'?tableFilters[shipping][value]=trouble');
            $sections[] = $this->section('cod', 'Đã giao, chờ đối soát tiền thu hộ', 'warning',
                Order::query()->where('payment_status', 'unpaid')->whereHas('latestShipment', fn ($q) => $q->where('status', 'delivered')->where('cod_amount', '>', 0))
                    ->with('latestShipment')->oldest('updated_at'),
                fn (Order $o) => [$o->code.' · '.$o->customer_name, 'thu hộ '.$this->money($o->latestShipment->cod_amount).' · vận đơn '.$o->latestShipment->bill_code, OrderResource::getUrl('edit', ['record' => $o])],
                OrderResource::getUrl().'?tableFilters[shipping][value]=delivered');
            $sections[] = $this->section('orders', 'Đơn hàng mới chưa xử lý', 'warning', Order::query()->where('status', 'new')->oldest(),
                fn (Order $o) => [$o->code.' · '.$o->customer_name.' · '.$o->phone, $this->money($o->total).' · đặt '.$this->when($o->created_at), OrderResource::getUrl('edit', ['record' => $o])],
                OrderResource::getUrl().'?tableFilters[status][value]=new');
            $sections[] = $this->section('unshipped', 'Đơn đã xác nhận quá 24 giờ, chưa có vận đơn', 'warning',
                Order::query()->where('status', 'confirmed')->where('updated_at', '<', now()->subDay())->whereDoesntHave('shipments')->oldest('updated_at'),
                fn (Order $o) => [$o->code.' · '.$o->customer_name, 'xác nhận '.$this->when($o->updated_at), OrderResource::getUrl('edit', ['record' => $o])],
                OrderResource::getUrl().'?tableFilters[status][value]=confirmed');
        }
        if ($user->hasPermission('leads')) {
            $scope = fn ($q) => $mine ? $q->where(fn ($q) => $q->where('assigned_to', $user->id)->orWhereNull('assigned_to')) : $q;
            $sections[] = $this->section('overdue', 'Liên hệ quá hạn hẹn gọi lại', 'danger',
                Lead::query()->whereNotIn('status', ['done', 'spam'])->where('follow_up_at', '<', now())->tap($scope)->oldest('follow_up_at'),
                fn (Lead $l) => [$l->name.' · '.$l->phone, 'hẹn '.$this->when($l->follow_up_at).($l->assignee ? ' · '.$l->assignee->getAttribute('name') : ' · chưa phân công'), LeadResource::getUrl().'?tableSearch='.urlencode((string) $l->phone)],
                LeadResource::getUrl().'?tableFilters[overdue][isActive]=1');
            $sections[] = $this->section('leads', 'Liên hệ / tư vấn mới chưa gọi', 'warning',
                Lead::query()->where('status', 'new')->tap($scope)->oldest(),
                fn (Lead $l) => [$l->name.' · '.$l->phone, (LeadResource::KINDS[$l->kind] ?? $l->kind).' · gửi '.$this->when($l->created_at), LeadResource::getUrl().'?tableSearch='.urlencode((string) $l->phone)],
                LeadResource::getUrl().'?tableFilters[status][value]=new');
        }
        if ($user->hasPermission('reviews')) {
            $sections[] = $this->section('reviews', 'Đánh giá chờ duyệt', 'info', Review::query()->whereNull('parent_id')->where('status', 'pending')->oldest(),
                fn (Review $r) => [(string) ($r->name ?? 'Khách'), str((string) $r->content)->limit(80)->toString(), ReviewResource::getUrl()], ReviewResource::getUrl());
        }
        if ($user->hasPermission('content')) {
            if ($user->canPerform('content_publish')) {
                $sections[] = $this->section('review', 'Bản nháp chờ duyệt', 'warning',
                    PostRevision::query()->with('post', 'requester')->where('state', 'draft')->where('review_state', 'waiting')->oldest('review_requested_at'),
                    fn (PostRevision $r) => [(string) $r->post?->title, 'gửi bởi '.($r->requester->name ?? '—').' '.$this->when($r->review_requested_at), PostRevisionResource::getUrl('edit', ['record' => $r])],
                    PostRevisionResource::getUrl().'?tableFilters[review_state][value]=waiting');
            }
            $sections[] = $this->section('returned', 'Bản nháp bị trả lại', 'danger',
                PostRevision::query()->with('post')->where('state', 'draft')->where('review_state', 'returned')
                    ->when($mine, fn ($q) => $q->where(fn ($q) => $q->where('review_requested_by', $user->id)->orWhere('updated_by', $user->id))),
                fn (PostRevision $r) => [(string) $r->post?->title, (string) $r->review_note, PostRevisionResource::getUrl('edit', ['record' => $r])],
                PostRevisionResource::getUrl().'?tableFilters[review_state][value]=returned');
            $sections[] = $this->section('scheduled', 'Bài hẹn giờ đăng trong 7 ngày', 'info',
                Post::query()->where('kind', 'article')->where('is_visible', true)->whereBetween('published_at', [now(), now()->addDays(7)])->orderBy('published_at'),
                fn (Post $p) => [(string) $p->title, 'đăng lúc '.Carbon::parse($p->published_at)->format('H:i d/m'), ArticleResource::getUrl('edit', ['record' => $p])],
                ArticleResource::getUrl().'?tableFilters[scheduled][isActive]=1');
        }
        if ($user->hasPermission('catalog')) {
            $issues = Cache::remember('catalog-health', 600, fn () => app(CatalogHealth::class)->run());
            $items = [];
            foreach (CatalogHealth::CHECKS as $key => [$severity, $label]) {
                if ($severity !== 'error') {
                    continue;
                }
                foreach ($issues[$key] ?? [] as $product) {
                    $items[] = ['label' => $product['name'], 'meta' => $label, 'url' => ProductResource::getUrl('edit', ['record' => $product['id']])];
                }
            }
            $sections[] = ['key' => 'products', 'title' => 'Sản phẩm đang hiện nhưng có lỗi', 'tone' => 'danger', 'count' => count($items),
                'more' => null, 'items' => array_slice($items, 0, 10), 'refs' => []];
        }

        return array_values(array_filter($sections, fn ($s) => $s['count'] > 0));
    }

    /** Sections that count toward the menu badge. */
    private const URGENT = ['transfer', 'orders', 'shipment_trouble', 'overdue', 'leads', 'review'];

    /**
     * Count shown on the menu: distinct orders, enquiries and drafts a customer or colleague is waiting on.
     * A record in two sections (a new order whose transfer was reported, an overdue enquiry that is also new)
     * is counted once; each section on the page still shows its own count.
     */
    public function urgentCount(User $user): int
    {
        return collect($this->sections($user))->whereIn('key', self::URGENT)->flatMap(fn ($s) => $s['refs'] ?? [])->unique()->count();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     * @param  callable(mixed): array{0: string, 1: string, 2: string}  $row
     * @return array{key: string, title: string, tone: string, count: int, more: ?string, items: list<array{label: string, meta: string, url: string}>}
     */
    private function section(string $key, string $title, string $tone, $query, callable $row, ?string $more): array
    {
        $count = $query->clone()->count();
        $refs = $count === 0 || ! in_array($key, self::URGENT, true) ? [] : $query->clone()->reorder()->pluck($query->getModel()->getQualifiedKeyName())
            ->map(fn ($id) => class_basename($query->getModel()).':'.$id)->all();
        $items = $count === 0 ? [] : $query->limit(10)->get()->map(function ($record) use ($row) {
            [$label, $meta, $url] = $row($record);

            return ['label' => $label, 'meta' => $meta, 'url' => $url];
        })->all();

        return ['key' => $key, 'title' => $title, 'tone' => $tone, 'count' => $count, 'more' => $more, 'items' => $items, 'refs' => $refs];
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 0, ',', '.').'đ';
    }

    private function when(mixed $at): string
    {
        return $at ? Carbon::parse($at)->format('H:i d/m') : '—';
    }
}
