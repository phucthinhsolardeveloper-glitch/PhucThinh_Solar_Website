# Đo và tối ưu thao tác website/Admin — 2026-10-03

## Phạm vi đo

Docker local PHP 8.1, MariaDB local và Chromium desktop 1440×1000. Quét 160 màn
Admin chỉ đọc; đo thêm dashboard, danh sách/tìm kiếm sản phẩm, đơn hàng, dự án,
trang chủ, catalog và trang dự án công khai. Số liệu là mẫu local, có cache trình
duyệt và thumbnail được làm nóng ở các lượt sau; không suy ra độ trễ production
hoặc mạng của khách hàng.

## Điểm nghẽn đã xác nhận và thay đổi

- PHP trả hầu hết màn Admin trong khoảng 10–35ms; quét 160 màn không có lỗi
  HTTP/PHP. Hai thao tác xuất file sản phẩm nặng hơn: khoảng 277ms và 631ms ở
  lượt đo này. Không đổi truy vấn chung khi chưa có bằng chứng nó gây lag.
- Danh sách sản phẩm dùng ảnh gốc cho thumbnail 70×55 vì cấu hình
  `product['san-pham']['thumb']` rỗng. Trang danh sách tải khoảng 7,7MB ảnh,
  tìm kiếm mẫu tải khoảng 12,3MB; riêng một PNG gần 10,9MB. Template Admin nay
  yêu cầu thumbnail 70×55, không đổi cấu hình ảnh gốc/upload. Sau khi cache
  thumbnail được tạo, hai màn chỉ chuyển khoảng 14KB và 13KB ảnh mới trong
  cùng trình duyệt thử. Lần tạo thumbnail đầu có thể chậm hơn; `per_page=100`
  vẫn tốn khoảng 350–390ms đến khi trang tải xong do lượng hàng DOM lớn.
- Dashboard và login trước đây có màn che toàn trang, đợi cố định 300ms rồi
  fade thêm. Bỏ màn che và đoạn hẹn giờ vì HTML đã sẵn trước khi script chạy.
- Login nạp 21 script/17 stylesheet cho hai ô nhập. Giờ chỉ nạp các asset cần
  cho login: 3 script/5 stylesheet. Các màn dashboard, danh sách sản phẩm,
  đơn hàng và dự án nạp bộ thư viện theo chức năng thực dùng; form chỉnh sửa
  vẫn giữ bộ đầy đủ. Kiểm tra trình duyệt phát hiện Select2 là phụ thuộc chung
  của danh sách, nên thư viện này vẫn được giữ. Mẫu sản phẩm giảm từ 21 xuống
  9 script; đơn hàng còn 12 script (bao gồm lọc ngày và giá).
- Mega-menu công khai đã có animation mở nhưng đóng bằng `hidden=true` cùng
  tick, làm panel biến mất đột ngột. Bây giờ đóng chuyển opacity/transform,
  sau đó mới ẩn; rê vào lại trong lúc đóng sẽ đảo chiều thay vì chớp tắt.
  Kiểm tra Chromium thấy opacity giảm từ 1 xuống khoảng 0,51 ở 80ms rồi ẩn
  ở khoảng 220ms. Luồng `prefers-reduced-motion` vẫn đóng ngay.
- Trang chủ từng tạo bundle JS khác các route còn lại chỉ vì hai file nhỏ
  `toc.js` và `home-story-v4.js` được nạp có điều kiện. Hai file tự bỏ qua khi
  không có DOM tương ứng; nay mọi route dùng cùng URL bundle khoảng 334KB để
  trình duyệt tái sử dụng cache khi chuyển trang. Đây là cải thiện cho lần
  chuyển trang sau, không giảm dung lượng lần truy cập đầu.

## Bổ sung đo và xử lý ngày 2026-10-04

- Hồ sơ Chromium mới trên bốn route đại diện cho thấy bundle JS public 335KB,
  CSS khoảng 291–295KB và bốn ảnh banner trang chủ tải cùng lúc tổng khoảng
  746KB. Trang chủ có các long task mẫu 59–134ms. Đây là phép đo local, không
  dùng riêng một lượt `load`/LCP để khẳng định đã hết khựng trên production.
- Jssor slider không còn markup `#jssor_1` ở template public; loại bộ JS/CSS
  và đoạn khởi tạo chết. Bootstrap 4.0.0 bị nạp sau 4.1.3 rồi bị 4.6.0 ghi đè
  trong cùng bundle; loại bản 4.0.0. Hai bản còn lại chưa gộp vì 4.1.3 bundle
  cung cấp Popper cho dropdown, cần kiểm tra tương thích sâu trước khi thay.
  Bundle dùng chung giảm từ 335KB xuống khoảng 299KB trong phép đo cùng cách.
