#!/usr/bin/env bash
set -euo pipefail

repo_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
release_root="${1:-$repo_dir/backup/releases}"
release_ref="${2:-HEAD}"
webroot_tree="domains/phucthinhsolar.com/public_html"

cd "$repo_dir"

if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "FAIL: Source tracked đang có thay đổi chưa chốt; không tạo gói release." >&2
    exit 1
fi

release_commit="$(git rev-parse --verify "${release_ref}^{commit}")"
release_short="$(git rev-parse --short=12 "$release_commit")"
release_parent="$(git rev-parse "${release_commit}^" 2>/dev/null || true)"
release_time="$(date -u +%Y%m%dT%H%M%SZ)"
release_dir="$release_root/${release_time}-${release_short}"
archive_name="public_html-${release_short}.tar.gz"
tools_archive_name="deployment-tools-${release_short}.tar.gz"

mkdir -p "$release_dir"

git archive --format=tar.gz --prefix=public_html/ \
    --output="$release_dir/$archive_name" \
    "${release_commit}:${webroot_tree}"

tar -tzf "$release_dir/$archive_name" > "$release_dir/FILES.txt"

git archive --format=tar.gz \
    --output="$release_dir/$tools_archive_name" \
    "$release_commit" \
    migrations scripts design-assets/product-featured-icons/2026-09-05 \
    docs/13-handoffs/2026-09-06-runbook-deploy-directadmin.md

tar -tzf "$release_dir/$tools_archive_name" > "$release_dir/DEPLOYMENT-TOOLS-FILES.txt"

for forbidden in \
    '^public_html/(backup|artifacts|caches|logs|upload|thumbs|watermark)/' \
    '^public_html/libraries/config\.php$' \
    '\.(sql|zip|7z|bak|log|env)$'; do
    if grep -Ei "$forbidden" "$release_dir/FILES.txt"; then
        echo "FAIL: Gói release chứa đường dẫn bị cấm: $forbidden" >&2
        exit 1
    fi
done

cat > "$release_dir/RELEASE.txt" <<EOF
created_at_utc=$release_time
commit=$release_commit
parent_commit=$release_parent
source_tree=$webroot_tree
archive=$archive_name
deployment_tools_archive=$tools_archive_name
configuration=preserve hosting libraries/config.php
runtime_data=preserve hosting upload, thumbs, watermark, caches and logs
legacy_assets=assets/wp-content/uploads included for WordPress media fallback
database=backup and review migrations separately before any schema change
rollback=redeploy the previous checksummed release; restore database only from a verified pre-deploy backup when required
EOF

(
    cd "$release_dir"
    sha256sum "$archive_name" "$tools_archive_name" FILES.txt DEPLOYMENT-TOOLS-FILES.txt RELEASE.txt > SHA256SUMS
    sha256sum -c SHA256SUMS
)

echo "PASS: Release được tạo tại $release_dir"
echo "Commit: $release_commit"
echo "Archive: $release_dir/$archive_name"
echo "Deployment tools: $release_dir/$tools_archive_name"
