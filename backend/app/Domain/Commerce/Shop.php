<?php

namespace App\Domain\Commerce;

use App\Domain\Catalog\PricingPolicy;
use App\Domain\Catalog\ProductDetail\ProductMediaUrls;
use App\Domain\Forms\FormMetadata;
use App\Domain\Leads\LeadSubmission;
use App\Legacy\Support\LegacyText;
use App\Legacy\Support\StatusFlags;
use App\Mail\MailTemplates;
use App\Mail\OrderNotification;
use App\Support\FormToken;
use App\Support\SiteSettings;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cart, checkout and thank-you page for Nuxt (pts_v1_cart*, pts_v1_checkout*, pts_v1_order_thanks,
 * sources/order.php, payment_vnpay.php). State lives in the Laravel session; orders are written to
 * table_order / table_order_detail so the PHP Admin keeps managing them.
 */
class Shop
{
    private const CODE = '/^[a-f0-9]{32}$/';

    private const THANKS_TTL = 7200;

    public function __construct(
        private readonly Session $session,
        private readonly Cart $cart,
        private readonly SiteSettings $settings,
        private readonly ProductMediaUrls $urls,
        private readonly Payments $payments,
        private readonly Shipping $shipping,
        private readonly FormMetadata $forms,
        private readonly MailTemplates $templates,
    ) {}

    /** @return array<string, mixed> GET /cart */
    public function cartPayload(): array
    {
        $pricing = new PricingPolicy($this->settings->productPricesVisible());
        $items = [];
        foreach ($this->cart->lines() as $line) {
            $product = $this->cart->product((int) $line['productid']);
            if (! $product || ! (new StatusFlags($product['status'] ?? ''))->visible()) {
                continue;
            }
            $id = (int) $product['id'];
            $slug = LegacyText::slug($product['slugvi'] ?? '');
            $name = LegacyText::plain($product['namevi'] ?? '');
            $quantity = max(1, min(999, (int) $line['qty']));
            if ($id <= 0 || $slug === '' || $name === '' || ! preg_match(self::CODE, (string) $line['code'])) {
                continue;
            }
            $price = $pricing->forProduct($product);
            $unit = $price['mode'] === 'price' ? (($price['salePrice'] ?? 0) > 0 ? $price['salePrice'] : $price['regularPrice']) : null;
            $items[] = ['code' => (string) $line['code'], 'productId' => $id, 'name' => $name, 'slug' => $slug, 'url' => '/san-pham/'.$slug,
                'image' => $this->urls->product($product['photo'] ?? ''), 'quantity' => $quantity,
                'color' => LegacyText::plain($this->cart->optionLabel('table_color', (int) $line['color'])), 'size' => LegacyText::plain($this->cart->optionLabel('table_size', (int) $line['size'])),
                'pricing' => $price, 'unitPrice' => $unit, 'lineTotal' => $unit !== null ? $unit * $quantity : null];
            if (count($items) >= 100) {
                break;
            }
        }
        $quoteItem = (bool) array_filter($items, fn ($item) => $item['lineTotal'] === null);

        return ['schemaVersion' => 1, 'locale' => 'vi', 'currency' => 'VND', 'count' => count($items), 'quantityCount' => array_sum(array_column($items, 'quantity')),
            'items' => $items, 'subtotal' => $quoteItem ? null : array_sum(array_column($items, 'lineTotal')), 'canCheckout' => ! $quoteItem && count($items) > 0,
            'csrfToken' => FormToken::issue()];
    }

