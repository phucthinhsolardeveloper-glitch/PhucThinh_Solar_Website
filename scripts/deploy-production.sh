#!/usr/bin/env bash
set -Eeuo pipefail

repo_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
mode=""
confirmed=0
activated=0

usage() {
    cat <<'EOF'
Usage:
  ./scripts/deploy-production.sh --safe --confirm-production
  ./scripts/deploy-production.sh --fast --confirm-production
  ./scripts/deploy-production.sh --auto --confirm-production

Modes:
  --safe  Full local predeploy + full Playwright + full production backup.
  --fast  Quick guards + DB backup; only accepts low-risk frontend changes.
  --auto  Tự chọn --fast cho CSS/JS/ảnh/HTML; còn lại tự chọn --safe.

Optional environment:
  PTS_DEPLOY_HOST      SSH target (default: root@222.255.182.182)
  PTS_DEPLOY_SSH_KEY   Private key (default: /home/admin01/.ssh/id_ed25519)
  PTS_STACK_ROOT       VPS stack root (default: /srv/phucthinhsolar)
  PTS_SITE_URL         Public origin (default: https://phucthinhsolar.com)
  PTS_DEPLOY_PREDEPLOY_REPO  Checkout for SAFE local PHP predeploy; defaults to this release checkout.
EOF
}

fail() {
    echo "FAIL: $*" >&2
    exit 1
}

step() {
    printf '\n[%s] %s\n' "$(date '+%H:%M:%S')" "$*"
}

for argument in "$@"; do
    case "$argument" in
        --safe|--fast|--auto)
            [ -z "$mode" ] || fail "chỉ chọn một trong --safe hoặc --fast."
            mode="${argument#--}"
            ;;
        --confirm-production) confirmed=1 ;;
        -h|--help) usage; exit 0 ;;
        *) usage; fail "tham số không hợp lệ: $argument" ;;
    esac
done

[ -n "$mode" ] || { usage; fail "phải chọn --safe hoặc --fast."; }
[ "$confirmed" -eq 1 ] || { usage; fail "thiếu --confirm-production."; }

deploy_host="${PTS_DEPLOY_HOST:-root@222.255.182.182}"
ssh_key="${PTS_DEPLOY_SSH_KEY:-/home/admin01/.ssh/id_ed25519}"
stack_root="${PTS_STACK_ROOT:-/srv/phucthinhsolar}"
site_url="${PTS_SITE_URL:-https://phucthinhsolar.com}"

printf '%s' "$deploy_host" | grep -Eq '^([A-Za-z0-9._-]+@)?[A-Za-z0-9.-]+$' || fail "PTS_DEPLOY_HOST không hợp lệ."
printf '%s' "$stack_root" | grep -Eq '^/[A-Za-z0-9._/-]+$' || fail "PTS_STACK_ROOT không hợp lệ."
printf '%s' "$site_url" | grep -Eq '^https://[A-Za-z0-9.-]+(:[0-9]+)?$' || fail "PTS_SITE_URL phải là HTTPS origin, không kèm path."
[ -f "$ssh_key" ] || fail "không thấy SSH key: $ssh_key"

for command in bash git docker curl ssh scp sha256sum sed npx node find; do
    command -v "$command" >/dev/null 2>&1 || fail "máy deploy thiếu lệnh $command."
done

ssh_args=(-i "$ssh_key" -o BatchMode=yes -o ConnectTimeout=15)
scp_args=(-i "$ssh_key" -o BatchMode=yes -o ConnectTimeout=15)
predeploy_repo="${PTS_DEPLOY_PREDEPLOY_REPO:-$repo_dir}"
remote() {
    ssh "${ssh_args[@]}" "$deploy_host" "$1"
}

on_error() {
    local status=$?
    echo >&2
    echo "FAIL: deploy dừng tại dòng $1 (exit $status)." >&2
    if [ "$activated" -eq 1 ]; then
        echo "Release mới đã từng được kích hoạt. Nếu website lỗi, rollback code bằng:" >&2
        printf 'ssh -i %q %q %q\n' "$ssh_key" "$deploy_host" "$stack_root/scripts/rollback-release.sh" >&2
    else
        echo "Release mới chưa được xác nhận kích hoạt; production cũ vẫn là mốc an toàn." >&2
    fi
    exit "$status"
}
trap 'on_error "$LINENO"' ERR

