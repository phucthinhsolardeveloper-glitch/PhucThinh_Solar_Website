#!/usr/bin/env bash
set -euo pipefail

repo_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose_file="$repo_dir/docker-compose.local.yml"
backup_root="${1:-$repo_dir/backup/local}"
timestamp="$(date +%Y%m%d-%H%M%S)"
backup_dir="$backup_root/$timestamp"
upload_dir="$repo_dir/domains/phucthinhsolar.com/public_html/upload"

mkdir -p "$backup_dir"

docker compose -f "$compose_file" exec -T db sh -lc \
    'exec mariadb-dump --single-transaction --routines --triggers --default-character-set=utf8mb4 -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' \
    > "$backup_dir/database.sql"

tar -C "$upload_dir" -czf "$backup_dir/uploads.tar.gz" .

{
    echo "created_at=$(date -Iseconds)"
    echo "git_commit=$(git -C "$repo_dir" rev-parse HEAD)"
    echo "database=local"
    echo "upload_source=domains/phucthinhsolar.com/public_html/upload"
} > "$backup_dir/release.txt"

(
    cd "$backup_dir"
    sha256sum database.sql uploads.tar.gz release.txt > SHA256SUMS
)

echo "$backup_dir"
