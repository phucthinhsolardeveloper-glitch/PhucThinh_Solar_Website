# Sổ tay chủ hệ thống Phúc Thịnh Solar

Tài liệu này dành cho chủ website hoặc người tiếp quản khi không có developer/AI
bên cạnh. Mục tiêu là trả lời ba câu:

1. Muốn làm một việc thì chạy lệnh nào?
2. Lệnh đó đọc/ghi/xóa dữ liệu gì?
3. Nếu thất bại thì dừng ở đâu và khôi phục thế nào?

Production hiện tại chạy tại `222.255.182.182` bằng Docker/Caddy. Repository
chuẩn nằm ở `/home/admin01/src/Web_PTS_PHP` trên máy phát triển. Không sửa trực tiếp
file trong container production; mọi thay đổi code phải đi qua Git và release.

## 1. Năm lệnh nên nhớ

```bash
# Mở local
docker compose -f docker-compose.local.yml up -d

# Cổng kiểm tra local đầy đủ
LOCAL_ENFORCE_RBAC=1 ./scripts/predeploy-local.sh

# Deploy an toàn
./scripts/deploy-production.sh --safe --confirm-production

# Deploy nhanh cho frontend ít rủi ro
./scripts/deploy-production.sh --fast --confirm-production

# Kiểm tra production từ VPS
ssh -i /home/admin01/.ssh/id_ed25519 root@222.255.182.182 \
  'cd /srv/phucthinhsolar && ./scripts/verify-stack.sh'
```

Nếu chưa chắc chọn lệnh nào, dùng `SAFE`. `FAST` tự chặn commerce, global
layout, API, Admin, library, migration, Docker và script vận hành.

### Quy tắc xử lý deploy bị dừng (dùng chung cho mọi AI agent)

Không ngồi chờ hoặc chạy lại nguyên quy trình nhiều lần khi lệnh deploy dừng.
Ngay lập tức phải:

1. Xác nhận tiến trình còn chạy hay đã thoát, production image có đổi chưa.
2. Lấy phần cuối log và chạy trace (`bash -x` hoặc chạy riêng bước lỗi) để xác
   định chính xác lệnh trả mã khác 0.
3. Phân loại lỗi: lỗi code/test thật, test stale/false positive, lỗi Docker/RAM,
   hay lỗi mạng/SSH. Chỉ sửa đúng nguyên nhân; không nới assertion để làm xanh.
4. Nếu lỗi là test stale, sửa guard để phản ánh dữ liệu và contract hiện hành,
   commit/push rồi chạy lại SAFE một lần. Nếu là thiếu tài nguyên, kiểm tra
   `free -h`, Docker và WSL trước khi thử lại.
5. Không upload hoặc báo “đã deploy” khi chưa thấy image production đổi sang
   đúng commit và smoke/healthcheck sau deploy PASS.

Một lần SAFE có thể lâu vì build Docker và chạy toàn bộ Playwright. Theo dõi
tiến độ bằng mốc rõ ràng (predeploy, Playwright, backup, upload, healthcheck),
không tạo các phiên deploy song song. Đây là quy tắc vận hành bắt buộc cho
Claude/Codex/agent khác dùng chung project.

## 2. Bản đồ hệ thống

```text
Máy phát triển
  /home/admin01/src/Web_PTS_PHP
  ├── source Git
  ├── Docker local :8080
  ├── test + backup local
  └── package release + điều khiển deploy

Internet
  └── DNS phucthinhsolar.com
       └── VPS 222.255.182.182
            └── Caddy :80/:443
                 └── Apache/PHP 7.4 container
                      └── MariaDB 10.11 (mạng Docker private)

Dữ liệu production sống ngoài image
  ├── Docker volume DB
  ├── /srv/phucthinhsolar/shared/upload
  ├── /srv/phucthinhsolar/secrets
  └── /srv/phucthinhsolar/backups
```

Code trong image có thể build/deploy/rollback. DB và `shared/upload` là dữ liệu
không tái tạo được; luôn backup trước thao tác có thể ghi chúng.

## 3. Chọn quy trình theo việc cần làm

