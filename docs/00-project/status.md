# Current status

Cập nhật gần nhất: 2026-09-03. Mọi thay đổi bên dưới mới ở Git + Docker local, chưa triển khai production.

## WORKING ON LOCAL

- Trang sản phẩm có giá gốc/giá khuyến mãi/% giảm giá.
- Bộ lọc thương hiệu, công suất và mức giá.
- Giỏ hàng, Mua ngay, checkout COD/chuyển khoản và trang cảm ơn.
- Review sản phẩm, ảnh feedback và nhãn “Đã mua hàng”.
- Admin quản lý đơn hàng và admin chỉnh sửa 5 trang chính sách.
- Video YouTube, 19 trường thông số có cấu trúc, danh sách thông số riêng và nội dung chứng nhận theo từng sản phẩm.
- Admin và Excel CSV/XLS/XLSX nhập/xuất từng thông số; frontend dựng bảng từ dữ liệu cấu trúc và vẫn fallback về HTML kỹ thuật cũ.
- 4 dự án hiện có đều có ảnh và nội dung.
- Toàn bộ giá public đang ẩn theo chế độ báo giá; Admin và Excel vẫn giữ giá nội bộ. Có công tắc Admin → Thiết lập thông tin → “Hiển thị giá sản phẩm” (khóa `public_product_prices`), mặc định “Liên hệ báo giá”, lưu xong tự xóa cache.
- Canonical, Twitter metadata, robots, sitemap, HTTP security headers và chặn file nhạy cảm đã có regression local.
- Wordmark “Phúc Thịnh Solar” trong template public dùng vàng kim gradient.
- Đợt 2026-08-31: phóng to giao diện public (thang chữ/header/container/section title), cân lại form liên hệ + chi tiết dự án, rà soát responsive mobile (nút liên hệ nổi, gutter, canh chữ, footer đầy đủ), sửa lỗi khung ảnh chi tiết sản phẩm phình trên mobile, khôi phục bộ lọc Thương hiệu/Công suất/Loại SP ở listing. Có `docs/07-features/ecommerce-upgrade-proposal.md` (phân tích Kitawa, chờ chốt phạm vi).
- Có script backup database/upload local kèm checksum; restore rehearsal đạt 49 bảng và 775 file upload.
- Media fallback đã phục hồi 85/85 URL ảnh WordPress legacy; package release vẫn giữ các asset đang được tham chiếu.
- Migration 007 thêm index route; lookup local dùng index thay cho full scan nhỏ.
- Đã loại N+1 ở menu, block trang chủ, danh mục Sản phẩm và thống kê review trong danh sách Sản phẩm Admin; full pre-deploy đạt.
- Profiling authenticated bốn danh sách Admin không còn N+1 theo từng dòng; dropdown địa giới Đơn hàng bỏ query quận/phường khi chưa chọn parent.
- Ba thử nghiệm conditional loading CSS/JS đã được hoàn nguyên để giữ tương thích visual và tính năng.
- Đã cài Playwright Chromium trong `tools/visual-tests`; full suite đạt 44 test, 2 skip có chủ ý. Có 7 screenshot baseline đã review, gồm lưới danh mục trang chủ desktop/mobile; regression phủ thêm mega menu, danh mục rỗng, modal tư vấn/báo giá, AJAX chuyển nhóm sản phẩm và drawer bộ lọc mobile.
- CTA “Gửi yêu cầu báo giá” ở chi tiết sản phẩm đã nối vào modal tư vấn hiện có; form có CSRF và backend newsletter, không còn là UI không thể mở.
- Import Excel sản phẩm xử lý được thương hiệu chưa có: preview gom tên mới và bắt quản trị viên chọn tạo mới, ghép hãng hiện hữu hoặc bỏ trống; brand mới và sản phẩm được áp dụng cùng lượt, có rollback thủ công cho MyISAM.
- Giá sản phẩm trong CRUD Admin và bulk import dùng chung một validator: chặn giá khuyến mãi lớn hơn giá gốc và luôn tính lại phần trăm giảm; bản ghi local #316 đã được sửa qua form Admin từ discount 0 thành 10.
- ADM-P1-004: `table_order` và `table_order_detail` đã chuyển sang InnoDB trên Docker local bằng migration 008; `order_detail` có index theo order/product. Regression xác nhận rollback thật xóa cả order/detail và không đổi tổng dữ liệu. Production chưa chạy migration; vẫn phải backup, kiểm orphan và dừng checkout trong thời gian ALTER.
- Reader XLSX data-only đã bỏ qua theme/custom property/sharedStrings không đầy đủ và chuẩn hóa Strict OOXML sang namespace PHPExcel hỗ trợ; lỗi parser còn lại được bắt thành thông báo Admin có kiểm soát.
- A3 e-commerce hoàn tất: trang chủ hiển thị đủ 9 danh mục nổi bật thành lưới 5/3/2 cột; ưu tiên ảnh Admin và dùng icon vàng khi danh mục chưa có ảnh, không thêm query DB.
- A4 e-commerce hoàn tất: trang danh mục có nút “Bộ lọc” trên mobile/tablet, drawer trượt có backdrop, khóa cuộn, Escape/focus/ARIA và tự đóng sau khi lọc AJAX; desktop giữ sidebar hiện hữu.
- Đã sửa cú pháp `.htaccess` upload từng làm ảnh/logo trả HTTP 500; pre-deploy kiểm tra trực tiếp media để chống tái phát.
- Đã lập dependency/usage map CSS, JS, media và bốn luồng video legacy; chưa xóa/tách asset khi chưa đủ browser/staging evidence.
- ADM-P1-012: sửa liên hệ trong Admin không còn bắt buộc trường địa chỉ (đồng bộ với form liên hệ Theme V2 đã bỏ trường này); `test-admin-readiness.php` phủ thêm case lưu liên hệ khi địa chỉ trống.
- ADM-P1-013: `docker-compose.local.yml` bind-mount webroot cho service `web`, sửa code không cần build lại image; `caches`/`upload`/`thumbs` chuyển sang named volume. Xem `docs/02-development/local-setup.md`.
- ADM-P2-001: 8 truy vấn + tính toán của bảng điều khiển chuyển từ `templates/index_tpl.php` sang controller `administrator/sources/index.php` (route mặc định nạp qua `administrator/index.php`); template chỉ render. Kết quả query và mảng chart giữ nguyên (characterization + 3 assertion trong `test-admin-readiness.php`).
- Auth full-page Admin không còn xoay `user_token` ở mỗi page load; token chỉ đổi khi đăng nhập mới để giữ phát hiện concurrent login mà không tạo race giữa request cùng phiên. `lastlogin` được throttle 60 giây và update có điều kiện; HTTP integration xác nhận hai page load liên tiếp không ghi lại auth state.
- ADM-P1-014: status API cập nhật set-based cho tối đa 500 dòng được chọn khi client gửi trạng thái đích 0/1; vẫn kiểm tra đủ ID tồn tại, RBAC/CSRF/table/attribute allowlist. Toggle legacy không có giá trị đích giữ hành vi tuần tự để tương thích.
- ADM-P1-008 (batch 1): danh sách Sản phẩm có menu “Điền dữ liệu thiếu”, lọc allowlist theo ảnh đại diện/mô tả/nội dung/thông số/công suất, hiển thị badge thiếu và liên kết thẳng tới form sửa. Bulk status dùng chung điều kiện filter để không cập nhật lệch phạm vi.
- ADM-P1-005/P1-008 (batch 2): từ danh sách đang lọc dữ liệu thiếu có thể mở màn bulk và xuất Excel/CSV đúng tập con; workbook vẫn có snapshot/conflict detection và cột ảnh chỉ đọc. Scope tùy ý bị bỏ qua, chỉ năm trạng thái thiếu dữ liệu trong allowlist được áp dụng.
- UX export luôn giữ nút “Xuất tất cả sản phẩm”; khi vào từ filter có nút riêng “Xuất nhóm đang lọc”. Dropdown nhóm thiếu không còn bị card cắt và có cuộn khi viewport thấp.
- ADM-P1-005 (batch 3): màn nhập/xuất hàng loạt nhận ZIP ảnh đặt tên theo mã sản phẩm, bắt buộc xem trước và xác nhận trước khi ghi. Chỉ bổ sung ảnh đại diện đang thiếu; không thay/xóa ảnh hiện hữu. ZIP bị giới hạn 20 MB/200 ảnh, từng ảnh 5 MB, kiểm tra extension + MIME + kích thước thực, chặn thư mục/path traversal và tổng giải nén quá 100 MB; commit kiểm tra lại mã, ảnh trống và mốc cập nhật, có rollback DB/file theo lượt.
- ADM-P1-005 (batch 4): ZIP ảnh hỗ trợ tên `MÃ-01` cho ảnh đại diện và `MÃ-02...` cho gallery; tên `MÃ.jpg` cũ vẫn tương thích và được lưu thành `MÃ-01`. Preview tách số ảnh chính/phụ, không ghi đè ảnh chính hoặc nhân đôi gallery cùng tên; commit kiểm tra lại dữ liệu, giới hạn file sau resize và rollback cả bản ghi gallery/file nếu lượt lỗi.
- ADM-P1-001: `PDODb` không còn render HTML rồi `die()` tại 15 nhánh lỗi connect/prepare/query/query-builder. Data layer ném `PDODbException` có message công khai trung tính; controller có thể catch/rollback, chẩn đoán chỉ giữ query template không ghép tham số. Regression xác nhận kết nối dùng lại được sau catch + rollback và toàn bộ pre-deploy giữ hành vi thành công như cũ.
- Quản trị yêu cầu tư vấn/đăng ký đại lý có pipeline theo cấu hình: ưu tiên yêu cầu mới, bộ đếm và lọc theo trạng thái, tìm theo thông tin khách, ghi chú nội bộ và trạng thái `Mới → Đã tiếp nhận/Đang xử lý → Hoàn tất`. Controller chỉ nhận field nghiệp vụ và từ chối trạng thái ngoài allowlist; integration test tạo fixture tạm, kiểm tra danh sách/lưu/từ chối rồi dọn sạch.
- Router public đã thay chuỗi lookup `$requick` tuần tự bằng một resolver `UNION ALL` parameterized, chỉ nhận table/field từ allowlist và giữ thứ tự ưu tiên route cũ. Test đối chứng đạt 503/503 slug và xác nhận mỗi slug chỉ gọi resolver DB một lần; Playwright chức năng đạt 37 PASS, 1 skip. Screenshot danh mục hiện lệch baseline vì DB local đang hiển thị nhóm sản phẩm thiếu ảnh, nên chưa cập nhật ảnh chuẩn.
- Chuẩn hóa Admin API batch 1: `login`, `status`, `upload` trả thêm envelope `ok/message/code/data` nhưng giữ nguyên field legacy cho JavaScript hiện tại. Security và authenticated Admin integration test đạt; endpoint HTML/scalar và database array chưa đổi.
- Admin API batch 2: lỗi CSRF/quyền JSON và `database.php` dùng cùng envelope; JavaScript bảo trì database hỗ trợ song song định dạng cũ/mới. Endpoint HTML fragment và scalar vẫn giữ nguyên để tránh phá consumer legacy.
- Admin API batch 3: cập nhật `numb` trả JSON rõ ràng và kiểm tra kết quả DB; input Admin tự phục hồi giá trị cũ khi request lỗi. Integration test xác nhận mutation đúng và fixture được dọn sạch.
- Admin API batch 4: sao chép product/news bỏ đệ quy slug không giới hạn, trả JSON và chỉ reload khi thành công. Sửa lấy sai ID từ boolean `insert()`, chuẩn hóa `cong_suat`/giá cho MariaDB strict mode và bắt lỗi DB thành response trung tính; integration test tạo bản nháp slug mới rồi cleanup về 0 fixture.
- Admin API batch 5: kiểm tra slug trả envelope JSON, xác thực định dạng/độ dài và ép kiểu ID. Điều kiện loại trừ bản ghi trong `Functions::checkSlug()` dùng parameterized query; integration test bao phủ slug trùng, slug của chính bản ghi, slug mới, ID bất thường và input sai định dạng.
- Admin API batch 6: ba endpoint kéo-thả menu/index/detail dùng chung validation ID, allowlist bảng, giới hạn 500 dòng và transaction; trả JSON envelope thay vì response rỗng. Client báo lỗi request, mapping RBAC `sortable_update` được đồng bộ với menu và integration test bao phủ cả ba bảng cùng trường hợp ID trùng.
- Admin API batch 7: sửa thao tác xóa sortable từng dùng sai selector nên có thể xóa nhầm bảng menu khi thao tác index/detail. Endpoint ánh xạ `menu/index/detail` qua allowlist, xác minh kết quả xóa và trả JSON; client chỉ gỡ dòng sau khi server xác nhận. Integration test bao phủ đúng cả ba bảng và trường hợp nhóm giả mạo.
- Admin API batch 8: mutation bình luận kiểm tra RBAC theo type lấy từ bản ghi gốc, trạng thái chỉ nhận `hienthi` và phản hồi Admin chỉ ghi các cột allowlist. Xóa bình luận xác minh lại DB, response giữ field legacy đồng thời thêm envelope; route/phương thức sai trả lỗi JSON rõ ràng. Integration test bao phủ duyệt, từ chối trạng thái tự chế, chống giả mạo phạm vi phản hồi và xóa cả luồng cha/con.
- Admin workflow batch 9: luồng lưu cấu hình sortable bỏ `var_dump($_POST)`, kiểm tra quyền `sortable_update`, allowlist field setting/options và validate bốn mảng đồng bộ cho từng nhóm menu/index/detail (tối đa 200 dòng). Ghi item, setting và SEO trong cùng transaction; ID hiện hữu phải thuộc đúng bảng. Integration test xác nhận payload thiếu bị từ chối, không lộ dữ liệu POST và không ghi dở setting.
- Admin API batch 10: xóa một/nhiều ảnh gallery trả envelope JSON và xác minh bản ghi đã xóa trước khi báo thành công. Xóa hàng loạt kiểm tra toàn bộ ID thuộc đúng album trước khi ghi để tránh xóa dở; JavaScript chỉ gỡ ảnh khỏi giao diện sau phản hồi thành công và hiện lỗi server khi thất bại. Integration test bao phủ xóa đơn, từ chối danh sách lẫn ID ngoài album và xóa batch hợp lệ.
- Admin API batch 11: kéo-thả gallery đổi từ cập nhật tuần tự từng ảnh sang một câu `UPDATE ... CASE` parameterized, giới hạn 200 ID và bắt buộc danh sách khớp toàn bộ album. API xác minh kết quả, trả envelope JSON nhưng giữ `id`/`numb` legacy; client báo lỗi và tải lại thứ tự server khi request thất bại. Integration test bao phủ reorder thành công và từ chối ID trùng không ghi dở.
- Admin API batch 12: sửa tiêu đề/số thứ tự từng ảnh gallery chỉ nhận field allowlist, số thứ tự 1–9 chữ số, xác minh DB rồi trả HTML mới trong envelope JSON. Client chỉ render lại album sau thành công và phục hồi từ server khi lỗi. Renderer API đóng riêng output buffer legacy phát sinh để HTML không làm hỏng JSON; integration test bao phủ cập nhật tiêu đề và từ chối số sai định dạng.
- Admin API batch 13: bootstrap AJAX không còn `die()` rỗng khi thiếu/hết phiên; mọi endpoint dùng chung trả HTTP 401 với code `AUTH_REQUIRED`. JavaScript có handler toàn cục báo hết phiên và chuyển về trang đăng nhập một lần, tránh người dùng tưởng mutation im lặng thành công. Integration test xác nhận contract trên cả API JSON và endpoint HTML fragment.
- Admin API batch 14: dropdown danh mục/địa giới giữ HTML fragment khi thành công nhưng từ chối method, bảng, type và level sai bằng HTTP 400/405 + JSON envelope. Client giữ dropdown hiện tại và báo lỗi thay vì chèn chuỗi lỗi vào form. Integration test bao phủ parent rỗng tương thích, level/table ngoài allowlist và method ngoài POST.
- Admin API batch 15: `filer.php` chuẩn hóa nốt method/command/module/scope/layout/hash/context sai thành HTTP 400/405 + JSON envelope; mutation gallery trả lỗi quyền dạng JSON. Refresh hợp lệ vẫn trả HTML và đóng đúng output buffer, client báo lỗi mà không dựng lại album giả. Integration test bao phủ refresh đủ fixture và ba nhóm request sai trước đây từng trả body rỗng.
- Admin API batch 16: bootstrap AJAX cài exception boundary chung; lỗi DB/runtime chưa được endpoint bắt riêng sẽ rollback transaction đang mở, ghi chi tiết vào error log và trả HTTP 500 với envelope `INTERNAL_ERROR` trung tính, không lộ SQL/stack cho trình duyệt.
- Admin API batch 17: hai endpoint legacy `component.php` và `save_add_item.php` tiếp tục bị vô hiệu hóa HTTP 404 nhưng nay trả contract JSON `LEGACY_API_DISABLED`; không khôi phục file manager hoặc đường ghi sortable cũ.
- Admin API batch 18: integration test dùng assertion schema chung để khóa đủ `ok/message/code/data` và kiểu dữ liệu cho lỗi hết phiên, endpoint fragment/legacy, validation, CSRF, gallery và sortable; tài liệu API được đồng bộ với trạng thái code hiện tại.
- Checkout idempotency local: form phát token ngẫu nhiên 256-bit, migration 009 thêm unique index trên đơn hàng; request replay trả lại đúng đơn đã commit, không tạo detail hoặc gửi email lần hai. Token được kiểm tra cùng session và chỉ xoay sau khi đã tạo trạng thái trang cảm ơn; regression hai tiến trình đồng thời cùng nhận một order ID.

