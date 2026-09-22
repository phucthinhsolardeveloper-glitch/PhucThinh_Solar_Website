# Bàn giao: tiếp tục homepage v4 và sửa UX header/gallery

- Ngày: 2026-09-21
- Nhánh: `feature/kitawa-inspired-header`
- Trạng thái: đã sửa và kiểm thử local; chưa commit, chưa push, chưa deploy.
- Quyết định giao diện gốc: [ADR 0006](../09-decisions/0006-homepage-story-layout-override.md).

## Phần hoàn tất trong lượt này

- Header desktop giữ kích thước lớn đã duyệt; dải 1501–2299px dùng
  `clamp(148px, 8vw, 164px)`, từ 2300px dùng 174px. Logo, menu và nút tư vấn
  được tăng tương ứng nhưng vẫn kiểm tra va chạm ở các chiều rộng desktop.
  Menu con bám ngay dưới mục cha, bỏ khoảng trống và chuyển động làm đứt hover.
- Submenu desktop được tăng theo tỷ lệ header: rộng tối thiểu 285px, dòng 46px
  và chữ 14.5px; ở màn từ 1800px tăng lên 310px, dòng 49px và chữ 15.5px. Menu
  Danh mục rộng hơn để tên sản phẩm dài không bị chật. Lớp submenu đặt cao hơn
  search panel nên vẫn rê chuột/chọn được mục con khi thanh tìm kiếm đang mở.
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
- Bổ sung ảnh theo chủ đề cho cả bốn slide hero và bốn tab giải pháp. Trên màn
  rộng từ 1800px, hero chiếm phần chiều cao còn lại sau header; tiêu đề danh mục
  kế tiếp không ló vào cuối khung nhìn đầu tiên. Khung nội dung trang chủ mở rộng
  theo màn lớn (tối đa 2200px) để giảm khoảng trống hai bên.
- Sắp thứ tự dải danh mục trang chủ theo nhóm: đèn; thiết bị (biến tần, bơm);
  pin (tấm pin, pin lưu trữ). Hero phía trên theo cùng nhịp: đèn, biến tần, tấm
  pin, pin lưu trữ.
- Cố định giới hạn co giãn logo theo cột header để ảnh logo không lấn sang nút
  Danh mục; browser test đo khoảng cách ở 1321–2560px.
- Giữ các bề mặt section Chiếu sáng, Dịch vụ và Liên hệ ở màu xám nhạt như thiết
  kế; chỉ đưa token navy dùng cho chữ/điểm nhấn về đúng màu footer `#061537`.
- Chuẩn hóa thêm thumbnail sản phẩm `x4`: với ảnh nền trong hoặc nền trắng gần
  đồng nhất, hệ thống dò vùng vật thể và căn giữa trong canvas vuông; ảnh nền
  phức tạp giữ nguyên toàn ảnh theo `contain`. Không sửa ảnh gốc, không phóng
  nguồn nhỏ lên; các bản thumbnail được tạo riêng theo URL cache mới.
- Ảnh danh mục trang chủ bỏ lớp phủ navy làm tối; phần mô tả dùng kính mờ trong
  hơn (nền trắng alpha 28–30%, blur 2px) để hình phía sau hiện rõ. Khối hợp tác
  đại lý chuyển sang nền navy đồng nhất `#061537` như footer; tiêu đề/mô tả vẫn
  lấy từ cấu hình quảng cáo Admin, không render ảnh nền ở section này.
- Các chip thiết bị ở tab giải pháp là liên kết tới nhóm sản phẩm phù hợp; chip
  “Điện mặt trời mái nhà” và nút xem giải pháp Gia đình dẫn thẳng tới
  `/giai-phap/dien-mat-troi-ho-gia-dinh/`, không về trang tổng quan.
- Giữ ba thẻ giải pháp có ảnh lớn và phần nội dung bên dưới. Cách này đúng yêu
  cầu bổ sung ảnh, dễ nhận biết ngữ cảnh hơn bản thẻ icon-only.
- Hero tự chuyển sau mỗi 4,2 giây, kể cả khi con trỏ/focus nằm trên banner và
  khi hệ điều hành bật chế độ giảm chuyển động. Chỉ tạm dừng lúc vuốt và khi
  tab trình duyệt bị ẩn; quay lại tab thì tự chạy tiếp.
- Thay riêng ảnh 4 slide Hero bằng ảnh mới sáng, rõ, cùng khung panorama:
  đèn đường, biến tần, tấm pin và pin lưu trữ. Ảnh đầu ra WebP 1860×845, tổng
  dung lượng khoảng 0,73 MB; CSS giảm lớp phủ tối toàn banner, chỉ giữ gradient
  nhẹ ở vùng chữ. Tên/mô tả/liên kết vẫn lấy từ Admin khi đủ bốn nhóm; ảnh Hero
  dùng bộ ảnh mới cố định để cấu hình ảnh cũ không phủ lại thiết kế vừa duyệt.
- Sau khi chủ project duyệt nền ảnh sáng, chữ Hero chuyển sang navy đậm; lớp phủ
  chuyển thành gradient trắng nhẹ ở vùng nội dung, bỏ bóng chữ trắng và giữ bộ
  điều khiển slider nền navy để tương phản thao tác.
