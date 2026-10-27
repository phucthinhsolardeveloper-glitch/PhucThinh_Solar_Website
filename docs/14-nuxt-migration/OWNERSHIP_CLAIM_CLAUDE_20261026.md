# Ranh giới quyền sở hữu — Claude, 2026-10-26

Mục đích: hai agent chạy song song trên cùng repo mà không ghi đè nhau.
Quy tắc nền: mỗi file có ĐÚNG MỘT chủ tại một thời điểm.

> **Cập nhật 2026-10-27:** không còn agent Codex nào chạy song song trên các
> worktree này; mọi worktree Codex đã được gộp vào
> `feature/pts-nuxt-integration-20261027` và Claude đang giữ toàn bộ (frontend
> + backend PHP DTO) một mình. Phần "Việc TRỐNG" dưới đây là hồ sơ lịch sử tại
> thời điểm bàn giao — một số dòng (bug SEO, giá PDP) đã được đánh dấu ĐÃ SỬA
> trực tiếp tại chỗ; các dòng còn lại (brand.logo, huy hiệu BCT, bộ lọc/sắp
> xếp, hai số hotline, nội dung block sản phẩm...) **chưa chắc còn đúng** —
> đọc code hiện tại trước khi tin theo bảng, đừng suy đoán từ bảng này.

## Claude đang giữ

- **Worktree** `/home/admin01/src/pts-nuxt-ui-foundation`
- **Nhánh** `feature/pts-nuxt-ui-foundation-20261026` (base `fcba5a5`)

Agent khác KHÔNG checkout, không merge, không rebase, không `git worktree remove`
worktree/nhánh này.

### File Claude giữ — agent khác không sửa

Nền tảng (đụng vào là vỡ toàn bộ theme của mọi trang):

```
frontend/package.json
frontend/package-lock.json
frontend/nuxt.config.ts
frontend/app/app.vue
frontend/app/app.config.ts
frontend/app/assets/css/main.css
frontend/public/fonts/**
```

Shell dùng chung (mọi trang đều render, sửa một chỗ ảnh hưởng tất cả):

```
frontend/app/components/SiteHeader.vue
frontend/app/components/SiteFooter.vue
frontend/app/components/SiteBreadcrumb.vue
frontend/app/assets/css/shell.css
frontend/app/assets/css/tokens.css
```

Nhánh thương mại (đang chuyển dở):

```
frontend/app/components/catalog/**
frontend/app/components/product-preview/**
frontend/app/assets/css/product-detail-preview.css
frontend/app/pages/migration-catalog-preview.vue
frontend/app/pages/migration-product-preview.vue
frontend/app/pages/migration-ui-lab-preview.vue
```

## Đã xong trên nhánh này

| Commit | Nội dung |
|---|---|
| `015337b` | Nền tảng Tailwind v4 + Nuxt UI v4, theme thương hiệu, font self-host |
| `a4ea0da` | SiteHeader sang Tailwind |
| `3d140b3` | SiteFooter sang Tailwind |
| `8545a52` | Trang danh mục sang Tailwind, xoá `catalog-preview.css` |

## Việc TRỐNG — agent khác nhận được ngay

### Ưu tiên 1: mở rộng PHP DTO (đây mới là thứ đang chặn)

Giá trị cao nhất, và hoàn toàn tách khỏi phạm vi Claude vì **chỉ đụng PHP**.
Chuyển CSS xong cũng không làm trang giống ảnh production được, vì dữ liệu
không có trong DTO. Các khoảng trống đã xác minh:

| Thiếu | DTO | Ảnh production có |
|---|---|---|
| `brand.logo` | `public-site.ts` đã khai trường, PHP chưa trả | wordmark vàng trên header |
| `brand.logoFooter` | nt | emblem dọc ở footer |
| Đường dây thứ hai | chỉ có một `brand.hotline` | "Tư vấn bán hàng" + "Hỗ trợ kỹ thuật", hai số khác nhau |
| `footer.policies` / `locations` | chưa trả | 7 link chính sách, 4 điểm kho |
| Huy hiệu Bộ Công Thương | chưa có trường | ảnh rộng 130px dưới cột chính sách |
| Số lượng giỏ hàng | chưa có | badge gold trên icon giỏ |
| Giá sản phẩm | `parity.prices.included = false` | 2.648.100đ |
| Công suất | không có trong `PublicCatalogProduct` | 650W |
| Bộ lọc / sắp xếp | `parity.filters/sort.included = false` | hộp BỘ LỌC + ô sắp xếp |

