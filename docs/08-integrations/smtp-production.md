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
SMTP_ENCRYPTION=CHƯA_XÁC_MINH
SMTP_USERNAME=website@phucthinhsolar.com
SMTP_PASSWORD=SECRET_KHÔNG_GHI_VÀO_GIT_DOC_LOG
MAIL_FROM=website@phucthinhsolar.com
MAIL_ADMIN_TO=phucthinhsolar.hr@gmail.com
```

Cần lấy từ DirectAdmin/nhà cung cấp đúng tên chế độ bảo mật SMTP outbound:
`STARTTLS` (explicit TLS) hay `SSL/TLS` (implicit TLS). Không suy ra chế độ chỉ
từ port 587. Trong cấu hình ứng dụng dùng `tls`/`starttls` cho STARTTLS, hoặc
`ssl`/`smtps` cho implicit SSL/TLS. PHP production phải bật extension `openssl`.

## Cấu hình secret ngoài webroot

Tạo `/home/<directadmin-user>/.pts-mail.php`, owner là DirectAdmin user, quyền
`0600`, nội dung:

```php
<?php
return array(
    'enabled' => true,
    'host' => 'mail.phucthinhsolar.com',
    'port' => 587,
    'encryption' => 'REPLACE_WITH_VERIFIED_VALUE',
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
