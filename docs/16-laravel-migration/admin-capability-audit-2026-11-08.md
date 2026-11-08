# Đối chiếu Admin legacy → Filament tại `7265b9ad`

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

| Legacy capability | Laravel/Filament equivalent | Test/evidence | Trạng thái |
|---|---|---|---|
| Product list/search/filter và form nhiều trường | `ProductResource`, 7 tab: chung, giá, media, specs, mô tả, tài liệu, SEO | Screen desktop/mobile; save unchanged với JSON, ảnh, khối nội dung | PASS cho đọc/sửa giữ dữ liệu |
| Product create/delete | Create/Edit pages; delete có guard reviews/orders | Livewire create/delete, kiểm không sót content/SEO | PASS |
| Product copy | `EditProduct` có action tạo bản sao ẩn, mã SKU trống, sao chép nội dung/SEO/ảnh/tài liệu | `ProductDuplicateTest`: bản sao độc lập với sản phẩm gốc và không mang review/order | PASS cho bản sao; ảnh/tài liệu dùng lại đường dẫn file gốc |
| Product bulk status | Bulk hiện/ẩn trong `ProductResource` | Có code, chưa thử action và quyền trên record | GAP |
| Product bulk price, CSV feed/template, import/export | `ListProducts` có CSV giá/trạng thái và CSV sản phẩm/ảnh riêng; xem trước, báo cáo từng dòng, áp dụng transaction | `ProductPriceCsvTest`, `ProductMediaCsvTest`: round-trip 0 thay đổi, tạo sản phẩm ẩn, thay ảnh đã upload, giữ caption ảnh cũ, chặn ảnh thiếu và dữ liệu đổi sau preview | PASS cho giá/trạng thái, tạo sản phẩm ẩn và gắn ảnh có sẵn; nạp ảnh vật lý hàng loạt cần thực hiện trước CSV |
| Category/Brand CRUD, thứ tự, SEO, khối kế thừa | `CategoryResource`, `BrandResource`, `contentSection`, `seo` | Screen; save unchanged; create/delete; sort field | PASS cho CRUD; kéo sắp xếp chưa có |
| Product specs, documents, applications, PDP inheritance | Tab Thông số/Mô tả/Tài liệu; resolver domain | `ProductSpecsTest`, `ProductContentResolverModesTest`, Product unchanged, API snapshot | PASS cho dữ liệu hiện có; upload tài liệu chưa kiểm |
| News/solution/project/policy CRUD | 4 `PostResource`, RichEditor và media | Screen; save unchanged Article/Solution/Project; create/delete cả 4 loại | PASS cho CRUD; copy/policy HTML edit còn GAP |
| Content sections import/export/restore/toggle/upload | `PageItemResource`, content blocks builder, media field | Screen và snapshot public; không có import/export/restore tương đương | GAP |
| Visual Studio draft → preview → publish → rollback | Bài viết có `ArticleDraftResource`, phiên bản, URL xem trước ký 15 phút trên Nuxt, publish transaction và khôi phục thành bản nháp | `ArticleWorkflowTest`: public giữ nguyên trước publish, preview có noindex, rollback nội dung, chặn draft cũ, giữ HTML | PASS cho bài viết; section tree, các loại content khác và sửa trực tiếp qua form cũ còn GAP |
| Gallery/photo/banner/video | `AlbumResource`, `BannerResource`, `VideoResource`, post/product repeaters | Screen; Album unchanged; file path code | GAP cho upload/delete/reorder thực tế |
| Rich editor media | `Fields::rich` lưu `filemanager/editor` trên disk uploads | Code path đúng; chưa upload và đọc lại file qua public route | GAP |
| SEO page và SEO từng entity | `SeoPageResource`, SEO relation trên resource | Screen, save unchanged một số entity, snapshot 1.754/1.754 | PASS cho read/save unchanged; sửa nội dung SEO cần test |
| Settings/site info | `SiteSettingsPage`, credentials trong env | Save unchanged test, screen | PASS cho các field hiện có |
| Mail template preview/test send | `MailTemplatesPage` sửa 5 mẫu email, xem trước bằng dữ liệu giả, gửi thử có rate limit; SMTP vẫn trong env | `MailTemplatesAdminTest`: lưu override giữ default, render HTML an toàn, kiểm quyền và gửi bằng Mail fake | PASS cho UI/copy; SMTP thật cần staging mailbox |
| Integrations settings | reCAPTCHA, VNPay, mail, carrier config trong env | Code/config; carrier chỉ `mapping_required` | GAP cho Admin UI và carrier API |
| Orders list/edit/payment | `OrderResource` khóa thanh toán trực tiếp; xác nhận tiền thủ công có tham chiếu, người xử lý và transaction; VNPay do callback ký xác nhận | `AdminScreensTest` kiểm sửa đơn giữ dữ liệu khách, không giả trạng thái VNPay, xác nhận manual và chặn lặp | PASS cho xử lý manual cơ bản; GAP cho refund, status workflow và đơn thật ở staging |
| Contact/newsletter/leads | `LeadResource` hợp nhất contact/consultation/dealer; phân công, hạn theo dõi, lọc quá hạn, lịch sử xử lý | `LeadTriageTest`: thao tác Filament thật, giữ nguyên dữ liệu khách, ghi lịch sử, chặn phân công sai quyền | PASS cho triage; GAP cho automation marketing và quy trình nhiều bước |
| Comment/review moderation | `ReviewResource` duyệt/ẩn/sửa/trả lời, bulk có xác nhận, xem ảnh/video khách gửi; `ReviewModeration` kiểm quyền và trạng thái | `ReviewModerationTest`: Filament action/bulk thật, public API hiện/ẩn, reply, media route, RBAC âm | PASS trên fixture synthetic; cần dùng dữ liệu thật ở staging |
| User management, owner/editor RBAC | `UserResource`, `RequiresPermission`, 7 area và 5 quyền thao tác: CSV/bulk sản phẩm, publish bài viết, manual payment, review moderation, mail test | `AdminLoginTest`, direct-route matrix và test service/action từng khu vực | PASS cho 5 thao tác này; upload và các domain nội dung khác còn GAP |
| Places (Tỉnh/TP → Phường/Xã) | `ProvinceResource` tạo/sửa/ẩn; `WardResource` tạo/sửa, phí giao theo ward; không cho xóa vật lý | `ProvinceAdminTest`: tạo thật, public API, ẩn tỉnh, chặn quyền; checkout validation tests | PASS cho quản trị hai cấp; kiểm dữ liệu thật ở staging |
| Quận/Huyện | Không đưa vào form/order mới, `district=0` legacy | Quy tắc `AGENTS.md` và checkout tests | RETIRED |
| Cache clear Admin | Laravel cache/queue vận hành bằng CLI | Chưa có nhu cầu business cho nút Admin | RETIRED khỏi Admin |
| Product campaign, OneSignal, tags, lang, word, generic Excel | Không tìm thấy workflow tương đương | Cần kiểm usage hiện hành trước khi quyết định nghỉ hẳn | GAP |

## Ưu tiên đóng gap

1. Mutation thật trên DB staging: tạo/sửa/xóa/khôi phục từng nhóm, quyền
   owner/editor theo action, upload và rich editor, kiểm public API sau lưu.
2. Nạp ảnh vật lý hàng loạt trước khi ghép CSV, upload/delete/reorder media
   và kiểm thao tác với file thật. CSV giá và sản phẩm/ảnh đã có preview và round-trip.
3. Mở workflow phiên bản sang Giải pháp/Dự án/Chính sách và page sections;
   chặn đường sửa trực tiếp khi editor nháp đủ trường. Sau đó template mail,
   campaign và các module còn consumer thật.
4. Orders/reviews cần fixture và tình huống thật vì clone local có 0 row; không
   lấy việc màn mở được làm bằng chứng chức năng.
