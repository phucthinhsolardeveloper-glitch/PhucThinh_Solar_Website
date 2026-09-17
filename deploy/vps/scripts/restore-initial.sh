#!/usr/bin/env bash
set -euo pipefail

stack_root="${PTS_STACK_ROOT:-/srv/phucthinhsolar}"
backup_dir="${1:-}"
confirmation="${2:-}"

[ "$(id -u)" -eq 0 ] || { echo "FAIL: chạy bằng root hoặc sudo." >&2; exit 1; }
[ -n "$backup_dir" ] || { echo "Usage: $0 /path/to/backup INITIAL_EMPTY_DATABASE" >&2; exit 1; }
[ "$confirmation" = "INITIAL_EMPTY_DATABASE" ] || { echo "BLOCK: thiếu xác nhận INITIAL_EMPTY_DATABASE." >&2; exit 1; }
[ -f "$backup_dir/database.sql.gz" ] && [ -f "$backup_dir/upload.tar.gz" ] || { echo "FAIL: backup thiếu DB hoặc upload." >&2; exit 1; }
for command in docker flock gzip tar sha256sum rsync; do
    command -v "$command" >/dev/null 2>&1 || { echo "FAIL: thiếu lệnh $command." >&2; exit 1; }
done
[ -d "$stack_root/state" ] && [ -d "$stack_root/shared/upload" ] || { echo "FAIL: chưa chạy install-stack.sh." >&2; exit 1; }
exec 9>"$stack_root/state/operation.lock"
flock -n 9 || { echo "BLOCK: một thao tác deploy/backup/restore khác đang chạy." >&2; exit 1; }

(cd "$backup_dir" && sha256sum -c SHA256SUMS)
cd "$stack_root"
set -a
. ./.env
set +a
docker compose up -d db

ready=0
for attempt in $(seq 1 40); do
    if docker compose exec -T db sh -ec 'export MYSQL_PWD="$(cat /run/secrets/db_root_password)"; mariadb-admin ping -h127.0.0.1 -uroot --silent' >/dev/null 2>&1; then
        ready=1
        break
    fi
    sleep 2
done
[ "$ready" -eq 1 ] || { echo "FAIL: MariaDB chưa sẵn sàng." >&2; exit 1; }

table_count="$(docker compose exec -T db sh -ec 'export MYSQL_PWD="$(cat /run/secrets/db_root_password)"; mariadb -N -B -uroot information_schema -e "SELECT COUNT(*) FROM tables WHERE table_schema=\"$MARIADB_DATABASE\""')"
[ "$table_count" = "0" ] || { echo "BLOCK: database đích đã có $table_count bảng; script initial không được phép ghi đè." >&2; exit 1; }

if find "$stack_root/shared/upload" -type f -print -quit | grep -q .; then
    echo "BLOCK: shared/upload không rỗng; script initial không được phép trộn dữ liệu." >&2
    exit 1
fi

tmp_upload="$(mktemp -d "$stack_root/state/upload-restore-XXXXXX")"
cleanup() { rm -rf "$tmp_upload"; }
trap cleanup EXIT
if tar -tzf "$backup_dir/upload.tar.gz" | grep -Eq '(^/|(^|/)\.\.(/|$))'; then
    echo "FAIL: archive upload chứa đường dẫn không an toàn." >&2
    exit 1
fi
tar -xzf "$backup_dir/upload.tar.gz" -C "$tmp_upload"
if [ -d "$tmp_upload/upload" ]; then
    upload_root="$tmp_upload/upload"
else
    # Chấp nhận cả gói local legacy có nội dung upload ở ngay gốc archive.
    upload_root="$tmp_upload"
fi
upload_count="$(find "$upload_root" -type f | wc -l)"
[ "$upload_count" -gt 0 ] || { echo "FAIL: archive upload không có file." >&2; exit 1; }

# Chỉ import DB sau khi archive upload đã được kiểm tra và giải nén thành công.
gzip -dc "$backup_dir/database.sql.gz" | docker compose exec -T db sh -ec 'export MYSQL_PWD="$(cat /run/secrets/db_root_password)"; exec mariadb -uroot "$MARIADB_DATABASE"'
rsync -a "$upload_root/" "$stack_root/shared/upload/"
chown -R 33:33 "$stack_root/shared/upload"
chmod -R u=rwX,g=rwX,o=rX "$stack_root/shared/upload"
echo "PASS: đã restore lần đầu vào database rỗng và $upload_count file upload."
