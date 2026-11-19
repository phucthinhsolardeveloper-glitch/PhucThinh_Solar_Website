Hai tấm pin cùng ghi "650 W" có thể khác nhau về kích thước, điện áp, dòng điện, cách chịu nhiệt và điều kiện bảo hành. Bảng thông số của hãng (datasheet) chứa đủ những dữ liệu đó. Người mua chỉ cần đọc đúng năm dòng đầu để so sánh; kỹ thuật viên cần đọc thêm phần điện áp, dòng điện để ghép với inverter.

Bài này dùng **LONGi Hi-MO X10 Explorer LR7-72HVH-650M** làm ví dụ, theo datasheet LONGi bản BGV02. Mọi số liệu phải được đối chiếu với **đúng mã hàng và đúng phiên bản datasheet** trước khi dùng để báo giá hay thiết kế.

![Infographic các thông số datasheet LONGi 650W gồm Pmax hiệu suất Voc Vmp Isc và Imp](/upload/news/pts-infographic-panel-650w.webp)

*Infographic biên soạn từ datasheet model tham khảo. Kiểm tra phiên bản hiện hành trước khi dùng số liệu để thiết kế.*

## Đọc nhanh: năm dòng người mua nên xem trước

| Dòng trên datasheet | Giá trị của model ví dụ | Trả lời câu hỏi |
|---|---|---|
| Mã model | LR7-72HVH-650M | Đang so đúng sản phẩm nào? |
| Pmax tại STC | 650 W | Công suất danh định để cộng ra kWp |
| Hiệu suất module | 24,1% | Mỗi mét vuông mái tạo được bao nhiêu điện |
| Kích thước, khối lượng | 2.382 × 1.134 × 30 mm; 28,5 kg | Mái đặt được bao nhiêu tấm, mái chịu bao nhiêu tải |
| Hệ số nhiệt Pmax | khoảng −0,260%/°C | Trời nóng thì công suất giảm nhanh hay chậm |

Phần chuyên sâu bên dưới đi qua từng thông số, theo thứ tự từ quen thuộc đến kỹ thuật.

## 1. Model và Pmax: công suất danh định

Tên thương mại như "Hi-MO X10" có thể bao gồm nhiều mức công suất và biến thể. Chỉ mã đầy đủ **LR7-72HVH-650M** mới xác định chính xác phiên bản đang xem.

**Pmax (Maximum Power)** là công suất cực đại của tấm pin tại điều kiện thử nghiệm xác định. Với model ví dụ, Pmax tại STC là **650 W**. Mười tấm cộng lại được **6,5 kWp** công suất DC danh định. Không thể lấy 6,5 kW nhân số giờ ban ngày để ra chính xác điện năng mỗi ngày; cách đổi giữa kWp và kWh có ở bài [kW, kWp và kWh khác nhau thế nào](/kw-kwp-va-kwh-khac-nhau-the-nao-7-vi-du-tranh-nham).

Một phép kiểm tra nhỏ: tích Vmp × Imp trong bảng điện của hãng là 44,56 V × 14,59 A ≈ 650,1 W, khớp với Pmax. Nếu một báo giá ghi số không khớp kiểu này, hãy hỏi lại nguồn số liệu.

## 2. STC và NOCT: hai điều kiện đo khác nhau

STC (Standard Test Conditions) dùng bức xạ 1.000 W/m², nhiệt độ tế bào 25°C và phổ sáng AM1.5. Đây là mốc chung để so các tấm pin với nhau.

Datasheet còn ghi NOCT/NMOT, một điều kiện thử nghiệm hoặc vận hành danh định khác STC. Ở datasheet LONGi tham khảo, model 650 W đạt **650 W tại STC** và **495 W tại NOCT**.

Hai con số này là hai điểm đo trong hai điều kiện khác nhau. **495 W không phải công suất cố định ngoài thực tế**, và 650 W cũng không phải cam kết phát liên tục trong mọi điều kiện.

