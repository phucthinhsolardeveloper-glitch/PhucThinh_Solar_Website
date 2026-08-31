# Rà soát HTTP hardening local — 2026-08-31

## Phạm vi

Kiểm tra `.htaccess`, response header và khả năng truy cập file nhạy cảm trên Docker local Apache/PHP 7.4. Chưa áp dụng hoặc xác minh trên hosting.

## Phát hiện

- File log của file manager legacy trả HTTP 200.
- Document root chứa bản SQL và gói ZIP bàn giao; image local không đóng gói hai file này nhưng hosting cần lớp chặn độc lập.
- Response PHP công khai phiên bản PHP qua `X-Powered-By`.
- PHP động bị gán cache trình duyệt bảy ngày, không phù hợp nội dung có session, giỏ hàng và form CSRF.
- Chưa có các header phòng vệ cơ bản cho MIME sniffing, framing, referrer và quyền thiết bị.

## Xử lý

- Chặn truy cập trực tiếp file SQL, log, backup, cấu hình và gói ZIP bàn giao.
- Không xóa hoặc di chuyển file hiện có.
- Bỏ `X-Powered-By`; thêm `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` và `Permissions-Policy`.
- Chuyển PHP động sang `no-store, no-cache, must-revalidate`.
- Thêm regression HTTP vào cổng pre-deploy local.

## Việc còn lại trên staging/hosting

- Xác minh các directive `.htaccess` được hosting cho phép và header không bị proxy/CDN ghi đè.
- Cấu hình HSTS chỉ sau khi HTTPS và toàn bộ subdomain đã sẵn sàng.
- Di chuyển dump, archive và log ra ngoài document root trong cửa sổ triển khai được phê duyệt.
- Kiểm tra CSP ở chế độ report-only trước khi enforce vì giao diện legacy còn inline script/style.
