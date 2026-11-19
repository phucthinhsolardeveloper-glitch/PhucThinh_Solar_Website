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

- 12 sản phẩm không có mã (`code` trống), trong đó 4 đang báo giá (3 cáp Thipha, kẹp biên 35) -> cần mã thật và giá công khai.
- JD-18300: có mã, đang báo giá.
- SolaX T-BAT-SYS-LV D150 Pro: có mã, giá, ảnh chính 1600 px; lý do không xác định từ dữ liệu public
  (nghi ảnh Shopping `feed_image` hỏng/nhỏ). Chạy lệnh readiness trên DB production (chỉ đọc) để xác nhận.

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
