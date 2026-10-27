# Agent workflow

- Bắt đầu bằng read-only discovery; tách fact khỏi inference và xác định
  worktree/owner trước khi sửa.
- Ghi `UNKNOWN`/`NEEDS VERIFICATION` thay vì đoán; không refactor trong
  discovery và không sửa live.
- Trước edit, nêu file/behavior/data/acceptance/negative path/test/rollback.
- Dùng prepared SQL, preserve routes, tránh secret/PII và duplicate ownership.
- Sau edit, kiểm tra diff, phân loại failure và cập nhật canonical docs.

## Điều phối agent và bằng chứng

Khi task có điều phối Codex/Claude/agent khác, ghi ba mốc riêng:

- `DISPATCHED`: đã có lệnh/phiên khởi chạy;
- `EXECUTED`: có log/đầu ra xác nhận agent thực sự làm việc;
- `VERIFIED`: đã kiểm exit code, diff, test và ancestry/integration nếu có.

Không gọi lại agent khi chưa kiểm PID/session/log. Không báo `DONE` chỉ từ
message thành công của agent; coordinator phải tự kiểm chứng. Nếu task không
yêu cầu điều phối, không tự tạo bằng chứng giả cho các mốc này.

## Format handoff bắt buộc

```text
DONE:
VERIFIED:
REMAINING:
KNOWN ISSUES:
RELEVANT FILES:
NEXT:
```

Báo cáo tiếng Việt, ngắn, có command/exit code/artifact quan trọng và production
state. Tách rõ điều đã làm, điều chưa chạy vì môi trường/quyền và điều cần chủ
project quyết định.
