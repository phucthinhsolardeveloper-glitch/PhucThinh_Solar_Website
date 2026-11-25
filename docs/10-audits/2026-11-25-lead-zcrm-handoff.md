# Chuyển lead website sang ZCRM

Trạng thái: **phía website đã viết, mặc định tắt; phía CRM chưa có.** Chưa tạo khóa API, chưa gọi CRM nào, chỉ thử với endpoint giả trong test.
Căn cứ: đọc `zalo-crm-solar` tại commit `19f2f344` (`backend/src/modules/api/`): chưa có `POST /api/public/leads` và phạm vi `leads.write`. Chưa kiểm CRM đang chạy.

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

Phía website (`CrmLeadForwarding`, dùng bảng `outbox_messages`, không thêm migration):

- Sau khi lead lưu xong, xếp một thông điệp `crm_lead`, khóa chống trùng `crm:lead:<id>`. Lỗi CRM không làm hỏng form.
- Bật bằng `CRM_LEADS_ENABLED`, `CRM_LEADS_URL` (HTTPS), `CRM_LEADS_API_KEY` trong `runtime/backend.env`; mặc định tắt. Đổi xong phải tạo lại backend, worker, scheduler.
- Thân gửi: `externalId`, `fullName`, `phone`, `email`, `need`, `sourcePath` (không kèm query), `kind`, `createdAt`; trường trống bị bỏ. Không gửi IP, nội dung tin nhắn, giỏ hàng hay mã click quảng cáo.
- 2xx: đã gửi. 408/425/429/5xx hoặc mất kết nối: thử lại tối đa 6 lần. Mã khác (kể cả chuyển hướng, 401, 403, 422): dừng, đánh dấu thất bại. Không lưu nội dung CRM trả về.
- Thất bại hiện ở "Việc cần làm" trong Admin và `php artisan pts:outbox`, tách khỏi lỗi email.
- Tắt công tắc: thông điệp đang chờ chuyển `cancelled`. Lead tạo khi đang tắt không được gửi bù.
- Chưa có: nút gửi lại cho thông điệp thất bại và gửi bù lead cũ.

Chiều ngược lại (chốt / mất, giá trị đơn) để giai đoạn sau; hiện Admin lead chỉ có Mới / Đã liên hệ / Đã xong / Rác nên chưa đo được lead → doanh thu.

## Chủ website cần quyết

CRM đích và tổ chức nhận; ai nhận lead mới; cách xử lý khách trùng; có lưu mã click quảng cáo khi khách từ chối cookie hay không.

## Phụ thuộc để bật được

1. CRM có `POST /api/public/leads` đúng hợp đồng trên (idempotent theo `externalId`, phạm vi `leads.write`).
2. Khóa API chỉ có phạm vi `leads.write`, do người quản trị CRM cấp.
3. Chủ website chốt các mục ở "Chủ website cần quyết".

## Kiểm thử

Đã có (`CrmLeadForwardingTest`, endpoint giả): tắt/thiếu cấu hình; gửi một lần; CRM lỗi 429/5xx/mất kết nối; bị từ chối 301/401/403/422; tắt khi đang chờ.
Còn lại, cần CRM thử: gửi lặp cùng `externalId`, gộp số ở ba cách viết, khóa sai phạm vi. Không ghi CRM thật.
