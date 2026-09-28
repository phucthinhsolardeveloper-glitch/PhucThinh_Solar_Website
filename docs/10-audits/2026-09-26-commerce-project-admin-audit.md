# Rà soát Dự án và checkout — 2026-09-26

## Đã tinh gọn an toàn

- Form Admin loại `du-an` không còn bắt nhập tay `Địa chỉ` và `Ngày hợp đồng`.
  Admin chọn Tỉnh/TP, Phường/Xã, địa chỉ chi tiết tùy chọn, ngày bắt đầu và
  ngày hoàn thành; hệ thống tự ghép chuỗi `address` public để giữ tương thích.
- Địa chỉ mới chỉ dùng hai cấp Tỉnh/TP → Phường/Xã. Đơn mới ghi
  `district = 0`; backend kiểm tra Phường/Xã thuộc đúng Tỉnh/TP.
- Checkout giữ COD và chuyển khoản hiện có; VNPay chỉ xuất hiện khi đủ cấu
  hình. Bảng phí phường/xã hiện tại được dùng làm fallback duy nhất khi chưa
  có quote thật từ hãng vận chuyển.
- Không xóa cột legacy, route, asset hoặc dữ liệu cũ: chúng còn được đọc bởi
  dự án/đơn lịch sử và cần kế hoạch redirect/migration riêng nếu bỏ hẳn.

## Tính năng giữ lại có lý do

- `address`, `ngayhopdong`, `district` và các API huyện cũ được giữ để đọc dữ
  liệu cũ hoặc phục vụ màn hình legacy; không dùng trong form dự án/checkout
  mới.
- CSS/JS hoặc asset WooCommerce/legacy chỉ được dọn sau khi có call-site và
  smoke test chứng minh không còn dùng. Rà soát hiện tại chưa đủ căn cứ để xóa
  hàng loạt.

## Còn chờ credential/mapping

- Quote/tạo vận đơn thật của GHN, GHTK, Viettel Post và Nhất Tín cần credential,
  kho gửi, mã địa chỉ của từng hãng, dịch vụ, cân nặng/kích thước kiện và quy
  tắc COD. Khung `commerceShippingQuote()` và endpoint
  `api/shipping_quote.php` đã thống nhất contract; adapter từng hãng sẽ nối vào
  đó sau khi nhận đủ dữ liệu, không tự đoán bảng giá.
- Migration 058/059 phải được chạy ở staging trước production; production chưa
  được thay đổi trong đợt này.