- Cụm nút chuyển slide được tinh chỉnh thành control navy kính nhẹ, viền sáng
  mảnh, số thứ tự tách rõ và trạng thái hover/focus màu vàng; vẫn giữ kích thước
  gọn, không che nội dung banner.
- Khối đăng ký đại lý desktop chuyển thành bố cục hai cột cân bằng: nội dung và
  ba lợi ích bên trái, hai CTA nằm cùng hàng bên phải để giảm khoảng trống dọc;
  từ breakpoint 860px trở xuống tự xếp CTA thành cột để không tràn mobile.
- Typography khối Liên hệ được tăng nhẹ: tiêu đề 24px, mô tả 15.5px, thông tin
  liên hệ 15px và trường form 16px; giữ breakpoint mobile để không gây tràn.
- Tên Hero được chuẩn hóa theo ngữ cảnh sản phẩm: “Đèn đường năng lượng mặt
  trời”, “Biến tần năng lượng mặt trời”, “Tấm pin năng lượng mặt trời”; “Pin lưu
  trữ” giữ nguyên. Tên lấy từ Admin cũng được bổ sung hậu tố nếu còn thiếu.
- Caption Hero được mở rộng theo viewport và khóa một dòng cho tên thiết bị;
  mobile dùng cỡ chữ linh hoạt để không bị xuống dòng hoặc tràn ngang.
- Ảnh tab “Chiếu sáng công trình” ở khối Giải pháp được thay bằng ảnh phối cảnh
  đại lộ năng lượng mặt trời quy mô lớn (`home-solution-construction-v1.png`),
  giữ vùng an toàn trung tâm cho crop 8:5 desktop/mobile; không thay nội dung,
  tab hoặc URL.
- Footer đã chuẩn hóa từ database local: tên pháp lý đầy đủ, trụ sở `424 Trường
  Chinh, Phường Hội Phú, Gia Lai`, tiêu đề “Hệ thống kho & điểm giao dịch”,
  các điểm `Kho TP.HCM`, `Kho Đà Nẵng`, `Kho Gia Lai`, cùng hai hotline có nhãn
  “Tư vấn bán hàng” và “Hỗ trợ kỹ thuật”. Cache ba truy vấn liên quan đã được
  làm mới sau transaction; không cập nhật production. Badge Bộ Công Thương đã
  chuyển sang cuối cột chính sách để đi cùng các thông tin hỗ trợ, không làm
  cột pháp lý bị kéo dài.
- Trang Đại lý được đồng bộ nhịp homepage: container rộng theo màn hình, hero
  giữ compact, typography/form tăng rõ và form desktop chuyển lưới hai cột.
  Bổ sung điều hướng nhanh ba mốc, gallery 9 ảnh theo lưới 3×3 có thứ tự, khối
  quyền lợi navy chia tiêu đề/mô tả thành hai cột và bốn quyền lợi thành một
  hàng. Mobile tự về một cột; không đổi dữ liệu Admin, CSRF, reCAPTCHA hay URL.
- Trang chi tiết sản phẩm có bố cục giới thiệu mới: gallery lớn nền trắng, vùng
  báo giá và CTA rõ hơn, mô tả ở cột trái cùng bảng thông số chiếm khoảng 40%
  bên phải trên desktop; trên màn hình hẹp hai phần xếp dọc. Bảng thông số bám
  theo khi cuộn desktop. Không đổi dữ liệu hay schema.

## Kiểm chứng

- Playwright header + homepage desktop: 8/8 pass; homepage mobile (không tràn,
  slider vuốt) và header search mobile: 2/2 pass. Test xác nhận submenu vẫn nhận
  hover khi search panel mở, hero không để lộ section kế tiếp, link giải pháp
  gia đình đúng URL, card có ảnh tải được, dealer navy và kính danh mục alpha 30%.
- Playwright autoplay với `prefers-reduced-motion: reduce`: 2 test pass trên
  desktop/mobile. Kiểm tra responsive sản phẩm 320–768px, đổi ảnh gallery,
  buy box và CTA báo giá cũng pass.
- Test homepage xác nhận đủ đúng bốn ảnh Hero mới và tải không lỗi.
- PHP 7.4 lint cho 5 file PHP liên quan homepage/gallery/card: pass.
- PHP 7.4 lint cho `sources/index.php` và `templates/index/index_tpl.php`: pass.
- `node --check` cho các test liên quan và `git diff --check`: pass.
- `node --check` cho JS mới và `git diff --check`: pass.
- Trang Đại lý: PHP 7.4 lint pass; Playwright chuyên trang 2/2 và ma trận
  responsive 360, 390, 768, 1024, 1366, 1440, 1920, 2560 đạt 18/18.
- Không thay schema và không thao tác production. Database local đã được cập nhật
  trong transaction; bản sao dữ liệu liên quan lưu tại `/tmp/pts-footer-db-backup-20260922.tsv`
  để đối chiếu/rollback thủ công khi cần.

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
