# QA giọng văn corporate — legacy-public 100, 101, 103, 108, 109

## Định hướng chung

- Viết ngắn, rõ và có bối cảnh; phân biệt bài tin/ghi nhận lịch sử với bài giới thiệu thương mại.
- Giữ ngày, tên tổ chức, slug, liên kết và hình ảnh nguồn khi có thể; không biến nội dung cũ thành cam kết hiện tại.
- Claim về độc quyền, phân phối, bảo hành, chứng nhận, hiệu suất và dịch vụ phải có nguồn/điều kiện cụ thể. Khi thiếu bằng chứng, dùng nhãn “theo thông báo lưu trữ” và đặt cờ kiểm duyệt.
- Không dùng viết hoa toàn bộ, lặp từ khóa SEO, số liệu trang trí, ROI bảo đảm hoặc lời hứa dịch vụ chưa có hợp đồng.

## Quyết định theo bài

ARTICLE-100, ARTICLE-101, ARTICLE-103 và ARTICLE-109 giữ trạng thái **HOLD** cho đến khi hoàn tất xác minh nguồn/chứng cứ tương ứng; không sync hoặc publish trong trạng thái này. ARTICLE-108 ở trạng thái REVIEW vì cần đối chiếu chính sách đối tác hiện hành.

### ARTICLE-100 — Sharp

Đổi title khỏi khẳng định “phân phối độc quyền” hiện tại. Giữ đây là thông báo năm 2024; mọi thông số, giá, bảo hành và quyền phân phối cần đối chiếu theo model và văn bản Sharp trước khi publish.

### ARTICLE-101 và ARTICLE-109 — Blue Carbon

Hai bài gần như cùng một chủ đề. Không để hai URL cùng làm trang SEO khẳng định độc quyền. Cần xác minh thông báo/chứng nhận, chọn một canonical lịch sử có chứng cứ tốt hơn, sau đó redirect hoặc noindex bài còn lại.

### ARTICLE-103 — chính sách điện mặt trời mái nhà

Đặt mốc tháng 6/2023 ngay phần mở đầu. Đây là bài lưu trữ về phát biểu/đề xuất chính sách, không phải tư vấn pháp luật. Nếu không truy được nguồn xuất bản gốc và ngày chính xác, giữ trạng thái hold.

### ARTICLE-108 — hợp tác

Nêu những nhóm lợi ích có thể được giới thiệu nhưng không biến thành nghĩa vụ chung. Bắt người đọc kiểm tra báo giá, hợp đồng, bảo hành, giao hàng, kỹ thuật và nghiệm thu bằng văn bản; không nêu ROI bảo đảm.

## Checklist trước khi xuất bản

- [ ] Biên tập viên xác minh nguồn và ngày cho các claim lịch sử.
- [ ] Legal/owner duyệt mọi từ “độc quyền”, “chính hãng”, “bảo hành”, “chứng nhận” và phạm vi dịch vụ.
- [ ] Quyết định canonical/redirect cho ARTICLE-101 và ARTICLE-109.
- [ ] Xác nhận quyền sử dụng và credit của từng ảnh, tài liệu scan, liên kết ngoài.
- [ ] Không đồng bộ DB, publish hoặc deploy từ batch draft này.

## QA fact-check bổ sung

- ARTICLE-103: sự kiện được xác minh là ngày 10/06/2023 theo [Cổng Thông tin điện tử Chính phủ](https://www2.en.baochinhphu.vn/khan-truong-hoan-thien-co-che-khuyen-khich-lap-dat-dien-mat-troi-mai-nha-102230610205935993.htm). Có thể ghi ngày này trong bản lưu trữ, nhưng không dùng làm tư vấn pháp luật hiện hành.
- ARTICLE-100: [Sharp Việt Nam](https://vn.sharp/) không xác nhận claim phân phối độc quyền PV của Phúc Thịnh Solar; giữ HOLD nếu thiếu giấy ủy quyền.
- ARTICLE-101/109: [Blue Carbon FAQ](https://bluecarbon.solar/faq/) chỉ nêu khả năng cấp quyền phân phối độc quyền theo hợp đồng vùng, không xác nhận Phúc Thịnh Solar; giữ HOLD nếu thiếu giấy ủy quyền.
- ARTICLE-109: không chốt năm 2022 dựa vào ngữ cảnh COP26 hoặc đường dẫn media; chỉ ghi năm/ngày khi có chứng cứ trực tiếp.
