# Rà soát mức độ sử dụng Admin và kế hoạch nâng cấp — 2026-09-05

## Phạm vi

- Đối chiếu cấu hình menu/type, route, source, template, consumer public và dữ liệu tổng hợp trong MariaDB local.
- Kiểm tra form thật qua HTTP bằng tài khoản fixture local; fixture được dọn trong `finally`.
- Không ghi production, không xóa record/upload legacy và không đổi schema trong đợt rà soát này.
- Tài liệu này bổ sung trạng thái mới cho các audit ngày 2026-08-29, 2026-09-01 và 2026-09-04; khi có khác biệt, kết quả ngày 2026-09-05 được ưu tiên.

## Kết luận ngắn

Admin không thiếu nhiều màn CRUD, nhưng đang có ba vấn đề vận hành chính:

1. Dữ liệu sản phẩm không đồng đều: thông số cấu trúc được dùng tốt nhưng ảnh, nội dung và SEO còn thiếu nhiều.
2. Một số nội dung public đang có consumer nhưng type tương ứng bị ẩn khỏi Admin, khiến nhân viên không thể cập nhật nguồn dữ liệu chính thức.
3. Nhiều tính năng đã có nhưng cách tổ chức còn theo từng record, chưa phù hợp khi vận hành hàng trăm sản phẩm hoặc chiến dịch diện rộng.

## Việc đã xử lý trong đợt này

| Hạng mục | Kết quả |
|---|---|
| Khung card khuyến mãi | Ảnh sản phẩm và PNG khung được ghép hai lớp; không thay ảnh gốc. Form sản phẩm có preview đúng cấu trúc ngoài website. |
| Tìm kiếm sản phẩm | Truy vấn ban đầu đã lấy `status` và `photo_khuyenmai`, nên card trong `/tim-kiem/` không còn mất khung. |
| Bật/tắt banner và popup | Công tắc `Hiển thị trên website` được đưa lên đầu form `banner-top` và `popup`; không còn bị chôn gần cuối form. |
| Gom điểm điều khiển chiến dịch | Sidebar có nhóm `Chiến dịch hiển thị` gồm Banner đầu trang, Popup và lối vào danh sách sản phẩm đang có khung; thumbnail Admin cũng preview hai lớp. |
| Breadcrumb | Bỏ nền ảnh, thu chiều cao và cân khoảng trống quang học trên/dưới ở desktop/mobile. |

### Vị trí vận hành khung card hiện tại

Có thể đi theo một trong hai đường:

- `Admin → Chiến dịch hiển thị → Sản phẩm có khung card` để xem đúng nhóm đang chạy.
- `Admin → Quản lý Sản phẩm → Sản phẩm → Sửa → Khung khuyến mãi/sự kiện` để sửa trực tiếp một sản phẩm.

Khung phải là PNG nền trong suốt, phần giữa để trống. Muốn hiện ngoài website cần đồng thời có file khung và bật trạng thái `Khuyến mãi/Sự kiện` của sản phẩm.

Đây là mô hình per-product phù hợp để thử nghiệm. Không nên dùng nó làm mô hình cuối cùng cho một chiến dịch áp lên nhiều sản phẩm.

## Mức sử dụng dữ liệu sản phẩm local

Snapshot local có 443 sản phẩm:

| Trường/tính năng | Đang có dữ liệu | Nhận định |
|---|---:|---|
| Ảnh chính | 80 | 363 sản phẩm thiếu ảnh chính; đây là khoảng trống nội dung lớn nhất. |
| Thông số cấu trúc | 419 | Được tận dụng tốt; nên giữ làm nguồn chính cho bảng thông số. |
| Nội dung CKEditor `contentvi` | 77 | Chỉ phù hợp làm nội dung tự do/fallback, không nên là cách nhập duy nhất. |
| Mô tả ngắn `descvi` | 3 | Gần như chưa được vận hành. |
| Chính sách mua hàng riêng | 153 | Có sử dụng, nhưng cần giữ rõ quy tắc override/fallback chính sách chung. |
| Video sản phẩm | 0 | Có consumer và form nhưng chưa được tận dụng. Chưa xóa trước khi chốt nhu cầu. |
| Ảnh phụ `photo2` | 0 | Có consumer legacy nhưng không có dữ liệu; là ứng viên ẩn khỏi form sau xác minh staging. |
| Khung campaign | 1 | Record demo local; mô hình hiện tại chưa phù hợp gán hàng loạt. |
| Điểm nổi bật / ứng dụng / lắp đặt / chứng nhận mới | 0 | Form có nhưng chưa có quy trình nhập hoặc kế thừa nội dung. |

Gallery có dữ liệu nhưng không cứu được 363 sản phẩm thiếu ảnh chính: nhóm thiếu ảnh chính hiện cũng không có gallery. Vì vậy cần xử lý nguồn media/import, không chỉ đổi CSS card.