cd "$repo_dir"
export PLAYWRIGHT_BROWSERS_PATH="${PLAYWRIGHT_BROWSERS_PATH:-$repo_dir/tools/visual-tests/.browsers}"
[ -d "$PLAYWRIGHT_BROWSERS_PATH" ] || fail "không thấy Playwright browser bundle: $PLAYWRIGHT_BROWSERS_PATH"
step "Kiểm tra Git và phạm vi release"
[ -z "$(git status --porcelain --untracked-files=normal)" ] || fail "working tree chưa sạch; commit hoặc cất thay đổi trước."
branch="$(git branch --show-current)"
[ -n "$branch" ] || fail "đang ở detached HEAD."
upstream="$(git rev-parse --abbrev-ref --symbolic-full-name '@{u}' 2>/dev/null || true)"
[ -n "$upstream" ] || fail "nhánh $branch chưa có upstream; push nhánh trước khi deploy."
commit="$(git rev-parse HEAD)"
upstream_commit="$(git rev-parse "$upstream")"
[ "$commit" = "$upstream_commit" ] || fail "HEAD chưa khớp $upstream; pull/push xong rồi deploy."
current_image="$(remote "cat $stack_root/state/current-image")"
printf '%s' "$current_image" | grep -Eq '^phucthinhsolar-web:[0-9a-f]{12}$' || fail "không nhận diện được current-image trên VPS: $current_image"
current_nuxt_image="$(remote "cat $stack_root/state/current-nuxt-image 2>/dev/null || true")"
if [ -n "$current_nuxt_image" ]; then
    printf '%s' "$current_nuxt_image" | grep -Eq '^phucthinhsolar-nuxt:[0-9a-f]{12}$' || fail "không nhận diện được current-nuxt-image trên VPS: $current_nuxt_image"
    current_nuxt_short="${current_nuxt_image#phucthinhsolar-nuxt:}"
    [ "$current_nuxt_short" = "${current_image#phucthinhsolar-web:}" ] || fail "PHP/Nuxt đang lệch release: $current_image / $current_nuxt_image"
