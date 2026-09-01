# Bàn giao liên tục cho AI/developer — 2026-08-31

Tài liệu này là điểm vào nhanh khi chuyển tab, tài khoản AI hoặc người thực hiện. Nó tổng hợp phương pháp phối hợp, trạng thái đã triển khai và bước tiếp theo; không thay thế checklist chi tiết.

## 1. Mệnh lệnh ưu tiên

Checklist cha phải luôn được đọc và tiếp tục cho đến khi hoàn thành:

- [Checklist sẵn sàng chạy quảng cáo và cập nhật hosting](2026-08-31-ads-hosting-readiness-checklist.md)

Yêu cầu phát sinh về giao diện, icon, nội dung hoặc lỗi là task con. Xử lý task con xong phải quay lại checklist cha. Mục tiêu xuyên suốt là toàn bộ project nhẹ hơn, nhanh hơn và an toàn hơn nhưng không được mất ảnh, icon, giao diện, dữ liệu hoặc tính năng.

Phạm vi hiện tại chỉ gồm source, Git và Docker local PHP 7.4/MariaDB 10.4. Chưa được deploy, ghi production, nhập dữ liệu thật, chạy migration dữ liệu hoặc bật RBAC nếu chưa có phê duyệt riêng của chủ project.

## 2. Cách bắt đầu ở tab/tài khoản mới

Đọc theo thứ tự:

1. `AGENTS.md` ở root.
2. Tài liệu này.
3. Checklist cha nêu trên.
4. `docs/00-project/status.md`.
5. Audit liên quan trực tiếp đến mục sắp làm trong `docs/10-audits/`.

Sau đó chạy kiểm tra chỉ đọc:

```bash
git status --short
git log -12 --oneline
```

Không động vào `artifacts/` nếu nó còn là thư mục untracked; đây là dữ liệu do chủ project giữ ngoài lịch sử Git. Xác nhận `HEAD` với commit gần nhất được ghi trong mục 6 trước khi tiếp tục.

Prompt ngắn có thể dùng ở tab AI mới:

```text
Đọc AGENTS.md và docs/13-handoffs/2026-08-31-ai-continuity-handoff.md, sau đó follow docs/13-handoffs/2026-08-31-ads-hosting-readiness-checklist.md như checklist cha. Chỉ làm trên Git/Docker local, không deploy hoặc ghi production nếu chưa được tôi cho phép riêng. Tiếp tục từ mục ưu tiên gần nhất, kiểm thử đầy đủ và báo cáo tiếng Việt.
```

## 3. Phương pháp làm việc đã chứng minh hiệu quả

Luồng bắt buộc:

```text
INSPECT -> UNDERSTAND -> PLAN -> IMPLEMENT -> TEST -> DEBUG -> VERIFY -> DOCUMENT -> REPORT
```

Mỗi batch nên nhỏ, độc lập và có rollback rõ ràng:

1. Đọc route, source, template, API, schema/config và docs liên quan.
2. Đo baseline hoặc tái hiện lỗi trước khi sửa; tách fact khỏi phỏng đoán.
3. Nêu phạm vi ảnh hưởng và dữ liệu có thể bị tác động.
4. Chỉ sửa nguyên nhân đã có bằng chứng; không cleanup/refactor ngoài task.
5. PHP lint đúng PHP 7.4, JS syntax nếu có JS, `git diff --check`.
6. So sánh hành vi/HTML/query trước và sau; kiểm tra ảnh, icon, log và dữ liệu test.
7. Chạy `./scripts/predeploy-local.sh` cho thay đổi có ảnh hưởng runtime.
8. Cập nhật checklist cha và audit/handoff tương ứng.
9. Commit riêng khi toàn bộ kiểm thử đạt; báo cáo kết quả, file, test, rủi ro, deploy và rollback.

Không dùng “giảm request/query/dung lượng” làm tiêu chí thành công duy nhất. Tối ưu frontend chỉ được chấp nhận khi browser visual, DOM sau JavaScript, console, network và tương tác đều đạt. Nếu người dùng phát hiện khác giao diện hoặc thiếu tính năng, dừng tối ưu liên quan, xác minh và hoàn nguyên riêng phần chưa chứng minh tương thích.

## 4. Các quyết định sản phẩm đã chốt