| Việc | Lệnh/tài liệu chính | Có ghi dữ liệu? |
|---|---|---|
| Chạy website local | `docker compose -f docker-compose.local.yml up -d` | Có, nhưng chỉ volume local |
| Kiểm tra trước deploy | `scripts/predeploy-local.sh` + Playwright | Test local có fixture rồi tự dọn |
| Backup local | `scripts/backup-local.sh` | Chỉ tạo file trong `backup/local` |
| Thử restore local | `scripts/test-backup-restore-local.sh` | Tạo DB tạm rồi tự xóa |
| Deploy thường | `scripts/deploy-production.sh --safe` | Đổi image; không tự migration |
| Deploy frontend nhanh | `scripts/deploy-production.sh --fast` | Đổi image; backup DB-only |
| Rollback code | VPS `scripts/rollback-release.sh` | Đổi image; không đụng DB/upload |
| Backup production | VPS `scripts/backup.sh` | Tạo backup; DB bị khóa ghi ngắn lúc dump |
| Thử restore production backup | VPS `scripts/restore-check.sh` | Chỉ container DB tạm |
| Restore sang VPS mới | VPS `scripts/restore-initial.sh` | Có; chỉ chấp nhận DB/upload rỗng |
| Dựng VPS mới | `deploy/vps/scripts/bootstrap-ubuntu.sh` rồi `install-stack.sh` | Cài package và tạo stack |
| Nhập Excel sản phẩm | Admin hoặc preview/commit script local | Có khi commit; luôn preview trước |
| Nhập ảnh hàng loạt | Importer local; production cần xác nhận riêng | Có thể ghi DB/upload |
| Kiểm SMTP | `scripts/smtp-readiness.php` | `--send-test` gửi một email thật |

Danh sách đầy đủ nằm ở [Danh mục script](../../scripts/README.md).

## 4. Quy trình làm việc hằng ngày

### 4.1 Bắt đầu một phiên làm việc

```bash
cd /home/admin01/src/Web_PTS_PHP
git status --short
git branch -vv
git pull --rebase
docker compose -f docker-compose.local.yml up -d
docker compose -f docker-compose.local.yml ps
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:8080/
```

Chỉ tiếp tục khi Git không có thay đổi lạ, container `db` healthy và trang chủ
local trả 200. Thay đổi chưa commit trong working tree có thể là công việc của
người khác; không reset/xóa nếu chưa xác minh.

### 4.2 Sửa code

1. Tái hiện lỗi trên local và ghi lại URL/viewport/dữ liệu.
2. Đọc route → source → template/API → schema/config liên quan.
3. Ghi rõ dữ liệu có thể bị tác động và rollback.
4. Sửa phạm vi nhỏ; không refactor ngoài yêu cầu.
5. Test đúng PHP 7.4 qua container.
6. Kiểm desktop, mobile, 2K nếu thay đổi giao diện.
7. Đọc `git diff`, tìm secret/PII và file ngoài phạm vi.

Source web được bind-mount, nên sửa PHP/JS/CSS thường thấy ngay. Chỉ build lại
local image khi đổi Dockerfile hoặc cấu hình PHP:

```bash
docker compose -f docker-compose.local.yml build web
docker compose -f docker-compose.local.yml up -d --force-recreate web
```

Xóa cache local đúng chỗ:

```bash
docker compose -f docker-compose.local.yml exec -T web \
  sh -c 'find /var/www/html/caches -maxdepth 1 -type f -name "cache_*" -delete'
```

### 4.3 Test theo mức rủi ro

Thay đổi nhỏ trong lúc làm:

```bash
# PHP
docker exec pts-php74-local php -l /var/www/html/duong/dan/file.php

# JavaScript
node --check domains/phucthinhsolar.com/public_html/assets/js/apps.js

# Một spec Playwright
cd tools/visual-tests
npx playwright test tests/mobile-commerce-experience.spec.js --workers=1
```

Cổng đầy đủ trước SAFE deploy:

```bash
cd /home/admin01/src/Web_PTS_PHP
LOCAL_ENFORCE_RBAC=1 ./scripts/predeploy-local.sh

cd tools/visual-tests
npx playwright test --workers=1
cd ../..
```

Điều kiện đạt là `0 failed`; không hardcode số pass vì bộ test tiếp tục tăng.
Không sửa/nới assertion chỉ để test xanh. Nếu screenshot lệch, phải xem ảnh
expected/actual và xác nhận thay đổi có chủ ý trước khi cập nhật baseline.

