# ADR 0008: Trang chủ product-first không render Hero

- Ngày: 2026-10-28
- Trạng thái: Đã duyệt theo operating contract hiện hành
- Owner: Chủ project Phúc Thịnh Solar
- Thay thế: Phần Hero và bố cục trang chủ kể chuyện của ADR 0006; các quy tắc
  điều hướng, dữ liệu thật, bảo mật và nhận diện dùng chung tiếp tục áp dụng.

## Bối cảnh

Rà soát Nuxt preview cho thấy trang chủ vẫn hiển thị Hero slider và các khối
giải pháp, quy trình, đại lý, giới thiệu. Operating contract hiện hành yêu cầu
trang chủ là showroom product-first, không render Hero và đưa nội dung dài sang
trang chuyên biệt. Website PHP cũ là tham chiếu cho dữ liệu và URL, không phải
chuẩn để giữ lại bố cục đã bị thay đổi.

## Quyết định

1. Trang chủ không render Hero/banner slider mặc định; vùng đầu tiên hiển thị
   danh mục hoặc sản phẩm.
2. Danh mục giữ một hàng ngang và cuộn khi thiếu chỗ. Sản phẩm/danh mục chiếm
   phần lớn nội dung trang.
3. Giới thiệu công ty, quy trình, dự án và nội dung giải pháp dài nằm ở trang
   chuyên biệt; homepage chỉ giữ nội dung ngắn phục vụ khám phá hoặc chuyển đổi.
4. Nội dung marketing thay đổi lấy từ Admin, có công tắc hiển thị và fallback
   an toàn. Không dùng dữ liệu giả để lấp section.
5. Quyết định này không bật public route flag, thay đổi URL, sửa PHP production
   hoặc ghi dữ liệu CMS.

## Hệ quả

- Nuxt homepage preview bắt đầu bằng catalog section và đặt tiêu đề catalog làm
  H1 duy nhất khi DTO tải thành công.
- Payload Hero có thể tiếp tục tồn tại trong hợp đồng nguồn để tương thích dữ
  liệu; composition Nuxt không render field đó.
- ADR 0006 được giữ làm lịch sử nhưng không còn là hướng dẫn bố cục hiện hành.

## Rollback

Revert composition thay đổi tại trang Nuxt và test tương ứng. Chỉ khôi phục Hero
hoặc bố cục kể chuyện sau khi có chỉ đạo sản phẩm mới và cập nhật ADR/design
system. Không cần migration hoặc rollback database.
