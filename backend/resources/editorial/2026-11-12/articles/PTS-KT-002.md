# Cách đọc datasheet tấm pin năng lượng mặt trời: 10 thông số quan trọng

Hai tấm pin cùng ghi “650 W” chưa chắc phù hợp với cùng một mái nhà hay inverter. Nếu chỉ có 30 giây, hãy tìm trước **năm thông số**: mã model, Pmax, hiệu suất, Voc/Vmp và Isc/Imp. Sau đó mới đọc nhiệt độ, kích thước, tải cơ học và bảo hành.

Bài này dùng **LONGi Hi-MO X10 Explorer LR7-72HVH-650M** làm ví dụ. Các số dưới đây phải được đối chiếu với đúng mã hàng và phiên bản datasheet trước khi báo giá, thiết kế hoặc thi công.

![Infographic các thông số datasheet LONGi 650W gồm Pmax hiệu suất Voc Vmp Isc và Imp](/upload/news/pts-infographic-panel-650w.webp)

*Infographic biên soạn từ datasheet model tham khảo; kiểm tra phiên bản hiện hành trước khi sử dụng số liệu.*

## Bắt đầu với 5 thông số

1. **Model:** “Hi-MO X10” là tên dòng; mã **LR7-72HVH-650M** mới xác định biến thể đang đọc.
2. **Pmax:** công suất cực đại của module; mẫu này là **650 W tại STC**.
3. **Hiệu suất:** mẫu tham khảo công bố **24,1%**; quan trọng khi diện tích mái hạn chế.
4. **Voc và Vmp:** điện áp hở mạch và điện áp tại điểm công suất cực đại; dùng để kiểm tra dải MPPT và giới hạn DC của inverter.
5. **Isc và Imp:** dòng ngắn mạch và dòng tại điểm công suất cực đại; cần đối chiếu với dòng vào MPPT, dây dẫn và thiết bị bảo vệ.

## Đọc kỹ từng nhóm

### 1. Pmax, STC và NOCT/NMOT

Pmax là công suất cực đại tại điều kiện đo xác định. Mười tấm 650 Wp có tổng công suất DC danh định **6,5 kWp**, nhưng không thể lấy số này nhân toàn bộ số giờ ban ngày để suy ra điện năng.

STC thường dùng bức xạ 1.000 W/m², nhiệt độ tế bào 25°C và phổ AM1.5. Datasheet cũng có thể ghi NOCT/NMOT. Ở datasheet LONGi được tham khảo, model 650 W có **650 W tại STC** và **495 W tại NOCT**. Đây là hai điểm đo trong điều kiện khác nhau; 495 W không phải công suất cố định ngoài thực tế.

### 2. Hiệu suất

Hiệu suất module là tỷ lệ bức xạ trên diện tích tấm được chuyển thành điện trong điều kiện chuẩn. Công suất lớn hơn không tự động có hiệu suất lớn hơn, vì tấm có thể có kích thước lớn hơn. Mái chật cần chú ý hiệu suất; mái rộng còn phải xét bố trí, chi phí toàn hệ và độ bền.

### 3. Voc và Vmp: điện áp của chuỗi

**Voc** là điện áp khi mạch hở; mẫu tham khảo có **53,90 V tại STC**. Voc thường tăng khi tế bào lạnh, nên khi nối nhiều tấm thành chuỗi, kỹ sư phải tính điện áp ở nhiệt độ thấp nhất dự kiến và không vượt giới hạn đầu vào DC của inverter.

**Vmp** là điện áp tại điểm công suất cực đại; mẫu này có **44,56 V tại STC**. Tổng Vmp cũng phải nằm trong dải MPPT phù hợp ở các điều kiện nhiệt độ khác nhau.

### 4. Isc và Imp: dòng điện

**Isc** là dòng ngắn mạch; **Imp** là dòng tại điểm công suất cực đại. Bảng giá trị STC của model minh họa:

| Thông số | Giá trị |
|---|---:|
| Pmax | 650 W |
| Hiệu suất module | 24,1% |
| Voc | 53,90 V |
| Vmp | 44,56 V |
| Isc | 15,29 A |
| Imp | 14,59 A |

Khi thiết kế, phải xét cách nối song song các chuỗi và giới hạn dòng của MPPT, dây, đầu nối, cầu chì và thiết bị bảo vệ. Không đánh giá an toàn chuỗi chỉ bằng tổng watt.

### 5. Hệ số nhiệt Pmax

Tấm silicon thường giảm công suất khi tế bào nóng. Hệ số nhiệt Pmax của mẫu tham khảo khoảng **-0,260%/°C**. Nếu nhiệt độ tế bào tăng từ 25°C lên 55°C, phép ước lượng tuyến tính cho mức giảm tương đối khoảng **7,8%** ở cùng bức xạ. Đây là ví dụ về tác động nhiệt, không phải dự báo sản lượng cả hệ.

### 6. Kích thước, khối lượng và tải

Kích thước quyết định số tấm có thể đặt trên mái; khối lượng liên quan tải tĩnh. Với phiên bản LONGi tham khảo, datasheet BGV02 nêu **2.382 × 1.134 × 30 mm** và **28,5 kg**. Tải thiết kế của module không thay thế việc tính kết cấu mái và khung.

### 7. Bảo hành

Bảo hành sản phẩm xử lý lỗi vật liệu, sản xuất hoặc gia công theo điều khoản hãng. Bảo đảm công suất nói về ngưỡng công suất theo thời gian. Với lô hàng cụ thể, cần xác minh tài liệu bảo hành hiện hành, thị trường áp dụng và giấy tờ mua hàng.

### 8. Các trường nên kiểm tra thêm

Điện áp hệ thống tối đa, đầu nối và chiều dài cáp; kính, khung, hộp nối và IP; chứng nhận; giới hạn của inverter; điều kiện lắp đặt và các hạn chế bảo hành đều có thể ảnh hưởng quyết định. Datasheet không thay thế khảo sát mái hoặc thiết kế điện.

## Kết luận

Đọc datasheet tốt là xác định đúng model, kiểm tra năm thông số đầu tiên, rồi đối chiếu điện áp, dòng, nhiệt độ, kích thước và bảo hành với toàn hệ thống. Không tự đấu nối DC hoặc chọn cấu hình chuỗi chỉ từ công suất danh định.

### Nguồn tham khảo

- [LONGi – Hi-MO X10](https://www.longi.com/en/products/modules/hi-mo-x10-guardian-anti-glare/)
- [LONGi – Thư viện tài liệu sản phẩm](https://www.longi.com/vn/download/)
- [Datasheet LONGi LR7-72HVH 630–650M BGV02 (bản tham khảo)](https://static.longi.com/LR_7_72_HVH_630_650_M_30_30_and_15_Explorer_BGV_02_20241118_EN_Draft_426c93d9d1.pdf)

*Thông số minh họa dựa trên tài liệu mẫu; kiểm tra datasheet đúng mã và phiên bản mới nhất trước khi dùng. Ảnh bìa là ảnh tư liệu minh họa, không phải nhân sự Phúc Thịnh Solar.*
