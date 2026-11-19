Nếu bạn cần có điện khi cúp, hệ hòa lưới thông thường không đáp ứng được, vì inverter dừng phát ngay khi lưới mất. Hybrid có thể giữ điện cho một nhóm tải nếu có pin lưu trữ, cổng cấp điện dự phòng và thiết kế cách ly đúng. Hệ độc lập phù hợp với nơi không có lưới hoặc lưới quá chập chờn, và phải tự gánh toàn bộ nhu cầu điện.

Nói gọn: chọn theo lúc bạn cần điện, không chọn theo tên thiết bị. Bảng dưới là điểm bắt đầu để hỏi đúng; nó không thay thế thiết kế điện của công trình.

| Câu hỏi tại công trình | Hòa lưới | Hybrid | Độc lập |
|---|---|---|---|
| Có lưới ổn định để bù phần thiếu? | Có | Có | Không có hoặc không đáng tin cậy |
| Muốn dùng điện mặt trời ban ngày? | Có | Có | Có |
| Muốn chuyển điện sang tối? | Không có pin thì không | Có thể, nếu có pin phù hợp | Có, trong giới hạn của pin |
| Khi mất lưới có tải còn điện? | Thông thường không | Có thể, ở nhánh cấp điện dự phòng | Có, nếu pin và nguồn đủ |
| Cần hỏi thêm | Chống đảo lưới, đấu nối | Pin, tải ưu tiên, cổng dự phòng | Mùa mưa, nguồn dự phòng, tải cực đại |

## Một ngày mất điện với ba hệ

**Hòa lưới.** Inverter bám theo điện áp và tần số của lưới. Khi lưới mất, nó dừng phát để chống đảo lưới, tức là không đẩy điện ngược lên đường dây mà người sửa chữa đang làm việc. Có tấm pin trên mái không có nghĩa ổ cắm vẫn có điện.

**Hybrid.** Inverter hybrid có thể điều phối điện từ tấm pin, lưới điện và pin lưu trữ, tùy từng mẫu. Khi mất lưới, nó có thể cấp điện cho một nhóm tải đã được tách riêng nếu có cổng cấp điện dự phòng (EPS), thiết bị cách ly và thiết kế phù hợp. Nó không mặc định cấp cho cả nhà.

**Độc lập.** Không có lưới để bù khi thiếu. Một hệ đủ cho đèn có thể không đủ cho máy bơm hoặc điều hòa; phải tính theo tải, theo những ngày mưa liên tiếp, theo dung lượng pin và nguồn dự phòng.

## Ước lượng nhanh nhánh dự phòng

Trước khi hỏi giá hybrid, hãy tự lập một bảng nhỏ cho những thiết bị bắt buộc phải còn điện khi cúp. Ví dụ dưới đây hoàn toàn là giả định để minh họa cách tính; công suất thật lấy từ nhãn thiết bị hoặc đo bằng đồng hồ.

| Tải ưu tiên (giả định) | Công suất khi chạy | Số giờ cần khi cúp | Điện năng |
|---|---|---|---|
| Đèn và quạt | 150 W | 4 giờ | 600 Wh |
| Tủ lạnh (trung bình theo chu kỳ) | 100 W | 4 giờ | 400 Wh |
| Modem, sạc điện thoại | 30 W | 4 giờ | 120 Wh |
| **Tổng** | **280 W chạy cùng lúc** | | **1.120 Wh ≈ 1,1 kWh** |

Từ bảng này bạn có hai con số để mang đi hỏi:

- **Công suất:** nhánh dự phòng phải chạy được khoảng 280 W cùng lúc, cộng phần dòng khởi động của tủ lạnh, thường cao hơn lúc chạy ổn định; mức cụ thể hỏi theo đúng thiết bị. Thiết bị có động cơ như máy bơm, điều hòa cần hỏi riêng.
- **Điện năng:** pin cần dung lượng **khả dụng** lớn hơn khoảng 1,1 kWh, cộng tổn hao và phần dự trữ không xả hết. Nếu muốn gấp đôi thời gian dự phòng, con số điện năng cũng gấp đôi.

Cách phân biệt kW và kWh trong bảng này có ở bài [kW, kWp và kWh khác nhau thế nào](/kw-kwp-va-kwh-khac-nhau-the-nao-7-vi-du-tranh-nham).

## Bốn câu hỏi mang đi khảo sát

1. Tải nào phải còn điện và chạy cùng lúc? Có thiết bị động cơ, có dòng khởi động lớn không?
2. Mất điện ở khu vực thường kéo dài bao lâu, mùa mưa có khác không?
3. Bạn có chấp nhận chi phí, bảo trì và thay pin theo vòng đời không?
4. Sơ đồ cách ly, bảo vệ và đấu nối của đúng model nằm ở tài liệu nào?

Không tự đấu nguồn dự phòng như máy phát hay bộ lưu điện vào hệ thống điện cố định. Khả năng thực tế phải được xác nhận bằng datasheet, sơ đồ đấu nối và thiết kế của người có chuyên môn. Nếu đang phân vân vì nhà dùng điện chủ yếu buổi tối chứ không phải vì cúp điện, bài [nhà chủ yếu dùng điện buổi tối](/co-nen-lap-dien-mat-troi-khi-nha-chi-dung-dien-nhieu-vao-buoi-toi) phù hợp hơn. Cách đọc cổng EPS và giới hạn của inverter có ở bài [Inverter điện mặt trời là gì](/inverter-dien-mat-troi-la-gi-no-thuc-hien-nhung-chuc-nang-nao).

## Nguồn tham khảo

- [U.S. Department of Energy – PV System Design Basics](https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics)
- [U.S. Department of Energy – Solar and Resilience Basics](https://www.energy.gov/cmei/systems/solar-and-resilience-basics)
