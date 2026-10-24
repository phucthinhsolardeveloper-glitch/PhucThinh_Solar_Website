# Kế hoạch nâng cấp cohesion website — 26/10/2026 đến 06/11/2026

- Ngày lập: 2026-10-24
- Phạm vi: storyline, content proof, shared shell/component stabilization, QA; không remake homepage đã duyệt, không đổi framework trong sprint, không deploy trong đợt audit.
- Mục tiêu deadline: có release candidate đủ câu chuyện và có thể ra quyết định GO/NO-GO trước deploy vào cuối ngày 06/11.
- Quyết định công nghệ: theo [ADR options](../09-decisions/2026-10-24-website-frontend-architecture-options.md).
- Evidence baseline: [executive audit](2026-10-24-website-cohesion-executive-audit.md), [UI system](../02-development/public-ui-ux-design-system.md), [frontend guide](../02-development/frontend-guide.md).

## 1. Backlog ưu tiên

| ID | Priority | Hạng mục | Done khi | Owner chính | Phụ thuộc |
|---|---|---|---|---|---|
| C-01 | **P0** | Proof/content register và claim freeze | Mỗi claim/certificate/project/branch/SLA có nguồn, owner, ngày duyệt; slot thiếu có fallback; không còn claim mới không xác minh | Sếp + marketing | Input sếp, marketing, sales, kho kỹ thuật |
| C-02 | **P0** | Chốt positioning và audience priority | 1 câu định vị, 3 proof pillars, ưu tiên B2C/B2B và CTA hierarchy được sếp ký | Sếp | Decision Request |
| C-03 | **P1** | Route/story map | Home, catalog, PDP, solution, project, about, dealer, news, contact đều có role, next step, related link và owner | UX/dev + sales | C-02 |
| C-04 | **P1** | Shared shell inventory | Header/nav/footer/breadcrumb/dock/CTA có owner, selector, states, URL behavior và snapshot cùng commit | Dev + QA | Browser/staging runtime |
| C-05 | **P1** | Solution → product bridge | Mỗi solution ưu tiên có relation category/product đã duyệt; thiếu relation không dựng link giả | Sales + kỹ thuật + dev | Product taxonomy/spec |
| C-06 | **P1** | PDP completeness wave 1 | 20 SKU/nhóm ưu tiên đạt ảnh, 4 specs quyết định, policy/warranty, CTA/context; SKU khác không bị điền giả | Kho kỹ thuật + marketing sản phẩm | Master data, asset thật |
| C-07 | **P1** | Project proof wave 1 | Tối thiểu case đủ field đã xác minh: bài toán, phạm vi, địa điểm/client được phép, ảnh, thiết bị liên quan; thiếu field thì ẩn | Sales + kỹ thuật | Permission/asset thật |
| C-08 | **P1** | Lead context | Form/modal nhận route, intent, solution/product và nguồn theo contract hiện có; không phá CSRF/reCAPTCHA | Dev + sales | Sales FAQ/field mapping |
| C-09 | **P1** | Media ratio and crop pass | Product 1:1 contain, solution/contact 8:5, project 4:3, news 16:9, campaign 16:5; có missing/long-content test | Dev + marketing asset + QA | Asset master |
| C-10 | **P2** | CSS primitive convergence | Focus, CTA, breadcrumb, card/media có token/owner; không thêm `!important`/legacy cleanup mù | Dev | C-04, browser evidence |
| C-11 | **P2** | News/knowledge connection | News card/detail có link liên quan đúng intent, CTA không lặp; content vẫn CKEditor | Marketing + dev | Editorial backlog |
| C-12 | **P2** | Performance/accessibility hardening | Mobile/desktop snapshot, WCAG AA, performance budgets và business flows pass | QA + dev | C-04–C-10 |

## 2. Timeline và checkpoint

| Ngày | Checkpoint | Output bắt buộc | Quyết định/đường lui |
|---|---|---|---|
| **Thứ Hai 26/10** | Kickoff + freeze | Sếp duyệt C-02 ở mức direction; route/story map v0; proof register; baseline route/flow list; phân công owner và due time | Nếu chưa có positioning: dùng wording hiện hành, không thêm claim; vẫn làm inventory/QA shell |
| **Thứ Ba 27/10** | Story + taste gate | 2–3 concept framing, 2 reference styles được chọn, C-02/C-03/C-04 sign-off; content gaps có owner và deadline | **GO/NO-GO #1:** không vào build concept nếu sếp chưa chọn positioning + reference style; fallback là stabilization-only |
| **Thứ Năm 29/10** | Evidence/content gate | Solution-product map, PDP priority list, project proof list, CTA context contract, asset ratio map | Thiếu input quá hạn: đóng slot đó, dùng fallback/ẩn; không viết placeholder hoặc claim mới |
| **Thứ Hai 02/11** | Vertical slice review | Staging/local build của shell + 1 solution + 1 category + 1 PDP + 1 project + 1 contact flow; snapshot desktop/mobile; QA findings | **GO/NO-GO #2:** nếu shell/lead/URL/business flow fail thì không mở rộng scope; sửa slice hoặc giữ current UI |
| **Thứ Sáu 06/11** | Release candidate + executive gate | Backlog P0/P1 closed/waived, QA matrix, screenshots, performance/accessibility report, rollback point, release notes | **GO/NO-GO cuối trước deploy:** chỉ GO khi sếp ký content/visual, dev/QA ký kỹ thuật, sales/kho ký business data và operator xác nhận backup/staging. Đợt audit này không deploy. |

