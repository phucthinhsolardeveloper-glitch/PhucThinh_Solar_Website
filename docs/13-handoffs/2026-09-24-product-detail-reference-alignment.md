# PDP — đối chiếu mẫu sếp gửi và checklist triển khai

Ngày: 2026-09-24  
Mẫu đối chiếu: `C:\Users\ADMIN\Downloads\PTS_Chi_Tiet_Xem_Thu.html` và ảnh
`1790209968568_...jpg`.

## Kết luận phân tích

Mẫu mới tổ chức trang theo luồng ra quyết định kỹ thuật: gallery và thông tin
chính ở hai cột cân nhau; bốn thông số quan trọng; giá/CTA; thanh điều hướng
gọn; ứng dụng; hướng dẫn lựa chọn; bảng thông số; media/tài liệu; FAQ; form tư
vấn và sản phẩm cùng nhóm. Phần lớn dữ liệu trong HTML mẫu là placeholder nên
không được nhập vào DB hoặc hiển thị như dữ liệu thật.

| Khối trong mẫu | Nguồn hiện tại | Fallback khi thiếu | Việc Admin/DB sau này |
|---|---|---|---|
| Gallery 1:1 | `product.photo`, gallery sản phẩm | ảnh dự phòng hiện có | không cần |
| Model, mô tả, giá | product hiện tại | CTA liên hệ theo cấu hình giá | không cần |
| 4 thông số quyết định | structured specs + `additional_specs` | ẩn ô thiếu, không dựng số liệu | bổ sung field inverter nếu cần lọc/báo cáo riêng |
| Bộ sản phẩm | `package_contents` | ẩn dòng | chuẩn hóa nội dung theo model |
| Ứng dụng | content block do Admin quản lý | ẩn section | field/media ứng dụng chuyên trách nếu nhu cầu tăng |
| Hướng dẫn lựa chọn | thông số kỹ thuật thật | ẩn item thiếu | bổ sung field theo từng dòng sản phẩm |
| Video | `video_url` hợp lệ | ẩn section | không cần |
| Chứng nhận/bảo hành | `certificate*`, `chinhsachmuahang*`, policy | ẩn section thiếu | tài liệu PDF chuyên trách |
| FAQ | sinh từ thông số thật | ẩn section | FAQ chuyên trách nếu cần biên tập độc lập |
| Form tư vấn | newsletter hiện tại, CSRF/reCAPTCHA | giữ validation hiện hành | nhu cầu/vị trí lắp chuyên trách nếu backend mở rộng |

## Checklist triển khai

- [x] Đọc HTML/CSS mẫu, route, source, template, schema field và design system.
- [x] Lập mapping; không import placeholder và không thay đổi DB production.
- [x] Hero desktop hai cột cân nhau; gallery 1:1 `contain` trên nền trung tính.
- [x] Thêm ma trận tối đa bốn thông số quyết định bằng dữ liệu thật.
- [x] Giữ đúng hai hành vi commerce: có giá thì mua/giỏ hàng, ẩn giá thì báo giá/gọi tư vấn.
- [x] Thanh điều hướng giữa trang gọn, sticky, tối đa bốn mục cấp cao và có
  trạng thái active khi cuộn. Link neo dùng path sản phẩm hiện tại để không bị
  thẻ HTML `base` điều hướng nhầm về trang chủ.
- [x] Khối ứng dụng và hướng dẫn chuyển sang bề mặt phẳng, không dùng card trang trí.
- [x] Bảng thông số, video/chứng nhận/bảo hành, FAQ chỉ render khi có dữ liệu.
- [x] Form tư vấn và sản phẩm liên quan bám nhịp mẫu nhưng dùng dữ liệu/hành vi hiện tại.
- [x] Kiểm tra 320–2560px, desktop/mobile, PHP lint và commerce smoke test.
- [x] Bổ sung bật/ẩn nội dung danh mục, hiển thị nguồn thực tế ở form sản phẩm,
  snapshot trước khi xoá và phục hồi bản gần nhất.