## Mức sử dụng khối nội dung và SEO

### Khối nội dung mô tả

- 69 khối gắn trực tiếp theo sản phẩm.
- 1 khối gắn theo thương hiệu.
- Chưa có khối dùng chung theo danh mục cấp 1/cấp 2 trong snapshot local.

Hệ thống kế thừa đã tồn tại nhưng gần như chưa được khai thác. Nếu tiếp tục nhập từng ảnh và từng đoạn trong CKEditor cho mỗi sản phẩm, chi phí vận hành sẽ tăng tuyến tính và bố cục khó đồng nhất.

Hướng đúng là tạo bộ section chuẩn ở danh mục/thương hiệu, cho sản phẩm kế thừa mặc định và chỉ override phần thật sự khác biệt.

### SEO

- Trong 443 sản phẩm hiện tại, 80 sản phẩm có SEO title và 73 có SEO description.
- Có 89 dòng SEO mồ côi trỏ tới sản phẩm không còn tồn tại trong snapshot local.
- Source lưu sản phẩm đã có fallback sinh SEO khi tạo/lưu, nhưng dữ liệu cũ chưa được backfill đồng đều.

Không nên âm thầm ghi đè SEO đã được biên tập. Cần màn preview/dry-run chỉ đề xuất cho trường đang rỗng, cho phép nhân viên duyệt trước khi ghi hàng loạt.

## Nội dung public có consumer nhưng bị ẩn khỏi Admin

| Dữ liệu | Bằng chứng consumer | Vấn đề | Đề xuất |
|---|---|---|---|
| `tom-tat-gioi-thieu` | `sources/about.php` đọc static này | Block cấu hình đang comment | Khôi phục type vào đúng nhóm Giới thiệu và smoke test trang public. |
| `hinh-thuc-thanh-toan` | `sources/order.php` và `Functions.php` đọc type này | Có dữ liệu local nhưng Admin không cho sửa | Khôi phục CRUD với field tên/mô tả và xác nhận checkout quote/order dùng đúng nguồn. |
| `watermark`, `watermark-news` | `libraries/router.php` đọc hai type | Record và consumer còn nhưng config Admin đang comment | Khôi phục có kiểm soát hoặc vô hiệu hóa cả consumer sau khi có quyết định nghiệp vụ; không để trạng thái nửa dùng nửa ẩn. |

`chinh-sach` hiện được khai báo hai lần trong `config-type-news.php`. Block sau ghi đè một phần block trước, còn các key không khai báo tiếp tục thừa hưởng âm thầm. Cần gộp thành một cấu hình đầy đủ rồi chạy lại menu/form/public smoke.

## Phần chưa dùng hoặc nên xem lại

### Có thể archive sau khi xác minh, chưa được xóa

- Media type cũ `background`, `background-breadcrumb`, `banner`, `banner-gioi-thieu`, `banner-san-pham`, `banner-du-an`: hiện không còn consumer PHP public sau khi breadcrumb chuyển sang dạng gọn.
- Static `slogan`: consumer trong `sources/allpage.php` đang comment.
- `cau-truc-co-cau`: có dấu hiệu tồn tại song song ở static/news nhưng chưa thấy consumer public rõ ràng.

Quy trình bắt buộc trước cleanup: backup DB/upload → scan consumer staging → ghi mapping URL/owner → ẩn menu trước → theo dõi → mới quyết định archive. Không xóa ngay record hoặc file.

### Không phải code chết

- `excel.php`, `excelAll.php`, `word.php`, `wordAll.php` vẫn được màn đơn hàng liên kết để xuất file.
- `product_bulk.php` đang được `product.php` nạp và là luồng nhập/xuất sản phẩm mới.
- `photo2`, video và gallery vẫn có consumer public dù dữ liệu hiện tại ít hoặc bằng 0.

## Rủi ro kỹ thuật ảnh hưởng nhân viên vận hành

### P0 — Query tìm kiếm Admin chưa parameterized đồng đều

Scan source phát hiện 21 vị trí ghép trực tiếp keyword vào `LIKE` trong các module product taxonomy, user, places, news, tags, language và export đơn hàng. `htmlspecialchars()` không thay thế prepared statement cho SQL.

Hướng xử lý: chuyển từng module sang `LIKE ?`, bind `%keyword%`, ép kiểu toàn bộ ID filter, thêm characterization test cho dấu nháy/ký tự `%`/Unicode và giữ nguyên pagination/query state.

### P0/P1 — Mật khẩu legacy dùng MD5

Các đường quản lý tài khoản vẫn tạo/so sánh password bằng MD5 ghép secret/salt. Không đổi hàng loạt trong một patch giao diện.

