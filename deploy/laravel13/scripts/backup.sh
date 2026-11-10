#!/usr/bin/env bash
# Backup của stack pts-laravel13 trên production. Thay cho
# /srv/phucthinhsolar/scripts/backup.sh của bản PHP cũ (đã ngừng chạy 2026-11-10).
#
#   backup.sh --full      DB + kho upload + storage của Laravel
#   backup.sh --db-only   chỉ DB (chạy mỗi 6 giờ, nhẹ)
#
# Không chép runtime/*.env vào backup: file đó chứa mật khẩu, giữ riêng.
set -euo pipefail

stack_root="${PTS_STACK_ROOT:-/srv/pts-laravel13}"
app_dir="$stack_root/app/deploy/laravel13"
mode="${1:---full}"
[ "$(id -u)" -eq 0 ] || { echo "FAIL: chạy bằng root hoặc sudo." >&2; exit 1; }
[ -f "$app_dir/runtime/stack.env" ] || { echo "FAIL: thiếu $app_dir/runtime/stack.env." >&2; exit 1; }
for command in docker flock gzip tar sha256sum find; do
    command -v "$command" >/dev/null 2>&1 || { echo "FAIL: thiếu lệnh $command." >&2; exit 1; }
done
case "$mode" in
    --full) backup_kind=full ;;
    --db-only) backup_kind=db ;;
    *) echo "Usage: $0 [--full|--db-only]" >&2; exit 1 ;;
esac

mkdir -p "$stack_root/state" "$stack_root/backups"
exec 9>"$stack_root/state/operation.lock"
flock -n 9 || { echo "BLOCK: một thao tác deploy/backup/restore khác đang chạy." >&2; exit 1; }

cd "$app_dir"
compose=(docker compose -p pts-laravel13 --env-file runtime/stack.env -f docker-compose.yml -f docker-compose.production.yml)

created_at_utc="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
timestamp="$(date -u +%Y%m%dT%H%M%SZ)-$backup_kind"
tmp_dir="$stack_root/backups/.partial-$timestamp"
final_dir="$stack_root/backups/$timestamp"
umask 077
mkdir "$tmp_dir"
cleanup() { [ ! -d "$tmp_dir" ] || rm -rf "$tmp_dir"; }
trap cleanup EXIT

# Bảng của Laravel đều là InnoDB nên --single-transaction cho bản nhất quán mà
# không khóa ghi; bản PHP cũ phải --lock-all-tables vì còn bảng MyISAM.
"${compose[@]}" exec -T db sh -ec '
  export MYSQL_PWD="$MARIADB_ROOT_PASSWORD"
  exec mariadb-dump --user=root \
    --host=127.0.0.1 --quick --single-transaction --routines --triggers --events --hex-blob \
    --default-character-set=utf8mb4 "$MARIADB_DATABASE"
' | gzip -9 > "$tmp_dir/database.sql.gz"

checksum_files=(database.sql.gz images.txt docker-version.txt MANIFEST.txt)
if [ "$backup_kind" = "full" ]; then
    tar -C "$stack_root" -czf "$tmp_dir/upload.tar.gz" upload
    # storage/app giữ file do Admin tạo (xuất Excel, phiếu in); framework/ và logs/ bỏ qua.
    docker run --rm -v pts-laravel13_app_storage:/s:ro -v "$tmp_dir":/out alpine \
        tar -C /s -czf /out/storage-app.tar.gz app
    checksum_files+=(upload.tar.gz storage-app.tar.gz)
fi
"${compose[@]}" config --images | sort -u > "$tmp_dir/images.txt"
docker version --format 'client={{.Client.Version}} server={{.Server.Version}}' > "$tmp_dir/docker-version.txt"
printf 'created_at_utc=%s\nkind=%s\ndatabase=%s\nupload_source=%s\nsource_revision=%s\n' \
    "$created_at_utc" "$backup_kind" \
    "$(grep -E '^DB_DATABASE=' runtime/stack.env | cut -d= -f2-)" \
    "$([ "$backup_kind" = full ] && printf upload || printf not-included)" \
    "$(grep -E '^PTS_SOURCE_REVISION=' runtime/stack.env | cut -d= -f2-)" > "$tmp_dir/MANIFEST.txt"
(cd "$tmp_dir" && sha256sum "${checksum_files[@]}" > SHA256SUMS && sha256sum -c SHA256SUMS)
mv "$tmp_dir" "$final_dir"
trap - EXIT

# 7 ngày: bản full khoảng 1,3 GB nên giữ lâu hơn là hết đĩa (VPS 116 GB).
retention="${BACKUP_RETENTION_DAYS:-7}"
if ! printf '%s' "$retention" | grep -Eq '^[0-9]+$' || [ "$retention" -lt 3 ]; then
    echo "WARN: BACKUP_RETENTION_DAYS không hợp lệ; không xoay backup." >&2
else
    find "$stack_root/backups" -mindepth 1 -maxdepth 1 -type d -name '20????????T??????Z-*' -mtime "+$retention" -print -exec rm -rf -- {} +
fi

echo "WARN: backup chỉ nằm trên VPS này; chưa có bản sao ngoài máy chủ." >&2
echo "PASS: backup hoàn tất tại $final_dir"
