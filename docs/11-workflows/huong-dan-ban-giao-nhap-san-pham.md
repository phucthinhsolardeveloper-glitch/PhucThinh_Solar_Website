# Hướng dẫn bàn giao nhập dữ liệu và ảnh sản phẩm

Tài liệu dành cho người được giao cập nhật sản phẩm tại **Admin → Sản phẩm → Nhập / xuất hàng loạt**. Luôn thao tác trên file xuất mới nhất và dùng bước xem trước; không import thẳng database.

## 1. Chọn đúng công cụ

| Nhu cầu | Công cụ |
|---|---|
| Tên, mã, danh mục, thương hiệu, mô tả, nội dung, thông số, công suất, giá hoặc trạng thái | Excel `.xlsx` |
| Bổ sung ảnh đại diện và gallery cho sản phẩm đã có mã | ZIP ảnh |
| Sửa riêng một sản phẩm hoặc thay ảnh hiện hữu có chủ ý | Form sửa sản phẩm |

Không chèn ảnh vào Excel. File Excel chỉ chứa dữ liệu; ZIP chỉ chứa file ảnh.

## 2. Cập nhật bằng Excel

1. Nhấn **Xuất tất cả sản phẩm**, hoặc chọn **Xuất theo dữ liệu thiếu** để nhận đúng nhóm cần làm.
2. Làm việc trong sheet `SanPham`. Không đổi tên/xóa cột, không sửa ID và không sửa/xóa sheet ẩn `_HeThong`.
3. Với sản phẩm đang có: giữ nguyên ID hoặc mã để hệ thống nhận đúng bản ghi. Với sản phẩm mới: để ID trống, nhưng mã và tên bắt buộc phải có.
4. Ô trống khi cập nhật có nghĩa là giữ dữ liệu website. Chỉ nhập `__CLEAR__` khi được yêu cầu xóa giá trị cũ.
5. Không tự ý đổi trạng thái hiển thị. Trạng thái hàng loạt có bước xác nhận riêng vì có thể làm sản phẩm biến mất khỏi website.
6. Upload tại **Tải file lên kiểm tra**. Đây mới là bước xem trước, chưa ghi dữ liệu.
7. Đọc số lượng tạo mới/cập nhật/tạm ẩn, lỗi, cảnh báo, URL trùng, thương hiệu mới và xung đột từng ô.
8. Chỉ xác nhận áp dụng khi kết quả đúng với phiếu bàn giao. Sau đó xuất lại file và kiểm tra ngẫu nhiên trên Admin lẫn trang public.

## 3. Bổ sung ảnh bằng ZIP

Tên ảnh phải bắt đầu bằng mã sản phẩm chính xác:

- `JD-T200-01.jpg`: ảnh đại diện.
- `JD-T200-02.jpg`, `JD-T200-03.jpg`...: ảnh gallery theo thứ tự.
- `JD-T200.jpg`: quy tắc cũ, vẫn được nhận là ảnh đại diện.

Quy trình:

1. Chỉ dùng JPG, PNG hoặc WEBP; mỗi ảnh tối đa 5 MB.
2. Đặt ảnh ngay ở thư mục gốc của ZIP, không có thư mục con.
3. Một ZIP tối đa 20 MB và 200 ảnh. Với khối lượng lớn, chia theo hãng/danh mục hoặc từng lô nhỏ.
4. Chọn ZIP rồi nhấn **Kiểm tra ZIP trước**.
5. Đối chiếu các nhóm: ảnh chính sẵn sàng, ảnh phụ sẵn sàng, ảnh đã có, không thấy mã và lỗi.
6. Sửa hết lỗi trước khi xác nhận. Hệ thống không thay hoặc xóa ảnh hiện hữu; muốn thay ảnh phải dùng form sửa sản phẩm và có phê duyệt.
7. Sau mỗi lô, mở một số sản phẩm để kiểm tra ảnh đại diện, thumbnail/gallery, thứ tự và hiển thị mobile.

## 4. Checklist người thực hiện

- Có file gốc và ghi ngày/người phụ trách cho từng lô.
- Dùng file Excel vừa xuất, không dùng lại bản cũ sau khi người khác đã sửa dữ liệu.
- Không chia sẻ tài khoản Admin; mỗi người dùng tài khoản được cấp quyền phù hợp.
- Luôn chụp hoặc lưu kết quả xem trước trước khi xác nhận.
- Không bỏ qua lỗi, cảnh báo trạng thái, URL trùng hoặc thương hiệu mới.
- Sau khi áp dụng: xuất đối chiếu, kiểm tra Admin và kiểm tra ít nhất một trang public trên desktop/mobile.
- Nếu số lượng hoặc kết quả bất thường: dừng, không bấm xác nhận và báo người quản trị.

## 5. Phục hồi khi thao tác sai

Không import tiếp để “sửa chồng”. Ghi lại file, thời gian thao tác và kết quả xem trước/commit, sau đó báo quản trị viên để đối chiếu backup và lịch sử. ZIP không ghi đè ảnh cũ; dữ liệu Excel cần phục hồi từ backup hoặc file xuất trước lượt cập nhật.
