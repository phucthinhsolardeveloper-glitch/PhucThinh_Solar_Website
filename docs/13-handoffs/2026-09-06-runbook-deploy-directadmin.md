# Runbook deploy DirectAdmin không có SSH

Áp dụng cho nhánh `feature/ecommerce-upgrade`. Production chưa được thay đổi bởi
các bước local trong repository. Dữ liệu sản phẩm chưa có ảnh/nội dung/thông số
thật là trạng thái được chủ project chấp nhận, không phải lỗi deploy và không
được tự sinh dữ liệu để lấp.

Làm đúng thứ tự. Gặp `FAIL`, `BLOCK`, `PARTIAL` hoặc kết quả khác dự kiến thì
dừng, giữ nguyên backup và không chạy tiếp migration.

## 0. Chuẩn bị và backup

1. Trên máy phát triển, chạy đầy đủ kiểm tra local và trình duyệt:

   ```bash
   ./scripts/predeploy-local.sh
   cd tools/visual-tests
   PLAYWRIGHT_BROWSERS_PATH=.browsers npx playwright test --workers=1
   ```

2. Chỉ khi cả hai lệnh đều đạt, tạo commit sạch rồi tạo gói release:

   ```bash
   ./scripts/package-hosting-release.sh
   ./scripts/package-oriana-media.sh    # chỉ cần khi triển khai ảnh ORIANA
   ```

3. Trong thư mục release, chạy `sha256sum -c SHA256SUMS`.
4. DirectAdmin → Create/Restore Backups: backup file + database và tải về máy.
5. phpMyAdmin → Export: tải thêm một bản SQL độc lập.
6. Ghi lại commit/release hiện tại để rollback code.

Không tiếp tục nếu chưa có đủ backup code, DB và `upload/` có thể phục hồi.

## 1. Hai gói triển khai

Mỗi release code có:

- `public_html-<commit>.tar.gz`: webroot, đã loại `libraries/config.php` và các
  thư mục runtime.
- `deployment-tools-<commit>.tar.gz`: `migrations/`, `scripts/`, icon nguồn và
  runbook; giải nén ở `/home/<user>/`, ngoài public webroot.

Không xóa hoặc ghi đè `libraries/config.php`, `upload/`, `thumbs/`, `watermark/`,
`caches/`, `logs/`. Nên giải nén webroot vào thư mục staging, chép file config và
runtime hiện hữu sang đó, rồi đổi thư mục/cutover theo khả năng của hosting.

Nếu buộc phải upload đè, chỉ đè file code trong archive; không xóa toàn bộ
`public_html` trước. Xác minh lại checksum sau khi upload nếu DirectAdmin hỗ trợ.

## 2. Kiểm tra runtime trước khi đổi traffic

Tạo Cron chạy một lần và nhận toàn bộ output qua email:

```text
/usr/local/bin/php /home/<user>/migrations/000_check_hosting_runtime.php --require-production
```

Nếu hosting dùng `/usr/bin/php`, thay đường dẫn trên. Kết quả phải là `READY`.
Script chỉ báo trạng thái, không in host/email/mật khẩu. Nó kiểm tra PHP 7.4,
extension cần thiết, quyền ghi thư mục runtime, RBAC, hai cờ debug, cấu hình SMTP
và ít nhất một phương thức thanh toán đang hiển thị.

`libraries/config.php` trên hosting phải có:

```php
'error-reporting' => false,
'debug-developer' => false,
```

File config local đã được đặt mặc định an toàn và Docker tự bật lại hai cờ để
test. `config.php` chứa bí mật nên không nằm trong Git/release; vì vậy luôn phải
kiểm tra trực tiếp file đang chạy trên hosting.

SMTP dùng `$config['mail']`; không còn lấy transport/password/recipient từ
`table_setting.options`. Tạo file secret `0600` tại
`/home/<directadmin-user>/.pts-mail.php`; source tự nạp file này theo
`docs/08-integrations/smtp-production.md`. Host `mail.phucthinhsolar.com`, port
`587` dùng STARTTLS (`tls`), sender `website@phucthinhsolar.com`, recipient
`phucthinhsolar.hr@gmail.com`. Handshake ngày 2026-09-07 nhận certificate
self-signed `CN=localhost`; phải yêu cầu hosting cài certificate hợp lệ cho mail
hostname hoặc cung cấp hostname SMTP hợp lệ trước khi authenticate/gửi test.
Không cấu hình certificate bypass.

Nếu CKFinder có license production, khai báo `CKFINDER_LICENSE_NAME` và
`CKFINDER_LICENSE_KEY` bằng constant trong config ngoài Git hoặc environment
của hosting. Không chép giá trị license vào source, archive, log hay tài liệu.

