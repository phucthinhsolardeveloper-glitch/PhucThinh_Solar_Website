# F03 — PDP PHP → Nuxt preview (checkpoint 2026-10-27)

## Đã triển khai

- API GET-only mới: `/api/pts_v1_product_detail.php?slug=...`.
- Helper DTO mới chỉ chọn bản ghi `san-pham` đã `hienthi`, whitelist field thông số, lọc slug/media và kiểm tra file ảnh thật trong upload product.
- Canonical lấy từ `publicProductEntityPath()`; payload ghi source map của resolver/category URL.
- Nuxt adapter cố định `/_migration/product-detail?slug=...`, reject method khác và slug không đúng regex.
- Preview noindex tại `/migration-product-preview?slug=...`, dùng SiteHeader/SiteFooter API hiện có, không takeover `/san-pham/{slug}`.
- PDP preview đã có gallery lightbox/zoom, pricing/quote và cart, specs/content/FAQ/documents, video, warranty/policies, related products, anchor navigation, reviews list + moderated submit và consultation form.
- Cart, review, consultation và form mutations đi qua PHP adapter được bảo vệ; PHP vẫn là authority của session, CSRF, rate-limit, reCAPTCHA khi cấu hình, DB/email và nghiệp vụ.
- Preview có Product/Breadcrumb/FAQ JSON-LD được dựng từ dữ liệu đã validate; đây không phải public SEO cutover.
- Gallery dùng ảnh CMS thật và `object-fit: contain`; thiếu ảnh/spec hiển thị trạng thái an toàn, không tạo dữ liệu giả.

## Dữ liệu chưa delegate / gap đã xác minh

| Hạng mục | Trạng thái | Lý do |
|---|---|---|
| Giá regular/sale, giảm giá | **Đã xuất 2026-10-27** | `ptsPdpPricingDto()` gọi cùng gate `publicProductPricesVisible()`/status `baogia` như `pts_public_catalog_api.php::ptsCatalogPricingDto()`; nhân đó cũng sửa luôn bug canonical PDP ép dư dấu `/` cuối (`ptsPdpCanonicalUrl`, không khớp `publicProductEntityPath()`/router thật) |
| Quote / mua ngay / giỏ hàng | Preview GUI đã nối PHP | Nuxt chỉ render và gọi adapter; PHP giữ session, calculation, mutation CSRF và checkout/order authority |
| Content blocks + FAQ | **Đã xuất 2026-10-27** | API gọi đúng `productResolveSections()` nên giữ `own/both/hide/inherit` + nguồn kế thừa product/brand/cat/list. DTO chỉ cho `heading/text/list/image/media`; FAQ tách bằng chính `productSplitFaqBlocks()`. Text render bằng Vue interpolation, không `v-html`; ảnh chỉ nhận `/upload/product/` an toàn. `cards` bị loại vì PDP legacy không render block này (chỉ category landing dùng). |
| Tài liệu sản phẩm | **Đã xuất 2026-10-27** | API chỉ lấy record `hienthi`, loại tài liệu allowlist (`datasheet/manual/warranty/certificate/other`), chỉ chấp nhận HTTPS hoặc file public `upload/assets` với đuôi PDF/DOC/DOCX/XLS/XLSX. Nuxt dùng proxy local riêng `/_migration/product-document` với path/MIME/size gate; URL HTTPS đi trực tiếp. |
| Reviews/comments | Preview đã có list + moderated submit | Mutation đi qua PHP adapter với CSRF/rate-limit; public route/canonical chưa cutover |
| Related products | Đã xuất trong preview | Dữ liệu/merchandising vẫn do PHP cung cấp; parity public cuối chưa ký |
| JSON-LD | Đã xuất trong preview | Có Product/Breadcrumb/FAQ từ dữ liệu validated; PHP vẫn là public metadata authority |
| Use-case/marketing blocks tự sinh | Chưa xuất | Template PHP còn có `productAutoHighlights()`, use-case defaults và fallback FAQ theo profile; không hardcode/tái tạo speculative content trong DTO block. |

## Kiểm thử và giới hạn

- `scripts/test-pts-public-product-detail-api.php`: test DTO, media traversal, private-field omission, content block/FAQ mapping, loại `cards`, 405 và invalid slug. PHP CLI/Docker hiện chưa khả dụng trên máy nên fixture này cần chạy lại khi runtime PHP mở.
- `frontend/tests/product-detail-contract.test.mjs`: test schema, slug, media, pricing invariant, structured blocks, source inheritance, FAQ và tài liệu public.
- `frontend/tests/product-document-proxy.test.mjs`: test whitelist path/extension/MIME, traversal, redirect, kích thước tối đa và preview flag của proxy tài liệu local.
- Chưa chạy PHP với DB thật trong sandbox; coordinator cần chạy endpoint against PHP fixture/container để xác minh 200/404 và canonical/media thực tế.
- Validation checkpoint hiện tại: nền `3b9cade`, range `3b9cade..57c786f`, kèm `35d0c5f` cho navigation; local hub `http://localhost:3100/migration-hub-preview`.
- Local validation của milestone: 11 PHP file/library mới qua `php -l`, `npm test` 98/98 PASS, typecheck exit 0 (warning vue-router/Volar không fatal), production build PASS, smoke PASS và 13/13 route preview quan trọng HTTP 200.
- Preview vẫn noindex và chỉ là local preview; chưa có production public route cutover. Các gate còn lại gồm SEO canonical/OG/sitemap/robots/redirect, catalog parity ngoài PDP, final visual/browser QA và deployment review.

Rollback: xóa/revert các file mới của F03; không có production route, migration, DB write hay deploy nào đã thực hiện.