### 4.4 Backup local trước thao tác dữ liệu

```bash
backup_dir="$(./scripts/backup-local.sh)"
./scripts/test-backup-restore-local.sh "$backup_dir"
```

Hai dòng phải kết thúc bằng checksum OK và `PASS: Restore local đạt`. Backup
local lấy upload từ named volume đang chạy, không lấy nhầm thư mục host cũ.

### 4.5 Commit và push

```bash
git diff --check
git status --short
git diff --stat
git add <danh-sach-file-da-xac-minh>
git diff --cached --check
git commit -m "type: mo ta ngan"
git push origin <ten-nhanh>
```

Không dùng `git add .` khi working tree có công việc không liên quan. Script
deploy yêu cầu working tree sạch và `HEAD` khớp upstream.

## 5. Deploy production

### 5.1 SAFE hay FAST

| Chọn | Khi nào |
|---|---|
| `SAFE` | PHP/API, cart/checkout, Admin, auth/RBAC, email, migration, Docker, script, global layout hoặc không chắc |
| `FAST` | CSS, ảnh, chữ, responsive hoặc frontend có phạm vi hẹp |
| `AUTO` | Để script tự chọn FAST/SAFE theo phạm vi thay đổi; dùng cho deploy thường ngày |

```bash
./scripts/deploy-production.sh --safe --confirm-production
./scripts/deploy-production.sh --fast --confirm-production
./scripts/deploy-production.sh --auto --confirm-production
```

Script tự làm: so Git với image active → test theo mode → package/checksum →
kiểm VPS → backup → upload → build image → healthcheck → clear cache → schema
preflight chỉ đọc → HTTP/Playwright production → log scan.

Chi tiết output và rollback: [Deploy production cho chủ website](deploy-production-owner.md).

Sau mỗi deploy phải cập nhật [nhật ký triển khai](deployment-log.md) với commit,
image, mode, migration, backup, smoke test, log scan và rollback point. Docs là
runbook và lịch sử vận hành; không chờ đến lúc dọn disk mới ghi lại.

### 5.2 Sau deploy tự kiểm bằng mắt

Mở cửa sổ ẩn danh và kiểm:

1. Trang chủ desktop, mobile và màn 2K.
2. Danh mục → chi tiết sản phẩm.
3. Menu mobile, tìm kiếm và đổi ngôn ngữ.
4. Footer, contact dock và không tràn ngang.
5. Nếu bật giá: product → cart → checkout → confirmation.
6. Admin login và màn vừa thay đổi.

Website hiện ở chế độ báo giá nên API add-cart trả 409 là hành vi chủ ý.

### 5.3 Rollback code

```bash
ssh -i /home/admin01/.ssh/id_ed25519 root@222.255.182.182 \
  'cd /srv/phucthinhsolar && ./scripts/rollback-release.sh && ./scripts/verify-stack.sh'
```

Rollback image không phục hồi DB/upload. Nếu một migration hoặc importer đã
ghi dữ liệu sai, dừng ghi mới và đánh giá riêng; không restore DB theo quán tính
vì sẽ mất dữ liệu phát sinh sau backup.

## 6. Vận hành VPS thường ngày

### 6.1 Đăng nhập và kiểm sức khỏe

```bash
ssh -i /home/admin01/.ssh/id_ed25519 root@222.255.182.182
cd /srv/phucthinhsolar

docker compose ps
./scripts/verify-stack.sh
cat state/current-image
cat state/previous-image
df -h /
docker system df
```

Kỳ vọng: `web` và `db` healthy, proxy running, HTTPS PASS, disk trống trên 30%.

### 6.2 Đọc log mà không lộ dữ liệu quá mức

```bash
docker compose logs --since=30m --tail=200 web
docker compose logs --since=30m --tail=100 proxy
docker compose logs --since=30m --tail=100 db
```

Tìm `PHP Fatal`, `Uncaught`, HTTP 5xx, DB restart hoặc certificate error. Không
chép nguyên log có IP/email/form data vào tài liệu/chat; chỉ trích phần cần chẩn
đoán và che PII.

### 6.3 Restart đúng loại thay đổi

```bash
# Nội dung bind-mount thay đổi: Caddyfile/config/secrets
docker compose restart web proxy

# Biến trong .env thay đổi: phải recreate để nạp environment mới
docker compose up -d --force-recreate web proxy

./scripts/verify-stack.sh
```

