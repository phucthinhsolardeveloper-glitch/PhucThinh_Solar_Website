# Sổ tay tự vận hành — Website Phúc Thịnh Solar

> [Trạng thái hiện hành](CURRENT.md) · [Quy chuẩn bảo trì tài liệu](DOCUMENTATION.md). Ưu tiên trạng thái ở CURRENT.md thay cho snapshot/roadmap cũ trong tài liệu chi tiết. AI dùng [AI-START.md](AI-START.md).


Bản đồ kỹ thuật gốc 2026-11-26 (5c35860f); bổ sung luồng CMS và tra cứu đơn 2026-11-28 (596ce498). Trạng thái mới nhất xem CURRENT.md.

Đọc trước khi sửa code. Đây là bản đồ thao tác cho người, không phải prompt AI. Các đường dẫn dưới đây được chọn từ cây source local; runtime và độ bao phủ test chưa được nghiệm thu trong đợt tài liệu này.

## 1. Hiểu hệ thống

Nuxt 4/Vue 3 là website public; proxy server Nuxt nằm trong [frontend/server/routes](../frontend/server/routes). Laravel 13/PHP 8.4 xử lý API và Filament Admin; MariaDB lưu dữ liệu. Hợp đồng API bắt đầu tại [backend/routes/api.php](../backend/routes/api.php), còn quy tắc nghiệp vụ nằm trong [backend/app/Domain](../backend/app/Domain). Không sửa theo bản đồ PHP legacy trong các tài liệu trước cutover.

## 2. Muốn sửa gì, vào đâu?

CMS hiện có đường dẫn từ Bố cục trang / Nội dung các trang đến nguồn dữ liệu, cảnh báo chất lượng dẫn vào danh sách sản phẩm, Lịch bài viết hỗ trợ duyệt và xem trước. Preview bố cục và bài viết không phải preview toàn bộ thay đổi nội dung. Xem [báo cáo CMS](10-audits/2026-11-26-cms-workflow-improvement.md).

| Việc | UI / điểm vào | Backend / kiểm thử |
|---|---|---|
| Trang chủ, menu, footer | [pages/index.vue](../frontend/app/pages/index.vue), [SiteHeader](../frontend/app/components/SiteHeader.vue), [SiteFooter](../frontend/app/components/SiteFooter.vue) | [Home](../backend/app/Domain/Home), [Site](../backend/app/Domain/Site) |
| Danh mục, chi tiết, giá, so sánh | [catalog](../frontend/app/components/catalog), [product-detail](../frontend/app/components/product-detail), [useProductCompare](../frontend/app/composables/useProductCompare.ts) | [Catalog](../backend/app/Domain/Catalog), [Products Admin](../backend/app/Filament/Resources/Products), [CatalogApiTest](../backend/tests/Feature/CatalogApiTest.php), [CategoryTreeEditorTest](../backend/tests/Feature/CategoryTreeEditorTest.php) |
| Bài viết, giải pháp, dự án | [pages/tin-tuc](../frontend/app/pages/tin-tuc), [solution-detail](../frontend/app/components/solution-detail), [project-detail](../frontend/app/components/project-detail) | [News](../backend/app/Domain/News), [Solutions](../backend/app/Domain/Solutions), [Projects](../backend/app/Domain/Projects), [Articles](../backend/app/Filament/Resources/Articles) |
| Giỏ hàng, checkout, thanh toán | [gio-hang](../frontend/app/pages/gio-hang), [thanh-toan.vue](../frontend/app/pages/thanh-toan.vue), [useShopCart](../frontend/app/composables/useShopCart.ts) | [Commerce](../backend/app/Domain/Commerce), [BankTransfer](../backend/app/Domain/Commerce/BankTransfer.php), [ManualPayment](../backend/app/Domain/Commerce/ManualPayment.php), [OrderLifecycle](../backend/app/Domain/Commerce/OrderLifecycle.php), [BankTransferTest](../backend/tests/Feature/BankTransferTest.php) |
| Vận chuyển Nhất Tín | [commerce](../frontend/app/components/commerce) | [Shipping](../backend/app/Domain/Shipping), [config/shipping.php](../backend/config/shipping.php), [NtlWebhookController](../backend/app/Http/Controllers/NtlWebhookController.php) |
| Tra cứu đơn hàng | [tra-cuu-don-hang.vue](../frontend/app/pages/tra-cuu-don-hang.vue) | [OrderLookupTest](../backend/tests/Feature/OrderLookupTest.php), [Commerce](../backend/app/Domain/Commerce) |
| CMS/Admin | [Filament Pages](../backend/app/Filament/Pages), [Resources](../backend/app/Filament/Resources) | [Admin](../backend/app/Domain/Admin), [AdminScreensTest](../backend/tests/Feature/AdminScreensTest.php), [AdminQuickHandlingTest](../backend/tests/Feature/AdminQuickHandlingTest.php) |
| SEO, Merchant, sitemap, analytics | [useDataLayer](../frontend/app/composables/useDataLayer.ts), [server routes](../frontend/server/routes) | [MerchantFeed](../backend/app/Domain/Catalog/MerchantFeed.php), [Seo](../backend/app/Domain/Seo), [Analytics](../backend/app/Domain/Analytics) |
| Form tư vấn, UTM, lead | [useLeadForm](../frontend/app/composables/useLeadForm.ts) | [Leads](../backend/app/Domain/Leads), [LeadSubmissionTest](../backend/tests/Feature/LeadSubmissionTest.php), [CrmLeadForwardingTest](../backend/tests/Feature/CrmLeadForwardingTest.php) |
| Ước tính điện mặt trời | [estimator](../frontend/app/components/estimator), [uoc-tinh-dien-mat-troi.vue](../frontend/app/pages/uoc-tinh-dien-mat-troi.vue) | [SolarEstimator](../backend/app/Domain/Solar/SolarEstimator.php) |

