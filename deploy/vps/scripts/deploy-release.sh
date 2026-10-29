#!/usr/bin/env bash
set -euo pipefail

stack_root="${PTS_STACK_ROOT:-/srv/phucthinhsolar}"
release_bundle="${1:-}"

usage() {
    echo "Usage: sudo $0 /path/to/release-directory" >&2
    echo "Release directory phải có checksum, PHP archive, frontend-source archive và deployment-tools archive." >&2
}

[ "$(id -u)" -eq 0 ] || { echo "FAIL: chạy bằng root hoặc sudo." >&2; exit 1; }
[ -n "$release_bundle" ] || { usage; exit 1; }
[ -d "$release_bundle" ] || { echo "FAIL: không thấy release directory." >&2; exit 1; }
[ -f "$stack_root/docker-compose.yml" ] || { echo "FAIL: chưa chạy install-stack.sh." >&2; exit 1; }
exec 9>"$stack_root/state/operation.lock"
flock -n 9 || { echo "BLOCK: một thao tác deploy/backup/restore khác đang chạy." >&2; exit 1; }

for command in docker tar sha256sum awk sed rsync flock cp install; do
    command -v "$command" >/dev/null 2>&1 || { echo "FAIL: thiếu lệnh $command." >&2; exit 1; }
done

(cd "$release_bundle" && sha256sum -c SHA256SUMS)
archive="$(find "$release_bundle" -maxdepth 1 -type f -name 'public_html-*.tar.gz' -printf '%f\n' | sort | head -1)"
[ -n "$archive" ] || { echo "FAIL: thiếu archive public_html." >&2; exit 1; }
frontend_archive="$(find "$release_bundle" -maxdepth 1 -type f -name 'frontend-source-*.tar.gz' -printf '%f\n' | sort | head -1)"
[ -n "$frontend_archive" ] || { echo "FAIL: thiếu archive frontend Nuxt." >&2; exit 1; }
tools_archive="$(find "$release_bundle" -maxdepth 1 -type f -name 'deployment-tools-*.tar.gz' -printf '%f\n' | sort | head -1)"
[ -n "$tools_archive" ] || { echo "FAIL: thiếu deployment-tools archive." >&2; exit 1; }

commit="$(sed -n 's/^commit=//p' "$release_bundle/RELEASE.txt" | head -1)"
if ! printf '%s' "$commit" | grep -Eq '^[0-9a-f]{40}$'; then
    echo "FAIL: RELEASE.txt không có commit SHA hợp lệ." >&2
    exit 1
fi
release_id="${commit:0:12}"
release_dir="$stack_root/releases/$release_id"
build_dir="$(mktemp -d "$stack_root/state/build-${release_id}-XXXXXX")"
cleanup() { rm -rf "$build_dir"; }
trap cleanup EXIT

if [ ! -d "$release_dir/public_html" ]; then
    install -d -m 0755 "$release_dir"
    tar -xzf "$release_bundle/$archive" -C "$release_dir"
fi
if [ -n "$tools_archive" ]; then
    tar -xzf "$release_bundle/$tools_archive" -C "$release_dir"
fi

if find "$release_dir/public_html" -type l -print -quit | grep -q .; then
    echo "FAIL: release chứa symlink; dừng để kiểm tra thủ công." >&2
    exit 1
fi

install -d -m 0755 "$build_dir/public_html" "$build_dir/app" "$build_dir/nuxt"
rsync -a "$release_dir/public_html/" "$build_dir/public_html/"
tar -xzf "$release_bundle/$frontend_archive" -C "$build_dir/nuxt" --strip-components=1
if find "$build_dir/nuxt" -type l -print -quit | grep -q .; then
    echo "FAIL: frontend archive chứa symlink; dừng trước khi build." >&2
    exit 1
fi
# The runtime definition is part of the checksummed release. Falling back to
# the stack copy keeps older releases deployable, but a new release must not
# silently inherit an old PHP base image from the VPS.
if [ -d "$release_dir/deploy/vps/app" ]; then
    rsync -a "$release_dir/deploy/vps/app/" "$build_dir/app/"
else
    rsync -a "$stack_root/app/" "$build_dir/app/"
fi

if ! grep -Eq '^FROM[[:space:]]+php:8\.1-apache([[:space:]]|$)' "$build_dir/app/Dockerfile"; then
    echo "FAIL: release runtime không phải PHP 8.1; dừng trước khi build/activate." >&2
    exit 1
fi

new_image="phucthinhsolar-web:$release_id"
docker build --pull=false --label "com.phucthinhsolar.commit=$commit" -f "$build_dir/app/Dockerfile" -t "$new_image" "$build_dir"
new_nuxt_image="phucthinhsolar-nuxt:$release_id"
docker build --pull=false --label "com.phucthinhsolar.commit=$commit" -f "$build_dir/nuxt/Dockerfile" -t "$new_nuxt_image" "$build_dir/nuxt"

