# Chế độ giá công khai

## Hai tầng điều khiển

Public mặc định đang ở chế độ **“Liên hệ báo giá”**: website giữ nguyên giá trong DB, Admin và Excel nhưng mọi sản phẩm public hiển thị “Liên hệ”.

Công tắc nằm ở Admin → Cấu hình website → Thiết lập thông tin → **Hiển thị giá sản phẩm** (hai lựa chọn: “Liên hệ báo giá” và “Hiển thị giá”).

Khi công tắc toàn site bật giá, từng sản phẩm vẫn có checkbox **Liên hệ báo giá** trong form Admin. Checkbox này chỉ ẩn giá và nút mua của sản phẩm đó; sản phẩm vẫn công khai nếu checkbox **Hiển thị** đang bật. Excel biểu diễn ba trạng thái độc lập bằng `Hiển thị (1/0)`, `Bán chạy (1/0)` và `Liên hệ báo giá (1/0)`.

## Cách hoạt động

- Lựa chọn lưu vào `table_setting.options` JSON, khóa `public_product_prices` với giá trị `"1"` (hiện giá) hoặc `"0"` (báo giá). `administrator/sources/setting.php` chỉ nhận đúng hai giá trị này.
- `libraries/public_pricing.php` đọc `$optsetting['public_product_prices']`. Khi khóa chưa tồn tại (database cũ), code fallback về hằng `PUBLIC_PRODUCT_PRICES` (mặc định `false`) nên trạng thái an toàn là báo giá.
- Sau khi lưu, Admin xóa cache truy vấn (`$cache->delete()`) để thiết lập có hiệu lực ngay; nếu không, front-end còn giữ bản cache `select * from #_setting` tới 2 giờ.
- Helper `publicProductRequiresQuote($product)` trả về `true` khi toàn site đang ở chế độ báo giá, sản phẩm có trạng thái `baogia`, hoặc sản phẩm không có `regular_price`/`sale_price`.

## Khi ở chế độ báo giá

- Card và trang chi tiết không xuất số tiền hoặc phần trăm giảm giá.
- Không có filter/sort theo giá (`is_price` bị bỏ qua ở `product.php`, `fillter_service.php`, `search_product.php`).
- Product structured data không có `Offer.price` (`templates/layout/strucdata.php`).
- Admin khóa xuất Merchant feed để không tạo giá feed lệch landing page.
- Nút mua/giỏ được thay bằng CTA gọi hoặc gửi yêu cầu báo giá.
- `api/cart.php` trả HTTP 409; `sources/order.php` không tạo đơn giá 0.

## Bật lại giá

Sau khi công ty duyệt loại giá được niêm yết, chọn “Hiển thị giá” trong Admin và lưu. Sản phẩm bật checkbox **Liên hệ báo giá** hoặc không có giá vẫn hiện “Liên hệ”. Sau thay đổi cần smoke lại product → cart → checkout → confirmation. Không cần migration và không cần sửa dữ liệu giá.

Rollback: chọn lại “Liên hệ báo giá”, lưu và xác nhận trang sản phẩm không còn `priceCurrency`, số tiền hoặc nút mua.
