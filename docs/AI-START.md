# Bắt đầu cho AI — hợp đồng tiếp quản và làm song song

## 1. Đọc tối thiểu
- [CURRENT.md](CURRENT.md): release gần nhất, trạng thái và việc tiếp theo.
- [Sổ tay chủ project](SO-TAY-CHU-PROJECT.md): bản đồ nghiệp vụ → UI → backend → test, tài liệu chuyên sâu.
- [README gốc](../README.md), AGENTS.md/CLAUDE.md ở root: rule, commands và invariant. Code/schema/test hiện hành là bằng chứng kỹ thuật, không lấy handoff cũ làm truth.

## 2. Trước khi sửa
Chạy git status --short, git branch --show-current, git log -5 --oneline, git worktree list. Xác định target branch/base SHA, task và người/agent đang sở hữu phạm vi file. Task nhỏ không cần thêm worktree nếu không xung đột; task song song phải tách worktree/branch, thống nhất phạm vi và acceptance. Chỉ đọc đúng docs, code và test cần thiết. Không reset, checkout đè, xóa nhánh/WIP, deploy hoặc dùng dữ liệu thật ngoài quyền được giao.

## 3. Trong khi làm
Một agent sở hữu một phạm vi thay đổi tại một thời điểm. Nếu chạm file giao nhau hoặc thay đổi API/schema chung, dừng thay đổi giao nhau và thống nhất với coordinator. Dùng Git/worktree và cơ chế điều phối hiện có; không dựng thêm task queue. Không đưa token/password/PII vào prompt, handoff hoặc log.

## 4. Bàn giao ngắn (ghi vào task/handoff đang dùng, không sinh file mới mặc định)
- Goal + acceptance / repo, branch, base SHA, owner.
- Files changed + behavior + test commands/results (phân biệt chạy thật và đề xuất).
- Remaining blockers / next action / migration and rollback risks.
- Trạng thái riêng: implementation, review, merge, deployment; dẫn chứng Git/release.
- Docs canonical đã cập nhật / chưa cần đổi và lý do.

## 5. Nghiệm thu và tích hợp
Review diff theo scope, kiểm thử đúng rủi ro, xác minh ancestry khi merge. Người điều phối xác nhận không đè WIP. Tính năng chưa có bằng chứng production thì không được báo đã deploy. Cập nhật docs nghiệp vụ và trạng thái trong cùng PR/commit; không sao chép nội dung vào AGENTS, CLAUDE hay nhiều handoff.

Xem [quy tắc bảo trì tài liệu](DOCUMENTATION.md).
