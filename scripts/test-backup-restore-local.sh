#!/usr/bin/env bash
set -euo pipefail

repo_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose_file="$repo_dir/docker-compose.local.yml"
backup_dir="${1:-}"

if [ -z "$backup_dir" ] || [ ! -d "$backup_dir" ]; then
    echo "Usage: $0 /path/to/backup" >&2
    exit 1
fi

for required_file in database.sql uploads.tar.gz release.txt SHA256SUMS; do
    if [ ! -f "$backup_dir/$required_file" ]; then
        echo "FAIL: Thiếu $required_file" >&2
        exit 1
    fi
done

(
    cd "$backup_dir"
    sha256sum -c SHA256SUMS
)

restore_dir="$(mktemp -d)"
restore_db="pts_restore_check_$$"
cleanup()
{
    docker compose -f "$compose_file" exec -T db sh -lc \
        'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS \`$1\`"' sh "$restore_db" >/dev/null 2>&1 || true
    rm -rf "$restore_dir"
}
trap cleanup EXIT

tar -xzf "$backup_dir/uploads.tar.gz" -C "$restore_dir"

docker compose -f "$compose_file" exec -T db sh -lc \
    'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" -e "CREATE DATABASE \`$1\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"' sh "$restore_db"

docker compose -f "$compose_file" exec -T db sh -lc \
    'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$1"' sh "$restore_db" \
    < "$backup_dir/database.sql"

source_tables="$(docker compose -f "$compose_file" exec -T db sh -lc 'mariadb -N -B -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=\"$MARIADB_DATABASE\""')"
restored_tables="$(docker compose -f "$compose_file" exec -T db sh -lc 'mariadb -N -B -uroot -p"$MARIADB_ROOT_PASSWORD" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=\"$1\""' sh "$restore_db")"

if [ "$source_tables" != "$restored_tables" ] || [ "$restored_tables" = "0" ]; then
    echo "FAIL: Số bảng source=$source_tables, restore=$restored_tables" >&2
    exit 1
fi

source_uploads="$(find "$repo_dir/domains/phucthinhsolar.com/public_html/upload" -type f | wc -l | tr -d ' ')"
restored_uploads="$(find "$restore_dir" -type f | wc -l | tr -d ' ')"
if [ "$source_uploads" != "$restored_uploads" ]; then
    echo "FAIL: Số file upload source=$source_uploads, restore=$restored_uploads" >&2
    exit 1
fi

echo "PASS: Restore local đạt với $restored_tables bảng và $restored_uploads file upload."
