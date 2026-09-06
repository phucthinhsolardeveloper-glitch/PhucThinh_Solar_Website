# Runbook deploy DirectAdmin không có SSH

Áp dụng cho nhánh `feature/ecommerce-upgrade`. Production chưa được thay đổi bởi
các bước local trong repository. Dữ liệu sản phẩm chưa có ảnh/nội dung/thông số
thật là trạng thái được chủ project chấp nhận, không phải lỗi deploy và không
được tự sinh dữ liệu để lấp.

Làm đúng thứ tự. Gặp `FAIL`, `BLOCK`, `PARTIAL` hoặc kết quả khác dự kiến thì
dừng, giữ nguyên backup và không chạy tiếp migration.

## 0. Chuẩn bị và backup

1. Tạo gói từ một commit sạch trên máy phát triển:

   ```bash
   ./scripts/package-hosting-release.sh
   ./scripts/package-oriana-media.sh    # chỉ cần khi triển khai ảnh ORIANA
   ```

2. Trong thư mục release, chạy `sha256sum -c SHA256SUMS`.
3. DirectAdmin → Create/Restore Backups: backup file + database và tải về máy.
4. phpMyAdmin → Export: tải thêm một bản SQL độc lập.
5. Ghi lại commit/release hiện tại để rollback code.

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

SMTP chỉ được coi là hoàn tất sau khi gửi thử form liên hệ và nhận email. Không
chép credential vào log, tài liệu hoặc chat.

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

Các file PHP dữ liệu không thể suy ra trạng thái chỉ bằng schema:

```text
002, 005, 006, 014, 015, 018, 019, 020, 023, 024, 027, 028
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
- Admin: đăng nhập role 3; một tài khoản hạn chế chỉ thấy đúng quyền.
- Gửi form liên hệ thật và nhận email.
- Nếu đang bật bán hàng: danh mục → sản phẩm → giỏ → checkout → xác nhận, kiểm
  tra đúng phương thức thanh toán và không tạo đơn trùng.
- `/account/` trả đúng 404 theo chủ đích hiện tại.
- Log PHP/web không có fatal, warning hoặc credential.

Chỉ mở traffic/chạy quảng cáo sau khi smoke sạch.

## 8. Rollback

- Code lỗi: đưa release trước có checksum trở lại; giữ config và runtime hiện tại.
- Schema/data lỗi: dừng traffic ghi và phục hồi DB từ backup trước deploy; không
  tự sửa tay trên production khi chưa đối soát.
- Media/icon lỗi: phục hồi DB + `upload/` từ cùng mốc backup. Không chỉ hoàn
  nguyên một phía vì sẽ tạo tham chiếu file mồ côi.
- RBAC khóa nhầm nhân viên: role 3 sửa nhóm quyền. Chỉ bật tạm
  `debug-developer` trong tình huống khẩn cấp, giới hạn truy cập, rồi tắt ngay.

Giữ backup và release trước ít nhất đến khi website ổn định, log sạch và chủ
project đã duyệt toàn bộ luồng P0.
