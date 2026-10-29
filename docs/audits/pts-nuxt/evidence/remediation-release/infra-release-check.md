# Infrastructure & release QA checkpoint — 2026-10-29

Phạm vi chỉ QA/local; không deploy production, không đổi DNS/DB.

## PASS

- `Caddyfile.local-full-nuxt` adapts/validates bằng `caddy:2-alpine` (exit 0).
- Caddy local smoke với Nuxt Nitro hiện tại trả 200 cho `/`, `/san-pham/`, `/tin-tuc/`, `/du-an-da-thuc-hien/`.
- API exception `/api/pts_v1_site.php` trả 200 JSON qua Caddy.
- Các asset thực lấy từ HTML (`/_nuxt/*.js`) trả 200 `text/javascript` qua Caddy.
- `/product/` trả 301; `/ve-chung-toi/` trả 301 trên edge QA.

## BLOCKED/FAIL cần xử lý

- `docker compose -f deploy/vps/docker-compose.yml config --quiet` không chạy nếu không có `.env` production secrets/image variables; lần chạy local exit 1 với biến bắt buộc `DB_NAME`, `DB_USER`, `MARIADB_IMAGE`, `PTS_NUXT_IMAGE`, `CADDY_IMAGE`, `PTS_WEB_IMAGE`, `SITE_DOMAIN`, `ACME_EMAIL`. Đây là expected safety gate, không được điền secret giả để bypass.
- `/du-an/` trả 404 trong build Nitro đang chạy dù route alias source tồn tại; cần rebuild đúng checkout sau khi tích hợp và kiểm tra lại, không đánh dấu alias PASS từ source.
- `/chinh-sach/...` route mẫu trả 404 khi slug không phải fixture thật; cần dùng slug CMS đã publish.
- `/upload/*` non-existent trả 404; chưa có asset thật để xác minh media mount/content-type.
- Rollback script không thể dry-run trong local vì yêu cầu root + `/srv/phucthinhsolar/state` + previous image/config; không thực thi với state giả.

Evidence: `infra-smoke-20261029.txt`, `caddy-qa-smoke-20261029.txt`.