Restart/recreate container không xóa DB hoặc upload vì chúng nằm ngoài image.

### 6.4 Backup production

```bash
# DB-only, nhanh
./scripts/backup.sh --db-only

# DB + upload
./scripts/backup.sh --full

# Xem timer và kết quả
systemctl list-timers 'phucthinhsolar*'
journalctl -u phucthinhsolar-backup.service -n 40 --no-pager
journalctl -u phucthinhsolar-db-backup.service -n 40 --no-pager
```

Dump dùng `--lock-all-tables` vì còn bảng MyISAM, nên ghi DB có thể bị khóa
ngắn. Full backup nên chạy giờ ít giao dịch.

Backup chỉ đáng tin sau restore rehearsal:

```bash
./scripts/restore-check.sh /srv/phucthinhsolar/backups/<timestamp-full>
```

Script tạo MariaDB tạm, import, đếm bảng/file rồi tự xóa. Không trỏ vào DB thật.

### 6.5 Dung lượng và dọn an toàn

```bash
df -h /
du -x -h -d1 /srv/phucthinhsolar | sort -h
du -x -h -d1 /srv/phucthinhsolar/backups | sort -h
du -x -h -d1 /srv/phucthinhsolar/incoming | sort -h
du -x -h -d1 /srv/phucthinhsolar/releases | sort -h
docker system df
```

Thứ tự dọn:

1. Cache sinh lại được: `shared/caches`, `shared/assets-caches`, rồi `thumbs` nếu
   chấp nhận website tái sinh thumbnail.
2. Incoming release/migration cũ đã xác minh không còn dùng.
3. Full backup cũ, nhưng giữ ít nhất hai bản gần nhất và một bản off-site đã
   restore thử.
4. Docker dangling image/build cache.
5. Release/image cũ chỉ xóa sau khi chắc không phải current/previous rollback.

Không bao giờ xóa `shared/upload`, Docker volume DB, `secrets`, `.env`,
`state/current-image` hoặc `state/previous-image` để giải phóng disk.

Trước khi xóa backup, luôn in đúng đường dẫn và dung lượng. Chỉ xóa đường dẫn
cụ thể, không dùng glob rộng:

```bash
du -sh /srv/phucthinhsolar/backups/<timestamp-cu>
rm -rf -- /srv/phucthinhsolar/backups/<timestamp-cu>
df -h /
```

### 6.6 Việc định kỳ

Rà soát tài liệu theo [lịch bảo trì tài liệu](documentation-maintenance.md):
ít nhất hàng tháng, và ngay sau milestone/chức năng lớn hoặc thay đổi workflow.
Mỗi đợt rà soát phải cập nhật lịch sử nếu phát hiện kiến thức mới, giả định sai
hoặc runbook lỗi thời.

Hằng ngày:

- Website trả 200, container healthy.
- Timer backup không failed.
- Disk không tăng bất thường.

Hằng tuần:

- Xem PHP/5xx log và dung lượng Docker/backups.
- Kiểm một login Admin, form quan trọng và một trang sản phẩm.

Hằng tháng:

- Tải một full backup từ off-site về và chạy `restore-check.sh`.
- Kiểm certificate, DNS, Cloud Firewall và user SSH.
- Xuất/cập nhật bundle runtime PHP 7.4 nếu image thay đổi:

```bash
./scripts/export-runtime-images.sh
```

Bundle này phải copy ra nơi khác VPS.

## 7. Dựng VPS mới từ số không

### 7.1 Yêu cầu

- Ubuntu 24.04 sạch, quyền console/root ban đầu.
- Khuyến nghị tối thiểu 4 vCPU, 8 GB RAM, disk đủ DB/upload + 2 bản full backup
  + Docker images; giữ trên 30% trống.
- Quyền sửa DNS và Cloud Firewall.
- SSH key quản trị, full backup đã verify, secrets từ password manager.
- Không dùng production DB/upload trong lúc rehearsal.

### 7.2 Cloud Firewall

Mở:

- TCP 22 chỉ từ IP quản trị/VPN.
- TCP 80 và 443 từ Internet.
- UDP 443 nếu muốn HTTP/3.

Không mở 3306, Docker API hoặc cổng container. Giữ console nhà cung cấp làm
đường cứu hộ.

