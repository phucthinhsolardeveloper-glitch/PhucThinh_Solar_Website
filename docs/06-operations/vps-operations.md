# Vận hành VPS hằng ngày và xử lý sự cố

Cập nhật: 2026-11-04. Dùng cho VPS **đã dựng xong**. Lần dựng đầu và chuyển nhà
xem [vps-cloudfly-runbook.md](vps-cloudfly-runbook.md).
Luồng end-to-end dành cho chủ website xem
[owner-system-handbook.md](owner-system-handbook.md).

Các thay đổi Nuxt/Caddy trong source là candidate cho public cutover; tài liệu
này không khẳng định cutover đã chạy trên VPS. Trước khi báo trạng thái
production, xác nhận `docker compose ps`, image tags và HTTP qua `verify-stack.sh`.

---

## 1. Thông tin máy

| Hạng mục | Giá trị (đo 2026-09-17) |
|---|---|
| IP | `222.255.182.182`, hostname `pts-prod-01` |
| OS / kernel | Ubuntu 24.04, `6.8.0-139-generic` |
| CPU / RAM / disk | 8 vCPU / 15 GiB / 116 GB (đo 20/09: dùng 25 GB = 22%, trống 91 GB) |
| Docker / Compose | 29.8.1 / 5.5.1 |
| Gốc stack | `/srv/phucthinhsolar` |
| Truy cập | `ssh -i ~/.ssh/id_ed25519 root@222.255.182.182` |
| Domain public | **đã cutover 2026-09-17**: `phucthinhsolar.com` + `www` trỏ về VPS |
| `.env` | `SITE_DOMAIN=phucthinhsolar.com`, `SITE_ALIASES=", www.phucthinhsolar.com"` |
| Certificate | Let's Encrypt cho domain chính và `www`, hạn 16/12/2026 |

**Stack `/srv/phucthinhsolar` là production.** `www` được 301 về non-www ở tầng Caddy.
Tại thời điểm kiểm tra 2026-11-04, `staging.phucthinhsolar.com` lại đang hoạt động
qua vhost edge Caddy và trỏ tới stack riêng `/srv/phucthinhsolar-staging`. Stack
staging có Compose, DB, PHP, Nuxt, shared data, releases và state riêng; phản hồi
HTTPS có `X-Robots-Tag: noindex, nofollow, noarchive`. Các đoạn lịch sử bên dưới
về việc gỡ staging năm 2026-09-17 không còn phản ánh trạng thái hiện tại.

Khi deploy staging, truyền `PTS_STACK_ROOT=/srv/phucthinhsolar-staging` cho
`backup.sh`, `deploy-release.sh` và `verify-stack.sh`; script mặc định trỏ vào
production. Kiểm tra image hiện tại, backup staging có checksum và rollback point
trước khi chuyển release. Không dùng `scripts/deploy-production.sh` cho staging.
Ngay sau khi container Nuxt/PHP chuyển sang healthy, Caddy có thể cần thêm một
chu kỳ active health probe trước khi public `/` hết 503; kiểm tra log upstream
và thử lại verify sau khi probe báo `host is up`.

Host cũ (`103.82.21.26`) đã được rút phần website ngày 2026-09-17: `public_html`
đã rỗng (đã xóa 28.213 file, 2.713 thư mục) và database `phucthinhs_data` đã
xóa; kiểm tra lại cho kết quả webroot `0` mục và DirectAdmin `0` database.
Mailbox, DirectAdmin account và FTP account vẫn được giữ để không làm gián đoạn
email; xem [cutover-dns-va-host-cu.md](cutover-dns-va-host-cu.md).

---

## 2. Bố cục `/srv/phucthinhsolar`

```text
/srv/phucthinhsolar
├── docker-compose.yml     proxy (Caddy) → nuxt (public SSR) + web (Apache/PHP 8.1 backend) → db (MariaDB 10.11)
├── Caddyfile              TLS + reverse proxy
├── .env                   domain, email ACME, DB name/user, PTS_WEB_IMAGE, PTS_NUXT_IMAGE   [0600]
├── secrets/               [thư mục 0700 root] — xem §6
├── config/config.php      mount read-only vào /var/www/html/libraries/config.php
├── app/                   Dockerfile + php-production.ini + apache-security.conf
├── scripts/               bootstrap, install-stack, install-systemd, deploy-release,
│                          rollback-release, verify-stack, backup, restore-check,
│                          restore-initial, export-runtime-images
├── systemd/               2 service + 2 timer backup
├── releases/<commit>/     release đã giải nén: public_html + frontend Nuxt + migrations + scripts + docs
├── incoming/              gói release/migration đang chờ kiểm checksum và triển khai
├── shared/                DỮ LIỆU SỐNG, mount vào container
│   ├── upload/            dữ liệu upload production  ← KHÔNG TÁI TẠO ĐƯỢC
│   ├── thumbs/            sinh lại được
│   ├── caches/            sinh lại được
│   ├── assets-caches/     sinh lại được
│   ├── watermark/         sinh lại được
│   └── logs/
├── state/                 thư mục tạm của script restore
└── backups/               [0700] 1–2 full backup ứng dụng gần nhất
```

