# FINAL — Audit parity giao diện Nuxt so với PHP

> **Cập nhật remediation:** các số liệu dưới đây là snapshot audit gốc trước khi route tuyển dụng được thêm. `/tin-tuyen-dung/` hiện đã được Nuxt render HTTP 200; theo baseline đó, hiện có 429 trực tiếp 200 + 28 canonical redirects = 457/457 route disposition. Các finding đã sửa/đóng và giới hạn còn lại xem [REMEDIATION-STATUS-20261030.md](REMEDIATION-STATUS-20261030.md). TSV gốc được giữ nguyên làm evidence lịch sử.

- Ngày audit: 2026-10-30
- Checkout: `/home/admin01/src/Web_PTS_PHP`, nhánh `master`, HEAD khi bắt đầu `e09f0bf`.
- So sánh runtime local: PHP `127.0.0.1:8080` và Nuxt `127.0.0.1:3100`.
- Mục tiêu: xác định phần nào còn thiếu so với PHP, phần nào là khác biệt thiết kế mới, và phần nào chưa đủ bằng chứng.
- Phạm vi an toàn: audit read-only; không sửa product code, không thay dữ liệu, không gọi production, không commit/push/deploy.

## 1. Tóm tắt điều hành

Sau remediation, cả 457 URL trong sitemap local đều có Nuxt route disposition: **429 trả 200 trực tiếp** và **28 redirect canonical có đích 200**. Route tuyển dụng trước đây trả 307 về PHP nay được render trong Nuxt; danh mục chưa có nội dung giữ `noindex` theo đúng trạng thái data. Đây chỉ xác nhận route coverage theo sitemap local, không xác nhận toàn bộ dynamic states hay production readiness.

Ở 10 nhóm trang đại diện, đo PHP và Nuxt tại 13 viewport từ 320 đến 2560px, **không ghi nhận horizontal overflow trong 260 lần đo**. Các nội dung quan trọng được yêu cầu trước đây nhìn chung đã có: catalog FAQ/ứng dụng/CTA, CTA listing dự án, dự án khác, news related/share, policy metadata/TOC/share/related, sticky TOC giải pháp từ 768px, empty state gallery/video và navy CTA/footer. Nuxt cũng khắc phục cấu trúc hai H1 trên PHP ở trang dự án và đại lý.

Sau remediation, các lỗi parity đã xác nhận ở route tuyển dụng, type scale, hover surfaces, reduced motion và fallback media được sửa hoặc đóng theo hợp đồng UI. Phần còn lại không phải lỗi P2 đã tái hiện: spacing/icon consistency là polish backlog; dealer production stats cần payload có nguồn để xác minh.

Nền navy CTA/footer **không bị thiếu hàng loạt** và token navy/gold cơ bản **không bị khai sai**. Các section navy đã render ở catalog, project, news và contact mẫu. Cảm giác “nền khác” đến từ palette header riêng, solid/gradient khác nhau và kích thước/spacing của từng section hơn là thiếu một utility class chung.

## 2. Mẫu số và kết quả coverage

### Route coverage

| Kết quả | Số lượng | Tỷ lệ trên 457 URL |
|---|---:|---:|
| Nuxt trả 200 trực tiếp | 428 | 93,65% |
| Canonical/alias redirect, đích Nuxt trả 200 | 28 | 6,13% |
| Handoff sang PHP | 1 | 0,22% |
| Tổng URL sitemap | 457 | 100% |

Snapshot audit ban đầu là **456/457** (428 direct 200 + 28 canonical redirects; route tuyển dụng 307 sang PHP). Sau remediation, route tuyển dụng trả 200 trực tiếp, nên coverage theo cùng mẫu số là **457/457 route disposition** (429 direct 200 + 28 canonical redirects). Raw response TSV vẫn giữ kết quả baseline để truy nguyên thay đổi.

Đây là sitemap do PHP local cung cấp, không phải sitemap production được đối chiếu trong lượt này. Không dùng tỷ lệ trên để tuyên bố toàn bộ dynamic/query states đã đạt.

### Responsive và browser evidence

