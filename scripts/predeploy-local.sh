#!/usr/bin/env bash
set -euo pipefail

repo_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
compose_file="$repo_dir/docker-compose.local.yml"
web_container="pts-php74-local"
base_url="http://localhost:8080"

cd "$repo_dir"

# Khi debug-css=false (đúng cấu hình production), toàn bộ <link> CSS được gộp
# vào một file assets/caches/<hash>.css nên đường dẫn CSS gốc (photobox.css,
# fancybox.css...) không còn xuất hiện nguyên văn trong HTML nữa. Kiểm tra cả
# hai trường hợp: link riêng (debug-css=true) hoặc rule đặc trưng có mặt trong
# bundle (debug-css=false), thay vì chỉ grep đường dẫn gốc.
assert_css_shipped() {
    local html_file="$1" literal_path="$2" distinctive_rule="$3"
    if grep -qF "$literal_path" "$html_file"; then
        return 0
    fi
    local bundle_url
    bundle_url="$(grep -oE 'href="[^"]*assets/caches/[a-f0-9]+\.css[^"]*"' "$html_file" | head -1 | sed -E 's/^href="//; s/"$//')"
    if [ -z "$bundle_url" ]; then
        echo "FAIL: Không thấy $literal_path (không link riêng, không có CSS bundle) trong $html_file" >&2
        exit 1
    fi
    local bundle_cache="/tmp/pts-predeploy-bundle-$(basename "${bundle_url%%\?*}")"
    if [ ! -s "$bundle_cache" ]; then
        curl -fsS -L -o "$bundle_cache" "$bundle_url"
    fi
    if ! grep -qF -- "$distinctive_rule" "$bundle_cache"; then
        echo "FAIL: CSS bundle thiếu \"$distinctive_rule\" — $literal_path có thể không được gộp vào bundle" >&2
        exit 1
    fi
}

# Cùng lý do với assert_css_shipped, áp cho JS: 2026-09-21 chuyển debug-js
# sang false (JsMinify::minify() gộp toàn bộ ~24 file JS thành một
# assets/caches/<hash>.js — xem chú thích trong class.JsMinify.php về việc
# KHÔNG bật lại compress() cũ, nó có bug cắt nhầm "//" trong chuỗi URL làm
# hỏng cú pháp). Từ đây đường dẫn JS gốc (photobox.js, fancybox.js...) không
# còn xuất hiện nguyên văn trong HTML.
assert_js_shipped() {
    local html_file="$1" literal_path="$2" distinctive_rule="$3"
    if grep -qF "$literal_path" "$html_file"; then
        return 0
    fi
    local bundle_url
    bundle_url="$(grep -oE 'src="[^"]*assets/caches/[a-f0-9]+\.js[^"]*"' "$html_file" | head -1 | sed -E 's/^src="//; s/"$//')"
    if [ -z "$bundle_url" ]; then
        echo "FAIL: Không thấy $literal_path (không script riêng, không có JS bundle) trong $html_file" >&2
        exit 1
    fi
    local bundle_cache="/tmp/pts-predeploy-bundle-$(basename "${bundle_url%%\?*}")"
    if [ ! -s "$bundle_cache" ]; then
        curl -fsS -L -o "$bundle_cache" "$bundle_url"
    fi
    if ! grep -qF -- "$distinctive_rule" "$bundle_cache"; then
        echo "FAIL: JS bundle thiếu \"$distinctive_rule\" — $literal_path có thể không được gộp vào bundle" >&2
        exit 1
    fi
}

