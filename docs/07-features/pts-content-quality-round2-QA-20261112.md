# PTS Solar — QA nội dung vòng hai (12/11/2026)

## Trạng thái được kiểm chứng trên CMS local

Snapshot `/home/admin01/orca/artifacts/PTS-CONTENT-REWORK-BASELINE-20261112.json`: **39 Post** = 33 article (12 công khai, 21 ẩn) + 6 solution (5 công khai, 1 ẩn).

- **21 bài Kiến thức PTS-KT-001–021:** đã cập nhật đầy đủ vào local bằng baseline SHA-256. Đối chiếu trực tiếp database: 21/21 body HTML khớp Markdown mới, summary và SEO description khớp; 21/21 vẫn ẩn; giữ 9 ảnh inline và 38 liên kết sản phẩm. Bài 001–003 yêu cầu opt-in riêng và kiểm tra ảnh/link/bảng trước khi cập nhật.
- **12 bài cũ đang công khai:** có 11 bản soạn lại ở `backend/resources/editorial/2026-11-12/legacy-public/`, còn ARTICLE-107 không tạo body mới vì trùng hoàn toàn với 102 và cần quyết định canonical. **Chưa ghi đè nội dung 12 bài đang công khai.**
- **5 trang giải pháp công khai:** có 5 bản thảo mới trong `solutions/`; chưa ghi đè CMS. Tên menu cho slug BESS đã đổi từ “Lưu trữ điện” thành **“Pin lưu trữ”** trên local và API, slug và tên đầy đủ không đổi.
- Full suite chạy sau tích hợp migration và CLI: **397 passed, 1 skipped (2.718 assertions)**. Test riêng cho đồng bộ 001–003 sau đó: **3 passed (29 assertions)**. Chưa push hoặc deploy production.

## Quyết định từng bài công khai

`HOLD` = chưa duyệt việc thay bài cũ bằng bản biên tập mới; **không có nghĩa bài đang công khai đã bị ẩn**.

| ID | Trạng thái bản biên tập mới | Lý do và hành động |
|---|---|---|
| 100 | HOLD | Claim “độc quyền Sharp” cần giấy ủy quyền/phạm vi/thời điểm; đã soạn dưới góc tin lịch sử |
| 101 | HOLD | Blue Carbon cùng chủ đề 109; quyền phân phối và ảnh chưa được xác minh |
| 102 | REVIEW | Hướng dẫn chọn đèn theo nhu cầu; phải kiểm tra canonical 102/107 và ảnh |
| 103 | HOLD | Có nguồn Chính phủ về sự kiện 10/06/2023; cần kiểm tra bản gốc và quyền ảnh; không phải văn bản pháp luật hiện hành |
| 104 | REVIEW | “TOP 4” đổi thành bốn câu hỏi theo vị trí lắp; chờ duyệt nguồn/ảnh |
| 105 | REVIEW | Hướng dẫn vận hành an toàn, bỏ các cam kết thời lượng chung không nguồn |
| 106 | HOLD | Blue Carbon cho dự án: thiếu chứng cứ model, bảo hành và quyền ảnh |
| 107 | HOLD_MERGE | Body cũ trùng **100%** với 102; không tự phát hành hai bài giống nhau; chưa quyết định 301 |
| 108 | REVIEW | Hợp tác: cần chính sách dịch vụ và bảo hành hiện hành, tránh cam kết chưa có hợp đồng |
| 109 | HOLD | Trùng chủ đề 101; claim độc quyền và ảnh/tài liệu cần xác minh |
| 110 | HOLD | Loại “CHIP LED CREE TOP 1”; phải kiểm chứng chip đúng model và ảnh chứng nhận |
| 111 | REVIEW | Chuyển sang phân tích tổng chi phí sở hữu, tách ý định khỏi 104 |

12 bài công khai cũ không có liên kết nguồn tham khảo bên ngoài được xác thực trong thân bài gốc; việc liên kết các ảnh trên website cũ không thay thế xác minh thông tin. Không dùng chính website công ty làm nguồn để xác thực tuyên bố thương mại của công ty. **Ghi nguồn không tự động cấp quyền sao chép nguyên văn hoặc tái sử dụng ảnh.**

## Trang giải pháp đã có bản thảo

| ID | Phạm vi | Trọng tâm nội dung |
|---|---|---|
| 61 | Điện mặt trời doanh nghiệp | Phụ tải ban ngày, mái, đấu nối, mục tiêu đầu tư và giới hạn dự phòng |
| 132 | Điện mặt trời gia đình | Giờ dùng điện, hiện trạng mái, nhu cầu lưu trữ/tải thiết yếu |
| 133 | Pin lưu trữ & BESS | kW/kWh, tải ưu tiên, BMS/PCS, tương thích và an toàn |
| 134 | Chiếu sáng năng lượng mặt trời | Lumen/lux, vùng sáng, độ đồng đều, nắng và bảo trì |
| 135 | Bơm nước năng lượng mặt trời | Cột áp/lưu lượng, mùa tưới, nguồn nước, bảo vệ chạy khô |

Nhóm giải pháp là **landing page**, không nên viết theo template “bài giải thích + checklist + kết luận” giống bài kiến thức. Giữ bản nháp để duyệt thực tế trước cập nhật public.

## QA hình ảnh và khả năng trình bày

- 21 ảnh bìa mới trong `frontend/public/assets/images/editorial/` đều hiện hữu ở 1600×900 WebP. Kiểm tra mẫu các đường dẫn ảnh Nuxt cho HTTP 200.
- Sáu ảnh `pts-plan-016.webp` đến `pts-plan-021.webp` đang dùng làm bìa, có dung lượng 46–51 KiB; cần kiểm duyệt thiết kế bằng mắt trước khi công khai. Không đồng nhất tất cả thành ảnh AI kiểu infographic. Ưu tiên ảnh thật chất lượng và đa dạng ảnh dự án, thiết bị, ứng dụng, sơ đồ/biểu đồ khi nội dung cần.
- Ảnh có nguồn ngoài: cần quyền sử dụng + caption/nguồn chính xác; ảnh tự biên soạn ghi “Hình minh họa”. Không biến ảnh quảng cáo thành bằng chứng chứng nhận hay hiệu suất thiết bị.

## Gate an toàn và việc tiếp theo

1. Rà chính xác số liệu/nguồn, đặc biệt Sharp/Blue Carbon/Cree LED và tin chính sách lịch sử; xác nhận quyền ảnh.
2. Kiểm tra Search Console trước khi 301/canonical các cặp 102–107 và 101–109.
3. Chỉ khi một bài công khai được **approved** kèm nguồn/ảnh hợp lệ mới dùng `pts:content-rework-apply` với `--ids` trên local; CLI fail-closed khi nguồn chưa qua gate hoặc baseline drift. Không chạy trên production.
4. Xem preview/biên tập ảnh 016–021, duyệt khác biệt các bài trong cùng series; giữ liên kết sản phẩm và không thay lịch/trạng thái xuất bản.
5. Sau khi duyệt trên local, mới chuẩn bị kế hoạch deploy riêng; **chưa push GitHub, chưa deploy website production**.

Nguồn kỹ thuật/biên tập và proposal: `docs/07-features/editorial-voice-and-review-standard.md`, `backend/resources/editorial/2026-11-12/reviews/`, `backend/resources/editorial/2026-11-12/legacy-public/`, `backend/resources/editorial/2026-11-12/solutions/`.
