# VNPay và review media: đóng hai lỗi correctness P0

Ngày 2026-11-09. Phạm vi: nhánh `migration/laravel13-integration`, DB test và
candidate local; không gọi VNPay thật hoặc thay production.

## VNPay

- Đơn manual được tính conversion khi đã lưu/tiếp nhận. Đơn VNPay chỉ được tính
  sau khi trạng thái DB thành `paid`; lần đọc trang cảm ơn khi còn pending hoặc
  failed không tiêu thụ quyền ghi nhận conversion.
- Hai callback return/IPN có thể đến bất kỳ thứ tự nào. Khóa hàng order bảo đảm
  chỉ lần chuyển `unpaid → paid` gửi mail đơn hàng. Gửi lại form giữ cùng order
  và token cảm ơn; callback lặp không gửi mail hay tạo conversion mới.
- Chỉ coi thanh toán thành công khi cả `vnp_ResponseCode` lẫn
  `vnp_TransactionStatus` bằng `00`, sau khi đã xác minh chữ ký, order và số tiền.
  Đồng thời đối chiếu mã terminal khi cấu hình. Đây là điều kiện theo
  [tài liệu tích hợp PAY của VNPay](https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html).
- Trang `/cam-on/` hiển thị trạng thái chờ/thất bại đúng với giao dịch và không
  hiện bước giao hàng khi chưa được chấp nhận/thanh toán.

Mail hiện vẫn được gửi đồng bộ sau commit của giao dịch DB. Nếu tiến trình chết
giữa commit và gửi mail, thông báo có thể mất; nếu SMTP lỗi, callback không tự
gửi lại để tránh mail trùng. Reliability Core tiếp theo phải thêm outbox bền vững
và database queue cho order/lead, với monitor/retry có giới hạn. Không gọi cơ chế
hiện tại là bảo đảm exactly-once ở nhà cung cấp mail.

## Review media

- Ảnh JPEG/PNG: kiểm extension, MIME, nội dung ảnh, tối đa 30 MB và 40 MP; tối
  đa 3 ảnh. Video MP4/MKV: kiểm extension, MIME, dấu hiệu container và giới hạn
  100 MB; video luôn cần poster hợp lệ. Kiểm box `ftyp`/`moov`/`mdat` ở MP4 mà
  không nạp toàn file vào RAM. Chưa kiểm codec/playback bằng ffprobe.
- Tệp được ghi vào `.review-staging/<batch>` không public trước. Sau đó transaction
  DB chuyển file tới `photo/` hoặc `video/` và ghi quan hệ media. Nếu lỗi bất kỳ,
  transaction rollback, file đã chuyển cùng staging được dọn; không ghi `path=''`.
  Nếu process bị kill đột ngột, staging ẩn có thể còn lại; cần dọn theo tuổi khi
  triển khai media lifecycle sau này.
- PHP local và candidate dùng `upload_max_filesize=100M`, `post_max_size=384M`
  để phù hợp review video và batch upload sản phẩm; giới hạn từng loại file vẫn
  được kiểm trong application.

## Bằng chứng và phần chưa làm

`ShopFlowTest` kiểm pending/failed → không conversion/mail, paid → mail 2 người
nhận một lần, callback lặp, duplicate checkout và transaction status sai.
`ReviewSubmissionTest` kiểm file giả, video/poster hợp lệ và lỗi DB sau khi đã
chuyển file; sau lỗi không còn row review hoặc file public/staging. Hai luồng
được chạy với Mail fake và DB test; không có giao dịch VNPay production.

Gate còn lại trước cutover: staging VPS với VNPay và mail
sandbox, restore/rollback qua Caddy. Điểm rollback code trước đợt sửa:
`d296b187`; image candidate cũ giữ theo `49dbf3dc-candidate` (backend) và
`967315c0-candidate` (Nuxt).
