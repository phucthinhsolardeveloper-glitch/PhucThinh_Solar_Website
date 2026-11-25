# GA4, Merchant Center, Search Console — việc cần kiểm và việc của chủ website (25/11/2026)

Trạng thái: **chưa kiểm tài khoản ngoài nào** (phiên làm việc không có quyền đọc GA4, GTM, Merchant Center, Search Console). Không thay đổi cấu hình nào.
Số liệu công khai đã đo: feed `/google-shopping.xml` ngày 25/11 buổi tối có 274 mục, cả 274 khai `in_stock` và `identifier_exists=no`, 0 mục có MPN hoặc GTIN.

## GA4 / GTM

Trước khi phát hành:

| # | Việc | Đạt khi |
|---|---|---|
| 1 | Trong GTM `GTM-52JWR8Q7`, tìm thẻ hoặc biến đọc `ecommerce.value` của `purchase` | Không thẻ nào hiểu đó là tổng tiền khách trả; nếu có, chuyển sang `order_success.order_value` |
| 2 | Xác nhận không có thẻ GA4 Event nào trong GTM gửi lại `purchase`, `generate_lead` | Mỗi sự kiện chỉ tới GA4 một lần |

Sau khi phát hành (Tag Assistant hoặc DebugView). Gửi form hoặc đặt đơn thật chỉ khi chủ website đồng ý, dùng tên ghi rõ là thử:

| Sự kiện | Tham số phải thấy |
|---|---|
| `generate_lead` | `form_kind` (contact, product, dealer), `form_source` (home, contact_page, consultation_popup, cart_quote, solar_estimator, product_page, dealer_page, dealer_popup), `page_path` |
| `estimator_result` | `building_type`, `with_battery`, `page_path`; chỉ sau khi khách đổi thông tin, một lần mỗi lượt xem |
| `purchase` | `transaction_id` là mã đơn; `value` = tiền hàng; `shipping` = phí giao; một lần mỗi đơn |

Không sự kiện nào được chứa tên, số điện thoại, email, địa chỉ hay số tiền điện.

Cấu hình trong GA4 (chủ website hoặc người quản trị):

1. Custom dimension phạm vi sự kiện: `form_source`, `form_kind`, `building_type`, `with_battery`.
2. Đánh dấu `generate_lead` là key event; kiểm `purchase` đang là key event.
3. Liên kết Search Console với GA4.
4. Đối chiếu mỗi tuần: số lead trong Admin với `generate_lead`, số đơn với `purchase`. Khách từ chối cookie không hiện đủ trong báo cáo nên GA4 thấp hơn Admin là bình thường; ghi lại tỷ lệ.
5. Ghi chú ngày phát hành: từ ngày đó doanh thu `purchase` không gồm phí giao.

## Merchant Center

Số mục feed giảm từ 324 xuống 274 trong ngày 25/11 vì 50 sản phẩm đèn đường được chuyển sang "Liên hệ báo giá" (sản phẩm không có giá công khai không vào feed). Muốn đưa lại: Admin → Sản phẩm → chọn đèn → Giá → Hiện giá cho khách.

| # | Việc của chủ website | Chứng cứ |
|---|---|---|
| 1 | Chốt 50 đèn đường có hiện giá lại hay không | Quyết định |
| 2 | Tồn kho thật từng mã | Sổ kho |
| 3 | MPN hoặc GTIN từng mã, hoặc xác nhận hàng không có mã định danh | Nhãn, datasheet |
| 4 | Chính sách đổi trả và giao hàng trong Merchant Center khớp trang chính sách | Trang chính sách trên web |
| 5 | Xem mục "Cần chú ý" của Merchant Center, xuất danh sách lỗi | File xuất |

Kiểm dữ liệu trên máy chủ (chỉ đọc, chưa chạy trong đợt này): `php artisan pts:merchant-readiness --only-blocked --csv=<file>`.
Chỉ bật `MERCHANT_FEED_VERIFIED_ENABLED=1` khi cột "feed đã xác minh" đạt ngưỡng 286. Không dùng mã `PTS-…` làm MPN; không tự đặt GTIN, tồn kho, chính sách.

## Search Console

Sau khi phát hành:

| # | Việc | Đạt khi |
|---|---|---|
| 1 | Kiểm URL trang thương hiệu `?page=2` | Cho phép lập chỉ mục, canonical là chính URL đó |
| 2 | Theo dõi báo cáo "Không tìm thấy (404)" trong 2 tuần | Chỉ tăng ở URL bộ lọc sai hoặc trang vượt cuối danh sách |
| 3 | Xem `/ho-gia-dinh` và `/doanh-nghiep` | Hai mô tả khác nhau |

Dữ liệu cần xuất để quyết các việc còn treo (28 ngày, theo trang và truy vấn):

- 13 trang thương hiệu chưa có sản phẩm: canadian-solar, aesolar, tcl-solar, sungrow, huawei, growatt, goodwe, sofar, anker-solix, deye, sigenergy, leader-solar, phuc-thinh-solar. Trang có truy vấn thật: bổ sung nội dung có thật; không có: cân nhắc `noindex, follow` và bỏ khỏi sitemap.
- Trang Sharp 550W, danh mục đèn, danh mục pin: vị trí trung bình và truy vấn trước khi đổi title.

## Nội dung chờ chủ website

| Việc | Cần |
|---|---|
| Popup hứa gọi trong 15 phút, hạn nội bộ 2 giờ làm việc | Một cam kết duy nhất |
| Bảo hành Sharp 550W (10 năm và "2 đến 5 năm") | Phiếu bảo hành |
| Hai dự án điện mặt trời dùng cùng đoạn tổng quan | Công suất, thiết bị, thời điểm, phạm vi, ảnh được phép công bố |
| Dự án "Thu Hồng" ghi khách "Nhà nghỉ Thu Hiền" | Hồ sơ dự án |
| Trang Đại lý còn đoạn "Năm 2021" | Nội dung mới |
| Lưu mã click quảng cáo khi khách từ chối cookie | Quyết định chính sách |

Báo cáo CRO và kỹ thuật của THY: không có trên máy này và chưa đọc được qua Bridge, nên chưa đối chiếu.
