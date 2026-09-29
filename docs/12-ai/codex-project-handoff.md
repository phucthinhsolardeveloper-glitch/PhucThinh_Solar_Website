# Bàn giao quy trình cho Codex/AI ở project khác

Tài liệu này dùng khi chuyển cách làm việc sang một project khác, ví dụ
Zalo-CRM. Không chép nguyên các đường dẫn, domain, schema hoặc thông tin
production của Phúc Thịnh Solar sang project mới.

## Gói rule nên đưa cho Codex mới

### Bắt buộc

1. `AGENTS.md` của project mới: đây là rule có hiệu lực cao nhất trong repo.
2. Một đoạn **Shared AI workflow** được điều chỉnh theo project mới, gồm:
   - inspect → understand → plan → implement → test → debug → verify → document → report;
   - phân loại thay đổi trước khi chạy: docs-only, frontend/low-risk, backend,
     data/migration, security/infrastructure;
   - chọn cách triển khai nhỏ nhất phù hợp, không chạy SAFE nặng cho thay đổi nhỏ;
   - tác vụ dài phải có checkpoint, không lặp lệnh hoặc hỏi lại output agent tự đọc được;
   - cleanup phải đo trước, giữ rollback point và không xóa dữ liệu sống;
   - sau milestone lớn/bảo trì phải rà soát và cập nhật docs.
3. Quy tắc báo cáo: kết quả, file/commit, test, production state, rủi ro,
   rollback và phần chưa làm; viết bằng ngôn ngữ chủ project sử dụng.

### Nên đưa nếu project có vận hành production

- Chính sách backup/retention/release/image và runbook rollback.
- Quy tắc dọn disk: `df`, `du`, công cụ container cache/image; cấm dọn mù
  volume/database/upload/secrets.
- Nhật ký deploy/bảo trì và lịch rà soát docs định kỳ.
- Ma trận mode triển khai tương ứng với rủi ro của project (không bắt buộc dùng
  tên `AUTO/FAST/SAFE`).

## Skills cần đưa thế nào

Skills là capability cài trong môi trường Codex, không phải kiến thức tự động
đi theo repository. Với Codex làm Zalo-CRM:

- Đọc danh sách skills khả dụng trong môi trường đó.
- Chỉ chọn skill khi task kích hoạt đúng mô tả của skill; đọc toàn bộ `SKILL.md`
  trước khi làm.
- Không chép nguyên skill UI/deploy của project này sang Zalo-CRM nếu task không
  cần; skill phải phù hợp framework, hosting và quyền của project mới.
- Nếu cần một skill dùng chung, cài skill vào môi trường Codex mới hoặc đưa phần
  **rule/workflow** trung tính vào `AGENTS.md`; không giả vờ rằng một file docs đã
  cài được skill.

## Tài liệu nên gửi kèm cho Codex Zalo-CRM

Gửi theo thứ tự:

1. `AGENTS.md` đã rút gọn theo rule của Zalo-CRM.
2. `docs/12-ai/codex-project-handoff.md` này để hiểu cách bàn giao.
3. Context map/README của Zalo-CRM.
4. Runbook deploy, backup, migration, rollback và maintenance của Zalo-CRM.
5. Tài liệu UI/design system hoặc security chỉ khi task liên quan.

Không cần gửi toàn bộ docs Phúc Thịnh Solar. Những file như
`vps-operations.md`, `deployment-log.md` và `owner-system-handbook.md` chỉ nên
dùng làm **mẫu cấu trúc**, sau đó viết lại theo hạ tầng và dữ liệu của CRM.

## Prompt khởi tạo ngắn

```text
Đọc AGENTS.md và context map của repo trước khi làm.
Tuân thủ inspect -> understand -> plan -> implement -> test -> debug -> verify
-> document -> report. Phân loại rủi ro trước khi chọn cách triển khai; tác vụ
dài phải có checkpoint; cleanup phải đo trước và giữ rollback. Không chép lời
hội thoại vào comment source. Sau milestone lớn hoặc bảo trì, cập nhật docs và
lịch sử bảo trì. Báo cáo ngắn gọn bằng tiếng Việt, nêu rõ test, rủi ro và rollback.
``` 
