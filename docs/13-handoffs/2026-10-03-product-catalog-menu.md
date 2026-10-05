# Mega-menu danh mục sản phẩm — triển khai local 03/10/2026

## Chỉnh desktop theo mẫu 04/10/2026

Panel desktop hiện rộng khoảng 960px và chỉ hiển thị đúng 4 lối tắt Chiếu sáng,
4 Điện mặt trời, 2 Thiết bị ứng dụng, cùng ô hỗ trợ ở cột cuối. Không còn các
dòng category con chen dưới lối tắt. Các slot được nhận diện qua `slugvi` ổn
định, còn URL đích lấy từ category đang hiển thị trong Admin; slot không tồn
tại sẽ không render. Các nhóm khác vẫn truy cập qua landing và menu mobile.
Chân panel giữ `Xem tất cả sản phẩm` và thay vị trí ngôn ngữ trong mẫu bằng
`Tư vấn chọn sản phẩm` mở form hiện hữu. Search lọc 10 lối tắt bằng cả tên
category con, Enter vẫn dùng route tìm kiếm hiện tại.

Visual QA: `design-qa.md`. Test header: 13 passed, 7 skipped. Chỉ đổi local,
không đổi DB, không deploy.

## Bổ sung mobile 04/10/2026

Theo ảnh đối chiếu mới, drawer mobile được đổi từ panel trượt sang accordion
inline khi người dùng bấm, không tự bung nhóm con; thêm icon, nút tư vấn cố định ở đáy và bỏ
bộ chọn ngôn ngữ trong drawer. Các đoạn bên dưới mô tả hành vi mobile cũ
trước thay đổi này. Taxonomy và URL vẫn từ Admin, nên số nhóm thực tế không
bị rút xuống bằng ảnh mẫu. Đã kiểm thử browser 320/390/768px, đóng/mở,
đường dẫn, tìm kiếm và CTA; bản mới có ảnh/test và kết quả trong `design-qa.md`.
Các screenshot 03/10 phía dưới là bản cũ, không dùng làm bằng chứng cho bản mới.

## Kết quả

- Nút Danh mục sản phẩm desktop mở overlay bằng hover trên thiết bị có chuột,
  và vẫn mở được bằng click/keyboard; độ trễ đóng ngắn cho phép rê từ nút vào
  panel mà không làm menu chớp/tắt. Ba cụm gồm Chiếu sáng, Điện mặt trời,
  Thiết bị ứng dụng; panel có tìm kiếm, đóng bằng nút/vùng nền/Escape, và lối
  tới toàn bộ sản phẩm cùng nhận tư vấn.
- Dữ liệu nhóm sản phẩm, danh mục con và URL lấy từ các danh mục đang hiển thị
  có sản phẩm; không thay đổi schema hoặc dữ liệu Admin.
- Mobile giữ Mmenu và cây URL đang có, thêm ô tìm sản phẩm gửi vào route search
  hiện hành; nút đóng nằm trong drawer ở trang gốc và submenu. Giữ nguyên năm
  nhóm điều hướng và các landing/URL public. Tên danh mục dài tự xuống dòng;
  đóng từ submenu reset về panel gốc để lần mở sau có lại ô tìm kiếm.
- Cụm danh mục desktop được suy ra từ tên nhóm hiện tại; nhóm chưa nhận diện
  được rơi về Thiết bị ứng dụng. Nếu quản trị đổi tên taxonomy lớn, cần kiểm
  tra lại rule nhóm hoặc bổ sung trường nhóm do Admin quản lý.

## File triển khai

- domains/phucthinhsolar.com/public_html/templates/layout/header.php
- domains/phucthinhsolar.com/public_html/templates/layout/menu.php
- domains/phucthinhsolar.com/public_html/templates/layout/mmenu.php
- domains/phucthinhsolar.com/public_html/assets/css/header-v3.css
- domains/phucthinhsolar.com/public_html/assets/js/apps.js
- tools/visual-tests/tests/header-v3.spec.js
- docs/02-development/public-ui-ux-design-system.md
- design-qa.md

## Kiểm tra

- PHP 7.4 lint: header.php, menu.php, mmenu.php — pass.
- node --check assets/js/apps.js — pass.
- git diff --check — pass.
- Trình duyệt local: desktop 1440px mở overlay, hiển thị đủ 3 nhóm, tìm/lọc
  danh mục, giới hạn focus, Escape đóng và trả focus; không tràn ngang.
- Hover local: đã phát hiện menu trước đó không mở khi rê chuột; sau chỉnh sửa,
  hover mở mà không kéo focus bất ngờ, rê chậm qua khoảng cách vào panel vẫn giữ
  menu mở, rê khỏi panel đóng trễ; click và bàn phím vẫn hoạt động.
- Mobile 390×844: drawer mở, tìm kiếm từ menu gửi đúng
  /tim-kiem?keyword=...; nút đóng trong drawer hoạt động từ panel gốc và
  submenu, lần mở sau trở về root; nhãn dài xuống dòng, không tràn ngang.
- `pnpm test -- tests/header-v3.spec.js`: 10 passed, 4 skipped (các test dành
  riêng cho desktop/mobile được skip theo project). `node --check` và
  `git diff --check` pass; browser console không có error/warning.
- Đối chiếu ảnh mobile: local dùng Mmenu drill-down theo taxonomy, mockup thể
  hiện danh mục mở inline dạng accordion. Đây là khác biệt có chủ đích để giữ
  cấu trúc nhiều cấp hiện hành; search nằm ở panel gốc.
- Screenshot audit hiện tại: `.playwright-mcp/menu-audit-20261003-02-desktop-hover-final.png`,
  `.playwright-mcp/menu-audit-20261003-03-mobile-root-final.png`,
  `.playwright-mcp/menu-audit-20261003-05-mobile-products.png` và
  `.playwright-mcp/menu-audit-20261003-06-mobile-subcategories.png`.
- Chỉ kiểm tra accessibility cơ bản qua semantics/focus/keyboard; chưa chạy
  screen reader hoặc đánh giá WCAG đầy đủ. Browser screenshot tự dịch sang English.

## Production / rollback

- Chỉ thay đổi local; không có deploy, commit, push, migration, DB import,
  backup hoặc cleanup production trong tác vụ này.
- Rollback local bằng cách khôi phục riêng các file triển khai ở trên; không có
  dữ liệu cần phục hồi.
- Trạng thái class.Functions.php thay đổi ngoài phạm vi được giữ nguyên, không
  stage hoặc sửa trong task này.
