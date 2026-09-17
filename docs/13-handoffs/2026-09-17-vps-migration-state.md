# Handoff — trạng thái chuyển host sang VPS, 2026-09-17

Điểm vào khi tiếp tục công việc chuyển host. Đọc kèm
[vps-cloudfly-runbook.md](../06-operations/vps-cloudfly-runbook.md) (quy trình)
và [vps-operations.md](../06-operations/vps-operations.md) (vận hành).

---

## 1. Tóm tắt một dòng

Website **đã chạy được trên VPS mới với dữ liệu production thật**, nhưng **chưa
cutover DNS** — host cũ vẫn là bản đang phục vụ khách.

---

## 2. Hai hệ đang song song

| | Host cũ (đang phục vụ khách) | VPS mới (chưa nhận traffic) |
|---|---|---|
| Địa chỉ | `103.82.21.26` | `222.255.182.182` (`pts-prod-01`) |
| Truy cập | **Không SSH.** FTP + phpMyAdmin | `ssh -i ~/.ssh/id_ed25519 root@...` |
| Stack | Apache/PHP 7.4.29, MariaDB 10.4.24 | Docker: Caddy → Apache/PHP 7.4 → MariaDB 10.11 |
| Deploy | `curl -T` từng file, rồi xoá cache | `deploy-release.sh` (checksum + build + healthcheck + auto-rollback) |
| DNS | đang trỏ vào đây | chưa trỏ; `.env` dùng `staging.phucthinhsolar.com` |

Domain/DNS ở **P.A Vietnam**, tách hoàn toàn khỏi hosting VNPT. Đây là tài sản
duy nhất không lấy lại được nếu mất — ưu tiên bảo vệ trên hết.

---

## 3. Đã hoàn thành trên VPS

- Bootstrap Ubuntu 24.04, Docker 29.8.1 / Compose 5.5.1, cây `/srv/phucthinhsolar`.
- Secrets đã tạo; `app_secret`/`app_salt` lấy từ config production cũ.
- Image `phucthinhsolar-web:4238b1221e68` đã build; release đã giải nén.
- **Dữ liệu production thật đã restore**: 54 bảng + 1.125 file upload (852 MB).
- Preflight migration: **READY** — cả 17 migration schema đã áp dụng, gồm
  `036 photo_feed`.
- Smoke: 9 trang chính **200**, admin **302** (redirect login), **0 cảnh báo PHP**.
- Thumbnail sinh theo yêu cầu (WebP) hoạt động.
- **Đã test thật việc chặn thực thi script trong `upload/`** (một trong các điều
  kiện chặn cutover): ghi một file `.php` vào `shared/upload/`, gọi qua HTTP →
  **403**, không thực thi và cũng không serve; file `.txt` đối chiếu → 200 kèm
  nội dung. Đã xoá file test. `AllowOverride All` đang bật nên
  `upload/.htaccess` có hiệu lực, và `pts-security.conf` đã được enable.
  **Host cũ cũng trả 403** với cùng phép thử.
- Timer backup systemd đã bật (DB mỗi 6 giờ, DB+upload ~02:36). Đã chạy thử
  một lần: PASS.
- Bundle runtime image đã xuất (420 MB) làm bảo hiểm cho PHP 7.4 EOL.

## 4. Gói migration — dùng gói nào

Có **hai** gói trong `incoming/`. Đừng lẫn:

| Gói | Nội dung | Dùng? |
|---|---|---|
| `migration-20260917-111507` | 54 bảng, **976** file upload | **KHÔNG.** Đây là backup **local**: 976 = 949 file local + 27 file mới. Dump nén 1,31 MB. |
| `migration-prod-20260917` | 54 bảng, **1.125** file upload | **ĐÚNG.** Dựng từ dump phpMyAdmin production `2026-09-17 09:17` + mirror FTP `upload/` từ production. |

Cách phân biệt nhanh: dump production nén ra **1,46 MB** (bị `table_counter`
99.180 dòng làm phình); dump local chỉ 1,31 MB. Số file upload production là
**1.125**, local là 949.

