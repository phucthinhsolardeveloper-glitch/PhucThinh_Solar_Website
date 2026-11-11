Hai tấm pin đều ghi “650 W” chưa chắc cho trải nghiệm sử dụng giống nhau. Datasheet của nhà sản xuất chứa nhiều dữ liệu quan trọng về công suất, hiệu suất, điện áp, dòng điện, nhiệt độ, kích thước và bảo hành. Hiểu đúng các thông số này giúp khách hàng so sánh sản phẩm tốt hơn và giúp kỹ thuật viên kiểm tra khả năng tương thích với inverter.

Bài viết lấy **LONGi Hi-MO X10 Explorer LR7-72HVH-650M** làm ví dụ minh họa. Các thông số phải được đối chiếu với **đúng phiên bản datasheet và mã hàng thực tế** trước khi dùng cho thiết kế.

![Infographic các thông số datasheet LONGi 650W gồm Pmax hiệu suất Voc Vmp Isc và Imp](/upload/news/pts-infographic-panel-650w.webp)

*Infographic biên soạn từ datasheet model tham khảo. Kiểm tra phiên bản hiện hành trước khi sử dụng số liệu để thiết kế.*

## 1. Model sản phẩm và công suất Pmax

Tên thương mại như “Hi-MO X10” có thể bao gồm nhiều mức công suất và biến thể. Mã **LR7-72HVH-650M** mới giúp xác định chính xác phiên bản tham khảo.

**Pmax (Maximum Power)** là công suất cực đại của module tại điều kiện thử nghiệm xác định. Với model ví dụ, giá trị Pmax tại STC là **650 W**. Mười tấm 650 Wp có tổng công suất DC danh định là **6,5 kWp**; không thể lấy 6,5 kW nhân toàn bộ số giờ ban ngày để suy ra chính xác điện năng mỗi ngày.

## 2. STC và NOCT/NMOT

STC (Standard Test Conditions) thường dùng điều kiện bức xạ 1.000 W/m², nhiệt độ tế bào 25°C và phổ sáng AM1.5. STC tạo mốc so sánh chung giữa các tấm pin.

Datasheet còn có thể ghi NOCT/NMOT, mô tả điều kiện thử nghiệm hoặc vận hành danh định khác STC. Ở datasheet LONGi được tham khảo, Pmax model 650 W là **650 W tại STC** và **495 W tại NOCT**.

Đây là hai điểm đo trong điều kiện khác nhau; **495 W không phải công suất cố định ngoài thực tế**, và 650 W cũng không phải cam kết phát điện liên tục trong mọi điều kiện.

## 3. Hiệu suất module

Hiệu suất module cho biết tỷ lệ năng lượng bức xạ chiếu đến diện tích tấm pin được chuyển thành điện năng trong điều kiện chuẩn. LONGi LR7-72HVH-650M được hãng công bố hiệu suất **24,1%**.

Đừng nhầm “công suất cao hơn” với “hiệu suất cao hơn”. Một tấm có kích thước lớn hơn có thể có công suất danh định lớn hơn dù hiệu suất bằng hoặc thấp hơn một tấm nhỏ.

Với mái nhà chật hẹp, hiệu suất là thông số quan trọng. Với mái rộng, tổng chi phí toàn hệ thống, độ bền và khả năng bố trí module cũng cần được xem xét.

## 4. Voc – điện áp hở mạch

**Voc** là điện áp đo được khi hai cực tấm pin không cấp dòng điện cho tải. Trong ví dụ này, Voc tại STC là **53,90 V**.

Voc thường tăng khi nhiệt độ tế bào giảm. Vì vậy khi nối nhiều tấm thành chuỗi, kỹ sư cần tính **điện áp hở mạch chuỗi tại nhiệt độ thấp nhất dự kiến** và bảo đảm không vượt giới hạn đầu vào DC của inverter.

Không được đánh giá an toàn chuỗi pin chỉ bằng cách cộng công suất watt.

## 5. Vmp – điện áp tại điểm công suất cực đại

**Vmp** là điện áp tại điểm vận hành mà tấm pin cho công suất lớn nhất trong một điều kiện đo. Mẫu LONGi tham khảo có **Vmp = 44,56 V tại STC**.

Khi thiết kế chuỗi tấm pin, tổng Vmp trong các điều kiện nhiệt độ khác nhau phải nằm trong dải MPPT phù hợp của biến tần.

## 6. Isc và Imp – hai giá trị dòng điện cần phân biệt

**Isc** là dòng ngắn mạch tại điều kiện đo; **Imp** là dòng điện tại điểm công suất cực đại.

