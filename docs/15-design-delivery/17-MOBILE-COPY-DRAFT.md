# Quyết định về rút gọn nội dung CMS

Trạng thái: **đã hủy phần rút gọn nội dung** theo quyết định của chủ website ngày 2026-11-07. Bản rút gọn đã được hoàn tác ở local và chưa từng áp lên production. Không chạy lại.

## Nguyên tắc

Đầy đủ, rõ ràng và minh bạch được ưu tiên hơn việc ít chữ. Giao diện không được đánh đổi thông tin mà khách cần để quyết định.

- Không cắt khỏi nội dung: thời hạn và cách đối chiếu bảo hành, điều kiện giao nhận, giá và tồn kho, hóa đơn chứng từ, phạm vi dịch vụ, cam kết của công ty.
- Khi trang dài, ưu tiên trình bày dễ đọc: tách đoạn trên điện thoại (`ChunkedText`) và mở rộng theo nút (`data-fold`). Hai cách này giữ nguyên từng chữ và người đọc vẫn mở được.
- Chỉ bỏ phần lặp đúng nguyên văn hoặc đúng ý trên cùng màn hình. Chính sách và cam kết luôn cần chủ website duyệt, kể cả khi chỉ sửa câu chữ.
- Script sửa nội dung phải được kiểm tra trên dữ liệu production bằng truy vấn chỉ đọc trước khi trình duyệt. Dữ liệu local không đại diện cho production.

## Vì sao hủy

- Bản rút gọn bỏ thời hạn bảo hành, điều kiện giao nhận và cam kết tư vấn, lắp đặt, hậu mãi.
- Script khớp 292 sản phẩm ở local nhưng chỉ 2 trên 109 sản phẩm có chính sách riêng ở production. Nếu chạy, chính sách sẽ không đồng nhất.

## Vẫn giữ trong code

| Vị trí | Thay đổi | Lý do |
|---|---|---|
| Trang chủ, tiêu đề dự phòng của khối giới thiệu | "Về Phúc Thịnh Solar" | Chỉ dùng khi CMS trống, phần mô tả bên dưới vẫn nêu đủ lĩnh vực |
| Trang chủ, khối đại lý | "Đăng ký đại lý" | Chỉ đổi kiểu chữ hoa |

Câu trên trang Liên hệ đã trả về bản đầy đủ: "Trao đổi cùng đội ngũ Phúc Thịnh Solar để chọn cấu hình phù hợp cho gia đình, cửa hàng hoặc công trình."
