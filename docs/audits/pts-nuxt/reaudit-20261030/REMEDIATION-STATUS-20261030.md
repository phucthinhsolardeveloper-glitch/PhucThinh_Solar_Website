# Remediation status — re-audit Nuxt 2026-10-30

## Phạm vi và checkout

- Checkout: `/home/admin01/src/Web_PTS_PHP`, nhánh `master`, HEAD nền `e09f0bf`.
- Chỉ thay đổi code local/QA và tài liệu audit. Không stage, commit, push, deploy, gọi CMS production hay sửa dữ liệu production.
- Working tree đã có WIP trước lượt này; không reset hoặc dọn các thay đổi đó.
- Audit gốc và TSV là baseline. Bảng dưới ghi trạng thái sau remediation; artifact raw không bị viết lại.

## Tóm tắt

Các lỗi đã xác nhận trong báo cáo được xử lý tại local: route tuyển dụng không còn handoff về PHP; race đổi tỉnh/Phường-Xã được chặn ở Nuxt và PHP legacy; media lỗi sớm trước hydration có fallback/retry; header tôn trọng reduced motion; hover cards được làm phẳng; dark CSS không còn kích hoạt theme chưa được duyệt; font scale responsive được đồng bộ theo số đo PHP. Mục header breakpoint 1681px giữ nguyên vì đó là quy tắc đã được design-system phê duyệt.

Dealer stats `5/50/5000` hiện lấy từ local PHP API, nhưng claim `5+/41+/4.012+` thuộc môi trường khác và không có production payload được phép đọc: vẫn **BLOCKED** để kết luận số liệu production.

## Issue status

| Finding | Trạng thái | Xử lý / bằng chứng | Còn lại |
|---|---|---|---|
| Route `/tin-tuyen-dung/` handoff 307 | FIXED | Thêm `frontend/app/pages/tin-tuyen-dung.vue`; route data-driven qua NewsCategoryPage. Browser local xác nhận HTTP 200, H1/title “Tin tuyển dụng”, robots `noindex,nofollow,noarchive` khi danh mục rỗng. Route regression test PASS. | Nội dung rỗng nên chưa index; Admin cần publish bài để đổi indexability. |
| IA-01 checkout stale ward/shipping request | FIXED | Nuxt dùng request identity/abort và snapshot guard. PHP `assets/js/functions.js` dùng request identity/abort và kiểm tra city/ward snapshot trước khi áp kết quả. PHP race-order VM simulation PASS 4/4; checkout/UI focused tests nằm trong 27/27 PASS. | Không submit đơn/ghi DB trong QA này. |
| IA-02 PDP ảnh lỗi bị hiểu nhầm là CMS chưa cấu hình | FIXED / browser-verified | `ProductPreviewGallery.vue` đối soát `complete && naturalWidth === 0` sau mount và khi đổi media/retry, ngoài `error` handler. Playwright chặn toàn bộ image requests trước khi tải route; PDP hiện “Không tải được hình ảnh sản phẩm…” và nút “Thử tải lại ảnh”; sau click, fallback vẫn hiện khi request tiếp tục bị chặn. | Không so sánh HTTP media fallback PHP vì template PHP không có cùng runtime handling. |
| PDP gallery surface và nhịp section | FIXED / browser-verified | Bỏ border/shadow hover quanh ảnh chính; giữ khung trắng và radius nhỏ. Các section PDP dùng 48px dọc trên mobile và 64px từ tablet (`390px = 48px`, `1440px = 64px` theo computed style), khớp spacing contract. | Crop/scale ảnh vẫn khác PHP ở mẫu đã audit. |
| UI-02 body/HomeHero typography scale | FIXED theo số đo local PHP | CSS responsive body 17px/18px/20px ở mobile/desktop/2K; HomeHero scale đã chỉnh. Playwright computed style xác nhận body 17/18/20px tại 390/1440/1920px; font Be Vietnam Pro load; document không tràn ngang. | Composition căn giữa của HomeHero là hướng thiết kế hiện tại, không buộc parity pixel với PHP. |
| UI-03 header nav chỉ ngang từ 1681px | CLOSED theo design contract | `docs/02-development/public-ui-ux-design-system.md` yêu cầu giữ hamburger đến 1680px, nav desktop từ 1681px. Không đổi breakpoint để tránh phá contract. | Không còn là defect; audit gốc dùng tiêu chí parity PHP cũ nên đọc như lịch sử. |
| UI-04 hover cards News/Project quá mạnh | FIXED | Bỏ card lift/shadow và giảm image zoom; giữ affordance màu/arrow và motion-reduce. Đối chiếu PHP final CSS và approved flat-surface rule. | Không có screenshot pointer-state mới; sửa trực tiếp theo class/source final override. |
| UI-06 spacing tokens | PARTIAL / enhancement | PDP sections nay hội tụ về 48/64px đã duyệt. Chưa có bằng chứng mọi page-family spacing còn lại gây lỗi; section-specific values vẫn dùng trong hệ hiện hành. | Tiếp tục theo từng page-family khi có mismatch được xác nhận; không token hóa đại trà. |
| TOK-06 icon weight/fill consistency | PARTIAL / enhancement | Không thấy icon hỏng/mất trong route mẫu; Nuxt SVG thay Font Awesome có chủ ý. | Thống nhất icon catalog/weight theo component system khi triển khai tiếp UI polish; chưa có finding visual/browser cụ thể để fix đại trà. |
| UI-07 hai palette header/brand | CLOSED bằng exception có tên | Header palette được ghi nhận là semantic exception, khớp PHP `header-v3.css`; ghi chú đã thêm vào public UI system và CSS. | Không có đổi màu runtime cần thiết. |
| UI-08 latent dark CSS trái light-only | FIXED | `tokens.css` thành light-only reset; `SolutionsIntro` bỏ các `dark:` class còn xung đột. Browser thêm class `.dark` vẫn computed white background và `color-scheme: light`. | Dark theme chưa phải mục tiêu sản phẩm. |
| UI-09 reduced-motion header | FIXED / browser-verified | CSS và `SiteHeader.vue` bỏ transform/scroll hide khi OS yêu cầu reduced motion. Browser emulation xác nhận header transform `none`, transition `0s` sau scroll. | Không có. |
| Hydration mismatch khi gTranslate tự phát hiện ngôn ngữ | FIXED / browser-verified | Console khi browser locale English cho thấy widget ngoài sửa text SSR trước khi hydration. `app.vue` nay nạp popup script từ `onMounted`; khi chặn CDN trong browser, nội dung tiếng Việt hydrate với 0 warnings. Sau đó để CDN chạy, trang đã được widget dịch sang English như cấu hình hiện tại. | Không có. |
| Mobile horizontal sections MHR-01…04 | FIXED / QA verified | Catalog, dealer, PDP applications dùng rail một hàng trên mobile; evidence pair 390px có trong `mobile-horizontal-evidence/remediation-20261030/`. Test liên quan Catalog/contract 22 PASS theo lượt remediation; focused suite hiện tại 27/27 PASS. | Gallery/video local dataset rỗng nên không có rail item để đối chiếu. |
| UI-05 homepage lighting composition | PASS / verified QA | PHP `api/pts_v1_home.php` và Nuxt `/_migration/home` cùng 4 hero/7 categories/0 campaigns/configured0/4 solutions/5 process/3 groups; 7 category DTO khớp; cùng fallback campaigns và visibility. | Dữ liệu local QA, không đọc production CMS. |
| Dealer stats 5/50/5000 vs 5/41/4.012 | BLOCKED | Local API xác nhận PHP/Nuxt đều lấy 5/50/5000 từ CMS DTO, Nuxt không hardcode. | Không truy cập production payload; cần data có nguồn để kết luận con số production. |

