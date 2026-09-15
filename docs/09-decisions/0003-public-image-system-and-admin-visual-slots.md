# ADR 0003: Hệ thống ảnh public và các vị trí hình ảnh quản lý bằng Admin

- Ngày: 2026-09-15
- Owner: Chủ project
- Trạng thái: Đã duyệt

## Bối cảnh

Sau khi bỏ border, shadow và lớp nền card trang trí, website cần tăng nhịp bán
hàng bằng hình ảnh có mục đích thay vì dựng lại các hộp vô nghĩa. Dữ liệu ảnh cũ
không đồng nhất: ảnh sản phẩm có nguồn nhỏ, ảnh dự án/giải pháp thường tải nguyên
bản quá lớn, logo có nguy cơ bị crop và một số trang quan trọng chưa có vị trí
ảnh riêng để Admin thay đổi hoặc tắt.

## Bằng chứng

- Kiểm thử độ phân giải phát hiện ảnh gốc 1.200–1.500 px từng được dùng ở khung
  chỉ 64–381 px, trong khi icon 43 px lại bị selector tổng quát kéo lớn.
- Trang sản phẩm, liên hệ và đại lý cần ba mục tiêu crop khác nhau; một quy tắc
  `cover` dùng cho mọi ảnh sẽ hoặc cắt sản phẩm/logo, hoặc làm khung nội dung
  không đều.
- Nội dung marketing có thể đổi phải tuân theo quy tắc Admin trong `AGENTS.md`.

## Quyết định

1. Phân loại theo mục đích: sản phẩm/logo/chứng nhận dùng `contain`; ảnh tin tức,
   dự án, giải pháp và ảnh không gian dùng `cover` trong tỷ lệ cố định.
2. Frontend yêu cầu thumbnail gần với kích thước render, không phục vụ nguyên
   bản lớn cho card nhỏ và không phóng thumbnail nhỏ hơn khung.
3. Thêm ba type ảnh quản lý qua Admin: `catalog-campaign`, `contact-visual`,
   `dealer-hero`; giữ `home-campaign` cho ba điểm nhấn trang chủ.
4. Mỗi type có cờ **Hiển thị**. Chỉ dùng ảnh đóng gói làm fallback khi type chưa
   từng có record; sau khi Admin tạo record, việc tắt cờ phải thật sự ẩn ảnh và
   không làm fallback tự xuất hiện lại.
5. Ảnh mẫu dựng chỉ minh họa sản phẩm/bối cảnh ngành điện; không được dùng làm
   bằng chứng dự án, khách hàng, nhân sự, chứng nhận hoặc thành tích có thật.
6. Kích thước nguồn, tỷ lệ và vùng an toàn được ghi ở Admin, audit ảnh và
   `AGENTS.md` để các lần sửa giao diện sau không tái tạo lỗi.

## Phương án đã cân nhắc

- Để ảnh hardcode trong template: nhanh nhưng Admin không vận hành được và không
  có cơ chế tắt.
- Chỉ dùng `object-fit: cover`: giao diện đều nhưng cắt sản phẩm, logo và hồ sơ.
- Dùng một kích thước cho mọi ảnh: đơn giản trên giấy nhưng lãng phí băng thông
  hoặc gây mờ ở các vùng hiển thị rất khác nhau.

## Hệ quả

- Website có nhịp hình ảnh rõ hơn mà vẫn giữ bề mặt phẳng theo ADR 0002.
- Admin phải chuẩn bị ảnh đúng tỷ lệ; ảnh cũ vẫn hiển thị an toàn nhưng các nguồn
  nhỏ cần được thay dần theo danh sách ưu tiên trong audit ngày 2026-09-15.
- Nhóm quyền đã quản lý quảng cáo được cấp thêm quyền cho ba type mới qua
  migration 042; migration mặc định chỉ preview và chạy lặp an toàn.

## Rollback

Gỡ các type mới khỏi `libraries/config-type.php` và `config-type-photo.php`, bỏ
logic tải chúng trong `sources/index.php`, `sources/product.php`,
`sources/contact.php`, `sources/daily.php`, rồi bỏ `media-system.css` khỏi
`templates/layout/css.php`. Không xóa record hoặc file upload; quyền thêm bởi
migration 042 có thể giữ lại vô hại hoặc thu hồi bằng một migration riêng đã
được duyệt.
