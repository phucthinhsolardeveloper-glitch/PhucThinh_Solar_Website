#!/usr/bin/env bash
set -euo pipefail

source_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
stack_root="${PTS_STACK_ROOT:-/srv/phucthinhsolar}"

if [ "$(id -u)" -ne 0 ]; then
    echo "FAIL: chạy script bằng root hoặc sudo." >&2
    exit 1
fi

for command in docker rsync install; do
    command -v "$command" >/dev/null 2>&1 || { echo "FAIL: thiếu lệnh $command." >&2; exit 1; }
done

install -d -m 0755 "$stack_root" "$stack_root/releases" "$stack_root/shared" "$stack_root/state"
install -d -m 0700 "$stack_root/secrets" "$stack_root/backups"
for directory in upload thumbs caches assets-caches logs watermark; do
    install -d -m 0770 -o 33 -g 33 "$stack_root/shared/$directory"
done

install -m 0644 "$source_dir/docker-compose.yml" "$stack_root/docker-compose.yml"
install -m 0644 "$source_dir/Caddyfile" "$stack_root/Caddyfile"
install -d -m 0755 "$stack_root/config" "$stack_root/app" "$stack_root/scripts" "$stack_root/systemd"
install -m 0644 "$source_dir/config/config.php" "$stack_root/config/config.php"
rsync -a --delete "$source_dir/app/" "$stack_root/app/"
rsync -a --delete "$source_dir/scripts/" "$stack_root/scripts/"
rsync -a --delete "$source_dir/systemd/" "$stack_root/systemd/"

if [ ! -f "$stack_root/.env" ]; then
    install -m 0600 "$source_dir/stack.env.example" "$stack_root/.env"
    echo "INFO: đã tạo $stack_root/.env; phải chỉnh SITE_DOMAIN trước khi chạy stack."
fi

# Chỉ optional license được phép là file rỗng. Secret bắt buộc không tự sinh
# để buộc người vận hành lấy đúng giá trị tương thích từ hệ thống hiện tại.
for optional_secret in ckfinder_license_name ckfinder_license_key recaptcha_secret; do
    if [ ! -e "$stack_root/secrets/$optional_secret" ]; then
        install -m 0600 /dev/null "$stack_root/secrets/$optional_secret"
    fi
done

required_secrets="db_password db_root_password app_secret app_salt pts_mail_config.php"
missing=0
for secret in $required_secrets; do
    if [ ! -s "$stack_root/secrets/$secret" ]; then
        echo "MISSING: $stack_root/secrets/$secret" >&2
        missing=1
    fi
done

if [ "$missing" -ne 0 ]; then
    echo "BLOCK: hạ tầng đã cài nhưng chưa được phép khởi chạy khi thiếu secret." >&2
    exit 2
fi

chmod 0700 "$stack_root/secrets" "$stack_root/backups"
chmod 0600 "$stack_root/.env"

# Docker Compose (không swarm) bind-mount secret vào /run/secrets GIỮ NGUYÊN
# owner/mode của file trên host, và `mode:` trong compose bị bỏ qua. Để mode
# 0600 root thì Apache worker (www-data, uid 33) không đọc được và mọi trang
# PHP trả 500 với "Missing required runtime secret" — đã xảy ra thật ngày
# 2026-09-17. Thư mục vẫn 0700 root nên host vẫn chỉ root vào được; phần bảo
# vệ nằm ở thư mục, không ở mode từng file.
web_secrets="db_password app_secret app_salt pts_mail_config.php ckfinder_license_name ckfinder_license_key recaptcha_secret"
for secret in $web_secrets; do
    [ -e "$stack_root/secrets/$secret" ] || continue
    chown 0:33 "$stack_root/secrets/$secret"
    chmod 0640 "$stack_root/secrets/$secret"
done
# db_root_password chỉ container db dùng, và entrypoint MariaDB đọc bằng root.
chown 0:0 "$stack_root/secrets/db_root_password"
chmod 0600 "$stack_root/secrets/db_root_password"
echo "PASS: stack files đã cài tại $stack_root."