### Quy tắc deadline và fallback

- Input đến sau checkpoint nhưng trước khi build chạm slot: dùng nội dung/asset đã xác minh gần nhất và ghi “deferred”; không tự sáng tác.
- Input quá hạn 24 giờ: owner bị đánh dấu blocked; slot chuyển sang fallback trung tính hoặc ẩn, không kéo deadline của các stream độc lập.
- Input P0 quá hạn (positioning, claim, certificate, project permission, policy/checkout): **NO-GO** cho route/feature liên quan; stabilization không phụ thuộc dữ liệu vẫn tiếp tục.
- Nếu QA không có browser runtime/staging: chỉ được báo “static/source review”; không gọi là visual/browser pass và không GO deploy.
- Mọi thay đổi data production, import Excel, migration, deploy, SSH đều nằm ngoài gói này và cần chỉ đạo riêng.

## 3. RACI phối hợp

| Workstream | Sếp | Marketing | Sales | Kho/kỹ thuật | Dev | QA/operator |
|---|---|---|---|---|---|---|
| Định vị, audience priority, concept | **A/R** | C | C | C | C | I |
| Brand voice/copy/ảnh campaign | A | **R** | C | C | C | I |
| FAQ, objections, CTA, lead context | A | C | **R** | C | C | I |
| Specs, warranty, certificate, product relation | A | C | C | **R** | C | I |
| Project/client/media permission | **A** | C | **R** | R/C | C | I |
| Template/CSS/component/build | I | C | C | C | **R** | C |
| Accessibility/performance/browser snapshot | I | C | C | C | R | **A/R** |
| Release/backup/rollback/deploy decision | **A** | I | I | I | R | **R** |

R = thực hiện, A = chịu trách nhiệm cuối, C = tham vấn, I = được thông báo. Mỗi row chỉ có một A; nếu sếp chưa chỉ định người thay mặt thì A vẫn là sếp, không tự suy đoán.

## 4. Decision Request — chỉ hỏi những việc làm thay đổi kết quả

### DR-01 — Ưu tiên định vị

Sếp chọn một hướng chính cho sprint:

- **Khuyến nghị:** “Thiết bị thật + tư vấn phương án phù hợp + đồng hành sau bàn giao” — cân bằng B2C/B2B, khớp evidence đang có và không thêm claim định lượng.
- “Nhà cung cấp thiết bị solar đáng tin cho gia đình và công trình” — thiên product/distribution.
- “Đối tác triển khai giải pháp năng lượng cho doanh nghiệp” — thiên B2B, cần project/capability proof mạnh hơn.

### DR-02 — Hai reference styles

Sếp chọn tối đa hai khẩu vị để team dùng làm guardrail, không copy nội dung:

- **Khuyến nghị:** editorial technical showroom + quiet premium industrial.
- Product catalog clarity + field-service proof.
- Corporate trust + case-study led.

Reference chỉ quyết định nhịp chữ/ảnh/mật độ; navy/gold, URL, dữ liệu thật và design system Phúc Thịnh Solar vẫn là nguồn chuẩn.

### DR-03 — Duyệt concept và scope

Sếp duyệt một concept story map tại checkpoint 27/10:

`Chúng tôi là ai → chọn theo nhu cầu → khám phá giải pháp → sản phẩm theo ứng dụng → năng lực/dự án chứng thực → quy trình/hậu mãi → CTA`

Concept được coi là duyệt khi sếp xác nhận: (a) không remake homepage đã duyệt, (b) page roles/CTA hierarchy, (c) content gaps/fallback chấp nhận được. Chi tiết pixel/state thuộc dev + QA trong system contract.

## 5. Acceptance criteria

### Content/narrative

