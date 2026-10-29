# Phase 2 — UI/UX & visual audit

Ngày audit: 2026-10-29  
Worktree: `codex/full-nuxt-public-ui-20261028`  
Phạm vi: đọc source, chạy browser riêng và ghi bằng chứng; audit này không sửa source, không commit, không deploy.

## Môi trường và coverage

- PHP baseline: `http://127.0.0.1:8080` (`pts-php81-local`).
- Nuxt candidate: `.output/server/index.mjs` port `3111`, Caddy rehearsal `8094`; Caddy chuyển `/upload`, `/api` về PHP.
- Route: `/`, `/san-pham/`, `/danh-muc-san-pham/den-nlmt/`, `/du-an-da-thuc-hien/`, `/tin-tuc/`, `/ho-tro/chinh-sach-bao-mat/`, `/dai-ly/`, `/lien-he/`.
- Ảnh đã có: Home ở 360/390/768/1024/1440/1920; route còn lại ở 390/1440/1920; Caddy rehearsal ở 390/1440.
- Matrix: [`matrix-caddy.json`](evidence/uiux/matrix-caddy.json).

## Findings

### UIUX-001 — P1 — Container public lệch chuẩn ở Home

- Route/file: `/`, [HomeHero.vue](../../../../frontend/app/components/home/HomeHero.vue:10), [HomeCatalog.vue](../../../../frontend/app/components/home/HomeCatalog.vue:4), [HomeCampaigns.vue](../../../../frontend/app/components/home/HomeCampaigns.vue:2), [HomeEditorial.vue](../../../../frontend/app/components/home/HomeEditorial.vue:3).
- Expected: design-system §4.1 tại 1920px dùng công thức container chung, khoảng 1766px và gutter khoảng 77px.
- Actual: Home dùng `w-[min(calc(100%-32px),2200px)]`, rộng 1888px ở 1920px và gutter 16px; các hub khác dùng owner khác.
- Reproduce: mở `/` ở 1440/1920 và đo wrapper trực tiếp của hero/catalog/campaign.
- Evidence: [`home-nuxt-1440.png`](evidence/uiux/home-nuxt-1440.png), [`home-nuxt-1920.png`](evidence/uiux/home-nuxt-1920.png), [`home-nuxt-caddy-390.png`](evidence/uiux/home-nuxt-caddy-390.png).
- Root cause: mỗi Home section tự đặt công thức width thay vì dùng shared container.
- Proposal: dùng một wrapper public theo công thức responsive; kiểm tra 1440/1600/1920/2560 toàn bộ hub.
- Release blocker: **Có** nếu Home dùng làm chuẩn cutover.

### UIUX-002 — P2 — Hit target mobile dưới chuẩn 44×44px

- Route/file: shared header, [SiteHeader.vue](../../../../frontend/app/components/SiteHeader.vue:280), [SiteHeader.vue](../../../../frontend/app/components/SiteHeader.vue:395), [SiteHeader.vue](../../../../frontend/app/components/SiteHeader.vue:426).
- Expected: design-system §5.3/§8 yêu cầu hit target tối thiểu 44×44px.
- Actual: hamburger, search và cart có `max-ph:h-[38px] max-ph:w-[38px]`; browser đo 38×38 ở 360/390px.
- Reproduce: mở `/` ở 360px và đo các control có aria-label mở menu/tìm kiếm/giỏ hàng.
- Evidence: [`home-nuxt-360.png`](evidence/uiux/home-nuxt-360.png); log browser đo 38×38.
- Root cause: breakpoint mobile thu control theo hình học header cũ.
- Proposal: giữ visual icon nhưng tăng vùng nút tối thiểu 44px; test keyboard/focus và 320/360/390.
- Release blocker: **Không**.

### UIUX-003 — P2 — Hero dùng line-break và transform cố định

