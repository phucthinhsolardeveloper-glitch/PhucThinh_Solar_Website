#!/usr/bin/env bash
set -euo pipefail

stack_root="${PTS_STACK_ROOT:-/srv/phucthinhsolar}"
[ "$(id -u)" -eq 0 ] || { echo "FAIL: chạy bằng root hoặc sudo." >&2; exit 1; }
[ -d "$stack_root/state" ] && [ -f "$stack_root/.env" ] || { echo "FAIL: chưa chạy install-stack.sh." >&2; exit 1; }
for command in docker flock sed awk install cp rm; do
    command -v "$command" >/dev/null 2>&1 || { echo "FAIL: thiếu lệnh $command." >&2; exit 1; }
done
exec 9>"$stack_root/state/operation.lock"
flock -n 9 || { echo "BLOCK: một thao tác deploy/backup/restore khác đang chạy." >&2; exit 1; }
previous_file="$stack_root/state/previous-image"
[ -s "$previous_file" ] || { echo "FAIL: chưa có previous-image." >&2; exit 1; }
previous_image="$(cat "$previous_file")"
docker image inspect "$previous_image" >/dev/null
previous_nuxt_file="$stack_root/state/previous-nuxt-image"
previous_nuxt_image=""
[ ! -f "$previous_nuxt_file" ] || previous_nuxt_image="$(cat "$previous_nuxt_file")"
rollback_dir="$stack_root/state/rollback"
[ -s "$rollback_dir/docker-compose.yml" ] && [ -s "$rollback_dir/Caddyfile" ] || { echo "FAIL: thiếu Compose/Caddy rollback point." >&2; exit 1; }

env_file="$stack_root/.env"
config_next="$stack_root/state/rollback-current"
install -d -m 0700 "$config_next"
trap 'rm -rf "$config_next"' EXIT
cp -p "$stack_root/docker-compose.yml" "$config_next/docker-compose.yml"
cp -p "$stack_root/Caddyfile" "$config_next/Caddyfile"
cp -p "$env_file" "$config_next/env"
chmod 0600 "$config_next/env"
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
unset_env() {
    local key="$1" input="$2" output="$3"
    awk -v key="$key" 'index($0,key "=")!=1 { print }' "$input" > "$output"
    chmod 0600 "$output"
}
restore_active_release() {
    cp -p "$config_next/env" "$env_file"
    install -m 0644 "$config_next/docker-compose.yml" "$stack_root/docker-compose.yml"
    install -m 0644 "$config_next/Caddyfile" "$stack_root/Caddyfile"
    cd "$stack_root"
    if grep -Eq '^  nuxt:' "$config_next/docker-compose.yml"; then
        docker compose up -d --force-recreate web nuxt proxy || true
    else
        docker compose up -d --force-recreate web proxy || true
    fi
}
set_env PTS_WEB_IMAGE "$previous_image" "$env_file" "$config_next/env.web"
if [ -n "$previous_nuxt_image" ]; then
    docker image inspect "$previous_nuxt_image" >/dev/null
    set_env PTS_NUXT_IMAGE "$previous_nuxt_image" "$config_next/env.web" "$config_next/env.next"
else
    unset_env PTS_NUXT_IMAGE "$config_next/env.web" "$config_next/env.next"
fi
mv "$config_next/env.next" "$env_file"
install -m 0644 "$rollback_dir/docker-compose.yml" "$stack_root/docker-compose.yml"
install -m 0644 "$rollback_dir/Caddyfile" "$stack_root/Caddyfile"
cd "$stack_root"
if ! docker compose config --quiet; then
    restore_active_release
    echo "FAIL: rollback Compose không hợp lệ; đã phục hồi cấu hình đang hoạt động." >&2
    exit 1
fi
caddy_image="$(sed -n 's/^CADDY_IMAGE=//p' "$env_file" | tail -1)"
if [ -z "$caddy_image" ] || ! docker run --rm --env-file "$env_file" \
    -v "$stack_root/Caddyfile:/etc/caddy/Caddyfile:ro" "$caddy_image" \
    caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile; then
    restore_active_release
    echo "FAIL: rollback Caddy không hợp lệ; đã phục hồi cấu hình đang hoạt động." >&2
    exit 1
fi
if grep -Eq '^  nuxt:' "$stack_root/docker-compose.yml"; then
    if ! docker compose up -d --force-recreate web nuxt proxy; then
        restore_active_release
        echo "FAIL: service rollback không khởi động; đã phục hồi release đang hoạt động." >&2
        exit 1
    fi
    nuxt_container="$(docker compose ps -q nuxt)"
else
    if ! docker compose up -d --force-recreate web proxy; then
        restore_active_release
        echo "FAIL: service rollback không khởi động; đã phục hồi release đang hoạt động." >&2
        exit 1
    fi
    nuxt_container=""
fi
web_container="$(docker compose ps -q web)"
for attempt in $(seq 1 60); do
    web_status="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$web_container" 2>/dev/null || true)"
    if [ "$web_status" = "healthy" ]; then break; fi
    sleep 2
done
if [ "$web_status" != "healthy" ]; then
    restore_active_release
    echo "FAIL: web chưa healthy sau rollback; đã khôi phục release đang hoạt động." >&2
    exit 1
fi
if [ -n "$nuxt_container" ]; then
    nuxt_status=""
    for attempt in $(seq 1 60); do
        nuxt_status="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$nuxt_container" 2>/dev/null || true)"
        [ "$nuxt_status" = "healthy" ] && break
        sleep 2
    done
    if [ "$nuxt_status" != "healthy" ]; then
        restore_active_release
        echo "FAIL: Nuxt chưa healthy sau rollback; đã khôi phục release đang hoạt động." >&2
        exit 1
    fi
fi
printf '%s\n' "$previous_image" > "$stack_root/state/current-image"
if [ -n "$previous_nuxt_image" ]; then printf '%s\n' "$previous_nuxt_image" > "$stack_root/state/current-nuxt-image"; else : > "$stack_root/state/current-nuxt-image"; fi
echo "PASS: đã rollback PHP $previous_image${previous_nuxt_image:+ và Nuxt $previous_nuxt_image}; database/upload không bị thay đổi."
