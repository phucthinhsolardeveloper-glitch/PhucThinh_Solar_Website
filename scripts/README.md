# Danh mục script Phúc Thịnh Solar

Đọc file này trước khi chạy một script lạ. Quy ước:

- `READ-ONLY`: không chủ ý ghi business data.
- `LOCAL-WRITE`: chỉ được ghi local/test.
- `PRODUCTION-WRITE`: có thể đổi production; cần backup và phê duyệt.
- `DESTRUCTIVE-GUARDED`: có guard mạnh nhưng vẫn phải kiểm đúng target.

Các script trả exit code khác 0 khi fail/block. Không nối `|| true` vào lệnh vận
hành chỉ để pipeline tiếp tục.

## 1. Entry point cho người vận hành

| Script | Phạm vi | Tác động | Dùng khi |
|---|---|---|---|
| `deploy-production.sh` | Local điều khiển VPS | Production code | Deploy `--auto`, `--safe` hoặc `--fast`; không tự migration |
| `predeploy-local.sh` | Local Docker | Test fixture local, tự cleanup | Cổng regression/lint/smoke/security trước deploy |
| `package-hosting-release.sh` | Local | Tạo `backup/releases/*` | Đóng gói source/tools theo Git commit + checksum |
| `backup-local.sh` | Local | Tạo `backup/local/*` | Trước import/migration/thay đổi dữ liệu local |
| `test-backup-restore-local.sh` | Local | DB/thư mục tạm rồi tự xóa | Chứng minh backup local restore được |

Lệnh chuẩn:

```bash
LOCAL_ENFORCE_RBAC=1 ./scripts/predeploy-local.sh
./scripts/deploy-production.sh --safe --confirm-production
./scripts/deploy-production.sh --fast --confirm-production
./scripts/deploy-production.sh --auto --confirm-production
```

## 2. Dữ liệu sản phẩm và nội dung

| Script | Mặc định | Khi ghi |
|---|---|---|
| `preview-product-bulk-file.php` | READ-ONLY preview Excel | Không có mode ghi |
| `commit-product-bulk-file.php` | Dry-run | `--commit`, chỉ dùng local |
| `setup-solution-hub-local.php` | Dry-run | `--apply`, từ chối ngoài `APP_ENV=local` |
| `import-solution-content-local.php` | Preview Markdown | `--apply`, transaction local |
| `import-lamp-images-local.php` | Dry-run mapping | `--apply`; production còn cần token xác nhận riêng |
| `import-oriana-images-local.php` | Dry-run mapping | `--apply`; production còn cần token xác nhận riêng |
| `optimize-webp.php` | Có thể ghi WebP nếu không truyền cờ | Dùng `--dry-run` trước; không xóa ảnh gốc |
| `package-oriana-media.sh` | Tạo archive media | Không ghi DB, yêu cầu đúng inventory ảnh |

Luật chung: backup → dry-run → đọc errors/warnings/conflicts → apply local →
test UI → mới xin phép production. Confirmation token chỉ chống chạy nhầm,
không phải sự cho phép thay dữ liệu.

## 3. Auth, quyền và email

| Script | Phạm vi | Lưu ý |
|---|---|---|
| `create-local-rbac-demo-accounts.php` | LOCAL-WRITE | Chỉ `APP_ENV=local`; credential file phải ngoài webroot, mode 0600 |
| `check-admin-permission-readiness.php` | READ-ONLY | Kiểm catalog/nhóm quyền và debug bypass |
| `sync-permission-groups.php` | PRODUCTION-WRITE khi `--apply` | Ưu tiên Admin UI; không lưu password argument vào history |
| `smtp-readiness.php` | READ-ONLY với config/connect | `--send-test` gửi một email thật |

## 4. Preflight và tối ưu

| Script | Tác động | Mục tiêu |
|---|---|---|
| `preflight-commerce-migrations.php` | READ-ONLY | Engine/index/orphan/token trước migration checkout |
| `optimize-webp.php` | Ghi file `.webp` nếu không dry-run | Giảm ảnh, chỉ giữ output hợp lệ và nhỏ hơn nguồn |

Migration production không chạy tự động bởi deploy. `migrations/000_preflight_production.php`
chỉ đọc schema; báo `READY` không có nghĩa là được phép chạy mọi migration dữ
liệu cũ.

## 5. Regression test PHP

