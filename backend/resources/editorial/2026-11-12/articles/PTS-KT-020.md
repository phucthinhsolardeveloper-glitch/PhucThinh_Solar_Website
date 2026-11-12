## Người mua cần nhớ gì về TOPCon, HJT và BC?

Nếu chỉ có 30 giây, hãy nhớ ba điều: đây là ba kiến trúc cell chứ không phải ba cấp chất lượng; hiệu suất ghi trên tấm pin không tự nói lên sản lượng của mái nhà; và bảng thông số, bảo hành, kích thước, inverter cùng giá trị sử dụng quan trọng không kém tên công nghệ. Phần dưới dành cho người muốn hiểu sâu hơn cách ba kiến trúc tạo và thu dòng điện.

### Từ điển nhanh

- **Cell:** tế bào quang điện, đơn vị biến ánh sáng thành điện trong một tấm pin.
- **Tiếp xúc thụ động hóa:** lớp cấu trúc giúp giảm mất mát tại nơi thu dòng.
- **Hệ số nhiệt Pmax:** mức công suất cực đại thay đổi khi nhiệt độ cell thay đổi.
- **BC/Back Contact:** cách đưa các tiếp điểm thu dòng ra mặt sau cell.

### Ba kiến trúc cell, ba cách tối ưu

TOPCon, HJT và BC đều là tên kiến trúc cell, không phải thang điểm chất lượng tuyệt đối của cả tấm pin. Hiệu suất module, hệ số nhiệt, độ suy giảm, bảo hành, kích thước, giá và năng lực nhà sản xuất mới quyết định sản phẩm có hợp mái nhà hay không. Fraunhofer ISE mô tả TOPCon là tiếp xúc thụ động hóa bằng lớp oxide đường hầm; HJT kết hợp silicon tinh thể với lớp silicon mỏng; BC đặt các tiếp điểm ở phía sau cell.

### TOPCon: tiếp xúc thụ động hóa

TOPCon là viết tắt của Tunnel Oxide Passivated Contact. Một lớp oxide cực mỏng và lớp silicon giúp chọn lọc hạt tải, giảm tái hợp tại tiếp xúc. Thiết kế này có thể dùng wafer n-type và tiếp điểm hai mặt, tạo nền tảng cho hiệu suất cao và module hai mặt. Fraunhofer từng công bố các mốc hiệu suất cell TOPCon trong phòng thí nghiệm; không nên lấy kỷ lục cell để suy ra mọi module thương mại.

Trong thực tế, TOPCon có lợi thế là hệ sinh thái sản xuất rộng và nhiều model phổ biến. Người mua vẫn phải xem hệ số nhiệt, dòng làm việc, kích thước, tải cơ học và bảo hành. Một module TOPCon lớn nhưng không vừa khoảng kèo mái hoặc vượt dòng MPPT không phải lựa chọn tốt.

### HJT: lớp mỏng và đặc tính nhiệt

HJT, hay silicon heterojunction, ghép wafer silicon tinh thể với các lớp silicon vô định hình/mỏng để thụ động hóa bề mặt. Cấu trúc này có thể cho hệ số nhiệt tốt và hiệu suất cao; một số module có khả năng hai mặt đáng kể. Tuy nhiên, quy trình sản xuất, vật liệu và cách liên kết ảnh hưởng chi phí, độ bền và khả năng cung ứng.

Đừng dùng nhãn HJT để bỏ qua kiểm tra nhiệt độ mái, khoảng thông gió và chất lượng encapsulant. Một nghiên cứu thực địa so sánh PERC, TOPCon và SHJ trong khí hậu sa mạc cho thấy độ suy giảm khác nhau giữa model; công nghệ cell không xóa được ảnh hưởng của thiết kế module và điều kiện môi trường.

### BC: đưa tiếp điểm ra mặt sau

