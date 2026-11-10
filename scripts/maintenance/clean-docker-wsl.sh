#!/usr/bin/env bash
# Dọn Docker và WSL của máy dev, phần chạy TRONG WSL (không cần quyền admin Windows).
# Mặc định chỉ XEM: in ra cái gì chiếm chỗ và cái gì sẽ bị xóa. Thêm --apply mới xóa.
#
#   bash scripts/maintenance/clean-docker-wsl.sh            # xem
#   bash scripts/maintenance/clean-docker-wsl.sh --apply    # dọn phần an toàn
#
# Chỉ xóa thứ TÁI TẠO ĐƯỢC: build cache, image không container nào dùng, layer treo, cache npm/pnpm.
# KHÔNG BAO GIỜ tự xóa: volume (chứa dữ liệu), container đang có, image của project khác (ZCRM...), thư mục dự án.
# Volume và image của project khác chỉ được LIỆT KÊ ở cuối để người dùng tự quyết.
#
# Sau khi chạy xong, file ổ ảo (.vhdx) trên Windows VẪN phình: chạy tiếp
#   scripts/maintenance/Compact-WslDisks.ps1   (PowerShell, "Run as administrator")
set -uo pipefail

APPLY=0
[ "${1:-}" = "--apply" ] && APPLY=1

# docker.exe qua WSL đọc stdin: luôn tách khỏi stdin để vòng lặp không bị nuốt dòng.
dk() { docker "$@" < /dev/null; }
say() { printf '\n== %s\n' "$*"; }

say "Dung lượng hiện tại"
df -h / | tail -1
dk system df

# Image của chính project này, không còn container nào dùng: bản thử nghiệm cũ, image dev cũ.
# Giữ tag mới nhất đang chạy (pts-local-*). Các tên dưới đây được ghi rõ, không dùng wildcard rộng.
PTS_IMAGE_PATTERNS='^(pts-laravel13-(backend|nuxt):.*-candidate|web_pts_php-(laravel13|web|nuxt-service|db):.*|pts-urgent-ui-parity-.*)$'

say "Image của project PTS không container nào dùng (sẽ xóa)"
used_ids=$(dk ps -a --format '{{.Image}}' | sort -u)
candidates=()
while IFS='|' read -r name id; do
  [[ "$name" =~ $PTS_IMAGE_PATTERNS ]] || continue
  if echo "$used_ids" | grep -qxF "$name" || echo "$used_ids" | grep -qxF "$id"; then continue; fi
  candidates+=("$name")
  echo "  $name"
done < <(dk image ls --format '{{.Repository}}:{{.Tag}}|{{.ID}}')
[ ${#candidates[@]} -eq 0 ] && echo "  (không có)"

say "Cache npm / pnpm"
du -xsh ~/.npm ~/.local/share/pnpm 2>/dev/null

if [ "$APPLY" -eq 1 ]; then
  say "Đang dọn"
  for img in "${candidates[@]}"; do dk image rm "$img" > /dev/null 2>&1 && echo "  đã xóa image $img" || echo "  giữ lại $img"; done
  dk builder prune -af | tail -1
  dk image prune -f | tail -1          # chỉ layer treo (không tag)
  dk container prune -f --filter "label=com.docker.compose.project=pts_laravel13_candidate" | tail -1
  command -v npm  > /dev/null && npm cache clean --force 2>&1 | tail -1
  command -v pnpm > /dev/null && pnpm store prune 2>&1 | tail -1
  # Trả block trống cho ổ ảo (cần root; bỏ qua nếu sudo hỏi mật khẩu).
  sudo -n fstrim -av 2> /dev/null || echo "  (fstrim cần root: Compact-WslDisks.ps1 sẽ làm bước này)"
  say "Sau khi dọn"
  df -h / | tail -1
  dk system df
else
  echo
  echo "Chưa xóa gì. Chạy lại với --apply để dọn các mục trên."
fi

say "Chỉ liệt kê, KHÔNG tự xóa (cần người quyết)"
echo "-- Volume không container nào dùng (có thể chứa dữ liệu, xóa bằng: docker volume rm <tên>):"
dk volume ls --format '{{.Name}}' | while read -r v; do
  [ -z "$(dk ps -a --filter "volume=$v" --format '{{.Names}}')" ] && printf '   %s  (%s)\n' "$v" "$(dk run --rm -v "$v":/d:ro alpine du -sh /d 2> /dev/null | cut -f1)"
done
echo "-- Image không container nào dùng, của project khác:"
dk image ls --format '{{.Size}} {{.Repository}}:{{.Tag}}' | grep -E 'zalo-crm|zcrm|hyans' | sort -rh | head -20
echo "-- Phần lớn nhất trong thư mục home:"
du -xsh ~/.[!.]* ~/* 2> /dev/null | sort -rh | head -8
