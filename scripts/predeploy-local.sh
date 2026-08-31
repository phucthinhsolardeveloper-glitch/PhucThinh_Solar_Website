#!/usr/bin/env bash
set -euo pipefail

repo_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose_file="$repo_dir/docker-compose.local.yml"
web_container="pts-php74-local"
base_url="http://localhost:8080"

cd "$repo_dir"

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

docker compose -f "$compose_file" exec -T web sh -lc '
set -eu
php -l administrator/api/login.php
php -l administrator/sources/order.php
php -l administrator/sources/product.php
php -l administrator/sources/product_bulk.php
php -l libraries/admin_security.php
php -l libraries/form_security.php
php -l libraries/login_security.php
php -l libraries/order_security.php
php -l libraries/session_security.php
php /tmp/test-security-hardening.php
php /tmp/test-admin-readiness.php
php /tmp/test-product-bulk.php
'

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
    rg -q '<title>' "/tmp/pts-desktop-${path_key}.html"
    rg -q '<meta name="viewport"' "/tmp/pts-mobile-${path_key}.html"
done

rg -q '<title>' /tmp/pts-predeploy-home.html
rg -q 'pts-brand-copy pts-brand-text' /tmp/pts-predeploy-home.html
rg -q '<span class="pts-brand-text">PHÚC THỊNH SOLAR</span>' /tmp/pts-predeploy-home.html
rg -q 'name="form_csrf"' /tmp/pts-predeploy-contact.html
rg -q 'title-pro-detail' /tmp/pts-predeploy-product.html
rg -q 'Gọi báo giá' /tmp/pts-predeploy-product.html
if rg -q 'class="addcart"|"priceCurrency"|"price"[[:space:]]*:' /tmp/pts-predeploy-product.html; then
    echo "FAIL: Trang sản phẩm còn lộ giá hoặc nút mua khi chế độ báo giá đang bật" >&2
    exit 1
fi
rg -q '<title>[^<]+</title>' /tmp/pts-predeploy-news-detail.html
rg -q 'class="box-detail-news"' /tmp/pts-predeploy-news-detail.html
rg -q '<title>[^<]+</title>' /tmp/pts-predeploy-solution-detail.html
rg -q 'class="box-detail-news"' /tmp/pts-predeploy-solution-detail.html
rg -q '<title>[^<]+</title>' /tmp/pts-predeploy-project-detail.html
rg -q 'id="inf_single_project"' /tmp/pts-predeploy-project-detail.html
rg -q 'assets/photobox/photobox.css' /tmp/pts-predeploy-project-detail.html
rg -q 'assets/photobox/photobox.js' /tmp/pts-predeploy-project-detail.html
rg -q 'assets/fancybox3/jquery.fancybox.css' /tmp/pts-predeploy-project-detail.html
rg -q 'assets/fancybox3/jquery.fancybox.js' /tmp/pts-predeploy-project-detail.html
rg -q 'assets/magiczoomplus/magiczoomplus.css' /tmp/pts-predeploy-product.html
rg -q 'assets/magiczoomplus/magiczoomplus.js' /tmp/pts-predeploy-product.html
rg -q 'assets/fileuploader/jquery.fileuploader.min.js' /tmp/pts-predeploy-product.html
rg -q 'assets/comment/comment.js' /tmp/pts-predeploy-product.html
rg -q 'assets/owlcarousel2/owl.carousel.css' /tmp/pts-predeploy-product.html
rg -q 'assets/owlcarousel2/owl.carousel.js' /tmp/pts-predeploy-product.html
if rg -q 'assets/(photobox|magiczoomplus|fileuploader|comment)/' /tmp/pts-predeploy-home.html; then
    echo "FAIL: Trang chủ còn tải asset chỉ dùng cho gallery hoặc chi tiết sản phẩm" >&2
    exit 1
fi
rg -q 'assets/css/jssor.css' /tmp/pts-predeploy-home.html
rg -q 'assets/js/jssor.slider.min.js' /tmp/pts-predeploy-home.html
rg -q 'assets/owlcarousel2/owl.carousel.css' /tmp/pts-predeploy-home.html
rg -q 'assets/owlcarousel2/owl.carousel.js' /tmp/pts-predeploy-home.html
if rg -q 'assets/fancybox3/' /tmp/pts-predeploy-home.html; then
    echo "FAIL: Trang chủ còn tải Fancybox chỉ dùng cho video hoặc chi tiết dự án" >&2
    exit 1
