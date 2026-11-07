# Bộ script kiểm tra giao diện

Chạy từ `tools/visual-tests`. Mọi script chỉ đọc, không gửi form hay đơn hàng. **Không xóa script trong thư mục này**: lần sau dùng lại và nâng cấp tiếp thay vì viết mới.

## Dùng thường xuyên

| Script | Việc | Lệnh |
|---|---|---|
| `site-audit.cjs` | Quét cả site: tràn ngang, chữ bị cắt, ảnh hỏng, lỗi 5xx, lỗi JS, menu con bị che | `node audit/site-audit.cjs` |
| `text-align-audit.cjs` | Đếm đoạn dài đã căn đều và độ rộng khe chữ lớn nhất | `node audit/text-align-audit.cjs` |
| `check-viewers.cjs` | Mở khung chứng nhận, khung ảnh, menu màn hình thấp, bảng tìm kiếm rồi chụp ảnh | `node audit/check-viewers.cjs` |
| `archive/wrap.cjs` | Đếm từ đôi bị ngắt và từ lẻ cuối đoạn | `node audit/archive/wrap.cjs` |
| `archive/w.cjs` | Vị trí thanh nút giữa các trang cùng nhóm | `node audit/archive/w.cjs /tmp/out` |
| `archive/spy2.cjs` | Thanh mục có đúng thứ tự khi lướt nhanh không | `node audit/archive/spy2.cjs` |
| `archive/tr2.cjs` | Hàng nút có chạy 2 lần hoặc cuộn ngược không | `node audit/archive/tr2.cjs` |
| `archive/f.cjs` | Thời gian tải sẵn và khựng khi lướt tới khối 3D | `node audit/archive/f.cjs /tmp/out` |
| `archive/cap.cjs` | Chụp lại ảnh tĩnh 3D, chạy lại khi đổi mô hình hoặc camera | `node audit/archive/cap.cjs` |
| `archive/parity.cjs` | So chữ hiển thị giữa desktop và điện thoại | `node audit/archive/parity.cjs /tmp/out` |
| `scan-dark-overlays.py` | Tìm lớp phủ tối còn sót trong mã nguồn | `python3 audit/scan-dark-overlays.py` |

Đặt `AUDIT_BASE=https://phucthinhsolar.com` để chạy `site-audit.cjs` trên site thật. Các script `archive/` mặc định dùng `http://localhost:3100`.

Trên máy này cần `PLAYWRIGHT_BROWSERS_PATH=$PWD/.browsers` trước lệnh `node`. Script có 3D cần card đồ họa hoặc SwiftShader nên số đo chỉ để so sánh tương đối.

## `archive/`

Toàn bộ script đo đã viết trong đợt nâng cấp tháng 11/2026, giữ nguyên để tham khảo cách đo. Tên ngắn vì viết nhanh; khi một script được dùng lại nhiều, hãy đổi tên rõ nghĩa và đưa lên bảng trên.

## Thêm kiểm tra mới

Lỗi nào quét tự động được thì thêm vào `site-audit.cjs`. Lỗi cần giữ lâu dài thì thêm vào `nuxt-tests/`.

## Script theo vấn đề (đợt 2026-11-07)

Chạy từ `tools/visual-tests` với `PLAYWRIGHT_BROWSERS_PATH=$PWD/.browsers`. Mọi script chặn widget dịch `cdn.gtranslate.net`, vì widget đó tự đổi trang sang tiếng Anh trong trình duyệt tự động.

| Script | Kiểm tra gì |
|---|---|
| `stage-story.cjs [thư mục]` | Mô hình 3D trang chủ: từng cảnh, tự xoay, quay lại, trên máy tính và điện thoại (`AUDIT_ONLY=phone`). Chậm vì dùng đồ họa giả lập |
| `sort-toolbar.cjs [route]` | Thanh Bộ lọc/Sắp xếp từ 412 đến 1280px: ô bị cắt, tràn ngang |
| `form-fit.cjs [thư mục]` | Form tư vấn và đại lý có vừa một màn không, còn chữ "không bắt buộc" không. Không gửi form |
| `gold-selected.cjs [thư mục]` | Bấm một lựa chọn trong từng form, báo màu ô đang chọn. Không gửi form |
| `section-rail-clicks.cjs` | Bấm chuột thật từng mục và nhảy cóc trên thanh mục: có tới nơi, ô sáng có đúng không. Khoảng 5 phút |
| `chip-rails.cjs [thư mục]` | Màu viên trượt và chữ ô đang chọn trên 6 loại trang |
| `phone-rail-overlap.cjs [thư mục]` | Trên điện thoại: ô đang chọn có bị che, viên trượt có khớp và nằm trong thanh |
| `about-history.cjs [thư mục]` | Lịch sử hình thành khi cuộn ở 3 cỡ màn, và thanh tìm kiếm có dính trên điện thoại |
| `header-menu.cjs [thư mục]` | Header và menu danh mục từ 1024 đến 2560px: tràn, cuộn, menu con ra ngoài màn |
| `button-hover.cjs` | Rê chuột qua mọi nút vàng trên 9 trang: nút nào xê dịch hoặc bật `filter` thì sẽ giật |
| `page-speed.cjs` | Tác vụ dài khi tải trang, mở menu, cuộn, với CPU chậm 4 lần |
| `word-scan.cjs "từ" ...` | Tìm chữ trên tối đa 80 trang, kể cả chữ ẩn trong dữ liệu trang, dùng khi gỡ một dữ kiện cũ |
- `catalog-brands-filter.cjs [thư mục]`: đếm logo thương hiệu ở đầu trang Sản phẩm và trang danh mục, rồi chọn 2 thương hiệu trong bộ lọc trên điện thoại để kiểm tra bộ lọc không tự đóng.
