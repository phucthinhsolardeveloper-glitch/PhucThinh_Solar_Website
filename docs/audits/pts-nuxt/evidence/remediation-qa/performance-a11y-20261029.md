# Performance / accessibility / SEO regression gate — 2026-10-29

Phạm vi: QA độc lập trên Nitro build mới tại `127.0.0.1:3134`, không sửa source.

## Runtime/browser method

```text
PORT=3134 NITRO_PORT=3134 node frontend/.output/server/index.mjs
NODE_PATH=/home/admin01/.npm/_npx/9833c18b2d85bc59/node_modules node <Playwright script>
```

Playwright Chromium headless đã chạy ở viewport 390×900 và 1440×900 cho `/`, `/san-pham/`, `/tin-tuc/`; chờ `networkidle`, đọc status/title/H1/robots/canonical, đo hit target và thu console errors.

## Results

| Surface | 390px | 1440px | Title/H1 | Robots/canonical |
|---|---:|---:|---|---|
| `/` | 200 | 200 | title + H1 đúng; H1 accessible label đầy đủ | `index, follow`; canonical theo request host |
| `/san-pham/` | 200 | 200 | title + H1 đúng | robots đúng; **canonical link bị thiếu** |
| `/tin-tuc/` | 200 | 200 | title + H1 đúng | `index, follow`; canonical theo request host |

### PASS

- Mobile header controls ở 390px: menu/search/cart đều đo **44×44px**.
- Desktop search/cart đo **48×48px**; menu mobile ẩn đúng ở desktop.
- Home H1 accessible name là `Thiết bị và giải pháp năng lượng mặt trời`, không còn dính chữ do `whitespace-nowrap`.
- Các route đã smoke trả HTTP 200, title/H1 hợp lệ, robots indexable đúng cho public pages.
- Preview route `/migration-about-preview`, `/migration-home-preview`, `/migration-site-preview` trả cả meta robots và `X-Robots-Tag: noindex, nofollow, noarchive` trên build mới.

### FAIL — SEO metadata

`/san-pham/` không render `<link rel="canonical">` trong SSR HTML ở cả 390px và 1440px; `/` và `/tin-tuc/` có canonical. Đây là regression/gap SEO cần sửa trong catalog page trước release gate.

### FAIL/BLOCKER — Console media errors

Mỗi route/viewport ghi nhiều console errors:

```text
Failed to load resource: the server responded with a status of 404 (Preview media not found)
```

Đây là lỗi media mapping/origin đã có trong audit trước; chưa có asset thật qua Caddy để xác định toàn bộ URL và không được đóng issue chỉ dựa trên fallback UI.

### BLOCKED — Lighthouse/CWV

Không có binary Lighthouse trong project; chỉ có Playwright Chromium. Vì vậy chưa có LCP/CLS/INP/TBT hoặc performance budget định lượng. Cần chạy Lighthouse trên Caddy release candidate sau khi media path ổn định.

## Independent verdict

Accessibility hit target và H1 đã đạt ở các viewport đã đo. SEO chưa đạt đầy đủ vì catalog canonical thiếu. Media console 404 vẫn còn. Release gate vẫn **NO-GO** cho đến khi catalog canonical, media edge và CWV được xác minh.

## Follow-up sau remediation

- Đã thêm canonical SSR cho `frontend/app/pages/san-pham/index.vue` và assertion trong `tests/public-functional-surface.test.mjs`.
- Build mới được khởi động bằng Nitro tại `127.0.0.1:3135`; `GET /san-pham/` trả HTTP **200**, H1 `Sản phẩm năng lượng mặt trời`, và SSR chứa canonical `/san-pham/`.
- Kết quả này xác nhận lỗi canonical đã được sửa ở artifact build. Console `Preview media not found` khi chạy direct Nitro không có PHP/media mount vẫn còn là giới hạn môi trường; smoke qua Caddy với upload fixture thật đã PASS ở evidence riêng.

