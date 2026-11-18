TOPCon, HJT và BC là ba cấu trúc tế bào quang điện. Chọn tấm pin bằng cách so model cụ thể với mái, inverter, giá và bảo hành.

| Công nghệ | Cách hiểu ngắn | Thông số nên xem |
|---|---|---|
| TOPCon | Lớp tiếp xúc giúp giảm tổn thất khi thu dòng | Hệ số nhiệt, dòng làm việc, kích thước, bảo hành |
| HJT | Silicon tinh thể kết hợp lớp silicon mỏng | Hệ số nhiệt, hai mặt, điều kiện thử |
| BC | Các tiếp điểm chính đặt ở mặt sau cell | Hiệu suất model, kích thước, giá thay thế, bảo hành |

## Ba kiến trúc, ba cách đọc datasheet

TOPCon là viết tắt của Tunnel Oxide Passivated Contact. Cấu trúc này dùng lớp oxide mỏng và silicon để giảm tổn thất tại vùng tiếp xúc. Một số model dùng silicon loại n hoặc thiết kế hai mặt.

HJT, hay silicon dị thể, kết hợp phiến silicon tinh thể với các lớp silicon mỏng để xử lý bề mặt. So hiệu suất, hệ số nhiệt và khả năng thu sáng mặt sau theo thiết kế và điều kiện thử của từng model.

BC (Back Contact) đưa các tiếp điểm chính ra sau cell, giảm phần đường dẫn điện che mặt trước. IBC và một số biến thể XBC là tên có thể gặp trong nhóm này. Hiệu quả trên mái có bóng và khả năng tương thích inverter cần xem theo model.

Với TOPCon, xem hiệu suất và thiết kế hai mặt của model. Với HJT, so hệ số nhiệt Pmax và thông số mặt sau. Với BC, xem hiệu suất toàn tấm.

## So đúng thông số và điều kiện lắp

Đặt cạnh nhau công suất, hiệu suất và thông số điện trong datasheet. So thêm hệ số nhiệt Pmax, kích thước, khối lượng, tải gió, cấp an toàn cháy và bảo hành. Ghi đủ mã hàng; “TOPCon 620 W” chưa đủ để tìm đúng datasheet.

| Ký hiệu | Nghĩa | Dùng để |
|---|---|---|
| Voc | Điện áp hở mạch | Kiểm tra điện áp chuỗi khi trời lạnh có vượt giới hạn inverter không |
| Vmp | Điện áp tại điểm công suất cực đại | Xem chuỗi có nằm trong dải MPPT không |
| Isc | Dòng ngắn mạch | Kiểm tra giới hạn dòng khi bức xạ cao |
| Imp | Dòng tại điểm công suất cực đại | Khớp với dòng vào của inverter, dây và thiết bị bảo vệ |

Sau đó kiểm tra kích thước với lối bảo trì, bóng che, điện áp và dòng của inverter. Không ghép các tấm khác kiến trúc vào cùng chuỗi chỉ vì cùng công suất nhãn. Kỹ thuật viên cần tính điện áp hở mạch khi lạnh, dòng ngắn mạch khi bức xạ cao và giới hạn inverter.

So datasheet cùng đơn vị và điều kiện thử. Sản lượng năm phụ thuộc nhiệt độ cell, hướng lắp, bóng che và tổn hao hệ thống. Mô phỏng cần ghi nguồn thời tiết, góc nghiêng và hướng mái. Ghi cả tổn hao giả định.

Với tấm hai mặt, mái sáng màu, khoảng hở và phản xạ nền ảnh hưởng điện thu từ mặt sau. Dùng cùng giả định khi so các báo giá.

Tấm pin công suất cao có thể có kích thước hoặc khối lượng khác, nên kiểm tra vùng lắp, tải mái và lối vận chuyển. Nếu bóng che một phần, cách chia chuỗi và theo dõi MPPT thường quan trọng hơn việc công nghệ nào đang được quảng bá là mới.

Hiệu suất cell và hiệu suất toàn tấm là hai thông số khác nhau; hãy so hiệu suất toàn tấm.

## Mái và bảo hành cũng quyết định lựa chọn

Nếu mái chật, so công suất trên diện tích và tổng chi phí sở hữu. Mái nóng cần xem hệ số nhiệt và khoảng thông gió. Nếu có bóng, xử lý hướng tấm, chuỗi và MPPT trước khi chọn tên công nghệ.

Bên cạnh hiệu suất, xem tải cơ học, chứng nhận đúng thị trường và điều kiện vận chuyển, bảo quản. Với mái gần biển hoặc môi trường ăn mòn, đọc điều kiện vật liệu và bảo hành theo hướng dẫn hãng. Tên TOPCon, HJT hay BC không cho biết các điều kiện này.

Bảo hành sản phẩm và bảo hành công suất là hai điều khoản khác nhau. Hỏi cách xử lý nếu nhà sản xuất ngừng model, ai nhận hồ sơ tại Việt Nam và bên nào chịu chi phí vận chuyển. Giữ hóa đơn, số sê-ri, datasheet đúng phiên bản và phiếu bảo hành khi nghiệm thu.

Khi so báo giá, thống nhất số tấm, cách bố trí, tổng công suất và điều kiện bảo hành. Hai tấm cùng tên công nghệ vẫn có thể khác kính, khung, vật liệu bao bọc hoặc quy trình sản xuất. Hỏi đầu mối cung cấp datasheet cập nhật theo mã hàng.

Lưu mã hàng trên báo giá, nhãn sản phẩm và hồ sơ nghiệm thu. Kỹ thuật viên dựa vào mã đó để kiểm tra điện áp, dòng và điều kiện bảo hành theo datasheet.

### Nguồn tham khảo

- [Photovoltaics Report — Fraunhofer ISE](https://www.ise.fraunhofer.de/en/publications/studies/photovoltaics-report.html)
- [TOPCon — Fraunhofer ISE](https://www.ise.fraunhofer.de/en/research-projects/topcon.html)
- [Solar Technology Trends Report — TaiyangNews](https://taiyangnews.info/pressreleases/taiyangnews-solar-technology-trends-report-maps-charts-the-future-of-solar-pv)
