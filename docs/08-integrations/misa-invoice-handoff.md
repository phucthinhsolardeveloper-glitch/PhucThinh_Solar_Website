# Bàn giao đơn hàng sang hóa đơn điện tử MISA

Trạng thái 26/11/2026: **thiết kế, chưa nối**. Website không phát hành hóa đơn, không gọi API MISA, không tạo chứng từ nào gọi là hóa đơn.
Phiếu đơn hàng (PDF) chỉ ghi nhận đơn đặt, không thay hóa đơn giá trị gia tăng hay chứng từ thanh toán.

## Quy trình tay, dùng được ngay

1. Sales xác nhận đơn, thống nhất phí vận chuyển và việc khách có lấy hóa đơn công ty hay không.
2. Khách lấy hóa đơn: sales hỏi tên đơn vị, mã số thuế, địa chỉ đăng ký, email nhận hóa đơn; ghi vào "Ghi chú nội bộ" của đơn.
3. Khi đến thời điểm lập hóa đơn do kế toán quy định, vào đơn trong Admin, bấm "Tải Excel" và chuyển cho kế toán.
4. Kế toán đối chiếu và phát hành trong MISA, rồi ghi số và ký hiệu hóa đơn vào ghi chú đơn.

## Dữ liệu đơn hiện có và còn thiếu

| Cần cho hóa đơn (kế toán xác nhận danh sách bắt buộc) | Trên website |
|---|---|
| Mã đơn, ngày đặt | Có: `orders.code`, `created_at` |
| Người mua, điện thoại, email, địa chỉ giao | Có: `orders.customer_name`, `phone`, `email`, `address` |
| Tên đơn vị, mã số thuế, địa chỉ xuất hóa đơn | **Không thu thập** (`tax_code` ở bảng tỉnh/xã là mã địa bàn, không phải của khách) |
| Tên hàng, số lượng, đơn giá | Có: `order_items` |
| Mã hàng | Qua sản phẩm hiện tại, có trong phiếu đơn hàng lưu lúc đặt |
| Đơn vị tính | **Không lưu theo dòng đơn** |
| Thuế suất, tiền thuế, giá đã/chưa gồm thuế | **Không lưu**; một số trang sản phẩm ghi "giá đã gồm VAT" |
| Phí vận chuyển và thuế của phí | Có số phí; 0 đang được hiểu là "chờ xác nhận" |
| Trạng thái thanh toán | Có: `orders.payment_status` |

## Nếu sau này nối API

Chỉ làm khi: kế toán chốt thời điểm lập, thuế suất theo mặt hàng, đơn vị tính; công ty có tài khoản và tài liệu API của gói MISA đang dùng; chủ website cho phép.

- Một đơn tối đa một hóa đơn; khóa chống lặp theo mã đơn, kiểm trạng thái bên MISA trước khi gửi lại.
- Người có quyền bấm tạo **nháp**; không tự phát hành, không chạy theo sự kiện thanh toán.
- Điều chỉnh, thay thế, hủy hóa đơn làm trong MISA, không làm từ website.
- Khóa API chỉ ở biến môi trường máy chủ; log chỉ ghi mã đơn, loại và mã lỗi.
- Thử trên môi trường thử của MISA trước.
