# Checkout write path — Laravel hiện hành

Source 5c35860f, 26/11/2026. [Shop.php](../../backend/app/Domain/Commerce/Shop.php), [OrderLifecycle.php](../../backend/app/Domain/Commerce/OrderLifecycle.php), [ManualPayment.php](../../backend/app/Domain/Commerce/ManualPayment.php).

1. Shop::placeOrder xác minh input/cart/checkout token; transaction ghi đơn, dòng hàng và payment liên quan. Tìm persistedOrder và finishExistingOrder khi điều tra retry. Đọc đầy đủ điều kiện validate trước khi sửa.
2. BankTransfer::issue tạo chỉ dẫn chuyển khoản; việc có QR không chứng minh tiền đã về.
3. Shop::reportTransfer kiểm token của session/trang cảm ơn, TTL, chỉ cập nhật transfer_reported_at khi đơn unpaid và chưa báo trước; rung chuông Admin khi thay đổi. Không chuyển paid.
4. ManualPayment::confirm đòi quyền orders_payment, mã tham chiếu không rỗng tối đa 120 ký tự; khóa đơn trong transaction, chỉ manual/unpaid. Nếu truyền số tiền và lệch tổng đơn thì từ chối. Ghi payment transaction, actor và chuyển paid qua OrderLifecycle. Kế toán phải đối chiếu sao kê trước khi xác nhận; không lấy ảnh chụp làm chứng từ tiền về. Amount hiện nullable trong service; kiểm form/action trước khi kết luận bắt buộc nhập.
5. ManualPayment::recordRefund ghi nhận hoàn tiền đã thực hiện ở ngoài hệ thống, không tự chuyển tiền. VNPay callback là đường riêng, không đồng nhất với manual. Kiểm cấu hình live trước khi nói có tự động đối soát.
6. OrderLifecycle là điểm kiểm state transition. Nhất Tín thuộc Domain/Shipping. Xuất/in đơn thuộc OrderExporter, OrderSheet, route web.

Bảng liên quan orders/order_items/payment_instructions/payment_transactions/order_documents/admin_audit_events; xem migrations. Test local: BankTransferTest, AuditAndLifecycleTest và các feature test checkout. Kiểm retry, người không có quyền, số tiền lệch, token hết hạn, hoàn tiền lặp. Không tạo đơn thật khi kiểm tra tài liệu.

Lịch sử MyISAM: git show 5c35860f:docs/03-data/checkout-write-path.md.