### 7.3 SSH và user vận hành

Đăng nhập root bằng key, tạo user có sudo, rồi kiểm ở cửa sổ thứ hai:

```bash
adduser deploy
usermod -aG sudo deploy
install -d -m 0700 -o deploy -g deploy /home/deploy/.ssh
cp /root/.ssh/authorized_keys /home/deploy/.ssh/authorized_keys
chown deploy:deploy /home/deploy/.ssh/authorized_keys
chmod 0600 /home/deploy/.ssh/authorized_keys
```

Từ máy local mở **phiên mới** và xác nhận `sudo -v` hoạt động. Sau đó dùng
`sudoedit /etc/ssh/sshd_config.d/99-pts-hardening.conf`:

```text
PubkeyAuthentication yes
PasswordAuthentication no
KbdInteractiveAuthentication no
PermitRootLogin prohibit-password
```

Kiểm trước khi reload:

```bash
sshd -t && systemctl reload ssh
```

Không đóng phiên root đầu tiên trước khi phiên `deploy` thứ hai đăng nhập và
sudo thành công. Tắt root hoàn toàn là quyết định riêng sau khi đã có đường cứu
hộ console.

### 7.4 Chép bộ hạ tầng và bootstrap

Từ local:

```bash
scp -i /home/admin01/.ssh/id_ed25519 -r deploy/vps \
  root@<IP-VPS-MOI>:/root/pts-vps
```

Trên VPS mới:

```bash
/root/pts-vps/scripts/bootstrap-ubuntu.sh
/root/pts-vps/scripts/install-stack.sh
```

Lần đầu `install-stack.sh` có thể dừng với `MISSING` vì secret chưa được tạo;
đó là guard đúng. Script đã cài cấu trúc stack nhưng không cho khởi chạy thiếu
secret.

### 7.5 Tạo `.env` và secrets

Chỉnh `/srv/phucthinhsolar/.env` bằng `sudoedit`. `.env` chỉ chứa domain, tên
DB/user và image tag; không đặt password ở đây.

Trước cutover, dùng một hostname staging trỏ A record tới VPS mới, đặt hostname
đó vào `SITE_DOMAIN` và để `SITE_ALIASES` rỗng. Cách này giúp Caddy lấy TLS thật
và giúp smoke test chắc chắn đang đi vào máy mới; không dùng domain production
khi DNS vẫn còn trỏ máy cũ vì `verify-stack.sh` có thể vô tình kiểm nhầm máy.

Secrets bắt buộc:

```text
/srv/phucthinhsolar/secrets/db_password
/srv/phucthinhsolar/secrets/db_root_password
/srv/phucthinhsolar/secrets/app_secret
/srv/phucthinhsolar/secrets/app_salt
/srv/phucthinhsolar/secrets/pts_mail_config.php
```

Optional: `ckfinder_license_name`, `ckfinder_license_key`, `recaptcha_secret`.

- DB password phải tạo mới bằng password manager.
- `app_secret` và `app_salt` phải giữ đúng giá trị production hiện có, nếu đổi
  thì mật khẩu legacy không xác minh được.
- Không truyền secret qua chat, Git, command argument hoặc shell history. Dùng
  `sudoedit`/secure copy và không `cat` secret ra màn hình.
- reCAPTCHA chỉ đặt `RECAPTCHA_ACTIVE=1` khi cả site key và secret đã đúng.

Chạy lại để kiểm đủ secret và đặt owner/mode chính xác:

```bash
/root/pts-vps/scripts/install-stack.sh
namei -l /srv/phucthinhsolar/secrets/db_password
```

Chi tiết quyền: [deploy/vps/secrets/README.md](../../deploy/vps/secrets/README.md).

### 7.6 Chuẩn bị và thử backup nguồn

```text
migration-input/
  database.sql.gz
  upload.tar.gz
  SHA256SUMS
```

Upload gói này qua SSH, rồi:

```bash
/srv/phucthinhsolar/scripts/restore-check.sh /path/to/migration-input
```

Chỉ tiếp tục nếu checksum, số bảng và số file đều PASS.

### 7.7 Restore lần đầu

Lệnh này chỉ chạy khi DB và upload đích rỗng:

```bash
/srv/phucthinhsolar/scripts/restore-initial.sh \
  /path/to/migration-input INITIAL_EMPTY_DATABASE
```

