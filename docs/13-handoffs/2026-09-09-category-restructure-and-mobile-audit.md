# Bàn giao: tái cấu trúc danh mục, nhập ảnh đèn, hero ảnh nền, rà soát mobile (2026-09-08 → 2026-09-09)

## Phạm vi

Production không có SSH. Toàn bộ deploy production trong phiên này thực hiện
qua FTP (`--ftp-ssl --ssl-reqd`, curl) cho file, và phpMyAdmin cho SQL (chủ
project tự chạy các file SQL do phiên này sinh sẵn và đã kiểm thử trước trên
bản khôi phục từ dump production thật). Không có CI/CD hay migration runner
tự động trên production.

## 1. Tái cấu trúc cây danh mục sản phẩm

- Từ 12 xuống 10 danh mục cấp 1 (`table_product_list`), thêm 25 danh mục cấp 2
  (`table_product_cat`), xếp lại 405 sản phẩm — theo audit ChatGPT Plus chủ
  project cung cấp, đối chiếu bằng bằng chứng thật (dump production, không
  suy đoán legacy behavior).
- Migration: `migrations/033_restructure_category_tree.php` (dry-run mặc
  định, `--apply` để ghi). Có 1 lỗi logic đã fix trong phiên: bước ẩn danh
  mục cấp 2 cũ dùng snapshot tên cũ chụp trước khi bước rename kịp chạy trong
  `--apply`, gây báo "không tìm thấy" sai; đã sửa sang query trực tiếp theo
  tên mới.
- Dọn dẹp: xóa 1 sản phẩm trùng (SP id 365, cascade đầy đủ — ảnh, thông số,
  liên kết), xóa 2 danh mục cấp 2 rỗng không dùng.
- Áp dụng: local + demo (Docker) bằng chính migration; production bằng SQL
  sinh sẵn từ migration (`backup/production_migration_033_20260908.sql`) chạy
  qua phpMyAdmin, đã đối chiếu khớp với migration trước khi gửi.

## 2. Nhập ảnh sản phẩm đèn JINDIAN / Blue Carbon

- Nguồn: `C:\Users\ADMIN\Downloads\DaChuanHoa` (Windows, qua WSL). 55 ảnh, 4
  hãng đèn.
- Đối chiếu tên file → mã sản phẩm bằng thuật toán tự viết (chuẩn hóa bỏ
  khoảng trắng/gạch ngang, thử bỏ hậu tố `-01`/`-02`/số phụ), đạt 54/55 khớp
  tự động + xác nhận thủ công; **1 ảnh (`BCT-OLF-02.jpg`) cố ý bỏ qua** — không
  có chỉ dấu công suất, có thể khớp bất kỳ ảnh nào trong 4 biến thể OLF, không
  đủ căn cứ để đoán.
- Script: `scripts/import-lamp-images-local.php` (theo đúng mẫu
  `import-oriana-images-local.php`: đối chiếu theo mã sản phẩm chính xác,
  không ghi đè ảnh đại diện đã có sẵn — tự động chuyển sang gallery, transaction
  đầy đủ có rollback an toàn qua `register_shutdown_function`).
- Áp dụng: local + demo trực tiếp bằng script; production bằng SQL sinh sẵn
  (`backup/production_lamp_images_20260909.sql`,
  `backup/production_lamp_images_2_20260909.sql`) chạy qua phpMyAdmin + upload
  file ảnh qua FTP.

## 3. Banner trang chủ: đổi được ảnh nền

- Yêu cầu chủ project: giữ nguyên hiệu ứng/chữ/panel chứng chỉ đang có trên
  banner, chỉ cho phép đổi ảnh nền qua Admin; **không** dùng lại chế độ
  slideshow legacy đang có sẵn trong code ("chưa hợp gu", sẽ thiết kế lại
  riêng sau).
