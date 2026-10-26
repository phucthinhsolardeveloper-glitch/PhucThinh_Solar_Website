# Phúc Thịnh Solar — V2 Technical Premium QA review (26/10/2026)

**Trạng thái: DESIGN DRAFT / chưa approved, chưa triển khai Nuxt, chưa deploy.**

## Screen đã tạo
- [V1 HTML source-checked homepage](https://stitch.google.com/projects/5456517467698612459?node-id=13025497894490543111) — mốc trước redesign.
- [V2 A Homepage dùng logo/ảnh thật](https://stitch.google.com/projects/5456517467698612459?node-id=8829550416884793261) — concept desktop.
- [V2 A Category draft sạch dữ liệu](https://stitch.google.com/projects/5456517467698612459?node-id=8737064375000549055).
- [V2 A Product detail draft sạch dữ liệu](https://stitch.google.com/projects/5456517467698612459?node-id=4233403115605725522).
- [V2 B Calm Commerce — REFERENCE ONLY](https://stitch.google.com/projects/5456517467698612459?node-id=743a02bce7244d618bd529ea6515571d). B thiếu nhiều section và đổi nội dung, không chọn làm nền.
- [V2 A Homepage Mobile — bản làm sạch nội dung, DRAFT](https://stitch.google.com/projects/5456517467698612459?node-id=17099233929460557533).

## Những gì đã thực hiện và có thể kiểm chứng
- Sinh V2 từ V1 đúng tiếng Việt theo hướng Technical Premium / Precision Industrial.
- Phát hiện và thay URL ảnh do Stitch tự tạo bằng ảnh sản phẩm / hình minh họa chính thức trên phucthinhsolar.com.
- Category: 17/17 img dùng nguồn thật, sửa các mã và công suất sai (JD-18500, JD-6128, Blue Carbon BCT-WW3.0/WW2.0).
- Product detail: 9/9 img nguồn thật, thay logo brand, bỏ giá 1.758.000đ và những giá gợi ý không xuất hiện trong HTML nguồn.
- Mobile homepage: 13/13 img nguồn thật, thay logo chính, xóa một số chứng nhận/hiệu suất/SLA/kho không có bằng chứng; phục hồi mô tả chung.
- Thực hiện giai đoạn source-check thêm 33 thay đổi câu chữ (category), 42 (PDP), 23 (mobile), theo .stitch/qa/v2-sanitization-log.json.
- Playwright QA static render tại local: desktop 1280×900 cho category/PDP; mobile 390×844 homepage. Không ảnh hỏng; documentWidth = viewportWidth, không scroll ngang; không phát hiện pageerror trong lượt kiểm tra. Log tại .stitch/qa/v2-runtime-source-checked-qa.json.
- Những bản import thành công có warnings=[] từ Stitch. Không có thay đổi production / Nuxt.

## Dữ liệu product JD-18500 đã đối chiếu từ V1
- Tên: Đèn Pha Năng Lượng Mặt Trời JINDIAN JD-18500 (500W).
- Tấm pin: 6V/32W MONO; pin: Li-ion 3.2V 30.000mAh.
- Nguồn sáng: 756pcs SMD 5730; kích thước đèn: 400×420mm.
- Nhiệt độ màu: 3000K–6500K; thời gian chiếu sáng: 10–15 hours.
- Bảo hành: 2 năm.
- **Quang thông, chiều cao lắp đặt, thời gian sạc: đang cập nhật** trên website nguồn; không tự suy diễn thông số.
- **Giá và tồn kho: chưa xác minh từ nguồn**, phải lấy dữ liệu catalogue/CRM thực tế; prototype dùng “Liên hệ báo giá” hoặc “Xác nhận tồn kho”.

## QA vẫn còn mở (gate bắt buộc trước implementation)
1. **Không được coi prototype là production-ready.** Các link demo dùng `#` (category 13/18; product detail 16/30; homepage mobile 6/36); form, tìm kiếm, lọc, giỏ hàng, phân trang chưa nối API/logic.
2. Copy V2 vẫn phải qua một vòng review đầy đủ với product source để loại bỏ tất cả claim marketing chưa chứng minh được (CO/CQ, IP rating, thời gian sạc, số lượng hàng, giá/chiết khấu, chứng nhận, khả năng vận hành, cam kết bảo hành).
3. Các thông số hiển thị phải có provenance theo model; riêng danh mục không áp thông số mẫu chung cho mọi sản phẩm.
4. Màn Stitch homepage mobile và PDP chỉ là **thiết kế draft**; chưa test responsive 320/360/768/1024, full-page screenshot diff, focus/tab keyboard và interaction states.
5. Thống nhất header/nav/footer/CTA/công thức card trên V2 A; screenshot hiện cho thấy category header còn chật 1280px và một số CTA ngắt dòng.
6. Kiểm tra tỉ lệ ảnh, crop, đúng model và ảnh sản phẩm tương ứng SKU; không dùng ảnh tự sinh.
7. Handoff Nuxt chỉ sau khi chọn direction, chốt tokens, lập UI components + states và có checklist QA; không sửa nhánh Nuxt bằng task thiết kế này.

## Quy tắc workflow
- V1 baseline bất biến; V2 A/B độc lập trên Stitch cùng Canvas.
- Worktree: /home/admin01/src/pts-stitch-design; branch feature/pts-stitch-design-baseline-20261026.
- HTML/ảnh/QA source ở .stitch/ được ignore Git, tài liệu review ở docs/design/stitch/.
- **Không merge main/master và không deploy sản phẩm** từ worktree này.
