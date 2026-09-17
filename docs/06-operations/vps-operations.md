# Vận hành VPS hằng ngày và xử lý sự cố

Cập nhật: 2026-09-17. Dùng cho VPS **đã dựng xong**. Lần dựng đầu và chuyển nhà
xem [vps-cloudfly-runbook.md](vps-cloudfly-runbook.md).

---

## 1. Thông tin máy

| Hạng mục | Giá trị (đo 2026-09-17) |
|---|---|
| IP | `222.255.182.182`, hostname `pts-prod-01` |
| OS / kernel | Ubuntu 24.04, `6.8.0-139-generic` |
| CPU / RAM / disk | 8 vCPU / 15 GiB / 116 GB (đang dùng 11 GB = 9%) |
| Docker / Compose | 29.8.1 / 5.5.1 |
| Gốc stack | `/srv/phucthinhsolar` |
| Truy cập | `ssh -i ~/.ssh/id_ed25519 root@222.255.182.182` |
| Domain public | **chưa trỏ DNS.** `.env` dùng `staging.phucthinhsolar.com` |

Website thật vẫn đang chạy ở host cũ (`103.82.21.26`, FTP + phpMyAdmin) cho tới
khi cutover. Xem [deployment.md](deployment.md).

---

## 2. Bố cục `/srv/phucthinhsolar`

```text
/srv/phucthinhsolar
├── docker-compose.yml     3 service: proxy (Caddy) → web (Apache/PHP 7.4) → db (MariaDB 10.11)
├── Caddyfile              TLS + reverse proxy
├── .env                   domain, email ACME, DB name/user, PTS_WEB_IMAGE   [0600]
├── secrets/               [thư mục 0700 root] — xem §6
├── config/config.php      mount read-only vào /var/www/html/libraries/config.php
├── app/                   Dockerfile + php-production.ini + apache-security.conf
├── scripts/               bootstrap, install-stack, install-systemd, deploy-release,
│                          rollback-release, verify-stack, backup, restore-check,
│                          restore-initial, export-runtime-images
├── systemd/               2 service + 2 timer backup
├── releases/<commit>/     release đã giải nén: public_html + migrations + scripts + docs
├── incoming/              gói release và gói migration mới tải lên
├── shared/                DỮ LIỆU SỐNG, mount vào container
│   ├── upload/            1.126 file / 852 MB  ← KHÔNG TÁI TẠO ĐƯỢC
│   ├── thumbs/            sinh lại được
│   ├── caches/            sinh lại được
│   ├── assets-caches/     sinh lại được
│   ├── watermark/         sinh lại được
│   └── logs/
├── state/                 thư mục tạm của script restore
└── backups/               [0700] bản backup local
```

Nguyên tắc: **`shared/upload` và DB là thứ duy nhất không tái tạo được.** Mọi
thứ khác dựng lại từ Git + image.

---

## 3. Lệnh dùng thường xuyên

Tất cả chạy trong `/srv/phucthinhsolar`.

```bash
# Trạng thái 3 container
docker compose ps

# Log web 100 dòng cuối (đây là chỗ xem lỗi PHP)
docker compose logs --tail=100 web

# Log DB
docker compose logs --tail=100 db

# Khởi động lại chỉ web (không mất DB, không mất upload)
docker compose up -d --force-recreate web

# Kiểm tra tổng thể: container, digest image, PHP runtime, HTTPS
./scripts/verify-stack.sh

# Xóa cache trang (sau khi deploy file PHP/template)
rm -f shared/caches/cache_*

# Vào shell container web
docker exec -it "$(docker compose ps -q web)" bash

# Gọi một URL từ trong container (bỏ qua Caddy/TLS — dùng khi DNS chưa trỏ)
docker exec "$(docker compose ps -q web)" curl -sS -o /dev/null -w '%{http_code}\n' http://127.0.0.1/
```

---

## 4. Deploy một release mới

