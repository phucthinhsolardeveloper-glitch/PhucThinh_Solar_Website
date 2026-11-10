#!/usr/bin/env bash
# Bật/tắt trang bảo trì của phucthinhsolar.com, chạy trên VPS.
#
#   maintenance.sh on       Trang công khai trả 503 kèm deploy/laravel13/maintenance.html.
#                           /admin, /api, /upload, /thumbs vẫn vào được để còn làm việc và kiểm tra.
#   maintenance.sh off      Trả lại cấu hình bình thường.
#   maintenance.sh status   Cho biết đang ở chế độ nào.
#
# 503 + Retry-After là mã đúng cho bảo trì: Google hiểu là tạm thời và giữ nguyên thứ hạng,
# khác với 200 (coi như nội dung thật) hay 404 (coi như trang đã mất).
#
# Caddyfile được gắn vào container theo inode, nên script luôn ghi ĐÈ TẠI CHỖ. Thay file bằng
# file mới sẽ khiến container vẫn đọc bản cũ (đã mắc một lần, 2026-11-10).
set -euo pipefail

app_root="${PTS_APP_ROOT:-/srv/pts-laravel13/app}"
caddy_dir="${PTS_CADDY_DIR:-/srv/pts-laravel13/caddy}"
live="${PTS_LIVE_CADDYFILE:-/srv/phucthinhsolar/Caddyfile}"
proxy="${PTS_PROXY_CONTAINER:-phucthinhsolar-proxy-1}"
normal="$caddy_dir/Caddyfile.laravel"
page="$app_root/deploy/laravel13/maintenance.html"
mode="${1:-status}"

[ "$(id -u)" -eq 0 ] || { echo "FAIL: chạy bằng root hoặc sudo." >&2; exit 1; }

reload() {
    local candidate="$1"
    # Kiểm tra trước khi nạp: cấu hình hỏng sẽ làm sập cả zcrm và training.
    docker run --rm -e ACME_EMAIL=website@phucthinhsolar.com -e SITE_DOMAIN=phucthinhsolar.com \
        -e "SITE_ALIASES=, www.phucthinhsolar.com" -e CADDY_SITE_SCHEME=https -e CADDY_GLOBAL_OPTIONS= \
        -v "$candidate":/etc/caddy/Caddyfile:ro caddy:2.10-alpine \
        caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile >/dev/null 2>&1 \
        || { echo "FAIL: cấu hình Caddy không hợp lệ, không đổi gì." >&2; exit 1; }
    cat "$candidate" > "$live"
    docker exec "$proxy" caddy reload --config /etc/caddy/Caddyfile --adapter caddyfile >/dev/null 2>&1
    echo "Đã nạp lại Caddy lúc $(date +%H:%M:%S)."
}

case "$mode" in
    on)
        [ -f "$page" ] || { echo "FAIL: thiếu $page." >&2; exit 1; }
        [ -f "$normal" ] || { echo "FAIL: thiếu $normal." >&2; exit 1; }
        tmp="$(mktemp)"
        trap 'rm -f "$tmp"' EXIT
        # Thay đúng dòng chuyển tiếp sang Nuxt. Khối @laravel nằm trước nó nên Admin và API
        # vẫn chạy; mọi trang còn lại rơi vào đây và nhận trang bảo trì.
        awk -v page="$page" '
            /reverse_proxy pts-laravel13-nuxt:3000/ {
                print "\t\thandle {"
                print "\t\t\theader {"
                print "\t\t\t\tCache-Control \"no-store, max-age=0\""
                print "\t\t\t\tContent-Type \"text/html; charset=utf-8\""
                print "\t\t\t\tRetry-After \"3600\""
                print "\t\t\t\tX-Robots-Tag \"noindex, nofollow, noarchive\""
                print "\t\t\t}"
                # Trang phải nằm trong khối body để đặt được mã 503; `respond 503 <<HTML` trả về 200 rỗng.
                print "\t\t\trespond 503 {"
                print "\t\t\t\tbody <<MAINTENANCE"
                while ((getline line < page) > 0) print line
                close(page)
                # Caddy đòi dấu đóng heredoc thụt vào đúng bằng nội dung; HTML để ở cột 0 nên dấu đóng cũng vậy.
                print "MAINTENANCE"
                print "\t\t\t}"
                print "\t\t}"
                next
            }
            { print }
        ' "$normal" > "$tmp"
        grep -q 'respond 503' "$tmp" || { echo "FAIL: không tìm thấy dòng chuyển tiếp Nuxt trong $normal." >&2; exit 1; }
        reload "$tmp"
        echo "ĐANG BẢO TRÌ: trang công khai trả 503; /admin và /api vẫn vào được."
        ;;
    off)
        [ -f "$normal" ] || { echo "FAIL: thiếu $normal." >&2; exit 1; }
        reload "$normal"
        echo "ĐÃ MỞ LẠI: website chạy bình thường."
        ;;
    status)
        if grep -q 'respond 503' "$live" 2>/dev/null; then
            echo "ĐANG BẢO TRÌ."
        else
            echo "Website đang chạy bình thường."
        fi
        ;;
    *)
        echo "Usage: $0 [on|off|status]" >&2
        exit 1
        ;;
esac
