# Đối chiếu Admin legacy → Filament tại `7265b9ad`

> Bản ghi tại thời điểm 2026-11-08, đã cũ ở phần bulk sản phẩm, bản nháp và media. Trạng thái hiện tại xem [`STATUS.md`](STATUS.md).

Ngày kiểm 2026-11-08, trên clone local. Nguồn legacy:
[`phase-0/admin-capabilities.md`](phase-0/admin-capabilities.md). `PASS` chỉ ghi
khi có kiểm runtime/test tương ứng; `GAP` gồm cả chức năng đã có code nhưng chưa
được thử thao tác thực. `RETIRED` chỉ dùng khi quy tắc nghiệp vụ hiện hành nói rõ.

Evidence: `admin-screens.cjs` 46/46 desktop + phone, 0 lỗi (report và ảnh ở
`/tmp/pts-laravel13-admin-audit/`); `AdminScreensTest.php` kiểm mở route, save
unchanged Product/Category/Brand/Post/Album, create/delete Product/Category/Brand/
Article/Solution/Project/Policy/Album, settings, order synthetic và quyền
editor. Audit screen không bấm Save/Xóa. DB rehearsal từ zero và snapshots có
evidence ở `/tmp/pts-laravel13-rehearsal/`.

Cập nhật sau baseline: `ProductContentWorkbookTest` kiểm xuất/nhập XLSX giữ
nguyên, sửa thông số và SEO, preview/apply qua Filament, chống file lỗi/dữ liệu
đổi/quyền thiếu; `EditorialProductLinksTest` và `ArticleWorkflowTest` kiểm liên
kết bài viết → sản phẩm qua form, bản nháp, preview, publish và restore. Test
public chỉ trả sản phẩm hiện, có ảnh; không tự sửa API snapshot cũ.