- Public đang dùng chế độ báo giá toàn website: không công khai giá, Offer schema, giỏ hàng hoặc checkout; giá nội bộ vẫn giữ trong Admin/Excel. Chuyển trạng thái bằng công tắc Admin → Thiết lập thông tin → “Hiển thị giá sản phẩm” (khóa `public_product_prices` trong `table_setting.options`), mặc định dữ liệu cũ là báo giá; chi tiết trong `docs/07-features/public-price-mode.md`.
- Ô Excel trống khi cập nhật nghĩa là giữ dữ liệu website; `__CLEAR__` mới là xóa có chủ ý. Lần đầu nhận file thật chỉ preview, lưu checksum và chưa commit dữ liệu.
- Không chạy migration 006 `--apply`; dữ liệu kỹ thuật thật sẽ đến từ file Excel bên ngoài.
- Wordmark “Phúc Thịnh Solar” trên public luôn dùng vàng kim gradient và có màu fallback.
- Tin tức, Giải pháp và Dự án dùng grid 3/2/1; card đơn không kéo giãn toàn hàng.
- Comment trong code phải ngắn, chuyên nghiệp và giải thích kỹ thuật/tính năng; không ghi lại lời giao việc hoặc hội thoại với chủ project.
- Không loại media/asset legacy trước khi xác minh usage. Không bật lại cả cụm `templates/layout/phone.php` chỉ vì thấy code icon; file legacy này chứa nhiều toolbar trùng và đã bị tắt từ baseline.

## 5. Những gì đã triển khai

### Admin, dữ liệu và chế độ báo giá