Nếu đích có bảng/file, script dừng và không ghi đè. Không tìm cách bỏ guard;
xác định vì sao đích không rỗng trước.

### 7.8 Release đầu tiên

Wrapper SAFE/FAST dùng image active để tính diff, nên release đầu tiên trên VPS
mới thực hiện thủ công.

Local:

```bash
LOCAL_ENFORCE_RBAC=1 ./scripts/predeploy-local.sh
cd tools/visual-tests && npx playwright test --workers=1 && cd ../..
./scripts/package-hosting-release.sh
```

Upload nguyên thư mục release vừa được in ra vào
`/srv/phucthinhsolar/incoming/<release>`, rồi trên VPS:

```bash
cd /srv/phucthinhsolar/incoming/<release>
sha256sum -c SHA256SUMS
/srv/phucthinhsolar/scripts/deploy-release.sh \
  /srv/phucthinhsolar/incoming/<release>
/srv/phucthinhsolar/scripts/verify-stack.sh
```

`deploy-release.sh` tự rollback nếu image mới không healthy. Nó không tự chạy
migration hoặc đổi DNS.

### 7.9 Backup timer, runtime image và cutover

```bash
/srv/phucthinhsolar/scripts/install-systemd.sh
/srv/phucthinhsolar/scripts/backup.sh --full
/srv/phucthinhsolar/scripts/restore-check.sh \
  /srv/phucthinhsolar/backups/<timestamp-full>
/srv/phucthinhsolar/scripts/export-runtime-images.sh
```

Trước cutover: smoke đầy đủ trên hostname staging, đồng bộ DB/upload delta cuối,
backup cuối, đổi bản ghi A, kiểm TLS trên mạng khác và giữ VPS cũ tối thiểu bảy
ngày. Không đổi nameserver và server cùng một lúc.

## 8. Script dữ liệu và tích hợp

### 8.1 Excel sản phẩm

Ưu tiên Admin. Nếu cần CLI local:

```bash
docker cp /path/products.xlsx pts-php74-local:/tmp/products.xlsx
docker cp scripts/preview-product-bulk-file.php pts-php74-local:/tmp/
docker exec pts-php74-local php /tmp/preview-product-bulk-file.php /tmp/products.xlsx
```

Chỉ khi preview không có error/conflict ngoài dự kiến và đã backup local:

```bash
docker cp scripts/commit-product-bulk-file.php pts-php74-local:/tmp/
docker exec pts-php74-local php /tmp/commit-product-bulk-file.php \
  /tmp/products.xlsx --commit
```

Không chạy commit script này trực tiếp trên production. Quy trình bàn giao chi
tiết: [Hướng dẫn nhập sản phẩm](../11-workflows/huong-dan-ban-giao-nhap-san-pham.md).

### 8.2 Nhập nội dung/ảnh

Các importer mặc định dry-run. Luôn chạy dry-run → đọc mapping → backup → apply
local → test → mới cân nhắc production. Import ảnh production có confirmation
token riêng nhưng token không thay thế phê duyệt của chủ project.

```bash
# Nội dung Giải pháp local
docker cp /path/content.md pts-php74-local:/tmp/content.md
docker cp scripts/import-solution-content-local.php pts-php74-local:/tmp/
docker exec pts-php74-local php /tmp/import-solution-content-local.php \
  --file=/tmp/content.md

# Tối ưu WebP: xem trước trước
docker cp scripts/optimize-webp.php pts-php74-local:/tmp/
docker exec pts-php74-local php /tmp/optimize-webp.php \
  /var/www/html/upload --quality=82 --min-bytes=150000 --dry-run
```

Không dùng ảnh AI/stock thay bằng chứng dự án/chứng nhận. Không xóa ảnh gốc chỉ
vì đã tạo WebP.

### 8.3 SMTP

Copy script vào container có config cần kiểm. Ví dụ local:

```bash
docker cp scripts/smtp-readiness.php pts-php74-local:/tmp/
docker exec pts-php74-local php /tmp/smtp-readiness.php
docker exec pts-php74-local php /tmp/smtp-readiness.php --connect
docker exec pts-php74-local php /tmp/smtp-readiness.php --send-test
```

