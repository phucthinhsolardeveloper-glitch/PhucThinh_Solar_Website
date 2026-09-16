# Bàn giao nội dung và điều hướng Giải pháp — 2026-09-15

## Kết quả

- Hành trình public chính chỉ còn một landing `/giai-phap/` và năm trang chi
  tiết đi thẳng: điện mặt trời gia đình, điện mặt trời doanh nghiệp, lưu trữ &
  BESS, chiếu sáng mặt trời và bơm nước mặt trời.
- Menu, card và thanh chuyển trang dùng `title_index` làm tên ngắn. Riêng nhãn
  hộ gia đình là **Điện mặt trời gia đình**; H1 và metadata vẫn dùng tiêu đề đầy
  đủ để không làm nghèo nội dung/SEO.
- Breadcrumb bài chi tiết còn đúng ba nấc:
  `Trang chủ › Giải pháp › bài hiện tại`. Các nhóm trung gian cũ không còn xuất
  hiện trong menu, card, switcher hoặc breadcrumb.
- Landing dùng năm card đồng kích thước trên một hàng desktop; tablet/mobile
  chuyển thành dải cuộn ngang thay vì lưới 2 cột có hàng cuối bị khuyết. Card
  là bề mặt phẳng, ảnh/placeholder cùng tỷ lệ 8:5.
- Nội dung dài có nền section riêng và mục lục cố định bên trái trên desktop;
  mục lục được ẩn trên màn hình hẹp để không lặp lại toàn bộ tiêu đề trước bài.
- Nội dung Markdown đã được tách đúng phạm vi public rồi nhập vào CKEditor local:
  một nội dung landing, năm bài dài, metadata SEO và CTA dùng chung. Hero, sản
  phẩm liên quan, CTA/form vẫn do template render nên không bị chép cứng trong
  editor.
- Trước khi nhập, script chuẩn hóa các thuật ngữ khó hiểu sang cách nói hướng
  khách hàng: giải thích bộ biến tần/hybrid/BESS/BMS/EMS/DoD, thay `backup`,
  `load`, `battery`, `solar`, `phụ tải`, `kiến trúc/chiến lược vận hành` bằng
  tiếng Việt rõ nghĩa và giữ thuật ngữ kỹ thuật trong ngoặc khi thật sự cần.
- Bài chiếu sáng cũ được đổi sang slug rõ nghĩa hơn; cả URL bare và URL nested
  cũ đều chuyển 301 đến `/giai-phap/chieu-sang-nang-luong-mat-troi/`.

## Dữ liệu local

- Nguồn: `phuc-thinh-solar-content-giai-phap.md` do chủ project cung cấp.
- Công cụ: `scripts/import-solution-content-local.php`; mặc định chỉ preview,
  chỉ ghi khi có `--apply`, dùng transaction và rollback toàn lượt nếu lỗi.
- Backup trước lần ghi đầu tiên: `backup/local/20260915-144101`.
- Bản ghi local sau import: landing `#906`; năm giải pháp `#907`, `#61`, `#908`,
  `#60`, `#909`; CTA `#910`.
- Không sửa hoặc nhập dữ liệu production.

## File chính

- Điều hướng/route: `sources/allpage.php`, `sources/news.php`,
  `libraries/product_category_urls.php`, `libraries/solution_hub.php`.
- Dữ liệu/render: `sources/solution_hub.php`, `templates/news/service_list_tpl.php`,
  `templates/news/service_detail_tpl.php`, `templates/layout/menu.php`,
  `templates/layout/mmenu.php`, `templates/layout/seo.php`.
- Admin/import: `libraries/type/config-type-news.php`,
  `scripts/import-solution-content-local.php`.
- Chống ảnh Tin tức lặp: `libraries/news_detail_media.php`,
  `templates/news/news_detail_tpl.php`.
- Regression: `tools/visual-tests/tests/solution-hub.spec.js`.

## Kiểm thử

- Script import lint trên PHP 7.4: đạt; preview và apply lặp lại an toàn trên
  dữ liệu local.
- HTTP: landing và bài gia đình trả 200; bài gia đình có một H1, bảy H2 nội dung
  CKEditor và breadcrumb ba nấc.
- Playwright `solution-hub.spec.js`: 10/10 đạt trên desktop và mobile; kiểm tra đủ
  năm nhãn ngắn, link trực tiếp, không tràn ngang, modal tư vấn, canonical và
  bốn redirect legacy.
- Đã chụp và kiểm tra trực quan landing cùng bài gia đình ở viewport desktop.
- Đối chiếu ảnh cho thấy sáu bài Tin tức có ảnh cover và ảnh CKEditor cùng hình
  nhưng khác tên do resize. Template chỉ ẩn cover ở đúng các trường hợp đó;
  bài có hai ảnh khác nhau vẫn hiển thị đủ.
- Card landing dùng thumbnail 600×375: đạt cả kiểm tra không phóng mờ và không tải
  dư trên desktop; card thiếu ảnh dùng placeholder có chủ đích.

## Deploy

1. Chưa deploy khi chưa có phê duyệt riêng cho production.
2. Backup DB/upload production và diễn tập restore theo runbook hiện hữu.
3. Deploy code, chạy migration quyền liên quan ở chế độ preview rồi mới apply.
4. Chạy import nội dung ở preview với đúng file nguồn; đối chiếu sáu trang và
   chỉ dùng `--apply` sau khi chủ project duyệt dữ liệu production.
5. Xóa cache, smoke test năm canonical, redirect cũ, menu desktop/mobile và
   kiểm tra lại metadata trước khi mở traffic bình thường.

## Rollback

- Code: revert commit của đợt triển khai.
- Dữ liệu local: khôi phục backup `backup/local/20260915-144101` nếu cần quay về
  đúng trạng thái trước import. Việc restore sẽ ghi đè DB nên chỉ thực hiện khi
  chủ project yêu cầu rõ ràng.
- Production: dùng đúng backup được tạo ngay trước deploy; không dùng backup
  local và không xóa thủ công các record/redirect khi chưa đối chiếu.

## Rủi ro còn lại

- Ba giải pháp mới chưa có ảnh thật nên template dùng placeholder số; cần bổ
  sung ảnh ứng dụng xác minh được trong Admin, không dùng ảnh AI làm bằng chứng.
- Cần chủ project duyệt lần cuối câu chữ pháp lý/kỹ thuật trước khi nhập lên
  production. Nội dung cũ khẳng định FIT/tiết kiệm tuyệt đối đã không được giữ.
