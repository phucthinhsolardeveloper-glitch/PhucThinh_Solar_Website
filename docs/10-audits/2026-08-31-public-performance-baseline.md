# Baseline hiệu năng public — 2026-08-31

Phạm vi đo là Docker local PHP 7.4 sau khi warm-up, ba lượt mỗi URL. Kết quả dùng để so sánh regression trong cùng môi trường, không đại diện tốc độ hosting hoặc trải nghiệm trình duyệt ngoài Internet.

| Trang | TTFB quan sát | Tổng thời gian | HTML tải về |
|---|---:|---:|---:|
| Trang chủ | 30–32 ms | 38–40 ms | khoảng 161 KB |
| Sản phẩm | 22–24 ms | 29–33 ms | khoảng 60 KB |
| Giải pháp | 21–22 ms | 27–29 ms | khoảng 67 KB |
| Dự án | 21–27 ms | 27–33 ms | khoảng 63 KB |
| Tin tức | 20–21 ms | 27–28 ms | khoảng 79 KB |
| Giới thiệu | 20 ms | 26 ms | khoảng 80 KB |
| Liên hệ | 20–21 ms | 27 ms | khoảng 72 KB |

Sitemap sau sửa có 137 URL hoạt động đã được xuất bản. Lượt crawl trước sửa phát hiện 4 URL 404 do ba danh mục sản phẩm không bật `hienthi` và module Tuyển dụng chưa hoạt động. Sitemap nay chỉ lấy bản ghi hiển thị; module Tuyển dụng chưa hoạt động không còn được đăng ký để xuất bản trong sitemap, trong khi URL Thư viện ảnh hợp lệ vẫn được giữ.

## Dung lượng cần xử lý thận trọng

- Gói source Git hiện khoảng 183 MB.
- `assets/` có 1.438 file, khoảng 251 MB trước nén.
- `assets/wp-content/uploads/` có 839 file legacy, khoảng 143 MB trước nén.
- Webroot làm việc còn chứa dữ liệu runtime/backup cục bộ rất lớn, nhưng công cụ package đã loại chúng khỏi archive release.

Inventory 137 trang, source và SQL local không tìm thấy tham chiếu tới `assets/wp-content/uploads`; nhánh này được loại khỏi archive release nhưng vẫn giữ nguyên trong repository để rollback/đối chiếu. Việc loại thử làm archive giảm từ khoảng 183 MB xuống khoảng 49 MB. Nội dung public vẫn có 109 đường dẫn riêng dưới `/wp-content/uploads`, vì vậy dữ liệu runtime đó phải được bảo toàn trên hosting. Có 15 JS/CSS dưới `assets/wp-content` đang được HTML sử dụng nên phần còn lại của cây asset chưa được loại.

Phát hiện 41 nhóm trùng nội dung với 85 file trong nhánh upload legacy. Chưa xóa file trong repository. Bước tiếp theo là rà tải CSS/JS thực tế, dependency và lỗi tài nguyên trước khi đề xuất tối ưu tiếp. Mọi thay đổi phải chạy lại pre-deploy và visual smoke.

Inventory HTML cho thấy 76 file CSS/JS local duy nhất trên toàn sitemap; riêng trang chủ tải 75 file, tổng khoảng 3,31 MB trước nén. Tất cả file đều tồn tại. Apache local trả gzip cho CSS kiểm tra (khoảng 88 KB còn 19 KB) và có cache `max-age`. Pre-deploy đã được bổ sung regression cho hai header này. Số dependency global còn cao; chưa gỡ các bản Bootstrap, slider hoặc thư viện upload chồng nhau vì cần dependency map và visual/browser regression trước.

Đợt conditional loading đầu tiên đã giới hạn Comment/FileUploader/MagicZoom vào chi tiết sản phẩm và Photobox vào chi tiết dự án/album. Trang chủ giảm từ 75 xuống 66 CSS/JS local, từ khoảng 3,31 MB xuống 2,96 MB trước gzip (giảm 9 request và khoảng 347 KB). Chi tiết sản phẩm vẫn tải đủ 74 asset cần thiết; chi tiết dự án vẫn tải Photobox. Pre-deploy kiểm tra cả hiện diện ở trang cần dùng và vắng mặt trên trang chủ.

Đợt 2 giới hạn Jssor vào trang chủ và Fancybox vào Video/chi tiết Dự án. Sau hai đợt, trang chủ còn 63 CSS/JS local và khoảng 2,88 MB trước gzip: giảm tổng cộng 12 request và khoảng 428 KB so với baseline. Danh sách Tin tức còn 62 asset/2,82 MB; chi tiết Dự án vẫn tải đủ Fancybox và Photobox. Pre-deploy đạt sau thay đổi.

Đợt 3 giới hạn Owl Carousel vào trang chủ và chi tiết sản phẩm, là hai ngữ cảnh duy nhất tạo carousel hoặc gọi API trả markup carousel. Danh sách Tin tức giảm thêm 3 request, còn 59 asset và khoảng 2,77 MB trước gzip. Trang chủ/chi tiết sản phẩm vẫn giữ Owl và toàn bộ pre-deploy đạt.
