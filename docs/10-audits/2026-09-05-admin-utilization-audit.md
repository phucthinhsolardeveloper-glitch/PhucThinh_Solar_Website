# Rà soát mức độ sử dụng Admin và kế hoạch nâng cấp — 2026-09-05

> **Cập nhật 2026-09-11:** dữ liệu, Hero, chính sách, danh mục, review và ảnh
> Shopping đã thay đổi đáng kể. Dùng
> [`2026-09-11-full-site-admin-alignment-retest.md`](2026-09-11-full-site-admin-alignment-retest.md)
> làm trạng thái hiện hành; tài liệu này được giữ làm mốc lịch sử.

## Phạm vi

- Đối chiếu cấu hình menu/type, route, source, template, consumer public và dữ liệu tổng hợp trong MariaDB local.
- Kiểm tra form thật qua HTTP bằng tài khoản fixture local; fixture được dọn trong `finally`.
- Không ghi production và không xóa record/upload legacy. Migration `022` chỉ thêm hai bảng campaign; đã áp dụng trên DB local để kiểm thử, chưa chạy production.
- Tài liệu này bổ sung trạng thái mới cho các audit ngày 2026-08-29, 2026-09-01 và 2026-09-04; khi có khác biệt, kết quả ngày 2026-09-05 được ưu tiên.

## Kết luận ngắn

Admin không thiếu nhiều màn CRUD, nhưng đang có ba vấn đề vận hành chính:

1. Dữ liệu sản phẩm không đồng đều: thông số cấu trúc được dùng tốt nhưng ảnh, nội dung và SEO còn thiếu nhiều.
2. Các type phương thức thanh toán và watermark từng bị ẩn khỏi Admin dù public còn dùng; đợt này đã khôi phục màn quản trị và giữ nguyên dữ liệu.
3. Nhiều tính năng đã có nhưng cách tổ chức còn theo từng record, chưa phù hợp khi vận hành hàng trăm sản phẩm hoặc chiến dịch diện rộng.

## Việc đã xử lý trong đợt này

| Hạng mục | Kết quả |
|---|---|
| Khung card khuyến mãi | Có thư viện campaign dùng chung: một PNG áp theo danh mục cấp 1/cấp 2, thương hiệu hoặc sản phẩm chọn riêng; không thay ảnh gốc và không upload lặp. Khung per-product cũ được giữ làm override. |
| Resolver card dùng chung | Trang danh mục, tìm kiếm, carousel trang chủ và sản phẩm liên quan dùng cùng quy tắc campaign; đã bổ sung đủ `id_list`, `id_cat`, `id_brand`, `photo_khuyenmai` cho các query rút gọn. |
| Bật/tắt banner và popup | Công tắc `Hiển thị trên website` được đưa lên đầu form `banner-top` và `popup`; không còn bị chôn gần cuối form. |
| Gom điểm điều khiển chiến dịch | Sidebar có nhóm `Chiến dịch hiển thị` gồm Banner đầu trang, Popup, thư viện khung và danh sách sản phẩm đang có khung; thumbnail Admin resolve cả campaign lẫn override. |
| Thumbnail Admin | Thêm version theo `filemtime` cho CSS Admin và theo mtime nguồn cho bundle CSS public; trình duyệt không còn giữ stylesheet cũ làm PNG khung phình kín bảng. |
| Tần suất popup | Popup trang chủ chỉ tự mở một lần trong mỗi tab bằng `sessionStorage`; reload cùng tab không làm phiền lại, tab mới vẫn hiện và nội dung mới sau khi Admin cập nhật dùng khóa phiên bản mới. |
| Breadcrumb | Bỏ nền ảnh, thu chiều cao và cân khoảng trống quang học trên/dưới ở desktop/mobile. |
| Tìm kiếm Admin | 21 truy vấn legacy đã chuyển sang placeholder; các filter ID liên quan và toàn bộ filter xuất đơn hàng không còn ghép dữ liệu request vào SQL. |
| Nguồn nội dung dùng chung | Gộp `chinh-sach` về một cấu hình; khôi phục Admin cho phương thức thanh toán và hai watermark. Ba record thanh toán cùng hai record watermark local được giữ nguyên, không import/ghi đè DB. |

### Vị trí vận hành khung card

Có thể đi theo một trong hai đường:

- `Admin → Chiến dịch hiển thị → Thư viện khung chiến dịch → Thêm chiến dịch` để upload một PNG, đặt lịch, ưu tiên, bật/tắt và chọn phạm vi.
- `Admin → Chiến dịch hiển thị → Sản phẩm đang có khung` để xem kết quả đã resolve.
- `Admin → Quản lý Sản phẩm → Sản phẩm → Sửa → Khung riêng của sản phẩm` chỉ dùng khi một sản phẩm cần artwork khác chiến dịch.

Khung phải là PNG nền trong suốt, phần giữa để trống. Campaign chỉ tác động các sản phẩm khớp phạm vi và nằm trong lịch đang bật; không thay `photo` gốc hay trạng thái sản phẩm. Khung riêng cũ chỉ hiện khi sản phẩm bật `Bật khung riêng`.

