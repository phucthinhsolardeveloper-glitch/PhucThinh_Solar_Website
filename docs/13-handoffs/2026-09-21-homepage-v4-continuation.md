# Bàn giao: tiếp tục homepage v4 và sửa UX header/gallery

- Ngày: 2026-09-21
- Nhánh: `feature/kitawa-inspired-header`
- Trạng thái: đã sửa và kiểm thử local; chưa commit, chưa push, chưa deploy.
- Quyết định giao diện gốc: [ADR 0006](../09-decisions/0006-homepage-story-layout-override.md).

## Phần hoàn tất trong lượt này

- Header desktop cao/rộng hơn, logo, menu và nút tư vấn lớn hơn; ở màn rất rộng
  header đạt tối thiểu 118px. Menu con bám ngay dưới mục cha, bỏ khoảng trống và
  chuyển động làm đứt hover; kiểm thử rê chuột từ mục cha xuống submenu.
- Submenu desktop được tăng theo tỷ lệ header: rộng tối thiểu 285px, dòng 46px
  và chữ 14.5px; ở màn từ 1800px tăng lên 310px, dòng 49px và chữ 15.5px. Menu
  Danh mục rộng hơn để tên sản phẩm dài không bị chật.
- Ẩn công tắc Google Translate khỏi header responsive/mobile. Bộ chọn ngôn ngữ
  trong menu drawer không đổi.
- Gallery chi tiết sản phẩm khóa khung hiển thị 1:1, ảnh dùng `contain` và căn
  giữa để đổi ảnh không làm co giãn khung hoặc cắt chủ thể. MagicZoom lấy ảnh
  600×600 (không phóng thumbnail 120px), và nếu ảnh bìa không còn trên đĩa thì
  tự chọn ảnh gallery hợp lệ làm ảnh chính, không ghi ngược vào DB. Card sản
  phẩm tiếp tục dùng canvas vuông 1:1.
- Thêm hai ảnh minh họa riêng cho Pin lưu trữ và Bơm năng lượng mặt trời ở
  `assets/images/site-visuals/`. Tên/liên kết danh mục vẫn ưu tiên dữ liệu thật;
  ảnh fallback mới được giữ để đồng bộ bộ ảnh trang chủ.
- Bổ sung ảnh theo chủ đề cho cả bốn slide hero và bốn tab giải pháp; hạ nhẹ cỡ
  chữ lớn/chiều cao hero trên desktop để cân đối khung nhìn, giữ cỡ mobile riêng.
- Cố định giới hạn co giãn logo theo cột header để ảnh logo không lấn sang nút
  Danh mục; browser test đo khoảng cách ở 1321–2560px.
- Giữ các bề mặt section Chiếu sáng, Dịch vụ và Liên hệ ở màu xám nhạt như thiết
  kế; chỉ đưa token navy dùng cho chữ/điểm nhấn về đúng màu footer `#061537`.
- Chuẩn hóa thêm thumbnail sản phẩm `x4`: với ảnh nền trong hoặc nền trắng gần
  đồng nhất, hệ thống dò vùng vật thể và căn giữa trong canvas vuông; ảnh nền
  phức tạp giữ nguyên toàn ảnh theo `contain`. Không sửa ảnh gốc, không phóng
  nguồn nhỏ lên; các bản thumbnail được tạo riêng theo URL cache mới.
- Khối hợp tác đại lý có ảnh nền thật thay vì một mảng màu trơn: ưu tiên ảnh
  quảng cáo Admin đã gắn với CTA `#ptsDealerModal`, dùng ảnh minh họa mới làm
  fallback khi chưa cấu hình; phủ navy vừa đủ để giữ tương phản chữ/nút. Ảnh
  fallback không đại diện cho dự án/khách hàng thực tế.
- Giữ ba thẻ giải pháp có ảnh lớn và phần nội dung bên dưới. Cách này đúng yêu
  cầu bổ sung ảnh, dễ nhận biết ngữ cảnh hơn bản thẻ icon-only.
- Hero tự chuyển sau mỗi 4,2 giây, kể cả khi con trỏ/focus nằm trên banner và
  khi hệ điều hành bật chế độ giảm chuyển động. Chỉ tạm dừng lúc vuốt và khi
  tab trình duyệt bị ẩn; quay lại tab thì tự chạy tiếp.
- Trang chi tiết sản phẩm có bố cục giới thiệu mới: gallery lớn nền trắng, vùng
  báo giá và CTA rõ hơn, mô tả ở cột trái cùng bảng thông số chiếm khoảng 40%
  bên phải trên desktop; trên màn hình hẹp hai phần xếp dọc. Bảng thông số bám
  theo khi cuộn desktop. Không đổi dữ liệu hay schema.

## Kiểm chứng

- Playwright nhóm header/homepage/media/gallery: 37 test pass, 1 test
  desktop-only được skip có chủ đích; sau khi thêm ảnh nền đại lý và sửa autoplay,
  riêng 8 lượt homepage desktop/mobile tiếp tục pass, gồm kiểm tra ảnh đại lý tải
  được và hero tự chuyển dù con trỏ nằm trên banner.
- Playwright autoplay với `prefers-reduced-motion: reduce`: 2 test pass trên
  desktop/mobile. Kiểm tra responsive sản phẩm 320–768px, đổi ảnh gallery,
  buy box và CTA báo giá cũng pass.
- PHP 7.4 lint cho 5 file PHP liên quan homepage/gallery/card: pass.
- `node --check` cho các test liên quan và `git diff --check`: pass.
- `node --check` cho JS mới và `git diff --check`: pass.
- Không thay schema, không ghi database, không thao tác production.

## Rủi ro/giới hạn còn lại

- Tỷ lệ chủ thể vẫn có thể khác nhau khi ảnh có nền không thuần trắng/trong hoặc
  chủ thể bị hòa vào nền. Với các ảnh đó, hệ thống chủ động không crop và giữ
  nguyên ảnh để tránh cắt mất sản phẩm; có thể cần chỉnh nguồn thủ công trong
  Admin nếu muốn cân tỷ lệ tuyệt đối.
- Ảnh bìa J200 trong ảnh báo lỗi trỏ tới file không có trong upload local; template
  sẽ dùng ảnh gallery còn tồn tại nếu có. Route sản phẩm đó không có trong dataset
  Playwright hiện tại nên fallback cụ thể cần chủ project xác nhận trên localhost.
  Nếu cả gallery cũng thiếu ảnh thật, Admin vẫn cần tải lại ảnh sản phẩm.
- Cần chủ project xem trực tiếp local và chốt cảm nhận kích thước header/ảnh.

## Deploy và rollback

- Hiện chỉ chạy local. Chưa commit/push/deploy; không tác động VPS.
- Rollback frontend bằng cách khôi phục các file thuộc redesign trong nhánh này
  về snapshot/commit đã được chủ project duyệt trước khi làm; không cần migration
  hay rollback dữ liệu. Trước rollback cần đối chiếu `git status` để giữ mọi thay
  đổi khác chưa thuộc redesign.
