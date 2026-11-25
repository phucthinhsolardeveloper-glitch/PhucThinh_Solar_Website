# Sổ lỗi dữ liệu sản phẩm (PIM) — 25/11/2026

Nguồn: dữ liệu public của web thật (sitemap + `/_api/product-detail`), chỉ đọc, 325/325 trang.
Tạo lại: `python3 -I tools/visual-tests/audit/pim-registry.py <file.csv>`.
Chi tiết từng sản phẩm: `pim-registry.csv`. **Chưa sửa dữ liệu nào.** Mỗi dòng là câu hỏi cần chứng từ, không phải giá trị đã sửa.

## Kết quả tự động

| Mã lỗi | Số sản phẩm | Ghi chú |
|---|---|---|
| `thong_so_trong` | 324 | Dòng thông số để trống trong dữ liệu. Nhiều nhất: Chiều cao lắp đặt 147, Quang thông 139, Công suất LED thực 65, Bảo hành 47, Loại biến tần 27. Chưa kiểm dòng trống có hiện ra giao diện hay không |
| `thieu_hang` | 9 | PTS-000354, PTS-000381, PTS-000391…396, JD-128L |
| `thieu_mo_ta` | 3 | Hai mã SolaX Aelio 49,9 kW / 60 kW và TRENE-P100B215 |
| `bao_hanh_nhieu_moc` | 1 | PTS-000381 (quạt Blue Fan 20W): 1 năm và 2 năm; có thể là bảo hành từng bộ phận |

Không phát hiện: thiếu mã sản phẩm; mã lệch trường "Model"; công suất trong tên lệch trường "Công suất danh định"; hiện giá mà không có giá; giá khuyến mãi không thấp hơn giá thường.
Các phép kiểm này chỉ so dữ liệu trong cùng một trang. **Chưa đối chiếu với datasheet của hãng**, nên không khẳng định thông số đúng.

## Phát hiện đọc tay (audit 25/11), cần chủ website xác nhận

| Việc | Chứng cứ cần |
|---|---|
| Sharp 550W: trang sản phẩm ghi 10 năm, đoạn chính sách chung trên trang ghi "2 năm đến 5 năm (tùy sản phẩm)" | Phiếu bảo hành hoặc chính sách hãng |
| Sharp 550W: tên "NU - JD 550 L", mã "JD-550L"; thông số "Dòng tại Pmax (Imp)" trống | Datasheet đúng phiên bản |
| Dự án "Thu Hồng" ghi khách hàng "Nhà nghỉ Thu Hiền" | Hồ sơ dự án |
| Popup tư vấn hứa gọi trong 15 phút; hạn nội bộ là 2 giờ làm việc | Chủ website chốt một cam kết |
| Trang Đại lý còn đoạn thị trường "Năm 2021" | Chủ website duyệt nội dung mới |

## Merchant Center: điều kiện còn thiếu

- Feed đang khai `in_stock` và `identifier_exists=no` cho mọi sản phẩm. Số mục trong feed ngày 25/11: sáng 324, chiều 274, do 50 đèn đường được chuyển sang "Liên hệ báo giá". Việc cần làm: `../2026-11-25-ga4-merchant-gsc-checklist.md`.
- Cần cho từng mã: tồn kho thật; MPN hoặc GTIN lấy từ nhãn/datasheet, hoặc xác nhận hàng không có mã định danh.
- Công cụ sẵn có: `php artisan pts:merchant-readiness --csv=<file>` (chỉ đọc; chưa chạy trên production trong đợt này). Cổng feed xác minh giữ **tắt** cho tới khi đạt ngưỡng 286.
- Không dùng mã `PTS-…` làm MPN, không tạo GTIN, tồn kho hay chính sách.

## Trang thương hiệu rỗng

Giữ nguyên index. Chỉ đổi khi có số liệu Search Console cho từng URL, hoặc khi chủ website quyết định bổ sung nội dung thật.
