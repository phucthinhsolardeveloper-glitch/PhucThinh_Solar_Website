<?php

namespace App\Domain\Commerce;

use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Outbox\Outbox;
use App\Mail\MailActions;
use App\Mail\MailTemplates;
use App\Mail\OrderNotification;
use App\Models\Order;
use App\Support\LogSafe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mail a buyer receives after the order is placed, and the switches that allow it. Every switch is off until
 * someone turns it on in Admin. A change is mailed at most once per order, and only changes made while the
 * switch is on: turning it on later sends nothing for the past.
 */
final class CustomerOrderMail
{
    public const SWITCHES = [
        'sheet' => 'Đính kèm phiếu đơn hàng (PDF) vào email xác nhận đặt hàng',
        'confirmed' => 'Gửi email khi đơn được xác nhận',
        'shipping' => 'Gửi email khi đơn chuyển sang đang giao',
        'paid' => 'Gửi email khi ghi nhận đã nhận tiền (đơn thanh toán thủ công)',
    ];

    public function __construct(private readonly MailTemplates $templates, private readonly MailActions $actions, private readonly ProductMediaUrls $urls) {}

    /** Read from the database each time: scheduled jobs and long-lived processes must see a switch just turned off. */
    public static function enabled(string $switch): bool
    {
        $saved = json_decode((string) DB::table('settings')->where('key', 'order_mail')->value('value'), true);

        return is_array($saved) && ($saved[$switch] ?? false) === true;
    }

    /** @return list<array<string, mixed>> the order's lines as the mail shows them */
    public function items(int $orderId): array
    {
        $origin = rtrim((string) config('site.canonical_origin'), '/');

        return DB::table('order_items')->where('order_id', $orderId)->orderBy('id')->get()->map(function ($item) use ($origin) {
            $price = (float) ($item->sale_price ?: $item->regular_price);
            $image = $this->urls->product($item->image);

            return ['name' => (string) $item->name, 'option' => implode(' - ', array_filter([$item->color, $item->size])),
                'quantity' => (int) $item->quantity, 'price' => $price, 'total' => $price * (int) $item->quantity, 'image' => $image !== '' ? $origin.$image : ''];
        })->values()->all();
    }

    /**
     * Queues the mail for "confirmed", "shipping" or "paid"; call it inside the transaction that changes the order.
     * A mail that cannot be prepared is logged and never undoes the change.
     */
    public function queue(int $orderId, string $event): bool
    {
        try {
            if (! isset(self::SWITCHES[$event]) || $event === 'sheet' || ! self::enabled($event)) {
                return false;
            }
            $order = Order::query()->with('paymentMethod')->find($orderId);
            if ($order === null || ! filter_var((string) $order->email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
            // A simulated or test waybill must not tell a buyer that goods are on the way.
            if ($event === 'shipping' && DB::table('shipments')->where('order_id', $orderId)->where('environment', '!=', 'production')
                ->whereNotIn('status', ['cancelled', 'failed'])->exists()) {
                return false;
            }
            $copy = $this->templates->copy('order_'.$event, ['order_code' => $order->code, 'customer_name' => $order->customer_name, 'customer_phone' => $order->phone]);
            $fields = ['Mã đơn hàng' => (string) $order->code, 'Họ tên' => (string) $order->customer_name, 'Điện thoại' => (string) $order->phone,
                'Email' => (string) $order->email, 'Địa chỉ' => OrderExporter::address($order->loadMissing('ward', 'province')),
                'Thanh toán' => (string) ($order->paymentMethod->name ?? '')];
            $totals = ['subtotal' => (float) $order->subtotal, 'shipping' => (float) $order->shipping_fee, 'total' => (float) $order->total];

            return Outbox::queue('order:'.$orderId.':customer:'.$event, 'order_'.$event, (string) $order->email,
                new OrderNotification($copy, $fields, $this->items($orderId), $totals, actions: $this->actions->customer()));
        } catch (Throwable $e) {
            Log::error('Order mail could not be queued', ['order' => $orderId, 'event' => $event, 'error' => LogSafe::error($e)]);

            return false;
        }
    }
}