- Mỗi route trong scope có đúng một H1, một job-to-be-done, một primary CTA và một next-step link.
- Home không bị đổi cấu trúc đã duyệt; các link từ home → solution/category/PDP/project/contact đúng URL canonical.
- Solution có context, related equipment và CTA; PDP có specs/policy/media theo dữ liệu thật; project chỉ hiển thị proof đã xác minh.
- Không có claim/certificate/client/project/outcome mới nếu chưa có owner sign-off; missing slot collapse/fallback sạch.
- CTA/form giữ route + intent + product/solution context theo contract; CSRF/reCAPTCHA/backend checkout không bị bỏ qua.

### Visual/component

- Header/menu/footer/breadcrumb/contact dock/primary CTA có cùng token, radius/surface/focus owner trên homepage, catalog, PDP, solution, project, about, dealer, news, contact.
- Product media 1:1 `contain`; solution/contact 8:5; project 4:3; news 16:9; campaign 16:5; không crop bằng chứng thật khỏi vùng an toàn.
- Không thêm gradient/pill/shadow/radius lớn chỉ để lấp khoảng trống; selector mới thuộc đúng CSS owner.

### QA snapshots/screens

- Chụp cùng commit trên 390, 768, 1440 và 1920px cho 8 route chính; ghi URL, viewport, commit, dữ liệu fixture/record, thời gian và pass/fail.
- Có before/reference từ `artifacts/ui-review-20261024/` nếu cần so sánh; không gọi artifact cũ là screenshot mới.
- Kiểm tra keyboard Tab/focus-visible, Escape/modal/drawer, hover không phải điều kiện duy nhất, reduced motion, screen reader labels cơ bản và touch target ≥44px nơi design system yêu cầu.

### WCAG/accessibility

- Contrast text/control đạt WCAG AA theo design system; focus visible trên nền sáng/navy; form label/error/status có tên và live region phù hợp.
- Không có heading hierarchy sai, alt sai cho ảnh thật, link không có accessible name, focus trap hỏng trong catalog/modal.

### Performance

- Không có horizontal overflow ở 320/360/390/768/1024/1440/1920/2560px.
- Ảnh hero/above-fold có kích thước hợp lý, lazy/fetch priority đúng vai trò; không phóng nguồn nhỏ; kiểm tra console/network failed asset.
- Ghi baseline và candidate cho HTML/JS/CSS/ảnh, LCP/INP/CLS nếu browser runtime cho phép; không đặt ngưỡng giả khi chưa đo, nhưng candidate không được regression rõ rệt hoặc tăng bundle critical không có lý do.

### Business flows

- Category → product → cart → checkout → confirmation: pass trên dữ liệu fixture, giá hiển thị/ẩn giá đúng, không đổi semantics.
- Solution/PDP/About/Project → contact/modal: CTA mở đúng form, giữ context, validation/CSRF/reCAPTCHA và success/error state đúng.
- Dealer form: field/permission/CSRF/reCAPTCHA và lead routing không đổi.
- News search/filter/pagination và related links không tạo URL lỗi; legacy public URLs vẫn hoạt động/canonical/redirect đúng.

## 6. Release gate và rollback

### GO chỉ khi

- P0 C-01/C-02 đã sếp ký; tất cả P1 trong release scope pass hoặc có waiver ghi owner/date/risk.
- QA có snapshot desktop/mobile, WCAG, performance, console/network và business-flow evidence.
- Dev xác nhận PHP lint đúng runtime, `git diff --check`, route/SEO/asset checks; operator xác nhận backup/restore point/staging.
- Sếp ký visual/content; sales ký CTA/FAQ; kho kỹ thuật ký specs/certificate/policy; marketing ký copy/media.
- Có explicit GO bằng văn bản sau checkpoint 06/11. Không deploy nếu chỉ có “looks good”.

### NO-GO khi

- Claim/proof chưa xác minh, checkout/business flow fail, URL/canonical thay đổi không có redirect plan, focus/overflow nghiêm trọng, hoặc không có rollback point.

### Rollback point

- Audit hiện tại: `c1a3c04`, chỉ thêm docs; không có production mutation.
- Runtime implementation sau này: giữ commit release đang chạy, commit candidate, previous release/image, DB/upload/config backup; rollback presentation bằng revert commit/file slice, không xóa data/Admin.
- Nếu test chỉ chạy local hoặc public HTML stale, trạng thái phải ghi “chưa đủ bằng chứng deploy”, không báo đang chạy.

## 7. Báo cáo bàn giao mỗi checkpoint

Mỗi checkpoint gửi một note ngắn gồm: đã đóng gì, evidence/link, input còn thiếu, owner/due date, risk, quyết định cần sếp, và trạng thái GO/NO-GO. Không stage/commit file ngoài ba deliverable của gói audit.
