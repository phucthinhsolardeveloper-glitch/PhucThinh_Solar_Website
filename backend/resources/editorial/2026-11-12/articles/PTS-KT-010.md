Sáng chủ nhật, điện lưới bị cắt nhưng ngoài trời vẫn nắng. Nhà có cả mái pin, vậy vì sao ổ cắm vẫn không có điện? **Tình huống thứ nhất:** hệ hòa lưới thông thường dừng phát. **Tình huống thứ hai:** hệ có nhánh cấp điện dự phòng nên chỉ đèn, modem hoặc tủ lạnh đã được chọn tiếp tục chạy.

Điểm khác nhau nằm ở cách inverter tạo nguồn, cách hệ thống cách ly khỏi lưới và những tải được thiết kế để duy trì.

![Sơ đồ so sánh hòa lưới khi mất điện và hệ hybrid có nhánh dự phòng](/upload/news/pts-explainer-grid-outage.webp)

*Hình minh họa: Phúc Thịnh Solar*

## Hai sơ đồ, một nguyên tắc an toàn

### Sơ đồ A — hòa lưới: có nắng nhưng vẫn tắt

Tấm pin tạo DC; inverter đổi sang AC và bám theo điện áp, tần số, pha của lưới. Khi lưới mất, inverter nhận biết điều kiện bất thường và ngắt khỏi đường AC. Nắng không biến hệ hòa lưới thuần túy thành máy phát độc lập.

Việc dừng phát là yêu cầu **chống đảo lưới (anti-islanding)**. Nếu nguồn phân tán vẫn cấp vào đoạn đường dây đang được sửa, người làm việc có thể gặp điện áp nguy hiểm; điện áp không ổn định cũng làm tái đóng điện khó kiểm soát. Không đấu tắt bảo vệ, cắm máy phát ngược vào ổ cắm hoặc nối nguồn dự phòng vào dây cố định khi chưa có thiết bị chuyển nguồn và phương án cách ly.

### Sơ đồ B — nhánh cấp điện dự phòng: chỉ tải đã chọn còn điện

Một hệ dự phòng có thể dùng inverter tạo lưới cục bộ khi lưới ngoài bị ngắt, thiết bị chuyển đổi/cách ly và thường có pin lưu trữ. Phần tải dự phòng tách khỏi phía lưới nên không đẩy điện ngược ra đường dây bên ngoài.

Danh sách tải phải chốt trước: modem, đèn, tủ lạnh hoặc thiết bị y tế có thể vào nhánh ưu tiên; máy lạnh, bếp điện, máy bơm và tải động cơ phải xét dòng khởi động và khả năng chạy đồng thời. Có điện dự phòng không có nghĩa mọi ổ cắm trong nhà đều hoạt động.

## Đọc báo giá bằng hai con số

**Công suất (kW/kVA)** trả lời tải nào chạy cùng lúc; **năng lượng (kWh)** trả lời duy trì được bao lâu. Pin lớn không bù được inverter thiếu công suất; inverter lớn cũng không làm pin nhỏ chạy cả đêm. Khi pin cạn hoặc nắng yếu, cần biết nhánh nào bị ngắt và hệ chuyển trạng thái ra sao.

### Bốn câu hỏi cần có trong thiết kế

1. Tải nào bắt buộc còn điện, tải nào có thể tắt?
2. Những tải đó có chạy cùng lúc hoặc có động cơ/dòng khởi động không?
3. Cổng cấp điện dự phòng (EPS) là một pha hay ba pha; công suất liên tục và quá tải ngắn hạn bao nhiêu?
4. Datasheet đúng model nói gì về pin tương thích, cách ly, nối đất và khi pin cạn?

Tên gọi “hybrid”, “cấp điện dự phòng” (backup), “EPS” hay “UPS” không đủ xác nhận khả năng của mọi model. Đấu nối và chạy thử phải do người có chuyên môn thực hiện.

### Nguồn tham khảo

- [U.S. Department of Energy – Solar and Resilience Basics](https://www.energy.gov/cmei/systems/solar-and-resilience-basics)
- [National Renewable Energy Laboratory – A Primer on the Unintentional Islanding Protection Requirement in IEEE Std 1547-2018](https://www.nrel.gov/docs/fy22osti/77782.pdf)
- [U.S. Department of Energy – Solar Integration: Inverters and Grid Services Basics](https://www.energy.gov/cmei/systems/solar-integration-inverters-and-grid-services-basics)
