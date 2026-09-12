# Bàn giao Codex: tiếp tục redesign trang chủ — 2026-09-12

## Trạng thái

Nhánh `NewLayoutWeb` (từ `feature/ecommerce-upgrade`), đã push lên
`origin/NewLayoutWeb`, commit `305675e`. Toàn bộ mới chỉ chạy ở **local**
(Docker `pts-php74-local`/`pts-mariadb-local`) — **chưa đụng demo/production**,
chủ project sẽ tự quyết định khi nào duyệt lên demo.

Không suy đoán thêm những gì báo cáo này chưa nói tới — đọc trực tiếp code ở
các file liệt kê bên dưới trước khi sửa tiếp, vì nhiều thứ đã đổi khác hẳn so
với các tài liệu `docs/13-handoffs/2026-09-11-*`, `docs/10-audits/2026-09-11-*`
(mô tả Hero kiểu cũ — 2 tài liệu đó nay **lỗi thời** ở phần Hero, xem mục 1).

## 1. Hero — đã đổi kiến trúc hoàn toàn, đừng tin 2 tài liệu Sep-11

`templates/layout/slide.php` giờ có **3 layout** cùng tồn tại trong
`.pts-hero-layouts`, chuyển bằng 1 nút tròn `.pts-hero-layout-toggle`
(chu kỳ 1→2→3→1, lưu lựa chọn `localStorage.ptsHeroLayout`, JS ở
`assets/js/apps.js`):

1. `.pts-home-hero` (`data-pts-hero-layout="1"`) — ảnh nền crossfade + cột
   chứng nhận, bản gốc lâu đời nhất, giữ nguyên 100%.
2. `.pts-hero2` (`data-pts-hero-layout="2"`, **mặc định**) — ảnh full-bleed +
   form tư vấn nổi bên phải, giữ nguyên 100%.
3. `.pts-hero-carousel` (`data-pts-hero-layout="3"`, **MỚI hôm nay**) — carousel
   editorial 4 slide thật (ảnh/eyebrow/tiêu đề/mô tả/2 CTA/tag riêng từng
   slide), dữ liệu ở `libraries/home_hero_carousel.php` hàm
   `getHeroCarouselSlides()` (mảng PHP tĩnh, CHƯA có Admin quản lý — xem
   comment đầu file để biết hướng mở rộng bằng `options` JSON sau này).

`docs/13-handoffs/2026-09-11-homepage-hero-review-implementation-plan.md` và
`docs/10-audits/2026-09-11-full-site-admin-alignment-retest.md` mô tả
`home_hero_mode=text` với ĐÚNG 2 layout do khách tự chọn, ảnh nền AI-composite,
số liệu hard-code `10.000+/500+/5+` — mọi phát hiện **khác Hero** trong 2 tài
liệu đó (migration 036, ảnh chính sách thiếu, `review_require_order_code`,
quyền `he-thong-dai-ly` lỗi thời, Docker build lỗi mirror Debian) **vẫn đúng**,
chỉ riêng phần Hero là lỗi thời.

## 2. Đối chiếu với mockup mới nhất — CHƯA khớp, đây là việc chính cần làm tiếp

Chủ project gửi 1 ảnh mockup AI-generated (không phải thiết kế cuối, chỉ để
lấy "phong cách/nhịp"):
`/mnt/c/Users/ADMIN/Downloads/ChatGPT Image 14_30_49 12 thg 9, 2026.png`
(qua WSL) và nhận xét bản hiện tại "vẫn hơi hướng layout cũ", có chỗ "y sì như
cũ chưa đổi mới" hoặc "dư thừa chưa bỏ". Đối chiếu cụ thể:

### Đã sửa theo đúng hướng mockup (không cần làm lại)

- Danh mục sản phẩm: đổi từ thanh cuộn ngang icon nhỏ sang **lưới tĩnh 2 hàng**
  (CSS Grid 12 cột, 4 card "HOT" span 3 + 6 card thường span 2, tự xuống hàng),
  thêm "Xem tất cả sản phẩm →" góc phải — khớp đúng bố cục "HỆ SINH THÁI THIẾT
  BỊ" trong mockup. Nhãn badge đổi từ "Chủ lực" → "HOT" theo đúng yêu cầu.
  Vẫn giữ nguyên cơ chế bấm card → AJAX đổi lưới sản phẩm bên dưới (không đổi
  JS, `api/product2.php`).