BC (Back Contact), thường gặp dưới tên IBC hoặc các biến thể XBC, đưa các cực thu dòng ra mặt sau. Mặt trước ít che bởi đường busbar nên có thể tận dụng ánh sáng tốt và tạo bề mặt đồng nhất. Nhưng pattern tiếp điểm, cách cô lập và quy trình sản xuất phức tạp; yêu cầu kiểm soát vật liệu và độ tin cậy rất chặt.

BC có thể hấp dẫn khi diện tích mái hạn chế và cần watt trên mét vuông cao. Song module BC không tự động thắng ở mọi mái: kích thước, giá, dòng inverter, bảo hành và nguồn thay thế sau nhiều năm vẫn quan trọng. Hãy so kWh sản lượng dự kiến trên diện tích thực, không chỉ so phần trăm hiệu suất trên datasheet.

### So sánh theo quyết định mua

Nếu cần sản phẩm phổ biến, dễ tìm và có nhiều lựa chọn công suất, TOPCon có thể là điểm bắt đầu. Nếu mái nóng, diện tích hạn chế và cần cân nhắc hệ số nhiệt, HJT đáng được đưa vào bảng so sánh. Nếu muốn tối đa công suất trên diện tích và chấp nhận kiểm tra kỹ nguồn cung, BC là một phương án. Đây là định hướng khảo sát, không phải bảng xếp hạng thương hiệu.

Đặt ba bộ datasheet cạnh nhau và kiểm tra: công suất danh định, hiệu suất module, Voc/Isc, Vmp/Imp, hệ số nhiệt Pmax, tải tuyết/gió (theo tiêu chuẩn công bố), kích thước, khối lượng, fire rating, bảo hành sản phẩm và bảo hành công suất. Xem cả điều kiện bảo hành: ai xử lý tại Việt Nam, thời gian phản hồi, model thay thế và yêu cầu hồ sơ lắp đặt.

### Công nghệ không thay thế thiết kế hệ thống

Một mái có bóng che cần xử lý chuỗi/MPPT trước khi tranh luận TOPCon hay BC. Tấm khác công nghệ không nên trộn trong cùng chuỗi nếu chưa kiểm tra dòng và điện áp. Cần tính điện áp hở mạch ở điều kiện nhiệt độ thấp, dòng ngắn mạch ở bức xạ cao và giới hạn inverter. Dây DC, đầu nối, cầu chì chuỗi, tiếp địa và chống sét phải đồng bộ.

Với hộ gia đình ở Gia Lai, nhiệt độ mái, bụi đỏ, mưa lớn và giông sét là dữ kiện thiết kế. Hỏi về khoảng thông gió, cách vệ sinh, bảo vệ cáp và quy trình kiểm tra sau mưa. Không quảng cáo hiệu suất phòng thí nghiệm như sản lượng cam kết. Hệ hòa lưới vẫn phải có chống đảo lưới; hệ có pin phải tách tải backup và có thiết bị bảo vệ riêng.

### Kết luận dễ nhớ

TOPCon tối ưu tiếp xúc bằng oxide đường hầm, HJT dùng cấu trúc dị thể, còn BC đưa tiếp điểm ra phía sau. Không có công nghệ tốt nhất một cách tuyệt đối. Chọn theo mái, phụ tải, inverter, ngân sách, điều kiện khí hậu và dịch vụ bảo hành; yêu cầu mô phỏng kWh thực tế và hồ sơ an toàn trước khi quyết định.

### Nguồn tham khảo

* [Photovoltaics Report — Fraunhofer ISE](https://www.ise.fraunhofer.de/en/publications/studies/photovoltaics-report.html)
* [TOPCon — Fraunhofer ISE](https://www.ise.fraunhofer.de/en/research-projects/topcon.html)
* [Solar Technology Trends Report — TaiyangNews](https://taiyangnews.info/pressreleases/taiyangnews-solar-technology-trends-report-maps-charts-the-future-of-solar-pv)
