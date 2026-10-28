# Catalog API contract — Phase A

Endpoint: `GET /api/pts_v1_catalog.php`

Query chỉ gồm `category` (slug phân cấp tối đa 4 đoạn), `page` (mặc định 1) và
`per_page` (mặc định 20, tối đa 48). Query khác, slug không hợp lệ, page lớn
hơn 10000 hoặc `per_page` lớn hơn 48 trả `400 BAD_QUERY`; method khác GET trả
`405 METHOD_NOT_ALLOWED`. Lỗi runtime không trả raw database error.

Response có `schemaVersion: 1`, `locale: "vi"`, `category`, `categories`,
`products`, `pagination` và `parity`. Category dùng `id`, `name`, `slug`, `url`;
product dùng `id`, `name`, `slug`, `url`, `media`. URL chuẩn lần lượt là
`/danh-muc-san-pham/{slug...}/` và `/san-pham/{slug}/`. Chỉ bản ghi `san-pham`
có status `hienthi`, tên/slug hợp lệ mới được xuất. Media chỉ nhận filename ảnh
đã tồn tại trong upload product; field private, HTML và giá không được xuất.

`pagination` gồm `page`, `perPage`, `total`. `parity.prices`, `parity.filters`
và `parity.sort` đều ghi rõ chưa được delegate. Giá không được suy diễn; mọi
quyết định giá vẫn thuộc `libraries/public_pricing.php`. API không tạo checkout,
cookie hoặc session business state và không thay đổi endpoint legacy.

## Parity gaps / TODO

- Chưa expose giá vì chưa có gate kiểm chứng đầy đủ với `public_pricing.php`.
- Chưa expose filter nhu cầu/công suất/giá, brand, search hoặc sort vì chưa có
  chứng cứ parity an toàn cho Nuxt.
- Thứ tự Phase A là `id desc` ổn định, không tuyên bố parity với
  `product_sort.php`/thứ tự merchandising hiện tại.
- Category response hiện công bố category cấp list ở `categories`; category
  được chọn được resolve chính xác theo path 1–4 cấp.
- Chưa tạo Nuxt page, chưa đổi router, template, pricing, cart hay deployment.
