Một gia đình ở khu vực hay cúp điện có hai câu hỏi trước khi so inverter: **có cần điện khi cúp không?** và **có chấp nhận mua, bảo trì pin không?** Hai câu trả lời này thường hữu ích hơn việc bắt đầu từ số MPPT hay tên thương mại.

![Bảng so sánh chức năng inverter hòa lưới và inverter hybrid](/upload/news/pts-explainer-inverter-types.webp)

*Hình minh họa: Phúc Thịnh Solar*

## Bảng quyết định

| Câu trả lời | Hướng xem xét đầu tiên | Điều phải xác nhận |
|---|---|---|
| Không cần điện khi cúp | Inverter hòa lưới | Mức tự dùng, giới hạn lưới, mái và giám sát |
| Cần điện khi cúp + chấp nhận pin | Hybrid có nhánh backup | Pin tương thích, cách ly, EPS, công suất và thời gian chuyển |
| Cần điện khi cúp + không chấp nhận pin | Chưa thể kết luận là hybrid | Giải pháp nguồn dự phòng khác, tải ưu tiên và thời gian cần điện |
| Không có lưới ổn định | Hệ độc lập/giải pháp chuyên biệt | Nguồn cân bằng, dự phòng nhiều ngày và quản lý tải |

Đây là bộ lọc nhu cầu, không phải quyết định mua model. “Hybrid” không có nghĩa máy luôn kèm pin, tự động cấp điện khi mất lưới hoặc chạy được toàn bộ tải.

## Hòa lưới: đơn giản khi mục tiêu là dùng điện ban ngày

Inverter hòa lưới đổi DC từ tấm pin thành AC đồng bộ với lưới. Khi lưới mất, chống đảo lưới khiến máy ngừng cấp điện vào đường AC; có nắng cũng không biến hệ thành nguồn độc lập. Loại này phù hợp hơn khi mục tiêu chính là dùng phần điện mặt trời tạo ra cùng lưới, không phải duy trì điện lúc cúp.

## Hybrid: thêm pin, thêm điều kiện

Hybrid thường quản lý PV, lưới, pin và có thể cấp một nhánh tải dự phòng. Khi mất lưới, hệ phải tách khỏi lưới ngoài rồi tạo nguồn cục bộ; thời gian chuyển, công suất liên tục, công suất đỉnh và khả năng chạy không pin phụ thuộc đúng model.

Nhánh backup không mặc định là toàn bộ nhà. Modem, đèn, tủ lạnh hoặc camera có thể được ưu tiên; bếp điện, máy lạnh, bình nước nóng, máy bơm và tải ba pha cần tính công suất đồng thời, dòng khởi động và phân bổ pha.

## Ba dòng cần có trong báo giá

Yêu cầu sơ đồ một đường thể hiện dàn pin, inverter, pin lưu trữ, lưới, tủ chính, tủ tải dự phòng và thiết bị cách ly. Trong datasheet, kiểm tra dải MPPT, điện áp DC tối đa, dòng mỗi MPPT, công suất AC liên tục/đỉnh, công suất sạc-xả, pin tương thích, số pha và thời gian chuyển EPS.

Với hybrid, tổng chi phí còn gồm pin, tủ tải, chuyển nguồn, bảo vệ, dây và công lắp đặt. Giá trị của backup phụ thuộc tần suất mất điện và giá trị tải cần duy trì; không có kết luận kinh tế chung nếu chưa có dữ liệu.

### Nguồn tham khảo

- [Clean Energy Reviews – Solar hybrid and battery-ready inverters](https://www.cleanenergyreviews.info/blog/solar-hybrid-battery-ready-inverters)
- [Fronius – What is an inverter?](https://blog.fronius.com/solar-energy/en/know-how/what-is-an-inverter/)
- [U.S. Department of Energy – Solar Photovoltaic System Design Basics](https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics)
