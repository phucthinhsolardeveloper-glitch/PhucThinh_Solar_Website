# Chi tiết sản phẩm v3 — 2026-09-23

## Kết quả

Trang chi tiết sản phẩm giữ nguyên URL, dữ liệu sản phẩm, giỏ hàng và backend
newsletter hiện có, nhưng chuyển phần nội dung dài sang luồng dọc phục vụ quyết
định mua: gallery + buy box, điều hướng mục, mô tả, hướng dẫn lựa chọn từ thông
số thật, bảng thông số, bảo hành, FAQ theo field kỹ thuật, đánh giá, form tư vấn
trong trang và nội dung liên quan.

- Ảnh chính/gallery dùng khung 1:1 và `object-fit: contain`; ảnh thiếu có fallback
  từ gallery mà không ghi lại DB.
- Sản phẩm có giá giữ số lượng, `Mua ngay` và `Thêm giỏ hàng`; sản phẩm báo giá
  dùng CTA cuộn đến `#pts-pdp-consultation` và nút gọi tư vấn.
- Video chỉ render với URL YouTube hợp lệ; bảo hành chỉ lấy nội dung riêng hoặc
  chính sách có tên liên quan đến bảo hành. Không dựng section rỗng.
- Khối ứng dụng có thể dùng ảnh thật từ block `media` trong phần nội dung Admin
  (tỷ lệ 8:5, `cover`); khi chỉ có danh sách chữ thì giữ visual biểu tượng an
  toàn. Không dùng ảnh AI/stock làm bằng chứng ứng dụng.
- Form tư vấn giữ CSRF, reCAPTCHA và tên field newsletter hiện hành; tên sản phẩm
  được điền sẵn vào nhu cầu.
- PDP tự sở hữu H1 tên sản phẩm và chặn H1 SEO ẩn của layout, nên HTML chỉ còn
  đúng một H1.
- Thứ tự nội dung mobile/desktop thống nhất với design system; rail điều hướng và
  bảng thông số vẫn dùng được ở mobile, không gây tràn ngang.
- Điều hướng mục desktop nằm giữa như mockup, cao 42px kể cả khi sticky, dùng
  vạch ngăn ngắn và gạch vàng đi theo section đang đọc; mobile cao 40px, canh
  đầu và cuộn ngang. Thanh không kế thừa nền chip xám của catalog.
- Carousel sản phẩm liên quan không tự chạy để người dùng đọc và thao tác ổn
  định; vẫn giữ kéo chuột/vuốt và nút điều hướng.

## File chính

- `sources/product.php`: chuẩn bị dữ liệu content, gallery, dự án liên quan và
  khai báo PDP có H1 nhìn thấy.
- `templates/product/product_detail_tpl.php`: markup gallery/buy box, luồng nội
  dung, form tư vấn, đánh giá và nội dung liên quan.
- `assets/css/product-detail-v2.css`: giao diện PDP, responsive và trạng thái
  control; không dùng gradient cho khối nhu cầu.
- `tools/visual-tests/tests/layout-interactions.spec.js`: kiểm tra một H1, gallery,
  CTA báo giá, thứ tự nội dung và chống overflow mobile.
- `tools/visual-tests/tests/visual-baseline.spec.js-snapshots/product-detail-desktop-chromium-linux.png`:
  baseline buy box desktop mới.

## Kiểm tra local

- PHP 7.4 lint `sources/product.php` và `product_detail_tpl.php`: PASS.
- `git diff --check` và `node --check` test Playwright: PASS.
- Playwright PDP desktop/mobile: 5 PASS, 1 SKIP đúng điều kiện mobile-only.
- Playwright buy box mobile: 1 PASS.
- Playwright cart/checkout desktop + mobile: 3 PASS, 3 SKIP đúng breakpoint.
- Baseline ảnh PDP desktop: PASS sau khi cập nhật ảnh chuẩn.
- Test content block, structured data và ưu tiên ảnh sản phẩm: PASS.
- HTML local: HTTP render không có Warning/Notice/Fatal và chỉ có một H1.

## Rủi ro, deploy và rollback

- Chưa gửi form thật vì local reCAPTCHA/email không đại diện production; mới xác
  minh markup, CSRF và control. Chưa kiểm tra trên thiết bị iOS/Android thật.
- Chưa thêm khu tải catalogue/PDF như ảnh tham chiếu vì hệ thống chưa có field
  tài liệu sản phẩm và upload riêng được kiểm soát; không tạo link/file giả.
- Chưa deploy production và không thay đổi DB. Trước deploy cần backup code, chạy
  predeploy đầy đủ và smoke test `category -> product -> cart -> checkout ->
  confirmation` trên staging/local tương đương.
- Rollback presentation bằng cách hoàn nguyên template, stylesheet, test/baseline
  và cờ H1 trong source; không xóa upload hoặc dữ liệu Admin.
