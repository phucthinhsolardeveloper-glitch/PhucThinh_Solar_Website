# SEO + Merchant sau release 4e309a9a (19/11/2026)

Nhánh `pts-seo-commerce-fix-20261119`. Nguồn số liệu: HTML/sitemap/feed public production (đọc), code master
04ae815e. Không có dữ liệu Search Console/GA4 đã xác thực; không kết luận về index/thứ hạng/CTR.

## Đã sửa trong code

| Hạng mục | Thay đổi | File |
|---|---|---|
| Product JSON-LD | Offer giữ giá; `availability` chỉ có khi Admin đã xác minh `merchant_availability=in_stock`; GTIN/MPN hợp lệ vào Product | `frontend/shared/product-structured-data.ts`, `frontend/app/composables/useProductDetailData.ts`, `backend/app/Domain/Catalog/ProductDetail/ProductDetailAssembler.php` |
| Trang chính sách | `NewsArticle` -> `WebPage`; chưa phát `MerchantReturnPolicy` khi điều khoản chưa đối chiếu | `frontend/app/components/views/PolicyView.vue` |
| Merchant readiness | Liệt kê mọi lý do chặn từng sản phẩm, đọc DB không ghi: `php artisan pts:merchant-readiness --only-blocked --csv=/tmp/merchant.csv`; Admin › Nhập/xuất sản phẩm › "Báo cáo dữ liệu Merchant" | `backend/app/Domain/Catalog/MerchantFeed.php`, `MerchantReadinessReport.php`, `MerchantReadinessCommand.php`, `ProductBulkPage.php` |
| Alt ảnh nội dung sản phẩm | Ảnh mô tả không có chú thích dùng tên sản phẩm làm alt (ảnh trang trí/biểu tượng giữ `alt=""`) | `frontend/app/components/product-detail/ProductDetailContent.vue` |

Feed public giữ nguyên khi release gate OFF: 13 cột, `in_stock`, `identifier_exists=no` (test parity XML/CSV).
Trạng thái tồn kho hợp lệ duy nhất là `in_stock` (Admin + workbook chỉ nhận giá trị này).

## Lý do 15 URL sitemap không có trong feed (đọc API public)

- 13 sản phẩm không có mã (`code` trống), trong đó 4 đang báo giá (3 cáp Thipha, kẹp biên 35) -> cần mã thật và giá công khai.
- JD-18300: có mã, đang báo giá.
- SolaX T-BAT-SYS-LV D150 Pro (ID 847): đã xác minh ảnh Shopping `t-bat-sys-lvd150pro-3-6450-shopping-trang.jpg`
  chỉ 454x454 (< 500 px), feed ưu tiên ảnh Shopping nên loại; ảnh chính 1600x1600. Cần ảnh Shopping sạch >= 500 px
  (hoặc Admin chọn ảnh chính sau khi kiểm tra không có chữ/logo). Code không tự thay ảnh.

Không có lỗi code loại sai; không tự cấp mã/giá. Danh sách từng SKU: `merchant-data-blocked-2026-11-19.csv`.

## DATA BLOCKED (cần dữ liệu thật từ hãng/kho)

- 8 sản phẩm thiếu thương hiệu (JD-550L: tên ghi Sharp nhưng mã JD, không suy hãng từ tên).
- 315/315 chưa có GTIN/MPN hoặc xác nhận "không có mã"; 315/315 chưa xác minh giao ngay. Nhập qua tab Google
  Merchant của sản phẩm hoặc workbook (giữ số 0 đầu GTIN). Khi đủ ngưỡng (286) mới bật
  `MERCHANT_FEED_VERIFIED_ENABLED=1`.

## Không làm / chờ quyền

- Title dài (11 trang) và 3 mô tả giải pháp >165 ký tự: không phải lỗi Google; sửa nội dung cần người biên tập duyệt.
- Không tạo trang địa phương/dự án mới khi chưa có dự án thật.
- Search Console API / GA4 Data API / Merchant API v1: chưa có credentials (GSC Wizard yêu cầu trả phí, OpenSEO
  `not_connected`). Bước cấp quyền khi chủ website muốn: tạo service account trong Google Cloud của công ty, bật
  Search Console API, Analytics Data API, Merchant API; thêm email service account vào Search Console (quyền Đọc),
  GA4 property (Viewer) và Merchant Center (Standard/read). Chưa viết code tích hợp: không có khóa để kiểm thử.

## Handoff release (19/11/2026)

Commit: 42d60ab0, 7c318b77, 6db23288 trên master 04ae815e. Không có migration mới (cột merchant_* đã có từ
2026_11_19_000300). Release gate `MERCHANT_FEED_VERIFIED_ENABLED` giữ OFF.

QA:
- Frontend `npm test` 536/536 PASS (535 ở commit đầu + 1 test alt); `nuxi typecheck` sạch; `nuxi build` exit 0.
- SSR bản build với API production (chỉ GET): Product có Offer giá, không `availability`; trang chính sách là `WebPage`.
- Pest (ProductWorkbook, ProductDetailApi, MerchantIdentifier, ProductDuplicate, ProductPreview, ProductBulkActions,
  AdminScreens) trên DB test local: 62 passed, 1 skipped, 658 assertions, exit 0.
- Pint `--test` và PHPStan trên file thay đổi: exit 0. Toàn repo còn lỗi ở file có sẵn trên master, không thuộc diff.
- Readiness chạy thật trên DB clone (không phải production; media local cũ): 330 sản phẩm hiển thị, 13 thiếu mã,
  21 thiếu thương hiệu, 5 thiếu giá, 147 `image_file` do clone thiếu ảnh (production có đủ ảnh cho 315 item).
  Phải chạy lại trên production để có số đúng của production.

Không có dữ liệu Google đã xác thực: Search Console (OpenSEO `not_connected`, GSC Wizard yêu cầu trả phí), GA4 Data
API, Merchant API chưa được cấp quyền. Không có số liệu index, CTR hay thứ hạng.