- [x] Nút reset bộ lọc chỉ hiện khi có filter/sort; trạng thái được cập nhật cả
  sau lọc AJAX và khi back/forward lịch sử trình duyệt.
- [ ] Backlog: thiết kế bảng tài liệu sản phẩm/PDF và quyền Admin sau khi chủ project duyệt schema.
- [ ] Backlog: cân nhắc media ứng dụng chuyên trách nếu content block không đủ cho vận hành thực tế.

## Kết quả kiểm tra local

- PHP 7.4 lint `sources/product.php` và `product_detail_tpl.php`: PASS.
- Homepage/category/product detail local: HTTP 200; product detail không có
  Warning/Notice/Fatal/Parse error trong HTML render.
- PDP interaction desktop/mobile, mobile commerce và visual baseline desktop:
  PASS. Snapshot được cập nhật vì CTA xếp dọc theo mẫu tham chiếu.
- Bộ layout/commerce/responsive kết hợp: 45 PASS, 11 SKIP có chủ ý. Hai lượt
  timeout ở ma trận responsive do local stack chậm khi chạy song song; cả hai
  PASS ở retry, cần theo dõi khi predeploy.
- Admin screen sweep: 117 màn hình chỉ đọc, 117 PASS, 0 lỗi, 0 cảnh báo.
- Preflight schema local: migration `049_add_content_section_backups.sql` PASS.

## Phạm vi và rollback

Đợt này có migration cộng thêm `table_content_section_backup`; đã áp dụng và kiểm
tra trên DB local, chưa áp dụng production. Migration không sửa dữ liệu hiện hữu.
Rollback code bằng cách hoàn nguyên các file Admin/frontend/test; giữ lại bảng
backup vì đây là bảng bổ sung an toàn, chỉ drop khi đã xác nhận không còn snapshot
cần phục hồi. Dữ liệu sản phẩm và nội dung hiện hữu không bị thay đổi bởi kiểm tra
local.

## Bổ sung: mô tả dài và bộ lọc sản phẩm

- Nội dung dạng tiêu đề + danh sách như ảnh không hard-code ở frontend. Nó được
  render từ `content_section` qua `productResolveSections()` và
  `productRenderSections()`.
- Nếu ảnh là phần nội dung nằm dưới lưới sản phẩm của trang danh mục, nguồn là
  scope `cat`/`list`: Admin có Sửa/Tạo, Bật/Tắt và Xoá toàn bộ khối; bản đang ẩn
  không được dùng làm nguồn kế thừa.
- Admin sản phẩm đã có bốn chế độ: kế thừa, nội dung riêng, riêng + kế thừa,
  hoặc ẩn mô tả dài. Có thể chọn nguồn kế thừa tự động, chỉ thương hiệu, chỉ
  danh mục hoặc cộng dồn các nguồn.
- Composer Admin hỗ trợ tiêu đề, đoạn văn, ảnh, ảnh + mô tả và danh sách; xoá
  hết khối riêng sẽ quay lại kế thừa. Hiện chưa có công tắc bật/tắt từng khối
  con; muốn bỏ một H2/danh sách phải sửa hoặc xoá chính khối đó.
- Nút `Xóa bộ lọc` ở landing sản phẩm được đổi từ link gạch chân thành secondary
  control phẳng, có viền nhẹ, hit target tối thiểu 44px và trạng thái hover/focus;
  href reset và luồng API không thay đổi. Nút chỉ hiện khi có filter/sort đang
  hoạt động.
- Admin đã có nút bật/ẩn nội dung scope `brand`/`cat`/`list`, hiển thị nguồn và
  trạng thái; thao tác xoá tạo snapshot ở `content_section_backup` và có thể
  phục hồi bản gần nhất. Export/import JSON giữ trạng thái hiển thị.
- Smoke test landing sản phẩm và filter drawer mobile: 3 PASS, 1 SKIP do test
  desktop không áp dụng mobile.
