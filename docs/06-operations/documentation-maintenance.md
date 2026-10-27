# Lịch rà soát và bảo trì tài liệu

Tài liệu là một phần của sản phẩm vận hành. Không cần ghi mọi thay đổi nhỏ theo
thời gian thực, nhưng phải cập nhật khi kiến thức đã trở thành quy tắc, workflow,
kiến trúc hoặc hành vi ổn định của project.

## Nhịp rà soát

- **Sau milestone lớn:** rà soát ngay khi hoàn tất tính năng lớn, migration,
  thay đổi schema, thay đổi deploy/rollback, bảo mật, backup hoặc cấu trúc thư mục.
- **Hàng tháng:** rà soát nhanh runbook vận hành, route/chức năng chính, mode
  deploy, retention backup/release/image, timer và các liên kết tài liệu.
- **Hàng quý:** rà soát sâu toàn bộ docs liên quan đến kiến trúc, quyền hạn,
  dữ liệu, restore/rollback và checklist release; đánh dấu tài liệu lỗi thời,
  gộp tài liệu trùng và cập nhật owner/assumption.
- **Trước release lớn:** không đóng release nếu docs vẫn mô tả workflow cũ hoặc
  thiếu hướng dẫn rollback/khôi phục tương ứng.

## Cách thực hiện

1. Đọc thay đổi thực tế trong code, script, schema/config và output vận hành.
2. Tìm các docs có lệnh, đường dẫn, tên bảng, mode deploy hoặc giả định liên quan.
3. Cập nhật runbook/README trước; ghi quyết định và kiến thức mới vào lịch sử.
4. Chạy `git diff --check`, kiểm tra link/path quan trọng và ghi rủi ro còn lại.

Không đưa credential, token, PII hoặc log chứa dữ liệu nhạy cảm vào docs.

Các file trong `docs/13-handoffs/`, audit có ngày tháng và các mục lịch sử trong
`docs/00-project/status.md` được giữ để truy nguyên quyết định cũ. Khi rà soát,
không sửa ngược sự kiện lịch sử; nếu trạng thái hiện tại khác, thêm mục hiện tại
và liên kết về runbook đang có hiệu lực.

## Lịch sử rà soát

### 2026-10-27 — chuẩn hóa workflow bằng evidence và handoff

- Bổ sung discovery/ownership, acceptance negative path, verification ladder và
  phân loại failure vào workflow website.
- Chuẩn hóa bằng chứng điều phối agent `DISPATCHED/EXECUTED/VERIFIED` và format
  handoff `DONE/VERIFIED/REMAINING/KNOWN ISSUES/RELEVANT FILES/NEXT`.
- Tách build/regression khỏi browser, integration, security và UAT; không dùng
  một lớp kiểm tra để thay cho lớp khác.

### 2026-10-04 — tối ưu tiếp public và Admin local

- Bổ sung số đo bốn route public, nguyên nhân bundle thừa Jssor/Bootstrap 4.0,
  ảnh banner đầu lazy và nút lưu Admin chờ `window.load` vào
  [audit hiệu năng](../10-audits/2026-10-03-website-admin-performance.md).
- Chỉ loại asset được xác minh không dùng hoặc bị ghi đè; giữ hai bản Bootstrap
  còn lại cho đến khi kiểm chứng đầy đủ tương thích Popper/dropdown.

### 2026-10-03 — đo hiệu năng website và Admin local

- Ghi baseline trình duyệt, truy vấn Admin, nguyên nhân ảnh gốc ở danh sách
  sản phẩm, tải thư viện theo màn và hiệu ứng đóng mega-menu tại
  [audit hiệu năng](../10-audits/2026-10-03-website-admin-performance.md).
- Xác nhận bài học từ đợt tách asset công khai trước: chỉ tối ưu theo route
  sau khi có regression tương tác và visual; giữ các bundle hiện hành cho
  những route chưa được kiểm chứng.

### 2026-09-29 — retention, backup và deploy workflow

- Ghi nhận vai trò của `incoming/`, `releases/`, `shared/` và chính sách giữ 1–2
  backup ứng dụng gần nhất.
- Phân biệt backup ứng dụng với backup/snapshot VPS CloudFly.
- Ghi quy tắc dọn Docker build cache/image an toàn và rollback candidate.
- Bổ sung `AUTO` deploy, deployment log và quy trình rà soát định kỳ.
- Chuẩn hóa quy tắc phối hợp dùng chung trong `AGENTS.md`: phân loại rủi ro,
  checkpoint tác vụ dài, cleanup có kiểm soát và format báo cáo để agent khác
  không phải thiết lập lại quy trình.
- Rà soát comment source: loại bỏ lời hội thoại, câu trích dẫn và cách kể lại
  phản hồi cá nhân; giữ lại rationale kỹ thuật, invariant và mốc kiểm chứng.
- Thêm tài liệu bàn giao quy trình cho AI ở project khác tại
  `docs/12-ai/codex-project-handoff.md`; phân biệt rõ rule có thể chia sẻ với
  skill phụ thuộc môi trường.