Chạy lần lượt `scripts/smtp-readiness.php`, thêm `--connect`, rồi
`--send-test`. SMTP chỉ được coi là hoàn tất sau khi mail test và form liên hệ
thật được nhận. Không chép credential vào log, tài liệu hoặc chat.

Nếu website chạy sau Cloudflare/reverse proxy, proxy phải chuyển đúng
`X-Forwarded-Proto`. Ứng dụng nhận token đầu tiên của header này để sinh
`configBase`, OpenGraph và canonical theo HTTPS, đồng thời không ghép cổng HTTP
của backend vào URL public. Sau cutover, kiểm tra HTML không còn base/canonical
`http://` trên trang HTTPS. Việc tin IP khách từ `X-Forwarded-For` vẫn là cơ chế
riêng và chỉ bật cho đúng IP trong `login.trusted-proxies`.

## 3. Preflight schema rồi mới chọn migration

Chạy Cron chỉ đọc:

```text
/usr/local/bin/php /home/<user>/migrations/000_preflight_production.php
```

- `READY`: schema đã đủ, không chạy lại SQL.
- `MISSING`: chỉ chạy đúng các số được báo, theo thứ tự tăng dần.
- `PARTIAL` hoặc `UNSAFE`: dừng và đối chiếu schema/backup, không cố chạy tiếp.

Danh mục migration schema đầy đủ là:

```text
001, 003, 004, 007, 008, 009, 010, 011, 012,
013, 016, 017, 021, 022, 026, 029
```

Mở đúng file SQL trong `migrations/` và chạy qua phpMyAdmin. Không suy đoán từ
danh sách cũ. Đặc biệt:

- `017` dùng `MODIFY COLUMN`, không có guard `IF NOT EXISTS`; chỉ chạy khi
  preflight báo thiếu.
- `026` và `029` thêm index trực tiếp; chỉ chạy khi thiếu. `026` nên chạy lúc
  ít khách vì có thể khóa bảng.
- `008` đổi bảng commerce sang InnoDB; chỉ chạy khi preflight không báo orphan,
  và nên tạm ngừng checkout trong lúc ALTER.
- Sau mỗi SQL, chạy lại preflight. Chỉ tiếp tục khi không còn `PARTIAL/UNSAFE`.

## 4. Migration dữ liệu: luôn dry-run, không chạy theo quán tính

Các file PHP dữ liệu/quyền không thể suy ra trạng thái chỉ bằng schema:

```text
002, 005, 006, 014, 015, 018, 019, 020, 023, 024, 027, 028, 031, 032
```

Chạy từng file không có `--apply`, lưu output và đọc phạm vi tác động. Chỉ thêm
`--apply` cho đúng file có kết quả mong muốn và đã được chủ project duyệt.

- `002`, `005`, `006`: backfill/chuẩn hóa dữ liệu cũ; không apply khi chưa có
  nguồn dữ liệu và phê duyệt riêng. Việc thiếu nội dung thật hiện tại là chủ đích.
- `014`: chỉ báo cáo.
- `018`–`020`: sửa quan hệ/slug; phải xem dữ liệu production thật.
- `023`: xóa mã hàng đã nghỉ; cần xác nhận danh sách.
- `024`: chuyển media WordPress; không xóa cây media cũ trước khi xác minh URL.
- `028`: giữ bản SQL các dòng sắp xóa cùng backup trước khi apply.
- `031`: chỉ cấp quyền Liên hệ/Khối nội dung cho nhóm đang có ít nhất 90% danh
  mục quyền hợp lệ cũ; đọc kỹ danh sách nhóm được chọn ở dry-run trước khi apply.
- `032`: chỉ đổi hai link banner legacy khớp chính xác sang popup Liên hệ/Đại
  lý; dry-run phải chỉ ra đúng banner mong muốn trước khi apply.
- `025`: tối ưu ảnh tùy chọn, không phải điều kiện deploy. Script dry-run đã chặn
  ảnh vượt 25 MP để tránh hết RAM; chỉ apply khi có backup ảnh riêng và chỉ định
  `--backup-dir` hợp lệ trên hosting.

## 5. Icon danh mục nổi bật

Script ánh xạ 12 icon theo slug danh mục, không theo ID. Nó chỉ điền ô ảnh trống
hoặc phục hồi file khi DB đã tham chiếu đúng tên; gặp ảnh khác sẽ `PRESERVE`,
không ghi đè lựa chọn riêng của quản trị viên.

```text
/usr/local/bin/php /home/<user>/migrations/030_install_featured_category_icons.php
/usr/local/bin/php /home/<user>/migrations/030_install_featured_category_icons.php --apply
```

