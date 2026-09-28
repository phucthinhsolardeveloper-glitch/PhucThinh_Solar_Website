# Cấu hình thanh toán và vận chuyển

Code đã có lớp tích hợp dùng credential từ environment. Provider mặc định tắt
cho tới khi có credential; COD, chuyển khoản và bảng phí phường/xã hiện tại vẫn
hoạt động.

## Environment

Các biến cần cấp ngoài Git: `VNPAY_ACTIVE`, `VNPAY_TMN_CODE`,
`VNPAY_HASH_SECRET`, `VNPAY_RETURN_URL`, `VNPAY_IPN_URL`; và theo từng hãng:
`GHN_TOKEN`, `GHN_SHOP_ID`, `GHTK_API_TOKEN`, `GHTK_PARTNER_CODE`,
`VIETTELPOST_USERNAME`, `VIETTELPOST_PASSWORD`, `VIETTELPOST_PARTNER_ID`,
`NHATTIN_USERNAME`, `NHATTIN_PASSWORD`, `NHATTIN_PARTNER_ID` cùng cờ
`*_ACTIVE=1` khi muốn bật.

Không commit secret, không đặt secret vào `table_setting`, và không gửi secret
qua frontend. Trước production phải chạy migration 058 sau backup/preflight.

Phần vận chuyển còn cần cấu hình không nhạy cảm: `SHIPPING_ORIGIN_NAME`,
`SHIPPING_ORIGIN_PHONE`, `SHIPPING_ORIGIN_ADDRESS`, mã Tỉnh/TP và Phường/Xã
điểm gửi (`SHIPPING_ORIGIN_CITY_CODE`, `SHIPPING_ORIGIN_WARD_CODE`), cùng cân
nặng/kích thước mặc định (`SHIPPING_DEFAULT_WEIGHT_GRAMS`,
`SHIPPING_DEFAULT_LENGTH_CM`, `SHIPPING_DEFAULT_WIDTH_CM`,
`SHIPPING_DEFAULT_HEIGHT_CM`). Nếu một đơn có nhiều kiện hoặc sản phẩm có quy
cách riêng thì adapter sẽ nhận dữ liệu kiện hàng riêng thay cho giá trị mặc
định.

VNPay callback dùng `api/payment_vnpay.php`; đặt Return URL có `?mode=return` và
IPN URL có `?mode=ipn`. Endpoint chỉ chấp nhận callback có chữ ký hợp lệ và số
tiền khớp `table_order.total_price`. Callback lặp lại không tạo thêm giao dịch
vì có unique key theo provider + mã đơn.

Các adapter vận chuyển được đăng ký trong `commerceShippingProviders()` để
Admin/checkout có thể bật tắt từng hãng. `api/shipping_quote.php` đã là endpoint
quote chung; hiện trả bảng phí phường/xã làm fallback và báo trạng thái mapping
cho từng hãng. Payload riêng của từng hãng chỉ được triển khai tiếp sau khi
nhận credential, kho gửi, dịch vụ, mapping địa chỉ và quy cách kiện hàng; không
tự suy đoán mã dịch vụ hay bảng giá.

VNPay là cổng thanh toán: khách được chuyển sang trang thanh toán, hệ thống
nhận callback có chữ ký và đối soát số tiền. Chuyển khoản ngân hàng là thanh
toán thủ công: Admin xác nhận tiền vào tài khoản. Hai phương thức không nên
dùng chung trạng thái `Đã thanh toán` trước khi có bằng chứng tương ứng.