- Ảnh banner đầu tiên (LCP tiềm năng) trước đây mang `loading=lazy` dù đang ở
  viewport. Chỉ ảnh đầu được chuyển sang tải ngay; slide sau giữ cách tải cũ.
  Chưa thay cơ chế tải bốn ảnh banner vì cần bảo đảm slide kế tiếp không chớp
  trắng trên mạng chậm.
- `validateForm()` trong Admin từng chỉ mở khóa nút submit ở `window.load`;
  ảnh/tài nguyên phụ tải chậm làm form đã hiện nhưng nút vẫn khóa. Chuyển khởi
  tạo sang `DOMContentLoaded`, có fallback khi script chạy sau thời điểm đó.
  Bài test mô phỏng ảnh chậm xác nhận nút sẵn sàng trước `window.load`.
- Browser test rộng chạy 274 trường hợp: 226 đạt, 35 skip, 4 chưa chạy và 9
  thất bại ở lần đầu. Bảy lỗi đến từ test cũ (nền tab danh mục, input lọc
  sidebar, khối mô tả danh mục nay có công tắc tắt, selector menu đã đổi sang
  button, và phép `scrollIntoViewIfNeeded` không tương đương click điều hướng
  PDP). Cập nhật các kỳ vọng theo hành vi hiện tại; chạy lại nhóm liên quan:
  9 đạt, 1 skip. Hai lỗi còn lại là ảnh baseline danh mục desktop/mobile đã
  cũ so với bố cục danh mục được đổi trước đợt tối ưu này; chưa ghi đè ảnh
  chuẩn vì cần duyệt riêng giao diện hiện hành.
- Quét lại 160 màn Admin chỉ đọc: 160 OK, không lỗi/cảnh báo/trống. CRUD fixture
  sản phẩm và tin tức: 19 đạt, 0 lỗi. Fixture local tự dọn sau bài test.

## Xác minh

- PHP 8.1 lint các template/entry Admin đã sửa; JS syntax và
  `git diff --check`: đạt.
- Admin readiness: đạt; CRUD vòng đời sản phẩm/tin tức 19/19 đạt.
- Playwright menu desktop/mobile: 10 đạt, 4 skip theo viewport.
- Playwright bundle JS chung qua trang chủ/danh mục/dự án: 2/2 đạt trên
  desktop/mobile, không có lỗi JavaScript.
- Browser có đăng nhập: dashboard, danh sách/tìm kiếm sản phẩm, đơn hàng,
  dự án, bộ lọc ngày/giá, tìm kiếm bằng nút và các route công khai trả 200;
  không có `pageerror`.
- Tài khoản Admin thử dùng trên local được xóa sau khi đo. Không thay đổi DB
  sản phẩm hoặc upload thật.

## Việc còn lại

- Trang chủ công khai trong mẫu tải khoảng 1,7MB; bundle JS/CSS lần lượt
  khoảng 334KB/292KB, ba ảnh hero khoảng 628KB. Catalog và trang dự án cũng
  nạp bundle CSS lớn. Tách asset public theo route cần bản đồ phụ thuộc và
  regression tương tác/visual đầy đủ; lần tách thư viện trước từng được hoàn
  nguyên vì làm sai giao diện, nên không cắt hàng loạt trong đợt này.
- Ảnh nguồn có đuôi `.webp` hiện không đi qua bộ tạo thumbnail chung. Nếu
  danh sách Admin chứa WebP gốc lớn cần xử lý riêng sau khi xác nhận codec và
  fallback; không thay logic ảnh toàn site khi chưa có mẫu lỗi.
- Cần đo thêm production với mạng thật và thao tác upload ảnh lớn. PHP local
  nhanh không bảo đảm VPS/CDN/trình duyệt khác có cùng độ trễ.
- Chưa có bằng chứng để bỏ thêm Bootstrap 4.1.3/4.6.0 hoặc các thư viện media
  còn dùng ở trang chi tiết sản phẩm và đánh giá; tránh cắt tiếp theo suy đoán.

## Rollback

Các thay đổi chỉ ở source/template/test, không có migration. Hoàn nguyên
  đúng các hunk của đợt tối ưu trong template ảnh danh sách sản phẩm, asset plan
  Admin, loader Admin, logic đóng mega-menu, bundle JS công khai, ảnh hero đầu
  và thời điểm khởi tạo form Admin. Giữ nguyên
  các sửa dở khác trong working tree. Không xóa cache/ảnh/DB để rollback giao diện.
