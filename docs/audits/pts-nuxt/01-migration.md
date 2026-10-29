# Phase 1 — Audit kiến trúc migration PHP → Nuxt

**Ngày:** 2026-10-29  
**Nuxt:** `/home/admin01/src/pts-nuxt-full-public-20261028`, branch `codex/full-nuxt-public-ui-20261028`, HEAD `aea5bc0`  
**PHP:** `/home/admin01/src/Web_PTS_PHP`, branch `master`, HEAD `5f616c4`  
**Phạm vi:** chỉ audit source/Git/route/API/CSS/JS; không sửa code sản phẩm, không commit/merge/deploy.

## Mẫu số và trạng thái

PHP có 18 nhóm template public (home, about, contact, dealer, catalog/PDP, solutions, projects, news, policy, gallery, video, search, account, cart, checkout, thanks và aliases). PHP API có 35 file trong `public_html/api/`. Nuxt có 23 page canonical/alias và 27 page `migration-*` preview/internal; preview không tính vào canonical coverage.

- **PASS:** mapping/source evidence tồn tại.
- **PARTIAL:** có implementation nhưng còn fixture/runtime/behavioral gate.
- **BLOCKED:** thiếu production/runtime/data evidence.
- **FAIL:** hành vi hoặc mapping sai đã xác nhận.
- **N/A:** PHP-owned Admin/API/media không phải Nuxt HTML.

## Kết luận

1. Kiến trúc candidate đúng hướng: Internet → Caddy → Nuxt SSR → PHP API/business → MariaDB. Evidence: `deploy/vps/Caddyfile:53-80`, `docs/14-nuxt-migration/PRODUCTION-CUTOVER-DRY-RUN.md:7-25`.
2. PHP vẫn render HTML nếu truy cập trực tiếp: `public_html/index.php:43-46` gọi router rồi include `templates/index.php`; `templates/index.php:15-47` dựng layout và `templates/index.php:31-34` include `$template."_tpl.php"`. Vì chưa production deploy, yêu cầu “PHP không còn render public frontend” chưa PASS.
3. Nuxt không import legacy global CSS/JS: `frontend/nuxt.config.ts:24-44`, `frontend/README.md:21`. Legacy CSS/JS vẫn tồn tại trong PHP checkout để rollback; không được xoá trước cutover.
4. Nuxt đã có page/API/component cho Home, Catalog/PDP, Solutions, Projects, News, About, Dealer, Contact, Gallery, Video, Account, Cart/Checkout/Thanks. Dynamic entity route dùng `app/pages/[slug].vue` + `server/routes/_migration/resolve-public-slug.get.ts`.
5. `/home/admin01/src/pts-nuxt4-migration` không phải checkout Git/Nuxt hoàn chỉnh trong phạm vi; audit dùng hai checkout thực tế ở trên.

## Issue register

| ID | Sev | Trạng thái | Evidence | Root cause / tác động | Fix proposal | Blocker |
|---|---|---|---|---|---|---|
| MIG-P1-001 | P1 | BLOCKED | PHP front controller/template lines trên; production dry-run ghi chưa SSH/deploy | PHP có thể tiếp tục render public HTML nếu edge chưa cutover. | Release riêng: validate Compose/Caddy, smoke public HTML + `/_nuxt/*`, xác minh PHP CSS/JS không dùng, giữ rollback. | Có |
| MIG-P2-002 | P2 | PARTIAL | `public_html/assets/css/*.css`, `assets/js/*.js`; Nuxt chỉ nạp 2 CSS nội bộ | Legacy assets chưa cleanup; chưa chứng minh runtime không dùng ngoài candidate Caddy. | Giữ rollback; sau cutover mới cleanup có kiểm kê. | Có cho full migration |
| MIG-P2-003 | P2 | BLOCKED | `deploy/vps/Caddyfile:53-80`; chưa có production checkpoint | Chưa xác minh toàn bộ route/alias/404/301/media qua một edge. | Smoke route matrix qua Caddy, ghi status/Location/content-type/origin. | Có |
| MIG-P2-004 | P2 | PARTIAL | `[slug].vue:22-61`, resolver route | Entity/category detail phụ thuộc resolver/API; integration report thiếu fixture published cho nhiều detail. | Bổ sung fixture thật và smoke canonical/404; không dùng mock để PASS. | Có cho detail parity |
| MIG-P2-005 | P2 | PARTIAL | `migration-about-preview.vue:5-12` chỉ đặt title; `nuxt.config.ts:52-64` chỉ noindex 3 preview route | Một preview route không khai báo noindex; có rủi ro index nếu edge public. | Noindex toàn bộ `migration-*` hoặc routeRule prefix; crawler smoke. | Có cho SEO |
| MIG-P3-006 | P3 | PARTIAL | 23 canonical pages + 27 preview pages; PHP template families nhiều hơn | Chưa có route manifest máy kiểm chứng trong code/test. | Dùng `route-matrix.md` làm release checklist, sau đó chuyển thành data-driven test. | Chặn PASS migration |
| MIG-P3-007 | P3 | PARTIAL | `SiteHeader.vue` fallback nav; nhiều page fallback site shell | Fallback an toàn có thể che CMS outage; không chứng minh content parity. | Giữ shell fallback, nhưng content lỗi phải noindex/log; không báo PASS. | Không |
| MIG-P3-008 | P3 | PASS có điều kiện | Nuxt package/config không import jQuery/Jssor/WOW/Toc/share; Vue components thay thế | Chưa smoke mọi interactive state qua Caddy. | Browser smoke header/lightbox/TOC/share/forms/cart. | Có behavioral parity |
| MIG-P4-009 | P4 | PASS | `main.css`, `tokens.css` đối chiếu PHP design tokens | Khác nền trước đây do section/class/thiếu band, không do PHP CSS đè khi chạy Caddy đúng. | Không đổi token global khi chưa có ảnh đo breakpoint mới. | Không |

## CSS/JS/mock/dependency

| Hạng mục | Trạng thái | Bằng chứng |
|---|---|---|
| Legacy CSS import trong Nuxt | PASS | `nuxt.config.ts:24-44` chỉ `main.css`, `tokens.css`. |
| Legacy JS import trong Nuxt | PASS có điều kiện | PHP còn jQuery/Jssor/WOW/Toc/share; Nuxt package chỉ Nuxt/Vue/Nuxt UI/Tailwind. Cần browser smoke thay thế hành vi. |
| DB access từ Nuxt | PASS | `README.md:21`; bridge server-side, không có PHP DB class trong frontend. |
| Marketing hardcode/fallback | PARTIAL | PHP Home fallback `sources/index.php:34-56`; Nuxt shell/campaign fallback. An toàn khi CMS chưa cấu hình nhưng không phải CMS evidence. |
| Preview/mock | PARTIAL | Preview pages dùng empty/error state khi API lỗi; `migration-about-preview` còn thiếu noindex evidence. |
| Media | BLOCKED runtime | Caddy giữ upload/thumbs/watermark/site visuals về PHP; chưa smoke production-like. |

## Verification và giới hạn

- Đã inventory PHP templates/sources/API, Nuxt pages/assets/package/config và Git worktrees.
- Build/typecheck/test trước checkpoint đã PASS (`npm run build`, `npm run typecheck`, `npm test`: 134/134); audit này không thay code sản phẩm.
- Không có production SSH/DNS/container checkpoint; không kết luận đã cutover.
- Không xoá PHP template/CSS/JS, không tác động DB/upload/backup.
- Dealer vẫn cần đối chiếu cùng origin/DB trước khi chốt số liệu; không hardcode `5+/41+/4.012+`.
