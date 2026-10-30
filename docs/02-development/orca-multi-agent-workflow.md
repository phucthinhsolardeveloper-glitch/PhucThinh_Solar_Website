# Orca + Git Worktree — Quy trình phát triển multi-agent

> Trạng thái: đề xuất chuẩn hoá 2026-10-30. `AGENTS.md` và các quy tắc bảo mật, deploy hiện có vẫn có hiệu lực. Tài liệu này giải thích vận hành hàng ngày, không tự cấp quyền deploy.

## 1. Mô hình tinh gọn

```text
Yêu cầu -> Task (owner + file scope + DoD)
                  |
        Orca Worktree riêng (khi CÓ ghi code song song)
                  |
     Agent chính -> sub-agent (read-only mặc định)
                  |
       HMR + focused checks + git diff
                  |
        commit -> review/integration
                  |
       main/master local -> release gate -> VPS
```

- **Repo** chứa lịch sử; **branch** là chuỗi commit; **worktree** là checkout file riêng; **tab/pane** là giao diện; **agent session** là phiên AI; **sub-agent** không mặc định có worktree; **Orca worker** chỉ tồn tại khi được orchestration quản lý.
- Tạo **tab** hoặc **agent** trong cùng worktree không tạo nhánh và không cô lập file.
- Chỉ cần một agent cho thay đổi nhỏ. Tách worktree khi từ hai agent trở lên cùng **ghi code**, muốn thử các phương án độc lập, hoặc cần baseline riêng.
- Các agent chỉ đọc có thể cùng checkout; với tác vụ sửa cần một **owner duy nhất cho mỗi phạm vi file**. Sub-agent chỉ được ghi nếu agent chính đã phân vùng ownership, không cùng chỉnh một file.

## 2. Luật trước khi dispatch

1. Xác nhận project/repo, base branch và base SHA thực tế (`git status --short --branch`, `git log -1 --oneline`).
2. Xem `orca worktree ps` và `git worktree list`. Không suy luận từ tên tab, tên thư mục hoặc danh sách agent.
3. Kiểm tra WIP chưa commit và người đang sở hữu. Không reset, stash, cherry-pick, commit hoặc merge thay đổi chưa nhận dạng.
4. Định nghĩa task: mục tiêu, owner, đường dẫn worktree, branch, file scope, base SHA, test, tiêu chí DONE và các file cấm sửa.
5. Ghi task và giao vào **worktree cụ thể**. Không đưa task sửa feature mới vào checkout `main/master`.
6. Đối với checkout mới: trong Orca dùng `worktree create`, **không** chỉ `agent launch` trong checkout tích hợp. Chọn `--base-branch` có chủ đích; Git HEAD commit không bao gồm WIP chưa commit.
7. Trước khi agent viết, chạy `bash scripts/dev/agent-preflight.sh` trong thư mục worktree. Lỗi chặn phải giải quyết rồi mới phát triển.

Ví dụ dành cho người điều phối (thay repo, tên task, base theo từng dự án):
```bash
orca worktree create --repo id:<REPO_ID> \
  --name task-pdp --branch feature/task-pdp \
  --base-branch master --setup skip --no-parent --json
# Sau đó chọn worktree vừa tạo và khởi chạy agent tại đúng đó.
orca worktree ps
git worktree list
```

## 3. Kỷ luật phát triển và tài nguyên

- UI: `npm run dev`/HMR để preview, không Docker rebuild sau mỗi chỉnh CSS/Vue.
- Backend/database: dùng runtime local chính khi phù hợp. Worktree không cô lập database, Docker volume, secrets, port hay quyền VPS.
- Mỗi dev server cần port riêng hoặc chỉ chạy một preview ở một thời điểm. Không cho agent tự khởi tạo DB mới/volume production.
- Các bước kiểm tra dựa vào rủi ro: UI nhỏ -> preview/focused check; API -> affected tests; auth/schema -> kiểm tra tích hợp phù hợp; full build và smoke release -> checkpoint.
- Không dùng `git add .` tùy tiện khi checkout có WIP của nhiều owner. Stage đúng file scope và review `git diff --cached`.
- Đơn vị commit = thay đổi có ý nghĩa, không phải một vòng sửa giao diện. Push khi cần PR/bàn giao; không phải sau mọi lần save.
- Không dùng `--dangerously-bypass-approvals-and-sandbox` hoặc `--dangerously-skip-permissions` làm mặc định cho agent sửa code; review quyền trước khi triển khai trên máy.
- Không cho agent điều khiển deploy, production config, migration hay secret khi task chỉ yêu cầu UI hoặc tài liệu.

## 4. Handoff bắt buộc khi agent hoàn thành

```text
TASK:
OWNER / AGENT:
WORKTREE (absolute path) / BRANCH:
BASE SHA / FINAL SHA:
FILE CHANGED (scope):
TEST RUN / RESULT / NOT RUN:
RISK / UNVERIFIED:
READY FOR REVIEW: yes/no
PRODUCTION CHANGED: no (nếu có, nêu đúng chứng cứ và quyền)
```

Integrator kiểm tra diff, ghi nhận WIP, tích hợp **một lane một lần**, kiểm tra theo rủi ro và xác nhận merge ở commit nào. Không coi “agent báo xong”, “build pass” hoặc “tab hoàn tất” là đã triển khai. Đừng merge hoặc cleanup cho đến khi xác minh task thực sự có mặt trên nhánh tích hợp.

## 5. Bốn tình huống phải chọn đúng cách làm

| Tình huống | Mô hình |
| --- | --- |
| Một sửa UI nhỏ | 1 agent, feature worktree khi chưa có lane riêng; HMR |
| Nhiều agent đọc/audit | Cùng checkout được, read-only |
| PDP và Catalog độc lập | 2 worktree / 2 file owners |
| Header + global CSS + design tokens | Chốt contract/owner trước; tránh sửa song song cùng file |
| Auth, DB migration, deploy | 1 người chịu trách nhiệm, có approval và rollback gate |

## 6. Kết thúc task và bảo vệ production

- Sau merge: xác nhận SHA, diff, runtime, đường dẫn preview và test liên quan; cập nhật task.
- `main/master` không phải nơi tiện tay thử code đa agent. Chỉ dùng tích hợp, kiểm chứng, phát hành.
- Cleanup worktree chỉ sau khi đã soát WIP, commit chưa tích hợp, nhánh/PR và backup. Không dùng `git worktree prune` hay xóa thư mục theo tên khi chưa kiểm tra.
- Release production: xem cấu hình deploy, backup/rollback, dữ liệu và health-check riêng. **Không suy luận deployment từ git push**.
- Orca `externalWorktreeVisibility=show` giúp nhìn thấy checkout do Git/Commander tạo, nhưng không tự cấp ownership, quyền hạn hoặc agent session.

## 7. Trách nhiệm

- **Bạn/Product Owner:** mục tiêu, quyết định phạm vi, nghiệm thu business và phê duyệt release.
- **Coordinator:** kiểm kê trạng thái, chia lane, tránh conflict, review evidence, quyết định tích hợp sau kiểm tra.
- **Worker/Agent:** chỉ làm trong worktree/scope được giao; không tự merge main, push production hay reset WIP.
- **Reviewer:** đối chiếu diff, test, ảnh, API contract và rủi ro; không tự chỉnh worktree của worker khác.

Tài liệu này là playbook chung. Nếu có mâu thuẫn về bảo mật, dữ liệu hoặc release, ưu tiên quy tắc an toàn trong `AGENTS.md` và quy trình production được phê duyệt.
