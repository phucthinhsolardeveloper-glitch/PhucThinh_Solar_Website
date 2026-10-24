# ADR: lựa chọn kiến trúc frontend cho đợt website cohesion

- Ngày: 2026-10-24
- Trạng thái: **Đề xuất — chờ sếp duyệt sau checkpoint 27/10**
- Owner: Codex Lead UX/Tech audit; quyết định cuối: chủ project
- Phạm vi: public frontend và cách tổ chức delivery; không đổi runtime trong đợt audit.

## Context

Website hiện là monolith PHP server-rendered. `templates/index.php` nạp chung head/CSS/header/menu/breadcrumb, page template, footer/modal/JS; các route catalog, PDP, solution, project, about, news, dealer, contact và checkout dùng cùng PHP/DB/Admin. Production/local hiện được tài liệu hóa trên PHP 8.1, Apache/PHP trong Docker, MariaDB và VPS Docker/Caddy. Admin là nguồn quản lý sản phẩm, nội dung, ảnh, policy, solution và lead form; solution dài dùng CKEditor.

Frontend có 13 năm CSS chồng lớp theo [frontend guide](../02-development/frontend-guide.md), gồm legacy WordPress/theme, shared tokens và các CSS page-specific. Design system đã chốt product-first, navy/gold, URL/slug SEO, Admin-backed content, ảnh theo ratio, WCAG AA và không xóa legacy khi chưa có coverage.

Các facts này được kiểm từ source/docs; chúng không chứng minh rằng mọi route public đang ở cùng release. Public HTML ngày 2026-10-24 có crawler timestamp không đồng nhất, nên không dùng làm lý do riêng để migration framework.

## Decision

**Giữ PHP server-rendered + frontend stabilization là hướng mặc định cho 2–3 release cohesion tiếp theo.** Trong đó:

1. Chuẩn hóa narrative/content governance trước.
2. Migrate shared primitives và page composition theo vertical slice: shell → CTA/form context → media/card → solution/product/project proof.
3. Giữ route, slug, canonical, Admin, PHP checkout và backend form hiện hữu.
4. Chỉ thử frontend island ở một surface có boundary rõ, không chuyển toàn site, sau khi có browser/staging evidence và quyết định riêng.
5. Không chọn headless Next.js/Nuxt/Astro migration trong đợt 26/10–06/11.

Lý do: mục tiêu hiện tại là câu chuyện, dữ liệu thật, proof và consistency; không có evidence rằng PHP runtime là nguyên nhân chính. Đổi framework bây giờ làm tăng rủi ro SEO/URL, Admin parity, checkout, memory/hosting, deploy/rollback và maintenance mà không trực tiếp giải quyết content gap.

## Options considered

| Option | Ưu điểm | Chi phí/rủi ro thực tế | SEO/URL/Admin/commerce | Đánh giá hiện tại |
|---|---|---|---|---|
| **A. Giữ PHP + stabilization** | Tái sử dụng template/DB/Admin/CKEditor; giữ checkout và URL; rollback hẹp; phù hợp team hiện tại | CSS legacy vẫn cần inventory; phải kỷ luật component owner; không tự có DX hiện đại | SEO/slug/canonical và Admin giữ nguyên; checkout PHP không bị tách; memory gần hiện trạng | **Chọn ngay** |
| **B. Gradual frontend islands** | Cải thiện một interaction/section bằng JS/component hiện đại; rollout từng lát; ít ảnh hưởng checkout | Cần build/runtime boundary, hydration/JS budget, hai cách render, test integration; dễ tạo “island drift” | PHP giữ SEO/Admin/checkout; phải quy định data contract và cache; cần theo dõi memory/asset size | **Chỉ pilot có điều kiện** |
| **C. Headless Next.js/Nuxt/Astro migration** | Component system rõ, route composition/DX tốt, có thể tối ưu static/edge; phù hợp nếu content API và team đã sẵn | Rebuild route/SEO/redirect, Admin/CKEditor preview, product/price/cart/checkout, auth/lead, media, analytics; dual runtime/cache; tăng vận hành và rollback | Cần giữ 100% URL/canonical/structured data; PHP checkout hoặc API bridge; Admin phải đạt parity; hosting phải chạy thêm Node/build/cache | **Không chọn hiện tại** |

## Phân tích theo tiêu chí bắt buộc

### Dữ liệu, Admin và content

- PHP hiện đọc trực tiếp các nguồn Admin/DB và có fallback theo dữ liệu trống. PDP đã có structured specs/content sections; solution detail đọc CKEditor; About/project có nhiều slot điều kiện.
- Headless buộc tạo API contract, preview/draft, permission, cache invalidation, image transform, relation mapping và fallback tương đương. Nếu không, marketing không thể tự quản lý content như hiện tại.
- Islands có thể dùng lại PHP output hoặc JSON endpoint nhỏ; chỉ đáng làm khi boundary có owner và có test contract.

### SEO, URL và legacy

