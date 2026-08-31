# Checklist công việc tiếp theo — 2026-08-29

Phạm vi hiện tại là Git và Docker local. Chưa được phép triển khai hoặc ghi dữ liệu production.

Checklist triển khai theo thứ tự ưu tiên, điều kiện Go/No-Go và rollback được chốt tại [Báo cáo sẵn sàng đưa website lên hosting](../06-operations/hosting-readiness-2026-08-29.md). Tất cả mục P0 trong tài liệu đó phải đạt trước khi mở production.

## A. Chốt batch thông số và Excel hiện tại

- [x] Mở rộng 19 trường thông số chuẩn và `additional_specs`.
- [x] Đồng bộ form Admin, CSV/XLS/XLSX và bảng thông số frontend.
- [x] Giữ fallback HTML kỹ thuật cũ; migration không xóa dữ liệu legacy.
- [x] Build Docker sạch và chạy test round-trip, conflict, `__CLEAR__`, rollback, XLS legacy và cleanup dữ liệu test.
- [x] Smoke frontend sau build sạch; không có PHP warning/fatal trong lượt test.
- [x] Rà diff cuối và tạo commit riêng cho batch thông số/Excel.
- [x] Chốt không chạy migration 006 `--apply`; dữ liệu chuẩn sẽ được nhận từ file Excel bên ngoài.

Quy ước dữ liệu Excel vẫn giữ nguyên: ô giá trống khi cập nhật thì giữ giá nội bộ cũ. Frontend hiện bật chế độ báo giá toàn website nên không công khai giá dù DB có giá; không thêm cột chế độ giá vào Excel.

## B. Khi nhận file Excel dữ liệu thật

- [ ] Backup database local/staging trước khi import.
- [ ] Dùng file xuất toàn bộ mới nhất; không sửa ID, tên cột hoặc sheet ẩn `_HeThong`.
- [ ] Lưu một bản file gốc nhận được và checksum để đối chiếu.
- [ ] Tải file lên và chỉ xem preview lần đầu; chưa xác nhận ghi.
- [ ] Rà số dòng tạo/sửa/ẩn/không đổi, lỗi, cảnh báo và xung đột.
- [ ] Đối chiếu mẫu các sản phẩm quan trọng: mã, tên, danh mục, giá, trạng thái và thông số.
- [ ] Sửa lỗi trong Excel rồi tải lại; chỉ chọn “Dùng Excel” cho xung đột đã xác minh.
- [ ] Nhập `CAP_NHAT` và áp dụng trên local/staging sau khi preview sạch.
- [ ] Kiểm tra DB sau import và xác nhận không có mã trùng/dòng test.
- [ ] Smoke category → product; kiểm tra sản phẩm có giá, “Liên hệ”, thông số chuẩn và fallback legacy.
- [ ] Lưu báo cáo import: file nguồn, thời điểm, số dòng và kết quả.

## C. Bắt buộc trước production/chạy quảng cáo

