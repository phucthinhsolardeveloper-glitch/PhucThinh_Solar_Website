# Giải pháp — First-fold UX slice (24/10/2026)

## Mục tiêu
Ưu tiên cho người đọc nhìn thấy các nhóm giải pháp sớm hơn trên mobile. Giữ brand, dữ liệu Admin, SEO, đường dẫn và form liên hệ đang chạy. Không rebuild/deploy production.

## Thay đổi
- Trong service_list_tpl.php: giữ toàn bộ mô tả trong DOM bằng <details open> khi không có JavaScript, chỉ đóng ở <=800px bằng progressive enhancement; người dùng có thể mở "Xem mô tả đầy đủ".
- Tập trung primary navigation vào liên kết anchor tới nhóm lựa chọn thay vì lặp CTA tư vấn đầu trang; CTA tư vấn vẫn tồn tại trong band liên hệ dưới trang.
- Trong solution-hub.css: chỉnh outline/focus, chiều cao card theo container, thay một số gradient trang trí bằng gold hiện hành.
- Sau review độc lập đã sửa bug CTA "Khám phá các giải pháp" bị chữ trắng trên nền trắng; giờ chữ navy có underline. Bổ sung regression assertion về contrast CSS.

## QA kiểm được
Browser simulation *read-only* trên HTML public ngày 24/10, ghép DOM tương đương và CSS source từ worktree để xem trước:
- 390px: card giải pháp đầu tiên top từ ~723px xuống ~339px; đoạn giới thiệu vẫn tồn tại trong disclosure.
- 1440px: card top từ ~518px xuống ~469px.
- 4/4 lần tải HTTP 200; không có horizontal overflow ở 390/1440 trong viewport đo.
- Screenshot trước/sau + review.json: artifacts/solution-review-20261024/. Đây là mô phỏng trên live public HTML, không phải QA local PHP build.
- Test mới: tools/visual-tests/tests/solution-journey-cohesion.spec.js kiểm tra viewport 390/768/1440/1920, text vẫn tồn tại, summary, link màu navy, grid overflow và anchor canonical. Test chưa có kết quả full local browser thực chạy trên phiên PHP sửa đổi.
- Cần chạy PHP lint tại môi trường có php-cli, cùng bộ regression frontend, kiểm tra JS disabled, modal/CTA, site map và content thực trước GO.

## Cần bổ sung từ các phòng ban
- Kinh doanh: map giải pháp -> 3-6 nhóm sản phẩm phù hợp và CTA intent.
- Marketing: biên tập lead text ngắn cho mobile được duyệt, ảnh có quyền sử dụng; bản hiện tại chỉ thay cách trình bày, chưa thay nội dung.
- Sếp: chốt ưu tiên phân phối thiết bị vs giải pháp thi công và visual tone. Không tạo claims giả.

## Rollback
Revert commit nhánh solution nếu chưa đủ nghiệm thu. Không cập nhật production cho đến khi QA có staging/local và người phụ trách duyệt.