```bash
# Trên máy dev: cả hai cổng phải sạch trước
LOCAL_ENFORCE_RBAC=1 ./scripts/predeploy-local.sh
cd tools/visual-tests && PLAYWRIGHT_BROWSERS_PATH=.browsers npx playwright test --workers=1

# Đóng gói (chỉ source website; docs/scripts nằm ở archive riêng)
./scripts/package-hosting-release.sh /duong/dan/release

# Đẩy lên VPS rồi:
sudo /srv/phucthinhsolar/scripts/deploy-release.sh /srv/phucthinhsolar/incoming/<release>
sudo /srv/phucthinhsolar/scripts/verify-stack.sh

# Preflight migration (chỉ đọc, không ghi)
web=$(docker compose ps -q web)
docker cp releases/<commit>/migrations/000_preflight_production.php "$web":/tmp/
docker exec "$web" php /tmp/000_preflight_production.php
```

`deploy-release.sh` tự xác minh checksum, build image theo commit, chờ
healthcheck và **tự quay lại image trước** nếu web không healthy. Nó **không**
tự chạy migration và **không** đổi DNS.

Rollback code (không động DB/upload): `./scripts/rollback-release.sh`.

---

## 5. Backup

Timer systemd (bật bằng `./scripts/install-systemd.sh`):

| Timer | Lịch | Nội dung |
|---|---|---|
| `phucthinhsolar-db-backup.timer` | mỗi 6 giờ | chỉ DB |
| `phucthinhsolar-backup.timer` | ~02:36 hằng ngày | DB + `upload/` |

```bash
systemctl list-timers 'phucthinhsolar*'
systemctl start phucthinhsolar-db-backup.service    # chạy ngay, không chờ timer
journalctl -u phucthinhsolar-db-backup.service -n 20 --no-pager
ls -lh /srv/phucthinhsolar/backups/
```

Mỗi bản backup có `MANIFEST.txt` và checksum; script tự verify sau khi tạo.

**Cảnh báo đang hiện và chưa xử lý:**

```text
WARN: chưa có /usr/local/sbin/pts-backup-offsite; backup hiện chỉ nằm trên VPS.
```

Backup nằm cùng máy với dữ liệu thì **không phải backup** trước sự cố mất máy.
Phải tạo `/usr/local/sbin/pts-backup-offsite` đẩy sang S3/Object Storage/rclone
(mã hoá) **ở nhà cung cấp khác**. Snapshot tự động của CloudFly cũng cùng nhà
cung cấp, không thay được bản sao độc lập.

Mỗi tháng: tải một bản off-site về và chạy
`./scripts/restore-check.sh /duong/dan/backup`. Backup chưa từng restore thử
thì chưa biết là backup.

---

## 6. Secrets — cạm bẫy đã làm sập toàn bộ site

`secrets/` là thư mục `0700 root` — đó là lớp bảo vệ chính trên host.

**Mode từng file không được là `0600`.** Docker Compose (không swarm)
bind-mount secret vào `/run/secrets` và **giữ nguyên owner/mode của file trên
host**; khai báo `mode:` trong compose bị bỏ qua. Apache worker chạy bằng
`www-data` (uid 33), nên `0600 root` làm `is_readable()` trong
`libraries/config.php` trả false và **mọi trang PHP trả HTTP 500**:

```text
PHP Fatal error: Uncaught RuntimeException: Missing required runtime secret:
PTS_DB_PASSWORD_FILE in /var/www/html/libraries/config.php:15
```

Quyền đúng (`install-stack.sh` tự đặt):

| File | Owner | Mode |
|---|---|---|
| `db_password`, `app_secret`, `app_salt`, `pts_mail_config.php`, `ckfinder_license_*` | `root:33` | `0640` |
| `db_root_password` | `root:root` | `0600` |

Kiểm tra nhanh khi nghi vấn:

```bash
web=$(docker compose ps -q web)
docker exec "$web" su -s /bin/sh www-data -c 'test -r /run/secrets/db_password && echo DOC-DUOC || echo KHONG-DOC-DUOC'
```

`app_secret` và `app_salt` **phải là đúng giá trị của production cũ**. Mật khẩu
Admin là `md5(secret + password + salt)`; sinh giá trị mới sẽ làm **toàn bộ mật
khẩu hiện có không đăng nhập được**.

---

## 7. Sự cố thường gặp → cách xử lý

### Mọi trang trả 500

