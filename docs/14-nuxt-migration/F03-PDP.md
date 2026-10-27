# F03 — PDP PHP → Nuxt preview

## Đã triển khai

- API GET-only mới: `/api/pts_v1_product_detail.php?slug=...`.
- Helper DTO mới chỉ chọn bản ghi `san-pham` đã `hienthi`, whitelist field thông số, lọc slug/media và kiểm tra file ảnh thật trong upload product.
- Canonical lấy từ `publicProductEntityPath()`; payload ghi source map của resolver/category URL.
- Nuxt adapter cố định `/_migration/product-detail?slug=...`, reject method khác và slug không đúng regex.
- Preview noindex tại `/migration-product-preview?slug=...`, dùng SiteHeader/SiteFooter API hiện có, không takeover `/san-pham/{slug}`.
- Gallery dùng ảnh CMS thật và `object-fit: contain`; thiếu ảnh/spec hiển thị trạng thái an toàn, không tạo dữ liệu giả.

## Dữ liệu chưa delegate / gap đã xác minh

| Hạng mục | Trạng thái | Lý do |
|---|---|---|
| Giá regular/sale, giảm giá | **Đã xuất 2026-10-27** | `ptsPdpPricingDto()` gọi cùng gate `publicProductPricesVisible()`/status `baogia` như `pts_public_catalog_api.php::ptsCatalogPricingDto()`; nhân đó cũng sửa luôn bug canonical PDP ép dư dấu `/` cuối (`ptsPdpCanonicalUrl`, không khớp `publicProductEntityPath()`/router thật) |
| Quote / mua ngay / giỏ hàng | Giữ PHP | Preview không tạo session, cart, POST hay checkout |
| Content blocks + FAQ | **Đã xuất 2026-10-27** | API gọi đúng `productResolveSections()` nên giữ `own/both/hide/inherit` + nguồn kế thừa product/brand/cat/list. DTO chỉ cho `heading/text/list/image/media`; FAQ tách bằng chính `productSplitFaqBlocks()`. Text render bằng Vue interpolation, không `v-html`; ảnh chỉ nhận `/upload/product/` an toàn. `cards` bị loại vì PDP legacy không render block này (chỉ category landing dùng). |
| Tài liệu sản phẩm | Chưa xuất | Cần gate path/file public riêng, tránh leak đường dẫn/private asset |
| Reviews/comments | Giữ PHP | Mutation/session legacy chưa được headless hóa |
| Related products | Chưa xuất | Thứ tự merchandising legacy chưa được kiểm chứng parity |
| JSON-LD | Chưa xuất | PHP structured-data vẫn là authority; chưa delegate đầy đủ |
| Use-case/marketing blocks tự sinh | Chưa xuất | Template PHP còn có `productAutoHighlights()`, use-case defaults và fallback FAQ theo profile; không hardcode/tái tạo speculative content trong DTO block. |

## Kiểm thử và giới hạn

- `scripts/test-pts-public-product-detail-api.php`: test DTO, media traversal, private-field omission, content block/FAQ mapping, loại `cards`, 405 và invalid slug. PHP CLI/Docker hiện chưa khả dụng trên máy nên fixture này cần chạy lại khi runtime PHP mở.
- `frontend/tests/product-detail-contract.test.mjs`: test schema, slug, media, pricing invariant, structured blocks, source inheritance và giới hạn FAQ.
- Chưa chạy PHP với DB thật trong sandbox; coordinator cần chạy endpoint against PHP fixture/container để xác minh 200/404 và canonical/media thực tế.
- Frontend full gate sau khi nối block: `npm test` 78/78 PASS, `nuxt typecheck` PASS (còn warning Volar/vue-router đã có từ trước), production `nuxt build` PASS. Chưa có browser parity với dữ liệu PDP thật vì PHP local/Docker đang không chạy.

Rollback: xóa/revert các file mới của F03; không có production route, migration, DB write hay deploy nào đã thực hiện.
