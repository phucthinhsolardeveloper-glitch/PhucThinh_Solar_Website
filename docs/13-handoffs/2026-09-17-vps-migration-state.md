# Handoff — trạng thái chuyển host sang VPS, 2026-09-17

Điểm vào khi tiếp tục công việc chuyển host. Đọc kèm
[vps-cloudfly-runbook.md](../06-operations/vps-cloudfly-runbook.md) (quy trình)
và [vps-operations.md](../06-operations/vps-operations.md) (vận hành).

---

## 1. Tóm tắt một dòng

**Đã cutover ngày 2026-09-17.** `phucthinhsolar.com` giờ do VPS
`222.255.182.182` phục vụ, với dữ liệu production thật. Host cũ chỉ còn giữ
`mail` và `ftp`.

---

## 2. Hai hệ đang song song

| | Host cũ (chỉ còn mail/ftp) | VPS — **là production** |
|---|---|---|
| Địa chỉ | `103.82.21.26` | `222.255.182.182` (`pts-prod-01`) |
| Truy cập | **Không SSH.** FTP + phpMyAdmin | `ssh -i ~/.ssh/id_ed25519 root@...` |
| Stack | Apache/PHP 7.4.29, MariaDB 10.4.24 | Docker: Caddy → Apache/PHP 7.4 → MariaDB 10.11 |
| Deploy | — | `deploy-release.sh` (checksum + build + healthcheck + auto-rollback) |
| DNS trỏ tới | chỉ `mail`, `ftp` | `@`, `www` (301 về non-www), `*`, `staging` |

⚠️ `staging.phucthinhsolar.com` trỏ **cùng máy và cùng database** với domain
thật, chỉ khác là bị chặn index. **Ghi trên staging là ghi vào dữ liệu thật.**

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

Đã xong: DNS cutover, cert Let's Encrypt cho 3 hostname, đăng nhập Admin xác
nhận `app_secret`/`app_salt` đúng, smoke 11 trang, responsive 4 khổ, gộp www,
`upload/` không thực thi script, preflight READY.

1. **SMTP** — website **chưa gửi được mail**. Không phải do chuyển host: hỏng từ
   2026-09-07 và cấu hình cũ không thể hoạt động vì máy chủ mail host cũ dùng
   cert tự ký `CN=localhost`. Phải chuyển sang relay có cert hợp lệ
   (Brevo/Gmail), rồi cập nhật SPF. Xem
   [smtp-production.md](../08-integrations/smtp-production.md) mục 2026-09-17.
2. **Theo dõi 24–48 giờ** trước khi huỷ hosting cũ: log web, đơn/form, cert,
   kiểm từ nhiều mạng khác nhau. Đây là đường quay lại duy nhất — chỉ cần đổi
   bản ghi A về `103.82.21.26`.
3. **Nội dung Admin còn thiếu** — xem mục 8, không chặn vận hành.
4. **Sau khi SMTP mới chạy ổn**: dọn `mail`, `ftp`, MX, SPF khỏi host cũ; tải
   backup host cũ về giữ ngoài cả hai nhà cung cấp; rồi huỷ dịch vụ và **đổi mật
   khẩu FTP `phucthinhs`**. Lưu ý `creator=admin` — tài khoản cấp trên vẫn reset
   được mật khẩu này bất kể đổi gì, nên rút hẳn khỏi host cũ mới là biện pháp
   thật.
5. **Off-site backup tự động** — chủ project đã quyết **không làm** ngày
   2026-09-17. Hiện có bản chụp thủ công tại `/home/admin01/pts-migration/out/`
   trên máy dev (checksum OK, và `restore-check.sh` đã PASS trên chính gói này),
   cộng dump gốc trong `Downloads`. Hạn chế: không tự cập nhật.
   `backup.sh` vẫn cảnh báo `chưa có /usr/local/sbin/pts-backup-offsite` — đúng.
6. **User `deploy`** có sudo, kiểm ở **phiên SSH thứ hai**, rồi mới tắt
   password/root login (runbook mục 4).
7. **Đợt riêng, không làm chung**: nâng PHP 7.4 → 8.x; thêm CSRF cho form đăng
   nhập Admin; cân nhắc đổi 47 bảng MyISAM sang InnoDB (phải để chủ project
   quyết).

---

## 7. Điều kiện KHÔNG được cutover

Lấy từ runbook mục 10, nhắc lại vì đây là hàng rào cuối:

- Còn fatal/warning trên trang public. → **đã kiểm 2026-09-17: sạch**
- `upload/` thực thi được script. → **đã kiểm 2026-09-17: 403, an toàn**
- Preflight PARTIAL. → **đã kiểm 2026-09-17: READY**
- SMTP sai certificate. → **vẫn đỏ**: cert mail host cũ là tự ký `CN=localhost`;
  đã quyết chuyển sang relay ngoài. Không chặn website vì mail vốn đã hỏng từ
  trước khi chuyển host.
- **Backup chưa từng restore thử thành công.** → **xanh**: `restore-check.sh`
  PASS trên gói `migration-prod-20260917` (54 bảng, 1.125 file).
- Chưa có đường rollback. → **xanh**: đổi bản ghi A về `103.82.21.26`; host cũ
  còn nguyên dữ liệu.

**Cutover đã thực hiện 2026-09-17** với hiểu biết rõ ràng rằng mục SMTP còn đỏ.

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
