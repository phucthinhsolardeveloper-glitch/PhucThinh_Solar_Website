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

# upload/ trong container là named volume pts_web_upload, KHÔNG phải thư mục
# host được bind mount. Thư mục host chỉ còn là bản cũ từ lúc build image, nên
# đóng gói nó sẽ ra một backup không chứa dữ liệu đang chạy — ngày 06/09 host có
# 784 file trong khi volume có 952, thiếu đúng những ảnh vừa thêm. Lấy thẳng từ
# container để backup đúng cái website đang phục vụ.
web_container="$(docker compose -f "$compose_file" ps -q web 2>/dev/null || true)"
if [ -n "$web_container" ]; then
    upload_source="named volume pts_web_upload (qua container web)"
    docker compose -f "$compose_file" exec -T web tar -C /var/www/html/upload -cz . \
        > "$backup_dir/uploads.tar.gz"
else
    # Không có container đang chạy thì đành lấy thư mục host và nói rõ trong release.txt.
    upload_source="domains/phucthinhsolar.com/public_html/upload (host - container không chạy)"
    tar -C "$upload_dir" -czf "$backup_dir/uploads.tar.gz" .
fi

{
    echo "created_at=$(date -Iseconds)"
    echo "git_commit=$(git -C "$repo_dir" rev-parse HEAD)"
    echo "database=local"
    echo "upload_source=$upload_source"
    echo "upload_files=$(tar -tzf "$backup_dir/uploads.tar.gz" | grep -vc '/$' || true)"
} > "$backup_dir/release.txt"

(
    cd "$backup_dir"
    sha256sum database.sql uploads.tar.gz release.txt > SHA256SUMS
)

echo "$backup_dir"