Không cần chạy từng file trong vận hành thường ngày; `predeploy-local.sh` copy
vào container PHP 7.4 và điều phối chúng. Nhóm chính:

- Security/session/email: `test-security-hardening.php`,
  `test-public-session-security.php`, `test-email-*.php`, `test-proxy-https.php`.
- Admin/auth/RBAC: `test-admin-*.php`, `test-permission-catalogue.php`,
  `check-admin-permission-readiness.php`.
- Commerce: `test-checkout-atomicity.php`, `test-commerce-transaction.php`,
  `test-product-pricing-admin-http.php`.
- Product/catalog: `test-product-*.php`, `test-cache-performance.php`.
- Public content/forms/routes: `test-news-hub.php`, `test-solution-hub.php`,
  `test-project-hub.php`, `test-public-*.php`.

Các test có thể tạo fixture local nhưng phải tự cleanup. `predeploy-local.sh`
chặn deploy nếu còn fixture nhận diện được.

## 6. Playwright

Nằm trong `tools/visual-tests/tests/`:

```bash
cd tools/visual-tests
npx playwright test --workers=1
```

- Dùng `--workers=1` vì MagicZoom/carousel/font paint nhạy timing.
- Không cập nhật snapshot hàng loạt khi chưa xem expected/actual.
- Production-safe subset được `deploy-production.sh` chạy sau deploy:
  `mobile-commerce-experience.spec.js` và `typography-system.spec.js`.

## 7. Script trên VPS

Các file nguồn nằm ở `deploy/vps/scripts/`, bản cài chạy tại
`/srv/phucthinhsolar/scripts/`.

| Script VPS | Tác động | Guard/rollback |
|---|---|---|
| `bootstrap-ubuntu.sh` | Cài Docker/package, tạo `/srv` | Chỉ Ubuntu 24.04; không sửa SSH/firewall/DNS |
| `install-stack.sh` | Cài compose/config/scripts, tạo shared dirs | Block khi thiếu secret; không xóa shared/secrets |
| `install-systemd.sh` | Cài/bật timer backup | Có thể kiểm bằng `systemctl list-timers` |
| `deploy-release.sh` | Build và chuyển image | Check checksum/symlink; auto rollback khi health fail |
| `verify-stack.sh` | READ-ONLY | Compose, image digest, PHP extensions, HTTPS |
| `rollback-release.sh` | Đổi về previous image | Không đụng DB/upload |
| `backup.sh --db-only` | Tạo DB backup | Khóa ghi ngắn; checksum tự kiểm |
| `backup.sh --full` | Tạo DB + upload backup | Tốn disk/thời gian; checksum tự kiểm |
| `restore-check.sh` | Restore vào container DB tạm | Tự cleanup, không đụng DB thật |
| `restore-initial.sh` | DESTRUCTIVE-GUARDED initial restore | Chỉ DB/upload rỗng + token `INITIAL_EMPTY_DATABASE` |
| `export-runtime-images.sh` | Tạo archive Docker images | Phải copy off-site |

## 8. Chính sách lưu giữ và dọn tài nguyên

- Backup ứng dụng: giữ 1–2 bản `*-full` gần nhất, có checksum và restore-check
  khi cần; không xóa bản đang dùng cho rollback.
- Release: giữ current, previous và tối đa một rollback candidate. `incoming/`
  là staging, chỉ giữ gói hiện tại và tối đa một gói trước đó sau khi triển khai
  thành công.
- Docker: kiểm tra `docker system df` trước khi dọn. `docker builder prune -af`
  chỉ dọn build cache không dùng; image chỉ được dọn sau khi đối chiếu
  `state/current-image`, `state/previous-image` và container đang chạy. Không
  dùng `docker system prune -a --volumes` trong production.
- `shared/upload` là dữ liệu sống, không dọn; thumbs/cache/logs chỉ dọn theo
  runbook và sau khi xác minh có thể tái tạo.

## 9. Script không thay thế quyết định con người

Không script nào được hiểu là quyền tự động để:

- đổi DNS/Cloud Firewall/SSH;
- tạo hoặc rotate secret;
- chạy migration ghi production;
- restore đè dữ liệu;
- xóa backup/upload/DB volume;
- import ảnh/nội dung chưa được duyệt;
- gửi email hàng loạt.

Quy trình chi tiết: [Sổ tay chủ hệ thống](../docs/06-operations/owner-system-handbook.md).
