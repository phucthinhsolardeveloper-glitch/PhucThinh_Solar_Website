TOPCon, HJT và BC mô tả các cách cấu tạo tế bào quang điện, không phải bảng xếp hạng chất lượng. Muốn chọn tấm pin, hãy so model cụ thể với mái, inverter, giá và bảo hành.

| Công nghệ | Cách hiểu ngắn | Thông số nên xem |
|---|---|---|
| TOPCon | Lớp tiếp xúc giúp giảm tổn thất khi thu dòng | Hệ số nhiệt, dòng làm việc, kích thước, bảo hành |
| HJT | Silicon tinh thể kết hợp lớp silicon mỏng | Hệ số nhiệt, hai mặt, điều kiện thử |
| BC | Các tiếp điểm chính đặt ở mặt sau cell | Hiệu suất model, kích thước, giá thay thế, bảo hành |

## Ba kiến trúc, ba cách đọc datasheet

TOPCon là viết tắt của Tunnel Oxide Passivated Contact. Cấu trúc này dùng lớp oxide mỏng và silicon để giảm tổn thất tại vùng tiếp xúc. Một số tấm dùng silicon loại n hoặc thiết kế hai mặt; các đặc điểm đó cần xác nhận theo model.

HJT, hay silicon dị thể, kết hợp phiến silicon tinh thể với các lớp silicon mỏng để xử lý bề mặt. Hiệu suất, hệ số nhiệt và khả năng thu sáng mặt sau tùy thiết kế tấm pin và điều kiện thử.

BC (Back Contact) đưa các tiếp điểm chính ra sau cell, giảm phần đường dẫn điện che mặt trước. IBC và một số biến thể XBC là tên có thể gặp trong nhóm này. Cấu trúc đó không tự quyết định hiệu quả trên mái có bóng hay khả năng tương thích inverter.

Tên công nghệ chỉ gợi ý cấu trúc. TOPCon không tự xác nhận hiệu suất hoặc khả năng hai mặt của một mẫu. Với HJT, xem hệ số nhiệt Pmax và thông số hai mặt nếu có. Với BC, xem hiệu suất toàn tấm, không chỉ hình ảnh mặt trước.

## So đúng thông số và điều kiện lắp

Đặt cạnh nhau công suất, hiệu suất và thông số điện trong datasheet. So thêm hệ số nhiệt Pmax, kích thước, khối lượng, tải gió, cấp an toàn cháy và bảo hành. Ghi đủ mã hàng; “TOPCon 620 W” chưa đủ để tìm đúng datasheet.

Voc là điện áp hở mạch.

Vmp là điện áp tại điểm công suất cực đại.

Isc là dòng ngắn mạch.

Imp là dòng ở điểm công suất cực đại.

Kỹ thuật viên dùng các giá trị này để kiểm tra giới hạn inverter và cách ghép chuỗi.

Sau đó kiểm tra kích thước với lối bảo trì, bóng che, điện áp và dòng của inverter. Không ghép các tấm khác kiến trúc vào cùng chuỗi chỉ vì cùng công suất nhãn. Kỹ thuật viên cần tính điện áp hở mạch khi lạnh, dòng ngắn mạch khi bức xạ cao và giới hạn inverter.

Một datasheet dùng điều kiện đo xác định; vì thế hãy so cùng đơn vị và cùng chế độ thử. Công suất nhãn không cho biết sản lượng cả năm. Nhiệt độ cell, hướng lắp, che bóng và tổn hao hệ thống đều ảnh hưởng điện tạo ra. Mô phỏng cần ghi nguồn dữ liệu thời tiết, góc nghiêng, hướng mái và tổn hao giả định.

Nếu chọn tấm hai mặt, hiệu suất mặt sau không tự áp dụng cho mọi mái. Mái sáng màu, khoảng hở và phản xạ nền ảnh hưởng phần đóng góp. Hỏi báo giá đã tính mặt sau hay chưa, rồi dùng cùng giả định khi so model khác.

Tấm pin công suất cao có thể có kích thước hoặc khối lượng khác, nên kiểm tra vùng lắp, tải mái và lối vận chuyển. Nếu bóng che một phần, cách chia chuỗi và theo dõi MPPT thường quan trọng hơn việc công nghệ nào đang được quảng bá là mới.

Thông số công suất cần được so trong cùng điều kiện thử. Hiệu suất cell không đồng nghĩa hiệu suất toàn tấm; sản lượng năm còn cần mô phỏng vị trí, hướng và bóng che. Một con số kỷ lục từ phòng thí nghiệm không bảo đảm kết quả của mái nhà cụ thể.

## Mái và bảo hành cũng quyết định lựa chọn

Nếu mái chật, so công suất trên diện tích và tổng chi phí sở hữu. Mái nóng cần xem hệ số nhiệt và khoảng thông gió. Nếu có bóng, xử lý hướng tấm, chuỗi và MPPT trước khi chọn tên công nghệ.

Bên cạnh hiệu suất, xem tải cơ học, chứng nhận đúng thị trường và điều kiện vận chuyển, bảo quản. Với mái gần biển hoặc môi trường ăn mòn, đọc điều kiện vật liệu và bảo hành theo hướng dẫn hãng. Không thể suy ra các yêu cầu này chỉ từ tên TOPCon, HJT hay BC.

Bảo hành sản phẩm và bảo hành công suất là hai điều khoản khác nhau. Hỏi cách xử lý nếu nhà sản xuất ngừng model, ai nhận hồ sơ tại Việt Nam và bên nào chịu chi phí vận chuyển. Giữ hóa đơn, số sê-ri, datasheet đúng phiên bản và phiếu bảo hành khi nghiệm thu.

Khi so báo giá, thống nhất số tấm, cách bố trí, tổng công suất và điều kiện bảo hành. Hai tấm cùng tên công nghệ vẫn có thể khác kính, khung, vật liệu bao bọc hoặc quy trình sản xuất. Hỏi đầu mối cung cấp datasheet cập nhật theo mã hàng.

Một thay đổi nhỏ về mã hàng có thể làm khác điện áp, dòng hoặc điều kiện bảo hành. Vì vậy cần lưu đúng mã trên báo giá, nhãn sản phẩm và hồ sơ nghiệm thu. Khi có khác biệt, datasheet của đúng mã hàng là căn cứ để kỹ thuật viên kiểm tra.

### Nguồn tham khảo

- [Photovoltaics Report — Fraunhofer ISE](https://www.ise.fraunhofer.de/en/publications/studies/photovoltaics-report.html)
- [TOPCon — Fraunhofer ISE](https://www.ise.fraunhofer.de/en/research-projects/topcon.html)
- [Solar Technology Trends Report — TaiyangNews](https://taiyangnews.info/pressreleases/taiyangnews-solar-technology-trends-report-maps-charts-the-future-of-solar-pv)