Nguyên tắc: **`shared/upload` và DB là thứ duy nhất không tái tạo được.** Mọi
thứ khác dựng lại từ Git + image.

---

## 3. Lệnh dùng thường xuyên

Tất cả chạy trong `/srv/phucthinhsolar`.

```bash
# Trạng thái các container
docker compose ps

# Public HTML/SSR render ở Nuxt; PHP là backend cho API/Admin/session/media.
# Không dùng PHP template/CSS/JS làm fallback cho public GET.
# Đọc log PHP và Nuxt khi public route/API lỗi:
docker compose logs --tail=100 web
docker compose logs --tail=100 nuxt
docker compose logs --tail=100 proxy

# Log DB
docker compose logs --tail=100 db

# Khởi động lại ứng dụng PHP + Nuxt (không mất DB, không mất upload)
docker compose up -d --force-recreate web nuxt

# HAI LOẠI THAY ĐỔI, HAI LỆNH KHÁC NHAU — nhầm là mất thời gian hoặc báo cáo sai.
#
# 1) Sửa NỘI DUNG file bind-mount (Caddyfile, config/config.php, secrets/*):
#    phải `restart`. `up -d` so sánh spec container; nội dung file đổi mà spec
#    không đổi nên Compose bỏ qua và container vẫn chạy CONFIG CŨ.
docker compose restart proxy
#
# 2) Sửa BIẾN MÔI TRƯỜNG trong .env (SITE_DOMAIN, SITE_ALIASES, RECAPTCHA_*):
#    phải `up -d --force-recreate`. Compose nội suy biến vào container lúc TẠO,
#    nên `restart` giữ nguyên giá trị cũ. Ngày 2026-09-17 tôi đã restart proxy
#    sau khi đổi SITE_DOMAIN và Caddy vẫn chỉ nhận hostname staging — website
#    thật tiếp tục chết thêm mấy phút.
docker compose up -d --force-recreate web nuxt proxy
#
# Luôn kiểm lại bằng admin API của Caddy, đừng tin lệnh đã chạy xong:
docker exec "$(docker compose ps -q proxy)" wget -qO- http://127.0.0.1:2019/config/ | head -c 400

# Kiểm tra tổng thể: container, PHP/Nuxt image, runtime, HTTPS
./scripts/verify-stack.sh

# Xóa cache trang (sau khi deploy file PHP/template)
rm -f shared/caches/cache_*

# Vào shell container PHP hoặc Nuxt
docker exec -it "$(docker compose ps -q web)" bash
docker exec -it "$(docker compose ps -q nuxt)" sh

# Gọi một URL từ trong container (bỏ qua Caddy/TLS — dùng khi DNS chưa trỏ)
docker exec "$(docker compose ps -q web)" curl -sS -o /dev/null -w '%{http_code}\n' http://127.0.0.1/
```

---

## 4. Deploy một release mới

Đường vận hành mặc định cho chủ website là
[deploy production SAFE/FAST một lệnh](deploy-production-owner.md):

```bash
./scripts/deploy-production.sh --safe --confirm-production
# Hoặc chỉ cho thay đổi frontend rủi ro thấp:
./scripts/deploy-production.sh --fast --confirm-production
```

Các lệnh dưới đây là quy trình thủ công để cứu hộ/điều tra.

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

`deploy-release.sh` tự xác minh checksum, build immutable image PHP và Nuxt theo
cùng commit, chuyển Caddy/Compose, chờ cả hai healthcheck và **tự quay lại
image/config trước** nếu một trong hai không healthy. Mỗi release lưu image
current/previous cho cả hai runtime. Nó **không** tự chạy migration và **không**
đổi DNS. Public GET không được fallback sang PHP HTML.

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

Tại lần rà soát ngày 2026-09-29, VPS giữ đúng hai full backup ứng dụng gần nhất:
`20260929T013137Z-full` và `20260929T012822Z-full`; cả hai đã được script backup
xác minh checksum khi tạo. Các bản cũ hơn đã được dọn theo chính sách lưu giữ.

