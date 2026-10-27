# P2-A — SEO/canonical/OG/sitemap/robots/redirect parity audit (2026-10-28)

Thực thi theo `SEO-CRAWL-GATES.md` / `NEXT-EXECUTION-PLAN.md` (task P2-A). Read-only
GET crawl, loopback-only, không login/mutation, không update golden image, không
sửa public route ownership.

## Môi trường dùng cho lần audit này

- PHP legacy/public owner: `http://127.0.0.1:8080` (container `pts-php81-local`
  đã chạy sẵn trước khi audit, **không restart/rebuild**).
- PHP migration code + Nuxt upstream riêng cho audit này: container
  `pts-php-nuxt-preview` (port `127.0.0.1:8088`), dựng từ
  `docker-compose.nuxt-preview.yml` trong worktree này, dùng chung DB/upload
  local qua network `web_pts_php_default`. Container cũ cùng tên đã `Exited`
  từ phiên trước được `docker rm` trước khi tạo lại (không có process nào
  đang giữ).
- Nuxt SSR preview: `http://127.0.0.1:3100` (build local trong worktree này,
  `scripts/nuxt-local-preview.sh start`).
- Đã `scripts/nuxt-local-preview.sh stop` sau khi chạy xong; không dừng
  container nào khác (`pts-php81-local`, `pts-mariadb-local`, zalo-crm-*
  không bị đụng).

## Lệnh đã chạy

```bash
node tools/visual-tests/scripts/migration-seo-audit.cjs \
  http://127.0.0.1:8080 http://127.0.0.1:3100 \
  --manifest tools/visual-tests/fixtures/migration-routes.json \
  --output frontend/.migration-artifacts/seo-audit
```

Output: `frontend/.migration-artifacts/seo-audit/report.json` (558 KB) và
`summary.md` — chưa commit (artifact dir untracked theo thiết kế), chỉ file
audit này được commit làm evidence tóm tắt.

## Kết quả theo gate (`SEO-CRAWL-GATES.md` → Gate decision)

### Gate 1 — Canonical public route / alias 301..308 / 404-410 cho missing-hidden-retired

- **FAIL (evidence mới, không phải do Nuxt gây ra):** `legacy-product-root-alias`
  (`/product/`) trả **404** trên PHP legacy, không redirect 301 về `/san-pham/`
  như `SEO-CRAWL-GATES.md` mô tả ("`product/` và `product/{slug}` là alias
  301") và như manifest kỳ vọng
  (`expectedRedirectStatus: 301`, `expectedCanonicalPath: /san-pham/`).
  Chỉ `/product/{slug}` (có slug) hoạt động như alias; `/product/` (root,
  không slug) không có route match → PHP 404 thật, không phải soft-200.
  Evidence: `report.json` → `routes[].legacy` cho `id=legacy-product-root-alias`,
  `status:404`, `redirectChain: []`.
- Các alias khác đã kiểm tra (`legacy-about-alias`, `legacy-dealer-alias`,
  `legacy-product-detail-alias`): PHP trả đúng 1 redirect hop, đích 200, không
  loop. PASS.
- Không có slug ẩn/retired riêng trong manifest hiện tại để xác nhận 404/410 âm
  tính ngoài case trên → NEEDS VERIFICATION (manifest cần thêm fixture
  negative case rõ ràng cho hidden/retired nếu muốn ký gate này đầy đủ).

### Gate 2 — Metadata owner (title/H1/canonical/robots) + preview noindex

- PASS cho toàn bộ 26 route trong manifest: PHP có `title`, `canonical` khớp
  path, `robots` không chặn; Nuxt preview đều có `robots: noindex, nofollow,
  noarchive` (`ownership.preview.status = preview-noindex`), không coi là
  public.

### Gate 3 — robots.txt / sitemap.xml

- PHP `robots.txt` = 200, `Disallow: /cgi-bin/, /administrator/`,
  `Sitemap: https://phucthinhsolar.com/sitemap.xml` — không leak staging path.
- PHP `sitemap.xml` = 200, `urlset` hợp lệ, `<loc>` đều là path canonical dạng
  `danh-muc-san-pham/...` (chưa sample toàn bộ URL để crawl GET xác nhận status
  — NEEDS VERIFICATION cho việc "mọi `<loc>` reachable", audit này chỉ xác
  nhận format sitemap hợp lệ, chưa crawl follow-up toàn bộ `<loc>`).
- Nuxt preview `robots.txt`/`sitemap.xml` = **404 cả hai** — đúng theo thiết
  kế (Nuxt preview không phải public owner, không được có sitemap riêng).
  PASS theo đúng nghĩa "không leak" nhưng cần ghi rõ: đây là 404 vì route
  preview không định nghĩa, không phải vì bị chặn.

### Gate 4 — JSON-LD

- **FAIL/gap xác nhận lại:** PHP legacy **không có JSON-LD ở route nào**
  trong manifest (`types: []` toàn bộ, kể cả `product-detail`). Nuxt preview
  chỉ có JSON-LD (`Answer, BreadcrumbList, FAQPage, ListItem, Offer, Product,
  Question`) ở `product-detail` và `legacy-product-detail-alias`; mọi route
  khác (home, catalog, solutions, projects, news, gallery, video...) không có
  JSON-LD ở cả 2 phía. Khớp với ghi nhận trước đó trong `NEXT-EXECUTION-PLAN.md`
  ("JSON-LD hiện mới có trên PDP preview") — audit này xác nhận bằng dữ liệu
  thật, không phải suy đoán. Đây là gap cần làm trước khi ký gate 4, không
  chỉ cho cutover mà cả cho PHP hiện tại.

### Gate 5 — Href mapping

- Chưa phân loại onlyLegacy/onlyPreview có hệ thống trong lượt audit này (cần
  thêm bước đối chiếu `hrefMapping` theo từng route trong `report.json`, dữ
  liệu đã có trong field `page.hrefs` từng route nhưng chưa tổng hợp bảng).
  NEEDS VERIFICATION — để lại cho lượt audit kế tiếp hoặc owner P2-A review
  thủ công `report.json`.

### OG basic — phát hiện thêm (không nằm trong 5 gate numbered nhưng thuộc
"OG basic" trong bảng QA)