Nguồn: `/mnt/c/Users/ADMIN/Downloads/phucthinhs_data (1).sql` (7,3 MB,
phpMyAdmin 5.1.3, MariaDB 10.4.24) + `upload/` tải qua FTP. Bản dựng lại nằm ở
`/home/admin01/pts-migration/out/` trên máy dev.

---

## 5. Hai lỗi đã tìm ra khi triển khai thật

Ghi lại vì cả hai đều không thể phát hiện bằng đọc code.

### 5.1 Toàn bộ trang PHP trả 500

```text
PHP Fatal error: Uncaught RuntimeException: Missing required runtime secret:
PTS_DB_PASSWORD_FILE in /var/www/html/libraries/config.php:15
```

Docker Compose (không swarm) bind-mount secret vào `/run/secrets` và **giữ
nguyên owner/mode của file trên host**; `mode:` trong compose bị bỏ qua.
`secrets/README.md` và `install-stack.sh` đang đặt `0600 root`, nhưng Apache
worker chạy bằng `www-data` (uid 33).

Đã sửa: secret của container web → `root:33` mode `0640`; thư mục `secrets/`
vẫn `0700 root` nên bảo vệ trên host không đổi. `db_root_password` giữ
root-only.

### 5.2 Healthcheck không phát hiện ra 5.1 — lỗi nặng hơn

Trong suốt lúc 100% trang trả 500, container vẫn báo `healthy`, vì healthcheck
gọi `robots.txt` — một **file tĩnh**. Hệ quả: `deploy-release.sh` dựa vào trạng
thái này để tự rollback, nên **một release chết hoàn toàn vẫn được coi là thành
công**.

Đã sửa: healthcheck gọi `GET /` và đòi đúng HTTP 200 (`ignore_errors=true` để
đọc được status của phản hồi lỗi). Đã kiểm exit code: `200→0`, `404→1`, `302→1`.

Bẫy kèm theo: lần sửa đầu đặt `start_period: 45s`, rơi ra ngoài vòng chờ 60s
của `deploy-release.sh` → **mọi deploy tốt cũng bị rollback oan**. Đã đồng bộ
`start_period 15s` / `interval 10s` / `retries 6` với vòng chờ nâng lên 120s;
đo thực tế healthy sau **8s**.

**Quy tắc rút ra:** healthcheck phải gọi đúng thứ mà người dùng thật gọi, và
mốc thời gian của nó phải khớp với vòng chờ của script deploy.

---

## 6. Việc còn phải làm, theo thứ tự

1. **Off-site backup** — `backup.sh` đang cảnh báo
   `chưa có /usr/local/sbin/pts-backup-offsite`. Backup nằm cùng máy với dữ liệu
   thì không bảo vệ được trước mất máy. Cần credential S3/Object Storage ở
   **nhà cung cấp khác**; snapshot CloudFly không thay được. Bundle runtime
   image 420 MB cũng cần mang ra ngoài.
2. **`staging.phucthinhsolar.com` → VPS** để Caddy xin cert thật.
   `verify-stack.sh` hiện báo `self-signed certificate`, đúng như dự kiến khi
   chưa có DNS.
3. **Smoke test đầy đủ** theo [runbook mục 10](../06-operations/vps-cloudfly-runbook.md):
   đăng nhập Admin và quyền tài khoản nghiệp vụ, upload WebP/JPG + thumbnail +
   file manager, luồng category → product → cart → checkout, form liên hệ/đại lý
   ghi DB, robots/sitemap/canonical/structured data/Merchant feed, và desktop
   2K + tablet + mobile.
   **Quan trọng:** phải xác nhận đăng nhập Admin bằng mật khẩu hiện có — đó là
   bằng chứng duy nhất rằng `app_secret`/`app_salt` đã lấy đúng từ production cũ
   (hash là `md5(secret + password + salt)`).
4. **User `deploy`** có sudo, kiểm ở **phiên SSH thứ hai**, rồi mới tắt
   password/root login (runbook mục 4).
5. **Cutover DNS** (runbook mục 11): hạ TTL → backup cuối + ngừng nhập liệu trên
   host cũ → đồng bộ delta DB/upload → đổi bản ghi A (**không** đổi nameserver
   cùng lúc) → theo dõi → **giữ host cũ tối thiểu 7 ngày**.
