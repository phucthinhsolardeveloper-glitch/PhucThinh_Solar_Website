# Chuyển lead website sang ZCRM — thiết kế (chưa triển khai)

Trạng thái: **chỉ là thiết kế.** Không có code gửi, không tạo khóa API, không ghi vào CRM.
Căn cứ: đọc `zalo-crm-solar` tại commit `19f2f344` (`backend/src/modules/api/`). Chưa kiểm CRM đang chạy.

## Interface đang có

- Xác thực `X-Api-Key` (`zcrm_…`), quyền theo phạm vi: `contacts.read`, `contacts.write`, …
- `POST /api/public/contacts`: nhận `fullName, phone, email, source, status, notes, tags`.
- `GET /api/public/contacts?search=`: tìm `phone contains`.
- `PUT /api/public/contacts/:id`: ghi đè các trường được gửi, gồm `notes`.
- Model `Contact` có `phoneNormalized` (dạng `84…`) và `metadata` JSON.

## Vì sao chưa nối thẳng vào interface này

1. `POST` không kiểm trùng số và không có khóa chống gửi lặp: mỗi lần thử lại tạo thêm một khách.
2. Tìm theo `phone contains` so trên số thô; số lưu dạng `+84…` hoặc có dấu cách sẽ không khớp `0…`.
3. `PUT` ghi đè `notes`, có thể xóa ghi chú sales đã viết.
4. `contacts.read` cho phép đọc toàn bộ khách; website chỉ cần quyền gửi lead.

## Đề xuất

Phía CRM (một endpoint mới, phạm vi mới `leads.write`):

`POST /api/public/leads` với `{ externalId: "pts-web:<lead.id>", fullName, phone, email?, need, sourcePath, kind, createdAt }`

- Idempotent theo `externalId`: gửi lại trả cùng bản ghi.
- Gộp theo `phoneNormalized` trong cùng tổ chức. Khách đã có: thêm một hoạt động "lead từ website", không đổi người phụ trách, trạng thái hay ghi chú.
- Trả `{ contactId, created }`; không trả dữ liệu khách khác.

Phía website:

- Gửi qua hàng đợi sau khi lead đã lưu (cùng cách Outbox đang làm cho mail: có khóa chống trùng và thử lại). Lỗi CRM không được làm hỏng form.
- Công tắc mặc định tắt; khóa API nằm trong cấu hình môi trường, không nằm trong repo hay Admin export.
- Số điện thoại đã được chuẩn hóa 10 chữ số từ bản sửa form.
- Chỉ gửi trường cần để sales gọi lại. Không gửi IP, nội dung giỏ ngoài phần khách đã gửi, hay mã click quảng cáo khi chủ website chưa quyết chính sách đồng ý cookie.

Chiều ngược lại (chốt / mất, giá trị đơn) để giai đoạn sau; hiện Admin lead chỉ có Mới / Đã liên hệ / Đã xong / Rác nên chưa đo được lead → doanh thu.

## Chủ website cần quyết

CRM đích và tổ chức nhận; ai nhận lead mới; cách xử lý khách trùng; có lưu mã click quảng cáo khi khách từ chối cookie hay không.

## Kiểm thử khi triển khai

Gửi lặp cùng `externalId`; trùng số ở ba cách viết; CRM lỗi 500/timeout; khóa sai phạm vi; chỉ dùng CRM thử, không ghi CRM thật.
