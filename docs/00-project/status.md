# Current status

Cập nhật gần nhất: 2026-09-22. Phần lớn nội dung bên dưới (tới dòng "Checkout
idempotency local") là log tích lũy từ trước 2026-09-06 và mới ở Git + Docker
local tại thời điểm ghi — KHÔNG tự suy ra các mục đó đã lên production chỉ vì
đợt 2026-09-08/09 đã bắt đầu deploy thật (xem mục riêng bên dưới); mỗi mục cũ
giữ nguyên phạm vi "local" như lúc viết trừ khi có ghi chú deploy production
rõ ràng. Trạng thái dữ liệu trống đã được chủ project xác nhận là do chưa có
nguồn thật; không coi đó là blocker kỹ thuật và không tự lấp.

## 2026-09-22: chuẩn hóa còn 9 danh mục sản phẩm, local, chưa deploy

- Cây sản phẩm public còn đúng 9 danh mục cấp 1 theo thứ tự đã duyệt. Đổi nhãn
  `Trụ đèn & phụ kiện` thành `Trụ đèn` và `Hệ thống bơm năng lượng mặt trời`
  thành `Bơm nước năng lượng mặt trời`; giữ slug public để bảo toàn SEO.
- `Hệ khung & phụ kiện tấm pin` không còn hiển thị như danh mục cấp 1. Bản ghi
  được ẩn, không xóa; ba danh mục cấp 2 và 12 sản phẩm của nó được chuyển thẳng
  sang `Tấm pin năng lượng mặt trời`. Không tạo danh mục cấp 3.
- URL cũ có redirect 301, phạm vi campaign được giữ ở ba danh mục cấp 2 và rail
  trang chủ đã đổi từ công thức 10 ô sang 9 ô. Migration
  `047_normalize_product_top_categories.php` đã áp dụng, hậu kiểm trên local;
  production chưa chạy migration và chưa deploy code này.
- Backup local trước khi ghi: `backup/local/20260922-150810` (checksum đạt).

## 2026-09-20: production đã cutover sang VPS và có quy trình vận hành chuẩn

- DNS `phucthinhsolar.com` đã trỏ vào VPS `222.255.182.182`; production hiện
  chạy bằng Docker/Caddy trong `/srv/phucthinhsolar`, có SSH. Host DirectAdmin
  cũ không còn là đường deploy website/database thường ngày.
- Deploy dùng `scripts/deploy-production.sh`: `--safe` cho thay đổi thông thường
  hoặc rủi ro cao; `--fast` chỉ cho frontend rủi ro thấp và có danh sách file
  nhạy cảm bị chặn tự động.
- Image đang chạy sau đợt commerce/mobile là `phucthinhsolar-web:86811592c357`;
  image rollback gần nhất là `phucthinhsolar-web:62f31b6a6284`.
- Backup tự động trên VPS hoạt động; bản full gần nhất đã được kiểm tra trong
  deploy. **Bản sao off-site vẫn chưa được cấu hình**, nên hỏng/mất toàn VPS
  vẫn là rủi ro chưa được loại bỏ.
- Điểm bắt đầu cho chủ hệ thống:
  [owner-system-handbook.md](../06-operations/owner-system-handbook.md).

## 2026-09-17: VPS mới chạy được với dữ liệu production, chưa cutover DNS

- Website đã chạy trên VPS `222.255.182.182` với **dữ liệu production thật**
  (54 bảng + 1.125 file upload). Preflight migration READY, 9 trang chính trả
  200, 0 cảnh báo PHP. **DNS chưa trỏ** — host cũ vẫn phục vụ khách.
- Phát hiện gói migration mà phiên trước đưa lên VPS là **backup local**, không
  phải production (976 file = 949 local + 27 mới). Đã dựng lại gói đúng từ dump
  phpMyAdmin production + mirror FTP `upload/`.
- Hai lỗi hạ tầng đã sửa: secret mount `0600 root` khiến `www-data` không đọc
  được → 500 toàn site; và healthcheck gọi `robots.txt` (file tĩnh) nên vẫn báo
  healthy trong lúc đó, làm `deploy-release.sh` mất khả năng tự rollback.
- Timer backup đã bật và chạy thử PASS; bundle runtime image (420 MB) đã xuất.
  **Off-site vẫn chưa có** — `backup.sh` đang cảnh báo đúng.
- Chi tiết và việc còn lại: [handoff 2026-09-17](../13-handoffs/2026-09-17-vps-migration-state.md).

## 2026-09-17: nhịp dọc toàn site về một thang, local

- Đo `getComputedStyle` ở 1440px và 390px: trang chủ có **5 giá trị padding
  section khác nhau** (44/56/58/64/70), hub có 3 cặp khác nhau (34/58, 28/50,
  32/56). Đã gom về 64px desktop / 48px mobile cho section cấp trang, và
  32/56 desktop / 24/40 mobile cho wrapper hub.
- Giữ nguyên `padding-top:0` ở các dải mở đầu và khoảng cách bên trong hub.
- Làm qua token trong `design-tokens.css` (nạp cuối); rollback = xoá một khối.

## 2026-09-16: WebP, ảnh showroom và media responsive local, chưa deploy

- Docker PHP 7.4 đã có GD WebP + `cwebp`; frontend tự ưu tiên WebP cho thumbnail
  JPG/PNG, tự fallback ảnh gốc nếu hosting thiếu codec và giữ ảnh gốc sau upload.
- Có CLI `scripts/optimize-webp.php` chạy file/thư mục theo dry-run; 18 ảnh
  marketing đang dùng đã giảm tổng dung lượng 41,9% mà không xóa JPG nguồn.
- Trang Giới thiệu dùng ảnh showroom thật do chủ project cung cấp: WebP 1920 px,
  WebP 960 px cho mobile và JPEG fallback. Migration 043 đóng gói asset + cập nhật
  DB theo điều kiện, mặc định chỉ preview và có rollback không xóa file.
- 10 banner danh mục đã được rà đúng ngữ cảnh; ba card Giải pháp từng thiếu ảnh đã
  có minh họa; dải chứng nhận trang chủ có nền quản trị được và bản quét lớn hơn.
- Media regression đạt 28/28 desktop/mobile. Full pre-deploy gồm 113 màn Admin,
  503 route, security, form và commerce đã đạt; chưa ghi/deploy production.

## 2026-09-15: nội dung và điều hướng Giải pháp local, chưa deploy

- Đã chuyển hành trình chính sang một landing `/giai-phap/` và năm bài chi tiết
  đi thẳng; nhóm Doanh nghiệp/Hộ gia đình cũ chỉ còn để giữ URL tương thích.
- Menu, card và switcher dùng tên ngắn; breadcrumb bài chi tiết còn ba nấc và
  canonical thống nhất `/giai-phap/{slug}/`.
- Đã nhập có transaction toàn bộ nội dung Markdown do chủ project cung cấp vào
  CKEditor local: một landing, năm bài chi tiết, SEO và CTA dùng chung. Backup
  trước import: `backup/local/20260915-144101`. Không ghi production.
- Playwright Giải pháp đạt 10/10 trên desktop/mobile, gồm URL cũ chuyển 301. Chi
  tiết xem `docs/13-handoffs/2026-09-15-solution-content-routing-report.md`.

## 2026-09-15: audit Admin, GUI và responsive local, chưa deploy

- Trang Đại lý đã bỏ khoảng trắng sau breadcrumb, thu gọn hero, cân lại form/nội
  dung và giữ H1 kể cả khi Admin tắt ảnh hero.
- Container/header/card được mở rộng theo Full HD, 2K và màn hình rộng; ma trận
  360–2560 px phủ năm hành trình public, kiểm tra cả tràn ngang, breakpoint, logo,
  lưới sản phẩm và form Đại lý.
- Quét 113/113 màn Admin, 248 quyền và 503 route đạt; các form/commerce/security
  preflight local đạt. Không ghi production.
- Bài Tin tức tự loại cover khi CKEditor đã có cùng hình; nội dung Giải pháp được
  đơn giản hóa về giọng chính thức Phúc Thịnh Solar.
- Chi tiết test, rủi ro nội dung và deploy/rollback xem
  `docs/10-audits/2026-09-15-admin-gui-responsive-audit.md`.

## 2026-09-12 → 2026-09-14: hoàn tất redesign local, chưa deploy

- Nhánh `NewLayoutWeb` đã hoàn tất tái bố cục trang chủ theo nhịp mockup mới,
  vẫn giữ nhận diện xanh navy – vàng, dữ liệu thật, URL public và các backend
  form hiện hữu. Chi tiết, test và phần chờ duyệt xem
  `docs/13-handoffs/2026-09-12-homepage-redesign-continuation-report.md`.
- Trang Về chúng tôi đã được thiết kế lại cho cả `/ve-chung-toi/` và
  `/gioi-thieu/`, dùng nguồn dữ liệu Admin hiện hữu và không tạo schema mới.
  Chi tiết xem `docs/13-handoffs/2026-09-14-about-page-redesign-report.md`.
- PHP 7.4 lint, Playwright desktop/mobile và toàn bộ cổng
  `LOCAL_ENFORCE_RBAC=1 ./scripts/predeploy-local.sh` đã đạt ở mốc bàn giao.
- Các thay đổi này mới ở Git + Docker local, chưa deploy demo/production và
  không ghi dữ liệu production. Trước khi deploy vẫn cần chủ project duyệt
  giao diện, xác minh SEO homepage, metadata đối tác, ảnh upload CTA và dữ liệu
  timeline About.

## 2026-09-08 → 2026-09-09: lịch sử production trên host cũ

Tại thời điểm này production chưa có SSH — toàn bộ deploy bên dưới từng thực
hiện qua FTP (file) + phpMyAdmin (SQL do chủ project tự chạy), không qua CI/CD
hay migration runner. Thông tin này chỉ là lịch sử; production hiện đã ở VPS.

- Tái cấu trúc cây danh mục sản phẩm: 12→10 danh mục cấp 1, +25 danh mục cấp 2,
  xếp lại 405 sản phẩm, dựa theo audit ChatGPT Plus chủ project cung cấp.
  Migration `migrations/033_restructure_category_tree.php`; SQL sinh sẵn đã
  chạy qua phpMyAdmin trên production. Xóa 1 sản phẩm trùng (SP 365, cascade
  đầy đủ) và 2 danh mục rỗng không dùng.
- Nhập 54 ảnh sản phẩm đèn JINDIAN/Blue Carbon (khớp theo đúng mã sản phẩm,
  script `scripts/import-lamp-images-local.php`), áp dụng cả local/demo và
  production. 1 ảnh (`BCT-OLF-02.jpg`) cố ý bỏ qua vì không đủ căn cứ khớp mã
  giữa 4 biến thể công suất OLF — không đoán.
- Tính năng mới: banner trang chủ đổi được ảnh nền (Admin → nhiều ảnh, tự
  chuyển ảnh), thay cho ô upload ảnh tĩnh cũ; không tái dùng chế độ slideshow
  cũ theo yêu cầu chủ project ("chưa hợp gu").
- Bật public giá + giỏ hàng toàn site (chủ project tự bật qua Admin) — xem
  ghi chú sửa lại dòng "Toàn bộ giá public đang ẩn..." bên dưới.
- Rà soát responsive Android/mobile toàn site và sửa trên production, gồm:
  nút liên hệ nổi (Facebook/Zalo/Phone) từng đè lên nội dung/che tên công ty ở
  cuối trang; carousel "Tại sao nên chọn PTS" xung đột Slick-vs-CSS-grid; popup
  giỏ hàng lặp giá/tràn ngang trên mobile; header và nút cuộn-lên-đầu giật khi
  cuộn (thiếu ngưỡng chống rung); nút cuộn-lên-đầu đè lên cụm nút liên hệ; menu
  dropdown desktop có 2 icon mũi tên chồng nhau (đã gộp lại 1, đúng màu vàng
  thương hiệu, chỉ hiện khi mục thật sự có danh mục con).
- Chưa xác minh trên thiết bị Android/iOS thật: có báo cáo lưới sản phẩm hiện
  2 cột trên trình giả lập desktop nhưng 1 cột trên điện thoại thật — đã rà
  toàn bộ CSS/JS/PHP liên quan, không tìm thấy cơ chế nào trong code có thể
  gây khác biệt này; đang chờ ảnh chụp thật từ chủ project để đối chiếu thay
  vì đoán và sửa sai chỗ.

## WORKING ON LOCAL

- Trang sản phẩm có giá gốc/giá khuyến mãi/% giảm giá.
- Bộ lọc thương hiệu, công suất và mức giá.
- Giỏ hàng, Mua ngay, checkout COD/chuyển khoản và trang cảm ơn.
- Review sản phẩm, ảnh feedback và nhãn “Đã mua hàng”.
- Admin quản lý đơn hàng và admin chỉnh sửa 5 trang chính sách.
- Video YouTube, 19 trường thông số có cấu trúc, danh sách thông số riêng và nội dung chứng nhận theo từng sản phẩm.
- Admin và Excel CSV/XLS/XLSX nhập/xuất từng thông số; frontend dựng bảng từ dữ liệu cấu trúc và vẫn fallback về HTML kỹ thuật cũ.
- 4 dự án hiện có đều có ảnh và nội dung.
- **[CẬP NHẬT 2026-09-09]** Giá public + giỏ hàng đã được BẬT trên production (chủ project tự bật qua Admin → Thiết lập thông tin → "Hiển thị giá sản phẩm", khóa `public_product_prices`). Dòng mô tả gốc bên dưới về mặc định "Liên hệ báo giá" chỉ còn đúng cho trạng thái TRƯỚC khi bật; cơ chế công tắc (Admin/Excel giữ giá nội bộ riêng, lưu xong tự xóa cache) không đổi.
- Canonical, Twitter metadata, robots, sitemap, HTTP security headers và chặn file nhạy cảm đã có regression local.
- Wordmark “Phúc Thịnh Solar” trong template public dùng vàng kim gradient.
- Đợt 2026-08-31: phóng to giao diện public (thang chữ/header/container/section title), cân lại form liên hệ + chi tiết dự án, rà soát responsive mobile (nút liên hệ nổi, gutter, canh chữ, footer đầy đủ), sửa lỗi khung ảnh chi tiết sản phẩm phình trên mobile, khôi phục bộ lọc Thương hiệu/Công suất/Loại SP ở listing. Có `docs/07-features/ecommerce-upgrade-proposal.md` (phân tích Kitawa, chờ chốt phạm vi).
- Có script backup database/upload local kèm checksum; restore rehearsal đạt 49 bảng và 775 file upload.
- Media fallback đã phục hồi 85/85 URL ảnh WordPress legacy; package release vẫn giữ các asset đang được tham chiếu.
- Migration 007 thêm index route; lookup local dùng index thay cho full scan nhỏ.
- Đã loại N+1 ở menu, block trang chủ, danh mục Sản phẩm và thống kê review trong danh sách Sản phẩm Admin; full pre-deploy đạt.
- Profiling authenticated bốn danh sách Admin không còn N+1 theo từng dòng; bộ lọc địa giới Đơn hàng dùng hai cấp Tỉnh/TP → Phường/Xã và chỉ tải phường/xã sau khi chọn tỉnh.
- Ba thử nghiệm conditional loading CSS/JS đã được hoàn nguyên để giữ tương thích visual và tính năng.
- Đã cài Playwright Chromium trong `tools/visual-tests`; full suite đạt 44 test, 2 skip có chủ ý. Có 7 screenshot baseline đã review, gồm lưới danh mục trang chủ desktop/mobile; regression phủ thêm mega menu, danh mục rỗng, modal tư vấn/báo giá, AJAX chuyển nhóm sản phẩm và drawer bộ lọc mobile.
- PDP v3 dùng luồng dọc gallery/buy box → mô tả → hướng dẫn lựa chọn → thông số → bảo hành → đánh giá → form tư vấn → nội dung liên quan; CTA “Yêu cầu báo giá” cuộn đến form trong trang, form vẫn dùng CSRF, reCAPTCHA và backend newsletter hiện có. HTML PDP chỉ còn một H1 nhìn thấy.
- Import Excel sản phẩm xử lý được thương hiệu chưa có: preview gom tên mới và bắt quản trị viên chọn tạo mới, ghép hãng hiện hữu hoặc bỏ trống; brand mới và sản phẩm được áp dụng cùng lượt, có rollback thủ công cho MyISAM.
- Giá sản phẩm trong CRUD Admin và bulk import dùng chung một validator: chặn giá khuyến mãi lớn hơn giá gốc và luôn tính lại phần trăm giảm; bản ghi local #316 đã được sửa qua form Admin từ discount 0 thành 10.
- ADM-P1-004: `table_order` và `table_order_detail` đã chuyển sang InnoDB trên Docker local bằng migration 008; `order_detail` có index theo order/product. Regression xác nhận rollback thật xóa cả order/detail và không đổi tổng dữ liệu. Predeploy đã tích hợp preflight chỉ đọc để chặn orphan, token trùng, migration 008/009 dở dang hoặc sai thứ tự. Production chưa chạy migration; vẫn phải backup, restore rehearsal và dừng checkout trong thời gian ALTER.
- Reader XLSX data-only đã bỏ qua theme/custom property/sharedStrings không đầy đủ và chuẩn hóa Strict OOXML sang namespace PHPExcel hỗ trợ; lỗi parser còn lại được bắt thành thông báo Admin có kiểm soát.
- A3 e-commerce hoàn tất: trang chủ có 12 danh mục nổi bật; bộ icon đồng nhất
  đã đủ 12/12 và được cài bằng script ghép slug, không ghi đè ảnh riêng đã chọn.
- A4 e-commerce hoàn tất: trang danh mục có nút “Bộ lọc” trên mobile/tablet, drawer trượt có backdrop, khóa cuộn, Escape/focus/ARIA và tự đóng sau khi lọc AJAX; desktop giữ sidebar hiện hữu.
- Đã sửa cú pháp `.htaccess` upload từng làm ảnh/logo trả HTTP 500; pre-deploy kiểm tra trực tiếp media để chống tái phát.
- Đã lập dependency/usage map CSS, JS, media và bốn luồng video legacy; chưa xóa/tách asset khi chưa đủ browser/staging evidence.
- ADM-P1-012: sửa liên hệ trong Admin không còn bắt buộc trường địa chỉ (đồng bộ với form liên hệ Theme V2 đã bỏ trường này); `test-admin-readiness.php` phủ thêm case lưu liên hệ khi địa chỉ trống.
- ADM-P1-013: `docker-compose.local.yml` bind-mount webroot cho service `web`, sửa code không cần build lại image; `caches`/`upload`/`thumbs` chuyển sang named volume. Xem `docs/02-development/local-setup.md`.
- ADM-P2-001: 8 truy vấn + tính toán của bảng điều khiển chuyển từ `templates/index_tpl.php` sang controller `administrator/sources/index.php` (route mặc định nạp qua `administrator/index.php`); template chỉ render. Kết quả query và mảng chart giữ nguyên (characterization + 3 assertion trong `test-admin-readiness.php`).
- Auth full-page Admin không còn xoay `user_token` ở mỗi page load; token chỉ đổi khi đăng nhập mới để giữ phát hiện concurrent login mà không tạo race giữa request cùng phiên. `lastlogin` được throttle 60 giây và update có điều kiện; HTTP integration xác nhận hai page load liên tiếp không ghi lại auth state.
- ADM-P1-014: status API cập nhật set-based cho tối đa 500 dòng được chọn khi client gửi trạng thái đích 0/1; vẫn kiểm tra đủ ID tồn tại, RBAC/CSRF/table/attribute allowlist. Toggle legacy không có giá trị đích giữ hành vi tuần tự để tương thích.
- ADM-P1-008 (batch 1): danh sách Sản phẩm có menu “Điền dữ liệu thiếu”, lọc allowlist theo ảnh đại diện/mô tả/nội dung/thông số/công suất, hiển thị badge thiếu và liên kết thẳng tới form sửa. Bulk status dùng chung điều kiện filter để không cập nhật lệch phạm vi.
- ADM-P1-005/P1-008 (batch 2): từ danh sách đang lọc dữ liệu thiếu có thể mở màn bulk và xuất Excel/CSV đúng tập con; workbook vẫn có snapshot/conflict detection và cột ảnh chỉ đọc. Scope tùy ý bị bỏ qua, chỉ năm trạng thái thiếu dữ liệu trong allowlist được áp dụng.
- UX export luôn giữ nút “Xuất tất cả sản phẩm”; khi vào từ filter có nút riêng “Xuất nhóm đang lọc”. Dropdown nhóm thiếu không còn bị card cắt và có cuộn khi viewport thấp.
- ADM-P1-005 (batch 3): màn nhập/xuất hàng loạt nhận ZIP ảnh đặt tên theo mã sản phẩm, bắt buộc xem trước và xác nhận trước khi ghi. Chỉ bổ sung ảnh đại diện đang thiếu; không thay/xóa ảnh hiện hữu. ZIP bị giới hạn 20 MB/200 ảnh, từng ảnh 5 MB, kiểm tra extension + MIME + kích thước thực, chặn thư mục/path traversal và tổng giải nén quá 100 MB; commit kiểm tra lại mã, ảnh trống và mốc cập nhật, có rollback DB/file theo lượt.
- ADM-P1-005 (batch 4): ZIP ảnh hỗ trợ tên `MÃ-01` cho ảnh đại diện và `MÃ-02...` cho gallery; tên `MÃ.jpg` cũ vẫn tương thích và được lưu thành `MÃ-01`. Preview tách số ảnh chính/phụ, không ghi đè ảnh chính hoặc nhân đôi gallery cùng tên; commit kiểm tra lại dữ liệu, giới hạn file sau resize và rollback cả bản ghi gallery/file nếu lượt lỗi.
- ADM-P1-001: `PDODb` không còn render HTML rồi `die()` tại 15 nhánh lỗi connect/prepare/query/query-builder. Data layer ném `PDODbException` có message công khai trung tính; controller có thể catch/rollback, chẩn đoán chỉ giữ query template không ghép tham số. Regression xác nhận kết nối dùng lại được sau catch + rollback và toàn bộ pre-deploy giữ hành vi thành công như cũ.
- Quản trị yêu cầu tư vấn/đăng ký đại lý có pipeline theo cấu hình: ưu tiên yêu cầu mới, bộ đếm và lọc theo trạng thái, tìm theo thông tin khách, ghi chú nội bộ và trạng thái `Mới → Đã tiếp nhận/Đang xử lý → Hoàn tất`. Controller chỉ nhận field nghiệp vụ và từ chối trạng thái ngoài allowlist; integration test tạo fixture tạm, kiểm tra danh sách/lưu/từ chối rồi dọn sạch.
- Router public đã thay chuỗi lookup `$requick` tuần tự bằng một resolver `UNION ALL` parameterized, chỉ nhận table/field từ allowlist và giữ thứ tự ưu tiên route cũ. Test đối chứng đạt 503/503 slug và xác nhận mỗi slug chỉ gọi resolver DB một lần; Playwright chức năng đạt 37 PASS, 1 skip. Screenshot danh mục hiện lệch baseline vì DB local đang hiển thị nhóm sản phẩm thiếu ảnh, nên chưa cập nhật ảnh chuẩn.
- Chuẩn hóa Admin API batch 1: `login`, `status`, `upload` trả thêm envelope `ok/message/code/data` nhưng giữ nguyên field legacy cho JavaScript hiện tại. Security và authenticated Admin integration test đạt; endpoint HTML/scalar và database array chưa đổi.
- Admin API batch 2: lỗi CSRF/quyền JSON và `database.php` dùng cùng envelope; JavaScript bảo trì database hỗ trợ song song định dạng cũ/mới. Endpoint HTML fragment và scalar vẫn giữ nguyên để tránh phá consumer legacy.
- Admin API batch 3: cập nhật `numb` trả JSON rõ ràng và kiểm tra kết quả DB; input Admin tự phục hồi giá trị cũ khi request lỗi. Integration test xác nhận mutation đúng và fixture được dọn sạch.
- Admin API batch 4: sao chép product/news bỏ đệ quy slug không giới hạn, trả JSON và chỉ reload khi thành công. Sửa lấy sai ID từ boolean `insert()`, chuẩn hóa `cong_suat`/giá cho MariaDB strict mode và bắt lỗi DB thành response trung tính; integration test tạo bản nháp slug mới rồi cleanup về 0 fixture.
- Admin API batch 5: kiểm tra slug trả envelope JSON, xác thực định dạng/độ dài và ép kiểu ID. Điều kiện loại trừ bản ghi trong `Functions::checkSlug()` dùng parameterized query; integration test bao phủ slug trùng, slug của chính bản ghi, slug mới, ID bất thường và input sai định dạng.
- Admin API batch 6: ba endpoint kéo-thả menu/index/detail dùng chung validation ID, allowlist bảng, giới hạn 500 dòng và transaction; trả JSON envelope thay vì response rỗng. Client báo lỗi request, mapping RBAC `sortable_update` được đồng bộ với menu và integration test bao phủ cả ba bảng cùng trường hợp ID trùng.
- Admin API batch 7: sửa thao tác xóa sortable từng dùng sai selector nên có thể xóa nhầm bảng menu khi thao tác index/detail. Endpoint ánh xạ `menu/index/detail` qua allowlist, xác minh kết quả xóa và trả JSON; client chỉ gỡ dòng sau khi server xác nhận. Integration test bao phủ đúng cả ba bảng và trường hợp nhóm giả mạo.
- Admin API batch 8: mutation bình luận kiểm tra RBAC theo type lấy từ bản ghi gốc, trạng thái chỉ nhận `hienthi` và phản hồi Admin chỉ ghi các cột allowlist. Xóa bình luận xác minh lại DB, response giữ field legacy đồng thời thêm envelope; route/phương thức sai trả lỗi JSON rõ ràng. Integration test bao phủ duyệt, từ chối trạng thái tự chế, chống giả mạo phạm vi phản hồi và xóa cả luồng cha/con.
- Admin workflow batch 9: luồng lưu cấu hình sortable bỏ `var_dump($_POST)`, kiểm tra quyền `sortable_update`, allowlist field setting/options và validate bốn mảng đồng bộ cho từng nhóm menu/index/detail (tối đa 200 dòng). Ghi item, setting và SEO trong cùng transaction; ID hiện hữu phải thuộc đúng bảng. Integration test xác nhận payload thiếu bị từ chối, không lộ dữ liệu POST và không ghi dở setting.
- Admin API batch 10: xóa một/nhiều ảnh gallery trả envelope JSON và xác minh bản ghi đã xóa trước khi báo thành công. Xóa hàng loạt kiểm tra toàn bộ ID thuộc đúng album trước khi ghi để tránh xóa dở; JavaScript chỉ gỡ ảnh khỏi giao diện sau phản hồi thành công và hiện lỗi server khi thất bại. Integration test bao phủ xóa đơn, từ chối danh sách lẫn ID ngoài album và xóa batch hợp lệ.
- Admin API batch 11: kéo-thả gallery đổi từ cập nhật tuần tự từng ảnh sang một câu `UPDATE ... CASE` parameterized, giới hạn 200 ID và bắt buộc danh sách khớp toàn bộ album. API xác minh kết quả, trả envelope JSON nhưng giữ `id`/`numb` legacy; client báo lỗi và tải lại thứ tự server khi request thất bại. Integration test bao phủ reorder thành công và từ chối ID trùng không ghi dở.
- Admin API batch 12: sửa tiêu đề/số thứ tự từng ảnh gallery chỉ nhận field allowlist, số thứ tự 1–9 chữ số, xác minh DB rồi trả HTML mới trong envelope JSON. Client chỉ render lại album sau thành công và phục hồi từ server khi lỗi. Renderer API đóng riêng output buffer legacy phát sinh để HTML không làm hỏng JSON; integration test bao phủ cập nhật tiêu đề và từ chối số sai định dạng.
- Admin API batch 13: bootstrap AJAX không còn `die()` rỗng khi thiếu/hết phiên; mọi endpoint dùng chung trả HTTP 401 với code `AUTH_REQUIRED`. JavaScript có handler toàn cục báo hết phiên và chuyển về trang đăng nhập một lần, tránh người dùng tưởng mutation im lặng thành công. Integration test xác nhận contract trên cả API JSON và endpoint HTML fragment.
- Admin API batch 14: dropdown danh mục/địa giới giữ HTML fragment khi thành công nhưng từ chối method, bảng, type và level sai bằng HTTP 400/405 + JSON envelope. Client giữ dropdown hiện tại và báo lỗi thay vì chèn chuỗi lỗi vào form. Integration test bao phủ parent rỗng tương thích, level/table ngoài allowlist và method ngoài POST.
- Admin API batch 15: `filer.php` chuẩn hóa nốt method/command/module/scope/layout/hash/context sai thành HTTP 400/405 + JSON envelope; mutation gallery trả lỗi quyền dạng JSON. Refresh hợp lệ vẫn trả HTML và đóng đúng output buffer, client báo lỗi mà không dựng lại album giả. Integration test bao phủ refresh đủ fixture và ba nhóm request sai trước đây từng trả body rỗng.
- Admin API batch 16: bootstrap AJAX cài exception boundary chung; lỗi DB/runtime chưa được endpoint bắt riêng sẽ rollback transaction đang mở, ghi chi tiết vào error log và trả HTTP 500 với envelope `INTERNAL_ERROR` trung tính, không lộ SQL/stack cho trình duyệt.
- Admin API batch 17: hai endpoint legacy `component.php` và `save_add_item.php` tiếp tục bị vô hiệu hóa HTTP 404 nhưng nay trả contract JSON `LEGACY_API_DISABLED`; không khôi phục file manager hoặc đường ghi sortable cũ.
- Admin API batch 18: integration test dùng assertion schema chung để khóa đủ `ok/message/code/data` và kiểu dữ liệu cho lỗi hết phiên, endpoint fragment/legacy, validation, CSRF, gallery và sortable; tài liệu API được đồng bộ với trạng thái code hiện tại.
- Checkout idempotency local: form phát token ngẫu nhiên 256-bit, migration 009 thêm unique index trên đơn hàng; request replay trả lại đúng đơn đã commit, không tạo detail hoặc gửi email lần hai. Token được kiểm tra cùng session và chỉ xoay sau khi đã tạo trạng thái trang cảm ơn; regression hai tiến trình đồng thời cùng nhận một order ID.

## PARTIAL / CHỜ DỮ LIỆU THẬT

- 5 trang chính sách đã có URL nhưng nội dung đang rỗng.
- DB local hiện có 404 sản phẩm (405 trước đó, trừ 1 bản trùng SP 365 đã xóa
  trong đợt tái cấu trúc danh mục 2026-09-08). Đối chiếu trực tiếp 2026-09-09
  (query thật, không suy đoán từ số cũ): 194 có ảnh đại diện, 210 chưa có ảnh
  (giảm từ 244 nhờ đợt nhập 54 ảnh đèn JINDIAN/Blue Carbon, dù không phải hết
  54 ảnh đều là ảnh đại diện mới — một phần vào gallery của sản phẩm đã có
  ảnh chính), 401 thiếu mô tả ngắn (`descvi`), 351 thiếu nội dung chi tiết
  (`contentvi`, không đổi) và 182 chưa có công suất hợp lệ (`cong_suat`). Đây
  là dữ liệu đang chờ nguồn thật, không tự ẩn/xóa. Có 3 phương thức thanh toán
  đang hiển thị; không phát hiện việc bị xóa.
- Lịch sử gồm lô ORIANA `san-pham-20260831-da-bo-sung-oriana.xlsx` và file master `san-pham-master-fixed-2026-09-01.xlsx`; từ 2026-09-12 các file nguồn nằm tại `D:\LinhTinh01\artifacts\`, tiếp tục được giữ ngoài Git/release. Xem `docs/13-handoffs/2026-09-12-source-assets-moved-to-d-drive.md`.
- Chứng nhận và video chỉ hiển thị khi admin nhập dữ liệu thật.
- Dữ liệu kỹ thuật thật sẽ được cập nhật từ file Excel bên ngoài; không chạy migration 006 apply nếu chưa có phê duyệt riêng.
- Nội dung mô tả chi tiết từng sản phẩm cần biên tập theo tài liệu kỹ thuật thật.
- SMTP production và extension PHP trên hosting chưa được xác minh; chạy
  `migrations/000_check_hosting_runtime.php --require-production` khi deploy.

## SECURITY

- Các finding code chính của Security Review ngày 2026-08-27 đã được xử lý và regression đạt trên local.
- CKFinder/elFinder đã đi qua bootstrap session + RBAC Admin dùng chung; quyền
  chỉ xem bị chặn 403, editor đúng quyền vẫn upload/rename/delete được, file
  PHP/script/HTML bị từ chối và mode tạo mới là `0644`/`0755`.
- Mật khẩu Admin tạo/đổi mới dùng `password_hash()`; tài khoản MD5 legacy vẫn
  đăng nhập bằng mật khẩu hiện hữu và được nâng hash tự động ngay lần login
  thành công đầu tiên. Sai mật khẩu không ghi DB.
- SMTP không còn tắt kiểm tra TLS: PHPMailer xác minh certificate/hostname và
  từ chối self-signed cho mọi nhánh provider; local mới verify cấu hình tĩnh,
  còn gửi thật phải kiểm tra trên hosting.
- Frontend, public API, Admin và Flash dùng chung bootstrap session; cookie bật
  strict mode, HttpOnly, SameSite=Lax và Secure khi request là HTTPS.
- `administrator/api/component.php` chỉ còn stub JSON 404; toàn bộ file-manager
  body và credential hard-code phía sau `exit()` đã được loại khỏi source.
- CKFinder license key được lấy từ production config ngoài Git hoặc environment,
  không còn lưu literal trong file được track.
- RBAC đã bật trong source và regression local đạt; vẫn cần test tài khoản
  nghiệp vụ, HTTPS, cookie Secure, proxy, SMTP, reCAPTCHA và upload video trên
  staging/production.
- Không coi nhánh hiện tại là sẵn sàng production cho tới khi cổng staging và backup hosting đạt.

## STILL MISSING

- Nội dung chính sách được doanh nghiệp phê duyệt.
- Nội dung/ảnh/video/chứng nhận thật cho sản phẩm.
- Staging/production và quy trình deploy/rollback hosting chưa diễn tập; runbook
  và release có checksum đã sẵn sàng để thực hiện có kiểm soát.
