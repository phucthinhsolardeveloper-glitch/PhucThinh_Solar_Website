#!/usr/bin/env bash
set -euo pipefail

backup_dir="${1:-}"
[ -n "$backup_dir" ] || { echo "Usage: $0 /srv/phucthinhsolar/backups/<timestamp>" >&2; exit 1; }
[ -d "$backup_dir" ] || { echo "FAIL: không thấy backup directory." >&2; exit 1; }
command -v openssl >/dev/null 2>&1 || { echo "FAIL: thiếu openssl." >&2; exit 1; }

(cd "$backup_dir" && sha256sum -c SHA256SUMS)
tmp_dir="$(mktemp -d /tmp/pts-restore-check-XXXXXX)"
container="pts-restore-check-$(date +%s)-$$"
cleanup() {
    docker rm -f "$container" >/dev/null 2>&1 || true
    rm -rf "$tmp_dir"
}
trap cleanup EXIT

if tar -tzf "$backup_dir/upload.tar.gz" | grep -Eq '(^/|(^|/)\.\.(/|$))'; then
    echo "FAIL: archive upload chứa đường dẫn không an toàn." >&2
    exit 1
fi
tar -xzf "$backup_dir/upload.tar.gz" -C "$tmp_dir"
if [ -d "$tmp_dir/upload" ]; then
    upload_root="$tmp_dir/upload"
else
    # Backup local cũ được tạo bằng `tar -C upload -cz .`, nên nội dung nằm
    # trực tiếp ở gốc archive. Backup VPS mới có thư mục upload/ bao ngoài.
    upload_root="$tmp_dir"
fi
upload_count="$(find "$upload_root" -type f | wc -l)"
[ "$upload_count" -gt 0 ] || { echo "FAIL: archive upload không có file." >&2; exit 1; }

root_password="$(openssl rand -hex 24)"
docker run -d --name "$container" \
    -e MARIADB_ROOT_PASSWORD="$root_password" \
    -e MARIADB_DATABASE=restore_check \
    mariadb:10.11 >/dev/null

ready=0
for attempt in $(seq 1 40); do
    if docker exec "$container" mariadb-admin ping -h127.0.0.1 -uroot -p"$root_password" --silent >/dev/null 2>&1; then
        ready=1
        break
    fi
    sleep 2
done
[ "$ready" -eq 1 ] || { echo "FAIL: MariaDB restore-check không sẵn sàng." >&2; exit 1; }

gzip -dc "$backup_dir/database.sql.gz" | docker exec -i "$container" mariadb -uroot -p"$root_password" restore_check
table_count="$(docker exec "$container" mariadb -N -B -uroot -p"$root_password" restore_check -e 'SHOW TABLES' | wc -l)"
[ "$table_count" -gt 0 ] || { echo "FAIL: database restore không có bảng." >&2; exit 1; }

echo "PASS: restore rehearsal độc lập — $table_count bảng, $upload_count file upload."
