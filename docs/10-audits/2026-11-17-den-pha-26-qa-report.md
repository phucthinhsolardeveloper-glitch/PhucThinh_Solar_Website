# QA ảnh đèn pha 26 mã · 17/11/2026

## Kết quả thực tế
- Phạm vi production được đọc: 72 đèn pha, đúng 26 mã chỉ có một ảnh; không kiểm tra ảnh Shopping, không thay đổi database hoặc upload.
- Đã khôi phục từ nguồn EMF nội bộ các mẫu ORIANA DB-760 / DB-7300 / DB-7500 / DB-71000 bằng Windows GDI+; tạo 4 ảnh PNG có alpha (đề xuất nâng cấp ảnh chính) và 4 ảnh **chi tiết LED được cắt từ file EMF**, nhãn model rõ, chưa gắn gallery.
- Đã tách nền ảnh thật đèn chóa JINDIAN JD-6128 từ file nội bộ 1620×1080; đây là ứng viên bổ sung gallery tốt nhất về độ phân giải nguồn.
- Đã lấy 6 ảnh chính hãng BCT-WR1.0 và 6 ảnh chính hãng BCT-WR4.0 để đối chiếu; tạo 5 ứng viên từ hãng gồm nhiều góc nhìn, ảnh mặt sau, bản vẽ kích thước; các ảnh hãng 720×720 nên còn giới hạn độ nét. Quyền tái sử dụng cần được xác minh.
- Đã tìm ảnh JD-V200/JD-V300 1080×1080 ở đại lý Sunny Solar nhưng giống góc ảnh hiện tại và có logo bên thứ ba; giữ reference, không tự đăng.
- **BLOCK:** JD-18300.emf hiện rõ chữ **JD-18200 / 200W**; ảnh JD-18300/10.png và /11.png hiện rõ **JDE-8300**; các ảnh này là ảnh sai SKU nên bị loại. Các ảnh 6, 1, 4 và 9 trong thư mục JD-18300 cũng có ngoại hình không khớp 6 cụm LED của sản phẩm live.
- **SOURCE MỚI (CHỈ THAM KHẢO):** Ảnh chụp thật JD-18300 300W 750x750 của nhà phân phối Hoàng Quốc Bảo có nhãn JD-18300 / 300W, giống mặt đèn 6 cụm trên website; đã tách nền làm ứng viên nhưng **chưa được phép đăng** vì chưa xác minh quyền sử dụng và độ nét gốc chỉ 750px. Nguồn: https://www.hoangquocbao.com/hoanghung/29/images/hinh-thuc-te-jd-18300-2.webp
- **BLOCK:** BCT-WR1.0/4.png và BCT-WR4.0/8.png từ pricebook có số cụm/chip LED khác ảnh live, nên không được dùng tự động.
- Nhóm DB-9xx có ảnh `*_2.png` giống nhau giữa nhiều model, ảnh nguồn chỉ 481×321, phải xác minh nhãn mã trước khi dùng.
- Nguồn catalog công suất/bảo hành ở một số nhóm có mâu thuẫn; batch ảnh **không gắn thông số mới** lên ảnh.

## Tài nguyên
- [Báo cáo xem trước 26 SKU](2026-11-17-den-pha-qa-batch-1.html)
- [Danh sách CSV](2026-11-17-den-pha-26-image-qa.csv)
- Manifest file+SHA256: `backend/resources/product-gallery/2026-11-17/den-pha-qa-batch-1/candidate-alpha/manifest.json`
- Ảnh trong `candidate-alpha` và `detail-candidates`; nguồn `emf-converted` / `external-review-only`.
- Công cụ chạy: `scripts/content/render-emf-product-sources-20261117.ps1`, `scripts/content/build-den-pha-qa-batch-1-20261117.py`.

## Kiểm tra tiếp theo
1. Tách rõ ứng viên nội bộ và ảnh cần quyền hãng/đại lý. Chỉ đưa ảnh đã duyệt vào batch apply với mã SHA256 và backup.
2. Kiểm tra nguồn ảnh thực 1:1 của ORIANA và mã sản phẩm trên mặt đèn; ưu tiên xin ảnh studio gốc >1200px nếu có.
3. Đèn JD-18300: cần ảnh thật chụp JD-18300 300W từ nhà phân phối; không sử dụng tài liệu JD-18200/JDE-8300.
4. JD-V200/300: xin góc mặt sau, giá đỡ, remote thật thay vì nhân bản ảnh chính.
5. Đánh giá 16 SKU còn lại từ CSV; tách nhóm ánh sáng vàng có ảnh bằng chứng thực tế.

**STOP SHIP:** Chưa đăng hoặc chỉnh sửa production. Ảnh hãng và đại lý chỉ dùng đối chiếu nội bộ khi chưa xác minh giấy phép sử dụng.
