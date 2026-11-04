# ADR 0006: Trang chủ kể chuyện theo layout được chủ project duyệt

- Ngày: 2026-09-21
- Owner: Chủ project
- Trạng thái: Superseded về Hero và bố cục homepage từ 2026-10-28 bởi ADR 0008
- Thay thế: Các quyết định về thứ tự/nội dung trang chủ tại ADR 0001. Các quy
  tắc bề mặt phẳng còn lại đã được supersede bởi ADR 0010; nhận diện, URL và dữ
  liệu tiếp tục theo owner direction hiện hành.

## Bối cảnh

Chủ project xác nhận yêu cầu mới của sếp: trang chủ phải theo mạch kể chuyện và
bố cục trong prototype `pts-trangchu-v4.html` cùng ảnh tham chiếu, không tiếp tục
bị giới hạn bởi trang chủ product-first đã duyệt ngày 15/09. Hero phải là banner
ảnh dài, rộng, full viewport; bốn slide lần lượt giới thiệu Tấm pin, Đèn đường,
Biến tần và Pin lưu trữ, không biến thành bốn nút/icon. Chủ project cũng yêu cầu
giữ nội dung tĩnh hữu ích và kết hợp với dữ liệu đang quản lý.

## Quyết định

1. Trang chủ dùng hero slider full-width; nội dung tiêu đề, giới thiệu và CTA là
   lớp HTML riêng trên ảnh, không đặt banner trong một card.
2. Sau hero là một banner ngang cho nhóm Đèn năng lượng mặt trời, tiếp theo là
   bốn card ảnh nền có tiêu đề, mô tả và liên kết tới nhóm Tấm pin, Biến tần,
   Pin lưu trữ, Bơm nước. Ảnh phải nằm gọn trong khung; chữ cần tương phản đọc
   được ở mọi vùng ảnh.
3. Giữ hành trình nội dung prototype: chiếu sáng theo không gian, giải pháp theo
   nhu cầu dạng tab, quy trình, hợp tác đại lý, giới thiệu và một form liên hệ,
   rồi footer. Mỗi tab giải pháp dùng ảnh bối cảnh phù hợp, nhãn hiển thị trên
   ảnh; nội dung tab và CTA vẫn là HTML có thể đọc/chọn được.
4. Dữ liệu và liên kết category/solution/setting/Admin hiện có được dùng khi có;
   copy tĩnh và asset fallback có chủ đích được giữ để nội dung vẫn hoàn chỉnh
   khi dữ liệu chưa có. Ảnh hero và ảnh của từng giải pháp phải đúng chủ đề;
   không dùng ảnh/biểu tượng chung để lấp chỗ. Không thay URL/slug và không ghi
   dữ liệu vào database.
5. Logo, màu navy-vàng-trắng, menu năm nhóm, các chức năng search/cart/ngôn ngữ,
   bảo vệ CSRF của form và các nguyên tắc UX/UI chung tiếp tục được giữ.
6. Contact section có khoảng kết thúc nhìn thấy được trước footer; CSS cho trang
   chủ phải thắng các reset legacy không phù hợp.

## Hệ quả

- Trang chủ dài hơn layout product-first và giữ lại nhiều nội dung giới thiệu.
- Hero carousel tái sử dụng nguồn ảnh đang được quản trị; fallback có đủ bốn
  nhóm thiết bị khi thiếu cấu hình tương ứng.
- Bốn card danh mục có ảnh nền trong khung và shade navy để bảo toàn độ tương phản;
  tab giải pháp cũng có ảnh bối cảnh riêng, với nhãn HTML nằm trên ảnh.
- Từ nay không dùng ADR 0001 để khôi phục layout product-first nếu chưa có phê
  duyệt đổi hướng mới.

## Phương án đã cân nhắc

- Giữ layout product-first cũ: không đáp ứng yêu cầu trực tiếp của sếp/chủ project.
- Đưa prototype tĩnh nguyên xi lên production: bỏ qua dữ liệu/route thật và gây
  mất luồng liên kết, nên chỉ lấy cấu trúc và kết hợp với nguồn dữ liệu hiện có.

## Rollback

Khôi phục `templates/index/index_tpl.php`, `sources/index.php`, `templates/layout`
và CSS/JS homepage/header về commit trước triển khai trên nhánh tính năng. Không
cần migration hay rollback database; không deploy production trong lần thực hiện
này.