| Legacy capability | Laravel/Filament equivalent | Test/evidence | Trạng thái |
|---|---|---|---|
| Product list/search/filter và form nhiều trường | `ProductResource`, 7 tab: chung, giá, media, specs, mô tả, tài liệu, SEO; thêm cột/bộ lọc thiếu ảnh, SEO, mô tả, giá, liên kết bài viết | Screen desktop/mobile; save unchanged; `ProductSalesReadinessTest` kiểm lọc và giải quyết gap bằng dữ liệu thật trong test DB | PASS cho đọc/sửa giữ dữ liệu và hàng đợi biên tập |
| Product create/delete | Create/Edit pages; delete có guard reviews/orders | Livewire create/delete, kiểm không sót content/SEO | PASS |
| Product copy | `EditProduct` có action tạo bản sao ẩn, mã SKU trống, sao chép nội dung/SEO/ảnh/tài liệu | `ProductDuplicateTest`: bản sao độc lập với sản phẩm gốc và không mang review/order | PASS cho bản sao; ảnh/tài liệu dùng lại đường dẫn file gốc |
| Product bulk status | Bulk hiện/ẩn trong `ProductResource` | `AdminUploadsTest`: hiện/ẩn hàng loạt thật trên record; người thiếu `catalog_bulk` không thấy thao tác | PASS |
| Product bulk price, CSV feed/template, import/export | `ListProducts` có CSV giá/trạng thái, CSV sản phẩm/ảnh và Excel nội dung/thông số/SEO; batch upload 20 ảnh/lần trả CSV tên file, các luồng có xem trước và áp dụng transaction | `ProductPriceCsvTest`, `ProductMediaCsvTest`, `ProductContentWorkbookTest`: round-trip, tạo sản phẩm ẩn, batch upload, sửa thông số/SEO, chặn dữ liệu đổi sau preview | PASS cho ba luồng bulk; cần vận hành file thật ở staging |
| Category/Brand CRUD, thứ tự, SEO, khối kế thừa | `CategoryResource`, `BrandResource`, `contentSection`, `seo` | Screen; save unchanged; create/delete; sort field | PASS cho CRUD; kéo sắp xếp chưa có |
| Product specs, documents, applications, PDP inheritance | Tab Thông số/Mô tả/Tài liệu; resolver domain | `ProductSpecsTest`, `ProductContentResolverModesTest`, Product unchanged, API snapshot | PASS cho dữ liệu hiện có; upload tài liệu chưa kiểm |
| News/solution/project/policy CRUD | 4 `PostResource`, RichEditor và media | Screen; save unchanged Article/Solution/Project; create/delete cả 4 loại | PASS cho CRUD; copy/policy HTML edit còn GAP |
| Content sections import/export/restore/toggle/upload | `ContentSectionResource`: sửa khối trong form chủ, lịch sử 30 bản và khôi phục khối đã xóa (`SectionHistory`) | `ContentSectionAdminTest`; import/export JSON bỏ có chủ đích (xem STATUS → Không làm) | PASS |
| Visual Studio draft → preview → publish → rollback | Bài viết có `ArticleDraftResource` và preview Nuxt ký 15 phút. Giải pháp/Dự án/Chính sách có `PostDraftResource`, publish transaction, SEO, thông tin Dự án, thiết bị liên quan và khôi phục thành bản nháp | `ArticleWorkflowTest`, `PostWorkflowTest`: public giữ nguyên trước publish, quyền editor/publisher, chặn draft cũ, giữ HTML/SEO, API công khai sau publish, restore | PASS cho bài viết và phiên bản nội dung/SEO của 3 loại còn lại; preview Nuxt, ảnh/thư viện và section tree còn GAP |
| Gallery/photo/banner/video | `AlbumResource`, `BannerResource`, `VideoResource`, post/product repeaters | `ProductMediaAdminTest`, `AlbumMediaAdminTest`: upload tạo file, đổi thứ tự, xóa quan hệ và giữ file gốc | PASS cho sản phẩm/album; banner/video/post upload chưa kiểm thật, xóa file vật lý cần kiểm tham chiếu |
| Rich editor media | `Fields::rich` lưu `filemanager/editor` trên disk uploads | `AdminUploadsTest`: ảnh chèn lưu khi lưu bài vào `filemanager/editor`, đọc lại qua `/upload`, lưu lại không đổi; SVG/file lạ bị chặn và báo lỗi thay vì lưu ảnh hỏng; banner upload ghi file và đọc lại | PASS |
| SEO page và SEO từng entity | `SeoPageResource`, SEO relation trên resource | Screen, save unchanged một số entity, snapshot 1.754/1.754 | PASS cho read/save unchanged; sửa nội dung SEO cần test |
| Settings/site info | `SiteSettingsPage`, credentials trong env | Save unchanged test, screen | PASS cho các field hiện có |
| Mail template preview/test send | `MailTemplatesPage` sửa 5 mẫu email, xem trước bằng dữ liệu giả, gửi thử có rate limit; SMTP vẫn trong env | `MailTemplatesAdminTest`: lưu override giữ default, render HTML an toàn, kiểm quyền và gửi bằng Mail fake | PASS cho UI/copy; SMTP thật cần staging mailbox |
| Integrations settings | `IntegrationsPage` đọc trạng thái VNPay, reCAPTCHA, mail, 4 carrier mà không hiện secret; cấu hình vẫn trong env | `IntegrationsAdminTest` kiểm màn, quyền và không lộ token; Nhất Tín thay 4 carrier (mock/sandbox/production khóa, xem `NhatTinShippingTest`) | PASS cho readiness UI và Nhất Tín MOCK; cấu hình vẫn trong env (cố ý) |
| Orders list/edit/payment | `OrderResource` khóa thanh toán trực tiếp; xác nhận và ghi nhận hoàn đủ tiền thủ công có tham chiếu, người xử lý, transaction và lịch sử đọc; VNPay do callback ký xác nhận | `AdminScreensTest` kiểm giữ dữ liệu khách, xác nhận/hoàn tiền manual, chặn lặp và từ chối VNPay | PASS cho manual full refund ghi nhận ngoài hệ thống; GAP cho refund gateway, partial refund, status workflow và đơn thật ở staging |
| Contact/newsletter/leads | `LeadResource` hợp nhất contact/consultation/dealer; phân công, hạn theo dõi, lọc quá hạn, lịch sử xử lý; trang nguồn/UTM và liên kết sản phẩm để đi từ FE → API → DB → Admin | `LeadTriageTest`, `LeadSubmissionTest`, `lead-source.test.mjs`: lưu nguồn, bỏ query riêng tư/URL ngoài site, gắn sản phẩm từ URL và hiện trong Admin | PASS cho triage và ngữ cảnh nguồn; GAP cho automation marketing và quy trình nhiều bước |
| Comment/review moderation | `ReviewResource` duyệt/ẩn/sửa/trả lời, bulk có xác nhận, xem ảnh/video khách gửi; `ReviewModeration` kiểm quyền và trạng thái | `ReviewModerationTest`: Filament action/bulk thật, public API hiện/ẩn, reply, media route, RBAC âm | PASS trên fixture synthetic; cần dùng dữ liệu thật ở staging |
| User management, owner/editor RBAC | `UserResource`, `RequiresPermission`, 7 area và 5 quyền thao tác: CSV/bulk sản phẩm, publish nội dung, manual payment, review moderation, mail test | `AdminLoginTest`, `ArticleWorkflowTest`, `PostWorkflowTest` kiểm tạo ẩn, chặn sửa trực tiếp nội dung public, quyền publish và route bản nháp | PASS cho 5 thao tác này; upload và page sections còn GAP |
| Places (Tỉnh/TP → Phường/Xã) | `ProvinceResource` tạo/sửa/ẩn; `WardResource` tạo/sửa, phí giao theo ward; không cho xóa vật lý | `ProvinceAdminTest`: tạo thật, public API, ẩn tỉnh, chặn quyền; checkout validation tests | PASS cho quản trị hai cấp; kiểm dữ liệu thật ở staging |
| Quận/Huyện | Không đưa vào form/order mới, `district=0` legacy | Quy tắc `AGENTS.md` và checkout tests | RETIRED |
| Cache clear Admin | Laravel cache/queue vận hành bằng CLI | Chưa có nhu cầu business cho nút Admin | RETIRED khỏi Admin |
| Product campaign, OneSignal, tags, lang, word, generic Excel | Khung khuyến mãi = `ProductCampaign` + `PromoFrames`; Excel sản phẩm = `ProductBulkPage` | `PromoFrameTest`, `ProductWorkbookTest`; OneSignal/tags/lang/word không làm (0 dữ liệu hoặc đã tắt, xem STATUS) | PASS |

## Ưu tiên đóng gap

1. Mutation thật trên DB staging: tạo/sửa/xóa/khôi phục từng nhóm, quyền
   owner/editor theo action, upload và rich editor, kiểm public API sau lưu.
2. Kiểm upload/delete/reorder với banner, video, bài viết và editor; thiết kế
   thu gom file không còn tham chiếu trước khi cho xóa vật lý. CSV giá và ảnh đã
   có preview, round-trip và tải nhiều ảnh từ Admin.
3. Mở workflow phiên bản sang Giải pháp/Dự án/Chính sách và page sections;
   chặn đường sửa trực tiếp khi editor nháp đủ trường. Sau đó template mail,
   campaign và các module còn consumer thật.
4. Orders/reviews cần fixture và tình huống thật vì clone local có 0 row; không
   lấy việc màn mở được làm bằng chứng chức năng.
