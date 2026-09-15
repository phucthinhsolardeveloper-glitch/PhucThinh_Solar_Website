# ADR 0005: Trang Giải pháp dùng CKEditor và module động của template

- Ngày: 2026-09-15
- Owner: Chủ project
- Trạng thái: Đã duyệt

## Bối cảnh

Nội dung Giải pháp là nội dung tư vấn dài, có thứ tự biên tập riêng cho từng
nhu cầu. Composer `content_section` hiện được thiết kế cho mô tả sản phẩm và cơ
chế kế thừa sản phẩm → thương hiệu/danh mục; đưa nó sang Giải pháp sẽ làm Admin
phức tạp mà không tạo thêm giá trị tương xứng.

## Quyết định

1. Nội dung dài trang tổng và trang chi tiết Giải pháp soạn bằng CKEditor.
2. Editor dùng HTML ngữ nghĩa đơn giản: H2/H3, đoạn văn, danh sách, bảng, ảnh và
   blockquote. Template cung cấp typography responsive thống nhất, không yêu cầu
   biên tập viên tự chèn class giao diện.
3. Hero, menu chuyển giải pháp, mục lục, thư viện ảnh, sản phẩm liên quan và CTA
   nằm ngoài CKEditor để giữ hành vi, responsive và dữ liệu quan hệ chính xác.
4. Trang Giải pháp bỏ ngày đăng và tác giả kiểu bài blog.
5. Link menu/card đi thẳng tới landing nội dung đại diện. Trang nhóm cũ vẫn tồn
   tại cho URL cũ nhưng không tạo thêm một bước click trong hành trình chính.
6. Canonical chi tiết dùng `/giai-phap/{slug}/`; URL bare slug và `solution/`
   được chuyển 301 để bảo toàn link đã công bố.
7. Cấu trúc nội dung public gồm đúng một landing `/giai-phap/` và năm landing
   chi tiết: gia đình, doanh nghiệp, lưu trữ/BESS, chiếu sáng và bơm nước. Nhóm
   `news_list` cũ chỉ giữ tương thích URL/Admin, không xuất hiện trong menu,
   card, switcher hoặc breadcrumb của hành trình chính.
8. H1/SEO giữ tên đầy đủ; field `title_index` là tên ngắn dùng riêng cho menu,
   card và switcher để điều hướng không bị dài hoặc xuống dòng vô ích.

## Hệ quả

- Nhập bộ nội dung Markdown vào Admin nhanh hơn và không cần xây một page
  builder mới.
- Những phần cần lọc đúng dự án/sản phẩm vẫn phải có quan hệ dữ liệu riêng;
  không nhập tên hoặc URL thủ công vào nội dung chỉ để giả lập quan hệ.
- Một trang chỉ có một H1 do template render; CKEditor bắt đầu từ H2.
- Breadcrumb chi tiết chỉ gồm `Trang chủ › Giải pháp › bài hiện tại`.

## Rollback

Khôi phục `service_detail_tpl.php`, bỏ CSS detail, bỏ route nested và trả link
menu/card về category cũ. Nội dung CKEditor và các URL cũ không bị xóa.
