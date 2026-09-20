# Deploy production cho chủ website

Tài liệu này là đường ngắn để tự deploy khi không có AI/developer hỗ trợ. Các
runbook dài vẫn là tài liệu cứu hộ; thao tác hằng ngày dùng script một lệnh.

## Chọn chế độ

| Chế độ | Dùng khi | Kiểm tra trước deploy | Backup | Thời gian thường gặp |
|---|---|---|---|---|
| `SAFE` | PHP/API, giỏ hàng, checkout, Admin, phân quyền, migration, hạ tầng, hoặc chưa chắc mức rủi ro | Full predeploy local và toàn bộ Playwright | DB + upload | Lâu hơn, thường 10–25 phút |
| `FAST` | CSS, chữ, ảnh, responsive hoặc template giao diện rủi ro thấp | Git/syntax/PHP 7.4 guard; tự từ chối đường dẫn nhạy cảm | DB-only | Thường 2–5 phút |

`FAST` chỉ rút ngắn phần test local và không nén lại toàn bộ upload. Nó **không
bỏ** checksum, image healthcheck, cache clear, schema preflight, HTTP smoke,
Playwright production hoặc log scan. Deploy code không tự chạy migration và
không ghi vào upload.

Nếu không chắc, chọn `SAFE`. Script sẽ từ chối `FAST` khi commit đụng commerce,
global layout, API, Admin, library, source, migration, Docker hoặc công cụ vận
hành.

## Chuẩn bị một lần

Máy deploy cần có repository, Docker local, Node/npm/Playwright, SSH key đã
được VPS chấp nhận và các container local của project. Cấu hình mặc định hiện
tại:

```text
Repository: /home/admin01/Web_PTS_PHP
SSH target: root@222.255.182.182
SSH key: /home/admin01/.ssh/id_ed25519
VPS stack: /srv/phucthinhsolar
Website: https://phucthinhsolar.com
```

Không chép password/token vào file này hoặc command history. Nếu đường dẫn/host
thay đổi, truyền `PTS_DEPLOY_HOST`, `PTS_DEPLOY_SSH_KEY`, `PTS_STACK_ROOT` và
`PTS_SITE_URL` ở môi trường shell.

## Deploy một lệnh

Trước hết commit và push thay đổi. Script cố ý từ chối working tree bẩn hoặc
commit chưa có trên remote để release luôn truy ngược được.

### SAFE

```bash
cd /home/admin01/Web_PTS_PHP
git pull --rebase
./scripts/deploy-production.sh --safe --confirm-production
```

### FAST

```bash
cd /home/admin01/Web_PTS_PHP
git pull --rebase
./scripts/deploy-production.sh --fast --confirm-production
```

Không đóng terminal cho tới dòng `PASS: deploy ... hoàn tất`. Website chỉ đổi
image sau khi image mới build xong; healthcheck không đạt thì
`deploy-release.sh` tự quay về image trước.

## Khi nào được coi là thành công

Cuối output phải có đủ:

```text
PASS: stack, runtime và HTTPS public đang hoạt động.
READY: toàn bộ schema migration đã đủ, không chạy lại file SQL.
ERROR_SCAN_web=0 ERROR_SCAN_proxy=0
PASS: deploy safe hoàn tất.
```

Dòng cuối sẽ là `fast` nếu chọn FAST. Sau đó mở cửa sổ ẩn danh và kiểm nhanh:

1. Trang chủ trên desktop và mobile.
2. Danh mục → chi tiết sản phẩm.
3. Menu mobile và đổi ngôn ngữ.
4. Giỏ hàng/checkout nếu website đang bật giá công khai.

Chế độ báo giá hiện tại cố ý trả `409` cho API thêm giỏ hàng; đó không phải lỗi
deploy.

## Rollback code một lệnh

Nếu release đã lên nhưng có lỗi giao diện/chức năng:

```bash
ssh -i /home/admin01/.ssh/id_ed25519 root@222.255.182.182 \
  '/srv/phucthinhsolar/scripts/rollback-release.sh'

ssh -i /home/admin01/.ssh/id_ed25519 root@222.255.182.182 \
  '/srv/phucthinhsolar/scripts/verify-stack.sh'
```

Rollback này chỉ đổi image code, không đụng database hoặc upload. Không restore
database theo quán tính; chỉ restore khi xác định có mutation DB sai và đã chọn
đúng backup đã verify.

## Nếu script dừng giữa chừng

- Dừng trước dòng `Build, chuyển image`: production cũ chưa đổi.
- `deploy-release.sh` báo image mới không healthy: script VPS tự quay về image
  trước.
- Dừng sau khi image đã active: output in sẵn lệnh rollback; chạy hai lệnh ở
  mục trên nếu website thực sự lỗi.
- SSH timeout nhưng website vẫn mở: đăng nhập VPS, chạy
  `cat /srv/phucthinhsolar/state/current-image` và `verify-stack.sh` trước khi
  quyết định rollback.
- Không tự chạy file migration SQL/PHP chỉ vì preflight liệt kê tên migration.

## Quy trình thủ công khi wrapper bị hỏng

Chỉ dùng phần này để cứu hộ. Thay `<release>` và `<commit-12>` bằng giá trị mà
`package-hosting-release.sh` in ra.

```bash
# Máy local
LOCAL_ENFORCE_RBAC=1 ./scripts/predeploy-local.sh
cd tools/visual-tests && npx playwright test --workers=1 && cd ../..
./scripts/package-hosting-release.sh

# VPS: backup trước
ssh -i /home/admin01/.ssh/id_ed25519 root@222.255.182.182 \
  '/srv/phucthinhsolar/scripts/backup.sh --full'

# Upload toàn bộ file trong release vào incoming/<release>, rồi VPS:
ssh -i /home/admin01/.ssh/id_ed25519 root@222.255.182.182 \
  'cd /srv/phucthinhsolar/incoming/<release> && sha256sum -c SHA256SUMS'
ssh -i /home/admin01/.ssh/id_ed25519 root@222.255.182.182 \
  '/srv/phucthinhsolar/scripts/deploy-release.sh /srv/phucthinhsolar/incoming/<release>'
ssh -i /home/admin01/.ssh/id_ed25519 root@222.255.182.182 \
  '/srv/phucthinhsolar/scripts/verify-stack.sh'
```

Các bước chi tiết về migration, backup/restore, hết disk và lỗi TLS nằm trong
[Vận hành VPS hằng ngày](vps-operations.md) và
[Runbook VPS CloudFly](vps-cloudfly-runbook.md).