fi
if rg -q 'assets/(css/jssor.css|js/jssor.slider.min.js)' /tmp/pts-predeploy-news.html; then
    echo "FAIL: Trang danh sách tin còn tải Jssor chỉ dùng cho slider trang chủ" >&2
    exit 1
fi
if rg -q 'assets/owlcarousel2/' /tmp/pts-predeploy-news.html; then
    echo "FAIL: Trang danh sách tin còn tải Owl Carousel không được sử dụng" >&2
    exit 1
fi
rg -q 'name="twitter:title"' /tmp/pts-predeploy-product-filtered.html
rg -q 'rel="canonical" href="http://localhost:8080/san-pham/"' /tmp/pts-predeploy-product-filtered.html
rg -q 'name="robots" content="noindex,nofollow"' /tmp/pts-predeploy-search.html
rg -q 'name="robots" content="noindex,nofollow"' /tmp/pts-predeploy-cart.html
rg -q 'pts-card-grid' /tmp/pts-predeploy-news.html
rg -q 'pts-card-grid' /tmp/pts-predeploy-solutions.html
rg -q 'pts-card-grid' /tmp/pts-predeploy-projects.html
rg -q '^Sitemap: https://phucthinhsolar.com/sitemap.xml$' /tmp/pts-predeploy-robots.txt
rg -q '<urlset ' /tmp/pts-predeploy-sitemap.xml
if rg -q '/(bien-tan-huawei|thiet-bi-lap-dat-nang-luong-mat-troi|unistar|tuyen-dung)</loc>' /tmp/pts-predeploy-sitemap.xml; then
    echo "FAIL: Sitemap còn xuất bản URL ẩn hoặc chưa có trang hoạt động" >&2
    exit 1
fi
if rg -q 'href="tuyen-dung"' /tmp/pts-predeploy-home.html; then
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
curl -fsS -D /tmp/pts-predeploy-headers.txt -o /dev/null "$base_url/"
rg -qi '^X-Content-Type-Options: nosniff' /tmp/pts-predeploy-headers.txt
rg -qi '^X-Frame-Options: SAMEORIGIN' /tmp/pts-predeploy-headers.txt
rg -qi '^Referrer-Policy: strict-origin-when-cross-origin' /tmp/pts-predeploy-headers.txt
rg -qi '^Cache-Control: no-store, no-cache, must-revalidate' /tmp/pts-predeploy-headers.txt
if rg -qi '^X-Powered-By:' /tmp/pts-predeploy-headers.txt; then
    echo "FAIL: Response còn lộ X-Powered-By" >&2
    exit 1
fi
curl -fsS -H 'Accept-Encoding: gzip, deflate' -D /tmp/pts-predeploy-static-headers.txt -o /dev/null "$base_url/assets/css/theme-v2.css"
rg -qi '^Content-Encoding: gzip' /tmp/pts-predeploy-static-headers.txt
rg -qi '^Cache-Control: .*max-age=' /tmp/pts-predeploy-static-headers.txt

remaining="$(docker compose -f "$compose_file" exec -T db mariadb -N -B -upts_local -ppts_local_only phucthinhs_local -e "SELECT (SELECT COUNT(*) FROM table_product WHERE code LIKE 'CODEX-BULK-%') + (SELECT COUNT(*) FROM table_user WHERE username LIKE 'codex-rbac-%') + (SELECT COUNT(*) FROM table_permission_group WHERE name LIKE 'CODEX-RBAC-%') + (SELECT COUNT(*) FROM table_user_limit WHERE login_ip LIKE 'form:%');")"
if [ "$remaining" != "0" ]; then
    echo "FAIL: Còn $remaining dòng dữ liệu kiểm thử trong DB local" >&2
    exit 1
fi

if docker compose -f "$compose_file" logs --since=15m web 2>&1 | rg -n 'PHP (Warning|Notice|Fatal|Parse)|Uncaught'; then
    echo "FAIL: Log web có lỗi PHP" >&2
    exit 1
fi

echo "PASS: Pre-deploy local hoàn tất; regression, smoke, log và cleanup đều sạch."
