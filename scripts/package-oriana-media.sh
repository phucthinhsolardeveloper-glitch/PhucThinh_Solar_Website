#!/usr/bin/env bash
set -euo pipefail

repo_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
source_dir="${1:-$repo_dir/ĐÈN ORIANA}"
release_root="${2:-$repo_dir/backup/media-releases}"

if ! git -C "$repo_dir" diff --quiet || ! git -C "$repo_dir" diff --cached --quiet; then
    echo "FAIL: Source tracked đang có thay đổi chưa chốt; không tạo media release gắn sai commit." >&2
    exit 1
fi

if [ ! -d "$source_dir" ]; then
    echo "FAIL: Không tìm thấy thư mục nguồn ORIANA: $source_dir" >&2
    exit 1
fi

release_commit="$(git -C "$repo_dir" rev-parse HEAD)"
release_short="$(git -C "$repo_dir" rev-parse --short=12 HEAD)"
release_time="$(date -u +%Y%m%dT%H%M%SZ)"
release_dir="$release_root/${release_time}-${release_short}-oriana"
archive_name="oriana-source-${release_short}.tar.gz"

mkdir -p "$release_dir"
list_file="$(mktemp)"
trap 'rm -f "$list_file"' EXIT

(
    cd "$source_dir"
    find . -type f \( -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.png' -o -iname '*.webp' \) -print0 | sort -z > "$list_file"
)

image_files="$(find "$source_dir" -type f \( -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.png' -o -iname '*.webp' \) -printf '.' | wc -c)"
if [ "$image_files" -ne 102 ]; then
    echo "FAIL: Bộ nguồn phải có đúng 102 file ảnh; hiện có $image_files." >&2
    exit 1
fi

(
    cd "$source_dir"
    xargs -0 sha256sum < "$list_file" > "$release_dir/SOURCE-SHA256SUMS"
)
unique_images="$(awk '{print $1}' "$release_dir/SOURCE-SHA256SUMS" | sort -u | wc -l)"
if [ "$unique_images" -ne 98 ]; then
    echo "FAIL: Bộ nguồn phải có đúng 98 nội dung ảnh duy nhất; hiện có $unique_images." >&2
    exit 1
fi

tar -czf "$release_dir/$archive_name" -C "$source_dir" --null -T "$list_file"
tar -tzf "$release_dir/$archive_name" > "$release_dir/FILES.txt"
cp "$repo_dir/scripts/import-oriana-images-local.php" "$release_dir/import-oriana-images.php"

cat > "$release_dir/README.txt" <<EOF
Gói ảnh ORIANA — commit $release_commit

1. Backup code, database và upload production.
2. Xác minh: sha256sum -c SHA256SUMS
3. Tạo thư mục oriana-source rồi giải nén $archive_name vào đó.
4. Upload import-oriana-images.php cạnh thư mục nguồn hoặc dùng bản trong deployment-tools.
5. Luôn dry-run trước:
   php import-oriana-images.php /duong/dan/oriana-source --public-html=/home/USER/domains/phucthinhsolar.com/public_html
6. Chỉ khi dry-run sạch và đặc tả đã xác nhận:
   php import-oriana-images.php /duong/dan/oriana-source --public-html=/home/USER/domains/phucthinhsolar.com/public_html --apply --confirm-production=IMPORT-ORIANA

Script đối chiếu bằng code sản phẩm, không dùng ID local và không ghi đè ảnh đại diện đang có.
EOF

(
    cd "$release_dir"
    sha256sum "$archive_name" SOURCE-SHA256SUMS FILES.txt import-oriana-images.php README.txt > SHA256SUMS
    sha256sum -c SHA256SUMS
)

echo "PASS: Media release ORIANA tại $release_dir"
echo "Ảnh nguồn: $image_files file / $unique_images nội dung duy nhất"
echo "Archive: $release_dir/$archive_name"