File cần sửa (KHÔNG trùng Claude một dòng nào):

```
domains/phucthinhsolar.com/public_html/api/pts_v1_*.php
domains/phucthinhsolar.com/public_html/libraries/pts_public_*_api.php
scripts/test-pts-public-*-api.php
frontend/shared/contracts/public-catalog.ts   <- CHỈ khi mở rộng contract
frontend/shared/contracts/public-site.ts      <- CHỈ khi mở rộng contract
```

Lưu ý: hai file `contracts/*.ts` là vùng giáp ranh. Nếu agent khác cần mở rộng
chúng thì báo trước; Claude chỉ ĐỌC, không sửa.

Ràng buộc: read-only với DB, giữ đúng filter published/hidden của PHP, có
contract test, không mở route public mới.

#### BUG SEO đã xác minh — ĐÃ SỬA 2026-10-27 (không còn Codex chạy, Claude tự xử lý luôn backend)

Giữ nguyên mô tả gốc dưới đây làm hồ sơ; bug KHÔNG còn tồn tại trên nhánh này.
Đã sửa đồng thời ở `ptsCatalogProductUrl()`, `ptsPdpCanonicalUrl()` +
construction `canonical.url` trong `ptsPdpPayload()`, và
`ptsProjectDetailProductDto()` (route thiết bị liên quan ở trang dự án dùng
chung bug) — cả 3 nơi phát sinh URL sản phẩm, không chỉ PDP tự trỏ về mình.
Cập nhật kèm theo ở `public-catalog.ts`, `public-product-detail.ts`,
`public-project-detail.ts` và toàn bộ fixture test PHP/TS liên quan (một số
fixture PHP hoá ra đã lỗi thời từ lần tích hợp sanitizer trước — `rendered`/
`paragraphs` không còn tồn tại trong payload thật — phát hiện và sửa luôn khi
đọc lại các file này). Đồng thời đã thêm `pricing` thật cho PDP (cùng gate
`publicProductPricesVisible()`/`baogia` như catalog), qua
`ptsPdpPricingDto()`; `product.content`/`parity.blocks` KHÔNG dùng sanitizer
vì nội dung dài của sản phẩm là hệ block JSON riêng
(`table_content_section`, `product_content_blocks.php`), không phải một cột
CKEditor đơn như news/project/solution — lý do đổi tên thành
`product_content_blocks_not_wired`, cần DTO riêng, chưa làm ở đây.

#### Hồ sơ gốc của bug (trước khi sửa)

`libraries/pts_public_product_detail_api.php:84` ép thêm dấu `/` vào cuối
canonical:

```php
'canonical' => array('url' => $canonical !== '' ? '/'.trim($canonical, '/').'/' : ptsPdpCanonicalUrl($slug), ...)
```

và dòng 32 cũng vậy: `return '/san-pham/'.$slug.'/';`

Nhưng site PHP đang chạy phát canonical KHÔNG có dấu `/` cuối.
`libraries/product_category_urls.php:40`:

```php
return !empty($row[$slug]) ? 'san-pham/'.$row[$slug] : '';
```

Router nhận CẢ HAI dạng (`[a:com]` và `[a:sub]/[a:com]/`), nên khi route PDP
Nuxt lên hybrid sẽ có hai URL cùng nội dung và canonical của Nuxt trỏ vào dạng
mà Google chưa index. Đây đúng loại lỗi mà Caddyfile đã phải vá một lần cho
www/non-www sau cutover 2026-09-17 — ghi chú trong file đó mô tả hậu quả là
"toàn site có hai URL trùng nội dung, chia đôi giá trị liên kết".

