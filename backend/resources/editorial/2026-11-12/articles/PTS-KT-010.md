Thấy các tấm pin trên mái, nhiều người nghĩ rằng ban ngày nhà sẽ luôn có điện, kể cả lúc điện lưới bị ngắt. Thực tế, phần lớn hệ điện mặt trời hòa lưới dân dụng sẽ tự dừng cấp điện khi phát hiện lưới mất. Đây là cơ chế an toàn có chủ đích, thường được gọi là **chống đảo lưới** (anti-islanding).

Muốn sử dụng điện trong thời gian mất lưới, cần thiết kế chức năng dự phòng phù hợp. Chỉ có tấm pin hoặc inverter hòa lưới thông thường chưa đủ.


![Sơ đồ so sánh hòa lưới khi mất điện và hệ hybrid có nhánh dự phòng](/upload/news/pts-explainer-grid-outage.webp)

*Hình minh họa: Phúc Thịnh Solar*

## Hệ hòa lưới cần một lưới điện ổn định để tham chiếu

Tấm pin tạo ra điện một chiều (DC), còn thiết bị gia dụng thông thường sử dụng điện xoay chiều (AC). Inverter hòa lưới chuyển đổi DC sang AC, đồng thời đồng bộ điện áp, tần số và pha với lưới điện tại điểm đấu nối. Khi điện mặt trời không đủ, nhà lấy phần thiếu từ lưới; khi tạo dư, hệ xử lý theo cấu hình và điều kiện đấu nối.

Inverter phổ thông thuộc nhóm **grid-following**: nó theo dõi tín hiệu lưới để biết dạng sóng AC cần đồng bộ. Khi điện lưới bị cắt hoặc thông số vượt ngưỡng an toàn, inverter ngắt khỏi lưới và ngừng cấp điện AC. Một số hệ có thể tiếp tục giám sát hoặc khởi động lại khi lưới trở lại ổn định, nhưng thời gian và điều kiện cụ thể phụ thuộc model, cài đặt và yêu cầu kết nối.

Vì thế, nếu ban ngày xảy ra mất điện, tấm pin vẫn có thể nhận ánh sáng nhưng inverter hòa lưới thông thường không tự biến chúng thành nguồn cấp độc lập cho ổ cắm trong nhà.

## Chống đảo lưới bảo vệ ai và điều gì?

“Đảo lưới” xảy ra khi một phần mạng điện vẫn bị nguồn phát phân tán cấp điện dù lưới chính đã mất. Tình huống này có thể gây nguy hiểm cho nhân viên đang sửa chữa đường dây, tạo điện áp không ổn định cho thiết bị, hoặc làm việc tái đóng điện trở nên khó kiểm soát.

Inverter được thiết kế để nhận biết điều kiện lưới bất thường và ngừng phát vào đường dây. NREL mô tả các phương pháp chống đảo lưới tích hợp trong inverter; DOE cũng giải thích vì sao các hệ điện mặt trời dân dụng thông thường được thiết kế tắt khi mất điện lưới. Đây không phải dấu hiệu mặc định rằng tấm pin hỏng hay hệ bị lỗi.

Không được tự đấu tắt bảo vệ, cắm máy phát ngược vào ổ cắm, hay nối nguồn dự phòng vào dây dẫn cố định khi chưa có thiết bị chuyển nguồn và phương án cách ly phù hợp. Những cách làm này có thể cấp điện ngược ra lưới hoặc tạo nguy cơ điện giật, cháy và hư hỏng thiết bị.

## Muốn có điện khi mất lưới cần những thành phần nào?

Một cấu hình dự phòng thường cần một inverter có khả năng tạo lưới cục bộ khi lưới ngoài bị ngắt, thiết bị chuyển đổi/cách ly phù hợp và thường có pin lưu trữ. Hệ thống phải tách phần tải được dự phòng khỏi phía lưới để tránh cấp điện ngược. Một số cấu hình có thể cấp điện mặt trời trực tiếp cho tải dự phòng khi có nắng, nhưng khả năng đó phụ thuộc thiết kế và tính tương thích của inverter, pin và các thiết bị bảo vệ.

