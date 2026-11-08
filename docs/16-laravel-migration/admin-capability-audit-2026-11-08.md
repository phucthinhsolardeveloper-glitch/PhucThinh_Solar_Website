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
| Product copy | Chưa có action tương đương | Không thấy copy flow trong Filament | GAP |
| Product bulk status | Bulk hiện/ẩn trong `ProductResource` | Có code, chưa thử action và quyền trên record | GAP |
| Product bulk price, CSV feed/template, import/export | `ListProducts` xuất CSV, xem trước, tải báo cáo từng dòng và áp dụng giá/trạng thái trong transaction | `ProductPriceCsvTest`: round-trip 0 thay đổi, chỉ sửa trường đã chọn, lỗi/ID trùng chặn toàn bộ, Filament action chạy thật | PASS cho giá/trạng thái; ảnh và create qua CSV còn GAP |
| Category/Brand CRUD, thứ tự, SEO, khối kế thừa | `CategoryResource`, `BrandResource`, `contentSection`, `seo` | Screen; save unchanged; create/delete; sort field | PASS cho CRUD; kéo sắp xếp chưa có |
| Product specs, documents, applications, PDP inheritance | Tab Thông số/Mô tả/Tài liệu; resolver domain | `ProductSpecsTest`, `ProductContentResolverModesTest`, Product unchanged, API snapshot | PASS cho dữ liệu hiện có; upload tài liệu chưa kiểm |
| News/solution/project/policy CRUD | 4 `PostResource`, RichEditor và media | Screen; save unchanged Article/Solution/Project; create/delete cả 4 loại | PASS cho CRUD; copy/policy HTML edit còn GAP |
| Content sections import/export/restore/toggle/upload | `PageItemResource`, content blocks builder, media field | Screen và snapshot public; không có import/export/restore tương đương | GAP |
| Visual Studio draft → preview → publish → rollback | Bài viết có `ArticleDraftResource`, phiên bản, URL xem trước ký 15 phút trên Nuxt, publish transaction và khôi phục thành bản nháp | `ArticleWorkflowTest`: public giữ nguyên trước publish, preview có noindex, rollback nội dung, chặn draft cũ, giữ HTML | PASS cho bài viết; section tree, các loại content khác và sửa trực tiếp qua form cũ còn GAP |
| Gallery/photo/banner/video | `AlbumResource`, `BannerResource`, `VideoResource`, post/product repeaters | Screen; Album unchanged; file path code | GAP cho upload/delete/reorder thực tế |
| Rich editor media | `Fields::rich` lưu `filemanager/editor` trên disk uploads | Code path đúng; chưa upload và đọc lại file qua public route | GAP |
| SEO page và SEO từng entity | `SeoPageResource`, SEO relation trên resource | Screen, save unchanged một số entity, snapshot 1.754/1.754 | PASS cho read/save unchanged; sửa nội dung SEO cần test |
| Settings/site info | `SiteSettingsPage`, credentials trong env | Save unchanged test, screen | PASS cho các field hiện có |
| Mail template preview/test send | Chưa có Filament equivalent; SMTP config trong env | Không thấy template editor/preview/send test | GAP |
| Integrations settings | reCAPTCHA, VNPay, mail, carrier config trong env | Code/config; carrier chỉ `mapping_required` | GAP cho Admin UI và carrier API |
| Orders list/edit/payment | `OrderResource`, payment transaction domain | Synthetic order save chỉ đổi handling fields; nguồn local có 0 order | GAP cho order thật, refund/status workflows |
| Contact/newsletter/leads | `LeadResource` hợp nhất contact/consultation/dealer | 2 row import; screen; không có lifecycle round-trip test | GAP |
| Comment/review moderation | `ReviewResource`, bulk approve/hide | Screen; local legacy có 0 review | GAP cho mutation và media |
| User management, owner/editor RBAC | `UserResource`, `RequiresPermission`, 7 permission areas | Login test; direct-route matrix; 2 user import | PASS cho route access; action/upload permission vẫn GAP |
| Places (Tỉnh/TP → Phường/Xã) | `WardResource`, phí giao theo ward; Province nhập từ legacy | 97 tỉnh và 3.321 ward import, 0 orphan; checkout validation tests | PASS cho mô hình 2 cấp; create/edit Province UI GAP |
| Quận/Huyện | Không đưa vào form/order mới, `district=0` legacy | Quy tắc `AGENTS.md` và checkout tests | RETIRED |
| Cache clear Admin | Laravel cache/queue vận hành bằng CLI | Chưa có nhu cầu business cho nút Admin | RETIRED khỏi Admin |
| Product campaign, OneSignal, tags, lang, word, generic Excel | Không tìm thấy workflow tương đương | Cần kiểm usage hiện hành trước khi quyết định nghỉ hẳn | GAP |

## Ưu tiên đóng gap

1. Mutation thật trên DB staging: tạo/sửa/xóa/khôi phục từng nhóm, quyền
   owner/editor theo action, upload và rich editor, kiểm public API sau lưu.
2. Product bulk image và create qua CSV còn thiếu; giá/trạng thái đã có luồng
   round-trip và preview. Chưa gọi Filament là parity đầy đủ với Admin PHP.
3. Mở workflow phiên bản sang Giải pháp/Dự án/Chính sách và page sections;
   chặn đường sửa trực tiếp khi editor nháp đủ trường. Sau đó template mail,
   campaign và các module còn consumer thật.
4. Orders/reviews cần fixture và tình huống thật vì clone local có 0 row; không
   lấy việc màn mở được làm bằng chứng chức năng.
