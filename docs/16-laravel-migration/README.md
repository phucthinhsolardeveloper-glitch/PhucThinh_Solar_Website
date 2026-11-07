# Chuyển đổi backend sang Laravel 13

Tài liệu đặc tả gốc: [../12-ai/LARAVEL13-MIGRATION-CLAUDE-MASTER-SPEC.md](../12-ai/LARAVEL13-MIGRATION-CLAUDE-MASTER-SPEC.md).
Thư mục này ghi trạng thái thật của quá trình chuyển đổi.

## Nhánh và môi trường

| Thứ | Dùng gì |
|---|---|
| Production, GitHub `master`, git local `master`, container local đang chạy site | Bản mới nhất của `master` |
| Chuyển đổi Laravel | Nhánh `migration/laravel13-integration`, worktree `/home/admin01/src/pts-laravel13` |
| Database cho Laravel | `pts_laravel13_clone` trong container `pts-mariadb-local`, bản sao của `phucthinhs_local`. Không trỏ Laravel vào `phucthinhs_local` hay production |

Không code Laravel trên `master`. Mọi thay đổi trên `master` phải đưa sang nhánh
chuyển đổi bằng `scripts/sync-laravel-branch.sh` ngay sau khi push. Script dừng khi
có xung đột và không ép push.

## Trạng thái

| Phase | Trạng thái | Bằng chứng |
|---|---|---|
| 0 — Đóng băng và kiểm kê | Đang làm, 2026-11-07 | [phase-0/](phase-0/) |
| 1 — Nền Laravel | Chưa bắt đầu | |

## Gate Phase 0

- [x] Mốc Git và trạng thái worktree: `phase-0/baseline.json`
- [x] Dấu vân tay schema và số dòng từng bảng: `phase-0/schema.md`
- [x] Database riêng cho chuyển đổi, cùng dấu vân tay schema với bản local
- [x] Danh sách API PHP, cầu nối Nitro, contract TypeScript: `phase-0/api-inventory.md`
- [x] Ma trận chức năng Admin legacy: `phase-0/admin-capabilities.md`
- [x] Bản đồ file → domain (bản nháp theo tên file): `phase-0/domain-map.md`
- [x] Kiểm kê media local: `phase-0/media.md`
- [x] Mốc test hiện tại và chênh lệch production/source: `phase-0/test-and-runtime-baseline.md`
- [ ] Chủ lane xác nhận bản đồ domain (32 file chưa phân loại)
- Không có thay đổi production nào trong Phase 0. Không có secret hay dữ liệu cá nhân trong các file trên.

Chạy lại kiểm kê: `python3 tools/migration/phase0_inventory.py` từ gốc worktree.
