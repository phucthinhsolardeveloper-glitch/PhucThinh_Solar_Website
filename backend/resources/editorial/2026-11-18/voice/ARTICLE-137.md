Hai tấm pin cùng ghi “650 W” vẫn có thể khác về hiệu suất, điện áp, dòng điện, kích thước và bảo hành. Datasheet giúp bạn so đúng model và kiểm tra tấm pin có phù hợp với inverter, mái nhà hay không.

![Infographic thông số datasheet tấm pin LONGi 650W](/upload/news/pts-infographic-panel-650w.webp)

### Đọc nhanh

- Xác định đúng mã sản phẩm và phiên bản datasheet.
- So công suất, hiệu suất, điện áp, dòng điện và hệ số nhiệt.
- Kiểm tra kích thước, khối lượng và điều khoản bảo hành.
- Dùng thông số đúng model khi thiết kế chuỗi với inverter.

Bài viết dùng LONGi Hi-MO X10 Explorer LR7-72HVH-650M làm ví dụ. Các con số dưới đây thuộc datasheet tham khảo của model này; khi báo giá hoặc thiết kế, cần dùng tài liệu đúng mã hàng.

## Xác định model và điều kiện đo

### Model và Pmax cho biết điều gì?

Tên dòng như “Hi-MO X10” gồm nhiều phiên bản. Mã LR7-72HVH-650M giúp xác định đúng tấm pin đang được nói đến.

Pmax là công suất cực đại của module trong điều kiện đo quy định. Model ví dụ có Pmax 650 W tại STC. Mười tấm 650 Wp cộng lại thành 6,5 kWp công suất DC ghi trên nhãn. Không thể nhân 6,5 kW với toàn bộ số giờ ban ngày để biết chính xác sản lượng.

### STC và NOCT/NMOT là hai điều kiện đo

STC (điều kiện thử chuẩn) dùng bức xạ 1.000 W/m², nhiệt độ tế bào 25°C và phổ sáng AM1.5. Đây là mốc chung để so thông số giữa các tấm pin.

Datasheet cũng có thể ghi NOCT/NMOT, là điều kiện đo khác STC.

Trong datasheet LONGi tham khảo, Pmax là 650 W tại STC và 495 W tại NOCT.

Hai số này ứng với hai điều kiện đo. 495 W không phải công suất cố định ngoài thực tế. 650 W cũng không phải mức phát liên tục trong mọi điều kiện.

### Hiệu suất giúp so công suất trên diện tích

Hiệu suất module cho biết tỷ lệ năng lượng ánh sáng trên diện tích tấm pin được chuyển thành điện trong điều kiện chuẩn. LONGi LR7-72HVH-650M được hãng công bố hiệu suất 24,1%.

Công suất cao hơn không đồng nghĩa hiệu suất cao hơn. Một tấm lớn hơn có thể ghi công suất cao hơn dù hiệu suất bằng hoặc thấp hơn tấm nhỏ. Với mái chật, hiệu suất đáng quan tâm; với mái rộng, hãy tính cả chi phí hệ thống và cách bố trí tấm.

## Điện áp và dòng điện cần khớp với inverter

### Voc và Vmp giúp kiểm tra điện áp

| Ký hiệu | Nghĩa | Bạn cần quan tâm vì |
|---|---|---|
| Voc | Điện áp hở mạch, khi tấm pin không cấp dòng cho tải | Voc tăng khi tế bào lạnh. Điện áp chuỗi không được vượt giới hạn DC của inverter. |
| Vmp | Điện áp tại điểm công suất cực đại | Tổng Vmp của chuỗi cần nằm trong dải MPPT của inverter. |

Trong ví dụ, Voc tại STC là 53,90 V và Vmp là 44,56 V. Khi thiết kế, kỹ sư tính điện áp chuỗi theo điều kiện nhiệt độ và so với giới hạn của inverter.

### Isc và Imp là hai mức dòng khác nhau

