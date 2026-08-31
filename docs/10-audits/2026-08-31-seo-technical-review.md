# Rà soát SEO kỹ thuật local — 2026-08-31

## Phạm vi

Kiểm tra metadata, canonical, robots và sitemap trên Docker local PHP 7.4. Chưa xác minh redirect, HTTPS hoặc index thực tế trên hosting.

## Kết quả

- Sửa metadata Twitter bị khai báo nhầm thành các thẻ Open Graph trùng lặp.
- Canonical loại bỏ query string của bộ lọc, sắp xếp và tìm kiếm.
- Trang tìm kiếm, giỏ hàng, cảm ơn, tài khoản và chuyển ngôn ngữ dùng `noindex,nofollow`.
- `robots.txt` khai báo sitemap production và tiếp tục chặn `/administrator/`.
- `sitemap.xml` render XML hợp lệ từ dữ liệu local.
- Cổng pre-deploy kiểm tra tự động các điều kiện trên.

## Kiểm thử

- PHP 7.4 lint đạt cho template metadata và lớp URL.
- Build Docker sạch và toàn bộ pre-deploy regression đạt.
- HTTP smoke đạt cho trang sản phẩm có query, tìm kiếm, giỏ hàng, robots và sitemap.
- Không có PHP warning, notice, fatal hoặc parse error trong log kiểm thử.

## Việc còn lại trên staging/hosting

- Xác minh HTTP sang HTTPS, www/non-www và URL `index.php` đều redirect 301 về một host chuẩn.
- Crawl toàn site để tìm liên kết hỏng, redirect chain, canonical sai host và metadata thiếu.
- Gửi sitemap production vào Google Search Console sau khi nội dung thật được duyệt.