Trên production, lấy script từ `releases/<commit>/scripts/`, copy vào web
container rồi chạy cùng ba mode. Chỉ thực hiện `--send-test` khi đã được phép
gửi email thật.

`--send-test` gửi email thật tới địa chỉ quản trị đã cấu hình. Không lặp lại nếu
chưa kiểm mailbox/spam; không in credential SMTP ra terminal.

### 8.4 Phân quyền

`create-local-rbac-demo-accounts.php` chỉ dùng local. Với production, ưu tiên
Admin UI để sửa nhóm quyền. `sync-permission-groups.php --apply` có thể ghi hàng
loạt quyền và nhận password qua argument, nên chỉ chạy trong phiên riêng sau
backup và phê duyệt; không lưu command chứa password vào tài liệu/history.

## 9. Xử lý sự cố

### Website 500/502 sau deploy

```bash
cd /srv/phucthinhsolar
docker compose ps
docker compose logs --tail=100 web
./scripts/rollback-release.sh
./scripts/verify-stack.sh
```

Nếu log báo `Missing required runtime secret`, kiểm owner/mode secret trước khi
rollback nhiều lần.

### Website cũ dù deploy đã PASS

Kiểm image active và clear cache sinh lại được:

```bash
cat state/current-image
find shared/caches -maxdepth 1 -type f -name 'cache_*' -delete
find shared/assets-caches -maxdepth 1 -type f -delete
./scripts/verify-stack.sh
```

### DB không healthy

```bash
docker compose ps db
docker compose logs --tail=200 db
df -h /
```

Không xóa volume DB hoặc tự import dump mới. Nếu phải restore, ghi nhận mốc
backup và dữ liệu phát sinh sau mốc đó trước khi xin phê duyệt.

### Mất SSH

Dùng console nhà cung cấp, kiểm Cloud Firewall TCP 22, disk, `sshd -t` và service
`ssh`. Không reboot/xóa firewall hàng loạt nếu chưa có console mở.

### Mất cả VPS

1. Dựng Ubuntu 24.04 mới.
2. Bootstrap/install stack.
3. Nạp secrets từ password manager.
4. Load runtime image bundle hoặc build release đã kiểm checksum.
5. Restore full backup off-site vào DB/upload rỗng.
6. Verify/smoke rồi đổi DNS.

Nếu không có off-site backup, backup nằm cùng VPS không bảo vệ được tình huống
mất máy. Đây là rủi ro vận hành còn phải hoàn tất.

## 10. Những việc script cố ý không tự làm

- Đổi DNS/nameserver.
- Đổi Cloud Firewall hoặc tắt SSH/root.
- Tạo/rotate production secrets.
- Chạy migration ghi DB.
- Restore đè DB/upload có dữ liệu.
- Xóa backup/release/image cũ bằng glob rộng.
- Bật giá công khai, reCAPTCHA hoặc SMTP nếu thiếu cấu hình thật.
- Đưa nội dung/ảnh chưa được xác minh lên production.

Các việc này cần quyết định con người vì sai một lần có thể mất truy cập hoặc
dữ liệu. Script nên làm phần lặp lại; chủ hệ thống giữ quyền với quyết định phá
hủy hoặc thay đổi trạng thái bên ngoài.

## 11. Checklist bàn giao cho người mới

- [ ] Có quyền Git và biết branch production hiện dùng.
- [ ] SSH bằng key vào VPS và sudo/root hoạt động.
- [ ] Biết mở console/Cloud Firewall/DNS tại nhà cung cấp.
- [ ] Biết vị trí password manager; không nhận secret qua chat.
- [ ] Chạy được local Docker và trang `localhost:8080`.
- [ ] Chạy được một backup local + restore rehearsal.
- [ ] Hiểu SAFE/FAST và đã xem một deploy output hoàn chỉnh.
- [ ] Biết rollback code và phân biệt với restore dữ liệu.
- [ ] Biết current/previous image và hai full backup gần nhất.
- [ ] Biết off-site backup đang hoạt động hay chưa.

Các runbook sâu hơn:

- [Deploy SAFE/FAST](deploy-production-owner.md)
- [Vận hành VPS và xử lý sự cố](vps-operations.md)
- [Dựng/chuyển VPS](vps-cloudfly-runbook.md)
- [Backup và restore](backup-restore.md)
- [Thiết lập local](../02-development/local-setup.md)
