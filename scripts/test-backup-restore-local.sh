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

# Bản backup phải phục hồi ĐÚNG CHÍNH NÓ. So số file với thư mục upload đang
# chạy là sai hướng: backup là một thời điểm trong quá khứ, khác với hiện tại là
# chuyện bình thường. Tệ hơn, so bằng số lượng có thể pass trong khi nội dung
# khác hẳn — ngày 06/09 backup có 784 file và upload cũng có 784 file, nhưng
# backup không hề chứa 56 ảnh wp-legacy mới thêm, chỉ là việc xoá 82 file sản
# phẩm ngừng bán vừa vặn bù trừ.
restored_uploads="$(find "$restore_dir" -type f | wc -l | tr -d ' ')"
if [ "$restored_uploads" = "0" ]; then
    echo "FAIL: Gói uploads.tar.gz giải nén ra 0 file" >&2
    exit 1
fi

# Dump phải có dữ liệu thật, không phải chỉ có schema rỗng.
for table_check in table_product table_news table_user; do
    rows="$(docker compose -f "$compose_file" exec -T db sh -lc \
        'exec mariadb -N -B -uroot -p"$MARIADB_ROOT_PASSWORD" -e "SELECT COUNT(*) FROM \`$1\`.\`$2\`"' sh "$restore_db" "$table_check" 2>/dev/null || echo 0)"
    rows="$(echo "$rows" | tr -d '[:space:]')"
    if [ -z "$rows" ] || [ "$rows" = "0" ]; then
        echo "FAIL: Bảng $table_check phục hồi ra 0 dòng — dump có thể chỉ có schema" >&2
        exit 1
    fi
    echo "  $table_check: $rows dòng"
done

# Chênh lệch so với thư mục đang chạy chỉ là thông tin cho người vận hành biết
# bản backup cũ tới đâu, không phải điều kiện pass/fail.
#
# Phải đọc upload/ TRONG CONTAINER: đó là named volume pts_web_upload mà
# website thật sự phục vụ. Thư mục upload trên host chỉ là bản cũ từ lúc build
# image; so với nó sẽ ra kết quả sai lệch hoàn toàn.
live_list="$(mktemp)"
restored_list="$(mktemp)"
if docker compose -f "$compose_file" exec -T web sh -lc 'cd /var/www/html/upload && find . -type f' 2>/dev/null | sort > "$live_list" && [ -s "$live_list" ]; then
    (cd "$restore_dir" && find . -type f | sort) > "$restored_list"
    only_live="$(comm -23 "$live_list" "$restored_list" | wc -l | tr -d ' ')"
    only_backup="$(comm -13 "$live_list" "$restored_list" | wc -l | tr -d ' ')"
    echo "  INFO: so với upload đang chạy — $only_live file mới chưa có trong backup, $only_backup file backup có mà hiện không còn."
fi
rm -f "$live_list" "$restored_list"

echo "PASS: Restore local đạt với $restored_tables bảng và $restored_uploads file upload."
