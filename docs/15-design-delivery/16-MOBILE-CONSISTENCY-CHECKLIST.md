# Checklist đồng bộ giao diện mobile

Áp dụng cho màn hình đến 820px. Desktop không đổi. Nguồn chung nằm trong `frontend/app/assets/css/main.css`, mục "Phone shape and material system".

Đo lại bằng: `node tools/visual-tests/mobile-consistency-audit.cjs` (cần preview ở cổng 3100).

## Bo góc: ba mức, lấy thanh header làm chuẩn

| Mức | Dùng cho |
|---|---|
| 22px | Header, popup, thẻ thông báo, ô tìm kiếm thả xuống |
| 16px | Nút, chip, ô nhập, thẻ, ảnh |
| 10px | Nút nhỏ (cao dưới 40px) |
| 8px | Nhãn nhỏ nằm trong thẻ (500W, chuyên mục tin) |
| 6px | Ô tick |
| 0 | Sidebar và bộ lọc sát mép màn hình |

Ngoại lệ giữ tròn: thanh dock, Zalo, nút icon tròn có sẵn.

- [ ] Không có nút dạng viên thuốc ngoài dock
- [ ] Không có góc tự đặt trong component: dùng `rounded-chip|btn|sm|md` hoặc `var(--radius-*)`
- [ ] Phần tử con không bo lớn hơn phần tử chứa nó

## Kính: ba chất liệu

| Chất liệu | Token | Dùng cho |
|---|---|---|
| Kính navy | `--m-glass-*` | Dock, sidebar, bộ lọc, tìm kiếm, thông báo giỏ hàng, thanh mua hàng |
| Kính sáng | `--m-glass-light-*` | Header, nút trên nền sáng hoặc ảnh, thanh mục lục |
| Thấu kính | `--m-glass-lens*` | Nút nằm trên nền kính navy |

- [ ] Không tự viết `backdrop-filter` hay nền kính mới trong component
- [ ] Khi máy tắt hiệu ứng trong suốt (Windows: Transparency effects), mọi lớp kính vẫn cùng một tông, chỉ là nền đặc
- [ ] Nội dung và thẻ sản phẩm giữ nền đặc, kính chỉ dành cho lớp nổi

## Chữ và nút

- [ ] H1 28 đến 31px (hero trang chủ 34, tên sản phẩm 22)
- [ ] H2 của mục 26px, trong khung hoặc form 20px
- [ ] H3 và tiêu đề thẻ 17px (thẻ sản phẩm 14, thẻ tin 15)
- [ ] Chỉ nút kết thúc form hoặc kết thúc trang mới cao 52px. Nút trong bố cục giữ kích thước của bố cục đó
- [ ] Nút nhỏ vẫn nhỏ: mở rộng vùng bấm bằng lớp vô hình, không phóng to phần nhìn thấy
- [ ] Icon tròn giữ tròn, không ép theo bo góc chung

## Hành trình mua hàng

- [ ] Một đảo cố định ở cùng vị trí: trang sản phẩm, giỏ hàng, thanh toán
- [ ] Giỏ có hàng thì dock nhường chỗ cho đảo, giỏ trống thì trả lại dock
- [ ] Bộ lọc và sắp xếp bám theo khi cuộn danh sách dài, có số bộ lọc đang chọn
- [ ] Phân trang nằm trên một hàng

## Luồng phải tự thử sau mỗi thay đổi lớn

- [ ] Không tràn ngang ở 360, 390, 768px
- [ ] Không còn ảnh hỏng, không lỗi hydration
- [ ] Mở và đóng: menu, tìm kiếm, bộ lọc, thông báo giỏ hàng, form tư vấn, form đại lý
- [ ] Đang gõ form thì dock, thanh mua và Zalo ẩn
- [ ] Kiểm tra trên máy thật, đặc biệt iPhone Safari (mô phỏng Chromium không thay được)

## Cần chủ website xác nhận

- [ ] Số liệu đại lý 5+, 50+, 5000+, các đánh giá và chứng nhận có đúng thực tế không
- [ ] Tiêu đề CMS viết hoa toàn bộ ("ĐĂNG KÝ ĐẠI LÝ", tên công ty ở trang liên hệ) có đổi sang chữ thường không
- [ ] Thử gửi form thật một lần khi được phép