- Import/export CSV/XLS/XLSX có preview, conflict detection, lựa chọn ghi đè từng ô, transaction rollback, archive và `__CLEAR__`. Đã dùng pipeline này import file thật lô ORIANA vào DB local (xem mục 6).
- Brand mới trong Excel không còn buộc tạo thủ công trước: preview bắt buộc chọn tạo mới, ghép với brand hiện có hoặc bỏ trống. Tên có sẵn được match không phân biệt hoa thường; brand tạo mới có slug/SEO và được rollback cùng sản phẩm nếu batch lỗi. Importer vẫn không tự tạo brand khi chưa có xác nhận.
- Đã harden PHPExcel reader cho XLSX từ generator khác: data-only import bỏ qua theme/custom property/sharedStrings không đầy đủ, chuẩn hóa Strict OOXML sang namespace transitional và không còn fatal/null notice đã gặp; mọi `Throwable` khi parse được trả thành lỗi preview thay vì làm sập Admin.
- File ngoài mới nhất `san-pham-master-fixed-import-ready (1).xlsx` (SHA-256 `d3c86ab9b3d1d41cf8477e3757af051f51a904e2495bf61c5807a7ecfbf2881c`) đã preview chỉ-đọc: 385 dòng, 0 lỗi/cảnh báo/xung đột. Importer gom 16 trường hợp trùng URL với sản phẩm legacy thiếu mã thành lựa chọn cập nhật/tạo URL mới/bỏ qua; gom 8 brand mới để xử lý một lần. File đặt cả 385 dòng là `Tạm ẩn`, tương ứng 310 tạo mới tạm ẩn và 75 sản phẩm local đang hiển thị sẽ chuyển sang ẩn; Admin nay hiển thị riêng tác động này và bắt buộc tick xác nhận trạng thái. Chưa commit file, DB local và production chưa bị tác động bởi file này.
- Template Excel mới tách trạng thái thành `Hiển thị (1/0)` và `Bán chạy (1/0)` để dùng dữ liệu boolean, không yêu cầu nhập text. Backend vẫn đọc cột text `Tình trạng website` của file cũ để tương thích; thứ tự chuỗi trạng thái được chuẩn hóa để round-trip export/import không sinh cập nhật giả.
- Chế độ giá nay có hai tầng: công tắc toàn site là khóa tổng; từng sản phẩm có checkbox/status `Liên hệ báo giá` (`baogia`) và cột Excel `Liên hệ báo giá (1/0)`. Sản phẩm báo giá vẫn có thể công khai nhưng không lộ giá/discount/Offer, không vào lọc giá và bị chặn thêm giỏ/checkout.
- UX danh sách sản phẩm được giảm thao tác thủ công: bỏ toolbar lặp ở cuối trang và action text trùng trong từng dòng; mỗi cột Bán chạy/Hiển thị/Liên hệ báo giá có checkbox áp dụng cho cả trang với hộp xác nhận. API status nhận giá trị đích 0/1 (không toggle mù), hỗ trợ tối đa 500 ID, kiểm tra permission/CSRF/allowlist và trả JSON. Import bỏ yêu cầu gõ `CAP_NHAT`; checkbox xác nhận tác động chỉ là bước chống nhầm, còn bảo mật vẫn do session/RBAC/CSRF và revalidation server.
- Trang nhập/xuất được tổ chức lại theo 4 bước, ưu tiên Excel và upload; CSV chuyển thành tùy chọn phụ, hướng dẫn an toàn chuyển sang trợ giúp thu gọn, thông báo sau import được gộp thành một kết quả có CTA về danh sách. CSS được scope bằng tiền tố `pts-bulk-` trong template để không ảnh hưởng các trang Admin khác.
- Checkbox trạng thái “Cả trang” dùng AdminLTE switch, phản ánh đủ ba trạng thái bật/tắt/một phần và chỉ đổi các dòng sau khi API xác nhận. `administrator/assets/js/apps.js` được gắn version `filemtime` để tránh HTML mới chạy cùng JavaScript cũ từ browser cache.
- Bulk status không còn giới hạn ở 10 dòng của trang: switch “Hàng loạt” cho chọn phạm vi `Trang này (N)` hoặc `Toàn bộ kết quả (N)` theo đúng danh mục/hãng/từ khóa/cảnh báo dữ liệu hiện tại. Backend dựng lại allowlist filter bằng query parameterized, giới hạn an toàn 5.000 sản phẩm/lượt, kiểm tra RBAC/CSRF/status allowlist và cập nhật set-based; integration test tạo fixture, gọi HTTP rồi dọn sạch đã PASS.
- Sửa `templates/product/product_detail_tpl.php`: khi sản phẩm không có ảnh, MagicZoom `href` fallback `assets/images/noimage.png` thay vì gọi `upload/product/` (HTTP 403). Phát hiện qua `empty-states.spec.js`.
- CTA “Gửi yêu cầu báo giá” trên chi tiết sản phẩm mở modal “Tư vấn & báo giá” hiện có; modal dùng form newsletter có CSRF và được regression mở/đóng trên desktop/mobile.
- A3 e-commerce: bỏ Swiper che bớt danh mục trang chủ, hiển thị đủ 9 danh mục bằng lưới responsive 5/3/2; ảnh lấy từ Admin, thiếu ảnh dùng icon Font Awesome theo loại và click vẫn tải sản phẩm qua AJAX cũ.
- 19 trường thông số chuẩn, thông số riêng, fallback HTML kỹ thuật cũ, video và chứng nhận tùy dữ liệu.
- Validation giá/công suất, lọc Admin, danh sách liên hệ/đơn/newsletter, CSRF và quyền API.
- Public quote-only giữ nguyên giá nội bộ nhưng không lộ giá hoặc thao tác mua hàng. Có công tắc Admin (`public_product_prices` = `1`/`0`); lưu xong `setting.php` gọi `$cache->delete()` để đổi trạng thái ngay; `public_pricing.php` fallback về hằng `PUBLIC_PRODUCT_PRICES` khi khóa vắng.

### Bảo mật

- CSRF cho 42 form Admin và mutation public quan trọng.
- RBAC/action mapping, session hardening, login/public rate limit, trusted proxy handling.
- Upload kiểm tra extension, MIME, size, filename và storage.
- HTTP security headers, chặn file nhạy cảm, canonical/noindex/robots/sitemap regression.
- RBAC vẫn tắt cho đến khi retest tài khoản nghiệp vụ trên staging.

### Giao diện và SEO