Chỉ chạy dòng `--apply` sau khi dry-run sạch. Sau đó chạy lại dry-run; dự kiến
`Đã đúng: 12`, `sẽ cài/khôi phục: 0`, không có `ERROR`.

## 6. Ảnh sản phẩm ORIANA (tác vụ dữ liệu riêng)

Không chép nguyên `upload/` local lên production. ID giữa hai DB có thể khác.
Gói media chứa 102 file nguồn / 98 nội dung duy nhất và importer ghép bằng mã
sản phẩm (`code`). Importer không ghi đè ảnh đại diện đã có và dừng trước khi
ghi nếu mapping xung đột.

1. Upload media release ngoài public webroot, chạy `sha256sum -c SHA256SUMS`.
2. Giải nén archive vào `oriana-source/`.
3. Dry-run:

   ```text
   /usr/local/bin/php import-oriana-images.php /home/<user>/oriana-source --public-html=/home/<user>/domains/phucthinhsolar.com/public_html
   ```

4. Chỉ khi toàn bộ mã khớp duy nhất, không có conflict và backup đủ, chạy:

   ```text
   /usr/local/bin/php import-oriana-images.php /home/<user>/oriana-source --public-html=/home/<user>/domains/phucthinhsolar.com/public_html --apply --confirm-production=IMPORT-ORIANA
   ```

5. Chạy dry-run lần nữa; số pending phải bằng 0. Kiểm tra ngẫu nhiên ảnh chính,
   gallery và trang chi tiết theo mã sản phẩm.

## 7. RBAC, cache và smoke test

Trước khi cho nhân viên sử dụng, chạy
`scripts/check-admin-permission-readiness.php` trên production. Role 3 vẫn có
quyền quản trị; tài khoản thường phải thuộc nhóm quyền đang bật và không rỗng.

Xóa nội dung `public_html/caches/` nhưng giữ thư mục. Kiểm tra:

- Trang chủ desktop/mobile và đủ 12 ô danh mục nổi bật.
- Danh mục → chi tiết sản phẩm → form tư vấn/báo giá.
- Tìm kiếm, lọc sản phẩm, breadcrumb và ảnh/gallery.
- Trên HTTPS: `<base>`, OpenGraph và canonical đều dùng `https://`, không mang
  cổng HTTP nội bộ của reverse proxy.
- Admin: đăng nhập role 3; cuộn sidebar rồi mở `Quản lý SEO page → Liên hệ`,
  xác nhận nhánh vẫn mở và mục vừa bấm không nhảy về đầu; lặp lại trên mobile.
- Admin: một tài khoản hạn chế chỉ thấy đúng quyền; các trạng thái yêu cầu tư
  vấn/đại lý có màu phân biệt và cập nhật nhanh được.
- Gửi thật form Liên hệ, đăng ký đại lý và tư vấn sản phẩm; xác nhận có toast,
  dữ liệu vào đúng màn Admin và email được nhận.
- Nếu đang bật bán hàng: danh mục → sản phẩm → giỏ → checkout → xác nhận, kiểm
  tra đúng phương thức thanh toán và không tạo đơn trùng.
- `/account/` trả đúng 404 theo chủ đích hiện tại.
- Log PHP/web không có fatal, warning hoặc credential.

Chỉ mở traffic/chạy quảng cáo sau khi smoke sạch.

## 8. Rollback

- Code lỗi: đưa release trước có checksum trở lại; giữ config và runtime hiện tại.
- Sau lần đăng nhập Admin đầu tiên, một số hash MD5 có thể đã được nâng sang
  `password_hash()`. Không rollback `21dfb0a` hoặc quay về source chỉ hiểu MD5;
  nếu phải rollback phần khác, dựng hotfix/release rollback vẫn giữ verifier
  hai định dạng. Nếu bỏ bước này, tài khoản đã nâng hash sẽ không đăng nhập được.
- Không khôi phục file-manager connector trước `a82da6c` hoặc body legacy trước
  `ed5229a`; security fix phải roll-forward kể cả khi rollback tính năng khác.
- Schema/data lỗi: dừng traffic ghi và phục hồi DB từ backup trước deploy; không
  tự sửa tay trên production khi chưa đối soát.
- Media/icon lỗi: phục hồi DB + `upload/` từ cùng mốc backup. Không chỉ hoàn
  nguyên một phía vì sẽ tạo tham chiếu file mồ côi.
- RBAC khóa nhầm nhân viên: role 3 sửa nhóm quyền. Chỉ bật tạm
  `debug-developer` trong tình huống khẩn cấp, giới hạn truy cập, rồi tắt ngay.

Giữ backup và release trước ít nhất đến khi website ổn định, log sạch và chủ
project đã duyệt toàn bộ luồng P0.
