# Feature inventory hiện hành

Source 5c35860f, 2026-11-26. IMPLEMENTED dưới đây nghĩa là tìm thấy implementation, không phải xác nhận production/UAT.

| Nhóm | Source | Trạng thái |
|---|---|---|
| Catalogue, giá, specs, so sánh | backend/app/Domain/Catalog; frontend/app/components/catalog, product-detail, compare | IMPLEMENTED |
| Nội dung, dự án, giải pháp | Domain/News, Projects, Solutions, Content; Filament Resources | IMPLEMENTED |
| CMS, media, revisions, audit | Filament; Domain/Admin, Media, Audit | IMPLEMENTED; CMS workflow và lịch biên tập đã merge/deploy, preview theo bài viết |
| Giỏ, checkout, báo đã chuyển, kế toán xác nhận | Domain/Commerce/Shop, BankTransfer, ManualPayment, OrderLifecycle | IMPLEMENTED; bank API auto-confirm chưa được xác nhận |
| Tra cứu đơn hàng | Domain/Commerce; frontend/app/pages/tra-cuu-don-hang.vue | IMPLEMENTED; production route đã kiểm HTTP 200, chưa nghiệm thu tra cứu đơn thật |
| Nhất Tín | Domain/Shipping/NhatTin, Shipments, NtlWebhookController | IMPLEMENTED; sandbox/live acceptance chưa xác minh |
| Lead/UTM/email, outbox | Domain/Leads, Forms, Outbox, Analytics | IMPLEMENTED |
| SEO/Merchant/sitemap | Domain/Seo, Catalog/MerchantFeed, MerchantFeedController | IMPLEMENTED; feed diagnostics riêng |
| Ước tính solar | Domain/Solar/SolarEstimator, frontend/app/components/estimator | IMPLEMENTED; 3D nâng cấp worktree riêng |

[Sổ tay](../SO-TAY-CHU-PROJECT.md) có link cụ thể đến code/test. [CURRENT](../CURRENT.md) là trạng thái release và việc tiếp theo. Không biến code tồn tại thành claim đã tích hợp provider hoặc đã deploy. Lịch sử PHP: git show 5c35860f:docs/07-features/README.md.