- Theme V2, wordmark vàng kim, nội dung/form dễ đọc hơn.
- **Đợt phóng to & rà soát responsive (2026-08-31)** — toàn bộ trong `assets/css/theme-v2.css` + 1 sửa template:
  - Phóng to thang chữ/container/header/breadcrumb/section title cho desktop; footer đồng bộ màu dòng hotline/email.
  - Form liên hệ: ép `.wpcf7-form-control-wrap` về block full-width (trước là `<span>` inline nên ô nhập không lấp cột).
  - Chi tiết dự án: chip "Khách hàng/Địa chỉ" bám dưới tiêu đề (thêm hàng lưới `1fr` hút phần thừa của card).
  - Mobile: gutter 16px, hạ thang chữ ở ≤640px, bỏ canh đều chữ, nút liên hệ nổi `#button-contact-vr` thu nhỏ + ghim góc.
  - **Chi tiết sản phẩm mobile**: sửa lỗi `.left-pro-detail` phình ~33.5M px (owl stage 80M + aspect-ratio vòng lặp) → neo `calc(100vw-32px)` + `contain:layout`; owl thumbnail thành dải cuộn ngang.
  - Bộ lọc listing: khôi phục Thương hiệu/Công suất/Loại SP (trước bị `publicProductPricesVisible()` bọc nhầm cả cụm); chỉ "Mức giá" còn theo chế độ giá.
  - Footer mobile: bỏ accordion, hiển thị đủ.
  - 5 pixel-baseline đã regenerate cho type scale mới.
- Phân tích đối thủ Kitawa + lộ trình nâng cấp e-commerce: `docs/07-features/ecommerce-upgrade-proposal.md` (chờ chủ project chốt phạm vi).
- Grid Tin tức/Giải pháp/Dự án được khóa 3/2/1.
- Sitemap đã loại 4 URL ẩn/không hoạt động; hiện còn 137 URL hợp lệ từ lần crawl local.
- 85 URL ảnh WordPress legacy từng 404 đã có fallback media an toàn; 85/85 trả HTTP 200.
- 57/57 glyph Font Awesome và 10/10 ảnh icon tĩnh được xác minh; regression giữ icon gọi ở header/card sản phẩm và font chính.

### Backup, release và rollback

- Backup local gồm database, upload, metadata và checksum ngoài webroot.
- Restore rehearsal đạt 49 bảng và 775 file upload.
- Có script pre-deploy, tạo release từ commit, manifest/checksum và hướng dẫn rollback.
- Package release hợp lệ gần nhất khoảng 183 MB vì phải giữ media legacy đang được dùng.

### Hiệu năng

- Baseline public warm TTFB local khoảng 20–32 ms; HTML khoảng 60–161 KB.
- Migration 007 thêm 22 index trên 11 bảng route; lookup từ scan khoảng 81–82 dòng xuống ước tính 1 dòng.
- Menu desktop/mobile: trang chủ từ 77 xuống 45 query.
- Block Giải pháp/Dự án trang chủ: 45 xuống 43 query.
- `/san-pham/`: 51 xuống 43 query.
- Admin danh sách Sản phẩm: thay tối đa 20 query đếm review bằng một query batch; dữ liệu đối chứng không sai khác.
- Authenticated Admin local: bốn danh sách không còn N+1 theo dòng; Đơn hàng bỏ hai query địa giới rỗng khi chưa chọn parent.
- Chi tiết sản phẩm public: `Comments::countStar()` 5 query → 1 `GROUP BY star` (44→40 query trang chi tiết ORIANA).
- Danh sách tỉnh `table_city` (63 dòng) chuyển sang query cache 7200s ở `allpage.php` + `order.php` — bỏ 1 query uncached khỏi mọi trang `[a:com]/` (giỏ hàng 41→39).
- Tồn đọng đã ghi nhận: `$requick` (`libraries/router.php`) dò slug tuần tự ~17 lookup index cho trang chi tiết; cần task riêng, không sửa lẻ vì ảnh hưởng toàn bộ routing.
- Profiling method: bật `general_log`+`log_output=TABLE` trên MariaDB local, warm 2 lần, `TRUNCATE mysql.general_log`, hit 1 lần, đếm/nhóm theo pattern chuẩn hoá. Nhớ `SET GLOBAL general_log='OFF'` sau khi đo.

Ba thử nghiệm conditional loading CSS/JS đã được hoàn nguyên vì phản hồi visual cho thấy giao diện/tính năng không còn giống baseline. Asset chuyên biệt hiện tải global để ưu tiên tương thích. Không lặp lại cách tối ưu này nếu chưa có browser regression đầy đủ.

## 6. Mốc Git gần nhất

