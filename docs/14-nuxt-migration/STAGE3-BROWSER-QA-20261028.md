# Giai đoạn 3 — Browser/visual QA (2026-10-28)

Chạy trên HEAD `d0d810e` của `feature/pts-nuxt-integration-20261027` (sau Lane
A: SEO fix + Lane B: gallery/video/brand link + focus trap). Container PHP
riêng port **8088** (bắt buộc — xem Lỗi #2) + Nuxt SSR preview riêng port
**3109**, cả hai đã dừng/xoá sau khi xong. Không đụng `pts-php81-local`
(8080), không đụng worktree Codex.

## Lỗi môi trường tìm thấy và đã tự sửa (không phải bug code, chỉ test harness)

1. **`docker-compose.*.yml` tmpfs sai đường dẫn cache.** `class.CssMinify.php`
   ghi vào `assets/caches/`, không phải `caches/` ở gốc — pattern này tái lặp
   lần thứ 3 (đã gặp ở audit SEO và Stage-2). Mount tmpfs tại
   `/var/www/html/caches` không đủ; phải thêm
   `/var/www/html/assets/caches:mode=1777`. Thiếu dòng này khiến mọi trang PHP
   chết giữa chừng (chỉ ra ~2KB thay vì full page) dù HTTP status vẫn 200 —
   **khuyến nghị sửa 1 lần trong `docker-compose.nuxt-preview.yml` gốc** để
   các lượt QA sau không lặp lại lỗi này.
2. **`frontend/server/utils/migration-api.ts:36` hardcode port `8080`/`8088`.**
   Test ban đầu dùng port 8099 (tự chọn tránh đụng ai) khiến mọi gọi PHP từ
   Nuxt (kể cả cart ở shared header) trả 502 "Untrusted PHP migration origin"
   — không phải bug, do allowlist cứng chỉ nhận 2 port đó. Phải đổi container
   PHP sang port 8088 mới test được đúng. Đây chính là gap đã giao cho Lane D
   (origin allowlist) — xác nhận lại bằng thực nghiệm, không chỉ đọc code.

## Kết quả manifest 3 viewport (390×844, 768×900, 1440×900), 39 phép đo (13 route × 3 viewport)

- **HTTP status**: 200/200 mọi route cả 2 phía — PASS.
- **Console/pageError/failedRequest (Nuxt preview)**: sạch ở mọi route trừ
  `contact` (Google Maps JS bị `net::ERR_ABORTED` — môi trường sandbox không
  có internet, không phải lỗi Nuxt; xảy ra như nhau ở cả 2 phía).
- **Horizontal overflow**: 0 ở mọi route/viewport — PASS.
- **Gate tổng (`gate.status`) đa số FAIL** nhưng nguyên nhân **100% là của PHP
  legacy, không phải do Lane A/B gây ra**:
  - `media` check fail ở **tất cả 13/13 route** (kể cả `home`, chưa từng bị
    Lane B đụng) — do ảnh cờ ngôn ngữ + logo trong header legacy dùng lazy-load
    chưa trigger khi chụp, `src=""` tại thời điểm đo. Preview phía Nuxt có
    `brokenMedia: []` ở mọi route. Đây là hành vi lazy-load sẵn có của theme
    PHP, không phải ảnh vỡ thật — **pre-existing, ngoài phạm vi Lane A/B**.
  - `responsiveStability` fail riêng ở `news-detail` (cả 3 viewport): CLS phía
    **legacy** 0.10–0.24 (> ngưỡng 0.1), phía **preview** 0.0002–0.03 (đạt).
    Root cause nằm ở PHP, Nuxt preview ổn định hơn. Không phải regression của
    Lane A/B.
  - `catalog`/`product-detail`/các trang nội dung: classification
    `mixed-data-and-ui-difference` — khác biệt dữ liệu/bố cục dự kiến giữa
    preview và PHP thật (đúng bản chất preview, không phải lỗi).

**Kết luận gate**: không có FAIL nào do Lane A (SEO fix) hay Lane B
(gallery/video/brand link + focus trap) gây ra. Toàn bộ FAIL đều quy về hành
vi sẵn có của PHP legacy (lazy-load ảnh header, CLS cao hơn ở `news-detail`).

## Review ảnh thật (không chỉ dựa pixel-diff)

- `catalog`, `product-detail`, `brand-detail` (1440×900): bố cục sạch, header/
  breadcrumb/filter/grid đúng, giá/badge công suất hiển thị đúng định dạng,
  placeholder "Chưa có hình ảnh" cho sản phẩm thiếu ảnh — không phải lỗi, là
  dữ liệu fixture thật thiếu ảnh.
- Không phát hiện vỡ layout, text đè ảnh, hay khoảng trắng bất thường ở các
  route đã xem.

## Gallery-detail / Video lightbox — focus trap (directive mục 3)

**KHÔNG verify được bằng Playwright tương tác thật** — lý do xác thực, không
phải bỏ qua: DB fixture local hiện **không có dữ liệu** cho Gallery/Video
(`GET /api/pts_v1_gallery.php` → `albums: [], total: 0`;
`GET /api/pts_v1_video.php` → `videos: [], total: 0`). Không có item nào để
mở lightbox thật. Chèn fixture giả vào DB chia sẻ (`pts-mariadb-local`) nằm
ngoài phạm vi an toàn được giao (không bịa dữ liệu, không ghi DB ngoài kiểm
soát).

Đã xác nhận thay thế:
- `migration-gallery-detail-preview?slug=<slug-giả>` trả 200 với thông báo
  "Không tìm thấy album" — không crash, không 500, graceful empty-state.
- Review code `migration-gallery-detail-preview.vue` (dòng 17–65) và
  `migration-video-preview.vue` (pattern tương tự):
  - `trapFocus`: Tab+Shift tại phần tử đầu → nhảy về cuối; Tab tại phần tử
    cuối → nhảy về đầu. Đúng pattern chuẩn, khớp `SiteHeader.vue`.
  - `focusFirst` chạy qua `watch(selectedIndex)` mỗi khi mở lightbox **và**
    mỗi khi `move()` đổi ảnh (ArrowLeft/ArrowRight) — tức là mỗi lần chuyển
    ảnh trong lightbox, focus bị kéo về phần tử focusable đầu tiên (thường là
    nút đóng/nav), không giữ ở vị trí người dùng vừa tương tác. **Minor UX
    nit**, không phải lỗi focus-trap (không thoát ra ngoài dialog), nhưng có
    thể gây khó chịu nhẹ cho người dùng bàn phím/screen reader khi duyệt
    nhiều ảnh liên tiếp. Chưa sửa — không chắc có phải hành vi cố ý hay không,
    để lại NEEDS FIX cho lượt sau xác nhận với UX.
  - `close()` trả focus đúng về `lastTrigger` (phần tử kích hoạt lightbox ban
    đầu) — đúng yêu cầu.

**NEEDS VERIFICATION thật sự**: hành vi Tab-cycle/focus bằng mắt trên dữ liệu
thật — cần fixture Gallery/Video CMS trước khi QA lượt sau.

## Container/port

- PHP: `pts-php81-stage3-qa`, port 8088 (đổi từ 8099 ban đầu do allowlist
  cứng) — đã `docker compose down`.
- Nuxt: port 3109 — đã kill process.
- Không đụng `pts-php81-local` (8080) hay bất kỳ container/worktree nào khác.

## Việc ngoài phạm vi, phát hiện thêm (không sửa)

- `docs/14-nuxt-migration/CODEX-HANDOFF-LANE-D-20261028.md` trong worktree
  `/home/admin01/src/pts-nuxt-lane-d-20261028` đã đổi trên đĩa trong lúc tôi
  chạy QA này (không phải tôi sửa) — có vẻ Lane D đã có người bắt đầu, ngoài
  phạm vi task này, không kiểm tra thêm.