- **10 route families × 2 hệ × 13 viewport = 260 DOM measurements.** Không thấy overflow ngang; tất cả Nuxt route mẫu có đúng 1 H1.
- Hai trang PHP cũ có 2 H1 trong DOM: `/du-an-da-thuc-hien/` và `/dai-ly/`. Nuxt có một H1 ở cả hai.
- Runtime details và raw output: [04-runtime-visual-and-responsive.md](04-runtime-visual-and-responsive.md), [05-responsive-results.tsv](05-responsive-results.tsv), [04-sitemap-route-results.tsv](04-sitemap-route-results.tsv).
- Ảnh so sánh 1440px: [home PHP](evidence/home-php-1440.png), [home Nuxt](evidence/home-nuxt-1440.png), [catalog PHP](evidence/category-php-1440.png), [catalog Nuxt](evidence/category-nuxt-1440.png), [PDP PHP](evidence/pdp-php-1440.png), [PDP Nuxt](evidence/pdp-nuxt-1440.png), [contact PHP](evidence/contact-php-1440.png), [contact Nuxt](evidence/contact-nuxt-1440.png).

## 3. Kết luận theo nhóm trang/tính năng

| Nhóm | Kết quả | Phần thiếu/khác cần biết |
|---|---|---|
| Shared shell/header/footer | PARTIAL | Header breakpoint 1681px theo hợp đồng đã duyệt; footer deep navy tồn tại. Nuxt dùng SVG/icon stroke thay Font Awesome; chưa có icon-level consistency issue được tái hiện. |
| Home | PARTIAL | CMS hero 4 slide được PHP API cấp cho cả Nuxt. `HomeCatalog` + `HomeCampaigns` giữ cùng dữ liệu/fallback/CTA/order/visibility với PHP (F-01/UI-05 PASS). Scale H1 đã điều chỉnh theo viewport; canh giữa là lựa chọn thiết kế hiện hành. |
| Catalog landing + category | PASS trong route mẫu | Hero/category navigation, filter, ứng dụng, FAQ và support CTA render. CTA cuối catalog hiện có. Không thấy block yêu cầu cũ bị thiếu trong mẫu đã so. |
| PDP | PASS nội dung mẫu / PARTIAL visual | Gallery, nội dung kỹ thuật, ứng dụng, policy, FAQ, CTA và related products có. Bỏ border/shadow thừa quanh gallery; section spacing khớp 48px mobile/64px tablet-desktop. Media URL hỏng có error copy/retry đã browser-verified; crop/scale ảnh vẫn khác PHP. |
| Solutions | PASS | Nội dung/CTA hiện có. Sticky TOC mới chạy tại viewport ≥768px; xác nhận `position: sticky` tại 768/900/1440 và static tại 390px. |
| Projects listing/detail | PASS nội dung mẫu | Listing có CTA tư vấn; detail có “Dự án khác” và CTA. Nuxt sửa H1 legacy; hover lift/shadow đã gỡ theo PHP final surface. |
| News listing/detail | PASS nội dung mẫu | Related cards/share hiện có. News detail có phần nội dung và share; ảnh related lấy từ path ảnh thật. Hover được làm nhẹ theo flat-surface rule. |
| Policy detail | PASS nội dung mẫu | Có ngày/tác giả, mục lục, share và tin liên quan; Nuxt bổ sung H1 rõ mà PHP page mẫu thiếu. |
| Dealer | PARTIAL data verify | Nuxt lấy stats qua PHP API, không hardcode; local API trả 5/50/5000. Con số 5/41/4.012 được nhắc từ môi trường khác chưa tái hiện, cần production CMS evidence mới xác minh được. |
| Gallery/video | PASS empty state | Hai hệ cùng báo “Không tìm thấy kết quả” khi dataset local rỗng; không thấy copy “đang được cập nhật”. |
| Contact/about | PASS nội dung chính / design delta | Contact giữ form, hotline, địa chỉ và hero data. Nuxt thêm generic H1 phía trên hero; PHP dùng câu tư vấn làm H1 trong hero. |
| Search/cart/checkout | PARTIAL | Route render được ở mẫu; checkout GET là route Nuxt mới, PHP legacy submit qua `/gio-hang`. Không test submit/order end-to-end trong UI audit. |
| Tin tuyển dụng | PASS route; noindex data state | Nuxt có page API-backed; khi danh mục chưa có bài, trả 200 với title/H1 đúng và `noindex`. |

## 4. Token/nền/typography/motion

### Màu và khối navy

- Tailwind v4 palette nằm trong `frontend/app/assets/css/main.css` (`@theme`); `tokens.css` hiện là light-only reset.
- Bộ màu shared PTS chính khớp PHP: `#0a2251`, `#061537`, `#123a7a`, `#c9962e`, `#e0b654`, `#9c7420`, `#64748b`, `#f8fafc`, `#e5e7eb`.
- Header dùng `hdr-*` palette cũng tồn tại trong PHP `header-v3.css`; đây được ghi nhận là semantic exception và runtime khớp PHP.
- Runtime đã xác nhận CTA/navy sections trên catalog, project, news, contact và footer. Không cần dựng thêm một navy CTA/footer dùng chung vì section tương ứng đã có trong các mẫu này.