- Route/file: `/`, [HomeHero.vue](../../../../frontend/app/components/home/HomeHero.vue:3), [HomeHero.vue](../../../../frontend/app/components/home/HomeHero.vue:10), [HomeHero.vue](../../../../frontend/app/components/home/HomeHero.vue:13).
- Expected: design-system §4.4 không căn hero bằng một translate cố định và không chèn break cứng theo một viewport.
- Actual: content dùng `[transform:translateY(calc(...))]` và mobile `[transform:translateY(-14px)]`, hero `max-md:min-h-[700px]`; H1 có hai `span.block whitespace-nowrap`. Accessible text nối thành `Thiết bị và giải phápnăng lượng mặt trời`.
- Reproduce: mở `/` ở 360/390/1440/1920, kiểm tra H1 accessible name và vị trí content.
- Evidence: [`home-nuxt-360.png`](evidence/uiux/home-nuxt-360.png), [`home-nuxt-1920.png`](evidence/uiux/home-nuxt-1920.png).
- Root cause: layout hero chữa theo ảnh chụp bằng transform/min-height và line break DOM.
- Proposal: dùng flow + clamp cho padding/position, để H1 wrap theo cụm nghĩa với khoảng trắng; kiểm tra 360/390/430/1440/1920/2560.
- Release blocker: **Không**, nhưng cần xử lý trước khi khóa Hero V4.

### UIUX-004 — P2 — Icon benefit dùng glyph Unicode

- Route/file: catalog landing, [CatalogPage.vue](../../../../frontend/app/components/catalog/CatalogPage.vue:198).
- Expected: design-system §5.4 dùng một nguồn icon shared, không trộn glyph/emoji/PNG.
- Actual: benefit rail render `✦`, `⚙`, `✓`, `↗` trực tiếp; baseline và nét phụ thuộc font platform.
- Reproduce: mở `/san-pham/` ở mobile/desktop, xem dải benefit.
- Evidence: [`catalog-nuxt-caddy-390.png`](evidence/uiux/catalog-nuxt-caddy-390.png), [`catalog-nuxt-caddy-1440.png`](evidence/uiux/catalog-nuxt-caddy-1440.png).
- Root cause: icon chép vào template như ký tự trang trí.
- Proposal: thay bằng icon component shared theo owner hiện tại, giữ `aria-hidden` cho icon trang trí.
- Release blocker: **Không**.

### UIUX-005 — P2 — Copy marketing fallback nằm cứng trong component

- Route/file: Home [HomeCampaigns.vue](../../../../frontend/app/components/home/HomeCampaigns.vue:1), [HomeEditorial.vue](../../../../frontend/app/components/home/HomeEditorial.vue:3); Catalog [CatalogPage.vue](../../../../frontend/app/components/catalog/CatalogPage.vue:198).
- Expected: design-system §2.1/§9 yêu cầu copy marketing thay đổi lấy từ Admin, có fallback an toàn.
- Actual: component chứa sẵn tên/description campaign, CTA, bullet đại lý/quy trình và benefit copy; khi CMS thiếu dữ liệu, nội dung marketing vẫn do code quyết định.
- Reproduce: trả payload rỗng cho campaigns/section Home hoặc mở source fallback.
- Evidence: [`home-nuxt-caddy-390.png`](evidence/uiux/home-nuxt-caddy-390.png), source lines nêu trên.
- Root cause: fallback UI và fallback marketing content trộn cùng component.
- Proposal: tách fallback UI trung tính khỏi copy marketing; đưa copy vào DTO/Admin fields có `visible`.
- Release blocker: **Không** cho visual cutover, nhưng là data-ownership debt.

## Pass / no issue observed

- Không thấy horizontal overflow ở Caddy 390/1440; Home đã kiểm tra thêm 360/768/1024/1920.
- H1 count là một ở các route đã smoke.
- Project card dùng 4:3; News card 16:9; Solution/Contact editorial dùng 8:5.
- Policy có metadata/TOC/share/related; News có related image cards/share; Project detail có related/CTA; Gallery/Video empty state dùng “Không tìm thấy kết quả”.
- Footer/nav navy/gold bám token; sai lệch quan sát được chủ yếu là container/ownership/icon.

## Blockers / limitations

- Không chạy production và không sửa source trong audit này.
- Không chụp đủ mọi route ở mọi viewport bắt buộc vì PHP request một số route chậm; coverage thực tế đã ghi ở trên.
- Ảnh Caddy dùng PHP local 8080 và Nuxt candidate 3111; không đại diện production deploy.