- URL/slug public, redirect, canonical và link cũ là tài sản phải giữ. PHP route đang gắn với taxonomy và `publicProductEntityPath()`/solution route.
- Headless có nguy cơ đổi trailing slash, breadcrumb, sitemap, hreflang, metadata, JSON-LD, 301 và link nội bộ. Không được đổi URL chỉ để “đẹp hơn”.
- Legacy CSS không phải bằng chứng cần framework mới; design system đã nêu lộ trình inventory → token → shared component → vertical slice → xóa legacy cuối cùng.

### Checkout và business flows

- Cart/checkout/confirmation là PHP/commerce flow, có CSRF, idempotency và chính sách dữ liệu riêng. Chuyển frontend cần chứng minh add-to-cart, price visibility, COD/chuyển khoản, order detail, thank-you, email/lead và retry không đổi.
- Trong audit này không chạm checkout. Bất kỳ headless proposal nào cũng phải giữ PHP checkout hoặc có API contract + rollback rehearsal trước khi migrate.

### Runtime, memory, hosting và maintenance

- Current VPS được tài liệu hóa là Docker/Caddy → Apache/PHP 8.1 → MariaDB; release không chứa config/upload/cache/log và giữ current/previous/rollback release.
- PHP-only tiếp tục dùng hosting/runbook hiện có, ít process và ít cache layer mới.
- Islands thêm JS bundle/build artifact nhưng có thể giới hạn trong một lát; cần đo HTML/JS/LCP/INP/memory trước và sau.
- Headless thêm Node runtime/build pipeline, SSR/ISR/cache, process healthcheck, deploy artifact, log và rollback; cần capacity/budget/support ngoài phạm vi hiện tại. Không suy ra memory limit cụ thể khi chưa có profiling production.

### Team và maintenance

- Team được phân vai marketing/sales/kho kỹ thuật/dev/QA; không có evidence về ownership Node platform, API platform hay dual-render operations.
- PHP stabilization tận dụng năng lực và runbook có sẵn. Headless chỉ hợp lý khi có người owner API/Node/SEO/observability, staging và lịch bảo trì dài hạn.

## Decision gates

### Gate 0 — trước khi build

- Sếp duyệt positioning, reference taste và concept.
- Marketing/sales/kỹ thuật ký nguồn content/proof; không có claim/certificate/dự án không xác minh.
- Route matrix và business flow matrix được đóng băng.

### Gate 1 — nếu cân nhắc islands

- Có một component boundary cụ thể, data contract versioned, server fallback và rollback bằng một file/feature flag.
- Playwright desktop/mobile, keyboard/WCAG, performance budget và console error baseline pass trên staging.
- Không tăng JS critical path hoặc memory/response risk vượt budget đã duyệt.

### Gate 2 — chỉ mở headless discovery khi đủ tất cả điều kiện

1. Có owner và ngân sách maintenance Node/API tối thiểu cho 2 quý.
2. API/content model đạt parity với Admin cho product, solution, project, about, news, policy, media và lead.
3. Có preview/draft/publish, RBAC, audit trail, cache invalidation và fallback khi API lỗi.
4. Có route inventory + redirect/canonical/structured-data snapshot và kế hoạch giữ URL public.
5. Có checkout strategy đã test: giữ PHP checkout ổn định hoặc API contract được kiểm thử end-to-end.
6. Có staging gần production, performance/load profile, error monitoring, backup/rollback và restore rehearsal.
7. Có cutover canary và rollback trong cùng ngày; không deploy thẳng production.

### Trigger đủ điều kiện để chuyển công nghệ

Chỉ mở ADR migration mới khi **ít nhất một pain có số liệu** (deploy frequency bị chặn, PHP response/memory bottleneck, content release cần API/preview mà PHP không đáp ứng, hoặc UX surface cần interaction không thể làm an toàn trong PHP) và **tất cả Gate 2** đã đạt. “Muốn đẹp hơn”, “framework mới phổ biến” hoặc CSS nhiều thế hệ không đủ trigger.

## Consequences

### Tích cực

- Deadline 06/11 tập trung vào câu chuyện, proof, CTA context và cohesion có thể đo.
- Giữ SEO/URL/Admin/checkout và rollback nhỏ.
- Có đường mở rộng islands nhưng không tạo migration cliff.

### Đánh đổi

- Một số legacy CSS tồn tại lâu hơn; phải chấp nhận cleanup có thứ tự.
- PHP templates vẫn cần discipline về data contract và content ownership.
- Nếu sau này headless được duyệt, sẽ phải đầu tư discovery/API/preview trước khi có visual payoff.

## Rollback

- Rollback của audit này: không có runtime mutation; bỏ 3 docs mới nếu chủ project không chọn.
- Rollback của stabilization: revert commit vertical slice tương ứng, giữ DB/assets/Admin; không xóa legacy/data.
- Rollback của islands: tắt flag/khôi phục server-rendered path và giữ artifact trước đó.
- Headless chỉ được phép có rollback point là PHP release hiện tại + DB/upload/config backup đã kiểm tra; không chấp nhận rollback bằng “deploy lại source mới”.
