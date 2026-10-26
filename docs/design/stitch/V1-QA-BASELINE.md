# V1 — Fidelity QA baseline (26/10/2026)

**Kết luận: V1 chưa nghiệm thu. Không triển khai V2, không merge/deploy.**

## Phần đã thực hiện
- Khởi tạo Stitch Canvas private: https://stitch.google.com/projects/5456517467698612459
- Chụp **16 screenshot nguồn tiếng Việt** của 8 loại trang (desktop 1280x900 và mobile 390x844), trigger scroll để tránh thiếu ảnh lazy.
- 16/16 URL trả về HTTP 200, DOM lang=vi; manifest tại .stitch/qa/manifest.json.
- **16/16 ảnh nguồn đã được upload vào Stitch**. Danh sách link trong SCREEN-INVENTORY.md.
- Sinh 2 bản UI Stitch V1 dự thảo của homepage (desktop, mobile) từ ảnh tiếng Việt.
- Trích 8 HTML nguyên bản bằng HTTP từ production, thêm base href tới domain nguồn; hiện lưu tại .stitch/raw-vi-base, không sửa production.
- Upload bản HTML homepage có base URL vào Canvas để so sánh với bản AI sinh.

## Điểm chưa đạt / chưa được kiểm chứng
1. Screenshot qua Stitch CLI capture browser bị Google/Chrome dịch sang tiếng Anh (DOM: lang=en, class=translated-ltr); tuyệt đối không lấy làm chuẩn text.
2. Bản V1 sinh bằng AI **chưa đạt chứng nhận 1:1**; có sự lệch tỷ lệ trang (source desktop 1280x6208, Stitch desktop 2560x9826; source mobile 390x9650, Stitch mobile 780x13626). So sánh phải quy chuẩn tỷ lệ và kiểm tra chi tiết từng section.
3. HTML clone tải bằng file:// hiển thị hero, grid và typography gần với nguồn, nhưng **một số icon/font không hiện**; cần inline font assets/SVG hoặc cấu hình CORS đúng.
4. Stitch cảnh báo 73 local/relative assets trên HTML homepage dù đã thêm base URL. Cần kiểm tra và xử lý asset trước khi gọi clone pixel-perfect.
5. Hero là carousel 4 ảnh; trước khi screenshot diff, buộc cùng slide (01/04) và trạng thái hover/scroll để tránh nhận nhầm sai khác.
6. Bộ ảnh có một số img không đặt src ban đầu (placeholder/lazy/hidden). Không được suy luận là HTTP 404 khi chưa xác minh network.
7. Menu hamburger mobile chưa được chụp đúng: bản chụp state hiện tại chỉ kích hoạt ô **tìm kiếm**; cần định vị đúng nút hamburger và menu chính.
8. Chưa audit trạng thái lọc, phân trang, tab, form, cart, CTA, breadcrumb, floating buttons; desktop/mobile cần nghiệm thu riêng.
9. 8 ảnh màn hình Stitch source reference không tương đương 8 bản thiết kế clone; hiện 2 bản UI V1 homepage là bản **dự thảo**.

## Định nghĩa DONE cho V1
- Mỗi loại trang desktop + mobile có: screenshot thật VI (source), màn thiết kế clone, ghi nhận QA side-by-side tại cùng viewport, checklist đầy đủ sections/text/assets/typography/layout/color.
- Hero slide/crop, logo, danh mục, ảnh sản phẩm dùng nguồn thật, không AI sáng tác.
- Các state header, search, menu, filter, gallery/tab, footer/CTA có màn tham chiếu tương ứng.
- Không thiếu ảnh/logo/icon/font, không tiếng Anh hoặc giá/sản phẩm/hành vi giả.
- Sẵn sàng handoff sau khi cập nhật tỷ lệ/spacing/cảm giác layout và đánh dấu QA PASS cho các màn quan trọng.

## Tiếp theo theo thứ tự
1. Sửa asset/font + chuẩn hóa HTML tham chiếu Việt trên Stitch.
2. Clone hoàn chỉnh trang category, all-products, PDP và global navigation, desktop/mobile; QA trước khi nhân rộng.
3. Clone giải pháp, dự án, kiến thức, giới thiệu, các form và states.
4. Nghiệm thu V1 và khóa/đặt immutable reference.
5. **Sau đó mới** duplicate sang V2 để nghiên cứu 2–3 hướng nâng cấp gold/navy, vuông vức bo nhẹ, motion tinh tế.

Chỉ phục vụ thiết kế độc lập: worktree feature/pts-stitch-design-baseline-20261026. Không thay đổi master PHP, Nuxt migration hoặc production.

## Cập nhật QA định lượng — 26/10/2026

- Đã sửa lỗi font icon Font Awesome khi chạy bản clone từ file local: các font cần thiết được inline vào HTML, xác minh không còn requestfailed đối với 3 font icon. Tác vụ script /api/addons.php vẫn có thể bị CORS ngoài domain thật; vì vậy tương tác JavaScript chưa thể được xem là đạt.
- Đã kiểm tra đối chiếu website live với HTML V1 asset-fixed cho 8 loại trang, 2 viewport (1280×900, 390×844).
- **16/16 cặp đối chiếu:** thứ tự và nội dung H1/H2/H3 khớp; x/y/width/height của từng heading khớp 0 px; chiều cao toàn trang khớp 0 px. Dữ liệu raw: .stitch/qa/fidelity-dom.json, báo cáo in được bằng .stitch/qa/fidelity_summary.py.
- **Không suy luận** từ DOM QA rằng mọi ảnh, trạng thái hover, hiệu ứng, tương tác và tất cả pixel giống nhau; vẫn phải QA screenshot/interaction từng state trước nghiệm thu V1.
- Đã lấy ảnh menu hamburger thực mobile tại .stitch/qa/home-mobile-hamburger-vi.png, khác với ảnh trạng thái tìm kiếm trước đó.
- Đã xác minh việc chuyển relative asset thành absolute URL và inline icon-font giúp import HTML V1 homepage vào Stitch với warnings=0.