`HEAD` hiện tại:

```text
8bd8262 style(theme-v2): show the full footer on mobile
a1bfedc fix(product): restore brand/power/type filters in listing sidebar
7b950c0 fix(theme-v2): repair product detail gallery on mobile
9a3df63 style(theme-v2): mobile responsive audit pass
04b793b style(theme-v2): scale up public UI and rebalance contact/project
75f25a4 docs: record N+1 profiling results and remaining router finding
8d3a3d7 perf(public): collapse review star N+1 and cache city list
```

Toàn bộ đã push lên `origin/master` (branch `master` track upstream). Các commit `be1b3d6`, `4314123`, `ad4520e` là lịch sử thử nghiệm conditional loading và đã bị `793e3eb` thay thế. Không cherry-pick hoặc tái áp dụng riêng các commit đó.

Thay đổi DB local (không nằm trong Git): đã import `artifacts/san-pham-20260831-da-bo-sung-oriana.xlsx` — +70 sản phẩm ORIANA (id 467–536, `numb=2` xếp cuối), cập nhật #321/#311; tổng `table_product` type `san-pham` = 151. Backup trước import: `backup/local/20260831-132554`. 70 sản phẩm mới còn thiếu ảnh + `descvi` + `contentvi`. Chạy lại được bằng `scripts/preview-product-bulk-file.php` / `scripts/commit-product-bulk-file.php`.

## 7. Trạng thái kiểm thử gần nhất

- `./scripts/predeploy-local.sh`: PASS (bao gồm assert front-end không lộ giá/nút mua ở chế độ mặc định).
- PHP 7.4 lint các file thay đổi: PASS.
- Security hardening: PASS, gồm 4 assert mới cho công tắc giá (mặc định an toàn, bật `1` hiện giá, sản phẩm giá 0 vẫn báo giá, tắt `0` ẩn lại).
- Admin readiness/RBAC/CSRF/session: PASS.
- Excel/import transaction và cleanup: PASS.
- Public smoke, SEO, static cache/compression headers và log PHP: PASS.
- E2E công tắc giá trên DB local: bật `1` → trang sản phẩm hiện giá + `priceCurrency` + nút giỏ; `0`/vắng khóa → “Liên hệ”, không Offer, `api/cart.php` trả HTTP 409. DB local đã trả về chế độ báo giá (xóa hẳn khóa).
- Import Excel thật: preview 0 lỗi/0 xung đột; commit transaction 70 create + 2 update; `predeploy-local.sh` và Playwright (5 pixel-baseline + smoke/interaction) vẫn PASS sau import và sau khi đặt `numb=2` cho sản phẩm mới.
- Local không còn dữ liệu test theo pre-deploy cleanup.

Môi trường hiện tại không có binary `rg` (Claude Code chỉ cấp qua shell function); `predeploy-local.sh` cần `rg`. Cách chạy: tạo shim `rg` gọi `exec -a rg "$CLAUDE_CODE_EXECPATH"` rồi prepend vào `PATH`. Không sửa script vì đây là hạn chế môi trường, không phải lỗi script.

Playwright `1.62.1` và Chromium đã được cài riêng trong `tools/visual-tests`; browser nằm ở thư mục `.browsers` đã gitignore. Chạy bằng `cd tools/visual-tests && npm test`. Full suite hiện đạt 44 test và 2 skip có chủ ý: smoke/interaction desktop-mobile, giỏ rỗng, tìm kiếm không/có kết quả, form liên hệ có CSRF, chi tiết sản phẩm không ảnh, danh mục rỗng, mega menu desktop, modal tư vấn/báo giá, AJAX danh mục trang chủ, drawer bộ lọc mobile, cùng 7 screenshot baseline đã review. `empty-states.spec.js` chặn GTranslate CDN như visual-baseline.

Ghi chú `templates/contact/contact_tpl.php`: form liên hệ **đang chạy** là block dùng class `wpcf7-*` (leftover WordPress) nhưng đã wired đúng cho PHP — có `form_csrf`, field `dataContact[...]`, `submit-contact`, `recaptcha_response_contact`; handler `sources/contact.php` xử lý đúng. Block ở dòng 1–98 (`contact-form validation-contact`) là bản redesign cũ **bị comment `<!-- ... -->`** từ baseline; nên xoá hẳn block chết + đổi tên class `wpcf7-*` khi có dịp, không phải bug chức năng. Pixel test chặn riêng GTranslate CDN để tránh DOM đổi ngôn ngữ không xác định; mobile MagicZoom vẫn có DOM/ảnh/CTA regression nhưng bỏ element screenshot vì canvas gây Chromium GPU/tile overflow.

