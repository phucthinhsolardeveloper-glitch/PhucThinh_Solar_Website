# Secrets cần tạo trên VPS

Thư mục runtime: `/srv/phucthinhsolar/secrets`, owner `root`, mode `0700` —
đây là lớp bảo vệ chính, trên host chỉ root vào được.

Mode từng file **không** phải `0600`. Docker Compose (không swarm) bind-mount
secret vào `/run/secrets` và **giữ nguyên owner/mode của file trên host**; khai
báo `mode:` trong compose bị bỏ qua. Apache worker chạy bằng `www-data`
(uid 33), nên file `0600 root` làm mọi trang PHP trả 500 với
`Missing required runtime secret` — đã xảy ra thật ngày 2026-09-17.

- Secret cho container web (`db_password`, `app_secret`, `app_salt`,
  `pts_mail_config.php`, hai file ckfinder): `root:33`, mode `0640`.
- `db_root_password` chỉ container db dùng và entrypoint MariaDB đọc bằng
  root: giữ `root:root`, mode `0600`.

`install-stack.sh` tự đặt đúng các quyền này. Không commit, không gửi qua chat
và không đặt trong webroot.

- `db_password`: mật khẩu riêng của user database ứng dụng.
- `db_root_password`: mật khẩu root MariaDB, khác `db_password`.
- `app_secret`, `app_salt`: **chép đúng giá trị từ config production cũ**;
  thay mới sẽ làm mật khẩu legacy không còn xác minh được.
- `pts_mail_config.php`: array SMTP theo `docs/08-integrations/smtp-production.md`.
- `ckfinder_license_name`, `ckfinder_license_key`: để file rỗng nếu chưa có
  license; không dùng giá trị demo.
- `recaptcha_secret`: **secret key** reCAPTCHA v3, lấy đúng từ config production
  cũ. Site key là giá trị công khai và đặt trong `.env` (`RECAPTCHA_SITE_KEY`),
  không phải ở đây. Để file rỗng + `RECAPTCHA_ACTIVE=0` sẽ khiến form
  **fail-open**: nhận mọi lượt gửi mà không kiểm bot — xem chú thích trong
  `docker-compose.yml`.

Tạo DB password mới bằng password manager. Không tái sử dụng mật khẩu hosting
cũ. `app_secret`/`app_salt` là ngoại lệ tương thích dữ liệu, không tự rotate
trong lần chuyển đầu tiên.