Hướng xử lý: thêm verifier tương thích hash cũ, rehash bằng `password_hash()` sau lần đăng nhập hợp lệ, dùng `password_verify()` cho hash mới, rồi thống kê và loại đường MD5 sau giai đoạn chuyển tiếp.

### P1 — RBAC có code nhưng đang tắt

`$config['permission']['active'] = false`, nên khi có nhiều nhân viên sẽ khó tách quyền nội dung, sản phẩm, đơn hàng và cấu hình hệ thống.

Hướng xử lý: chốt ma trận vai trò trước; test server-side cho save/delete/status/upload/export; bật trên staging bằng tài khoản fixture; chuẩn bị tài khoản break-glass và rollback config trước khi bật production.

### P1 — Không có hàng đợi “độ hoàn thiện sản phẩm” đầy đủ

Dashboard mới theo dõi một số chỉ số, nhưng chưa gom các lỗi quan trọng như thiếu ảnh, thiếu SEO, thiếu danh mục, thiếu nội dung hoặc lỗi kế thừa.

Hướng xử lý: thêm bộ lọc/readiness score chỉ đọc; mỗi chỉ số dẫn về danh sách đã lọc và bulk export tương ứng. Không tự sửa dữ liệu khi người dùng chỉ bấm xem.

## Kế hoạch phát triển đề xuất

### Giai đoạn 1 — Correctness và khả năng vận hành

1. Parameterize 21 query tìm kiếm còn lại và thêm regression.
2. Gộp cấu hình `chinh-sach`; khôi phục ba nguồn public đang bị ẩn khỏi Admin sau smoke test.
3. Thêm hàng đợi thiếu ảnh/SEO/danh mục/nội dung vào Admin; ưu tiên xử lý 363 sản phẩm thiếu ảnh.
4. Thêm SEO preview/dry-run và bulk fill **chỉ trường rỗng**; không đổi slug/URL public.

### Giai đoạn 2 — Không nhập thủ công lặp lại

1. Chuẩn hóa thư viện section: hero, điểm nổi bật, gallery ứng dụng, hướng dẫn lắp, chứng nhận, CTA.
2. Cho section kế thừa theo `danh mục → thương hiệu → sản phẩm`; product chỉ override section khác biệt.
3. Dùng ZIP/bulk media theo mã sản phẩm và báo cáo file không khớp; không bắt nhân viên mở từng sản phẩm.
4. Chuyển form sản phẩm thành các tab có completeness indicator; giữ CKEditor làm “nội dung tự do”, không dùng làm layout engine chính.

### Giai đoạn 3 — Thư viện chiến dịch thay khung per-product

Thiết kế entity campaign dùng chung gồm:

- tên nội bộ, PNG overlay/asset, link và mức ưu tiên;
- thời gian bắt đầu/kết thúc, trạng thái bật/tắt;
- phạm vi áp dụng: toàn site, danh mục, thương hiệu hoặc danh sách sản phẩm;
- preview desktop/mobile và kiểm tra vùng giữa trong suốt;
- quy tắc chỉ chọn một campaign thắng khi nhiều phạm vi trùng nhau.

Card chỉ resolve campaign đang hiệu lực; nhân viên không phải upload cùng một PNG cho hàng trăm sản phẩm. Cột `photo_khuyenmai` hiện tại được giữ làm override trong giai đoạn chuyển tiếp.

### Giai đoạn 4 — Bảo mật và cleanup có kiểm soát

1. Chuyển password hash theo cơ chế tương thích và bật RBAC sau staging.
2. Lập audit trail cho thay đổi trạng thái/giá/nội dung quan trọng.
3. Ẩn rồi archive type/media thật sự không còn consumer; không xóa upload trước thời hạn rollback.
4. Xử lý 89 SEO orphan bằng báo cáo và backup; không chạy DELETE tự động trong deployment source.

## Test đã chạy

- PHP 7.4 lint cho source tìm kiếm, form sản phẩm, form media và breadcrumb: đạt.
- Node syntax check cho Admin JS và Playwright test: đạt.
- Admin readiness authenticated: đạt toàn bộ, gồm preview khung sản phẩm và công tắc của banner/popup.
- Playwright Chromium tìm kiếm khung campaign: 2/2 desktop/mobile đạt.
- Playwright Chromium header/menu/breadcrumb/footer: 2/2 desktop/mobile đạt.
- `git diff --check`: đạt.

## Deploy và rollback

- Chưa deploy production và chưa chạy migration/SQL production.
- Patch hiện tại chỉ cần deploy source/assets và xóa cache ứng dụng sau deploy.
- Rollback source bằng revert commit của đợt này; không cần rollback schema.
- Record demo khung/banner/popup chỉ thuộc DB local. Nếu không cần demo, dùng câu rollback local đã ghi trong thư mục artifact; không xóa media production theo tên file demo.
