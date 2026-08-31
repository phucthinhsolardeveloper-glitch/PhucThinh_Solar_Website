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

Chưa xóa hoặc di chuyển asset legacy vì nội dung database có thể tham chiếu đường dẫn động. Bước tiếp theo là lập inventory URL asset thực tế từ HTML và nội dung database, sau đó mới đề xuất tách hoặc loại file không dùng. Mọi thay đổi phải chạy lại pre-deploy và visual smoke.