    /** @return array{0: int, 1: array<string, mixed>} POST /cart {action: add|update|delete} */
    public function mutate(mixed $body): array
    {
        if (! is_array($body)) {
            return [400, ['error' => ['code' => 'BAD_BODY']]];
        }
        if (! FormToken::valid($body['csrfToken'] ?? null)) {
            return [419, ['error' => ['code' => 'INVALID_SESSION', 'message' => 'Phiên giỏ hàng không hợp lệ. Vui lòng tải lại trang.']]];
        }
        $action = is_scalar($body['action'] ?? null) ? (string) $body['action'] : '';
        if (! in_array($action, ['add', 'update', 'delete'], true)) {
            return [400, ['error' => ['code' => 'BAD_ACTION']]];
        }
        $pricesVisible = $this->settings->productPricesVisible();
        if (! $pricesVisible && $action !== 'delete') {
            // Quote-only mode still lets visitors empty their cart.
            return [409, ['error' => ['code' => 'QUOTE_ONLY', 'message' => 'Sản phẩm hiện nhận báo giá trực tiếp.']]];
        }
        $int = fn (string $key, int $default = 0) => is_scalar($body[$key] ?? null) ? (int) $body[$key] : $default;
        $code = is_scalar($body['code'] ?? null) ? trim((string) $body['code']) : '';
        $bad = [400, ['error' => ['code' => 'BAD_ITEM']]];
        if ($action === 'add') {
            [$id, $quantity, $color, $size] = [$int('productId'), $int('quantity', 1), $int('color'), $int('size')];
            if ($id <= 0 || $quantity < 1 || $quantity > 999 || $color < 0 || $size < 0) {
                return $bad;
            }
            $product = $this->cart->product($id);
            if (! $product || ($product['type'] ?? '') !== 'san-pham' || ! (new StatusFlags($product['status'] ?? ''))->visible()) {
                return [404, ['error' => ['code' => 'PRODUCT_NOT_FOUND']]];
            }
            if (Cart::requiresQuote($product, $pricesVisible)) {
                return [409, ['error' => ['code' => 'QUOTE_ONLY', 'message' => 'Sản phẩm này nhận báo giá trực tiếp.']]];
            }
            $this->cart->add($id, $quantity, $color, $size);
        } elseif ($action === 'update') {
            [$id, $quantity] = [$int('productId'), $int('quantity')];
            if ($id <= 0 || $quantity < 1 || $quantity > 999 || ! preg_match(self::CODE, $code)) {
                return $bad;
            }
            $result = $this->cart->update($code, $id, $quantity);
            if ($result !== 'ok') {
                return $result === 'mismatch' ? [409, ['error' => ['code' => 'ITEM_MISMATCH']]] : [404, ['error' => ['code' => 'NOT_FOUND']]];
            }
        } else {
            if (! preg_match(self::CODE, $code)) {
                return $bad;
            }
            if (! $this->cart->remove($code)) {
                return [404, ['error' => ['code' => 'NOT_FOUND']]];
            }
        }

        return [200, ['ok' => true, 'count' => count($this->cart->lines())]];
    }

    /** @return array<string, mixed> GET /checkout */
    public function checkoutPayload(): array
    {
        $payments = [];
        foreach ($this->payments->visible() as $row) {
            $id = (int) $row['id'];
            $name = LegacyText::plain($row['namevi']);
            if ($id !== 0 && $name !== '') {
                $payments[] = ['id' => $id, 'name' => $name, 'description' => LegacyText::plain(str_replace(['<br>', '<br/>', '<br />'], "\n", $row['descvi'])),
                    'code' => $id === Payments::VNPAY_ID ? 'vnpay' : 'legacy'];
            }
        }
        $pricesVisible = $this->settings->productPricesVisible();
        $ready = $this->cart->lines() !== [] && $pricesVisible;
        foreach ($ready ? $this->cart->lines() : [] as $line) {
            $product = $this->cart->product((int) $line['productid']);
            if (! $product || Cart::requiresQuote($product, $pricesVisible)) {
                $ready = false;
                break;
            }
        }

        return ['schemaVersion' => 1, 'locale' => 'vi', 'csrfToken' => FormToken::issue(), 'checkoutToken' => $this->checkoutToken(),
            'shippingEnabled' => (bool) config('legacy.order_shipping'), 'cartReady' => $ready, 'cities' => array_map(fn ($c) => ['id' => $c['id'], 'name' => LegacyText::plain($c['name'])], $this->forms->cities()),
            'payments' => $payments];
    }

