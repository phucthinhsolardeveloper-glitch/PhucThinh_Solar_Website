# Giả lập VNPay, Nhất Tín và hộp thư cho bản thử nghiệm

Chỉ dùng trên máy local, cạnh stack ứng viên (`deploy/laravel13`). Không có cổng thanh toán, vận đơn
hay hộp mail thật nào được gọi. Không bao giờ dùng cho server.

| Thành phần | Địa chỉ trên máy | Việc |
|---|---|---|
| `sim` (`server.mjs`) | `http://127.0.0.1:18086` | Trang VNPay giả (ký HMAC-SHA512 như VNPay, gọi IPN rồi trả khách về), API Nhất Tín giả (đăng nhập, cước, tạo/hủy, hành trình, webhook) |
| `mailpit` | `http://127.0.0.1:18025` | Hộp thư bắt mọi mail SMTP của bản thử |

## Bật

1. `runtime/sim.env` giữ khóa ngẫu nhiên của giả lập; `runtime/backend.env` dùng đúng các khóa đó
   (`VNPAY_*`, `MAIL_HOST=mailpit`, `NTL_MODE=sandbox`, `NTL_SANDBOX_URL=http://sim:8080/ntl`, `NTL_*`).
   Bản trước khi bật nằm ở `runtime/backend.env.before-sim`.
2. Trong `deploy/laravel13`:
   `docker compose --env-file runtime/stack.env -f docker-compose.yml -f docker-compose.sim.yml up -d`
3. Tắt: chép lại `backend.env.before-sim` thành `backend.env`, rồi
   `docker compose --env-file runtime/stack.env -f docker-compose.yml -f docker-compose.sim.yml rm -sf sim mailpit`
   và `up -d` stack thường.

## Kết quả 2026-11-10 (ứng viên `96ee9684`)

- Nhất Tín qua HTTP: 11/11 bước đạt (báo giá, tạo, chặn tạo trùng, token hết hạn được cấp lại, webhook có token,
  hủy, push trễ không đổi trạng thái đã hủy, timeout → "Chưa rõ kết quả" → đối chiếu → tạo lại mã `-2`).
- VNPay: thành công (IPN `00`, về `/cam-on/`), báo về 2 lần (vẫn 1 giao dịch, 1 bộ mail), thất bại `51`, khách hủy `24`
  (đơn giữ "chưa thanh toán"). Mail khách và mail quản trị đi qua worker → SMTP → Mailpit.
- Phát hiện: `MAIL_ADMIN_TO` trống thì không có mail báo đơn cho cửa hàng; `verify-candidate.sh` chặn khi dùng SMTP
  và Admin → Tích hợp báo thiếu. Phải khai trước khi chuyển hệ thống.
- Cửa hàng nhận tối đa 5 đơn/IP/10 phút; chạy kịch bản VNPay cách nhau 10 phút.

## Thử

- Thanh toán: đặt đơn chọn VNPay trên `http://127.0.0.1:18085`, trang giả cho chọn thành công, thành công
  mà VNPay báo về 2 lần, thất bại, hoặc khách hủy.
- Nhất Tín: Admin → đơn hàng → Vận chuyển. Lỗi một lần cho lần gọi kế tiếp:
  `curl -XPOST 127.0.0.1:18086/_sim/ntl/next -d '{"fail":"timeout"}'` (hoặc `401`, `500`).
  Đẩy trạng thái: `curl -XPOST 127.0.0.1:18086/_sim/ntl/push -d '{"bill_code":"SIM…","status_id":4,"status_name":"Đã giao"}'`.
- Nhật ký các lần gọi: `curl 127.0.0.1:18086/_sim/log`. Mail: mở `http://127.0.0.1:18025`.
- Kịch bản tự động: VNPay + mail `cd tools/visual-tests && node audit/sandbox-rehearsal.cjs`; Nhất Tín
  `docker cp ../../tools/sandbox-sim/ntl-rehearsal.php pts-laravel13-backend:/tmp/ && docker compose --env-file runtime/stack.env exec -T backend php /tmp/ntl-rehearsal.php`
  (docker.exe qua WSL không chuyển stdin, nên chép file vào trước).
  (tự xóa đơn thử, vận đơn và tài khoản tạm).
