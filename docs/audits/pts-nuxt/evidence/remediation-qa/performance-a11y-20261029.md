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

## Rerun trên HEAD `8fbacd0` — fresh build

Đã chạy lại `npm run build` (Nitro build complete, exit 0) và khởi động artifact mới tại `127.0.0.1:3135`. Playwright Chromium chạy lại 390×900 và 1440×900 cho `/`, `/san-pham/`, `/tin-tuc/`.

### Kết quả rerun

- `/san-pham/` hiện có canonical ở cả hai viewport: `http://127.0.0.1:3135/san-pham/`.
- `/`, `/san-pham/`, `/tin-tuc/`: HTTP 200; title/H1 đúng; `robots=index, follow`.
- Home mobile menu/search/cart: 44×44; desktop search/cart: 48×48; không hồi quy H1 accessible name.
- `migration-about-preview` và `migration-home-preview`: meta robots + `X-Robots-Tag: noindex, nofollow, noarchive`.
- Console media lỗi vẫn tồn tại trên fresh build: Home 8–9 lỗi, catalog 13/19 lỗi, news 14/15 lỗi tùy viewport; tất cả là `Preview media not found`.
- Không ghi nhận console error ngoài nhóm media 404.

Canonical catalog đã **PASS** sau fix `8fbacd0`. Media mapping/edge và CWV vẫn **BLOCKED/NO-GO**.

## Media proxy follow-up với QA runtime đúng cấu hình

Lần rerun direct Nitro trước đó dùng mặc định `previewMediaProxy=false`, nên không thể dùng để kết luận media mapping sai. Đã chạy lại fresh Nitro với:

```text
NITRO_PHP_ORIGIN=http://127.0.0.1:8088
NITRO_PREVIEW_MEDIA_PROXY=1
```

Playwright ở 390px và 1440px trên `/san-pham/` đều trả HTTP 200, canonical đúng và **0 media response lỗi**. Các fixture thật qua proxy cũng trả 200:

- `/assets/images/site-visuals/catalog-products-hero-20261007-v2.png` → `image/png`
- `/assets/images/site-visuals/catalog-equipment-system.jpg` → `image/jpeg`
- `/upload/photo/logo-phucthinh-solar-wordmark-2026.png` → `image/png`
- `/thumbs/480x300x1/upload/news/du-an-anh-quan-enhanced-20261002.png` → `image/png`

Kết luận: lỗi media browser trước đó là **thiếu biến cấu hình QA**, không phải sai mapping Nuxt. Runbook/smoke phải luôn bật `NITRO_PREVIEW_MEDIA_PROXY=1`; production vẫn nên để Caddy phục vụ media PHP trực tiếp. Watermark/full media inventory chưa được đóng.


