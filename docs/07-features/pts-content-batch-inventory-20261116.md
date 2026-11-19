# Phúc Thịnh Solar — Inventory và kế hoạch biên tập hàng loạt (16/11/2026)

## Nguồn và nguyên tắc
- Đã đọc API public https://phucthinhsolar.com/_api/news-detail cho 33 slug đang có trên local; dữ liệu trong `content-public-snapshot-2026-11-16.json`. Snapshot chỉ là HTML *đã sanitize*, KHÔNG tương đương body raw DB; tuyệt đối không dùng SHA của snapshot làm baseline SQL.
- Production: 20 bài đang công khai (100–111, 136–143); 13 bài chưa công khai (144–156).
- Local: 12 bài cũ visible (100–111) + 21 bài mới hidden (136–156). 136,137,138 đã được biên tập production khác local, cần rebaseline trước bất kỳ lệnh sync.
- 20/20 bài public không có `linkedProducts` do biên tập viên gán; 17/20 không có internal link trong body; bốn bài có chưa tới ~350 từ theo tách từ bằng dấu cách (101,104,141,143). Frontend vẫn có khối sản phẩm gợi ý client-side theo từ khóa; không hiểu nhầm là không có tính năng đó.
- Chỉ sửa `body`, `summary` và SEO description/title có duyệt; KHÔNG thay title, slug, published_at, visibility, image, products/category. Giữ bản sửa Admin mới nhất. Ảnh/tên doanh nghiệp/tài liệu thương mại phải duyệt thủ công.

## Danh sách 20 bài đang công khai

| ID | Nhóm | Ưu tiên | Việc cần làm |
|---:|---|---|---|
| 100 | Thông báo Sharp | P0 | Đối chiếu tài liệu ủy quyền; loại claim độc quyền chưa xác minh; xem xét bản lưu trữ |
| 101 | Blue Carbon thông báo | P0 | Kiểm tra pháp lý/ủy quyền, gần trùng 109; HOLD |
| 102 | Đèn NLMT nên chọn | P1 | Đã có draft mới; nội dung hướng chọn theo tình huống, kiểm tra trùng 107 |
| 103 | Tin chính sách cũ | P0 | Gắn ngày nguồn gốc, không dùng như chính sách hiện hành |
| 104 | 4 lợi ích đèn NLMT | P1 | Đã có draft mới; định vị mỗi lợi ích với nơi phù hợp/không phù hợp |
| 105 | 4 lưu ý đèn NLMT | P1 | Đã có draft mới; sửa claim 8h, an toàn khi vệ sinh/lắp |
| 106 | Đèn Blue Carbon dự án | P0 | Đối chiếu model, bản quyền hình, chất lượng dữ kiện; HOLD |
| 107 | Đèn NLMT lý do bạn nên chọn | P0 | Nội dung gần như giống 102; HOLD quyết định canonical/301 sau khi có GSC |
| 108 | Hợp tác đại lý | P0 | Kiểm tra chính sách thương mại hiện hành và nguồn minh chứng |
| 109 | Blue Carbon độc quyền | P0 | Kiểm tra ủy quyền và trùng 101; HOLD |
| 110 | Chip CREE TOP 1 | P0 | Có draft thay claim TOP1 nhưng chưa xác minh cấu hình/ảnh; HOLD |
| 111 | 8 lý do đèn NLMT | P0 | Có draft mới thành bài toán tổng chi phí, bỏ 'an toàn tuyệt đối 100%' |
| 136 | Nguyên lý điện mặt trời | P2 | Bản production mới hơn local; giữ sơ đồ, bổ sung link ngữ cảnh và hình nghiệm thực tế |
| 137 | Đọc datasheet LONGi | P1 | Bản production đã chỉnh; giữ dữ liệu LONGi được đối chiếu, thêm ví dụ chọn model và link sản phẩm được duyệt |
| 138 | Xu hướng IEA PVPS 2026 | P2 | Bản production đã chỉnh; rà lại nguồn, cách dùng số liệu và góc nhìn Việt Nam |
| 139 | kW/kWp/kWh | P2 | Thêm liên kết bài hóa đơn, ví dụ áp dụng có điều kiện |
| 140 | Inverter làm gì | P2 | Sơ đồ MPPT/AC/DC và sản phẩm đúng dòng |
| 141 | Nhà dùng điện buổi tối | P1 | Bổ sung biểu đồ lịch tải giả định, bảng 3 kịch bản và link 142 |
| 142 | Hòa lưới/hybrid/độc lập | P1 | Thêm sơ đồ backup/lưới, không hứa mất điện vẫn có toàn bộ tải |
| 143 | Chọn đèn đừng nhìn W | P1 | Thêm bảng watt/lumen/lux/Wh có điều kiện; gắn mẫu đèn có chứng cứ |

## Sản phẩm biên tập đã tạo
- `backend/resources/editorial/2026-11-16/revisions/ARTICLE-{102,104,105,110,111}.md` — 5 bản mới từ Claude, được chỉnh nguồn thành liên kết Markdown đúng chuẩn và thay đường dẫn DOE cũ sang trang nguồn chính thức. 
- `backend/resources/editorial/2026-11-16/revisions/review-notes.json` — ghi chú vấn đề còn cần kiểm chứng. TẤT CẢ vẫn là draft, KHÔNG tự xác nhận QA approved.
- 13 bài hidden trên production không được tự bật chỉ để đạt chỉ tiêu 2–3 bài/ngày.

## Cửa kiểm duyệt trước khi cập nhật
1. Lấy baseline **raw** theo ID từ DB mục tiêu, đối chiếu SHA-256/body/summary/SEO, lock row trong transaction, kiểm tra drift.
2. Xem diff trước/sau với nguồn, link, ảnh, layout mobile và mục tiêu tìm kiếm; đánh giá >=24/30, điểm nguồn & tự nhiên >=4, không còn hard blocker.
3. Có xác nhận thật của owner / kỹ thuật / nội dung. Chỉ khi đủ mới tạo manifest approved để apply.
4. Snapshot backup private trước khi ghi; giữ nguyên URL, ngày và media; kiểm tra API public, sitemap và các liên kết sau deploy.
5. Không dùng schema migration cho việc sửa lời văn; dùng lệnh Artisan để thực hiện SQL UPDATE trong transaction. Không ghi đè production bằng local dump.

## Quyết định cần dữ liệu Search Console
- 102/107 và 101/109: chỉ gộp, noindex hoặc 301 khi đã đối chiếu impressions, clicks, queries, backlink, lead; không redirect đồng loạt bằng suy luận.
- 136/137/138: production đã sửa khác local (tương đồng chữ ~0.66/0.58/0.42), bắt buộc chọn bản live và không áp dụng bản nháp local cũ.