Theo gói hạ tầng hiện hành, CloudFly cũng cung cấp backup/snapshot tự động cho
toàn VPS. Đây là lớp khôi phục sự cố do nhà cung cấp quản lý, tách biệt với
`/srv/phucthinhsolar/backups`; khi kiểm tra định kỳ cần đối chiếu lịch chạy,
retention và khả năng restore trên CloudFly Console.

**Phân biệt các lớp backup:**

```text
WARN: chưa có /usr/local/sbin/pts-backup-offsite; script ứng dụng chưa có uploader
riêng. Cảnh báo này chỉ nói về lớp copy sang nhà cung cấp/object storage độc lập,
không có nghĩa là CloudFly backup/snapshot đang thiếu.
```

CloudFly backup/snapshot là lớp khôi phục toàn VPS do nhà cung cấp quản lý, nằm
ngoài `/srv/phucthinhsolar/backups`; không cần dọn cùng thư mục backup ứng dụng.
Backup ứng dụng vẫn giữ DB + `shared/upload` để phục hồi chọn lọc. Uploader
off-site độc lập (S3/Object Storage/rclone mã hoá) là lớp phòng vệ bổ sung, không
phải điều kiện chặn deploy khi CloudFly backup đang hoạt động.

### Chính sách lưu giữ và dọn an toàn

- `backups/`: chỉ giữ 1–2 bản `*-full` gần nhất đã kiểm checksum; không xóa bản
  mới nhất hoặc bản đang được restore-check.
- `incoming/`: chỉ là vùng staging. Sau khi checksum, deploy và smoke test thành
  công, có thể xóa archive/migration cũ; giữ gói hiện tại và tối đa một gói
  rollback nếu còn cần.
- `releases/`: giữ release đang chạy, release trước đó (theo
  `state/current-image`/`state/previous-image`) và tối đa một release rollback
  thêm. Không xóa release đang được symlink hoặc container tham chiếu.
- `shared/upload/`: dữ liệu sống, không xóa. `thumbs/`, `caches/`, `logs/` và
  `assets-caches/` có thể tái tạo hoặc dọn theo runbook sau khi kiểm tra.
- Docker build cache có thể kiểm tra bằng `docker system df` rồi dọn bằng
  `docker builder prune -af`. Với image, chỉ dọn sau khi xác định current,
  previous và một rollback candidate; không dùng `docker system prune -a --volumes`.

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

## 8. Bảo hiểm cho PHP 8.1 đã EOL

```bash
./scripts/export-runtime-images.sh
```

Xuất bundle image ra file và **copy sang nơi khác**. PHP 8.1 và base Debian đã
hết hỗ trợ; khả năng build lại từ Internet trong vài năm tới không được coi là
đảm bảo. Nâng lên PHP 8.x là đợt riêng, không làm chung với chuyển host.

---

## 9. Việc còn phải làm trên VPS

- [x] Duy trì backup/snapshot tự động của CloudFly theo gói hạ tầng hiện hành;
      định kỳ đối chiếu lịch chạy, retention và restore trên CloudFly Console.
- [ ] Tạo `/usr/local/sbin/pts-backup-offsite` nếu cần thêm một bản copy độc lập
      ngoài CloudFly; đây là lớp bổ sung, không phải điều kiện để coi hệ thống
      hiện đã có backup.
- [x] Xác minh `staging.phucthinhsolar.com` trỏ tới stack staging riêng và
      không được lập chỉ mục (2026-11-04).
- [x] Chạy `export-runtime-images.sh` trên VPS.
- [ ] Mang bundle runtime image sang kho lưu trữ độc lập nếu muốn thêm lớp bảo vệ
      ngoài CloudFly; không đồng nhất mục này với backup/snapshot CloudFly.
- [ ] Chạy smoke test production-safe đầy đủ theo
      [runbook mục 10](vps-cloudfly-runbook.md).
- [x] Tạo user `deploy` có sudo và xác minh ở phiên SSH riêng.
- [x] Cutover domain chính và `www` sang VPS.
- [ ] Cấu hình SMTP Brevo sau khi chủ project cung cấp tài khoản/API credential.
- [ ] Cấu hình reCAPTCHA production nếu tiếp tục dùng form công khai.
- [x] Xóa website và database ở host cũ sau khi chủ project xác nhận; giữ
      mailbox, DirectAdmin account và FTP account.
- [ ] Chuyển mailbox sang dịch vụ mail chuyên dụng trước khi hủy hosting cũ;
      sau đó mới xóa bản ghi `mail`, `MX`, `ftp` và tài khoản còn lại.
- [ ] Gửi lại trang chủ và sitemap trong Google Search Console để Google thu
      thập metadata thương hiệu vừa chuẩn hóa.
