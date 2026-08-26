# Backup and restore

Trạng thái: PARTIAL. Bản bàn giao chứa DirectAdmin archive/database/config/mail metadata, nhưng không chứng minh job tự động, retention, encryption, off-site copy hoặc restore test.

Runbook cần được nhà cung cấp xác nhận: owner, lịch, phạm vi DB/upload/mail, nơi lưu, retention, encryption, restore RTO/RPO. Restore rehearsal chỉ chạy trên staging: dựng account sạch, import DB, restore upload, cập nhật config/domain, kiểm tra login/content/product/order/email và ghi thời gian/kết quả.
