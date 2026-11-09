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

## Thử

- Thanh toán: đặt đơn chọn VNPay trên `http://127.0.0.1:18085`, trang giả cho chọn thành công, thành công
  mà VNPay báo về 2 lần, thất bại, hoặc khách hủy.
- Nhất Tín: Admin → đơn hàng → Vận chuyển. Lỗi một lần cho lần gọi kế tiếp:
  `curl -XPOST 127.0.0.1:18086/_sim/ntl/next -d '{"fail":"timeout"}'` (hoặc `401`, `500`).
  Đẩy trạng thái: `curl -XPOST 127.0.0.1:18086/_sim/ntl/push -d '{"bill_code":"SIM…","status_id":4,"status_name":"Đã giao"}'`.
- Nhật ký các lần gọi: `curl 127.0.0.1:18086/_sim/log`. Mail: mở `http://127.0.0.1:18025`.
- Kịch bản tự động: VNPay + mail `cd tools/visual-tests && node audit/sandbox-rehearsal.cjs`; Nhất Tín
  `docker compose --env-file runtime/stack.env -f docker-compose.yml -f docker-compose.sim.yml exec -T backend php < ../../tools/sandbox-sim/ntl-rehearsal.php`
  (tự xóa đơn thử, vận đơn và tài khoản tạm).