Pin giúp cân bằng chênh lệch tức thời giữa công suất phát và phụ tải. Mây che, thiết bị bật tắt hoặc tải khởi động có thể làm nguồn thay đổi nhanh. Inverter dự phòng cần giữ điện áp và tần số trong giới hạn cho phép, còn bộ pin phải đáp ứng yêu cầu sạc, xả và giao tiếp của hệ.

Ngay cả khi có pin, hệ thống không nhất thiết cấp điện cho toàn bộ căn nhà. Có thể chỉ chọn một nhánh tải thiết yếu như đèn, modem, tủ lạnh hoặc thiết bị y tế. Các tải có động cơ hay công suất lớn như máy bơm, máy lạnh, bếp điện cần kiểm tra dòng khởi động và khả năng cấp đồng thời.

## Công suất và thời gian dự phòng là hai câu hỏi khác nhau

Khi chọn backup, cần phân biệt **công suất tức thời** và **năng lượng dự trữ**. Công suất inverter tính bằng kW hoặc kVA giúp xác định những thiết bị có thể chạy đồng thời và khả năng khởi động tải. Dung lượng pin tính bằng kWh liên quan đến lượng năng lượng có thể dùng trong một khoảng thời gian. Hai thông số không thay thế cho nhau.

Ví dụ, một pin có năng lượng lớn vẫn có thể không chạy được tải nếu inverter không đủ công suất hoặc dòng khởi động. Ngược lại, inverter có công suất cao nhưng pin nhỏ có thể chạy tải lớn trong thời gian ngắn rồi hết năng lượng. Năng lượng sử dụng thực tế còn tùy giới hạn xả, hiệu suất chuyển đổi, nhiệt độ, tình trạng pin, tải và lượng nắng trong thời gian mất điện.

Do đó, không thể chỉ nhìn số kWh của pin để cam kết “dùng được cả đêm”. Cần lập danh sách tải cần duy trì, công suất từng thiết bị, thời lượng mong muốn, chu kỳ bật tắt và kịch bản thời tiết. Kỹ thuật viên sẽ đối chiếu các dữ liệu này với thông số liên tục, quá tải ngắn hạn và giới hạn backup của đúng model.

## Checklist trước khi chọn chức năng dự phòng

- Xác định thiết bị nào thật sự cần điện khi mất lưới và có thể tắt tải nào.
- Ghi công suất định mức, thời gian sử dụng, tải khởi động và thiết bị nào chạy đồng thời.
- Hỏi rõ inverter có cổng EPS/backup hay không, cổng đó cấp một pha hay ba pha, và giới hạn công suất là bao nhiêu.
- Kiểm tra sơ đồ cách ly khỏi lưới, thiết bị chuyển nguồn, bảo vệ quá dòng và phương án nối đất.
- Xem inverter có cần pin để tạo điện dự phòng không, danh sách pin tương thích và dung lượng khả dụng.
- Xác nhận hệ có thể sử dụng điện mặt trời khi lưới mất hay chỉ cấp từ pin; điều kiện nắng yếu và pin cạn được xử lý thế nào.
- Yêu cầu bàn giao sơ đồ, hướng dẫn vận hành và thử nghiệm chức năng bởi người có chuyên môn.

Các thuật ngữ như “hybrid”, “backup”, “EPS” hoặc “UPS” không đủ để khẳng định mọi model có cùng thời gian chuyển mạch, công suất hay khả năng chạy không pin. Hãy xem datasheet và hướng dẫn lắp đặt của đúng mã thiết bị.

## Kết luận

Tấm pin vẫn nhận ánh sáng khi lưới mất, nhưng hệ hòa lưới thường dừng cấp điện để chống đảo lưới và bảo đảm an toàn. Muốn duy trì một số thiết bị, cần cấu hình inverter, lưu trữ, cách ly và nhánh tải dự phòng phù hợp; phải kiểm tra riêng công suất và thời gian sử dụng dự kiến.

### Nguồn tham khảo

- [U.S. Department of Energy – Solar and Resilience Basics](https://www.energy.gov/cmei/systems/solar-and-resilience-basics)
- [National Renewable Energy Laboratory – A Primer on the Unintentional Islanding Protection Requirement in IEEE Std 1547-2018](https://www.nrel.gov/docs/fy22osti/77782.pdf)
- [U.S. Department of Energy – Solar Integration: Inverters and Grid Services Basics](https://www.energy.gov/cmei/systems/solar-integration-inverters-and-grid-services-basics)