env_file="$stack_root/.env"
old_image="$(sed -n 's/^PTS_WEB_IMAGE=//p' "$env_file" | tail -1)"
[ -n "$old_image" ] || old_image="phucthinhsolar-web:bootstrap"
old_nuxt_image="$(sed -n 's/^PTS_NUXT_IMAGE=//p' "$env_file" | tail -1)"
old_compose="$build_dir/docker-compose.previous.yml"
old_caddy="$build_dir/Caddyfile.previous"
cp -p "$stack_root/docker-compose.yml" "$old_compose"
cp -p "$stack_root/Caddyfile" "$old_caddy"
cp -p "$env_file" "$build_dir/env.previous"
chmod 0600 "$build_dir/env.previous"

set_env() {
    local key="$1" value="$2" input="$3" output="$4"
    awk -v key="$key" -v value="$value" '
        BEGIN { prefix=key "="; replaced=0 }
        index($0,prefix)==1 { print prefix value; replaced=1; next }
        { print }
        END { if(!replaced) print prefix value }
    ' "$input" > "$output"
    chmod 0600 "$output"
}

set_env PTS_WEB_IMAGE "$new_image" "$env_file" "$build_dir/env.web"
set_env PTS_NUXT_IMAGE "$new_nuxt_image" "$build_dir/env.web" "$build_dir/env.nuxt"
mv "$build_dir/env.nuxt" "$env_file"

if [ ! -f "$release_dir/deploy/vps/docker-compose.yml" ] || [ ! -f "$release_dir/deploy/vps/Caddyfile" ]; then
    echo "FAIL: release tools thiếu production Compose/Caddyfile." >&2
    cp -p "$build_dir/env.previous" "$env_file"
    exit 1
fi
install -m 0644 "$release_dir/deploy/vps/docker-compose.yml" "$stack_root/docker-compose.yml"
install -m 0644 "$release_dir/deploy/vps/Caddyfile" "$stack_root/Caddyfile"

restore_previous_config() {
    cp -p "$build_dir/env.previous" "$env_file"
    install -m 0644 "$old_compose" "$stack_root/docker-compose.yml"
    install -m 0644 "$old_caddy" "$stack_root/Caddyfile"
}

restore_previous_services() {
    if grep -Eq '^  nuxt:' "$old_compose"; then
        docker compose up -d --force-recreate web nuxt proxy
    else
        docker compose up -d --force-recreate web proxy
    fi
}

state_backup="$build_dir/state.previous"
install -d -m 0700 "$state_backup"
for state_name in current-image previous-image current-nuxt-image previous-nuxt-image deploy-history.tsv; do
    if [ -f "$stack_root/state/$state_name" ]; then
        cp -p "$stack_root/state/$state_name" "$state_backup/$state_name"
        : > "$state_backup/$state_name.present"
    fi
done
if [ -d "$stack_root/state/rollback" ]; then
    cp -a "$stack_root/state/rollback" "$state_backup/rollback"
    : > "$state_backup/rollback.present"
fi

restore_previous_state() {
    local state_name
    for state_name in current-image previous-image current-nuxt-image previous-nuxt-image deploy-history.tsv; do
        if [ -f "$state_backup/$state_name.present" ]; then
            cp -p "$state_backup/$state_name" "$stack_root/state/$state_name" || return 1
        else
            rm -f "$stack_root/state/$state_name" || return 1
        fi
    done
    rm -rf "$stack_root/state/rollback" || return 1
    if [ -f "$state_backup/rollback.present" ]; then
        cp -a "$state_backup/rollback" "$stack_root/state/rollback" || return 1
    fi
}

