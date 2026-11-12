# Inverter điện mặt trời là gì? Nó thực hiện những chức năng nào?

Nếu tấm pin là nơi tạo DC, inverter là bộ phận biến dòng đó thành AC để tải trong nhà có thể dùng. Khi đọc thông số inverter, đừng chỉ hỏi “bao nhiêu kW”; hãy hỏi nó nhận dàn pin ra sao, xử lý mất lưới thế nào và cấp điện cho tải nào.

## Một inverter làm ba việc chính

1. **Biến đổi:** chuyển DC từ pin thành AC phù hợp với hệ thống.
2. **Tối ưu công suất:** chức năng MPPT giúp bộ biến tần tự điều chỉnh để tấm pin hoạt động gần mức công suất tốt nhất khi nắng thay đổi.
3. **Bảo vệ/điều khiển:** giám sát điều kiện lưới và thực hiện các chức năng ngắt, giới hạn hoặc điều phối theo model.

## Đọc theo tình huống mái nhà

| Tình huống | Điều cần xem trên tài liệu |
|---|---|
| Mái một hướng, ít bóng | Dải MPPT, giới hạn DC và cách ghép chuỗi |
| Hai hướng mái khác nhau | Số MPPT độc lập và phân bổ chuỗi |
| Có nhu cầu dùng khi cúp điện | Cổng cấp điện dự phòng (EPS), pin tương thích và mạch cách ly |
| Tải có động cơ | Công suất liên tục, quá tải và dòng khởi động |

Nhiều MPPT không tự tạo thêm điện; lợi ích chỉ xuất hiện khi cách bố trí chuỗi cần các điểm theo dõi riêng. Công suất inverter và công suất dàn pin cũng không nhất thiết bằng nhau — phải theo thiết kế và giới hạn của đúng model.

## Ba câu hỏi trước khi chốt model

- Voc ở nhiệt độ thấp nhất và Vmp của chuỗi có nằm trong giới hạn DC/dải MPPT không?
- Isc/Imp khi ghép song song có vượt dòng vào MPPT, dây, đầu nối hoặc bảo vệ không?
- Khi lưới mất, thiết bị dừng hoàn toàn hay có nhánh cấp điện dự phòng đã được thiết kế? Tải nào được cấp, trong bao lâu?

Inverter hòa lưới thông thường dừng để chống đảo lưới. Tên “hybrid” hoặc “battery-ready” chưa đủ xác nhận khả năng dự phòng; cần datasheet, sơ đồ đấu nối và danh sách pin tương thích của đúng mã.

### Nguồn tham khảo

- [Fronius – Solar Energy Blog](https://blog.fronius.com/solar-energy/en/)
- [U.S. Department of Energy – PV System Design Basics](https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics)