- Khối "Giới thiệu ngắn" (About teaser) — đã có, cấu trúc khớp mockup (ảnh
  trái, eyebrow/tiêu đề/mô tả/4 bullet 2×2/CTA phải).
- Khối "Thương hiệu" — đã có (mockup gọi là "THƯƠNG HIỆU ĐỒNG HÀNH").

### CHƯA khớp mockup — cần làm tiếp, đây là trọng tâm

1. **Quy trình** (`.pts-process-grid`, `index_tpl.php`): hiện đang là 4 ô nền
   navy đậm, mỗi ô có đoạn mô tả dài — vẫn mang hơi hướng khối `process-slider`
   navy cũ, chỉ bỏ carousel chứ chưa đổi ngôn ngữ thị giác. Mockup dùng 1 hàng
   ngang nhẹ nhàng hơn: icon tròn nhỏ + số + tiêu đề ngắn, nền sáng, nối bằng
   mũi tên/chevron giữa các bước, KHÔNG có đoạn mô tả dài hiển thị sẵn (có thể
   ẩn description hoặc rút gọn 1 câu). Cần thiết kế lại theo hướng nhẹ/thoáng
   hơn, không phải hộp tối như hiện tại.
2. **Thương hiệu** (`.pts-brands`, `index_tpl.php` + CSS cùng tên): đang hiện
   NGUYÊN ẢNH CHỤP GIẤY CHỨNG NHẬN (khổ dọc, nhiều chữ, con dấu) trong card
   lớn — nhìn rối, không giống dải logo phẳng gọn gàng trong mockup. **Không
   có asset logo phẳng/nền trong suốt riêng cho từng brand trong DB** (chỉ có
   `table_photo` type=`chungnhan`, đúng là ảnh chứng nhận) — cần hỏi chủ
   project có file logo riêng (PNG nền trong suốt) của HUAWEI/SHARP/JINDIAN/
   ZUOLIGHT/BLUE CARBON không; nếu có thì đổi hẳn sang hiện logo, nếu không thì
   ít nhất thu nhỏ + làm nhạt (grayscale, 1 dải ngang mảnh kiểu "as seen in")
   thay vì card lớn. **Không tự đặt tên brand nào khác** (mockup ghi "Sungrow"
   là ví dụ AI bịa, PTS không có brand này — chỉ dùng đúng 5 brand thật).
3. **Sơ đồ "Lợi ích" hình tròn** (`$loiich`, khoảng giữa `index_tpl.php`) —
   VẪN CÒN nguyên, đứng riêng 1 section, không xuất hiện trong mockup và đã bị
   3 tài liệu rà soát trước đó đề nghị ẩn/gộp — vẫn CHƯA xử lý. Đề xuất: ẩn
   khỏi trang chủ (theo đúng cách đã làm với `$solieu` — comment + `false &&`,
   không xoá code/data) hoặc gộp nội dung 4 lợi ích vào khối Giải pháp.
4. **"Hoạt động dự án" trùng dữ liệu với "Dự án tiêu biểu"** — xác nhận bằng
   evidence thật: `sources/index.php:20` (`$dahoatdong`) và biến `$dalist`/
   `$featuredProjects` dùng cho khối "DỰ ÁN ĐÃ THỰC HIỆN" phía trên **CÙNG một
   type `du-an`** trong `table_news`, chỉ khác cách truy vấn (list phẳng 6 dòng
   mới nhất vs group theo tab + carousel Slick). Đây là trùng lặp thật, không
   phải suy đoán — cần gộp làm 1 (giữ khối "Dự án tiêu biểu" đã redesign, bỏ
   cột "Hoạt động dự án" cạnh Kiến thức ở `index_tpl.php` dòng ~557-580).
5. **Giải pháp theo nhu cầu** — vẫn dùng tab UIkit mặc định (`uk-switcher`),
   chưa đổi CSS sang dạng pill-button ngang như mockup (Hộ gia đình/Doanh
   nghiệp/Trang trại/Công trình công cộng). Chưa làm.
