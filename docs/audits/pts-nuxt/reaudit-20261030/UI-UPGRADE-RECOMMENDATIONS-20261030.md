# Đánh giá nâng cấp giao diện và trải nghiệm Nuxt

**Ngày:** 30/10/2026  
**Phạm vi:** đánh giá UX/UI toàn website từ audit parity, responsive, mobile rail, Nuxt UI adoption đã có; kiểm tra lại runtime header/sidebar/link CMS trên Nuxt `127.0.0.1:3100`.  
**Checkout:** `/home/admin01/src/Web_PTS_PHP`, nhánh `master`, working tree có nhiều WIP. Chỉ bổ sung báo cáo này; không sửa product code, không đụng production.

## Kết luận

Website đã có nền tảng tốt để tiếp tục polish, không cần làm lại toàn bộ: route coverage theo sitemap QA đạt 457/457 disposition; audit trước đo 10 nhóm trang ở 13 viewport (260 phép đo) và không thấy overflow ngang. Các luồng chính có nội dung và trạng thái tương ứng. Tuy vậy, phát hiện một vấn đề UX mobile chưa có trong báo cáo cũ: menu sản phẩm mở cùng lúc toàn bộ danh mục con, làm drawer kéo dài và đẩy mục điều hướng/CTA xuống dưới.

Khuyến nghị ưu tiên sửa cấu trúc điều hướng mobile trước khi thêm hiệu ứng hoặc làm mới card. Sau đó tập trung vào hình ảnh sản phẩm PDP, nhịp chữ/khoảng cách giữa các page family, rồi áp dụng Nuxt UI có chọn lọc cho form và overlay. Dùng Apple Design như nguyên tắc về thứ bậc, tính dễ đọc, chuyển động ngắn và có mục đích; không biến website thành giao diện Liquid Glass hoặc mô phỏng app native.

## Kết quả kiểm chứng lượt này

| Khu vực | Trạng thái | Bằng chứng |
|---|---|---|
| Header desktop | PASS ở các điểm đã đo | Tại 1920px: nav 18px, nút danh mục 17px, gap 12px. Đo các viewport 320, 390, 768, 1024, 1366, 1440, 1600, 1681, 1800, 1920, 2560: document không tràn ngang; menu desktop chỉ hiện từ 1681px theo contract. |
| Mega-menu hover | PASS sau chỉnh sửa gần nhất | Rê vào trigger mở menu, rê vào panel giữ menu mở, rời toàn bộ vùng menu thì `aria-expanded` chuyển `false`. |
| Link danh mục Kiến thức | PASS trong mẫu | `/category/tin-moi-nhat/`, `/category/tin-truyen-thong/`, `/category/kien-thuc-san-pham/` đều chuyển tới Nuxt route tương ứng, HTTP 200 và title khớp. |
| Sidebar mobile | **FAIL — UX-MOB-01** | 390×844px: drawer có `scrollHeight=1650px`, nhóm sản phẩm có 27 link hiển thị cùng lúc dưới 3 tiêu đề nhóm. CTA tư vấn nằm sau toàn bộ nội dung trong flow cuộn. |
| Dự án cá nhân/doanh nghiệp | Cùng template; data cần xác minh riêng | Hai trang dùng `ProjectCategoryPage.vue`, nên chưa có bằng chứng về hai layout code khác nhau. Nuxt PHP project API hiện không sẵn sàng trên local runtime của lượt này; không thể xác nhận số lượng dự án hiện hành và không nên thêm card giả để làm hai trang trông giống nhau. |

## Finding cần xử lý trước — UX-MOB-01

- **Mức độ:** P2 — ảnh hưởng trực tiếp khả năng tìm danh mục và dùng navigation trên mobile.
- **Trang/component:** sidebar mobile, tất cả trang có `SiteHeader.vue`.
- **Kỳ vọng:** hiển thị các nhóm danh mục cấp đầu; mục có con có disclosure để mở khi người dùng cần. CTA tư vấn luôn trong tầm tay ở cạnh dưới drawer. Đây là cấu trúc PHP hiện dùng qua cây `<ul>` lồng nhau, cùng nút tư vấn ghim đáy.
- **Thực tế:** Nuxt lặp qua `catalogGroupLinks(group)`, hàm này flatten toàn bộ cây thành danh sách link; 27 link và ba nhãn nhóm hiện cùng lúc. Nút tư vấn được đặt sau navigation thay vì ở footer cố định của drawer.
- **Tác động:** ở màn 390×844, danh sách sản phẩm chiếm gần hai chiều cao viewport; người dùng phải cuộn sâu mới tới các nhóm Giải pháp/Kiến thức/Hợp tác và CTA.
- **Source:** Nuxt [SiteHeader.vue](/home/admin01/src/Web_PTS_PHP/frontend/app/components/SiteHeader.vue:1047), flatten cây ở `SiteHeader.vue:104`; CTA trong flow tại `SiteHeader.vue:1117`. PHP dựng tree menu trong [mmenu.php](/home/admin01/src/Web_PTS_PHP/domains/phucthinhsolar.com/public_html/templates/layout/mmenu.php:10), còn CSS ghim tư vấn cạnh dưới tại `header-v3.css:252`.
- **Đề xuất:** dùng `productCategoryTree` của API làm danh mục cấp đầu cho mobile; giữ mục con gập mặc định và mở bằng nút riêng có `aria-expanded`; không dùng ba nhóm shortcut của desktop mega-menu làm cây mobile. Tách drawer thành vùng nội dung cuộn và footer tư vấn cố định, có safe-area/padding để không che link cuối. Giữ URL thật và mục “Xem tất cả sản phẩm”.
- **Verification cần có sau khi triển khai:** 320/390/768px; cây sâu cấp 2–3; scroll/focus/Escape/backdrop; CTA luôn thấy được và không che mục cuối; bấm danh mục cha vẫn tới URL; disclosure chỉ mở/đóng nhánh tương ứng.