- [ ] Chốt và duyệt nội dung thật cho 5 trang chính sách.
- [ ] Duyệt video, mô tả, ảnh và chứng nhận của nhóm sản phẩm chạy quảng cáo.
- [x] Ẩn toàn bộ giá public; gỡ filter/sort giá và Offer schema, đổi CTA sang báo giá, chặn cart/checkout khi chưa niêm yết.
- [x] Thêm CSRF cho xóa/lưu đơn, checkout và review; xóa đơn chuyển sang POST.
- [x] Thêm allowlist identifier cho API Admin chính và hardening upload MIME/size/non-executable storage.
- [x] Chuyển action xóa Admin legacy sang POST + CSRF; khóa filer theo đúng context và bỏ đường dẫn upload do client cung cấp.
- [x] Ánh xạ quyền action cho API status/numb/copy/upload/filer; mặc định từ chối mapping không xác định khi RBAC bật.
- [x] Tạo ma trận và tài khoản tạm local cho nhóm sản phẩm, đơn hàng, nội dung, chỉ xem; test quyền đúng phạm vi và từ chối chéo module, sau đó dọn sạch.
- [ ] Lặp lại ma trận bằng tài khoản staging trước khi bật `permission.active`; RBAC hiện vẫn tắt.
- [x] Bổ sung CSRF cho 42 form POST, controller save/upload/sync/create, comment, sortable và bảo trì database.
- [x] Chuyển tạo file ngôn ngữ và đẩy OneSignal từ GET sang POST; chặn file manager/API legacy không còn usage.
- [x] Bổ sung rate limit login bền vững theo IP/hash tài khoản, trusted-proxy allowlist và thông báo lỗi không phân biệt tài khoản.
- [x] Bổ sung rate limit DB cho checkout, review, contact và newsletter; tách bucket, không lưu nội dung khách gửi.
- [x] Harden session Admin: strict cookie, `HttpOnly`, `SameSite=Lax`, session ID regeneration và timeout cấu hình.
- [x] Retest tự động đủ 42/42 form POST Admin có CSRF và luồng HTTP thiếu/hợp lệ.
- [ ] Retest trình duyệt trên staging các thao tác nghiệp vụ của 42 form; local mới xác minh cấu trúc và luồng đại diện.
- [ ] Retest toàn bộ Security Review và cập nhật finding đã đóng/còn mở.
- [ ] Xác minh SMTP production bằng mailbox kiểm thử và cấu hình reCAPTCHA thật.
- [ ] Xác minh PHP extension, đặc biệt `calendar`, trên hosting.
- [ ] Xác nhận menu/quy trình xử lý đơn hàng cho nhân viên vận hành.
- [ ] Backup code, DB và upload; ghi owner, thời điểm, checksum và vị trí lưu ngoài webroot.
- [ ] Diễn tập restore trên staging và xác nhận rollback migration/code.
- [ ] Build staging sạch và smoke: category → product → cart → checkout → confirmation → admin order.
- [ ] Smoke contact/newsletter/review bằng dữ liệu test có kiểm soát và dọn sạch sau test.
- [ ] Kiểm tra desktop/mobile, menu, filter, console và log PHP/Apache.
- [x] Kiểm tra SEO kỹ thuật local: canonical bỏ query, Twitter metadata, noindex trang nội bộ, robots và sitemap; đã thêm regression pre-deploy.
- [ ] Chỉ deploy production khi chủ project phê duyệt rõ ràng.

## D. Sau khi dữ liệu và production checklist đã chốt

- [ ] Tinh chỉnh Theme V2 bằng đối chiếu trực quan desktop/mobile.
- [ ] Hoàn thiện các section/trang nội dung legacy còn lại nếu vẫn nằm trong phạm vi kinh doanh.
- [x] Thêm lệnh pre-deploy local tự động: build, lint, regression, frontend smoke, log và cleanup DB.
- [ ] Đưa lệnh pre-deploy vào CI/staging runner khi có môi trường triển khai.
- [ ] Thiết kế audit trail thay đổi trạng thái đơn hàng.
- [ ] Thiết kế/enforce RBAC trước khi mở thêm tài khoản Admin.
- [ ] Lập mapping usage trước khi archive route, media hoặc asset legacy.
- [ ] Lập kế hoạch nâng PHP, dependency và database sau khi có test baseline.

## Rollback batch thông số/Excel

- Code: revert commit riêng của batch hiện tại và build lại image trước đó.
- Schema: chỉ rollback sau khi đã export/backup các cột mới; không drop cột nếu đã có import dữ liệu thật.
- Dữ liệu: khôi phục từ backup hoặc file export trước import; revert code không tự hoàn tác dữ liệu đã nhập.
- HTML `thongsokythuatvi/en` vẫn được giữ để đối chiếu và phục hồi hiển thị legacy.