1. `docker compose logs --tail=50 web` — đọc dòng `PHP Fatal error`.
2. Nếu là `Missing required runtime secret` → xem §6.
3. Nếu là lỗi DB → `docker compose ps` xem `db` có `healthy`;
   `docker compose logs --tail=50 db`.
4. Nếu vừa deploy → `./scripts/rollback-release.sh`.

### Container báo `healthy` nhưng website vẫn lỗi

Đây là lỗi **đã từng xảy ra thật**: healthcheck cũ gọi `robots.txt` (file
tĩnh), nên nó vẫn xanh trong khi 100% trang PHP trả 500 — và
`deploy-release.sh` do đó cũng không rollback.

Đã sửa: healthcheck giờ gọi `GET /` và **đòi đúng HTTP 200**
(`ignore_errors=true` để đọc được status của phản hồi lỗi; 404/302/500 đều
fail). Nếu tự sửa lại healthcheck, luôn kiểm bằng một URL trả non-200 và xác
nhận exit code là 1.

Hai mốc thời gian **phải khớp nhau**: healthcheck `start_period` +
2×`interval` phải nhỏ hơn vòng chờ trong `deploy-release.sh`. Hiện tại
`start_period 15s` / `interval 10s` / `retries 6` so với vòng chờ 120s; đo thực
tế container healthy sau **8s**. Nếu đặt `start_period` dài hơn hạn chờ của
script thì **mọi deploy tốt cũng bị rollback oan**.

### HTTPS lỗi certificate

Trước cutover, `verify-stack.sh` báo `self-signed certificate` là **bình
thường** — domain chưa trỏ về VPS nên Caddy không xin được cert thật. Muốn có
cert thật trước cutover: tạo bản ghi `staging.phucthinhsolar.com` trỏ tới VPS
(runbook mục 6). Kiểm tra chức năng trong lúc chờ bằng cách gọi HTTP trực tiếp
vào container (§3).

### Ảnh không hiện

1. Ảnh gốc có trong container chưa:
   `docker exec "$(docker compose ps -q web)" sh -c 'find /var/www/html/upload -type f | wc -l'`
2. Thumbnail sinh theo yêu cầu, đường dẫn dạng
   `/thumbs/<WxHxMODE>/upload/<...>.webp`. Gọi thẳng URL đó xem trả 200 không.
3. `shared/thumbs` phải ghi được bởi uid 33.
4. `upload/.htaccess` **phải tồn tại** — nó chặn thực thi `.php` trong `upload/`.

### Hết disk

```bash
df -h /
du -sh /srv/phucthinhsolar/* | sort -h | tail
docker system df
```

Dọn theo thứ tự an toàn: `shared/caches`, `shared/thumbs`,
`shared/assets-caches` (đều sinh lại được) → `docker image prune` các image
release cũ → backup local quá hạn retention (mặc định 14 ngày). Giữ tối thiểu
**30% disk trống**. **Không bao giờ** dọn `shared/upload`.

### Không SSH được

Console CloudFly là đường cứu hộ. Kiểm tra Cloud Firewall còn mở TCP 22 từ IP
quản trị hiện tại — nếu IP động thì rule cũ đã hết đúng.

---

## 8. Bảo hiểm cho PHP 7.4 đã EOL

```bash
./scripts/export-runtime-images.sh
```

Xuất bundle image ra file và **copy sang nơi khác**. PHP 7.4 và base Debian đã
hết hỗ trợ; khả năng build lại từ Internet trong vài năm tới không được coi là
đảm bảo. Nâng lên PHP 8.x là đợt riêng, không làm chung với chuyển host.

---

## 9. Việc còn phải làm trên VPS

- [ ] Tạo `/usr/local/sbin/pts-backup-offsite` (backup hiện chỉ nằm trên VPS).
- [ ] Trỏ `staging.phucthinhsolar.com` về VPS để Caddy lấy cert thật.
- [ ] Chạy `export-runtime-images.sh` và mang bundle ra ngoài.
- [ ] Smoke test đầy đủ theo [runbook mục 10](vps-cloudfly-runbook.md).
- [ ] Tạo user `deploy` có sudo, kiểm tra ở **phiên SSH thứ hai**, rồi mới tắt
      root login (runbook mục 4).
- [ ] Cutover DNS (runbook mục 11). Giữ host cũ tối thiểu 7 ngày.