Database: [migrations](../backend/database/migrations), [models](../backend/app/Models). Cấu hình nghiệp vụ: [config](../backend/config). Tìm controller thật từ route, đừng suy đoán theo tên module.

## 3. Ví dụ tự xử lý: khách chuyển khoản nhưng đơn chưa xác nhận

- Tái hiện với đơn local, đối chiếu mã đơn, tổng tiền, trạng thái và audit event; không dùng khách thật.
- Xem UI checkout, BankTransfer, ManualPayment, Payments và OrderLifecycle. Phân biệt tạo QR với xác nhận nhận tiền. Chủ project định hướng kế toán xác nhận thủ công khi chưa có bank API.
- Không tự chuyển trạng thái đã thanh toán chỉ vì QR được tạo, không tự ghi nhận tiền từ ảnh chụp. Kiểm thử số tiền lệch, xác nhận lặp, quyền kế toán, xuất dữ liệu và audit.
- Đọc [Orders Admin](../backend/app/Filament/Resources/Orders) và test liên quan trước khi thay đổi.

## 4. Chạy local, test, triển khai

Đọc [README](../README.md) cho lệnh Docker và Nuxt; theo cấu hình README: backend 8090, frontend 3110, admin /admin. Đây là port theo tài liệu, không phải xác nhận đang chạy.

```bash
docker compose ps
docker compose config --services
# kiểm tra log local, không in env/secret
docker compose logs --tail=100
# chỉ chạy trong container local đã xác nhận tên
docker exec pts-laravel13-local php artisan test
cd frontend
npm test
npx nuxi typecheck
```

Deploy Laravel: [deploy/laravel13/README.md](../deploy/laravel13/README.md), [build-candidate.sh](../deploy/laravel13/build-candidate.sh), [verify-candidate.sh](../deploy/laravel13/verify-candidate.sh), [backup scripts](../deploy/laravel13/scripts). Kiểm tra script hiện hành và release log trước khi chạy; hướng dẫn deploy PHP cũ không tự động còn đúng. Không chạy import legacy --fresh trên DB có dữ liệu.

## 5. Bước tiếp theo (đề xuất, chưa xác nhận nghiệm thu)

| Ưu tiên | Việc | Tiêu chí xong |
|---|---|---|
| P0 | Đối chiếu release production Laravel và log | Ghi commit/image thật, health, rollback, backup |
| P0 | CMS workflow | Nghiệm thu từng thao tác Admin với người sử dụng; kiểm tra quyền và dữ liệu |
| P0 | Thanh toán/chuyển khoản/vận chuyển | Kế toán đối soát, audit, trạng thái đơn và xuất dữ liệu; test provider sandbox khi có |
| P1 | Ước tính điện và 3D | Công thức đúng, giao diện dễ dùng, tải nhanh, kiểm thử mobile |
| P1 | SEO/Merchant | Xác minh feed, lỗi nguồn dữ liệu thật, GA4 event và Search Console |

Worktree quan sát: CMS `feat/pts-cms-workflow-20261126`, mô phỏng `feat/pts-solar-visual-calculator-20261126`, commerce `feat/pts-order-lookup-20261126`. Không coi thay đổi trên các nhánh này là đã merge/deploy.

Tài liệu [sổ tay cũ](SO-TAY.md), [status cũ](00-project/status.md) và [migration](16-laravel-migration/README.md) còn giá trị lịch sử; xác minh từng hướng dẫn với source Laravel/Nuxt hiện hành. [Index đầy đủ](README.md).


## Cách tự sửa một tính năng từ đầu đến cuối

1. Ghi rõ hiện tượng, tài khoản/role thử, URL, dữ liệu mẫu đã ẩn thông tin cá nhân, kết quả mong muốn. Tái hiện trên local.
2. Dùng bản đồ bên trên tìm màn hình → API → xử lý nghiệp vụ → model/schema. Đọc cả test liên quan trước khi sửa.
3. Chạy `git status --short`, `git branch --show-current`, `git log -5 --oneline`; không ghi đè công việc người khác. Tạo nhánh riêng nếu cần.
4. Sửa nhỏ nhất có thể; kiểm tra cả đường thành công, lỗi, phân quyền, dữ liệu rỗng và thao tác lặp. Nếu đổi schema, dùng migration có kế hoạch rollback.
5. Chạy test liên quan và build; kiểm tra diff. Chỉ triển khai sau khi có backup, release identifier và phương án quay lại.
6. Cập nhật tài liệu này hoặc tài liệu chuyên sâu trong cùng thay đổi: file nào đổi, lý do, cách test, trạng thái local/staging/production. Không ghi secret, token, cookie, dữ liệu khách hàng.

## Đọc trạng thái đúng

- Commit ghi ở đầu là snapshot **source local**, không chứng minh production đang chạy commit đó. Kiểm tra release log + runtime trước khi kết luận.
- Có thư mục/module không đồng nghĩa đã bật tính năng hoặc đã nghiệm thu.
- Khi chưa xác minh, ghi CHƯA XÁC MINH; không tự đánh dấu hoàn thành.
- Trước khi thực thi lệnh thay đổi dữ liệu hoặc deploy, xác nhận host, database, branch và backup. Không dùng reset DB, xóa volume hay import fresh để sửa lỗi thông thường.