## Backlog nâng cấp theo giá trị trải nghiệm

### P1 — Navigation mobile và luồng chọn sản phẩm

1. Xử lý UX-MOB-01 như trên.
2. Giữ mega-menu desktop đủ rộng và dễ quét; nhóm/nhãn/icon phải đi cùng link CMS đang hiển thị. Hover mở/đóng đã được kiểm chứng lại; giữ click/keyboard như lối thay thế.
3. Xem xét header breakpoint như quyết định sản phẩm: hiện desktop nav bắt đầu 1681px để bảo vệ logo và action. PHP dùng nav ngang ở màn phổ biến hơn. Nếu muốn nav ngang từ 1440px, cần đo fit thực tế ở 1321–1680 trước khi đổi breakpoint; không chỉ hạ breakpoint.

### P2 — Trang sản phẩm và chuyển đổi

1. **PDP media:** audit visual trước ghi ảnh sản phẩm trong Nuxt nhỏ hơn và crop/scale khác PHP trong khung gallery. Ưu tiên tăng diện tích ảnh hữu dụng và giữ `contain`/tỷ lệ; không thêm viền hay shadow để bù.
2. **Home:** hero và các section nghiệp vụ đã có dữ liệu/fallback CMS; nâng cấp nên làm rõ thứ bậc headline → sản phẩm → ứng dụng → tư vấn, không tạo section marketing trùng thông điệp. Phần hero hiện khác cách căn/scale PHP có chủ ý, nên chốt hướng redesign trước khi đồng bộ mọi trang.
3. **Forms:** dùng một form liên hệ/nhận tư vấn làm pilot `UFormField` + `UInput`/`USelect`/`UTextarea`. Giữ nguyên tên field, validation, payload, CSRF và trạng thái API. Chỉ mở rộng sau khi kiểm chứng focus/error/success và style parity.
4. **Project categories:** giữ một template chung. Khi API xác nhận nhóm doanh nghiệp rỗng, dùng empty state và CTA; không tạo nội dung dự án giả. Số liệu hiện tại chưa được đọc từ production.

### P3 — Độ đồng nhất thị giác và hoàn thiện

1. Tạo semantic token cho khoảng cách section/gutter và áp dụng theo page family; chưa cần token hóa từng gap nhỏ. Audit trước ghi nhận spacing Nuxt đang trộn Tailwind scale với pixel tùy chỉnh.
2. Định nghĩa quy ước icon cho kích thước, stroke/fill và trạng thái. Giữ SVG đặc thù sản phẩm khi nó truyền tải đúng loại thiết bị; dùng `UIcon`/Lucide cho action phổ thông.
3. Chuẩn hóa states cho button, input, disclosure, carousel và toast/inline feedback; dùng Nuxt UI nơi nó giảm code lặp, không thay native `<details>`/CSS rail đang làm tốt chỉ để tăng số lượng Nuxt UI.
4. Tiếp tục giới hạn transition vào thay đổi trạng thái có ích; giữ `prefers-reduced-motion`, tránh card lift/zoom/shadow đồng loạt.

## Những gì đã ổn, không cần làm lại

- Shared navy/gold tokens khớp với màu nền tảng PHP; deep-navy CTA/footer đã tồn tại. Cảm giác nền khác đến từ section cụ thể dùng solid/gradient và spacing khác, không phải thiếu một token navy toàn site.
- Homepage hero đã lấy slide từ CMS QA và fallback có kiểm soát; dữ liệu kiểm chứng là local QA, không phải production.
- Rail mobile cho PDP applications, dealer testimonials, category applications và catalog navigation đã được sửa/đo trong audit mobile hiện có.
- News category links trên sidebar đã hoạt động qua alias `/category/:slug/` về Nuxt canonical path.
- Dark mode, reduced motion, body type scale và một số hover-card mismatch đã có remediation/evidence trong `REMEDIATION-STATUS-20261030.md`.

## Trạng thái readiness

- **Tiếp tục phát triển UI trên QA:** GO.
- **Đóng UX-MOB-01:** ưu tiên đầu tiên trước đợt polish tiếp theo.
- **Tuyên bố production-ready:** không thuộc kết luận của audit UI này. Report route/viewport coverage là bằng chứng local và theo mẫu; local API dự án của lượt này không hoạt động, production CMS chưa được truy cập.
- **Thay đổi trong lượt audit:** chỉ thêm báo cáo này; không sửa code, không commit, không push, không deploy.

## Tài liệu bằng chứng nền

- [FINAL-UI-PARITY-20261030.md](FINAL-UI-PARITY-20261030.md) — coverage, route families, token/visual summary.
- [MOBILE-HORIZONTAL-SECTION-AUDIT-20261030.md](MOBILE-HORIZONTAL-SECTION-AUDIT-20261030.md) — rail responsive và kết quả sau sửa.
- [NUXT-UI-ADOPTION-AUDIT-20261030.md](NUXT-UI-ADOPTION-AUDIT-20261030.md) — đề xuất áp dụng Nuxt UI có chọn lọc.
- [REMEDIATION-STATUS-20261030.md](REMEDIATION-STATUS-20261030.md) — finding đã sửa và giới hạn còn lại.
