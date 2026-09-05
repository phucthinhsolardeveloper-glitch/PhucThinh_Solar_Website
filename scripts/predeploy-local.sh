#!/usr/bin/env bash
set -euo pipefail

repo_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose_file="$repo_dir/docker-compose.local.yml"
web_container="pts-php74-local"
base_url="http://localhost:8080"

cd "$repo_dir"

# ripgrep không có trên máy deploy (trong Claude Code nó chỉ là shell function
# của phiên tương tác, script non-interactive không thấy). Mọi guard phải dùng
# grep, nếu không script chết giữa chừng và assertion phía sau không chạy.
if grep -n '^\s*rg -q' scripts/*.sh; then
    echo "FAIL: Script deploy còn dùng ripgrep; chuyển sang grep -qF/-qE" >&2
    exit 1
fi

git diff --check
docker compose -f "$compose_file" build web
docker compose -f "$compose_file" up -d web

ready=0
for attempt in $(seq 1 20); do
    if curl -fsS -o /tmp/pts-predeploy-home.html "$base_url/"; then
        ready=1
        break
    fi
    sleep 1
done
if [ "$ready" -ne 1 ]; then
    echo "FAIL: Web local không sẵn sàng sau 20 giây" >&2
    exit 1
fi

docker cp scripts/test-security-hardening.php "$web_container:/tmp/test-security-hardening.php"
docker cp scripts/test-admin-readiness.php "$web_container:/tmp/test-admin-readiness.php"
docker cp scripts/test-product-bulk.php "$web_container:/tmp/test-product-bulk.php"
docker cp scripts/test-product-project-links.php "$web_container:/tmp/test-product-project-links.php"
docker cp scripts/test-product-power-filter.php "$web_container:/tmp/test-product-power-filter.php"
docker cp scripts/test-product-content-blocks.php "$web_container:/tmp/test-product-content-blocks.php"
docker cp scripts/test-cache-performance.php "$web_container:/tmp/test-cache-performance.php"
docker cp scripts/test-admin-transfer-toast.php "$web_container:/tmp/test-admin-transfer-toast.php"
docker cp scripts/test-admin-permission-gate.php "$web_container:/tmp/test-admin-permission-gate.php"
docker cp scripts/check-admin-permission-readiness.php "$web_container:/tmp/check-admin-permission-readiness.php"
docker cp scripts/test-frontend-transfer-toast.php "$web_container:/tmp/test-frontend-transfer-toast.php"
docker cp scripts/test-product-campaign.php "$web_container:/tmp/test-product-campaign.php"
docker cp scripts/test-pdodb-exceptions.php "$web_container:/tmp/test-pdodb-exceptions.php"
docker cp scripts/test-product-pricing-admin-http.php "$web_container:/tmp/test-product-pricing-admin-http.php"
docker cp scripts/test-checkout-atomicity.php "$web_container:/tmp/test-checkout-atomicity.php"
docker cp scripts/test-commerce-transaction.php "$web_container:/tmp/test-commerce-transaction.php"
docker cp scripts/test-public-route-resolver.php "$web_container:/tmp/test-public-route-resolver.php"
docker cp scripts/preflight-commerce-migrations.php "$web_container:/tmp/preflight-commerce-migrations.php"

docker compose -f "$compose_file" exec -T web sh -lc '
set -eu
php -l administrator/api/login.php
php -l administrator/api/status.php
php -l administrator/sources/order.php
php -l administrator/sources/product.php
php -l administrator/sources/product_bulk.php
php -l libraries/class/class.PDODb.php
php -l libraries/admin_product_pricing.php
php -l libraries/admin_product_completeness.php
php -l libraries/admin_security.php
php -l libraries/form_security.php
php -l libraries/login_security.php
php -l libraries/requick.php
php -l libraries/order_security.php
php -l libraries/session_security.php
php -l libraries/public_route_resolver.php
php -l libraries/product_category_urls.php
php -l libraries/product_promo.php
php -l administrator/sources/product_campaign.php
php -l administrator/templates/product_campaign/list_tpl.php
php -l administrator/templates/product_campaign/form_tpl.php
php -l libraries/router.php
php /tmp/test-security-hardening.php
php /tmp/test-admin-readiness.php
php /tmp/test-product-bulk.php
php /tmp/test-product-project-links.php
php /tmp/test-product-power-filter.php
php /tmp/test-product-content-blocks.php
php /tmp/test-cache-performance.php
php /tmp/test-admin-transfer-toast.php
php /tmp/test-admin-permission-gate.php
php /tmp/check-admin-permission-readiness.php
php /tmp/test-frontend-transfer-toast.php
php /tmp/test-product-campaign.php
php /tmp/test-pdodb-exceptions.php
php /tmp/test-product-pricing-admin-http.php
php /tmp/test-checkout-atomicity.php
php /tmp/test-commerce-transaction.php
php /tmp/test-public-route-resolver.php
php /tmp/preflight-commerce-migrations.php
'

redirect_table="$(docker compose -f "$compose_file" exec -T db mariadb -N -B -upts_local -ppts_local_only phucthinhs_local -e "SHOW TABLES LIKE 'table_url_redirect';")"
if [ "$redirect_table" != "table_url_redirect" ]; then
    echo "FAIL: Chưa áp dụng migrations/012_add_public_url_redirects.sql trên DB local" >&2
    exit 1
fi

campaign_table_count="$(docker compose -f "$compose_file" exec -T db mariadb -N -B -upts_local -ppts_local_only information_schema -e "SELECT COUNT(*) FROM TABLES WHERE TABLE_SCHEMA='phucthinhs_local' AND TABLE_NAME IN ('table_product_campaign','table_product_campaign_scope');")"
if [ "$campaign_table_count" != "2" ]; then
    echo "FAIL: Chưa áp dụng migrations/022_add_product_campaigns.sql trên DB local" >&2
    exit 1
fi

node --check domains/phucthinhsolar.com/public_html/administrator/assets/js/apps.js

curl -fsS -L -o /tmp/pts-predeploy-contact.html "$base_url/lien-he"
product_slug="$(docker compose -f "$compose_file" exec -T db mariadb -N -B -upts_local -ppts_local_only phucthinhs_local -e "SELECT slugvi FROM table_product WHERE FIND_IN_SET('hienthi',status) AND slugvi<>'' ORDER BY id DESC LIMIT 1;")"
if [ -z "$product_slug" ]; then
    echo "FAIL: DB local không có sản phẩm hiển thị để smoke" >&2
    exit 1
fi
curl -fsS -L -o /tmp/pts-predeploy-product.html "$base_url/$product_slug"

declare -A content_slugs
for content_type in tin-tuc giai-phap du-an; do
    content_slugs[$content_type]="$(docker compose -f "$compose_file" exec -T db mariadb -N -B -upts_local -ppts_local_only phucthinhs_local -e "SELECT slugvi FROM table_news WHERE type='$content_type' AND FIND_IN_SET('hienthi',status) AND slugvi<>'' ORDER BY id DESC LIMIT 1;")"
    if [ -z "${content_slugs[$content_type]}" ]; then
        echo "FAIL: DB local không có nội dung $content_type hiển thị để smoke" >&2
        exit 1
    fi
done
curl -fsS -L -o /tmp/pts-predeploy-news-detail.html "$base_url/${content_slugs[tin-tuc]}"
curl -fsS -L -o /tmp/pts-predeploy-solution-detail.html "$base_url/${content_slugs[giai-phap]}"
curl -fsS -L -o /tmp/pts-predeploy-project-detail.html "$base_url/${content_slugs[du-an]}"
curl -fsS -L -o /tmp/pts-predeploy-product-filtered.html "$base_url/san-pham/?brand=1&sort=price-asc"
curl -fsS -L -o /tmp/pts-predeploy-search.html "$base_url/tim-kiem?keyword=den"
curl -fsS -L -o /tmp/pts-predeploy-cart.html "$base_url/gio-hang"
curl -fsS -L -o /tmp/pts-predeploy-news.html "$base_url/tin-tuc/"
curl -fsS -L -o /tmp/pts-predeploy-solutions.html "$base_url/giai-phap/"
curl -fsS -L -o /tmp/pts-predeploy-projects.html "$base_url/du-an/"
curl -fsS -o /tmp/pts-predeploy-robots.txt "$base_url/robots.txt"
curl -fsS -o /tmp/pts-predeploy-sitemap.xml "$base_url/sitemap.xml"

public_paths=("/" "/san-pham/" "/giai-phap/" "/du-an/" "/tin-tuc/" "/gioi-thieu/" "/lien-he/")
for public_path in "${public_paths[@]}"; do
    path_key="$(echo "$public_path" | tr '/-' '__')"
    curl -fsS -L -A 'Mozilla/5.0 (X11; Linux x86_64) Chrome/120' -o "/tmp/pts-desktop-${path_key}.html" "$base_url$public_path"
    curl -fsS -L -A 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Mobile Safari/537.36' -o "/tmp/pts-mobile-${path_key}.html" "$base_url$public_path"
    grep -qF '<title>' "/tmp/pts-desktop-${path_key}.html"
    grep -qF '<meta name="viewport"' "/tmp/pts-mobile-${path_key}.html"
done

grep -qF '<title>' /tmp/pts-predeploy-home.html
grep -qF 'pts-brand-wordmark' /tmp/pts-predeploy-home.html
grep -qF '<span class="pts-brand-text">PHÚC THỊNH SOLAR</span>' /tmp/pts-predeploy-home.html
grep -qF '<i class="fas fa-phone-alt"></i> Hotline:' /tmp/pts-predeploy-home.html
grep -qF 'name="form_csrf"' /tmp/pts-predeploy-contact.html
grep -qF 'title-pro-detail' /tmp/pts-predeploy-product.html
grep -qF 'Gọi báo giá' /tmp/pts-predeploy-product.html
grep -qE 'class="pts-card-ask"[^>]*><i class="fas fa-phone-alt"></i>' /tmp/pts-predeploy-product.html
test -s domains/phucthinhsolar.com/public_html/assets/fontawesome512/all.css
test -s domains/phucthinhsolar.com/public_html/assets/fonts/awesome/fa-solid-900.woff2
test -s domains/phucthinhsolar.com/public_html/assets/fonts/awesome/fa-brands-400.woff2
test -s domains/phucthinhsolar.com/public_html/assets/themes/local/fonts/Linearicons/fonts/Linearicons-Free54e9.woff2
if grep -Eq 'class="addcart"|"priceCurrency"|"price"[[:space:]]*:' /tmp/pts-predeploy-product.html; then
    echo "FAIL: Trang sản phẩm còn lộ giá hoặc nút mua khi chế độ báo giá đang bật" >&2
    exit 1
fi
grep -qE '<title>[^<]+</title>' /tmp/pts-predeploy-news-detail.html
grep -qF 'class="box-detail-news"' /tmp/pts-predeploy-news-detail.html
grep -qE '<title>[^<]+</title>' /tmp/pts-predeploy-solution-detail.html
grep -qF 'class="box-detail-news"' /tmp/pts-predeploy-solution-detail.html
grep -qE '<title>[^<]+</title>' /tmp/pts-predeploy-project-detail.html
grep -qF 'id="inf_single_project"' /tmp/pts-predeploy-project-detail.html
grep -qF 'assets/photobox/photobox.css' /tmp/pts-predeploy-project-detail.html
grep -qF 'assets/photobox/photobox.js' /tmp/pts-predeploy-project-detail.html
grep -qF 'assets/fancybox3/jquery.fancybox.css' /tmp/pts-predeploy-project-detail.html
grep -qF 'assets/fancybox3/jquery.fancybox.js' /tmp/pts-predeploy-project-detail.html
grep -qF 'assets/magiczoomplus/magiczoomplus.css' /tmp/pts-predeploy-product.html
grep -qF 'assets/magiczoomplus/magiczoomplus.js' /tmp/pts-predeploy-product.html
grep -qF 'assets/fileuploader/jquery.fileuploader.min.js' /tmp/pts-predeploy-product.html
grep -qF 'assets/comment/comment.js' /tmp/pts-predeploy-product.html
grep -qF 'assets/owlcarousel2/owl.carousel.css' /tmp/pts-predeploy-product.html
grep -qF 'assets/owlcarousel2/owl.carousel.js' /tmp/pts-predeploy-product.html
grep -qF 'assets/css/jssor.css' /tmp/pts-predeploy-home.html
grep -qF 'assets/js/jssor.slider.min.js' /tmp/pts-predeploy-home.html
grep -qF 'assets/owlcarousel2/owl.carousel.css' /tmp/pts-predeploy-home.html
grep -qF 'assets/owlcarousel2/owl.carousel.js' /tmp/pts-predeploy-home.html
grep -qF 'name="twitter:title"' /tmp/pts-predeploy-product-filtered.html
grep -qF 'rel="canonical" href="http://localhost:8080/san-pham/"' /tmp/pts-predeploy-product-filtered.html
grep -qF 'name="robots" content="noindex,nofollow"' /tmp/pts-predeploy-search.html
grep -qF 'name="robots" content="noindex,nofollow"' /tmp/pts-predeploy-cart.html
grep -qF 'pts-card-grid' /tmp/pts-predeploy-news.html
grep -qF 'pts-card-grid' /tmp/pts-predeploy-solutions.html
grep -qF 'pts-card-grid' /tmp/pts-predeploy-projects.html
grep -qE '^Sitemap: https://phucthinhsolar.com/sitemap.xml$' /tmp/pts-predeploy-robots.txt
grep -qF '<urlset ' /tmp/pts-predeploy-sitemap.xml
# Trước đây check này dò một danh sách slug cứng đã lỗi thời: 3/4 slug không còn
# tồn tại và slug còn lại giờ là danh mục thật đang hiển thị, nên guard không còn
# bảo vệ gì. Thay bằng chính bất biến cần giữ: mọi URL trong sitemap phải mở được.
sitemap_urls="$(grep -oE '<loc>[^<]*</loc>' /tmp/pts-predeploy-sitemap.xml | sed 's#</\?loc>##g')"
sitemap_total="$(printf '%s\n' "$sitemap_urls" | grep -c .)"
if [ "$sitemap_total" -lt 1 ]; then
    echo "FAIL: Sitemap không có URL nào" >&2
    exit 1
fi
sitemap_checked=0
sitemap_bad=0
for sitemap_url in $(printf '%s\n' "$sitemap_urls" | awk 'NR%20==1'); do
    sitemap_status="$(curl -s -o /dev/null -w '%{http_code}' -L "$sitemap_url")"
    sitemap_checked=$((sitemap_checked + 1))
    if [ "$sitemap_status" != "200" ]; then
        echo "FAIL: Sitemap công bố URL không mở được ($sitemap_status): $sitemap_url" >&2
        sitemap_bad=$((sitemap_bad + 1))
    fi
done
if [ "$sitemap_bad" -ne 0 ]; then
    echo "FAIL: $sitemap_bad/$sitemap_checked URL sitemap được lấy mẫu bị lỗi" >&2
    exit 1
fi
echo "INFO: Sitemap $sitemap_total URL; đã lấy mẫu $sitemap_checked URL, tất cả trả 200."
if grep -Eq 'href="tuyen-dung"' /tmp/pts-predeploy-home.html; then
    echo "FAIL: Menu public còn liên kết đến trang tuyển dụng chưa hoạt động" >&2
    exit 1
fi

for sensitive_path in administrator/elfinder/debug.log phucthinhsolar_com.sql codientumanhdat.com.zip; do
    sensitive_status="$(curl -sS -o /dev/null -w '%{http_code}' "$base_url/$sensitive_path")"
    if [ "$sensitive_status" != "403" ] && [ "$sensitive_status" != "404" ]; then
        echo "FAIL: File nhạy cảm $sensitive_path trả HTTP $sensitive_status" >&2
        exit 1
    fi
done
for legacy_media_path in \
    wp-content/uploads/2022/04/2-13.png \
    wp-content/uploads/2022/04/2-13-600x576.png \
    wp-content/uploads/2023/07/chung-nhan-1086x1536.jpg; do
    curl -fsS -o /dev/null "$base_url/$legacy_media_path"
done
for upload_media_path in \
    upload/photo/logo-phucthinh-solar-wordmark-2026.png \
    upload/photo/banner4-1-33501.jpg; do
    curl -fsS -o /dev/null "$base_url/$upload_media_path"
done
curl -fsS -D /tmp/pts-predeploy-headers.txt -o /dev/null "$base_url/"
grep -qiE '^X-Content-Type-Options: nosniff' /tmp/pts-predeploy-headers.txt
grep -qiE '^X-Frame-Options: SAMEORIGIN' /tmp/pts-predeploy-headers.txt
grep -qiE '^Referrer-Policy: strict-origin-when-cross-origin' /tmp/pts-predeploy-headers.txt
grep -qiE '^Cache-Control: no-store, no-cache, must-revalidate' /tmp/pts-predeploy-headers.txt
if grep -Eqi '^X-Powered-By:' /tmp/pts-predeploy-headers.txt; then
    echo "FAIL: Response còn lộ X-Powered-By" >&2
    exit 1
fi
curl -fsS -H 'Accept-Encoding: gzip, deflate' -D /tmp/pts-predeploy-static-headers.txt -o /dev/null "$base_url/assets/css/theme-v2.css"
grep -qiE '^Content-Encoding: gzip' /tmp/pts-predeploy-static-headers.txt
grep -qiE '^Cache-Control: .*max-age=' /tmp/pts-predeploy-static-headers.txt

remaining="$(docker compose -f "$compose_file" exec -T db mariadb -N -B -upts_local -ppts_local_only phucthinhs_local -e "SELECT (SELECT COUNT(*) FROM table_product WHERE code LIKE 'CODEX-BULK-%') + (SELECT COUNT(*) FROM table_product_campaign WHERE name LIKE 'CODEX%') + (SELECT COUNT(*) FROM table_user WHERE username LIKE 'codex-rbac-%') + (SELECT COUNT(*) FROM table_permission_group WHERE name LIKE 'CODEX-RBAC-%') + (SELECT COUNT(*) FROM table_user_limit WHERE login_ip LIKE 'form:%');")"
if [ "$remaining" != "0" ]; then
    echo "FAIL: Còn $remaining dòng dữ liệu kiểm thử trong DB local" >&2
    exit 1
fi

route_index_count="$(docker compose -f "$compose_file" exec -T db mariadb -N -B -upts_local -ppts_local_only information_schema -e "SELECT COUNT(DISTINCT CONCAT(TABLE_NAME, ':', INDEX_NAME)) FROM STATISTICS WHERE TABLE_SCHEMA='phucthinhs_local' AND INDEX_NAME IN ('idx_route_type_slug','idx_type_numb_id');")"
if [ "$route_index_count" != "22" ]; then
    echo "FAIL: Migration 007 chưa tạo đủ index route/listing trên DB local" >&2
    exit 1
fi

if docker compose -f "$compose_file" logs --since=15m web 2>&1 | grep -En 'PHP (Warning|Notice|Fatal|Parse)|Uncaught'; then
    echo "FAIL: Log web có lỗi PHP" >&2
    exit 1
fi

# Diễn tập phục hồi bản backup mới nhất. Một bản backup chưa từng restore thử
# thì chưa tính là có backup; kiểm tra tốn khoảng 4 giây nên chạy mỗi lần.
latest_backup="$(ls -1dt "$repo_dir/backup/local"/*/ 2>/dev/null | head -1 || true)"
if [ -n "$latest_backup" ] && [ -f "${latest_backup}database.sql" ]; then
    echo "INFO: Diễn tập phục hồi backup $(basename "$latest_backup")"
    bash "$repo_dir/scripts/test-backup-restore-local.sh" "${latest_backup%/}"
else
    echo "INFO: Chưa có bản backup local nào để diễn tập phục hồi (chạy scripts/backup-local.sh)."
fi

echo "PASS: Pre-deploy local hoàn tất; regression, smoke, log và cleanup đều sạch."