- Loại config `home-hero-bg` mới trong `libraries/type/config-type-photo.php`,
  từ `photo_static` (1 ảnh) nâng lên `man_photo` (tối đa 5 ảnh,
  1920×900) theo yêu cầu bổ sung "muốn có chế độ chuyển ảnh" ngay sau đó.
- Frontend: `templates/layout/slide.php` thêm layer ảnh nền + lớp phủ gradient
  giữ nguyên markup glow/texture/panel chứng chỉ; `assets/js/apps.js` thêm
  vòng lặp crossfade (`data-pts-hero-cycle`, mặc định 6000ms).

## 4. Rà soát responsive Android/mobile — production

Bắt đầu từ yêu cầu "quét kỹ responsive mobile của android và xử lý đi", audit
trực tiếp trên production (`https://phucthinhsolar.com`, viewport 412×915).
Toàn bộ đã deploy production, xác minh lại bằng Playwright sau khi deploy chứ
không chỉ tin thao tác upload thành công. Các lỗi đã sửa:

- **Nút liên hệ nổi (Facebook/Zalo/Phone) đè lên nội dung** — nguyên nhân
  gốc: `templates/layout/footer.php` có 1 khối `<style>` thừa hard-code
  `bottom:130px!important`, là thứ DUY NHẤT đang thắng được 1 file CSS plugin
  thứ 3 (`stylephone3781.css`, nạp sau cùng qua `js.php`) đặt `bottom:0` không
  `!important`. Xóa khối thừa ban đầu gây regression tạm thời (mất `!important` cần
  thiết) — đã fix lại đúng chỗ trong `theme-v2.css`, có comment giải thích rõ
  bẫy thứ tự nguồn để không lặp lại.
- **Carousel "Tại sao nên chọn PTS" cắt chữ 2 bên** — JS (`main080f.js`) vẫn
  khởi tạo Slick cho `#CRicos_intro_home` trong khi CSS đã đổi phần này sang
  lưới tĩnh responsive; 2 cơ chế đá nhau khiến width từng slide tính sai theo
  độ dài nội dung. Tắt hẳn 2 khối `InitSlider` liên quan.
- **Popup giỏ hàng lỗi hiển thị trên mobile** (giá lặp 2 lần, header lặp chữ
  "Thành tiền", tràn ngang) — do thiếu 2 dòng CSS ẩn cột trùng khi chuyển logic
  từ `cart.css` (file không được nạp) sang `theme-v2.css`, cộng thêm bộ đếm số
  lượng tràn khỏi cột `col-3` (~89px) vì `input.quantity-procart` khoá cứng
  110px. Đã bổ sung ẩn cột đúng + thu gọn bộ đếm còn 80px.
- **Header giật khi cuộn** — `window.onscroll` gốc không có ngưỡng, chỉ so
  sánh vị trí cuộn liên tiếp; rung nhẹ khi vuốt tay (rất phổ biến trên
  Android) khiến header liên tục lật trạng thái ẩn/hiện, chạy lại transition
  0.5s mỗi lần. Thêm vùng đệm (ẩn khi cuộn xuống vượt 8px tích lũy, luôn hiện
  khi còn trong phạm vi chiều cao header) — cùng nguyên nhân khiến form dài
  hơn cần cuộn trên Android so với giả lập iPhone (header kẹt hiện ăn mất
  ~90-100px màn hình).
- **Nút cuộn-lên-đầu giật** — cùng lỗi thiếu ngưỡng (1 mốc 150px duy nhất);
  thêm khoảng đệm hiện >170px / ẩn <130px.
- **Nút cuộn-lên-đầu đè lên cụm nút liên hệ** — theo yêu cầu chủ project
  ("luôn hiện, không ẩn/hiện theo cuộn, canh 1 cột dọc không đè nhau"): đặt
  nút cuộn-lên-đầu lên TRÊN cụm 3 nút liên hệ (không còn chỗ đặt bên dưới vì
  cụm liên hệ đã sát đáy màn hình), giữ nguyên canh giữa theo cột đã đúng sẵn.