commit_deploy_state() {
    local state_next="$stack_root/state/rollback.next"
    rm -rf "$state_next"
    install -d -m 0700 "$state_next"
    cp -p "$old_compose" "$state_next/docker-compose.yml" || return 1
    cp -p "$old_caddy" "$state_next/Caddyfile" || return 1

    printf '%s\n' "$old_image" > "$stack_root/state/previous-image.next" || return 1
    printf '%s\n' "$old_nuxt_image" > "$stack_root/state/previous-nuxt-image.next" || return 1
    printf '%s\n' "$new_nuxt_image" > "$stack_root/state/current-nuxt-image.next" || return 1
    printf '%s\n' "$new_image" > "$stack_root/state/current-image.next" || return 1
    chmod 0600 "$stack_root/state/previous-image.next" "$stack_root/state/previous-nuxt-image.next" \
        "$stack_root/state/current-nuxt-image.next" "$stack_root/state/current-image.next" || return 1

    : > "$state_backup/deploy-history.next" || return 1
    if [ -f "$state_backup/deploy-history.tsv" ]; then
        cat "$state_backup/deploy-history.tsv" > "$state_backup/deploy-history.next" || return 1
    fi
    printf '%s\t%s\t%s\n' "$(date -u +%FT%TZ)" "$new_image" "$old_image" >> "$state_backup/deploy-history.next" || return 1

    rm -rf "$stack_root/state/rollback.previous" || return 1
    if [ -d "$stack_root/state/rollback" ]; then
        mv "$stack_root/state/rollback" "$stack_root/state/rollback.previous" || return 1
    fi
    if ! mv "$state_next" "$stack_root/state/rollback"; then
        [ ! -d "$stack_root/state/rollback.previous" ] || mv "$stack_root/state/rollback.previous" "$stack_root/state/rollback"
        return 1
    fi
    mv "$stack_root/state/previous-image.next" "$stack_root/state/previous-image" || return 1
    mv "$stack_root/state/previous-nuxt-image.next" "$stack_root/state/previous-nuxt-image" || return 1
    mv "$stack_root/state/current-nuxt-image.next" "$stack_root/state/current-nuxt-image" || return 1
    mv "$state_backup/deploy-history.next" "$stack_root/state/deploy-history.tsv" || return 1
    mv "$stack_root/state/current-image.next" "$stack_root/state/current-image" || return 1
    rm -rf "$stack_root/state/rollback.previous"
}

cd "$stack_root"
if ! docker compose config --quiet; then
    echo "FAIL: compose config không hợp lệ; khôi phục image và proxy config active." >&2
    restore_previous_config
    exit 1
fi

caddy_image="$(sed -n 's/^CADDY_IMAGE=//p' "$env_file" | tail -1)"
if [ -z "$caddy_image" ] || ! docker run --rm --env-file "$env_file" \
    -v "$stack_root/Caddyfile:/etc/caddy/Caddyfile:ro" "$caddy_image" \
    caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile; then
    echo "FAIL: Caddy config không hợp lệ; khôi phục image và proxy config active." >&2
    restore_previous_config
    exit 1
fi

if ! docker compose up -d db; then
    echo "FAIL: database service không khởi động; khôi phục image và proxy config trước deploy." >&2
    restore_previous_config
    restore_previous_services || true
    exit 1
fi
if ! docker compose up -d --force-recreate web nuxt proxy; then
    echo "FAIL: service không khởi động; phục hồi image và proxy config trước deploy." >&2
    restore_previous_config
    restore_previous_services || true
    exit 1
fi

web_healthy=0
nuxt_healthy=0
web_container="$(docker compose ps -q web)"
nuxt_container="$(docker compose ps -q nuxt)"
# 120s. Healthcheck web có start_period 15s + interval 10s (xem compose), nên
# probe đầu tiên rơi vào ~15-25s và vòng này còn dư biên. Không hạ xuống dưới
# start_period + 2*interval, nếu không deploy tốt sẽ bị rollback oan.
for attempt in $(seq 1 60); do
    web_status="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$web_container" 2>/dev/null || true)"
    nuxt_status="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$nuxt_container" 2>/dev/null || true)"
    [ "$web_status" = "healthy" ] && web_healthy=1
    [ "$nuxt_status" = "healthy" ] && nuxt_healthy=1
    if [ "$web_healthy" -eq 1 ] && [ "$nuxt_healthy" -eq 1 ]; then break; fi
    sleep 2
done

if [ "$web_healthy" -ne 1 ] || [ "$nuxt_healthy" -ne 1 ]; then
    echo "FAIL: PHP hoặc Nuxt image mới không healthy; khôi phục image, Compose và Caddy trước deploy." >&2
    restore_previous_config
    restore_previous_services || true
    exit 1
fi

if ! commit_deploy_state; then
    echo "FAIL: không thể ghi trạng thái release; phục hồi image, Compose, Caddy và metadata trước deploy." >&2
    state_restore_ok=1
    config_restore_ok=1
    services_restore_ok=1
    restore_previous_state || state_restore_ok=0
    restore_previous_config || config_restore_ok=0
    restore_previous_services || services_restore_ok=0
    if [ "$state_restore_ok" -ne 1 ] || [ "$config_restore_ok" -ne 1 ] || [ "$services_restore_ok" -ne 1 ]; then
        echo "CRITICAL: phục hồi state=$state_restore_ok config=$config_restore_ok services=$services_restore_ok; kiểm tra VPS thủ công trước thao tác tiếp." >&2
        exit 1
    fi
    exit 1
fi
echo "PASS: đã kích hoạt PHP $new_image và Nuxt $new_nuxt_image. Chưa tự động chạy migration hoặc đổi DNS."
