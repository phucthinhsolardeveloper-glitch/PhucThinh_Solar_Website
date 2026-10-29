#!/usr/bin/env bash
set -euo pipefail

stack_root="${PTS_STACK_ROOT:-/srv/phucthinhsolar}"
release_bundle="${1:-}"

usage() {
    echo "Usage: sudo $0 /path/to/release-directory" >&2
    echo "Release directory phải chứa SHA256SUMS, RELEASE.txt và public_html-*.tar.gz." >&2
}

[ "$(id -u)" -eq 0 ] || { echo "FAIL: chạy bằng root hoặc sudo." >&2; exit 1; }
[ -n "$release_bundle" ] || { usage; exit 1; }
[ -d "$release_bundle" ] || { echo "FAIL: không thấy release directory." >&2; exit 1; }
[ -f "$stack_root/docker-compose.yml" ] || { echo "FAIL: chưa chạy install-stack.sh." >&2; exit 1; }
exec 9>"$stack_root/state/operation.lock"
flock -n 9 || { echo "BLOCK: một thao tác deploy/backup/restore khác đang chạy." >&2; exit 1; }

for command in docker tar sha256sum awk sed rsync flock; do
    command -v "$command" >/dev/null 2>&1 || { echo "FAIL: thiếu lệnh $command." >&2; exit 1; }
done

(cd "$release_bundle" && sha256sum -c SHA256SUMS)
archive="$(find "$release_bundle" -maxdepth 1 -type f -name 'public_html-*.tar.gz' -printf '%f\n' | sort | head -1)"
[ -n "$archive" ] || { echo "FAIL: thiếu archive public_html." >&2; exit 1; }
tools_archive="$(find "$release_bundle" -maxdepth 1 -type f -name 'deployment-tools-*.tar.gz' -printf '%f\n' | sort | head -1)"

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
if [ -n "$tools_archive" ] && [ ! -d "$release_dir/migrations" ]; then
    tar -xzf "$release_bundle/$tools_archive" -C "$release_dir"
fi

if find "$release_dir/public_html" -type l -print -quit | grep -q .; then
    echo "FAIL: release chứa symlink; dừng để kiểm tra thủ công." >&2
    exit 1
fi

install -d -m 0755 "$build_dir/public_html" "$build_dir/app"
rsync -a "$release_dir/public_html/" "$build_dir/public_html/"
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

env_file="$stack_root/.env"
old_image="$(sed -n 's/^PTS_WEB_IMAGE=//p' "$env_file" | tail -1)"
[ -n "$old_image" ] || old_image="phucthinhsolar-web:bootstrap"
env_next="$stack_root/state/env.next"
awk -v image="$new_image" '
    BEGIN { replaced=0 }
    /^PTS_WEB_IMAGE=/ { print "PTS_WEB_IMAGE=" image; replaced=1; next }
    { print }
    END { if(!replaced) print "PTS_WEB_IMAGE=" image }
' "$env_file" > "$env_next"
chmod 0600 "$env_next"
mv "$env_next" "$env_file"

cd "$stack_root"
if ! docker compose config --quiet; then
    echo "FAIL: compose config không hợp lệ; hoàn tác image active." >&2
    sed -i "s#^PTS_WEB_IMAGE=.*#PTS_WEB_IMAGE=$old_image#" "$env_file"
    exit 1
fi

docker compose up -d db
docker compose up -d web proxy

healthy=0
web_container="$(docker compose ps -q web)"
# 120s. Healthcheck web có start_period 15s + interval 10s (xem compose), nên
# probe đầu tiên rơi vào ~15-25s và vòng này còn dư biên. Không hạ xuống dưới
# start_period + 2*interval, nếu không deploy tốt sẽ bị rollback oan.
for attempt in $(seq 1 60); do
    status="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$web_container" 2>/dev/null || true)"
    if [ "$status" = "healthy" ]; then healthy=1; break; fi
    sleep 2
done

if [ "$healthy" -ne 1 ]; then
    echo "FAIL: web mới không healthy; tự động quay lại $old_image." >&2
    sed -i "s#^PTS_WEB_IMAGE=.*#PTS_WEB_IMAGE=$old_image#" "$env_file"
    if docker image inspect "$old_image" >/dev/null 2>&1; then
        docker compose up -d web proxy || true
    else
        echo "WARN: image trước không tồn tại cục bộ; cần phục hồi thủ công." >&2
    fi
    exit 1
fi

printf '%s\t%s\t%s\n' "$(date -u +%FT%TZ)" "$new_image" "$old_image" >> "$stack_root/state/deploy-history.tsv"
printf '%s\n' "$new_image" > "$stack_root/state/current-image"
printf '%s\n' "$old_image" > "$stack_root/state/previous-image"
echo "PASS: đã kích hoạt $new_image. Chưa tự động chạy migration hoặc đổi DNS."