- **Tên công ty ở cuối trang bị che** — dòng "© ... CÔNG TY..." là nội dung
  cuối cùng của trang nên luôn sát mép dưới thật khi cuộn hết; cụm nút liên hệ
  neo cứng cùng mép dưới viewport. Thêm đệm dưới `.footer_bottom` bằng đúng
  chiều cao cụm nút. Phát sinh thêm 1 nguyên nhân phụ: mmenu.js đo chiều cao
  trang lúc khởi tạo (có thể trước khi ảnh/badge cuối trang tải xong) rồi khoá
  cứng vào `.wap-all-page{overflow:clip}` — đo hụt thì phần đệm mới bị cắt.
  Đã bắn lại 1 sự kiện `resize` sau `window.load` để mmenu đo lại đúng.
- **Menu dropdown desktop: 2 icon mũi tên chồng nhau** — mục "Sản phẩm" dùng
  1 span `+`/`-` (`.child-menu`) khác với "Giải pháp"/"Tin tức" vốn đã dùng
  chevron `.has-child` thống nhất; đồng thời phát hiện MỌI mục dropdown (kể
  cả không có con) đều có sẵn 1 mũi tên trái trang trí không điều kiện
  (`style.css:40`). Kết quả cuối theo đúng yêu cầu chủ project: chỉ còn 1
  mũi tên/mục, bên phải, chỉ hiện khi thực sự có danh mục con, dùng lại đúng
  icon Font Awesome màu vàng thương hiệu (không phải ô viền đen 5x5px nhỏ).

### Chưa xác minh — cần bằng chứng thêm, không đoán

- Báo cáo: lưới sản phẩm hiện **2 cột** trên trình giả lập responsive desktop
  (Chrome DevTools resize thủ công, User-Agent vẫn là desktop) nhưng **1 cột**
  trên điện thoại thật (cả Android và iOS). Đã rà toàn bộ:
  - CSS: `.grid-product.grid-product-3` ở mọi breakpoint (`style.css`,
    `theme-v2.css`) đều quy về 2 cột từ ≤768px xuống tới màn hình hẹp nhất,
    không có breakpoint nào thu tiếp xuống 1 cột.
  - JS: không có script nào tính số cột theo `touch`/`pointer`/`userAgent`.
  - PHP: `$deviceType` (Mobile_Detect) được tính trong `router.php` nhưng
    KHÔNG được dùng ở đâu khác — dòng chọn template mobile riêng đã bị comment
    out từ trước, HTML/class trả về giống hệt nhau cho mọi User-Agent.
  - Kết luận: không tìm được cơ chế nào trong code có thể tạo ra khác biệt
    này. Đang chờ ảnh chụp thật từ điện thoại + biết chính xác trang nào
    (`/san-pham/` hay `/danh-muc-san-pham/...`) để đối chiếu, thay vì sửa mò.

## Xác minh

- Mỗi thay đổi đều test local → đối chiếu file giống hệt demo (docker cp +
  diff) → deploy production qua FTP → **đọc lại nội dung thật trên production
  URL** (không chỉ tin exit code của lệnh upload) → Playwright kiểm tra hành
  vi thực tế (không phải chỉ so khớp text tĩnh).
- 1 lần tự gây regression trong phiên: xóa khối `<style>` tưởng dư thừa ở
  footer.php nhưng thực ra là nguồn `!important` duy nhất đang thắng 1 file
  CSS khác — phát hiện và sửa lại trong cùng phiên sau khi chủ project báo lại
  bằng ảnh chụp thật.

## Rollback

- Category restructure + lamp images: SQL production đã lưu trong `backup/`
  (`production_migration_033_20260908.sql`, `production_lamp_images_*.sql`);
  restore theo quy trình phpMyAdmin thông thường nếu cần.
- CSS/JS/PHP template changes: toàn bộ qua Git (`feature/ecommerce-upgrade`,
  commit từ `23e1a66` đến `a37eb17`), revert bằng git rồi FTP lại file cũ.
