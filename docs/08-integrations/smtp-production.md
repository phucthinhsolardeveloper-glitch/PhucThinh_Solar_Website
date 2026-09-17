# SMTP production — phucthinhsolar.com

Runtime dùng PHPMailer `6.12.0` (nhánh 6.x tương thích PHP 7.4). Bản vendor cũ
`6.1.6` đã được thay vì nằm trong phạm vi `<= 6.4.1` có
[security advisory của upstream](https://github.com/PHPMailer/PHPMailer/security);
không rollback riêng ba file PHPMailer về bản cũ.

## Luồng và địa chỉ

- Authenticated sender/From: `website@phucthinhsolar.com`.
- Notification recipient: `phucthinhsolar.hr@gmail.com`.
- Form có email khách: `Reply-To` là email khách sau validation.
- `phucthinhs@phucthinhsolar.com`: account mặc định/legacy, không dùng làm SMTP
  website và không dùng password DirectAdmin.
- Source Git không chứa SMTP password. Runtime không còn đọc `password_host`,
  `password_gmail`, `password_host_as` hoặc các sender/recipient SMTP cũ trong
  `table_setting.options`.

Các workflow qua cùng wrapper `Email`: liên hệ (admin + xác nhận khách), yêu cầu
tư vấn (admin), đăng ký đại lý (admin), đơn hàng (admin + xác nhận khách), các
handler legacy kích hoạt/reset mật khẩu thành viên và gửi newsletter từ Admin.
Route account hiện không public do thứ tự router hiện hữu; không bật lại trong
vòng SMTP này. Gửi newsletter nhiều người dùng dùng BCC; các recipient không
nhìn thấy địa chỉ của nhau.

Màn `Admin → Thông tin công ty → Hệ thống email` chỉ hiển thị trạng thái,
From/recipient/transport không nhạy cảm và bản đồ workflow. Password không xuất
hiện trong HTML. Admin chỉ soạn nội dung thủ công khi gửi Newsletter; template
tự động hiện được version hóa trong source, chưa có chức năng chỉnh sửa từ DB.

## Giá trị đã xác minh và giá trị còn thiếu

```text
SMTP_HOST=mail.phucthinhsolar.com
SMTP_PORT=587
SMTP_ENCRYPTION=STARTTLS (`tls`)
SMTP_USERNAME=website@phucthinhsolar.com
SMTP_PASSWORD=SECRET_KHÔNG_GHI_VÀO_GIT_DOC_LOG
MAIL_FROM=website@phucthinhsolar.com
MAIL_ADMIN_TO=phucthinhsolar.hr@gmail.com
```

STARTTLS đã được xác minh bằng handshake thật trên port 587. Tuy nhiên tại lần
kiểm tra 2026-09-07, server trả certificate self-signed `CN=localhost`, không
khớp `mail.phucthinhsolar.com`; TLS verification chuẩn vì vậy từ chối kết nối.
Hosting phải cài certificate hợp lệ cho `mail.phucthinhsolar.com` hoặc cung cấp
SMTP hostname chính thức có certificate hợp lệ. Không tắt `verify_peer`,
`verify_peer_name` hoặc bật `allow_self_signed`. PHP production phải bật
extension `openssl`.

## Cấu hình secret ngoài webroot

Tạo `/home/<directadmin-user>/.pts-mail.php`, owner là DirectAdmin user, quyền
`0600`, nội dung:

```php
<?php
return array(
    'enabled' => true,
    'host' => 'mail.phucthinhsolar.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'website@phucthinhsolar.com',
    'password' => 'REPLACE_WITH_MAILBOX_PASSWORD',
    'from' => 'website@phucthinhsolar.com',
    'from_name' => 'Phúc Thịnh Solar',
    'admin_to' => 'phucthinhsolar.hr@gmail.com',
    'timeout' => 10
);
```

Source tự nạp đường dẫn này qua `libraries/mail_config.php`; không cần sửa
`api/config.php`, không cần đổi đường dẫn khi deploy và không cần chép nguyên
`libraries/config.php` local lên hosting.

Phương án đơn giản hơn là đặt trực tiếp array trên vào
`public_html/libraries/config.php`. File này đang bị `.gitignore` và release
archive loại trừ nên code hỗ trợ, nhưng mức cô lập kém hơn file `0600` ngoài
webroot. Không đặt secret trong `api/config.php` hoặc
`administrator/api/config.php`: đó là bootstrap request được Git theo dõi.

Không đưa file `.pts-mail.php` vào `public_html`, archive, backup chia sẻ, log,
docs hoặc chat. File local `<repo>/.pts-mail.php` cũng bị Git ignore và có cùng
cấu trúc để copy riêng khi deploy. Backup bí mật phải mã hóa và giới hạn quyền
truy cập.

## Readiness trên hosting

Chạy qua SSH hoặc DirectAdmin Cron một lần, không tạo URL public:

```text
/usr/local/bin/php /home/<user>/scripts/smtp-readiness.php
/usr/local/bin/php /home/<user>/scripts/smtp-readiness.php --connect
/usr/local/bin/php /home/<user>/scripts/smtp-readiness.php --send-test
```

Ba bước lần lượt kiểm tra field, kết nối/authenticate và gửi đúng một mail tới
`mail.admin_to`. Output chỉ là `PASS/FAIL` và stage; không in host, account,
recipient, password hay raw SMTP error. Sau test phải kiểm tra Inbox/Spam và
header: From đúng mailbox website, DKIM/SPF/DMARC pass nếu hosting cung cấp.

## Hành vi lỗi

- Contact/tư vấn/đại lý/order lưu DB trước; SMTP thất bại không xóa dữ liệu và
  không làm trắng response.
- SMTP lỗi chỉ log mã cấu hình/stage tổng quát, không log exception thô.
- Forgot-password có CSRF và giới hạn 3 yêu cầu/IP/15 phút; nếu SMTP thất bại,
  password cũ được phục hồi có điều kiện để không khóa tài khoản. Mail kích
  hoạt/reset vẫn là workflow đồng bộ và phải test thật trước mở traffic. Thiết
  kế reset bằng token hết hạn và nâng hash tài khoản thành viên là backlog riêng.

---

## 2026-09-17: SMTP production đang KHÔNG hoạt động — và cấu hình được ghi ở trên cũng không thể hoạt động

Phát hiện khi cấu hình mail cho VPS mới. Ba sự thật đo được, không suy đoán:

**1. File cấu hình chưa bao giờ được tạo trên production.**
`loadMailConfig()` tìm theo thứ tự: `$config['mail']` trong `libraries/config.php`,
rồi `~/.pts-mail.php`. Kiểm qua FTP ngày 2026-09-17:

- `~/.pts-mail.php` → FTP trả `550 The file does not exist`.
- `libraries/config.php` (4.131 byte) → **0** lần xuất hiện `$config['mail']`,
  **0** lần xuất hiện chuỗi `mail.phucthinhsolar.com`. Các khoá `host`/`port`/
  `username` trong file đó là cấu hình **database**.

Nên `loadMailConfig()` trả về `array()`, và `class.Email.php:173`
(`if(!isset($config['enabled']) || $config['enabled'] !== true)`) chặn mọi lần
gửi. Hướng dẫn tạo file đã viết ngày 2026-09-07 (commit `7b38d6d`) nhưng
**bước đó chưa từng được thực hiện trên production**.

**2. Máy chủ mail của host cũ dùng certificate tự ký `CN=localhost`.**
Đo từ VPS bằng `openssl s_client -starttls smtp` tới
`mail.phucthinhsolar.com`, cả cổng 587 và 465:

```text
verify error:num=18:self-signed certificate
subject=C = GB, ST = Someprovince, L = Sometown, O = none, OU = none,
        CN = localhost, emailAddress = webmaster@localhost
```

Đây là certificate snakeoil mặc định của Exim/Debian, không phải cert cho
`mail.phucthinhsolar.com`.

**3. App bắt buộc kiểm tra certificate nghiêm ngặt, và không cho gửi không mã hoá.**

- `class.Email.php:140-142` → `verify_peer => true`, `verify_peer_name => true`,
  `allow_self_signed => false`.
- `class.Email.php:179` → `encryption` chỉ nhận `tls` (STARTTLS) hoặc `ssl`
  (SMTPS). **Không có lựa chọn gửi không mã hoá**, nên cũng không thể lách bằng
  `host => localhost`.

### Kết luận

Cấu hình `host => mail.phucthinhsolar.com` ghi ở phần trên tài liệu này **không
thể hoạt động** với chính máy chủ mail đó, dù có tạo `~/.pts-mail.php` đúng như
hướng dẫn. Đợt siết bảo mật SMTP ngày 2026-09-07 đã đặt ra một cấu hình mà hạ
tầng mail hiện có không đáp ứng được.

Đã kiểm chứng trực tiếp: từ VPS, `SMTP->connect()` **OK**, nhưng
`startTLS()` **thất bại**.

### Ảnh hưởng thực tế

Các luồng không gửi được mail: liên hệ, yêu cầu tư vấn, đăng ký đại lý, đơn
hàng, kích hoạt/reset mật khẩu thành viên, newsletter.

**Dữ liệu không mất.** `sources/allpage.php:259` ghi vào `table_contact`
**trước** khi gửi mail, và chỉ báo lỗi cho khách nếu bước ghi DB thất bại. Nên
lead vẫn nằm trong DB; chỉ là không ai được thông báo, phải vào Admin mới thấy.

Tại 2026-09-17, `table_contact` và `table_order` đều có **0 dòng**, nên chưa có
lead nào thực sự bị bỏ sót.

### Cách sửa: dùng SMTP relay có certificate hợp lệ

Việc này sửa **cả hai** vấn đề cùng lúc — mail production đang hỏng, và VPS mới
chưa gửi được mail — nên không cần giải pháp bắc cầu tạm.

Đã đo từ VPS ngày 2026-09-17, cả hai đều thông cổng 587 và cert hợp lệ
(`Verify return code: 0`):

| Nhà cung cấp | Cert | Ghi chú |
|---|---|---|
| `smtp.gmail.com:587` | `CN=smtp.gmail.com`, Google Trust Services | Đơn giản nhất: dùng App Password của `phucthinhsolar.hr@gmail.com`, không cần tài khoản mới. Nhưng From sẽ là địa chỉ Gmail |
| `smtp-relay.brevo.com:587` | Let's Encrypt | Miễn phí 300 thư/ngày, **giữ được** From `website@phucthinhsolar.com` nếu xác thực domain (SPF + DKIM) |

Sau khi chọn, cập nhật SPF. Hiện là `v=spf1 a mx ~all`; với Brevo thêm
`include:spf.brevo.com`, với Gmail thêm `include:_spf.google.com`. Thiếu bước
này thì thư gửi được nhưng dễ vào spam.

Áp dụng cho từng nơi:

- **Host cũ**: tạo `~/.pts-mail.php` (quyền `0600`, owner DirectAdmin user) với
  host/port/username/password của nhà cung cấp mới.
- **VPS**: ghi vào `/srv/phucthinhsolar/secrets/pts_mail_config.php`, rồi
  `chown 0:33` + `chmod 0640` + `docker compose restart web` — xem
  [vps-operations § 6](../06-operations/vps-operations.md).