Browser test đã phát hiện `.htaccess` trong `/upload` sinh sai cú pháp khiến ảnh/logo trả HTTP 500. Generator và pre-deploy regression đã được sửa; full pre-deploy và Playwright đều đạt sau khi build lại. Kiểm tra tự động này chưa thay thế việc duyệt thẩm mỹ thủ công cho khoảng cách, popup, trạng thái rỗng và toàn bộ tương tác.

## 8. Việc tiếp theo theo ưu tiên

1. **Hoàn tất readiness trước deploy** theo checklist cha: ưu tiên dữ liệu/nội dung sản phẩm quảng cáo và 5 trang chính sách khi chủ project cung cấp/chốt dữ liệu; sau đó thực hiện các bước staging/hosting có phê duyệt.
2. Refactor `$requick` trong `libraries/router.php`: hiện dò slug tuần tự qua ~26 bảng (mỗi trang chi tiết ~17 lookup). Cân nhắc bảng ánh xạ `slug -> (table, type, id)` hoặc một truy vấn UNION. Blast radius toàn routing → cần đọc kỹ + regression đầy đủ trước khi đụng.
3. Duyệt thẩm mỹ thủ công phần chưa tự động hoá được: popup tư vấn sản phẩm, menu mega desktop, trạng thái rỗng danh mục, khoảng cách/tràn ngang trên nhiều breakpoint.
4. Chỉ thử conditional loading hoặc xử lý asset dung lượng lớn theo từng commit nhỏ, dùng 7 screenshot baseline và full suite làm cổng bắt buộc.
5. Lặp lại profiling Admin trên dataset staging gần production; local đã xác nhận bốn danh sách không còn N+1 theo dòng nhưng chưa đủ kết luận capacity hosting.

B3 **So sánh sản phẩm** được chủ project phân loại là tính năng mở rộng: chỉ xem xét sau khi production đã deploy và vận hành ổn định; không tiếp tục triển khai trong giai đoạn readiness hiện tại và không phải điều kiện chặn deploy.

### Chờ dữ liệu thật

- Ảnh + `descvi` + `contentvi` cho 70 sản phẩm ORIANA vừa import (id 467–536); sau khi đủ thì đưa `numb` về 0/1 để lên đầu danh mục.
- Xác nhận danh mục cấp 2 của lô ORIANA (36 Đèn Pha, 28 Đèn Đường, 6 chỉ có cấp 1).
- Chốt sản phẩm/landing page chạy quảng cáo; đối chiếu mã, slug, danh mục, trạng thái, thông số, ảnh và nội dung.
- Bổ sung 5 chính sách và các mô tả/nội dung/thông số còn thiếu cho sản phẩm cũ.

### Chờ staging/hosting và phê duyệt

- SMTP, reCAPTCHA, HTTPS, cookie Secure, HSTS, proxy chain và security headers.
- Document root, PHP extension, upload limit, rewrite, quyền ghi, OPcache, memory/process limits.
- Tài khoản/phân quyền nghiệp vụ, video upload và endpoint legacy.
- Backup/restore staging, package release cuối và production deploy.

Production vẫn **NO-GO** cho đến khi P0, staging và phê duyệt đều đạt.

## 9. Quy tắc báo cáo và bàn giao

Báo cáo cho chủ project bằng tiếng Việt, dẫn bằng kết quả và luôn nêu:

- Kết quả đạt/chưa đạt và số liệu trước/sau.
- File hoặc commit thay đổi.
- Test đã chạy và phạm vi chưa test được.
- Rủi ro còn lại.
- Có hay không ảnh hưởng production.
- Cách deploy và rollback khi phù hợp.

Khi gần hết context/token, cập nhật tài liệu này, checklist cha, `docs/00-project/status.md`, commit các thay đổi đã test và ghi chính xác `HEAD`. Không để công việc quan trọng chỉ tồn tại trong hội thoại.