## 3. Hiệu suất module

Hiệu suất cho biết bao nhiêu phần năng lượng bức xạ chiếu lên diện tích tấm pin được đổi thành điện ở điều kiện chuẩn. LONGi công bố hiệu suất **24,1%** cho LR7-72HVH-650M.

Có thể tự kiểm tra từ kích thước: 2,382 m × 1,134 m ≈ 2,70 m². Ở 1.000 W/m², tấm pin nhận khoảng 2.700 W bức xạ; 650 W chia 2.700 W ≈ 24,1%. Con số khớp với hãng công bố.

Đừng nhầm "công suất cao hơn" với "hiệu suất cao hơn". Một tấm lớn hơn có thể có công suất danh định lớn hơn dù hiệu suất bằng hoặc thấp hơn một tấm nhỏ. Mái chật thì hiệu suất là thông số quan trọng. Mái rộng thì cần xét thêm tổng chi phí hệ thống, độ bền và cách bố trí tấm pin.

## 4. Voc: điện áp hở mạch

**Voc** là điện áp đo được khi hai cực tấm pin không cấp dòng cho tải. Model ví dụ có Voc tại STC là **53,90 V**.

Voc tăng khi nhiệt độ tế bào giảm. Vì vậy khi nối nhiều tấm thành chuỗi, kỹ sư phải tính **điện áp hở mạch của cả chuỗi tại nhiệt độ thấp nhất dự kiến**, dùng hệ số nhiệt của Voc ghi trên datasheet, và bảo đảm không vượt giới hạn đầu vào DC của inverter.

Ví dụ minh họa: một chuỗi 10 tấm có tổng Voc tại STC là 10 × 53,90 V = 539 V. Buổi sáng trời lạnh, điện áp chuỗi sẽ cao hơn con số này. Không được đánh giá an toàn chuỗi pin chỉ bằng cách cộng công suất watt.

## 5. Vmp: điện áp tại điểm công suất cực đại

**Vmp** là điện áp tại điểm vận hành cho công suất lớn nhất trong một điều kiện đo. Model LONGi tham khảo có **Vmp = 44,56 V tại STC**.

Khi thiết kế chuỗi, tổng Vmp ở các mức nhiệt độ khác nhau phải nằm trong dải MPPT phù hợp của inverter. Cách đọc dải MPPT phía inverter có ở bài [Inverter điện mặt trời là gì](/inverter-dien-mat-troi-la-gi-no-thuc-hien-nhung-chuc-nang-nao).

## 6. Isc và Imp: hai giá trị dòng điện

**Isc** là dòng ngắn mạch tại điều kiện đo. **Imp** là dòng điện tại điểm công suất cực đại.

Dữ liệu STC của model ví dụ:

| Thông số | Giá trị |
|---|---|
| Pmax | 650 W |
| Hiệu suất module | 24,1% |
| Voc | 53,90 V |
| Vmp | 44,56 V |
| Isc | 15,29 A |
| Imp | 14,59 A |

Các giá trị dòng phải được đối chiếu với dòng vào cho phép của MPPT, khả năng chịu dòng của dây dẫn, đầu nối và thiết bị bảo vệ. Khi nối song song nhiều chuỗi, dòng cộng lại nên càng phải kiểm tra kỹ.

## 7. Hệ số nhiệt của công suất

Tấm pin silicon thông thường giảm công suất khi nhiệt độ tế bào tăng. Hệ số nhiệt Pmax của model tham khảo khoảng **−0,260%/°C**.

Ví dụ: nhiệt độ tế bào tăng từ 25°C lên 55°C, tức tăng 30°C. Ước lượng tuyến tính cho mức giảm tương đối khoảng 30 × 0,26% = **7,8%** ở cùng điều kiện bức xạ, nghĩa là 650 W còn khoảng 599 W. Đây chỉ là phép minh họa tác động của nhiệt, không phải dự báo sản lượng của cả hệ thống.

