# Admin: danh sách sản phẩm và các trạng thái

Màn `/admin/products` (`backend/app/Filament/Resources/Products/`).

## Ba trạng thái độc lập

| Trạng thái | Cột | Khách thấy |
|---|---|---|
| Hiện / ẩn (`is_visible`) | công tắc "Hiện / ẩn" + nhãn "Trên website" | ẩn: trang báo đã ngừng hiển thị, rời danh sách, tìm kiếm, sitemap, Google Shopping |
| Ẩn giá (`quote_only`) | "Giá khách thấy" | "Liên hệ báo giá", không đặt mua, rời Google Shopping; giá vẫn lưu |
| Nổi bật (`is_bestseller`) | "★ Nổi bật" dưới tên | nhãn trên thẻ, đứng đầu khi sắp xếp Nổi bật |

"Giá khách thấy" theo đúng `PricingPolicy` của website (`ProductPriceState`): ẩn giá, chưa nhập giá và công tắc ẩn giá
toàn website đều ra "Liên hệ báo giá", kèm dòng ghi lý do.

## Thao tác

- Tab Tất cả / Đang hiện / Đang ẩn / Báo giá / Nổi bật; số đếm theo bộ lọc danh mục và thương hiệu.
- Chọn nhiều: nhóm **Hiện / Ẩn**, **Giá** (ẩn/hiện giá, đổi giá, khuyến mãi), **Phân loại & xóa**. Hộp xác nhận nói số
  sản phẩm thật sự đổi.
- Thông báo sau thao tác hàng loạt có **Hoàn tác** (30 phút, một lần, đúng người làm). `ProductUndo` chỉ trả lại ô chưa
  bị sửa tiếp. Không áp dụng cho xóa sản phẩm.
- "Sửa nhanh" (hoặc bấm vào giá): hiện/ẩn, ẩn giá, giá, danh mục, nổi bật.

## Bao lâu website đổi theo

Nitro giữ bản đọc công khai 10 giây và không bao giờ trả bản đã hết hạn (`frontend/nuxt.config.ts`). Không cần nút xóa
cache. Trình duyệt đang mở sẵn trang thì tải lại trang.

## Kiểm tra

`php artisan test --filter=ProductListStates`; `tools/visual-tests/audit/admin-products-states.cjs` (chỉ chạy trên bản
xem thử local vì script sửa dữ liệu).
