# Checklist công việc tiếp theo — 2026-08-29

Phạm vi hiện tại là Git và Docker local. Chưa được phép triển khai hoặc ghi dữ liệu production.

## A. Chốt batch thông số và Excel hiện tại

- [x] Mở rộng 19 trường thông số chuẩn và `additional_specs`.
- [x] Đồng bộ form Admin, CSV/XLS/XLSX và bảng thông số frontend.
- [x] Giữ fallback HTML kỹ thuật cũ; migration không xóa dữ liệu legacy.
- [x] Build Docker sạch và chạy test round-trip, conflict, `__CLEAR__`, rollback, XLS legacy và cleanup dữ liệu test.
- [x] Smoke frontend sau build sạch; không có PHP warning/fatal trong lượt test.
- [x] Rà diff cuối và tạo commit riêng cho batch thông số/Excel.
- [x] Chốt không chạy migration 006 `--apply`; dữ liệu chuẩn sẽ được nhận từ file Excel bên ngoài.

Quy ước giá giữ như hệ thống cũ: ô giá trống khi cập nhật thì giữ nguyên; cả giá gốc và giá khuyến mãi bằng `0` thì frontend hiển thị “Liên hệ”. Không thêm cột chế độ giá mới.

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
- [ ] Xử lý phần Security Review còn lại: CSRF cho xóa đơn/checkout/review; allowlist và authorization API mutation Admin; hardening upload MIME/size/filename/non-executable storage.
- [ ] Retest toàn bộ Security Review và cập nhật finding đã đóng/còn mở.
- [ ] Xác minh SMTP production bằng mailbox kiểm thử và cấu hình reCAPTCHA thật.
- [ ] Xác minh PHP extension, đặc biệt `calendar`, trên hosting.
- [ ] Xác nhận menu/quy trình xử lý đơn hàng cho nhân viên vận hành.
- [ ] Backup code, DB và upload; ghi owner, thời điểm, checksum và vị trí lưu ngoài webroot.
- [ ] Diễn tập restore trên staging và xác nhận rollback migration/code.
- [ ] Build staging sạch và smoke: category → product → cart → checkout → confirmation → admin order.
- [ ] Smoke contact/newsletter/review bằng dữ liệu test có kiểm soát và dọn sạch sau test.
- [ ] Kiểm tra desktop/mobile, menu, filter, console và log PHP/Apache.
- [ ] Chỉ deploy production khi chủ project phê duyệt rõ ràng.

## D. Sau khi dữ liệu và production checklist đã chốt

- [ ] Tinh chỉnh Theme V2 bằng đối chiếu trực quan desktop/mobile.
- [ ] Hoàn thiện các section/trang nội dung legacy còn lại nếu vẫn nằm trong phạm vi kinh doanh.
- [ ] Thêm smoke/E2E tự động và CI tối thiểu.
- [ ] Thiết kế audit trail thay đổi trạng thái đơn hàng.
- [ ] Thiết kế/enforce RBAC trước khi mở thêm tài khoản Admin.
- [ ] Lập mapping usage trước khi archive route, media hoặc asset legacy.
- [ ] Lập kế hoạch nâng PHP, dependency và database sau khi có test baseline.

## Rollback batch thông số/Excel

- Code: revert commit riêng của batch hiện tại và build lại image trước đó.
- Schema: chỉ rollback sau khi đã export/backup các cột mới; không drop cột nếu đã có import dữ liệu thật.
- Dữ liệu: khôi phục từ backup hoặc file export trước import; revert code không tự hoàn tác dữ liệu đã nhập.
- HTML `thongsokythuatvi/en` vẫn được giữ để đối chiếu và phục hồi hiển thị legacy.
