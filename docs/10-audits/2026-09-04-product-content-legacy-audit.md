# Audit nội dung sản phẩm và tính năng legacy — 2026-09-04

Phạm vi: source và database Docker local, không ghi production.

## Kết luận triển khai

- `photo`, gallery sản phẩm, `photo2`, video và chứng nhận đều còn consumer public; giữ nguyên, không xóa.
- Gallery dự án là nguồn ảnh cho khối “Dự án thực tế”; quan hệ sản phẩm–dự án không sao chép media.
- Size, color và tags còn source legacy nhưng đang tắt trong cấu hình sản phẩm. Tiếp tục để dormant, không bật UI khi chưa có dữ liệu/use case đã duyệt.
- Thông số HTML cũ và nội dung CKEditor tiếp tục làm fallback. Ba khối nullable mới không backfill và không ghi đè dữ liệu cũ.
- Chính sách riêng của sản phẩm được coi là override có nhãn; nếu rỗng, UI dẫn tới các trang chính sách dùng chung.

## Chất lượng phân loại local

- `Đèn NLMT` có 165 sản phẩm: 21 sản phẩm chưa gán `id_cat`, 1 sản phẩm trỏ tới loại đã không còn tồn tại.
- Bốn loại hợp lệ dưới `Đèn NLMT`: Đèn Pha (74), Đèn Đường (64), Đèn UFO (3), Đèn Ốp Trần (2).
- `Đèn AC` đang hiển thị trong database nhưng có 0 sản phẩm; hai loại con dưới `Đèn Sân Vườn` cũng có 0 sản phẩm.
- Public chỉ render danh mục/loại đang hiển thị và có sản phẩm đang hiển thị. Admin gắn cảnh báo cho sản phẩm thiếu loại hoặc liên kết loại lỗi; không tự động sửa dữ liệu.

## Quyết định và rủi ro

- Không xóa bảng, field, upload hay asset legacy.
- Không tự suy luận “Đèn sân vườn”, “Đèn trụ cổng”… từ tên sản phẩm để cập nhật hàng loạt. Chủ nội dung cần tạo/chọn taxonomy chuẩn trước khi gán.
- Trước production: backup, chạy migration 010 và 011, kiểm tra orphan, rồi mới deploy source. Rollback source bằng revert; không DROP cột nullable nếu đã có dữ liệu nhập mới.