# ripgrep không có trên máy deploy (trong Claude Code nó chỉ là shell function
# của phiên tương tác, script non-interactive không thấy). Mọi guard phải dùng
# grep, nếu không script chết giữa chừng và assertion phía sau không chạy.
if grep -n '^\s*rg -q' scripts/*.sh; then
    echo "FAIL: Script deploy còn dùng ripgrep; chuyển sang grep -qF/-qE" >&2
    exit 1
fi

git diff --check
bash -n scripts/package-hosting-release.sh
bash -n scripts/deploy-production.sh
bash -n scripts/package-oriana-media.sh
if grep -qE '\$auth_users|FM_ROOT_PATH|function[[:space:]]+fm_' domains/phucthinhsolar.com/public_html/administrator/api/component.php; then
    echo "FAIL: component.php còn implementation/credential file manager legacy" >&2
    exit 1
fi
if grep -qE "\['licenseKey'\][[:space:]]*=[[:space:]]*['\"]" domains/phucthinhsolar.com/public_html/administrator/ckfinder/config.php; then
    echo "FAIL: CKFinder license key đang được hard-code" >&2
    exit 1
fi
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
docker cp scripts/test-news-hub.php "$web_container:/tmp/test-news-hub.php"
docker cp scripts/test-product-bulk.php "$web_container:/tmp/test-product-bulk.php"
docker cp scripts/test-product-project-links.php "$web_container:/tmp/test-product-project-links.php"
docker cp scripts/test-product-power-filter.php "$web_container:/tmp/test-product-power-filter.php"
docker cp scripts/test-product-content-blocks.php "$web_container:/tmp/test-product-content-blocks.php"
docker cp scripts/test-product-merchant-feed.php "$web_container:/tmp/test-product-merchant-feed.php"
docker cp scripts/test-product-structured-data.php "$web_container:/tmp/test-product-structured-data.php"
docker cp scripts/test-cache-performance.php "$web_container:/tmp/test-cache-performance.php"
docker cp scripts/test-admin-transfer-toast.php "$web_container:/tmp/test-admin-transfer-toast.php"
docker cp scripts/test-admin-permission-gate.php "$web_container:/tmp/test-admin-permission-gate.php"
docker cp scripts/test-admin-filemanager-security.php "$web_container:/tmp/test-admin-filemanager-security.php"
docker cp scripts/test-admin-password-migration.php "$web_container:/tmp/test-admin-password-migration.php"
docker cp scripts/test-email-tls-security.php "$web_container:/tmp/test-email-tls-security.php"
docker cp scripts/test-email-production-config.php "$web_container:/tmp/test-email-production-config.php"
docker cp scripts/test-public-session-security.php "$web_container:/tmp/test-public-session-security.php"
docker cp scripts/test-admin-button-visibility.php "$web_container:/tmp/test-admin-button-visibility.php"
docker cp scripts/test-admin-crud-lifecycle.php "$web_container:/tmp/test-admin-crud-lifecycle.php"
docker cp scripts/test-public-form-submissions.php "$web_container:/tmp/test-public-form-submissions.php"
docker cp scripts/test-public-lead-and-review.php "$web_container:/tmp/test-public-lead-and-review.php"
docker cp scripts/test-admin-account-management.php "$web_container:/tmp/test-admin-account-management.php"
docker cp scripts/test-permission-catalogue.php "$web_container:/tmp/test-permission-catalogue.php"
docker cp scripts/test-admin-self-profile-integrity.php "$web_container:/tmp/test-admin-self-profile-integrity.php"
docker cp scripts/test-admin-screen-sweep.php "$web_container:/tmp/test-admin-screen-sweep.php"
docker cp scripts/check-admin-permission-readiness.php "$web_container:/tmp/check-admin-permission-readiness.php"
docker cp scripts/test-frontend-transfer-toast.php "$web_container:/tmp/test-frontend-transfer-toast.php"
docker cp scripts/test-product-campaign.php "$web_container:/tmp/test-product-campaign.php"
docker cp scripts/test-pdodb-exceptions.php "$web_container:/tmp/test-pdodb-exceptions.php"
docker cp scripts/test-product-pricing-admin-http.php "$web_container:/tmp/test-product-pricing-admin-http.php"
docker cp scripts/test-checkout-atomicity.php "$web_container:/tmp/test-checkout-atomicity.php"
docker cp scripts/test-commerce-transaction.php "$web_container:/tmp/test-commerce-transaction.php"
docker cp scripts/test-public-route-resolver.php "$web_container:/tmp/test-public-route-resolver.php"
docker cp scripts/test-proxy-https.php "$web_container:/tmp/test-proxy-https.php"
docker cp scripts/import-solution-content-local.php "$web_container:/tmp/import-solution-content-local.php"
docker cp scripts/optimize-webp.php "$web_container:/tmp/optimize-webp.php"
docker cp scripts/preflight-commerce-migrations.php "$web_container:/tmp/preflight-commerce-migrations.php"
docker cp migrations/000_preflight_production.php "$web_container:/tmp/000_preflight_production.php"
docker cp migrations/000_check_hosting_runtime.php "$web_container:/tmp/000_check_hosting_runtime.php"
docker cp migrations/025_compress_oversized_images.php "$web_container:/tmp/025_compress_oversized_images.php"
docker cp migrations/031_grant_contact_content_section_permissions.php "$web_container:/tmp/031_grant_contact_content_section_permissions.php"
docker cp migrations/032_adv_banner_popup_links.php "$web_container:/tmp/032_adv_banner_popup_links.php"
docker cp migrations/037_grant_redesign_page_permissions.php "$web_container:/tmp/037_grant_redesign_page_permissions.php"
docker cp migrations/038_grant_news_hub_permissions.php "$web_container:/tmp/038_grant_news_hub_permissions.php"
docker cp migrations/039_grant_home_and_project_point_permissions.php "$web_container:/tmp/039_grant_home_and_project_point_permissions.php"
docker cp migrations/040_grant_hero_carousel_photo_permission.php "$web_container:/tmp/040_grant_hero_carousel_photo_permission.php"
docker cp migrations/041_grant_home_campaign_photo_permissions.php "$web_container:/tmp/041_grant_home_campaign_photo_permissions.php"
docker cp migrations/042_grant_site_visual_photo_permissions.php "$web_container:/tmp/042_grant_site_visual_photo_permissions.php"
docker cp migrations/043_install_about_showroom_image.php "$web_container:/tmp/043_install_about_showroom_image.php"
docker exec "$web_container" mkdir -p /tmp/assets/043-about-showroom
docker cp migrations/assets/043-about-showroom/. "$web_container:/tmp/assets/043-about-showroom/"
docker cp scripts/import-oriana-images-local.php "$web_container:/tmp/import-oriana-images.php"
docker cp scripts/create-local-rbac-demo-accounts.php "$web_container:/tmp/create-local-rbac-demo-accounts.php"
docker compose -f "$compose_file" exec -T web sh -lc '
set -eu
php -l administrator/api/login.php
php -l administrator/api/status.php
php -l administrator/api/newsletter_confirm_status.php
php -l administrator/index.php
php -l administrator/sources/photo.php
php -l administrator/sources/order.php
php -l administrator/sources/product.php
php -l administrator/sources/product_bulk.php
php -l administrator/sources/user.php
php -l administrator/sources/setting.php
php -l libraries/class/class.PDODb.php
php -l libraries/admin_product_pricing.php
php -l libraries/admin_product_completeness.php
php -l libraries/admin_security.php
php -l administrator/filemanager_bootstrap.php
php -l administrator/ckfinder/config.php
php -l administrator/elfinder/php/connector.php
php -l administrator/elfinder/index.php
php -l libraries/form_security.php
php -l libraries/login_security.php
php -l libraries/requick.php
php -l libraries/order_security.php
php -l libraries/session_security.php
php -l libraries/mail_config.php
php -l libraries/class/class.Email.php
php -l libraries/public_route_resolver.php
php -l libraries/product_category_urls.php
php -l libraries/product_promo.php
php -l libraries/product_merchant_feed.php
php -l libraries/product_structured_data.php
php -l libraries/solution_hub.php
php -l libraries/news_detail_media.php
php -l libraries/config-type.php
php -l libraries/type/config-type-news.php
php -l libraries/type/config-type-photo.php
php -l libraries/type/config-type-product.php
php -l libraries/type/config-type-static.php
php -l administrator/sources/product_campaign.php
php -l administrator/templates/product_campaign/list_tpl.php
php -l administrator/templates/product_campaign/form_tpl.php
php -l administrator/templates/newsletter/man/mans_tpl.php
php -l administrator/templates/layout/header.php
php -l administrator/templates/layout/js.php
php -l administrator/templates/layout/menu.php
php -l administrator/templates/photo/man/photo_add_tpl.php
php -l administrator/templates/photo/man/photo_edit_tpl.php
php -l administrator/templates/user/permission/permission_group_tpl.php
php -l administrator/templates/setting/man/man_add_tpl.php
php -l sources/allpage.php
php -l sources/contact.php
php -l sources/daily.php
php -l sources/index.php
php -l sources/product.php
php -l sources/news_hub.php
php -l sources/solution_hub.php
php -l templates/about/about_tpl.php
php -l templates/contact/contact_tpl.php
php -l templates/daily/daily_tpl.php
php -l templates/account/forgot_password_tpl.php
php -l templates/index/index_tpl.php
php -l templates/layout/css.php
php -l templates/layout/js.php
php -l templates/layout/strucdata.php
php -l templates/layout/menu.php
php -l templates/layout/mmenu.php
php -l templates/layout/modal.php
php -l templates/news/project_cta_tpl.php
php -l templates/news/project_tpl.php
php -l templates/news/service_list_tpl.php
php -l templates/news/service_detail_tpl.php
php -l templates/news/project_detail_tpl.php
php -l templates/product/product_detail_tpl.php
php -l templates/product/product_list_tpl.php
php -l templates/product/product_tpl.php
php -l libraries/router.php
php -l /tmp/import-solution-content-local.php
php -l /tmp/optimize-webp.php
php -l /tmp/000_preflight_production.php
php -l /tmp/000_check_hosting_runtime.php
php -l /tmp/025_compress_oversized_images.php
php -l /tmp/031_grant_contact_content_section_permissions.php
php -l /tmp/032_adv_banner_popup_links.php
php -l /tmp/037_grant_redesign_page_permissions.php
php -l /tmp/038_grant_news_hub_permissions.php
php -l /tmp/039_grant_home_and_project_point_permissions.php
php -l /tmp/040_grant_hero_carousel_photo_permission.php
php -l /tmp/041_grant_home_campaign_photo_permissions.php
php -l /tmp/042_grant_site_visual_photo_permissions.php
php -l /tmp/043_install_about_showroom_image.php
php -r "exit(function_exists(\"imagewebp\") ? 0 : 1);"
cwebp -version
php -l /tmp/import-oriana-images.php
php -l /tmp/create-local-rbac-demo-accounts.php
php -l /tmp/test-admin-self-profile-integrity.php
php -l /tmp/test-admin-permission-gate.php
php -l /tmp/test-admin-filemanager-security.php
php -l /tmp/test-admin-password-migration.php
php -l /tmp/test-email-tls-security.php
php -l /tmp/test-email-production-config.php
php -l /tmp/test-public-session-security.php
php -l /tmp/test-admin-button-visibility.php
php -l /tmp/test-product-merchant-feed.php
php -l /tmp/test-product-structured-data.php
php -l /tmp/test-proxy-https.php
php /tmp/test-security-hardening.php
php /tmp/test-admin-readiness.php
php /tmp/test-news-hub.php
php /tmp/test-product-bulk.php
php /tmp/test-product-project-links.php
php /tmp/test-product-power-filter.php
php /tmp/test-product-content-blocks.php
php /tmp/test-product-merchant-feed.php
php /tmp/test-product-structured-data.php
php /tmp/test-cache-performance.php
php /tmp/test-admin-transfer-toast.php
php /tmp/test-admin-permission-gate.php
php /tmp/test-admin-filemanager-security.php
php /tmp/test-admin-password-migration.php
php /tmp/test-email-tls-security.php
php /tmp/test-email-production-config.php
php /tmp/test-public-session-security.php
php /tmp/test-admin-button-visibility.php
php /tmp/test-admin-crud-lifecycle.php
php /tmp/test-public-form-submissions.php
php /tmp/test-public-lead-and-review.php
php /tmp/test-admin-account-management.php
php /tmp/test-permission-catalogue.php
php /tmp/test-admin-self-profile-integrity.php
php /tmp/test-admin-screen-sweep.php
php /tmp/check-admin-permission-readiness.php
php /tmp/test-frontend-transfer-toast.php
php /tmp/test-product-campaign.php
php /tmp/test-pdodb-exceptions.php
php /tmp/test-product-pricing-admin-http.php
php /tmp/test-checkout-atomicity.php
php /tmp/test-commerce-transaction.php
php /tmp/test-public-route-resolver.php
php /tmp/test-proxy-https.php
php /tmp/preflight-commerce-migrations.php
php /tmp/000_preflight_production.php --require-all
php /tmp/000_check_hosting_runtime.php
php /tmp/031_grant_contact_content_section_permissions.php
php /tmp/032_adv_banner_popup_links.php
php /tmp/037_grant_redesign_page_permissions.php
php /tmp/038_grant_news_hub_permissions.php
php /tmp/039_grant_home_and_project_point_permissions.php
php /tmp/040_grant_hero_carousel_photo_permission.php
php /tmp/041_grant_home_campaign_photo_permissions.php
php /tmp/042_grant_site_visual_photo_permissions.php
php /tmp/043_install_about_showroom_image.php
php /tmp/optimize-webp.php /var/www/html/assets/images/site-visuals --quality=82 --min-bytes=150000 --dry-run
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

content_section_backup_table="$(docker compose -f "$compose_file" exec -T db mariadb -N -B -upts_local -ppts_local_only phucthinhs_local -e "SHOW TABLES LIKE 'table_content_section_backup';")"
if [ "$content_section_backup_table" != "table_content_section_backup" ]; then
    echo "FAIL: Chưa áp dụng migrations/049_add_content_section_backups.sql trên DB local" >&2
    exit 1
fi

pdp_profile_markers="$(docker compose -f "$compose_file" exec -T db mariadb -N -B -upts_local -ppts_local_only information_schema -e "SELECT (SELECT COUNT(*) FROM COLUMNS WHERE TABLE_SCHEMA='phucthinhs_local' AND TABLE_NAME='table_product' AND COLUMN_NAME='pdp_profile') + (SELECT COUNT(*) FROM COLUMNS WHERE TABLE_SCHEMA='phucthinhs_local' AND TABLE_NAME='table_product_list' AND COLUMN_NAME='pdp_profile') + (SELECT COUNT(*) FROM TABLES WHERE TABLE_SCHEMA='phucthinhs_local' AND TABLE_NAME='table_product_document');")"
if [ "$pdp_profile_markers" != "3" ]; then
    echo "FAIL: Chưa áp dụng migrations/050_add_product_pdp_profiles_and_documents.sql trên DB local" >&2
    exit 1
fi

node --check domains/phucthinhsolar.com/public_html/administrator/assets/js/apps.js
node --check domains/phucthinhsolar.com/public_html/administrator/assets/js/sidebar-state.js
node --check domains/phucthinhsolar.com/public_html/assets/js/apps.js
node --check domains/phucthinhsolar.com/public_html/assets/wp-content/themes/local/js/main080f.js

curl -fsS -L -o /tmp/pts-predeploy-contact.html "$base_url/lien-he"
curl -fsS -L -o /tmp/pts-predeploy-dealer.html "$base_url/dai-ly"
curl -fsS -L -o /tmp/pts-predeploy-catalog.html "$base_url/san-pham/"
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
head_status="$(curl -sS -I -o /dev/null -w '%{http_code}' "$base_url/")"
if [ "$head_status" != "200" ]; then
    echo "FAIL: HEAD trang chủ trả HTTP $head_status, cần trả 200 như GET." >&2
    exit 1
fi
grep -qF '<meta property="og:site_name" content="Phúc Thịnh Solar"' /tmp/pts-predeploy-home.html
grep -qF '"@type":"WebSite"' /tmp/pts-predeploy-home.html
grep -qF '"name":"Phúc Thịnh Solar"' /tmp/pts-predeploy-home.html
grep -qF 'class="header pts-header-v3 pts-header-v4"' /tmp/pts-predeploy-home.html
grep -qF 'pts-brand-wordmark' /tmp/pts-predeploy-home.html
grep -qF 'class="hdr-search-form"' /tmp/pts-predeploy-home.html
grep -qF 'class="hdr-call"' /tmp/pts-predeploy-home.html
grep -qF 'id="keyword-res"' /tmp/pts-predeploy-home.html
grep -qF 'class="menu-knowledge"' /tmp/pts-predeploy-home.html
grep -qF 'class="menu-cooperation"' /tmp/pts-predeploy-home.html
grep -qF '>Kiến thức</a>' /tmp/pts-predeploy-home.html
grep -qF '>Hợp tác</a>' /tmp/pts-predeploy-home.html
grep -qF '"default_language": "vi"' /tmp/pts-predeploy-home.html
grep -qF '"detect_browser_language": true' /tmp/pts-predeploy-home.html
assert_css_shipped /tmp/pts-predeploy-home.html 'assets/css/header-v3.css' '.pts-header-v3'
assert_css_shipped /tmp/pts-predeploy-home.html 'assets/css/media-system.css' '.pts-contact-intro-row'
assert_css_shipped /tmp/pts-predeploy-home.html 'assets/css/responsive-architecture.css' '--pts-mobile-gutter'
assert_css_shipped /tmp/pts-predeploy-home.html 'assets/css/commerce-experience.css' '.form-cart .procart-row{display:grid!important'
assert_css_shipped /tmp/pts-predeploy-home.html 'assets/css/typography-system.css' '--pts-type-base'
if grep -qF 'async: false' domains/phucthinhsolar.com/public_html/assets/js/apps.js; then
    echo "FAIL: Add-to-cart vẫn dùng synchronous XHR và khóa giao diện" >&2
    exit 1
fi
grep -qF 'name="form_csrf"' /tmp/pts-predeploy-contact.html
grep -qF 'title-pro-detail' /tmp/pts-predeploy-product.html
grep -qF '"@type":"Product"' /tmp/pts-predeploy-product.html
if grep -Eq '925872|Executive Objects|UsedCondition|2020-11-05|priceValidUntil' /tmp/pts-predeploy-product.html; then
    echo "FAIL: Product JSON-LD còn dữ liệu mẫu/giả hoặc hạn giá lỗi thời" >&2
    exit 1
fi
grep -qF 'Yêu cầu báo giá' /tmp/pts-predeploy-product.html
# Nút báo giá dạng icon thuộc card listing, không phải template chi tiết. Sản
# phẩm mới nhất có thể không có related products nên dò trong product detail sẽ
# tạo fail giả dù catalog đang render đúng CTA.
grep -qE 'class="pts-card-ask"[^>]*><i class="fas fa-file-alt"' /tmp/pts-predeploy-catalog.html
test -s domains/phucthinhsolar.com/public_html/assets/fontawesome512/all.css
test -s domains/phucthinhsolar.com/public_html/assets/fonts/awesome/fa-solid-900.woff2
test -s domains/phucthinhsolar.com/public_html/assets/fonts/awesome/fa-brands-400.woff2
test -s domains/phucthinhsolar.com/public_html/assets/themes/local/fonts/Linearicons/fonts/Linearicons-Free54e9.woff2
for site_visual in \
    home-street-lighting.jpg home-solar-storage.jpg home-outdoor-lighting.jpg \
    catalog-street-lighting.jpg catalog-equipment-system.jpg \
    contact-consultation.jpg dealer-distribution.jpg; do
    test -s "domains/phucthinhsolar.com/public_html/assets/images/site-visuals/$site_visual"
done
if grep -Eq 'class="addcart"|"priceCurrency"|"price"[[:space:]]*:' /tmp/pts-predeploy-product.html; then
    echo "FAIL: Trang sản phẩm còn lộ giá hoặc nút mua khi chế độ báo giá đang bật" >&2
    exit 1
fi
grep -qE '<title>[^<]+</title>' /tmp/pts-predeploy-news-detail.html
grep -qF 'class="box-detail-news"' /tmp/pts-predeploy-news-detail.html
grep -qE '<title>[^<]+</title>' /tmp/pts-predeploy-solution-detail.html
grep -qF 'pts-solution-detail' /tmp/pts-predeploy-solution-detail.html
grep -qF 'id="solution-detail-title"' /tmp/pts-predeploy-solution-detail.html
grep -qE 'rel="canonical" href="http://localhost:8080/giai-phap/[a-z0-9-]+/"' /tmp/pts-predeploy-solution-detail.html
if grep -Eq 'class="[^"]*(date_content|author_post)' /tmp/pts-predeploy-solution-detail.html; then
    echo "FAIL: Chi tiết Giải pháp còn render metadata bài blog" >&2
    exit 1
fi
grep -qE '<title>[^<]+</title>' /tmp/pts-predeploy-project-detail.html
grep -qF 'id="inf_single_project"' /tmp/pts-predeploy-project-detail.html
assert_css_shipped /tmp/pts-predeploy-project-detail.html 'assets/photobox/photobox.css' '#pbOverlay'
assert_js_shipped /tmp/pts-predeploy-project-detail.html 'assets/photobox/photobox.js' 'pbOverlay'
assert_css_shipped /tmp/pts-predeploy-project-detail.html 'assets/fancybox3/jquery.fancybox.css' '.fancybox-bg'
assert_js_shipped /tmp/pts-predeploy-project-detail.html 'assets/fancybox3/jquery.fancybox.js' 'data-fancybox'
assert_css_shipped /tmp/pts-predeploy-product.html 'assets/magiczoomplus/magiczoomplus.css' '.MagicZoom'
assert_js_shipped /tmp/pts-predeploy-product.html 'assets/magiczoomplus/magiczoomplus.js' 'MagicZoomPlus'
assert_js_shipped /tmp/pts-predeploy-product.html 'assets/fileuploader/jquery.fileuploader.min.js' 'fileuploader'
assert_js_shipped /tmp/pts-predeploy-product.html 'assets/comment/comment.js' 'comment-lists'
assert_css_shipped /tmp/pts-predeploy-product.html 'assets/owlcarousel2/owl.carousel.css' '.owl-carousel'
assert_js_shipped /tmp/pts-predeploy-product.html 'assets/owlcarousel2/owl.carousel.js' 'owl.carousel'
assert_css_shipped /tmp/pts-predeploy-home.html 'assets/css/jssor.css' 'jssorl-009-spin'
assert_js_shipped /tmp/pts-predeploy-home.html 'assets/js/jssor.slider.min.js' 'JssorArrowNavigator'
assert_css_shipped /tmp/pts-predeploy-home.html 'assets/owlcarousel2/owl.carousel.css' '.owl-carousel'
assert_js_shipped /tmp/pts-predeploy-home.html 'assets/owlcarousel2/owl.carousel.js' 'owl.carousel'
grep -qF 'name="twitter:title"' /tmp/pts-predeploy-product-filtered.html
grep -qF 'rel="canonical" href="http://localhost:8080/san-pham/"' /tmp/pts-predeploy-product-filtered.html
grep -qF 'name="robots" content="noindex,nofollow"' /tmp/pts-predeploy-search.html
grep -qF 'name="robots" content="noindex,nofollow"' /tmp/pts-predeploy-cart.html
grep -qF 'pts-news-hub' /tmp/pts-predeploy-news.html
assert_css_shipped /tmp/pts-predeploy-news.html 'assets/css/news-hub.css' '.pts-news-hub'
grep -qF 'pts-solution-hub' /tmp/pts-predeploy-solutions.html
grep -qF 'pts-sh-choice' /tmp/pts-predeploy-solutions.html
assert_css_shipped /tmp/pts-predeploy-solutions.html 'assets/css/solution-hub.css' '.pts-solution-hub'
grep -qF 'pts-project-hub' /tmp/pts-predeploy-projects.html
grep -qF 'pts-ph-card' /tmp/pts-predeploy-projects.html
assert_css_shipped /tmp/pts-predeploy-projects.html 'assets/css/project-hub.css' '.pts-project-hub'
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
# Ba đường dẫn WordPress legacy từng được kiểm tra cứng ở đây đã không còn file
# trong source và cũng không còn nơi nào tham chiếu. Không biến asset mồ côi đã
# bỏ thành điều kiện deploy; phía dưới vẫn kiểm tra các upload public thật đang
# được giao diện hiện tại sử dụng.
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

# table_user_limit chứa cả bucket rate-limit của request public thật, không có
# namespace riêng cho test nên không được xem mọi dòng form:* là fixture rác.
# Các script form tự dọn bucket tạo mới; guard ở đây chỉ đếm những fixture có
# tiền tố test nhận diện chắc chắn, tránh chặn deploy vì trạng thái runtime.
remaining="$(docker compose -f "$compose_file" exec -T db mariadb -N -B -upts_local -ppts_local_only phucthinhs_local -e "SELECT (SELECT COUNT(*) FROM table_product WHERE code LIKE 'CODEX-BULK-%') + (SELECT COUNT(*) FROM table_product_campaign WHERE name LIKE 'CODEX%') + (SELECT COUNT(*) FROM table_user WHERE username LIKE 'codex-rbac-%') + (SELECT COUNT(*) FROM table_permission_group WHERE name LIKE 'CODEX-RBAC-%');")"
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