- **FAIL (PHP legacy, không phải do Nuxt):** nhiều route PHP thiếu field OG
  bắt buộc:
  - `search`, `cart`: thiếu `og:description` và `og:url` (rỗng).
  - `brand-root`, `solutions`, `gallery`, và tương tự
    `brand-detail`/`projects`/`news`/`about`/`dealer`/`contact`/`video`/
    `policy-detail`/`checkout-preview`/`order-success`/`legacy-about-alias`/
    `legacy-dealer-alias`: thiếu `og:image` (rỗng).
  - `catalog-category`: OG thiếu (không kiểm tra chi tiết field nào trong lượt
    này, xem `report.json` → `routes[id=catalog-category].legacy.page.og`).
  - Route đạt đủ OG: `home`, `catalog`, `product-detail`, `project-detail`,
    `news-detail`, `legacy-product-detail-alias`.
  - Evidence: `report.json`, field `routes[].legacy.page.og` từng route.

## Tổng kết PASS / FAIL / NEEDS VERIFICATION

| Gate | Kết quả |
|---|---|
| Alias 301-308 (trừ `/product/` root) | PASS |
| `/product/` root alias | **FAIL** — PHP 404, không redirect như doc/manifest kỳ vọng |
| 404/410 cho hidden/retired khác | NEEDS VERIFICATION — thiếu fixture negative trong manifest |
| Canonical ownership + preview noindex | PASS (26/26 route) |
| robots.txt / sitemap.xml (không leak) | PASS |
| Sitemap `<loc>` reachable (crawl toàn bộ) | NEEDS VERIFICATION — chưa crawl hết |
| JSON-LD parity | **FAIL** — PHP không có JSON-LD ở route nào; Nuxt chỉ có ở PDP |
| Href mapping onlyLegacy/onlyPreview | NEEDS VERIFICATION — chưa tổng hợp |
| OG basic | **FAIL một phần** — nhiều route PHP thiếu og:image/description/url (danh sách ở trên) |

**Kết luận:** Chưa đủ điều kiện ký pre-cutover gate SEO (gate 1, 4 và OG basic
đang FAIL ở phía PHP legacy — đây là gap tồn tại từ trước, không phải do Nuxt
preview gây ra). Không dùng audit này để claim parity; cần: (1) owner PHP xác
nhận `/product/` root có nên alias hay bỏ khỏi gate, (2) bổ sung JSON-LD cho
PHP public routes hoặc chốt kế hoạch chỉ bổ sung ở Nuxt sau cutover, (3) bổ
sung og:image/description/url còn thiếu, (4) chạy lại audit với manifest có
thêm negative fixture và crawl sitemap đầy đủ.