`frontend/shared/contracts/public-product-detail.ts` hiện cũng ÉP dạng có dấu
`/` (`product.url !== '/san-pham/' + slug + '/'` thì reject), nên sửa PHP không
thôi sẽ làm contract test fail. Phải sửa cả hai cùng lúc — đây là lý do mục này
cần một chủ duy nhất, và chủ đó nên là người đang giữ PHP DTO.

~~Claude KHÔNG sửa mục này vì đã nhường PHP; chỉ phát hiện và bàn giao.~~ —
hết hiệu lực 2026-10-27, xem phần ĐÃ SỬA ở trên.

### Ưu tiên 2: nhánh nội dung (nếu muốn làm frontend)

Hoàn toàn tách khỏi phạm vi Claude:

```
frontend/app/components/{news,news-detail,projects,project-detail,solutions,solution-detail}/**
frontend/app/components/{about,contact,dealer,home}/**
frontend/app/assets/css/{news,news-detail,projects,project-detail,solutions,solution-detail}-preview.css
frontend/app/assets/css/{about,contact,dealer,home,migration-hub}-preview.css
frontend/app/pages/migration-{news,news-detail,projects,project-detail,solutions,solution-detail}-preview.vue
frontend/app/pages/migration-{about,contact,dealer,home,hub,site,shell}-preview.vue
frontend/app/components/layout/MigrationShell.vue
frontend/app/assets/css/layout-migration.css
```

Phải tạo worktree con RIÊNG từ SHA mới nhất, không làm trong worktree của Claude.

**Chặn cứng:** không thêm `v-html` mới cho nội dung CKEditor cho tới khi
sanitizer `b856784` được review và merge.

## Hai cái bẫy đã kiểm chứng — đọc trước khi đo bất cứ thứ gì

1. **`typography-system.css` nạp SAU và ghi đè bằng `!important`.**
   `header-v3.css` khai nav 13,2px nhưng cỡ chữ thật ở ≥1501px là **17,5px**.
   Đọc một file CSS là ra số sai. Luôn grep thêm `typography-system.css`,
   `design-tokens.css`, `flat-surface-system.css`, `responsive-architecture.css`.
   Thứ tự nạp ở `templates/layout/css.php`.

2. **Header đang chạy KHÔNG dùng token thương hiệu.**
   Gold thật `#c69a45`, chữ `#10243a` — không phải `--pts-gold #c9962e` /
   `--pts-navy #0a2251`. Đo pixel trên ảnh production xác nhận. Đã đưa vào
   `main.css` dưới tiền tố `hdr-*` kèm ghi chú xung đột. Chưa có quyết định gộp.

## Công cụ đo

Ảnh production 2026-10-26 ở `/mnt/c/Users/ADMIN/Downloads/screencapture-phucthinhsolar-*.png`,
rộng 1920px, **DPR = 1** nên 1px ảnh = 1px CSS.

Script tại `/tmp/claude-1000/-home-admin01-src-Web-PTS-PHP/d7cbd939-f70b-487b-b32e-d8db2196a893/scratchpad/imgtools/`:

| Script | Dùng để |
|---|---|
| `box.mjs <ảnh> <ra.png> <l> <t> <w> <h> [scale]` | cắt và phóng to một vùng |
| `bbox.mjs <ảnh> <l> <t> <w> <h>` | đo bounding box phần có mực |
| `gold.mjs <ảnh> <top> <height>` | dò khối màu gold → ra vị trí/bề rộng nút và cột lưới |
| `dom.mjs <ảnh> <l> <t> <w> <h>` | lấy màu chữ thật trên nền sẫm |
| `probe.mjs <ảnh> x,y ...` | đọc màu tại toạ độ |

## Quy tắc chung cho cả hai bên

- Một build hoặc một phiên Chromium tại một thời điểm (WSL ~7,8 GiB).
- Không push, không deploy, không đụng `master`, không sửa production.
- Không bịa dữ liệu. DTO thiếu trường thì ghi `CÒN THIẾU:` tại chỗ, không hardcode.
- Giao/nhận bằng SHA commit, không bằng mô tả miệng.
