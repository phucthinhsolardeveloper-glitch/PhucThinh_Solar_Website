# Shared operating contract

Áp dụng cho developer, Claude Code và Codex.

```text
INSPECT -> UNDERSTAND -> PLAN -> IMPLEMENT -> TEST -> DEBUG -> VERIFY -> DOCUMENT -> REPORT
```

Project được kế thừa. Không mặc định hành vi hiện có là chủ ý. Trước thay đổi đáng kể phải đọc route/source/template/API, schema/config liên quan, xác định dữ liệu bị tác động, test và rollback.

## Quy tắc

- Không sửa production hoặc import đè DB nếu chưa được phép rõ ràng.
- Không xóa code/upload/cache/legacy asset trước khi xác minh.
- Không đưa credential, token, password hash hoặc PII vào tài liệu/chat.
- Không cleanup/refactor ngoài phạm vi task.
- SQL mới phải parameterized; form mutation phải kiểm tra session/quyền và CSRF.
- Upload phải kiểm tra extension, MIME, size, filename và lưu ở nơi không thực thi PHP.
- Giữ URL/slug public trừ khi có kế hoạch redirect/SEO.
- Test file thay đổi bằng đúng PHP mục tiêu; commerce smoke test theo category -> product -> cart -> checkout -> confirmation.
- Cập nhật docs nếu implementation chứng minh tài liệu sai.

Báo cáo cho chủ project bằng tiếng Việt: kết quả, file thay đổi, test, rủi ro còn lại, deploy và rollback.
