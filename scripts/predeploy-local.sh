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
rg -q '<title>' /tmp/pts-predeploy-home.html
rg -q 'name="form_csrf"' /tmp/pts-predeploy-contact.html
rg -q 'title-pro-detail' /tmp/pts-predeploy-product.html
rg -q 'Gọi báo giá' /tmp/pts-predeploy-product.html
if rg -q 'class="addcart"|"priceCurrency"|"price"[[:space:]]*:' /tmp/pts-predeploy-product.html; then
    echo "FAIL: Trang sản phẩm còn lộ giá hoặc nút mua khi chế độ báo giá đang bật" >&2
    exit 1
fi

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
