# Shared AI rules — template cho project mới

Dùng file này làm nền cho `AGENTS.md` của project mới; phải chỉnh lại lệnh,
framework, hạ tầng, dữ liệu và quyền thực tế của project đó.

## Quy trình bắt buộc

`INSPECT → UNDERSTAND → PLAN → IMPLEMENT → TEST → DEBUG → VERIFY → DOCUMENT → REPORT`

- Đọc rule/context map trước khi sửa.
- Phân loại task: docs-only, frontend rủi ro thấp, backend/API, data/migration,
  security/infrastructure.
- Chọn cách triển khai nhỏ nhất phù hợp; không chạy kiểm thử/deploy nặng cho
  thay đổi nhỏ, nhưng không né test khi có rủi ro dữ liệu hoặc bảo mật.
- Tác vụ dài phải có checkpoint và log; không lặp lệnh khi chưa có chẩn đoán mới.
- Comment source chỉ ghi rationale kỹ thuật, invariant và giới hạn; không ghi lời
  hội thoại, tên AI hoặc trích dẫn người dùng.

## Backup, disk và container

- Đo trước bằng `df -h`, `du -x` theo thư mục và công cụ `docker system df -v`
  (hoặc công cụ tương ứng).
- Giữ bản backup/release/image hiện tại, trước đó và rollback cần thiết; không
  xóa dữ liệu sống, database volume, upload, secrets hoặc state reference.
- Chỉ prune cache/image không dùng sau khi đối chiếu container và rollback point.
- Không dùng lệnh dọn toàn hệ thống có thể xóa volume production khi chưa có
  kế hoạch khôi phục và phê duyệt rõ ràng.

## Tài liệu và báo cáo

- Không cần ghi docs theo thời gian thực cho mọi thay đổi nhỏ.
- Sau milestone lớn, migration, thay đổi kiến trúc/deploy hoặc đợt bảo trì phải
  rà soát docs và ghi lịch sử bảo trì.
- Báo cáo: kết quả, file/commit, test, trạng thái runtime, backup/cleanup, rủi
  ro còn lại, rollback point và phần chưa làm.

## Skills

Skills phụ thuộc môi trường AI, không tự được cài bằng cách chép Markdown.
Agent phải đọc danh sách skills của môi trường, chỉ dùng skill khi task kích
hoạt đúng mô tả và đọc toàn bộ `SKILL.md` trước khi thao tác. Rule chung đặt ở
`AGENTS.md`; skill-specific instructions giữ trong package skill tương ứng.
