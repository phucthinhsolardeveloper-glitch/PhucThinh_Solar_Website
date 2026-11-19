# Audit Merchant feed và Product structured data — 2026-09-16

## Kết luận

Luồng CSV Shopping cũ chưa đủ an toàn để gửi thẳng lên Merchant Center: tên
cột chưa đúng đặc tả, giá thiếu đơn vị tiền tệ và ảnh chính được dùng thay khi
thiếu `photo_feed` dù ảnh đó có thể chứa chữ, logo hoặc khung chiến dịch. Product
JSON-LD cũng còn dữ liệu mẫu không thuộc Phúc Thịnh Solar.

Đợt này đã sửa logic và test nhưng **không nhập ảnh, không sửa dữ liệu sản phẩm
và không gửi feed ra dịch vụ bên ngoài**.

## Thay đổi đã triển khai

- CSV dùng các cột `id`, `title`, `description`, `link`, `image_link`,
  `availability`, `price`, `sale_price`, `brand`, `condition`,
  `identifier_exists`, `product_type`.
- Chỉ xuất sản phẩm đang hiển thị, có mã tối đa 50 ký tự, giá hợp lệ, không gắn
  trạng thái `baogia`, và có ảnh `photo_feed` thật từ 500×500 px trở lên.
- Không fallback sang ảnh chính và không tự bịa GTIN/MPN/thương hiệu. Khi chưa
  có GTIN hoặc MPN đã xác minh, feed dùng `identifier_exists=no`.
- Chặn xuất feed khi website ẩn giá; sản phẩm báo giá riêng cũng bị loại để giá
  feed không lệch landing page.
- Form Admin chặn ngay ảnh Shopping dưới 500×500 px; danh sách có filter
  `Thiếu ảnh Shopping sạch`. Nếu chưa có dòng đủ chuẩn, thao tác xuất trả thông
  báo và dẫn tới filter cần xử lý thay vì tải CSV rỗng.
- Preflight production kiểm tra migration 036 (`table_product.photo_feed`).
- Product JSON-LD bỏ MPN giả, review/aggregate rating giả, ngày hết hạn giá
  2020, `UsedCondition` và seller mẫu. Schema mới chỉ xuất brand/rating/Offer
  khi có dữ liệu thật; chế độ báo giá không xuất Offer.

## Snapshot dữ liệu local

Truy vấn chỉ đọc ngày 2026-09-16 trên `type=san-pham`:

| Chỉ số | Số lượng |
|---|---:|
| Tổng sản phẩm | 417 |
| Đang hiển thị | 415 |
| Thiếu ảnh chính | 223 |
| Có `photo_feed` | 0 |
| Thiếu mã sản phẩm | 21 |
| Thiếu cả giá gốc và giá bán | 29 |
| Đồng thời thiếu mã và giá | 12 |

Vì `photo_feed=0`, feed an toàn hiện chưa có sản phẩm để xuất. Đây là chủ ý:
không dùng ảnh website không đạt chuẩn để đổi một file CSV “có dữ liệu” lấy rủi
ro sản phẩm bị từ chối. Cần nhập ảnh sạch cho nhóm sản phẩm ưu tiên, sau đó mới
chạy thử và đọc lỗi cụ thể trong Merchant Center.

## Bổ sung cùng ngày — giao diện trang chi tiết sản phẩm

Chủ project hỏi "mấy cái nút này nằm ở đây có phải hợp lí k" (thanh tab Mô tả /
Thông số / Chính sách) và yêu cầu nâng cấp trang chi tiết cho ngang các trang
đã redesign. Đo ra hai vấn đề thật:

- Tab nằm ở lề trái container (x=32) nhưng nội dung trong tab có
  `margin:270px auto` (căn giữa, rộng 820px), nên tab lơ lửng không dính với
  nội dung nó điều khiển. Đã đưa hai thứ về cùng một trục, giữ giới hạn bề
  ngang cho dễ đọc.
- Mục tab đang chọn vẫn dùng gradient vàng (rule `.nav-link.active` ở
  theme-v2 gộp chung nhiều thành phần), trái hệ gold phẳng. Đã chuyển thành
  tab thật: đường kẻ chạy hết chiều rộng, mục đang chọn gắn vào vùng nội dung
  bằng gạch vàng dưới chân.
- Khối giá bỏ nền cream bo 12px, chuyển nền trung tính + vạch nhấn vàng; nút
  "Mua ngay" bỏ gradient về gold phẳng bo 4px; tiêu đề "Đánh giá sản phẩm" và
  "Sản phẩm liên quan" dùng đúng hệ tiêu đề section của site.

Đồng thời sửa một regression do lazy-load native: ảnh trong carousel "Sản phẩm
liên quan" có `src` trả 200 nhưng `naturalWidth=0` (10/15 ảnh) vì slider dùng
transform đẩy slide ra ngoài vùng nhìn nên trình duyệt hoãn tải vô thời hạn,
chỉ tải khi bấm mũi tên. Tách rõ hai khái niệm trong `_product_card.php`:
`$pcNoLazy` (tắt lazy JS, vẫn cho native lazy — dùng cho lưới tĩnh trang chủ)
và `$pcInCarousel` (tắt cả native lazy — chỉ cho carousel). Sau sửa: slider
15/15 ảnh tải đủ, trang chủ vẫn giữ 67 ảnh lazy và 350 KB.

## Giới hạn còn lại

- Database chưa có field GTIN/MPN đã xác minh; không được lấy SKU nội bộ làm
  MPN nếu nhà sản xuất chưa công bố.
