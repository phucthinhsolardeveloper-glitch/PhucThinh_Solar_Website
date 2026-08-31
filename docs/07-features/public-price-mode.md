# Chế độ giá công khai

## Trạng thái hiện tại

`libraries/public_pricing.php` đặt `PUBLIC_PRODUCT_PRICES = false`. Website giữ nguyên giá trong DB, Admin và Excel nhưng mọi sản phẩm public hiển thị “Liên hệ”.

Khi tắt giá công khai:

- Card và trang chi tiết không xuất số tiền hoặc phần trăm giảm giá.
- Không có filter/sort theo giá.
- Product structured data không có `Offer.price`.
- Nút mua/giỏ được thay bằng CTA gọi hoặc gửi yêu cầu báo giá.
- API cart trả HTTP 409 và checkout không tạo đơn giá 0.

## Bật lại giá

Chỉ đổi constant sang `true` sau khi công ty duyệt loại giá được niêm yết. Sau thay đổi phải chạy `./scripts/predeploy-local.sh` và smoke lại product → cart → checkout → confirmation. Không cần migration và không cần sửa dữ liệu giá.

Rollback chế độ công khai giá: đặt constant về `false`, build/deploy lại và xác nhận trang sản phẩm không còn `priceCurrency`, số tiền hoặc nút mua.