Với model ví dụ, dữ liệu STC gồm:

| Thông số | Giá trị |
|---|---:|
| Pmax | 650 W |
| Hiệu suất module | 24,1% |
| Voc | 53,90 V |
| Vmp | 44,56 V |
| Isc | 15,29 A |
| Imp | 14,59 A |

Các giá trị dòng điện phải được đối chiếu với dòng vào MPPT, dòng cho phép của dây dẫn, đầu nối và thiết bị bảo vệ; đồng thời xem xét cách nối song song các chuỗi.

## 7. Hệ số nhiệt độ của công suất

Tấm pin silicon thông thường giảm công suất khi nhiệt độ tế bào tăng. Hệ số nhiệt Pmax của mẫu tham khảo là khoảng **-0,260%/°C**.

Giả sử nhiệt độ tế bào từ 25°C tăng đến 55°C (tăng 30°C), phép ước lượng tuyến tính cho mức suy giảm tương đối khoảng **7,8%** tại cùng điều kiện bức xạ. Đây chỉ là phép minh họa tác động nhiệt độ, không phải dự báo sản lượng của cả hệ thống.

Khi so sánh module, cần xem cả hệ số nhiệt, độ thông thoáng mái, khí hậu và hiệu suất toàn chuỗi.

## 8. Kích thước, khối lượng và tải trọng cơ học

Thông số cơ khí có ý nghĩa lớn đối với hệ mái: kích thước module quyết định số lượng có thể bố trí, khối lượng liên quan đến tải tĩnh, còn tải thiết kế của module không thay thế việc tính kết cấu mái và khung.

Với phiên bản LONGi đang tham khảo, datasheet BGV02 công bố kích thước **2.382 × 1.134 × 30 mm**, khối lượng **28,5 kg**. Thông số cơ khí của dòng module khác, kể cả cùng mức công suất, có thể thay đổi.

## 9. Bảo hành sản phẩm khác bảo đảm công suất

- **Bảo hành sản phẩm:** áp dụng cho các lỗi vật liệu, sản xuất hoặc gia công theo điều khoản của hãng.
- **Bảo đảm công suất:** mô tả ngưỡng công suất duy trì theo thời gian và cách áp dụng của chính sách bảo hành.

Datasheet mẫu được sử dụng có nội dung liên quan đến thời hạn bảo hành vật liệu và bảo đảm hiệu suất dài hạn. Tuy nhiên, để tư vấn cho một lô hàng cụ thể, cần xác minh tài liệu bảo hành chính thức hiện hành, thị trường áp dụng và giấy tờ mua hàng.

## 10. Những trường nên kiểm tra thêm

Ngoài mười nhóm thông số trên, người mua chuyên nghiệp nên kiểm tra:

- Điện áp hệ thống tối đa, loại đầu nối và chiều dài cáp.
- Kết cấu kính, khung, hộp nối và mức bảo vệ IP công bố.
- Chứng nhận phù hợp và kết quả thử nghiệm liên quan.
- Dòng/điện áp tương thích với biến tần thực tế.
- Điều kiện lắp đặt và hạn chế bảo hành của nhà sản xuất.

## Kết luận: đọc datasheet để chọn đúng hệ thống

Một module phù hợp không chỉ có công suất lớn. Lựa chọn nên dựa vào **model chính xác, hiệu suất, dải điện áp/dòng điện, đặc tính nhiệt, kích thước, khả năng chịu tải và bảo hành**.

Phúc Thịnh Solar khuyến nghị đối chiếu datasheet của hãng, nhu cầu sử dụng và thông số inverter trước khi thiết kế hệ điện mặt trời; không tự đấu nối dây DC hoặc chọn cấu hình chuỗi dựa trên công suất danh định.

### Nguồn tham khảo

- [LONGi – Hi-MO X10](https://www.longi.com/en/products/modules/hi-mo-x10-guardian-anti-glare/)
- [LONGi – Thư viện tài liệu sản phẩm](https://www.longi.com/vn/download/)
- [Datasheet LONGi LR7-72HVH 630–650M BGV02 (bản tham khảo)](https://static.longi.com/LR_7_72_HVH_630_650_M_30_30_and_15_Explorer_BGV_02_20241118_EN_Draft_426c93d9d1.pdf)

*Thông số minh họa dựa trên tài liệu mẫu; kiểm tra datasheet đúng mã và phiên bản mới nhất trước khi báo giá, thiết kế hoặc thi công. Ảnh bìa là ảnh tư liệu minh họa, không phải nhân sự Phúc Thịnh Solar.*