- Database chưa có trạng thái tồn kho riêng. Feed hiện coi sản phẩm đang hiển
  thị và đủ điều kiện là `in_stock`; trước khi vận hành phải chốt quy trình tắt
  hiển thị hoặc bổ sung field tồn kho khi hết hàng.
- Hệ thống chỉ kiểm tra file, định dạng và kích thước; không thể tự chứng minh
  ảnh không chứa watermark/chữ. Người nhập vẫn phải kiểm duyệt bằng mắt.
- Chưa gửi thử lên tài khoản Merchant Center vì tác vụ đó cần quyền và phối hợp
  bên ngoài phạm vi local.

## Test và rollback

- Unit test: `scripts/test-product-merchant-feed.php` và
  `scripts/test-product-structured-data.php`.
- Browser regression: `tools/visual-tests/tests/public-pages.spec.js` parse
  JSON-LD thực từ một trang sản phẩm.
- Rollback source: hoàn nguyên các file feed/schema/test trong commit triển
  khai. Không cần rollback DB vì đợt này không ghi dữ liệu và không thêm schema.

## Addendum — Merchant schema và rollout gate, 2026-11-19

Đây là thay đổi source local, chưa migrate/deploy production và chưa sửa dữ liệu
sản phẩm. Migration
`2026_11_19_000300_add_merchant_product_attributes.php` thêm availability,
GTIN, MPN và cờ xác nhận không có mã; các trường định danh/trạng thái ban đầu
để trống, không backfill. Tiền tố `000100` đã thuộc migration kho hàng nên migration
mới dùng `000300`.

`MERCHANT_FEED_VERIFIED_ENABLED` mặc định `0`, nên route công khai tiếp tục dùng
feed/cache legacy hiện tại và không bị chuyển sang feed rỗng khi cột mới còn
NULL. Đây chỉ là compatibility giữ nguyên hành vi đã chạy: legacy hiện vẫn gửi
`in_stock` và `identifier_exists=no` theo quy tắc cũ. Hai giá trị đó không phải
bằng chứng tồn kho hay xác nhận thiếu mã định danh. Không bật cờ khi chưa có dữ liệu tồn kho được
xác nhận. Khi cờ bật, feed mới chỉ nhận `merchant_availability=in_stock` đã
chọn rõ; sản phẩm báo giá, hết hàng hoặc chưa xác minh không được đưa vào. Nếu
feed mới chưa đạt ngưỡng, endpoint dùng cache/feed legacy để giữ danh sách đang
được phục vụ và gửi `X-Feed-Mode: legacy-fallback`; feed verified rỗng không
được cache. Chỉ trả `503` khi không có cache legacy và legacy builder cũng
không có sản phẩm, tránh gửi danh sách rỗng như bản phát hành mới. Tắt cờ để
quay lại route/cache legacy.

Gate còn yêu cầu tối thiểu 286 item (mốc owner-provided gần nhất) qua
`MERCHANT_FEED_VERIFIED_MINIMUM_ITEMS`; cập nhật ngưỡng khi đối chiếu danh sách
được duyệt. Admin có nút tải CSV **Xem trước feed Merchant mới** riêng để đối
chiếu ID và số lượng mà không đụng route public. Ngưỡng 286 chỉ là chốt chặn từ
snapshot 315/286, không chứng minh các mặt hàng hiện còn đủ điều kiện.

Admin sản phẩm và workbook CSV/XLSX có các cột availability, GTIN, MPN và xác
nhận thiếu mã. GTIN được giữ dạng chuỗi, kiểm tra độ dài/check digit và loại dải
restricted/coupon; workbook XLSX ghi chuỗi tường minh, CSV đánh dấu GTIN có số 0
đầu để Excel không đổi thành số, importer bỏ dấu đánh dấu. GTIN và MPN dạng số
có số 0 đầu đều được bảo vệ trong CSV. UPC-A
`042100005264` giữ nguyên số 0 đầu và được xử lý theo prefix GS1 sau bước chuẩn
hóa, không coi số 0 UPC-A là prefix hạn chế. MPN chỉ được nhận khi
người biên tập nhập mã do hãng cấp và giới hạn 70 ký tự. Feed ghi
`identifier_exists=yes` với GTIN hợp lệ hoặc cặp MPN + brand; ghi `no` chỉ khi
đã xác nhận không có GTIN/MPN và không có dữ liệu nhập; trường hợp thiếu brand,
mã chưa rõ hoặc dữ liệu không hợp lệ để trống. Chưa thêm `out_of_stock` vào Admin
vì trang sản phẩm và giỏ hàng hiện vẫn cho đặt hàng; cần triển khai đồng bộ hành
vi bán hàng trước khi quảng bá trạng thái đó.

Quy tắc GTIN/MPN và `identifier_exists` được đối chiếu với [Google Product data
specification](https://support.google.com/merchants/answer/7052112), [Google
identifier_exists](https://support.google.com/merchants/answer/6324478) và [GS1
General Specifications](https://ref.gs1.org/standards/genspecs/). Không
tra cứu hoặc suy diễn thông số của sản phẩm từ website công ty. GTIN/MPN phải
được người quản trị đối chiếu nhãn/datasheet hãng; không dùng SKU nội bộ thay thế.

Rollback ứng dụng: đặt `MERCHANT_FEED_VERIFIED_ENABLED=0` và khởi động lại app;
feed/cache legacy dùng key cũ, cache verified dùng key riêng. Trước khi rollback
hoặc chạy `down()` cần xuất workbook lưu các GTIN/MPN/availability đã nhập vì
migration `down()` xóa các cột mới. Không có DB production, backup hay trạng thái
Merchant Center nào bị tác động trong đợt local này.