### Typography/container

- Audit baseline body tại 390/1440/1920px là PHP `17/18/20px`, Nuxt `16/16/16px`; sau fix, browser computed body Nuxt là `17/18/20px` và Be Vietnam Pro đã load.
- Audit baseline Home H1 là PHP `40.95/50/62.4px`, Nuxt `29.25/54.72/72px` tại 390/1440/1920px; sau fix responsive clamp đưa H1 Nuxt về 40.95/49.68/62.4px.
- PHP có 2K container tối đa 1920px; Nuxt mở tới 2200px từ công thức tại `main.css`. Đây là đổi nhịp layout ultrawide đáng kể, có thể là redesign chủ ý.
- Nuxt có gutter/container nhưng chưa có semantic section-spacing token được áp dụng toàn trang; hiện trộn Tailwind scale và arbitrary px. Chưa đủ bằng chứng để nói mọi khoảng cách đều sai.

### Motion/hover

- Remediation đã bỏ lift/shadow của project/news cards, giảm zoom ảnh và giữ affordance màu/arrow nhẹ.
- PDP cross-fade, optical zoom, header scroll-hide, overlay search và solution TOC là nâng cấp mới, không phải phần thiếu so PHP.
- Header scroll transform và các animation liên quan nay có reduced-motion policy; browser emulation xác nhận header đứng yên khi tùy chọn bật.

## 5. Findings theo severity

| Severity | Số lượng | Nội dung |
|---|---:|---|
| P0 | 0 | Không có lỗi nghiêm trọng kiểu site/data loss được xác nhận |
| P1 | 0 | Không có lỗi production trực tiếp được kiểm tra |
| P2 còn mở | 0 | Các P2 baseline đã sửa hoặc đóng theo design contract |
| P3 còn mở | 3 | Spacing scale polish; icon weight/fill consistency; dealer stats production verification (BLOCKED do không có production payload) |
| P4 | 0 | — |

Đây là số finding còn mở sau remediation, không phải số lượng lịch sử trong audit baseline. Xem [REMEDIATION-STATUS-20261030.md](REMEDIATION-STATUS-20261030.md) để phân biệt enhancement với xác minh bị chặn.

## 6. Vấn đề nghiêm trọng và việc còn lại theo ưu tiên

1. **Dealer stats:** xác nhận bộ số production bằng payload CMS do chủ dự án/QA cung cấp; hiện chỉ biết local CMS là 5/50/5000.
2. **Spacing scale:** chuẩn hóa khi có mục tiêu section cụ thể, không token hóa mọi micro-gap.
3. **Icon consistency:** chốt quy ước fill/stroke/size nếu tiếp tục polish; chưa có lỗi icon cụ thể được tái hiện.

## 7. Quyết định audit

- **UI parity local:** CONDITIONAL GO — route disposition trong sitemap đạt 457/457; các P2 baseline đã xử lý; còn dealer CMS production data và một số consistency enhancements chưa xác minh/hoàn thiện.
- **Cutover production:** chưa đủ bằng chứng để GO. Local route coverage không chứng minh production CMS, API/session/commerce, hạ tầng hay rollback; không deploy trong đợt này.
- **Tiếp tục nâng cấp UI trong QA:** GO. Các findings đã có file/route/evidence và có thể dùng làm backlog nâng cấp.
- **Production:** không truy cập, không thay đổi, không deploy.

## 8. Tài liệu kiểm chứng

- [04-runtime-visual-and-responsive.md](04-runtime-visual-and-responsive.md) — runtime findings, screenshot, token/spacing/type/motion detail.
- [01-route-content-parity.md](01-route-content-parity.md) — PHP → Nuxt route/content mapping và F-01.
- [02-tokens-motion.md](02-tokens-motion.md) — code-level palette/surface/motion review; findings đã được đối chiếu bổ sung bằng runtime ở report 04.
- [03-interaction-accessibility.md](03-interaction-accessibility.md) — interaction/accessibility source review và checkout race.
- [04-sitemap-route-results.tsv](04-sitemap-route-results.tsv) — response code/title/H1 cho 457 URL.
- [05-responsive-results.tsv](05-responsive-results.tsv) — DOM measurements cho 260 viewport checks.
- [REMEDIATION-STATUS-20261030.md](REMEDIATION-STATUS-20261030.md) — trạng thái sau sửa, test/browser evidence và giới hạn hiện hành.

Toàn bộ artifacts chỉ nằm trong `docs/audits/pts-nuxt/reaudit-20261030/`; các file WIP của phiên khác được giữ nguyên và không stage.
