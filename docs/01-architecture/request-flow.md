# Request flow hiện hành

Đối chiếu source 2026-11-26 (5c35860f).

## Public read
Ví dụ trang chủ: frontend/app/pages/index.vue → Nitro frontend/server/routes/_api/home.get.ts → fetchBackendPayload ở server/utils/backend-api.ts → GET /api/v1/home → HomeController → Domain/Home → database. Nitro kiểm payload bằng shared/contracts/public-home.ts, normalize hero và trả private/no-store. Tìm request của từng page thật, không giả định mọi route giống nhau.

## Cart và checkout
UI useShopCart + pages/gio-hang, thanh-toan.vue → Nitro _api/cart.get.ts, cart.post.ts, checkout.get.ts → session helper server/utils/backend-session.ts → Laravel /api/v1/cart, /api/v1/checkout. api.php gắn EncryptCookies, AddQueuedCookiesToResponse, StartSession cho nhóm commerce. Cart mutation kiểm action, CSRF token, số lượng và item code ở proxy; backend vẫn phải kiểm nghiệp vụ.

Đặt hàng: ShopController::placeOrder → Domain/Commerce/Shop::placeOrder, transaction ghi order/items/payment, xử lý token checkout/idempotency và trả thanks. reportTransfer chỉ là báo đã chuyển; kế toán xác nhận qua ManualPayment, trạng thái qua OrderLifecycle. Xem [checkout write path](../03-data/checkout-write-path.md).

## Admin
/admin → AdminPanelProvider → Filament resources/pages → model/domain. Đọc quyền và validation cụ thể của resource/action; không sửa thẳng DB từ giao diện. In đơn /admin/orders/{order}/in và phiếu PDF là route web riêng. Preview có signed route và throttle trong api.php.

## Media / SEO / webhook
routes/stateless.php → HealthController, MediaController, SitemapController, MerchantFeedController; Nitro có route proxy tương ứng. Nhất Tín webhook POST /api/integrations/ntl/webhook → NtlWebhookController. Kiểm bảo vệ webhook thực tế trước khi đổi.

Nguồn: [API](../../backend/routes/api.php), [web](../../backend/routes/web.php), [stateless](../../backend/routes/stateless.php), [bootstrap](../../backend/bootstrap/app.php), [Nitro routes](../../frontend/server/routes), [contracts](../../frontend/shared/contracts).

Lịch sử PHP: git show 5c35860f:docs/01-architecture/request-flow.md.