Khi sửa campaign, danh mục/thương hiệu/sản phẩm đã bị ẩn vẫn được giữ trong bộ chọn với nhãn `(đang ẩn)`, tránh thao tác đổi ảnh hoặc lịch chạy vô tình làm mất phạm vi cũ.

Nếu nhiều campaign trùng sản phẩm: ưu tiên số lớn hơn thắng; nếu bằng nhau thì `sản phẩm → danh mục cấp 2 → thương hiệu → danh mục cấp 1 → toàn bộ`; nếu vẫn bằng nhau thì campaign tạo sau thắng. Không có thao tác xóa campaign/media trong UI: tắt campaign để giữ lịch sử và khả năng rollback.

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
| Khung per-product legacy | 1 | Record demo local được giữ làm override; thư viện campaign mới chưa tự backfill dữ liệu này. |
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

## Đối chiếu nội dung dùng chung với consumer public

| Dữ liệu | Bằng chứng consumer | Kết quả đối chiếu |
|---|---|---|
| `tom-tat-gioi-thieu` | `sources/about.php` có câu query nhưng template không đọc biến `$tomtat` | Đính chính audit: đây chưa phải consumer hoàn chỉnh. Type tiếp tục ẩn để không tạo màn hình Admin vô tác dụng; record local vẫn được giữ nguyên. |
| `hinh-thuc-thanh-toan` | `sources/order.php` và `Functions.php` đọc type này | Đã khôi phục danh sách/form Admin với tên, mô tả và trạng thái. Checkout chỉ liệt kê/chấp nhận record đúng type đang bật `hienthi`; không thể gửi ID tin tức hoặc phương thức đã tắt. |
| `watermark`, `watermark-news` | `libraries/router.php` đọc hai type | Đã khôi phục hai form Admin, gồm PNG, trạng thái và thiết lập vị trí/tỉ lệ watermark nâng cao. |

`chinh-sach` đã được gộp còn đúng một cấu hình đầy đủ. Form giữ nội dung CKEditor, slug và SEO; trường ảnh/copy ảnh không dùng được loại khỏi giao diện. URL và năm record chính sách không thay đổi.

## Phần chưa dùng hoặc nên xem lại

### Có thể archive sau khi xác minh, chưa được xóa

- Media type cũ `background`, `background-breadcrumb`, `banner`, `banner-gioi-thieu`, `banner-san-pham`, `banner-du-an`: hiện không còn consumer PHP public sau khi breadcrumb chuyển sang dạng gọn.
- Static `slogan`: consumer trong `sources/allpage.php` đang comment.
- `cau-truc-co-cau`: có dấu hiệu tồn tại song song ở static/news nhưng chưa thấy consumer public rõ ràng.

Quy trình bắt buộc trước cleanup: backup DB/upload → scan consumer staging → ghi mapping URL/owner → ẩn menu trước → theo dõi → mới quyết định archive. Không xóa ngay record hoặc file.

### Không phải code chết

- `excel.php`, `excelAll.php`, `word.php`, `wordAll.php` vẫn được màn đơn hàng liên kết để xuất file.
- Smoke local bổ sung cho thấy riêng luồng tạo file tổng hợp đang lỗi legacy: `excelAll.php` gặp file writer `Comments.php` rỗng, còn `wordAll.php` cố tạo file tạm trong `libraries/sample/` không có quyền ghi. Đây là lỗi generation độc lập với query filter; cần sửa và test download riêng trước deploy.
- `product_bulk.php` đang được `product.php` nạp và là luồng nhập/xuất sản phẩm mới.
- `photo2`, video và gallery vẫn có consumer public dù dữ liệu hiện tại ít hoặc bằng 0.

## Rủi ro kỹ thuật ảnh hưởng nhân viên vận hành

### P0 — Query tìm kiếm Admin đã parameterized

Scan source ban đầu phát hiện 21 vị trí ghép trực tiếp keyword vào `LIKE` trong các module product taxonomy, user, places, news, tags, language và export đơn hàng. `htmlspecialchars()` không thay thế prepared statement cho SQL.

Đã chuyển cả 21 vị trí sang helper chỉ nhận identifier cột hợp lệ và sinh `LIKE ?`; `%keyword%` được bind riêng. ID danh mục/địa giới được ép kiểu và bind. Hai export đơn hàng dùng chung bộ dựng điều kiện parameterized cho danh sách ID, trạng thái, địa giới, ngày, khoảng giá và keyword; list ID sai định dạng trả tập rỗng thay vì mở rộng phạm vi export. Regression chạy keyword `O'Reilly % Đèn` qua helper, static guard và năm màn có template/config phù hợp. Bộ lọc export được test tách biệt với lỗi generation legacy nêu trên.

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

1. **Đã hoàn tất:** parameterize 21 query tìm kiếm còn lại và thêm regression.
2. **Đã hoàn tất:** gộp cấu hình `chinh-sach`; khôi phục Admin cho phương thức thanh toán và hai watermark. `tom-tat-gioi-thieu` được xác minh là query thừa, chưa có render public nên không bật lại.
3. Thêm hàng đợi thiếu ảnh/SEO/danh mục/nội dung vào Admin; ưu tiên xử lý 363 sản phẩm thiếu ảnh.
4. Thêm SEO preview/dry-run và bulk fill **chỉ trường rỗng**; không đổi slug/URL public.

