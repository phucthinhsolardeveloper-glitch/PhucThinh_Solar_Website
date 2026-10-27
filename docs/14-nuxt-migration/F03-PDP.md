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
| CKEditor/content blocks | Deferred — **khác cơ chế so với news/project/solution** | Nội dung dài của sản phẩm không nằm ở một cột CKEditor đơn mà là hệ block JSON riêng (`table_content_section`, đọc/ghi qua `product_content_blocks.php`, scope=`product`) — không thể tái dùng `pts_public_richtext.php` trực tiếp như 3 loại nội dung kia (đã xong sanitizer 2026-10-27), cần thiết kế DTO theo từng loại block trước. Reason string đổi thành `product_content_blocks_not_wired` để không bị hiểu nhầm là "chỉ còn thiếu sanitizer". |
| Tài liệu sản phẩm | Chưa xuất | Cần gate path/file public riêng, tránh leak đường dẫn/private asset |
| Reviews/comments | Giữ PHP | Mutation/session legacy chưa được headless hóa |
| Related products | Chưa xuất | Thứ tự merchandising legacy chưa được kiểm chứng parity |
| JSON-LD | Chưa xuất | PHP structured-data vẫn là authority; chưa delegate đầy đủ |
| Use-case/marketing blocks | Chưa xuất | Không dùng fallback marketing/speculative content trong DTO |

## Kiểm thử và giới hạn

- `scripts/test-pts-public-product-detail-api.php`: test DTO, media traversal, private-field omission, 405 và invalid slug.
- `frontend/tests/product-detail-contract.test.mjs`: test schema, slug, media và pricing invariant.
- Chưa chạy PHP với DB thật trong sandbox; coordinator cần chạy endpoint against PHP fixture/container để xác minh 200/404 và canonical/media thực tế.
- Chưa chạy browser/heavy build theo yêu cầu task; responsive CSS chỉ được static review.

Rollback: xóa/revert các file mới của F03; không có production route, migration, DB write hay deploy nào đã thực hiện.
