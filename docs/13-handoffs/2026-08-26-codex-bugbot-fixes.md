# Handoff 2026-08-26 — Sửa finding Bugbot trước khi chạy quảng cáo

Phạm vi: Docker local, chưa thay đổi production.

## Kết quả

Đã xử lý 4/4 finding từ Bugbot:

1. Bật module quản lý đơn hàng trong admin (`$config['order']['active'] = true`).
2. Đồng bộ card sản phẩm trả về từ AJAX filter: có badge giảm giá, nút **Mua ngay** và **Thêm giỏ hàng** như card tải ban đầu.
3. Trang cảm ơn dùng token ngẫu nhiên gắn với session thay cho mã đơn trên URL. Conversion `order_success` chỉ phát một lần; URL chia sẻ hoặc token không hợp lệ không lộ thông tin đơn.
4. Review sản phẩm yêu cầu mã đơn + số điện thoại + email trùng với đơn có chứa sản phẩm. Review hợp lệ được gắn trạng thái `verified-purchase` và hiển thị nhãn **Đã mua hàng**.

## File thay đổi

- `domains/phucthinhsolar.com/public_html/libraries/config-type.php`
- `domains/phucthinhsolar.com/public_html/api/fillter_service.php`
- `domains/phucthinhsolar.com/public_html/sources/order.php`
- `domains/phucthinhsolar.com/public_html/sources/thankyou.php`
- `domains/phucthinhsolar.com/public_html/templates/order/thanks_tpl.php`
- `domains/phucthinhsolar.com/public_html/libraries/class/class.Comments.php`
- `domains/phucthinhsolar.com/public_html/templates/product/comment.php`
- `domains/phucthinhsolar.com/public_html/libraries/sample/comment/customer/lists.php`

## Kiểm thử local

- PHP 7.4 lint: đạt trên toàn bộ 8 file PHP thay đổi.
- Danh mục sản phẩm: HTTP 200.
- Chi tiết sản phẩm: HTTP 200.
- AJAX filter: còn đủ badge giảm giá, Mua ngay, Thêm giỏ hàng.
- Review sai thông tin đơn: bị từ chối.
- Review đúng mã đơn, điện thoại và email: được lưu với `verified-purchase`.
- Trang cảm ơn tải hai lần trong cùng session: chi tiết đơn vẫn hiển thị, event conversion chỉ phát lần đầu.
- Dữ liệu đơn/review giả dùng cho test đã được xóa khỏi database Docker local.

## Deploy và rollback

Chưa được phép triển khai production. Khi deploy phải backup code + DB, chạy migration đã nêu trong handoff Claude, kiểm tra extension `calendar`, cấu hình SMTP và thực hiện smoke test category -> product -> cart -> checkout -> confirmation -> admin order.

Rollback code bằng revert commit chứa thay đổi này. Thay đổi này không thêm schema database mới.

## Rủi ro còn lại

- SMTP production chưa được xác minh/sửa cấu hình.
- Extension `calendar` trên hosting production chưa được xác minh.
- Cần Security Review riêng trước production, đặc biệt các controller admin cũ, CSRF và upload.
- Chưa làm video/mô tả mở rộng, giấy chứng nhận; nội dung dự án/chính sách vẫn cần dữ liệu thật.
- 42 sản phẩm chưa có công suất chuẩn hóa cần rà soát thủ công.