fi
deployed_short="${current_image#phucthinhsolar-web:}"
deployed_commit="$(git rev-parse --verify "${deployed_short}^{commit}" 2>/dev/null || true)"
baseline_note="Git commit"
if [ -z "$deployed_commit" ]; then
    # Một số CSS/JS hotfix cũ được đóng gói ngoài Git bằng content-hash 12 ký
    # tự. RELEASE.txt của release đó giữ parent Git commit thật; dùng parent
    # làm baseline sau khi xác minh tag và parent khớp, không coi content-hash
    # là một commit Git giả.
    external_baseline="$(remote "
        for release_file in $stack_root/incoming/*/RELEASE.txt; do
            [ -f \"\$release_file\" ] || continue
            release_commit=\$(sed -n 's/^commit=//p' \"\$release_file\" | head -1)
            case \"\$release_commit\" in \"$deployed_short\"*) ;; *) continue ;; esac
            [ \"\${#release_commit}\" -eq 40 ] || continue
            parent_commit=\$(sed -n 's/^parent_commit=//p' \"\$release_file\" | head -1)
            printf '%s\\n' \"\$parent_commit\"
            exit 0
        done
    " 2>/dev/null || true)"
    external_baseline="$(printf '%s\n' "$external_baseline" | sed -n '/^[0-9a-f]\{40\}$/p' | head -1)"
    [ -n "$external_baseline" ] || fail "Git local không có commit production $deployed_short và không tìm thấy RELEASE.txt chứa parent baseline trên VPS."
    deployed_commit="$(git rev-parse --verify "${external_baseline}^{commit}" 2>/dev/null || true)"
    [ -n "$deployed_commit" ] || fail "Release production $deployed_short trỏ tới parent $external_baseline nhưng Git local không có parent; fetch đầy đủ history rồi thử lại."
    baseline_note="parent Git commit của release ngoài Git $deployed_short"
fi
git merge-base --is-ancestor "$deployed_commit" "$commit" || fail "commit production không phải ancestor của HEAD; dừng để kiểm tra nhánh."
changed_paths="$(git diff --name-only "$deployed_commit" "$commit")"
printf '%s\n' "$changed_paths" | grep -Eq '^(domains/phucthinhsolar\.com/public_html/|frontend/|deploy/vps/)' || fail "commit không đổi source/runtime website; không cần deploy production."
git diff --check "$deployed_commit" "$commit"
bash -n scripts/package-hosting-release.sh scripts/predeploy-local.sh scripts/deploy-production.sh

if [ "$predeploy_repo" != "$repo_dir" ]; then
    [ -x "$predeploy_repo/scripts/predeploy-local.sh" ] || fail "PTS_DEPLOY_PREDEPLOY_REPO thiếu scripts/predeploy-local.sh."
    predeploy_commit="$(git -C "$predeploy_repo" rev-parse HEAD 2>/dev/null || true)"
    [ -n "$predeploy_commit" ] || fail "PTS_DEPLOY_PREDEPLOY_REPO không phải checkout cùng Git repository."
    git cat-file -e "${predeploy_commit}^{commit}" 2>/dev/null || fail "commit predeploy không tồn tại trong repository release."
    git merge-base --is-ancestor "$predeploy_commit" "$commit" || fail "checkout predeploy không phải ancestor của commit release."
    predeploy_runtime_changes="$(git diff --name-only "$predeploy_commit" "$commit" -- domains/ docker/ docker-compose.local.yml migrations/ scripts/ deploy/ | grep -Ev '^scripts/deploy-production\.sh$' || true)"
    [ -z "$predeploy_runtime_changes" ] || fail "checkout predeploy khác release ở source/runtime cần kiểm thử: $(printf '%s' "$predeploy_runtime_changes" | tr '\n' ' ')"
    predeploy_dirty_runtime="$(git -C "$predeploy_repo" status --porcelain --untracked-files=normal -- domains/ docker/ docker-compose.local.yml migrations/ scripts/ deploy/)"
    [ -z "$predeploy_dirty_runtime" ] || fail "checkout predeploy có thay đổi chưa commit trong source/runtime."
fi

if [ "$mode" = "auto" ]; then
    if printf '%s\n' "$changed_paths" | grep -Eq '^(migrations/|deploy/|docker/|docker-compose[^/]*|scripts/|domains/phucthinhsolar\.com/public_html/(api/|administrator/|libraries/|sources/|templates/.*\.php$|.*\.php$))'; then
        mode="safe"
        echo "AUTO: phát hiện thay đổi backend/Admin/template/migration/hạ tầng -> SAFE"
    else
        mode="fast"
        echo "AUTO: chỉ phát hiện thay đổi frontend tĩnh -> FAST"
    fi
fi

if [ "$mode" = "fast" ]; then
    if printf '%s\n' "$changed_paths" | grep -Eq '^(migrations/|deploy/|docker/|docker-compose[^/]*|domains/phucthinhsolar\.com/public_html/(api/|administrator/|libraries/|sources/|templates/order/|templates/layout/|templates/product/product_detail_tpl\.php|assets/js/(apps|functions)\.js))'; then
        fail "--fast từ chối commerce/global layout/API/Admin/library/source/migration/hạ tầng; dùng --safe."
    fi
    other_script_paths="$(printf '%s\n' "$changed_paths" | grep -E '^scripts/' | grep -Ev '^scripts/deploy-production\.sh$' || true)"
    if [ -n "$other_script_paths" ]; then
        fail "--fast từ chối thay đổi script vận hành; dùng --safe."
    fi
    step "FAST: syntax guard cho file JavaScript/PHP đã đổi"
    while IFS= read -r javascript; do
        [ -z "$javascript" ] || [ ! -f "$javascript" ] || node --check "$javascript"
    done < <(printf '%s\n' "$changed_paths" | grep -E '\.js$' || true)
    if printf '%s\n' "$changed_paths" | grep -qE '^domains/phucthinhsolar\.com/public_html/.*\.php$'; then
        running="$(docker inspect --format '{{.State.Running}}' pts-php81-local 2>/dev/null || true)"
        [ "$running" = "true" ] || fail "FAST cần container pts-php81-local đang chạy để lint PHP 8.1; hoặc dùng --safe."
        while IFS= read -r php_file; do
            [ -z "$php_file" ] && continue
            relative="${php_file#domains/phucthinhsolar.com/public_html/}"
            docker exec pts-php81-local php -l "/var/www/html/$relative"
        done < <(printf '%s\n' "$changed_paths" | grep -E '^domains/phucthinhsolar\.com/public_html/.*\.php$' || true)
    fi
else
    step "SAFE: chạy full predeploy local"
    (cd "$predeploy_repo" && LOCAL_ENFORCE_RBAC=1 ./scripts/predeploy-local.sh)
    step "SAFE: chạy toàn bộ Playwright một worker"
    (cd tools/visual-tests && npx playwright test --workers=1)
fi

step "Đóng gói release theo commit $commit"
package_output="$(./scripts/package-hosting-release.sh)"
printf '%s\n' "$package_output"
release_dir="$(printf '%s\n' "$package_output" | sed -n 's/^PASS: Release được tạo tại //p' | tail -1)"
[ -d "$release_dir" ] || fail "không đọc được release directory từ package output."
release_name="$(basename "$release_dir")"
printf '%s' "$release_name" | grep -Eq '^[0-9]{8}T[0-9]{6}Z-[0-9a-f]{12}$' || fail "release name không hợp lệ."
release_id="${commit:0:12}"
remote_release="$stack_root/incoming/$release_name"

step "Kiểm tra VPS trước deploy"
echo "Production hiện tại: PHP=$current_image Nuxt=${current_nuxt_image:-chưa có} ($deployed_commit; baseline $baseline_note)"
remote "cd $stack_root && docker compose ps && df -h $stack_root && printf 'PHP=' && cat state/current-image && printf 'Nuxt=' && (cat state/current-nuxt-image 2>/dev/null || echo 'chưa có')"
deploy_started_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

if [ "$mode" = "safe" ]; then
    step "SAFE: full backup production"
    remote "$stack_root/scripts/backup.sh --full"
else
    step "FAST: backup database production"
    remote "$stack_root/scripts/backup.sh --db-only"
fi

step "Upload và kiểm checksum phía VPS"
remote "install -d -m 0755 $remote_release"
mapfile -d '' release_files < <(find "$release_dir" -maxdepth 1 -type f -print0)
[ "${#release_files[@]}" -ge 6 ] || fail "release thiếu file bàn giao."
scp "${scp_args[@]}" "${release_files[@]}" "$deploy_host:$remote_release/"
remote "cd $remote_release && sha256sum -c SHA256SUMS"

step "Build, chuyển image và chờ healthcheck"
# Chạy đúng deploy script đã đi cùng archive và được checksum. Không dùng
# bản copy cố định trên VPS: bản đó có thể cũ hơn release và lấy nhầm runtime
# Dockerfile của stack phải giữ cùng major runtime PHP 8.1 với local và production.
remote "tar -xzf $remote_release/deployment-tools-${release_id}.tar.gz -C $remote_release deploy/vps && bash $remote_release/deploy/vps/scripts/deploy-release.sh $remote_release"
activated=1

step "Xóa cache sinh lại được"
remote "page_count=\$(find $stack_root/shared/caches -maxdepth 1 -type f -name 'cache_*' | wc -l); asset_count=\$(find $stack_root/shared/assets-caches -maxdepth 1 -type f | wc -l); find $stack_root/shared/caches -maxdepth 1 -type f -name 'cache_*' -delete; find $stack_root/shared/assets-caches -maxdepth 1 -type f -delete; echo CLEARED_page_cache=\$page_count CLEARED_asset_cache=\$asset_count"

step "Verify stack và schema preflight chỉ đọc"
remote "bash $remote_release/deploy/vps/scripts/verify-stack.sh && cd $stack_root && web_container=\$(docker compose ps -q web) && docker cp releases/$release_id/migrations/000_preflight_production.php \$web_container:/tmp/pts-preflight.php && docker exec \$web_container php /tmp/pts-preflight.php --require-all"

step "HTTP smoke test public"
for path in / /san-pham/ /san-pham/den-pha-nang-luong-mat-troi-jindian-jd-t300-300w /gio-hang/ /tin-tuc/; do
    curl -fsS -L --max-time 20 -o /dev/null "$site_url$path"
    echo "PASS: $site_url$path"
done

step "Playwright production: mobile commerce, ngôn ngữ và typography"
(cd tools/visual-tests && PTS_VISUAL_BASE_URL="$site_url" npx playwright test \
    tests/mobile-commerce-experience.spec.js tests/typography-system.spec.js --workers=1)

step "Quét lỗi mới trong log và xác nhận image/health PHP + Nuxt"
remote "cd $stack_root && web_bad=\$(docker compose logs --since=$deploy_started_at web 2>&1 | grep -Eci 'PHP (Fatal|Warning)|Uncaught|AH[0-9]+:error|HTTP/[0-9.]+ 5[0-9][0-9]' || true); nuxt_bad=\$(docker compose logs --since=$deploy_started_at nuxt 2>&1 | grep -Eci 'ERROR|FATAL|Unhandled|HTTP/[0-9.]+ 5[0-9][0-9]' || true); proxy_bad=\$(docker compose logs --since=$deploy_started_at proxy 2>&1 | grep -Eci 'level=(error|fatal)|status.:5[0-9][0-9]' || true); web_container=\$(docker compose ps -q web); nuxt_container=\$(docker compose ps -q nuxt); web_status=\$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' \$web_container); nuxt_status=\$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' \$nuxt_container); echo ERROR_SCAN_web=\$web_bad ERROR_SCAN_nuxt=\$nuxt_bad ERROR_SCAN_proxy=\$proxy_bad PHP_HEALTH=\$web_status NUXT_HEALTH=\$nuxt_status; test \$web_bad -eq 0; test \$nuxt_bad -eq 0; test \$proxy_bad -eq 0; test \"\$web_status\" = healthy; test \"\$nuxt_status\" = healthy; test \"\$(cat state/current-image)\" = \"phucthinhsolar-web:$release_id\"; test \"\$(cat state/current-nuxt-image)\" = \"phucthinhsolar-nuxt:$release_id\""

trap - ERR
printf '\nPASS: deploy %s hoàn tất.\n' "$mode"
echo "Commit: $commit"
echo "PHP image: phucthinhsolar-web:$release_id"
echo "Nuxt image: phucthinhsolar-nuxt:$release_id"
echo "Release: $remote_release"
echo "Rollback code: ssh -i $ssh_key $deploy_host $stack_root/scripts/rollback-release.sh"