### Giai đoạn 2 — Không nhập thủ công lặp lại

1. Chuẩn hóa thư viện section: hero, điểm nổi bật, gallery ứng dụng, hướng dẫn lắp, chứng nhận, CTA.
2. Cho section kế thừa theo `danh mục → thương hiệu → sản phẩm`; product chỉ override section khác biệt.
3. Dùng ZIP/bulk media theo mã sản phẩm và báo cáo file không khớp; không bắt nhân viên mở từng sản phẩm.
4. Chuyển form sản phẩm thành các tab có completeness indicator; giữ CKEditor làm “nội dung tự do”, không dùng làm layout engine chính.

### Giai đoạn 3 — Thư viện chiến dịch thay khung per-product — lõi đã hoàn tất

Thiết kế entity campaign dùng chung gồm:

- tên nội bộ, PNG overlay/asset và mức ưu tiên;
- thời gian bắt đầu/kết thúc, trạng thái bật/tắt;
- phạm vi áp dụng: toàn site, danh mục, thương hiệu hoặc danh sách sản phẩm;
- preview ghép lớp trong Admin;
- quy tắc chỉ chọn một campaign thắng khi nhiều phạm vi trùng nhau.

Card chỉ resolve campaign đang hiệu lực; nhân viên không phải upload cùng một PNG cho hàng trăm sản phẩm. Cột `photo_khuyenmai` hiện tại được giữ làm override trong giai đoạn chuyển tiếp.

Phần còn lại nếu cần nâng thêm: validator tự động đo alpha/vùng giữa của PNG và audit log từng lần sửa. Hiện hệ thống chủ động giữ file artwork cũ khi thay ảnh, chưa có cleanup tự động.

### Giai đoạn 4 — Bảo mật và cleanup có kiểm soát

1. Chuyển password hash theo cơ chế tương thích và bật RBAC sau staging.
2. Lập audit trail cho thay đổi trạng thái/giá/nội dung quan trọng.
3. Ẩn rồi archive type/media thật sự không còn consumer; không xóa upload trước thời hạn rollback.
4. Xử lý 89 SEO orphan bằng báo cáo và backup; không chạy DELETE tự động trong deployment source.

## Test đã chạy

- PHP 7.4 lint cho source tìm kiếm, form sản phẩm, form media và breadcrumb: đạt.
- Node syntax check cho Admin JS và Playwright test: đạt.
- Admin readiness authenticated: đạt toàn bộ, gồm preview khung sản phẩm và công tắc của banner/popup.
- Product campaign regression: đạt quy tắc lịch chạy, mức ưu tiên, độ cụ thể phạm vi, override legacy và resolver DB parameterized.
- Admin campaign HTTP: danh sách/form 200; upload PNG + scope sản phẩm + CSRF + transaction đạt; card public nhận đúng campaign và fixture được dọn sạch.
- Admin readiness cho cấu hình dùng chung: form Chính sách, danh sách/form phương thức thanh toán và hai form watermark đều trả 200, có CSRF và đúng bộ field.
- Checkout atomicity: đạt, gồm kiểm tra chỉ nạp phương thức đang hiển thị và từ chối ID khác type.
- Security/Admin search regression: helper, static guard đủ 21 vị trí và năm màn danh sách với dấu nháy, `%`, Unicode đều đạt; filter export đạt ở unit nhưng generation file local còn lỗi legacy đã ghi nhận.
- Playwright Chromium tìm kiếm khung campaign: 2/2 desktop/mobile đạt.
- Playwright Chromium popup một lần mỗi tab: 2/2 desktop/mobile đạt; reload cùng tab không mở lại và tab mới vẫn mở.
- Toàn bộ Playwright: 54 đạt, 2 skip đúng theo project/thiết kế; visual baseline trang chủ, danh sách sản phẩm, dải danh mục và chi tiết JD-T300 đều đạt. Test chi tiết đã cố định slug thay vì phụ thuộc sản phẩm đầu tiên sau import.
- Full pre-deploy local: đạt; lint PHP 7.4, regression, commerce/preflight, 511 URL sitemap và mẫu 26 URL đều sạch.
- Playwright Chromium header/menu/breadcrumb/footer: 2/2 desktop/mobile đạt.
- `git diff --check`: đạt.

## Deploy và rollback

- Chưa deploy production và chưa chạy migration/SQL production.
- Chạy `migrations/022_add_product_campaigns.sql` sau `021`, trước khi mở màn Admin campaign; sau đó deploy source/assets và xóa cache ứng dụng. Migration chỉ tạo bảng, không ghi bảng sản phẩm.
- Rollback an toàn: revert commit nhưng giữ hai bảng campaign. Chỉ DROP bảng nếu đã xác nhận chưa có campaign thật cần giữ.
- Record demo khung/banner/popup chỉ thuộc DB local. Nếu không cần demo, dùng câu rollback local đã ghi trong thư mục artifact; không xóa media production theo tên file demo.
