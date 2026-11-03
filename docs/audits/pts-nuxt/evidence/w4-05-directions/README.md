# W4-05 — So sánh hướng thiết kế Cinematic Project Story

Các ảnh dưới đây là mockup định hướng trước khi implementation. Hình ảnh và
metadata sử dụng trong mockup lấy từ tập dữ liệu dự án đã kiểm tra ở local
preview; các nội dung dẫn giải chỉ mô tả cấu trúc, không phải số liệu dự án.
Detail fixture hiện xác nhận tên dự án, khách hàng, danh mục, địa điểm và ảnh
cover; chưa có media gallery hoặc thiết bị liên kết. Production chỉ render các
chapter tương ứng khi CMS có dữ liệu.

## Hướng 1 — Solar Cinema

Listing mở bằng ảnh cover dự án nổi bật toàn khổ, copy/filters lấy từ DTO CMS,
sau đó là dải hồ sơ khác theo ảnh 4:3. Detail mở bằng ảnh công trình và tiêu đề
trên nền navy; chapter navigation dẫn tự nhiên tới tổng quan, tư liệu, thiết bị
liên quan. Chuyển động chỉ dùng opacity/transform nhẹ khi vào viewport và tôn
trọng `prefers-reduced-motion`; không khóa scroll.

- [Listing — Solar Cinema](./w4-05-direction-01-cinema-list.png)
- [Detail — Solar Cinema](./w4-05-direction-01-cinema-detail.png)

Ưu điểm: tạo nhịp cảm xúc rõ từ ảnh công trình thật, nối danh sách sang hồ sơ
chi tiết và mở rộng được khi CMS có thêm tư liệu. Tradeoff: ảnh cover phải đủ
chất lượng, có overlay tương phản; nếu thiếu ảnh thì dùng fallback trung tính.

## Hướng 2 — Architectural Atlas

Listing bất đối xứng theo kiểu mục lục/bản vẽ: featured ngang, các hồ sơ còn lại
thành dòng có thứ tự. Detail dùng index cạnh nội dung, tách metadata và technical
highlights thành cột riêng.

- [Listing — Architectural Atlas](./w4-05-direction-02-atlas-list.png)
- [Detail — Architectural Atlas](./w4-05-direction-02-atlas-detail.png)

Ưu điểm: đọc nhanh, nhấn mạnh cấu trúc thông tin và metadata. Tradeoff: thiên về
catalogue, ít cảm giác điện ảnh; index sticky cần tinh chỉnh kỹ trên màn hình nhỏ.

## Hướng 3 — Documentary Evidence Wall

Listing là contact sheet của các ảnh cover dự án; detail tạo một evidence wall
ảnh lớn cạnh ledger danh mục/khách hàng/địa điểm, sau đó mới tới nội dung và phần
thiết bị liên quan.

- [Listing — Documentary Evidence Wall](./w4-05-direction-03-evidence-list.png)
- [Detail — Documentary Evidence Wall](./w4-05-direction-03-evidence-detail.png)

Ưu điểm: bằng chứng hình ảnh và dữ liệu dễ đối chiếu, không cần lời quảng bá.
Tradeoff: listing trở nên phụ thuộc vào độ đồng đều của ảnh; ảnh đơn lẻ không đủ
để tạo wall chi tiết nếu CMS chưa có gallery.

## Lựa chọn

Chọn **Solar Cinema** vì cân bằng được mục tiêu cinematic với nội dung đang có:
listing có thể dùng project cover/intro hiện hữu, detail có thể dùng chapter
anchor và chỉ render phần nội dung, gallery, thiết bị khi dữ liệu CMS tương ứng
tồn tại. Không đưa benefit, thông số, ảnh hay quan hệ sản phẩm mẫu vào UI.

Các screenshot hướng thiết kế là mockup desktop 1440px để so sánh bố cục trước
code. Sau implementation, đã chụp before/after từ base `bcf1b985` và cùng local
DTO fixture:

| Route | Before | After |
|---|---|---|
| Listing, 1440px | [Before](./w4-05-before-list-1440.png) | [After](./w4-05-after-list-1440.png) |
| Listing, 390px | [Before](./w4-05-before-list-390.png) | [After](./w4-05-after-list-390.png) |
| Detail, 1440px | [Before](./w4-05-before-detail-1440.png) | [After](./w4-05-after-detail-1440.png) |
| Detail, 390px | [Before](./w4-05-before-detail-390.png) | [After](./w4-05-after-detail-390.png) |

Preview local: `http://127.0.0.1:3115/du-an-da-thuc-hien/` và
`http://127.0.0.1:3115/du-an-dien-nlmt-ho-anh-hoa-thanh-an/`. SSR lấy dữ liệu từ
fixture local ở port 8089; ảnh là asset dự án đã có trong repository. Fixture
không được commit và không đại diện cho trạng thái CMS live.

## Kiểm chứng

- Listing và detail đã mở ở viewport 320, 390, 768 và 1440px; `scrollWidth`
  khớp viewport ở cả hai route, mỗi trang có một H1.
- Chapter link `Điểm nhấn kỹ thuật` đổi fragment tới đúng section ở mobile.
- Browser console: 0 error, 0 warning trong lần kiểm tra cuối; ảnh dự án trả
  HTTP 200/304.
- `npm run typecheck` và `npm run build` trong `frontend/`: PASS.