## PARTIAL / CHỜ DỮ LIỆU THẬT

- 5 trang chính sách đã có URL nhưng nội dung đang rỗng.
- DB local hiện có 445 sản phẩm, được chủ project xác nhận ngày 2026-09-03 là dữ liệu nhập có chủ ý. Không hoàn nguyên, tự ẩn hoặc đổi thứ tự lô này.
- Ảnh, mô tả ngắn, nội dung chi tiết và thông số của bộ 445 sản phẩm đang được chủ project kiểm tra/bổ sung dần. Baseline đọc ngày 2026-09-03: 364 thiếu ảnh đại diện, 442 thiếu mô tả ngắn và 366 thiếu nội dung chi tiết; 0 nhóm trùng mã và 0 nhóm trùng slug.
- Lịch sử gồm lô ORIANA `san-pham-20260831-da-bo-sung-oriana.xlsx` và file master `san-pham-master-fixed-2026-09-01.xlsx`; các file nằm trong `artifacts/`, tiếp tục được giữ ngoài Git/release.
- Chứng nhận và video chỉ hiển thị khi admin nhập dữ liệu thật.
- Dữ liệu kỹ thuật thật sẽ được cập nhật từ file Excel bên ngoài; không chạy migration 006 apply nếu chưa có phê duyệt riêng.
- Nội dung mô tả chi tiết từng sản phẩm cần biên tập theo tài liệu kỹ thuật thật.
- SMTP production và extension `calendar` trên hosting chưa được xác minh.

## SECURITY

- Các finding code chính của Security Review ngày 2026-08-27 đã được xử lý và regression đạt trên local.
- RBAC vẫn tắt; cần test tài khoản nghiệp vụ, HTTPS, cookie Secure, proxy, SMTP, reCAPTCHA và upload video trên staging.
- Không coi nhánh hiện tại là sẵn sàng production cho tới khi cổng staging và backup hosting đạt.

## STILL MISSING

- Nội dung chính sách được doanh nghiệp phê duyệt.
- Nội dung/ảnh/video/chứng nhận thật cho sản phẩm.
- CI/CD, E2E nghiệp vụ đầy đủ, staging và quy trình deploy/rollback production đã diễn tập.
