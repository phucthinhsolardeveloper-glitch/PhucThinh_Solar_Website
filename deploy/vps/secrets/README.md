# Secrets cần tạo trên VPS

Thư mục runtime: `/srv/phucthinhsolar/secrets`, owner `root`, mode `0700`;
mỗi file mode `0600`. Không commit, không gửi qua chat và không đặt trong
webroot.

- `db_password`: mật khẩu riêng của user database ứng dụng.
- `db_root_password`: mật khẩu root MariaDB, khác `db_password`.
- `app_secret`, `app_salt`: **chép đúng giá trị từ config production cũ**;
  thay mới sẽ làm mật khẩu legacy không còn xác minh được.
- `pts_mail_config.php`: array SMTP theo `docs/08-integrations/smtp-production.md`.
- `ckfinder_license_name`, `ckfinder_license_key`: để file rỗng nếu chưa có
  license; không dùng giá trị demo.

Tạo DB password mới bằng password manager. Không tái sử dụng mật khẩu hosting
cũ. `app_secret`/`app_salt` là ngoại lệ tương thích dữ liệu, không tự rotate
trong lần chuyển đầu tiên.