## Route coverage sau remediation

Baseline sitemap có 457 URL: 428 HTTP 200, 28 canonical redirect, 1 route 307 về PHP. Finding duy nhất đã được thêm page Nuxt và xác nhận trực tiếp ở browser là `/tin-tuyen-dung/` HTTP 200. Suy ra theo đúng baseline (không chạy lại toàn bộ sitemap): **429 direct 200 + 28 canonical redirects đã kiểm tra đích = 457/457 route disposition trên Nuxt**. Raw TSV được giữ làm bằng chứng lịch sử, không sửa thành kết quả mới.

Route này có page content rỗng theo CMS; trạng thái SEO noindex là có chủ ý, không phải thiếu route.

## Kiểm chứng sau remediation

- `node --test tests/commerce-preview-ui.test.mjs tests/editorial-category-public-routes.test.mjs tests/checkout-contract.test.mjs tests/checkout-preview-safety.test.mjs` trong `frontend/` — **27/27 PASS**.
- `npm run typecheck` trong `frontend/` — **PASS**, exit code 0. Ghi chú cũ trong mobile audit đã được đánh dấu là lịch sử.
- PHP legacy checkout: `node --check` cho `functions.js`/`apps.js`, race-order VM simulation **4/4 PASS** theo kết quả agent thực hiện.
- Browser QA: recruitment route 200/SEO metadata đúng; PDP với request ảnh bị chặn trước hydration hiện media error/retry; main gallery không border/shadow; PDP section spacing 48/64px tại 390/1440; reduced-motion header đứng yên; `.dark` không đảo theme; body font scale responsive khớp các điểm đã đo; gTranslate dịch sau hydration và route kiểm tra có 0 hydration warnings.
- `git diff --check` sẽ chạy sau khi tài liệu và code cập nhật xong.

## Kết luận và rủi ro

- Các lỗi migration trong báo cáo có thể kiểm chứng bằng local đã sửa hoặc đóng theo hợp đồng được duyệt.
- Đây không phải xác nhận production-ready: chưa gọi CMS production, chưa submit order, chưa deploy hoặc smoke production-like Caddy/Compose trong lượt này.
- Không thay đổi production state; không có backup/rollback artifact mới vì không chạy deployment.
- Rollback là revert các diff local cụ thể; do working tree chứa WIP nhiều phiên, không dùng reset/checkout toàn file. Không commit trong lượt này.

## Files chính đã thay đổi trong lượt remediation

- Checkout: `frontend/app/pages/thanh-toan.vue`, `domains/phucthinhsolar.com/public_html/assets/js/functions.js`.
- PDP/runtime: `frontend/app/components/product-preview/ProductPreviewGallery.vue`, `frontend/app/app.vue`, `frontend/tests/commerce-preview-ui.test.mjs`.
- Route: `frontend/app/pages/tin-tuyen-dung.vue`, route fixtures/tests.
- UI/responsive: `frontend/app/assets/css/main.css`, `frontend/app/assets/css/tokens.css`, `SiteHeader.vue`, `HomeHero.vue`, News/Project/Product/Dealer/Catalog/Solution card components listed in `git status`.
- Typecheck: `frontend/package.json`, `frontend/package-lock.json`, `frontend/nuxt.config.ts`.
- Audit: this report, `03-interaction-accessibility.md`, `FINAL-UI-PARITY-20261030.md`, `MOBILE-HORIZONTAL-SECTION-AUDIT-20261030.md`.
