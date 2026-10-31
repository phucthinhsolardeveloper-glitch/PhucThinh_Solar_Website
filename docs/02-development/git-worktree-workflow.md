# Git và worktree workflow

Tài liệu này là quy tắc làm việc song song trong repository. `AGENTS.md`, chỉ
đạo mới nhất của chủ project và quyền an toàn vẫn có ưu tiên cao hơn.

## 1. Trước khi tạo hoặc dùng worktree

Chạy read-only từ repository root:

```bash
git status --short --branch
git branch -vv
git worktree list --porcelain
git log -5 --oneline --decorate
```

Xác định bằng evidence:

- branch/worktree canonical của task và base SHA;
- owner, file scope và worktree đang giữ file đó;
- WIP/untracked/ignored state của mọi worktree liên quan;
- integration target thật từ docs/remote/branch map, không đoán theo tên.

Trong checkout hiện tại, `master` là nhánh tích hợp của mã PHP và frontend Nuxt
đã được chấp nhận. Production vẫn chạy PHP cho tới khi có task cutover/deploy
riêng. Không lấy một nhánh tiến độ hoặc checkout cũ làm baseline thay cho
`master`; xác minh target theo task và trạng thái remote hiện tại.

## 2. Ownership và isolation

- Một task/owner một branch/worktree; task song song phải có file scope không
  chồng lấn hoặc có người duyệt integration rõ ràng.
- Worktree mới tạo ở sibling path phù hợp; không checkout branch đang được
  worktree khác sử dụng.
- Không sửa trực tiếp worktree của agent khác, không stage cả repository, không
  `git add -A` khi có WIP không thuộc task.
- Trước khi sửa, ghi branch, path, base SHA, owner, file scope, acceptance, test
  và rollback point trong task/handoff.

## 3. Bảo vệ WIP

- Worktree bẩn hoặc có file chưa theo dõi thuộc người khác là dữ liệu cần giữ.
- Không `reset --hard`, `clean`, `checkout`, `stash`, force-delete branch hoặc
  `git worktree remove --force` để làm sạch workspace.
- Nếu cần đổi branch/path nhưng có WIP, dừng và báo blocker; chỉ di chuyển,
  commit hoặc xóa sau khi chủ sở hữu xác nhận rõ.
- Không gộp nguyên working tree; chỉ stage đúng file thuộc task.

## 4. Từ task tới integration

1. Tạo branch/worktree từ base SHA đã xác minh.
2. Implement diff nhỏ, test trong chính worktree đó, ghi artifact/exit code.
3. Commit với scope rõ; review `git diff`, secret/PII và file ngoài phạm vi.
4. Merge/cherry-pick/PR vào đúng target theo chỉ đạo; không báo “đã gộp” chỉ
   vì file đã được copy hoặc agent đã báo thành công.
5. Xác minh integration bằng ancestry/diff/test:

   ```bash
   git merge-base --is-ancestor <feature-sha> <target-branch>
   git diff --stat <target-branch>...<feature-branch>
   ```

   Nếu target đã tiến lên, kiểm tra lại conflict và chạy test trên kết quả đã
   merge. `PROMOTED`/copy receipt, screenshot hoặc cùng nội dung file không thay
   thế commit ancestry evidence.

## 5. Kết thúc và cleanup

Chỉ dọn branch/worktree sau khi:

- commit đã được integrate đúng target và có ancestry evidence;
- test/verification của kết quả integrate đã pass hoặc known issue đã được ghi;
- worktree không còn WIP/untracked cần giữ;
- owner xác nhận không còn phiên/process dài đang dùng path.

Worktree host-managed hoặc chứa WIP không thuộc agent hiện tại thì để nguyên.
Không xóa upload, cache, DB volume, backup, secrets hoặc state reference trong
quá trình cleanup.

Sau khi merge đã được xác minh, có thể xóa nhánh task local bằng `git branch -d`
khi branch đã là ancestor của target và không còn worktree dùng nó. Giữ các
nhánh `backup/*`; việc dọn local không xóa nhánh trên remote.

## 6. Handoff tối thiểu

```text
DONE:
VERIFIED:
REMAINING:
KNOWN ISSUES:
BRANCH/WORKTREE:
BASE/TARGET/ANCESTRY:
RELEVANT FILES:
TESTS/ARTIFACTS:
ROLLBACK:
NEXT:
```
