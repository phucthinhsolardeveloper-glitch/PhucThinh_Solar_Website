Sáng chủ nhật, điện lưới bị cắt nhưng ngoài trời vẫn nắng. Nhà có cả mái pin, vậy vì sao ổ cắm vẫn không có điện? **Tình huống thứ nhất:** hệ hòa lưới thông thường nên inverter dừng phát. **Tình huống thứ hai:** hệ có nhánh backup nên chỉ đèn, modem hoặc tủ lạnh đã được chọn tiếp tục chạy.

Điểm khác nhau không phải ở việc tấm pin còn thấy nắng, mà ở cách inverter tạo nguồn, cách hệ thống cách ly khỏi lưới và những tải được thiết kế để duy trì.

![Sơ đồ so sánh hòa lưới khi mất điện và hệ hybrid có nhánh dự phòng](/upload/news/pts-explainer-grid-outage.webp)

*Hình minh họa: Phúc Thịnh Solar*

## Tình huống 1: có pin trên mái nhưng nhà vẫn tắt

Tấm pin tạo điện một chiều (DC), còn thiết bị trong nhà dùng điện xoay chiều (AC). Inverter hòa lưới đổi DC sang AC và bám theo điện áp, tần số, pha của lưới. Khi lưới mất, thiết bị nhận biết điều kiện bất thường và ngắt khỏi đường AC.

Vì vậy, nắng không biến một hệ hòa lưới thuần túy thành máy phát độc lập. Đây là hành vi an toàn có chủ đích, không phải dấu hiệu mặc định rằng tấm pin hỏng.

## Vì sao phải tắt: chống đảo lưới

Nếu nguồn phân tán vẫn cấp lên đoạn lưới đã mất điện, đường dây mà nhân viên đang sửa có thể còn điện. Điện áp không ổn định cũng gây rủi ro cho thiết bị và làm việc tái đóng điện khó kiểm soát. Cơ chế **chống đảo lưới** (anti-islanding) buộc inverter ngừng phát khi không còn điều kiện lưới phù hợp.

Không được đấu tắt bảo vệ, cắm máy phát ngược vào ổ cắm hoặc nối nguồn dự phòng vào dây cố định khi chưa có thiết bị chuyển nguồn và phương án cách ly phù hợp. Đây là điểm phải giao cho người có chuyên môn.

## Tình huống 2: có điện ở một nhánh đã chọn

Một hệ dự phòng có thể dùng inverter tạo lưới cục bộ khi lưới ngoài bị ngắt, thiết bị chuyển đổi/cách ly và thường có pin lưu trữ. Phần tải dự phòng được tách khỏi phía lưới, nên hệ không đẩy điện ngược ra đường dây bên ngoài.

Nhà không nhất thiết cấp điện cho mọi ổ cắm. Modem, đèn, tủ lạnh hoặc thiết bị y tế có thể vào nhánh ưu tiên; máy lạnh, bếp điện, máy bơm và tải có động cơ cần kiểm tra dòng khởi động và khả năng chạy đồng thời.

Pin cân bằng chênh lệch giữa công suất phát và tải. Nhưng **công suất** (kW/kVA) và **năng lượng** (kWh) là hai câu hỏi khác nhau: inverter quyết định tải nào chạy cùng lúc, còn pin liên quan thời gian duy trì. Pin lớn không bù được inverter thiếu công suất; inverter lớn cũng không làm pin nhỏ chạy cả đêm.

## Trước khi hỏi báo giá, chốt bốn điều

1. Thiết bị nào phải còn điện, thiết bị nào có thể tắt?
2. Những tải đó chạy cùng lúc không, có động cơ hoặc dòng khởi động không?
3. Cổng backup/EPS cấp một pha hay ba pha, công suất liên tục và quá tải ngắn hạn là bao nhiêu?
4. Khi pin cạn hoặc nắng yếu, hệ xử lý ra sao và nhánh nào bị ngắt?

Tên “hybrid”, “backup”, “EPS” hay “UPS” không đủ để xác nhận khả năng của mọi model. Cần xem datasheet, sơ đồ cách ly, danh sách pin tương thích và hướng dẫn lắp đặt của đúng mã thiết bị. Việc đấu nối, nối đất và chạy thử phải do người có chuyên môn thực hiện.

### Nguồn tham khảo

- [U.S. Department of Energy – Solar and Resilience Basics](https://www.energy.gov/cmei/systems/solar-and-resilience-basics)
- [National Renewable Energy Laboratory – A Primer on the Unintentional Islanding Protection Requirement in IEEE Std 1547-2018](https://www.nrel.gov/docs/fy22osti/77782.pdf)
- [U.S. Department of Energy – Solar Integration: Inverters and Grid Services Basics](https://www.energy.gov/cmei/systems/solar-integration-inverters-and-grid-services-basics)
