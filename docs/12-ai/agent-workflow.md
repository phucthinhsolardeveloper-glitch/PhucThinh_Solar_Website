# Agent workflow

- Bắt đầu bằng read-only discovery; tách fact khỏi inference.
- Ghi `UNKNOWN`/`NEEDS VERIFICATION` thay vì đoán.
- Không refactor trong discovery; không sửa live.
- Trước edit, nêu file/behavior/data/test/rollback.
- Dùng prepared SQL, preserve routes, tránh secret/PII.
- Sau edit, kiểm tra diff và cập nhật canonical docs.
- Báo cáo tiếng Việt, ngắn và có evidence.
