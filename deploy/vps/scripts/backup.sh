#!/usr/bin/env bash
set -euo pipefail

stack_root="${PTS_STACK_ROOT:-/srv/phucthinhsolar}"
mode="${1:---full}"
[ "$(id -u)" -eq 0 ] || { echo "FAIL: chạy bằng root hoặc sudo." >&2; exit 1; }
[ -f "$stack_root/.env" ] || { echo "FAIL: thiếu $stack_root/.env." >&2; exit 1; }
for command in docker flock gzip tar sha256sum find; do
    command -v "$command" >/dev/null 2>&1 || { echo "FAIL: thiếu lệnh $command." >&2; exit 1; }
done
exec 9>"$stack_root/state/operation.lock"
flock -n 9 || { echo "BLOCK: một thao tác deploy/backup/restore khác đang chạy." >&2; exit 1; }
case "$mode" in
    --full) backup_kind=full ;;
    --db-only) backup_kind=db ;;
    *) echo "Usage: $0 [--full|--db-only]" >&2; exit 1 ;;
esac

cd "$stack_root"
set -a
. ./.env
set +a

created_at_utc="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
timestamp="$(date -u +%Y%m%dT%H%M%SZ)-$backup_kind"
tmp_dir="$stack_root/backups/.partial-$timestamp"
final_dir="$stack_root/backups/$timestamp"
umask 077
mkdir "$tmp_dir"
cleanup() { [ ! -d "$tmp_dir" ] || rm -rf "$tmp_dir"; }
trap cleanup EXIT

# --lock-all-tables tạo snapshot logic nhất quán cho các bảng MyISAM legacy.
# Chạy vào giờ thấp điểm vì thao tác ghi bị khóa trong thời gian dump.
docker compose exec -T db sh -ec '
  export MYSQL_PWD="$(cat /run/secrets/db_root_password)"
  exec mariadb-dump --user=root \
    --host=127.0.0.1 --quick --lock-all-tables --routines --triggers --events --hex-blob \
    --default-character-set=utf8mb4 "$MARIADB_DATABASE"
' | gzip -9 > "$tmp_dir/database.sql.gz"

if [ "$backup_kind" = "full" ]; then
    tar -C "$stack_root/shared" -czf "$tmp_dir/upload.tar.gz" upload
fi
cp "$stack_root/state/current-image" "$tmp_dir/current-image" 2>/dev/null || true
docker compose config --images | sort -u > "$tmp_dir/images.txt"
docker version --format 'client={{.Client.Version}} server={{.Server.Version}}' > "$tmp_dir/docker-version.txt"
printf 'created_at_utc=%s\nkind=%s\ndatabase=%s\nupload_source=%s\n' \
    "$created_at_utc" "$backup_kind" "$DB_NAME" "$([ "$backup_kind" = full ] && printf shared/upload || printf not-included)" > "$tmp_dir/MANIFEST.txt"
checksum_files=(database.sql.gz images.txt docker-version.txt MANIFEST.txt)
[ "$backup_kind" != "full" ] || checksum_files+=(upload.tar.gz)
(cd "$tmp_dir" && sha256sum "${checksum_files[@]}" > SHA256SUMS && sha256sum -c SHA256SUMS)
mv "$tmp_dir" "$final_dir"
trap - EXIT

retention="${BACKUP_RETENTION_DAYS:-14}"
if ! printf '%s' "$retention" | grep -Eq '^[0-9]+$' || [ "$retention" -lt 7 ]; then
    echo "WARN: BACKUP_RETENTION_DAYS không hợp lệ; không xoay backup." >&2
else
    find "$stack_root/backups" -mindepth 1 -maxdepth 1 -type d -name '20????????T??????Z-*' -mtime "+$retention" -print -exec rm -rf -- {} +
fi

if [ -x /usr/local/sbin/pts-backup-offsite ]; then
    /usr/local/sbin/pts-backup-offsite "$final_dir"
else
    echo "WARN: chưa có /usr/local/sbin/pts-backup-offsite; backup hiện chỉ nằm trên VPS." >&2
fi

echo "PASS: backup hoàn tất tại $final_dir"
