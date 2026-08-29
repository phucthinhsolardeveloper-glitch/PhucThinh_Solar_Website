# Documentation changelog

## 2026-08-26

- Tạo documentation bootstrap từ source/schema/backup/runtime evidence.
- Thêm fact map, architecture, data/API/auth/security/operations/features/integrations, audit và AI operating rules.
- Không thay đổi PHP, database hoặc production behavior.

# 2026-08-29

- Chuyển thông số kỹ thuật sản phẩm từ bảng HTML sang 19 trường chuẩn và danh sách thông số riêng.
- Admin và Excel import/export nhận từng thông số theo ô; frontend dựng lại bảng từ dữ liệu cấu trúc.
- Thêm migration dry-run/apply, không xóa nội dung HTML cũ.
- Chuẩn hóa parser thông số giữa CRUD form và import; giữ đủ dữ liệu legacy trùng nhãn trên frontend/export.
- Bulk commit dùng transaction kèm rollback thủ công cho database engine legacy; bổ sung test XLS, CSV, `__CLEAR__`, fault rollback và fallback HTML cũ.
- Thêm migration 006 dry-run để chuyển nhãn đồng nghĩa vào trường chuẩn và đổi tên nhãn trùng mà không mất giá trị.
- Tối giản trải nghiệm Excel: chỉ mã/tên bắt buộc khi tạo mới, tô màu cột theo vai trò và viết lại sheet hướng dẫn ngắn gọn về ô trống, dữ liệu tự động, `__CLEAR__` và trạng thái website.
