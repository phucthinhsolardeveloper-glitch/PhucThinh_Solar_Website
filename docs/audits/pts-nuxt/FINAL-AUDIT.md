# FINAL-AUDIT — PTS Nuxt 4 migration

Ngày: 2026-10-29 (Asia/Bangkok)  
Kết luận: **NO-GO thay thế frontend PHP ở thời điểm audit**.

## 1. Executive summary

Audit đã đọc source/Git/runtime của hai checkout thực tế:

- Nuxt: `/home/admin01/src/pts-nuxt-full-public-20261028`, branch `codex/full-nuxt-public-ui-20261028`, `aea5bc0`.
- PHP master: `/home/admin01/src/Web_PTS_PHP`, branch `master`, `5f616c4`.
- Đường dẫn người dùng nêu `/home/admin01/src/pts-nuxt4-migration` không phải checkout Git/Nuxt hoàn chỉnh, chỉ có `domains/`; không dùng làm bằng chứng.

Nuxt đã có mapping source cho các nhóm public chính và build/typecheck/test đều pass. Tuy nhiên chưa đủ điều kiện cutover: PHP vẫn có thể render HTML trực tiếp, production PHP 8080 thiếu route resolver làm hỏng bare-slug detail, preview PHP 8088 lệch DTO contract, media/edge chưa được smoke production-like, commerce chưa có session/order evidence, và còn lỗi UI P1/P2. Không có deploy, DB write, DNS change hay production mutation trong audit.

## 2. Tỷ lệ có mẫu số

| Chỉ số | Mẫu số và kết quả | Diễn giải |
|---|---:|---|
| Mapping PHP template → Nuxt source | 18/18 = **100%** | Chỉ là coverage source; không đồng nghĩa runtime parity/cutover. |
| Canonical/alias Nuxt page inventory | 23/23 = **100%** | 27 `migration-*` preview bị loại khỏi canonical. |
| Route browser smoke mẫu | 13/15 = **86,7% PASS** | 13 route 200/render; 2 bare-slug detail FAIL 502. Không phải tỷ lệ toàn site. |
| Functional interaction checks | 5/8 = **62,5% PASS** | 2 bị BLOCKED vì CMS rỗng; 1 FAIL do resolver. |
| Read-only API groups chính | 10/10 = **100% 200** trên hai PHP origin | site/home/catalog/news/projects/solutions/dealer/contact/about/gallery/video; contract parity vẫn chưa đủ. |
| Valid detail fixtures | 4/4 = **100% 200** | news/project/solution/product detail API riêng; không chứng minh public slug resolver. |
| Negative mutation checks | 3/3 = **100% expected** | lead 400, cart/review 419; chưa phải commerce E2E. |
| Automated quality checks | 3/3 PASS | typecheck, 134/134 tests, production build. |

## 3. Consolidated issue count

Đã gộp các phát hiện trùng giữa năm báo cáo theo cùng root cause; không cộng lặp F/BE/REL cho một lỗi:

| Severity | Số issue hợp nhất |
|---|---:|
| P0 | 0 |
| P1 | 3 |
| P2 | 11 |
| P3 | 3 |
| P4 | 0 |

P1 hợp nhất: cutover/PHP direct rendering chưa được chứng minh; resolver production/preview contract làm hỏng bare slug; Home container lệch chuẩn khi Home là chuẩn cutover. P2 gồm noindex preview, product 404 semantics, projection 8080/8088 lệch, media edge, commerce evidence, legacy runtime gate, mobile hit target, hero/accessibility, icon glyph, hardcoded marketing fallback và thiếu published fixtures. Chi tiết có ID gốc trong các báo cáo Phase 1–3.

## 4. Route/feature coverage

Xem đầy đủ tại [route-matrix.md](./route-matrix.md). Tóm tắt:

- PASS source/runtime mẫu: home, catalog, category, solutions hub/detail source, projects listing, news listing, about, contact, dealer, policy detail, gallery/video empty state.
- PARTIAL/BLOCKED: brand/PDP fixture, project/news bare slug, gallery detail, cart, checkout, account, thanks, media path, production aliases qua edge.
- PHP-owned exception được giữ: `/api/*`, `/administrator/*`, `/upload/*`, `/thumbs/*`, `/watermark/*`, legacy media và session actions.
- PHP vẫn render `index.php` → router → `templates/index.php`; do đó “old PHP frontend đã loại hoàn toàn” chưa PASS ở source/runtime ownership.

## 5. UI/UX

Đã đối chiếu PHP/Nuxt bằng ảnh ở Home 360/390/768/1024/1440/1920; các hub ở 390/1440/1920 và Caddy rehearsal 390/1440. Những phần đã xác nhận: card ratio, empty copy “Không tìm thấy kết quả”, policy metadata/TOC/share/related, news image-related/share, project related/CTA, navy/gold token/footer. Vấn đề còn lại: Home container 16px thay vì container chung, controls mobile 38px (<44px), H1 accessible spacing, Unicode benefit glyph và marketing fallback hardcoded. Chi tiết tại [02-uiux.md](./02-uiux.md).

## 6. Backend/API/data

Các API list chính trả 200, negative status/security header pass. Blocker là `pts_v1_public_route_resolve.php` không có ở PHP 8080 và DTO 8088 trả `canonicalPath/kind` trong khi Nuxt yêu cầu `canonical/entity`. Hai PHP origin còn lệch các projection home/projects/dealer; dealer hiện trả 5/50/5000 ở cả hai origin, chưa có bằng chứng DB snapshot cho 5/41/4.012. Chi tiết tại [04-backend.md](./04-backend.md).

## 7. Release/quality

`npm run typecheck`, `npm test` (134/134) và `npm run build` pass; log tại `evidence/release/`. Chưa có Lighthouse/CWV, production SSH/DNS checkpoint, authenticated commerce/payment flow, hoặc edge media smoke. Preview noindex chưa phủ chắc toàn bộ `migration-*`. Chi tiết tại [05-release.md](./05-release.md).

## 8. Ưu tiên trước khi audit lại

1. Đưa resolver endpoint + library lên đúng PHP master/prod, chốt DTO `canonical/entity`, test bare slug qua Caddy.
2. Chạy một release candidate đồng bộ PHP/Nuxt (SHA/checksum), smoke public HTML, aliases/301/404, `/_nuxt/*`, API, media và rollback.
3. Chuẩn bị CMS fixtures thật cho product/brand/solution/project/news/policy/gallery; chạy commerce category→product→cart→checkout→confirmation bằng sandbox/session/CSRF.
4. Phủ noindex toàn bộ preview, sửa product 404 pass-through và đồng bộ projection 8080/8088.
5. Sửa UI P1/P2, sau đó đo CWV/accessibility và chụp lại baseline PHP/Nuxt.

## 9. Go decision, production state và rollback

- **Decision: NO-GO.** Không thay public frontend PHP cho đến khi P1 resolver/cutover và các release blockers được xác minh.
- Production state: audit không deploy, không đổi DNS/Caddy production, không ghi DB, không xóa upload/cache/legacy asset.
- Rollback point quan sát được: PHP `master@5f616c4`; Nuxt candidate `aea5bc0`; mọi thay đổi dirty khác trong worktree là WIP có sẵn của các agent khác, không phải do audit này.
- Báo cáo/evidence chỉ được thêm dưới `docs/audits/pts-nuxt/`; không có commit mới.