6. **"Bạn đang cần gì?"** — hiện đang phân loại theo NHU CẦU (đại lý/dự án
   chiếu sáng/tư vấn theo nhu cầu, đã có sẵn từ trước, không phải tôi thêm).
   Mockup phân loại theo ĐỐI TƯỢNG khách hàng (Hộ gia đình/Doanh nghiệp/Dự án/
   Đối tác) — đây là đổi CHIẾN LƯỢC nội dung, không chỉ CSS, **cần chủ project
   xác nhận trước khi đổi**, không tự quyết.
7. **Băng CTA đại lý** — vị trí hiện tại (ngay sau About, trước Giải pháp) và
   thiết kế (nền navy đặc) chưa khớp mockup (ảnh nền bắt tay + 1 băng ngang
   gần cuối trang, trước Kiến thức). Cân nhắc dời + đổi nền ảnh, rủi ro trung
   bình vì phải di chuyển khối lớn trong template — đã cố tình chưa làm phiên
   này.
8. Mockup có thêm 1 form "NHẬN TƯ VẤN" đầy đủ nhúng ngay TRƯỚC footer (không
   chỉ nút mở modal). Cẩn thận nguyên tắc "chỉ 1 form tư vấn dài trên trang" đã
   nêu ở mọi tài liệu trước — nếu thêm form này, cân nhắc bỏ form nhúng trong
   Hero layout 2 (`pts-hero2-form-card`) để không bị 2 form dài cùng lúc, hoặc
   xác nhận với chủ project trước.

## 3. Việc đã hoàn tất và verify bằng Playwright (không cần làm lại)

- Ẩn khối "Con số ấn tượng" (`$solieu`) — số liệu hiện tại là số tròn rõ ràng
  chưa xác minh (1.000.000 tấm pin, 1.000 inverter, 1.000 diện tích, 100 dạng
  mẫu mã — xem `index_tpl.php`, điều kiện đổi thành `if(false && count($solieu))`).
  Không xoá query/data.
- Quy trình: bỏ 2 Swiper (`icon-slider`/`process-slider` trong `.home-7`),
  guard bằng `document.querySelector(...)` trước `new Swiper()` ở
  `assets/wp-content/themes/local/js/home080f.js` (file THẬT được load, xem
  `templates/layout/js.php:48` — có 1 bản trùng tên ở
  `assets/themes/local/js/home080f.js` KHÔNG được dùng, đừng sửa nhầm file đó
  lần nữa). Guard là bắt buộc vì các Swiper khác (trang sản phẩm, về chúng
  tôi...) nằm chung 1 câu lệnh `var`, lỗi "container not found" giữa chừng sẽ
  làm hỏng toàn bộ chuỗi.
- Dự án tiêu biểu: giữ nguyên Slick + cơ chế tab theo `$dalist`, chỉ redesign
  CSS card (`.home-6 .project`) — bỏ khối navy che thông tin, gộp
  khách hàng/địa chỉ/ngày hợp đồng thành chip.
- Category grid 4 lớn ("HOT") + 6 nhỏ: xem mục 2.

## 4. Ràng buộc vẫn áp dụng

- Header/footer giữ nguyên, không đổi URL/slug public, không đổi backend/CSRF/
  reCAPTCHA của form hiện có.
- Không tự chọn số liệu/nhãn "ủy quyền"/"đối tác chiến lược" khi chưa xác
  minh; không xoá dữ liệu/code cũ, chỉ ẩn (theo đúng cách đã làm ở mục 3).
- Chỉ triển khai local; demo/production chờ chủ project duyệt riêng.
- PHP 7.4 lint + Playwright (desktop/mobile, console không lỗi) cho mọi file
  sửa, giống cách đã làm trong toàn bộ commit `305675e`.

## 5. File liên quan

`templates/index/index_tpl.php`, `templates/layout/slide.php`,
`libraries/home_hero_carousel.php`, `sources/index.php`,
`libraries/type/config-type-product.php`, `assets/css/theme-v2.css`,
`assets/js/apps.js`, `assets/wp-content/themes/local/js/home080f.js`.
