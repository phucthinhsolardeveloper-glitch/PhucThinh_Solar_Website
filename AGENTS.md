# Shared operating contract

Áp dụng cho developer, Claude Code và Codex.

```text
INSPECT -> UNDERSTAND -> PLAN -> IMPLEMENT -> TEST -> DEBUG -> VERIFY -> DOCUMENT -> REPORT
```

Project được kế thừa. Không mặc định hành vi hiện có là chủ ý. Trước thay đổi đáng kể phải đọc route/source/template/API, schema/config liên quan, xác định dữ liệu bị tác động, test và rollback.

## Quy tắc

- Không sửa production hoặc import đè DB nếu chưa được phép rõ ràng.
- Không xóa code/upload/cache/legacy asset trước khi xác minh.
- Không đưa credential, token, password hash hoặc PII vào tài liệu/chat.
- Không cleanup/refactor ngoài phạm vi task.
- SQL mới phải parameterized; form mutation phải kiểm tra session/quyền và CSRF.
- Upload phải kiểm tra extension, MIME, size, filename và lưu ở nơi không thực thi PHP.
- Giữ URL/slug public trừ khi có kế hoạch redirect/SEO.
- Test file thay đổi bằng đúng PHP mục tiêu; commerce smoke test theo category -> product -> cart -> checkout -> confirmation.
- Cập nhật docs nếu implementation chứng minh tài liệu sai.

## Quy tắc UX/UI đã duyệt

- Trang chủ là showroom bán hàng: sản phẩm và danh mục chiếm phần lớn nội dung; giới thiệu công ty, giải pháp dài, quy trình và dự án để ở trang chuyên biệt.
- Mỗi section dùng một tiêu đề chính rõ ràng. Không thêm eyebrow, gạch chân, nhãn nhỏ hoặc họa tiết chỉ để trang trí nếu chúng chiếm không gian mà không bổ sung thông tin hay hành động.
- Danh mục sản phẩm trang chủ nằm trên một hàng ngang và cuộn ngang khi thiếu chỗ; không tự xuống thành nhiều hàng làm kéo dài trang. Ẩn thanh scrollbar thị giác, nhưng phải giữ kéo chuột/trackpad và vuốt cảm ứng hoạt động.
- Trang chủ product-first hiện không render Hero; không tự bật lại banner/slider Hero nếu chưa có yêu cầu mới từ chủ project.
- Header không có mục Trang chủ; logo là đường dẫn về trang chủ. Nhãn public của `/ve-chung-toi/` là “Giới thiệu”, không đổi URL.
- Điều hướng chính desktop giữ nút Danh mục sản phẩm và đúng 5 nhóm: Giới thiệu, Giải pháp, Dự án, Kiến thức, Hợp tác. Không đưa Tin tức, Đại lý hoặc Liên hệ trở lại thành mục top-level; chúng là nội dung con của Kiến thức/Hợp tác/Giới thiệu. Giữ các URL public cũ hoạt động để bảo toàn SEO và link đã chia sẻ.
- Hạn chế bo góc lớn. Card, khung ảnh, ô tìm kiếm và CTA dạng chữ chỉ bo nhẹ khoảng 2–5px; chỉ dùng hình tròn/pill cho icon hoặc trạng thái có lý do chức năng.
- Ưu tiên bề mặt phẳng trên toàn website: card nội dung, card sản phẩm, danh mục, bài viết và khối thông tin phải hòa vào nền section, không bọc thêm nền gần giống nền trang, border, gạch phân cách, bo góc hoặc shadow chỉ để trang trí. Tạo phân cấp bằng khoảng trắng, lưới, typography và ảnh; chỉ giữ border cho form control, bảng dữ liệu, focus, trạng thái chọn hoặc ranh giới có ý nghĩa thao tác.
- Breadcrumb không có border/gạch chân dưới. Các hub/trang con cũng tuân thủ quy tắc một tiêu đề chính, không eyebrow, đường kẻ hay dấu trang trí thừa như trang chủ.
- Không lặp lại cùng một thông điệp ở nhiều section. Mỗi khối phải phục vụ một mục tiêu cụ thể: tìm danh mục, xem sản phẩm, xác nhận thương hiệu, đọc tin hoặc chuyển đổi.
- Nội dung marketing có khả năng thay đổi phải lấy từ dữ liệu đang quản lý trong Admin; cần fallback an toàn khi chưa nhập dữ liệu.
- Khi tham chiếu website khác, học nhịp nội dung và khả năng bán hàng nhưng giữ nhận diện, dữ liệu thật và hành vi của Phúc Thịnh Solar; không sao chép máy móc.
- Giao diện phẳng không đồng nghĩa với trang trắng tối giản và thiếu điểm nhấn. Trang dài phải có ảnh sản phẩm, ảnh ứng dụng hoặc banner chiến dịch có mục đích để tạo nhịp; mọi vị trí marketing phải quản lý được từ Admin và có công tắc Hiển thị, không dùng viền/card rỗng để bù cho thiếu nội dung.
- Ảnh sản phẩm dùng khung vuông 1:1 và `object-fit: contain`; logo/chứng nhận cũng dùng `contain`, không crop. Ảnh tin tức 16:9, dự án 4:3, ảnh nội dung/đại lý/liên hệ 8:5 hoặc tỷ lệ được ghi rõ tại field Admin; ảnh campaign catalog dùng 16:5. Không kéo giãn, không phóng ảnh nhỏ vượt kích thước tự nhiên, không chèn chữ trực tiếp vào ảnh khi frontend đã render chữ.
- Ảnh cover phải giữ chủ thể trong vùng an toàn trung tâm 70–75% để crop responsive không che/cắt chi tiết quan trọng. Không dùng ảnh AI/stock làm bằng chứng khách hàng, dự án, chứng nhận hoặc năng lực thực tế; các vị trí đó chỉ dùng dữ liệu có thể xác minh.
- Trang Giải pháp và bài Kiến thức dùng CKEditor cho nội dung biên tập dài với H2/H3, đoạn văn, danh sách, bảng và ảnh; không tái sử dụng composer khối dành cho mô tả sản phẩm. Hero, điều hướng, sản phẩm/dự án liên quan, CTA/form và dữ liệu có quan hệ phải do template/Admin field chuyên trách render, không chép cứng vào CKEditor.
- `Giải pháp` và `Danh mục sản phẩm` đều có landing page truy cập được. Menu con là lối tắt, không được biến nhãn cha thành trigger hover-only; trên mobile phải có lối `Tất cả giải pháp`/`Xem tất cả sản phẩm`. Card/menu Giải pháp ưu tiên đi thẳng landing nội dung, không bắt người dùng qua trang nhóm trung gian khi nhóm chỉ có một nội dung đại diện.

Báo cáo cho chủ project bằng tiếng Việt: kết quả, file thay đổi, test, rủi ro còn lại, deploy và rollback.