6. **Sau cutover**: đổi mật khẩu FTP `phucthinhs`. Hiện vẫn là mật khẩu cũ vì
   còn cần để deploy lên host cũ. Lưu ý `creator=admin` — tài khoản cấp trên vẫn
   reset được mật khẩu này bất kể đổi gì, nên chuyển host mới là biện pháp thật.
7. **Đợt riêng, không làm chung**: nâng PHP 7.4 → 8.x; cân nhắc đổi 47 bảng
   MyISAM sang InnoDB (thời điểm sạch nhất là lúc restore vào DB rỗng, nhưng có
   rủi ro riêng và phải để chủ project quyết).

---

## 7. Điều kiện KHÔNG được cutover

Lấy từ runbook mục 10, nhắc lại vì đây là hàng rào cuối:

- Còn fatal/warning trên trang public. → **đã kiểm 2026-09-17: sạch**
- `upload/` thực thi được script. → **đã kiểm 2026-09-17: 403, an toàn**
- Preflight PARTIAL. → **đã kiểm 2026-09-17: READY**
- SMTP sai certificate.
- **Backup chưa từng restore thử thành công.**
- Chưa có đường rollback.

---

## 8. Sau cutover — nội dung còn thiếu, đo chính xác 2026-09-17

Suite Playwright chạy trên domain thật: **161 pass / 8 fail**. Tám lỗi là 4 test
× 2 khổ màn hình, **tất cả đều do thiếu nội dung**, không phải lỗi code. Đây là
việc của chủ project trong Admin, không chặn vận hành.

### 8.1 Badge "HOT" trên rail danh mục trang chủ — 0/4

Badge hiện khi danh mục cấp 1 có cờ `major`. Production có **0** danh mục, local
có **4**.

Admin → **Danh mục cấp 1** → tick **"Chủ lực (card lớn trang chủ)"**. Bốn danh
mục local đang đánh dấu:

| id | Danh mục |
|---:|---|
| 20 | Đèn năng lượng mặt trời |
| 19 | Biến tần (Inverter) |
| 27 | Trụ đèn & phụ kiện |
| 28 | Đèn sân vườn |

### 8.2 Popup quảng cáo — có record nhưng đang tắt

`table_photo` type `popup`: production có **1 record, 0 đang Hiển thị**; local
có 1 record đang hiện. Admin → **Popup quảng cáo (hiện khi vào trang chủ)** →
tick "Hiển thị". Đây là quyết định marketing, không tự bật.

### 8.3 Trang chủ chỉ có 20 sản phẩm thay vì ≥24

`sources/index.php:92` lấy 3 nhóm đầu theo thứ tự `numb, id` trong các danh mục
`noibat,hienthi`, mỗi nhóm `limit 10`, và **chỉ lấy sản phẩm có `photo <> ''`**.
Nên con số phụ thuộc hoàn toàn vào việc đã nhập ảnh hay chưa:

| id | Danh mục | Có ảnh + hiển thị | Tổng SP | Hiện trên trang chủ |
|---:|---|---:|---:|---:|
| 20 | Đèn năng lượng mặt trời | 94 | 133 | 10 (đã chạm giới hạn) |
| 19 | Biến tần (Inverter) | **7** | 34 | 7 |
| 21 | Tấm pin năng lượng mặt trời | **3** | 29 | 3 |
| | | | | **20** |

Chỉ cần nhập ảnh cho **4 sản phẩm nữa** thuộc Biến tần hoặc Tấm pin là trang chủ
đạt 24. Nhập tới 10 mỗi nhóm thì đạt 30 (mức tối đa của layout).

Hai danh mục **chưa có ảnh nào**, nên hoàn toàn không xuất hiện trên trang chủ:

| id | Danh mục | Có ảnh | Tổng SP |
|---:|---|---:|---:|
| 27 | Trụ đèn & phụ kiện | **0** | 25 |
| 24 | Hệ thống bơm năng lượng mặt trời | **0** | 26 |

### 8.4 Hai lỗi đã sửa trong đợt này, giờ đã pass

- Ảnh showroom nằm sai bản ghi → `migrations/045`.
- Bản ghi Giải pháp trùng còn sót, URL cũ trả 200 không redirect →
  `migrations/044`.