    /** @return array{0: int, 1: array<string, mixed>} POST /shipping-quote {city, ward} */
    public function shippingQuote(mixed $body): array
    {
        $city = is_array($body) && is_scalar($body['city'] ?? null) ? (int) $body['city'] : 0;
        $ward = is_array($body) && is_scalar($body['ward'] ?? null) ? (int) $body['ward'] : 0;
        $result = $this->shipping->quote($city, $ward, $this->cart->total());

        return [$result['ok'] ? 200 : 422, $result];
    }

    /**
     * POST /checkout. JSON in, JSON out: on success Nuxt goes to the returned redirect (thank-you page
     * or VNPay); on a validation problem the messages and the typed values come back to refill the form.
     *
     * @return array{0: int, 1: array<string, mixed>}
     */
    public function placeOrder(mixed $body, string $ip): array
    {
        if (! is_array($body) || ! FormToken::valid($body['csrfToken'] ?? null)) {
            return [419, ['error' => ['code' => 'INVALID_SESSION', 'message' => 'Phiên đặt hàng không hợp lệ. Vui lòng tải lại trang.']]];
        }
        $token = is_string($body['checkoutToken'] ?? null) ? $body['checkoutToken'] : '';
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return [409, ['error' => ['code' => 'CHECKOUT_TOKEN', 'message' => 'Phiên gửi đơn hàng không hợp lệ. Vui lòng tải lại trang.']]];
        }
        // A repeated submit of an order already saved (double click, retry) goes to the same thank-you page.
        if ($existing = $this->persistedOrder($token)) {
            return [200, $this->finish($existing['id'], $existing['code'])];
        }
        if (! hash_equals($this->checkoutToken(), $token)) {
            return [409, ['error' => ['code' => 'CHECKOUT_TOKEN', 'message' => 'Phiên gửi đơn hàng không hợp lệ. Vui lòng tải lại trang.']]];
        }
        $rateKey = 'form:checkout|'.$ip;
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return [429, ['error' => ['code' => 'RATE_LIMITED', 'message' => 'Bạn thao tác quá nhanh. Vui lòng thử lại sau.']]];
        }
        RateLimiter::hit($rateKey, 600);
        $lines = $this->cart->lines();
        if (! $lines) {
            return [409, ['error' => ['code' => 'EMPTY_CART', 'message' => 'Đơn hàng không hợp lệ. Vui lòng thử lại sau.']]];
        }
        $pricesVisible = $this->settings->productPricesVisible();
        foreach ($lines as $line) {
            $product = $this->cart->product((int) $line['productid']);
            if (! $product || Cart::requiresQuote($product, $pricesVisible)) {
                return [409, ['error' => ['code' => 'QUOTE_ONLY', 'message' => 'Giỏ hàng có sản phẩm đã chuyển sang liên hệ báo giá. Vui lòng gửi yêu cầu tư vấn.']]];
            }
        }

        $input = is_array($body['data'] ?? null) ? $body['data'] : [];
        $value = fn (string $key) => is_scalar($input[$key] ?? null) ? trim((string) $input[$key]) : '';
        $cityId = (int) $value('city');
        $wardId = (int) $value('ward');
        $payment = $this->payments->find((int) $value('payments'));
        $db = DB::connection('legacy');
        $city = $cityId > 0 ? (array) ($db->selectOne("select id, name from table_city where id = ? and find_in_set('hienthi', status) limit 0,1", [$cityId]) ?? []) : [];
        $ward = $cityId > 0 && $wardId > 0 ? (array) ($db->selectOne("select id, name, ship_price from table_ward where id = ? and id_city = ? and id_district = 0 and find_in_set('hienthi', status) limit 0,1", [$wardId, $cityId]) ?? []) : [];

        $errors = [];
        if (empty($payment['id'])) {
            $errors[] = 'Hình thức thanh toán không hợp lệ hoặc đã ngừng sử dụng';
        }
        if ($value('fullname') === '') {
            $errors[] = 'Họ tên không được trống';
        }
        if ($value('phone') === '') {
            $errors[] = 'Số điện thoại không được trống';
        } elseif (! LeadSubmission::isPhone($value('phone'))) {
            $errors[] = 'Số điện thoại không hợp lệ';
        }
        if ($cityId <= 0) {
            $errors[] = 'Chưa chọn tỉnh/thành phố';
        } elseif (! $city) {
            $errors[] = 'Tỉnh/thành phố không hợp lệ';
        }
        if ($wardId <= 0) {
            $errors[] = 'Chưa chọn phường/xã';
        } elseif (! $ward) {
            $errors[] = 'Phường/xã không thuộc tỉnh/thành phố đã chọn';
        }
        if ($value('address') === '') {
            $errors[] = 'Địa chỉ không được trống';
        }
        if ($value('email') === '') {
            $errors[] = 'Email không được trống';
        } elseif (! filter_var($value('email'), FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ';
        }
        if ($errors) {
            $form = [];
            foreach (['fullname' => 150, 'phone' => 30, 'email' => 190, 'city' => 12, 'ward' => 12, 'address' => 255, 'requirements' => 1000, 'payments' => 12] as $key => $max) {
                if ($value($key) !== '') {
                    $form[$key] = mb_substr(strip_tags($value($key)), 0, $max);
                }
            }

            return [422, ['error' => ['code' => 'VALIDATION_FAILED', 'messages' => $errors], 'form' => $form ?: null]];
        }

        $subtotal = $this->cart->total();
        $shipPrice = 0.0;
        $requirements = htmlspecialchars($value('requirements'));
        if (config('legacy.order_shipping')) {
            $quote = $this->shipping->quote($cityId, $wardId, $subtotal);
            $shipPrice = ! empty($quote['priced']) ? (float) $quote['selected']['amount'] : 0.0;
            if (empty($quote['priced'])) {
                $notice = 'Phí vận chuyển: chờ xác nhận trước khi chốt đơn.';
                $requirements = $requirements !== '' ? $requirements.' | '.$notice : $notice;
            }
        }
        $address = implode(', ', array_filter([htmlspecialchars($value('address')), (string) ($ward['name'] ?? ''), (string) ($city['name'] ?? '')]));
        $total = $subtotal + $shipPrice;
        $order = [
            'id_user' => 0,
            'code' => $this->newOrderCode(),
            'checkout_token' => $token,
            'fullname' => htmlspecialchars($value('fullname')),
            'phone' => htmlspecialchars($value('phone')),
            'address' => $address,
            'email' => htmlspecialchars($value('email')),
            'order_payment' => (int) $payment['id'],
            'payment_method_code' => (string) ($payment['slugvi'] ?? '') ?: 'legacy',
            'payment_status' => 0,
            'ship_price' => $shipPrice,
            'temp_price' => $subtotal,
            'total_price' => $total,
            'requirements' => $requirements,
            'date_created' => time(),
            'order_status' => 1,
            'city' => $cityId,
            'district' => 0,
            'ward' => $wardId,
            'numb' => 1,
        ];

        try {
            [$orderId, $items] = $db->transaction(function () use ($db, $order, $lines) {
                $orderId = (int) $db->table('table_order')->insertGetId($order);
                $items = [];
                foreach ($lines as $line) {
                    $product = $this->cart->product((int) $line['productid']);
                    $quantity = (int) $line['qty'];
                    if (! $product || $quantity <= 0) {
                        throw new \RuntimeException('Cart product missing');
                    }
                    $color = $this->cart->optionLabel('table_color', (int) $line['color']);
                    $size = $this->cart->optionLabel('table_size', (int) $line['size']);
                    $db->table('table_order_detail')->insert(['id_product' => (int) $product['id'], 'id_order' => $orderId, 'photo' => $product['photo'], 'name' => $product['namevi'],
                        'code' => $order['code'], 'color' => $color, 'size' => $size, 'regular_price' => $product['regular_price'], 'sale_price' => $product['sale_price'], 'quantity' => $quantity]);
                    $price = ! empty($product['sale_price']) ? (float) $product['sale_price'] : (float) $product['regular_price'];
                    $items[] = ['name' => (string) $product['namevi'], 'option' => implode(' - ', array_filter([$color, $size])), 'quantity' => $quantity, 'price' => $price, 'total' => $price * $quantity];
                }

                return [$orderId, $items];
            });
        } catch (Throwable $e) {
            Log::error('Checkout failed to save the order', ['error' => $e->getMessage()]);
            if ($existing = $this->persistedOrder($token)) {
                return [200, $this->finish($existing['id'], $existing['code'])];
            }

            return [500, ['error' => ['code' => 'SAVE_FAILED', 'message' => 'Có lỗi khi lưu đơn hàng. Giỏ hàng của bạn vẫn được giữ nguyên, vui lòng thử lại.']]];
        }

        if ((int) $payment['id'] === Payments::VNPAY_ID) {
            $url = Payments::vnpayUrl($order['code'], $total, 'Thanh toan don hang '.$order['code'], $ip);
            if ($url === null) {
                return [503, ['error' => ['code' => 'PAYMENT_UNAVAILABLE', 'message' => 'Cổng thanh toán đang tạm thời chưa sẵn sàng. Vui lòng chọn chuyển khoản hoặc COD.']]];
            }
            $now = time();
            $db->table('table_payment_transaction')->insert(['id_order' => $orderId, 'provider' => 'vnpay', 'transaction_ref' => $order['code'], 'amount' => $total, 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now]);

            return [200, $this->finish($orderId, $order['code'], $url)];
        }

        $this->sendOrderMails($order, $items, (string) ($payment['namevi'] ?? ''));

        return [200, $this->finish($orderId, $order['code'])];
    }

    /** @return array{0: int, 1: array<string, mixed>} GET /order-thanks?token= (one conversion event per order) */
    public function thanks(array $get): array
    {
        $token = is_scalar($get['token'] ?? null) ? trim((string) $get['token']) : '';
        if (! preg_match('/^[a-f0-9]{48}$/', $token) || count($get) !== 1) {
            return [400, ['error' => ['code' => 'BAD_QUERY']]];
        }
        $all = (array) $this->session->get('order_thanks', []);
        $entry = $all[$token] ?? null;
        $notFound = [404, ['error' => ['code' => 'NOT_FOUND']]];
        if (! is_array($entry) || empty($entry['created_at']) || time() - (int) $entry['created_at'] > self::THANKS_TTL) {
            unset($all[$token]);
            $this->session->put('order_thanks', $all);

            return $notFound;
        }
        $order = DB::connection('legacy')->selectOne('select code,fullname,total_price from table_order where id=? and code=? limit 1', [(int) $entry['id'], (string) $entry['code']]);
        if (! $order || empty($order->code)) {
            unset($all[$token]);
            $this->session->put('order_thanks', $all);

            return $notFound;
        }
        $fire = ! empty($entry['conversion_pending']);
        $all[$token]['conversion_pending'] = false;
        $this->session->put('order_thanks', $all);

        return [200, ['schemaVersion' => 1, 'locale' => 'vi', 'order' => ['code' => trim((string) $order->code), 'customerName' => trim(strip_tags((string) $order->fullname)),
            'totalPrice' => max(0, (int) round((float) $order->total_price))], 'conversionPending' => $fire]];
    }

    /**
     * Where a classic form submit goes next: the thank-you page or payment page on success, otherwise
     * back to the cart with a one-time notice (read by GET /checkout-notice) and the typed values.
     *
     * @param  array{0: int, 1: array<string, mixed>}  $result
     */
    public function formRedirect(array $result): string
    {
        [$status, $body] = $result;
        if ($status === 200 && isset($body['redirect'])) {
            return (string) $body['redirect'];
        }
        $error = $body['error'] ?? [];
        $notice = ['messages' => [], 'toast' => null, 'form' => null];
        if (($error['code'] ?? '') === 'VALIDATION_FAILED') {
            $notice['messages'] = array_slice((array) $error['messages'], 0, 8);
            $notice['form'] = $body['form'] ?? null;
        } else {
            $notice['toast'] = ['status' => 'error', 'text' => (string) ($error['message'] ?? 'Đơn hàng không hợp lệ. Vui lòng thử lại sau.')];
        }
        $this->session->put('checkout_notice', $notice);

        return ($error['code'] ?? '') === 'QUOTE_ONLY' ? '/lien-he/' : '/gio-hang/';
    }

    /** @return array<string, mixed> GET /checkout-notice: one-time messages left for the cart page */
    public function notice(): array
    {
        $notice = $this->session->pull('checkout_notice');

        return is_array($notice) ? $notice + ['schemaVersion' => 1, 'locale' => 'vi'] : ['schemaVersion' => 1, 'locale' => 'vi', 'messages' => [], 'toast' => null, 'form' => null];
    }

    /**
     * VNPay return (browser) and IPN (server) callback. Payment state changes only after the signature,
     * order and amount all match. IPN answers in VNPay's RspCode format.
     *
     * @return array{0: int, 1: array<string, mixed>|string, 2?: string}
     */
    public function vnpayCallback(array $input, bool $ipn): array
    {
        $input = array_filter($input, 'is_scalar');
        $ipnReply = fn (string $code, string $message) => [200, ['RspCode' => $code, 'Message' => $message]];
        if (! Payments::vnpayVerify($input)) {
            return $ipn ? $ipnReply('97', 'Invalid signature') : [400, 'Invalid signature'];
        }
        $code = trim((string) ($input['vnp_TxnRef'] ?? ''));
        $db = DB::connection('legacy');
        $order = $code !== '' ? $db->selectOne('select id,total_price,payment_status from table_order where code = ? limit 1', [$code]) : null;
        if (! $order) {
            return $ipn ? $ipnReply('01', 'Order not found') : [400, 'Invalid order or amount'];
        }
        $amount = (float) ($input['vnp_Amount'] ?? 0) / 100;
        if (abs((float) $order->total_price - $amount) > 0.01) {
            return $ipn ? $ipnReply('04', 'Invalid amount') : [400, 'Invalid order or amount'];
        }
        if ($ipn && (int) $order->payment_status === 1) {
            return $ipnReply('02', 'Order already confirmed');
        }
        $status = ($input['vnp_ResponseCode'] ?? '') === '00' ? 'paid' : 'failed';
        $transaction = trim((string) ($input['vnp_TransactionNo'] ?? ''));
        $payload = (string) json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $now = time();
        $db->transaction(function () use ($db, $order, $code, $status, $transaction, $payload, $amount, $now) {
            $existing = $db->table('table_payment_transaction')->where('provider', 'vnpay')->where('transaction_ref', $code)->first();
            $row = ['provider_transaction_ref' => $transaction, 'status' => $status, 'payload_hash' => hash('sha256', $payload), 'payload' => $payload, 'updated_at' => $now];
            if ($existing) {
                $db->table('table_payment_transaction')->where('id', $existing->id)->update($row);
            } else {
                $db->table('table_payment_transaction')->insert($row + ['id_order' => (int) $order->id, 'provider' => 'vnpay', 'transaction_ref' => $code, 'amount' => $amount, 'created_at' => $now]);
            }
            if ($status === 'paid') {
                $db->table('table_order')->where('id', $order->id)->update(['payment_status' => 1, 'payment_method_code' => 'vnpay', 'payment_transaction_ref' => $transaction]);
            }
        });
        if ($ipn) {
            return $ipnReply('00', 'Confirm Success');
        }
        foreach ((array) $this->session->get('order_thanks', []) as $token => $entry) {
            if (is_array($entry) && ($entry['code'] ?? '') === $code) {
                return [302, [], '/cam-on/?token='.rawurlencode((string) $token)];
            }
        }

        return [200, ($status === 'paid' ? 'Thanh toán thành công' : 'Thanh toán chưa thành công').' cho đơn '.$code.'.'];
    }

    /** Six characters, unique among existing orders (the PHP code drew one without checking). */
    private function newOrderCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (DB::connection('legacy')->table('table_order')->where('code', $code)->exists());

        return $code;
    }

    /** Session-bound idempotency key for one checkout attempt (checkoutIdempotencyToken). */
    private function checkoutToken(): string
    {
        $token = (string) $this->session->get('checkout_token', '');
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            $token = bin2hex(random_bytes(32));
            $this->session->put('checkout_token', $token);
        }

        return $token;
    }

    /** @return array{id: int, code: string}|null */
    private function persistedOrder(string $token): ?array
    {
        $row = DB::connection('legacy')->selectOne('select o.id, o.code, count(od.id) as detail_count from table_order o left join table_order_detail od on od.id_order = o.id where o.checkout_token = ? group by o.id, o.code limit 0,1', [$token]);

        return $row && (int) $row->detail_count > 0 ? ['id' => (int) $row->id, 'code' => (string) $row->code] : null;
    }

    /** Empty the cart, retire the checkout key and hand out a thank-you token. */
    private function finish(int $orderId, string $code, ?string $paymentUrl = null): array
    {
        $all = array_filter((array) $this->session->get('order_thanks', []), fn ($entry) => is_array($entry) && time() - (int) ($entry['created_at'] ?? 0) <= self::THANKS_TTL);
        $token = bin2hex(random_bytes(24));
        $all[$token] = ['id' => $orderId, 'code' => $code, 'created_at' => time(), 'conversion_pending' => true];
        $this->session->put('order_thanks', $all);
        $this->session->forget('checkout_token');
        $this->cart->clear();

        return ['ok' => true, 'orderCode' => $code, 'thanksToken' => $token, 'redirect' => $paymentUrl ?? '/cam-on/?token='.rawurlencode($token)];
    }

    private function sendOrderMails(array $order, array $items, string $paymentName): void
    {
        $values = ['order_code' => $order['code'], 'customer_name' => htmlspecialchars_decode($order['fullname']), 'customer_phone' => htmlspecialchars_decode($order['phone'])];
        $fields = ['Mã đơn hàng' => $order['code'], 'Họ tên' => htmlspecialchars_decode($order['fullname']), 'Điện thoại' => htmlspecialchars_decode($order['phone']),
            'Email' => htmlspecialchars_decode($order['email']), 'Địa chỉ' => htmlspecialchars_decode($order['address']), 'Thanh toán' => $paymentName,
            'Yêu cầu thêm' => htmlspecialchars_decode($order['requirements'])];
        $totals = ['subtotal' => (float) $order['temp_price'], 'shipping' => (float) $order['ship_price'], 'total' => (float) $order['total_price']];
        $email = htmlspecialchars_decode($order['email']);
        try {
            if (($admin = (string) config('mail.admin_to')) !== '') {
                Mail::to($admin)->send(new OrderNotification($this->templates->copy('order_admin', $values), $fields, $items, $totals, $email, $values['customer_name']));
            }
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Mail::to($email)->send(new OrderNotification($this->templates->copy('order_customer', $values), $fields, $items, $totals));
            }
        } catch (Throwable $e) {
            // The order is saved; a mail outage must not cost the customer their confirmation page.
            Log::warning('Order email failed', ['order' => $order['code']]);
        }
    }
}
