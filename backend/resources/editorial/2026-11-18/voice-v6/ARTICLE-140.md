Inverter biến dòng điện một chiều (DC) từ tấm pin thành điện xoay chiều (AC) để thiết bị trong nhà sử dụng.

Máy còn theo dõi điểm làm việc của tấm pin và thực hiện các chức năng bảo vệ theo thiết kế.

## Inverter làm gì trong hệ thống?

- **Biến đổi điện:** đổi DC từ tấm pin thành AC phù hợp với hệ thống điện.
- **Theo dõi công suất:** MPPT giúp inverter điều chỉnh điểm làm việc khi ánh sáng thay đổi.
- **Bảo vệ hệ thống:** máy theo dõi điều kiện lưới và ngắt hoặc giới hạn hoạt động theo thiết kế.

## Mái nhà và tải quyết định điều gì?

| Trường hợp | Thông số nên xem |
|---|---|
| Mái một hướng, ít bóng | Dải MPPT, giới hạn DC và cách ghép chuỗi |
| Hai hướng mái khác nhau | Số MPPT độc lập và cách chia chuỗi |
| Muốn dùng điện khi mất lưới | Cổng cấp điện dự phòng (EPS), pin tương thích và mạch cách ly |
| Tải có động cơ | Công suất liên tục, khả năng quá tải và dòng khởi động |

Nhiều MPPT có ích khi các nhóm tấm pin cần được theo dõi riêng, chẳng hạn khi chúng nằm ở những mặt mái khác hướng.

Số MPPT nhiều hơn tự nó không làm hệ thống tạo thêm điện.

## Các ký hiệu trên datasheet nghĩa là gì?

| Ký hiệu | Nghĩa | Bạn cần quan tâm vì |
|---|---|---|
| Voc | Điện áp hở mạch của tấm pin | Khi trời lạnh, điện áp chuỗi có thể tăng; tổng điện áp phải nằm dưới giới hạn DC của inverter. |
| Vmp | Điện áp tại điểm công suất cực đại | Điện áp chuỗi cần nằm trong dải MPPT để inverter theo dõi tấm pin đúng cách. |
| Isc | Dòng điện ngắn mạch của tấm pin | Khi ghép chuỗi song song, dòng tổng phải phù hợp với giới hạn đầu vào. |
| Imp | Dòng điện tại điểm công suất cực đại | Dòng làm việc cần phù hợp với đầu vào MPPT, dây dẫn và thiết bị bảo vệ. |

Khi ghép nhiều chuỗi song song, hãy xem tổng dòng có nằm trong giới hạn của inverter và các thiết bị điện hay không.

Chọn đúng model và xem datasheet giúp tránh ghép sai dải điện áp hoặc dòng vào.

## Inverter có cấp điện khi lưới mất không?

Inverter hòa lưới thông thường dừng để chống phát điện ngược ra đường dây.

Muốn giữ điện cho một số tải khi mất lưới, hệ thống cần nhánh dự phòng được thiết kế và cách ly phù hợp.

Tên gọi “hybrid” hoặc “battery-ready” chưa cho biết tải nào được cấp điện; hãy xem datasheet và sơ đồ đấu nối của đúng model.

### Đọc thêm

- [Hệ hòa lưới, hybrid, độc lập: nên chọn loại nào?](/he-hoa-luoi-hybrid-doc-lap-nen-chon-loai-nao/)
- [Inverter hòa lưới và hybrid khác nhau thế nào?](/inverter-hoa-luoi-va-hybrid-khac-nhau-the-nao/)
- [Xem biến tần (inverter)](/danh-muc-san-pham/bien-tan-inverter/)

### Nguồn tham khảo

- [Fronius – Solar Energy Blog](https://blog.fronius.com/solar-energy/en/)
- [U.S. Department of Energy – PV System Design Basics](https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics)
