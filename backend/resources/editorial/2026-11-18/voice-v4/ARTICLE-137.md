Hai tấm pin cùng ghi “650 W” vẫn có thể khác về hiệu suất, điện áp, dòng điện, kích thước và bảo hành. Datasheet giúp bạn so đúng model và kiểm tra tấm pin có phù hợp với inverter, mái nhà hay không.

![Infographic thông số datasheet tấm pin LONGi 650W](/upload/news/pts-infographic-panel-650w.webp)

### Đọc nhanh

- Xác định đúng mã sản phẩm và phiên bản datasheet.
- So công suất, hiệu suất, điện áp, dòng điện và hệ số nhiệt.
- Kiểm tra kích thước, khối lượng và điều khoản bảo hành.
- Dùng thông số đúng model khi thiết kế chuỗi với inverter.

Các thông số dưới đây thuộc LONGi Hi-MO X10 Explorer LR7-72HVH-650M, theo datasheet BGV02.

## Xác định model và điều kiện đo

### Model và Pmax cho biết điều gì?

Tên dòng như “Hi-MO X10” gồm nhiều phiên bản. Mã LR7-72HVH-650M xác định phiên bản cụ thể.

Pmax là công suất cực đại của module trong điều kiện đo quy định. Model ví dụ có Pmax 650 W tại STC. Mười tấm 650 Wp cộng lại thành 6,5 kWp công suất DC ghi trên nhãn. Sản lượng cần tính theo bức xạ, nhiệt độ và tổn thất của hệ thống.

### STC và NOCT/NMOT là hai điều kiện đo

STC (điều kiện thử chuẩn) dùng bức xạ 1.000 W/m², nhiệt độ tế bào 25°C và phổ sáng AM1.5. Đây là mốc chung để so thông số giữa các tấm pin.

Datasheet cũng có thể ghi NOCT/NMOT, là điều kiện đo khác STC.

Trong datasheet LONGi tham khảo, Pmax là 650 W tại STC và 495 W tại NOCT.

Hai số ứng với hai điều kiện đo khác nhau; công suất ngoài mái thay đổi theo điều kiện vận hành.

### Hiệu suất giúp so công suất trên diện tích

Hiệu suất module cho biết tỷ lệ năng lượng ánh sáng trên diện tích tấm pin được chuyển thành điện trong điều kiện chuẩn. LONGi LR7-72HVH-650M được hãng công bố hiệu suất 24,1%.

Tấm lớn hơn có thể ghi công suất cao hơn dù hiệu suất bằng hoặc thấp hơn tấm nhỏ. Mái chật cần so hiệu suất; mái rộng cần tính cả chi phí hệ thống và cách bố trí.

## Điện áp và dòng điện cần khớp với inverter

### Voc và Vmp giúp kiểm tra điện áp

| Ký hiệu | Nghĩa | Bạn cần quan tâm vì |
|---|---|---|
| Voc | Điện áp hở mạch, khi tấm pin không cấp dòng cho tải | Voc tăng khi tế bào lạnh. Điện áp chuỗi không được vượt giới hạn DC của inverter. |
| Vmp | Điện áp tại điểm công suất cực đại | Tổng Vmp của chuỗi cần nằm trong dải MPPT của inverter. |

Model này có Voc 53,90 V và Vmp 44,56 V tại STC. Kỹ sư dùng nhiệt độ dự kiến để tính điện áp chuỗi rồi so với giới hạn inverter.

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

Dòng điện cần khớp với đầu vào MPPT, dây dẫn, đầu nối và thiết bị bảo vệ; ghép song song làm tổng dòng tăng.

## Nhiệt độ, kích thước và bảo hành ảnh hưởng thế nào?

### Hệ số nhiệt cho biết ảnh hưởng của nhiệt độ

Tấm pin silicon thường giảm công suất khi tế bào nóng lên. Hệ số nhiệt Pmax của model khoảng -0,260%/°C.

Khi nhiệt độ tế bào tăng từ 25°C lên 55°C (30°C), phép tính tuyến tính cho mức giảm công suất khoảng 7,8% ở cùng bức xạ. Khi so module, xem hệ số nhiệt cùng độ thông thoáng mái.

### Kích thước và khối lượng có ảnh hưởng đến cách lắp

Kích thước quyết định cách bố trí trên mái; khối lượng liên quan đến tải tĩnh. Kỹ sư cần tính riêng tải trọng mái và khung.

Datasheet BGV02 được tham khảo ghi kích thước 2.382 × 1.134 × 30 mm và khối lượng 28,5 kg cho phiên bản LONGi này. Cùng mức công suất nhưng khác dòng sản phẩm có thể khác kích thước và khối lượng.

### Đọc riêng điều khoản bảo hành

Bảo hành sản phẩm áp dụng cho lỗi vật liệu, sản xuất hoặc gia công theo điều khoản của hãng. Bảo hành hiệu suất nói về mức công suất duy trì theo thời gian và cách áp dụng.

Điều khoản bảo hành vật liệu và hiệu suất dài hạn cần đọc theo tài liệu áp dụng cho lô hàng và thị trường.

## Hoàn tất việc kiểm tra datasheet

### Những trường khác nên xem

- Điện áp hệ thống tối đa, loại đầu nối và chiều dài cáp.
- Kết cấu kính, khung, hộp nối và cấp bảo vệ IP.
- Chứng nhận, kết quả thử nghiệm và điều kiện bảo hành.
- Dòng, điện áp tương thích với inverter đang dùng.
- Điều kiện lắp đặt và các giới hạn của nhà sản xuất.

Chọn tấm theo model, hiệu suất, điện áp, dòng điện, đặc tính nhiệt, kích thước và bảo hành. Thiết kế chuỗi dựa trên datasheet của model và thông số inverter.

### Nguồn tham khảo

- [LONGi – Hi-MO X10](https://www.longi.com/en/products/modules/hi-mo-x10-guardian-anti-glare/)
- [LONGi – Thư viện tài liệu sản phẩm](https://www.longi.com/vn/download/)
- [Datasheet LONGi LR7-72HVH-630–650M BGV02](https://static.longi.com/LR_7_72_HVH_630_650_M_30_30_and_15_Explorer_BGV_02_20241118_EN_Draft_426c93d9d1.pdf)


