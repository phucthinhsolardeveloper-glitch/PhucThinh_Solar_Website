# Stitch V1/V2 — tiến độ 26/10/2026

Project riêng tư: https://stitch.google.com/projects/5456517467698612459

## V1 — Visual clone baseline PASS
- HTML từ 11 mẫu trang hiện có (tiếng Việt), import Stitch thành công **11/11, warnings=0**.
- 22 screenshot source (desktop/mobile) và các state mobile menu được lưu lên Stitch.
- DOM QA live vs local clone **22/22**: heading content/x/y/size và page height khớp.
- Screenshot first-fold QA live vs final local clone **22/22 pixelmatch difference=0 tại threshold 0.15**.
- CORS của /api/addons.php khi chạy clone ở origin khác vẫn cần xử lý nếu muốn dùng làm ứng dụng thật; không phải vấn đề đổi giao diện tĩnh.
- Không khẳng định chức năng hay full-page pixel-perfect khi chưa test mọi state.
- Bản V1 gốc giữ nguyên để so sánh.

## V2 — Thiết kế nghiên cứu
- Hai concept được xác định trong V2-CONCEPT-BRIEF.md:
  A. Technical Premium — Precision Industrial
  B. Calm Commerce — tinh giản, ưu tiên tìm/đọc sản phẩm
- Đã sinh 2 màn V2 desktop: [Concept A](https://stitch.google.com/projects/5456517467698612459?node-id=a136d61bf2a24449b17709c8c4d81887) và [Concept B](https://stitch.google.com/projects/5456517467698612459?node-id=743a02bce7244d618bd529ea6515571d).
- Đã audit nội dung: A giữ nhiều section hơn nhưng dùng ảnh/logo không chính chủ; B có thiếu section/logo giả/mốc năm sai.
- Đã tạo [Concept A đã sửa tài sản thật](https://stitch.google.com/projects/5456517467698612459?node-id=8829550416884793261): logo gốc đầu/cuối trang và 10 ảnh từ chính website.
- Đã bổ sung V2 A homepage mobile, danh mục đèn NLMT và PDP JD-18500. Các bản này đã được sửa ảnh/logo theo website nguồn, làm sạch dữ liệu tự sinh và QA local static; vẫn là **DRAFT**, chưa phê duyệt chức năng/nội dung toàn diện.
- Xem V2-CONCEPT-REVIEW.md và V2-QA-REVIEW.md để mở đúng màn đã chỉnh, nắm các gate còn mở; giữ nguyên V1 gốc.

## Nguồn và kỹ thuật
- Repo worktree: /home/admin01/src/pts-stitch-design, branch feature/pts-stitch-design-baseline-20261026.
- Ảnh/HTML/QA JSON trong .stitch/ được ignore Git; docs nằm tại docs/design/stitch/.
- Tham khảo SCREEN-INVENTORY.md, V1-QA-BASELINE.md, ASSET-MAP.md và V2-CONCEPT-BRIEF.md.
- Không deploy, không merge master.