Isc là dòng ngắn mạch tại điều kiện đo; Imp là dòng tại điểm công suất cực đại. Bảng dưới đây giữ các thông số STC của model tham khảo.

| Thông số | Giá trị |
|---|---:|
| Pmax | 650 W |
| Hiệu suất module | 24,1% |
| Voc | 53,90 V |
| Vmp | 44,56 V |
| Isc | 15,29 A |
| Imp | 14,59 A |

Dòng điện cần phù hợp với đầu vào MPPT, dây dẫn, đầu nối và thiết bị bảo vệ. Khi ghép các chuỗi song song, cần tính tổng dòng.

## Nhiệt độ, kích thước và bảo hành ảnh hưởng thế nào?

### Hệ số nhiệt cho biết ảnh hưởng của nhiệt độ

Tấm pin silicon thường giảm công suất khi tế bào nóng lên. Hệ số nhiệt Pmax của model tham khảo khoảng 0,260%/°C.

Nếu nhiệt độ tế bào tăng từ 25°C lên 55°C, tức tăng 30°C. Phép tính tuyến tính minh họa mức giảm tương đối khoảng 7,8% trong cùng điều kiện bức xạ. Đây không phải dự báo sản lượng của cả hệ thống. Khi so module, hãy xem hệ số nhiệt cùng độ thông thoáng mái và điều kiện lắp.

### Kích thước và khối lượng có ảnh hưởng đến cách lắp

Kích thước quyết định cách bố trí trên mái; khối lượng liên quan đến tải tĩnh. Tải thiết kế của tấm pin không thay cho việc tính kết cấu mái và khung.

Datasheet BGV02 được tham khảo ghi kích thước 2.382 × 1.134 × 30 mm và khối lượng 28,5 kg cho phiên bản LONGi này. Cùng mức công suất nhưng khác dòng sản phẩm có thể khác kích thước và khối lượng.

### Đọc riêng điều khoản bảo hành

Bảo hành sản phẩm áp dụng cho lỗi vật liệu, sản xuất hoặc gia công theo điều khoản của hãng. Bảo hành hiệu suất nói về mức công suất duy trì theo thời gian và cách áp dụng.

Datasheet tham khảo có nội dung về bảo hành vật liệu và hiệu suất dài hạn. Với lô hàng cụ thể, hãy xem tài liệu bảo hành chính thức hiện hành, thị trường áp dụng và giấy tờ mua hàng.

## Hoàn tất việc kiểm tra datasheet

### Những trường khác nên xem

- Điện áp hệ thống tối đa, loại đầu nối và chiều dài cáp.
- Kết cấu kính, khung, hộp nối và cấp bảo vệ IP.
- Chứng nhận, kết quả thử nghiệm và điều kiện bảo hành.
- Dòng, điện áp tương thích với inverter đang dùng.
- Điều kiện lắp đặt và các giới hạn của nhà sản xuất.

Một tấm pin phù hợp không chỉ dựa vào công suất. Hãy so model, hiệu suất, điện áp, dòng điện, đặc tính nhiệt, kích thước và bảo hành. Việc chọn chuỗi cần dùng datasheet đúng mã và thông số inverter.

### Nguồn tham khảo

- [LONGi – Hi-MO X10](https://www.longi.com/en/products/modules/hi-mo-x10-guardian-anti-glare/)
- [LONGi – Thư viện tài liệu sản phẩm](https://www.longi.com/vn/download/)
- [Datasheet LONGi LR7-72HVH-630–650M BGV02](https://static.longi.com/LR_7_72_HVH_630_650_M_30_30_and_15_Explorer_BGV_02_20241118_EN_Draft_426c93d9d1.pdf)

Thông số minh họa lấy từ datasheet tham khảo; cần dùng bản đúng mã và phiên bản mới nhất trước khi báo giá, thiết kế hoặc thi công. Ảnh bìa là ảnh tư liệu, không phải nhân sự Phúc Thịnh Solar.
