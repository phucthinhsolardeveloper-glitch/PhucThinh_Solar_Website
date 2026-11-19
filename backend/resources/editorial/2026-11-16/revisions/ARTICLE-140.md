Inverter là thiết bị đổi điện một chiều (DC) từ tấm pin thành điện xoay chiều (AC) để các thiết bị trong nhà dùng được. Ngoài việc đổi điện, nó còn liên tục dò điểm cho công suất tốt nhất (MPPT) và bảo vệ hệ thống khi lưới điện có sự cố. Vì vậy khi chọn inverter, câu hỏi "bao nhiêu kW" chỉ là một phần; cần hỏi thêm nó nhận dàn pin ra sao, xử lý mất lưới thế nào và cấp điện cho tải nào.

## Ba việc chính của một inverter

1. **Biến đổi:** chuyển DC từ dàn pin thành AC phù hợp với hệ thống điện.
2. **Tối ưu công suất:** chức năng MPPT (dò điểm công suất cực đại) tự điều chỉnh để tấm pin làm việc gần mức công suất tốt nhất khi nắng thay đổi.
3. **Bảo vệ và điều khiển:** giám sát điều kiện lưới, thực hiện chức năng ngắt, giới hạn hoặc điều phối tùy theo model.

Chức năng bảo vệ quan trọng nhất ở hệ hòa lưới là **chống đảo lưới**. Khi lưới mất điện, inverter hòa lưới thông thường phải dừng phát để không đẩy điện ngược lên đường dây mà người sửa chữa đang làm việc. Đây là lý do nhiều nhà có tấm pin trên mái vẫn bị cúp điện cùng cả xóm.

## Đọc thông số theo tình huống mái nhà

| Tình huống | Điều cần xem trên tài liệu |
|---|---|
| Mái một hướng, ít bóng | Dải MPPT, giới hạn DC và cách ghép chuỗi |
| Hai hướng mái khác nhau | Số MPPT độc lập và cách phân bổ chuỗi |
| Cần dùng điện khi cúp | Cổng cấp điện dự phòng (EPS), pin tương thích và mạch cách ly |
| Tải có động cơ | Công suất liên tục, khả năng quá tải và dòng khởi động |

Nhiều MPPT không tự tạo thêm điện. Lợi ích chỉ xuất hiện khi cách bố trí tấm pin thật sự cần các điểm theo dõi riêng, ví dụ một chuỗi hướng đông và một chuỗi hướng tây. Công suất inverter và công suất dàn pin cũng không nhất thiết bằng nhau; tỷ lệ giữa hai bên phải theo thiết kế và giới hạn của đúng model.

## Phần chuyên sâu: ghép tấm pin với inverter

Phần này dành cho người đang so sánh model hoặc đọc bản thiết kế. Hai giới hạn quan trọng nằm ở phía điện áp và phía dòng điện.

**Điện áp.** Điện áp hở mạch (Voc) của cả chuỗi tăng khi trời lạnh. Lấy ví dụ tấm LONGi 650 W có Voc 53,90 V tại điều kiện chuẩn: chuỗi 10 tấm có tổng Voc 539 V ở 25°C, và cao hơn vào sáng sớm trời lạnh. Con số ở nhiệt độ thấp nhất dự kiến phải nằm dưới giới hạn điện áp DC tối đa của inverter. Trong lúc vận hành, tổng điện áp làm việc (Vmp) của chuỗi phải nằm trong dải MPPT. Cách đọc Voc, Vmp trên bảng thông số tấm pin có ở bài [cách đọc datasheet tấm pin](/cach-doc-datasheet-tam-pin-nang-luong-mat-troi).

**Dòng điện.** Dòng ngắn mạch (Isc) và dòng làm việc (Imp) của chuỗi, nhất là khi ghép song song nhiều chuỗi vào một MPPT, không được vượt dòng vào cho phép của MPPT, của dây dẫn, đầu nối và thiết bị bảo vệ.

**Mất lưới.** Datasheet phải nói rõ khi lưới mất, thiết bị dừng hoàn toàn hay có nhánh cấp điện dự phòng, công suất nhánh đó bao nhiêu và chuyển mạch ra sao.

## Ba câu hỏi trước khi chốt model

- Voc ở nhiệt độ thấp nhất và Vmp của chuỗi có nằm trong giới hạn DC và dải MPPT không?
- Isc, Imp khi ghép song song có vượt dòng vào MPPT, dây, đầu nối hoặc thiết bị bảo vệ không?
- Khi lưới mất, thiết bị dừng hẳn hay có nhánh dự phòng đã được thiết kế? Tải nào được cấp, trong bao lâu?

Tên "hybrid" hoặc "battery-ready" chưa đủ xác nhận khả năng dự phòng. Cần datasheet, sơ đồ đấu nối và danh sách pin tương thích của đúng mã. Nếu mục tiêu chính là có điện khi cúp, bảng so sánh ở bài [hệ hòa lưới, hybrid, độc lập: nên chọn loại nào](/he-hoa-luoi-hybrid-doc-lap-nen-chon-loai-nao) giúp chọn đúng nhóm trước khi so từng model.

## Nguồn tham khảo

- [Fronius – Solar Energy Blog](https://blog.fronius.com/solar-energy/en/)
- [U.S. Department of Energy – PV System Design Basics](https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics)