Khi so sánh các tấm pin, hãy xem cùng lúc hệ số nhiệt, độ thông thoáng dưới mái, khí hậu nơi lắp và hiệu suất của cả chuỗi.

## 8. Kích thước, khối lượng và tải trọng cơ học

Kích thước quyết định số tấm đặt vừa mái. Khối lượng liên quan đến tải tĩnh lên mái. Tải thiết kế của tấm pin không thay thế việc tính kết cấu mái và khung đỡ.

Phiên bản LONGi tham khảo, theo datasheet BGV02: **2.382 × 1.134 × 30 mm**, **28,5 kg**. Quy ra, riêng tấm pin nặng khoảng 10,5 kg mỗi mét vuông; mười tấm khoảng 285 kg, chưa tính khung, kẹp và dây. Dòng module khác, kể cả cùng mức công suất, có thể có thông số cơ khí khác.

## 9. Bảo hành sản phẩm khác bảo đảm công suất

- **Bảo hành sản phẩm:** áp dụng cho lỗi vật liệu, sản xuất hoặc gia công theo điều khoản của hãng.
- **Bảo đảm công suất:** mô tả ngưỡng công suất được duy trì theo thời gian và cách áp dụng của chính sách bảo hành.

Datasheet mẫu có nội dung về thời hạn bảo hành vật liệu và bảo đảm hiệu suất dài hạn. Với một lô hàng cụ thể, cần xác minh tài liệu bảo hành chính thức hiện hành, thị trường áp dụng và giấy tờ mua hàng.

## 10. Những dòng nên kiểm tra thêm

- Điện áp hệ thống tối đa, loại đầu nối và chiều dài cáp.
- Kết cấu kính, khung, hộp nối và cấp bảo vệ IP được công bố.
- Chứng nhận phù hợp và kết quả thử nghiệm liên quan.
- Dòng và điện áp có tương thích với inverter thực tế không.
- Điều kiện lắp đặt và giới hạn bảo hành của nhà sản xuất.

## Tự kiểm tra: bạn đọc datasheet đúng chưa?

1. Báo giá ghi "6,5 kW mỗi ngày" cho 10 tấm 650 W. Sai ở đâu? *6,5 kWp là công suất danh định, không phải điện năng mỗi ngày.*
2. Tấm A 650 W, tấm B 620 W. Tấm A luôn hiệu suất cao hơn? *Chưa chắc; phải chia công suất cho diện tích.*
3. Chuỗi pin ổn vào buổi trưa nóng thì cũng ổn vào buổi sáng lạnh? *Không chắc; Voc tăng khi lạnh, phải tính ở nhiệt độ thấp nhất.*
4. Con số 495 W tại NOCT có phải công suất thật trên mái? *Không; đó là một điều kiện đo khác STC.*

## Khi dùng datasheet để chọn tấm pin

Một tấm pin phù hợp không chỉ có công suất lớn. Lựa chọn nên dựa vào **model chính xác, hiệu suất, dải điện áp và dòng điện, đặc tính nhiệt, kích thước, khả năng chịu tải và bảo hành**, rồi đối chiếu với nhu cầu sử dụng và thông số inverter. Không tự đấu nối dây DC hoặc chọn cấu hình chuỗi dựa trên công suất danh định.

## Nguồn tham khảo

- [LONGi – Hi-MO X10](https://www.longi.com/en/products/modules/hi-mo-x10-guardian-anti-glare/)
- [LONGi – Thư viện tài liệu sản phẩm](https://www.longi.com/vn/download/)
- [Datasheet LONGi LR7-72HVH 630–650M BGV02 (bản tham khảo)](https://static.longi.com/LR_7_72_HVH_630_650_M_30_30_and_15_Explorer_BGV_02_20241118_EN_Draft_426c93d9d1.pdf)

*Thông số minh họa dựa trên datasheet bản tham khảo; kiểm tra datasheet đúng mã và phiên bản mới nhất trước khi báo giá, thiết kế hoặc thi công.*
