Để tính mái nhà tạo ra bao nhiêu điện mỗi tháng, bạn dùng công cụ miễn phí PVGIS của Ủy ban châu Âu (JRC). Bạn chỉ cần nhập tọa độ, công suất dàn pin (kWp), mức tổn hao, độ dốc và hướng mái để lấy bảng sản lượng dự kiến.

## Vì sao nên tính sản lượng theo đúng tọa độ mái nhà?

Theo Bộ Năng lượng Hoa Kỳ (DOE), sản lượng điện trên mái phụ thuộc vào vị trí địa lý, diện tích, hướng mái, độ nghiêng và bóng che. Hai ngôi nhà lắp cùng dàn pin 5 kWp nhưng ở hai tỉnh khác nhau, hoặc xoay khác hướng, sẽ cho ra số điện chênh nhau rõ rệt.

Ứng dụng PVGIS (bản mặc định 5.3) dùng dữ liệu bức xạ và nhiệt độ theo giờ để tính điện xoay chiều (AC) sau inverter. Tại Việt Nam, công cụ tự động lấy dữ liệu khí tượng PVGIS-ERA5 giai đoạn 2005–2023. Phần mềm cũng kết hợp bản đồ địa hình để tính bóng che ở đường chân trời.

## Cách nhập thông số mái nhà trên PVGIS

Bạn mở giao diện web PVGIS không cần tạo tài khoản, rồi điền bốn nhóm thông số ở thẻ nối lưới (Grid-connected):

- **Bước 1 – Chọn vị trí.** Bạn chấm vị trí nhà lên bản đồ, hoặc nhập vĩ độ (Latitude) và kinh độ (Longitude) dạng số thập phân. Ở khung bên phải, bạn giữ mục mặc định `Calculated horizon` để máy tính bóng che địa hình.
- **Bước 2 – Nhập loại pin và công suất.** Tại ô `PV technology`, bạn chọn pin tinh thể silicon (`Crystalline silicon`). Ở ô `Installed peak power`, bạn điền công suất đỉnh trên nhãn dàn pin theo kWp. Mức này đo ở chuẩn STC (bức xạ 1000 W/m², nhiệt độ pin 25°C). Bạn nhập `1` cho hệ 1 kWp, hoặc `5` cho hệ 5 kWp.
- **Bước 3 – Điền tổn hao và kiểu lắp.** Ô `System loss` mặc định là 14% để trừ hao trên dây dẫn, inverter, bụi bẩn và độ suy giảm theo năm. Hao hụt do tấm pin nóng lên sẽ được phần mềm tính riêng theo nhiệt độ vùng. Ở mục `Mounting position`, bạn chọn `Free-standing` khi khung pin thoáng gió phía sau. Kiểu lắp áp kín (`Building-integrated`) làm tấm pin nóng thêm tới 15°C dưới nắng 1000 W/m².
- **Bước 4 – Nhập góc nghiêng và hướng mái.** Ô `Slope` là góc nghiêng tấm pin so với mặt ngang. Ô `Azimuth` là góc hướng mái tính từ hướng chính Nam: Nam nhập `0°`, Đông nhập `-90°` và Tây nhập `90°`. Nếu làm khung tự chọn góc, bạn đánh dấu `Optimize slope and azimuth` để máy tìm góc tối ưu.

Điền xong, bạn bấm `Visualize results` để xem biểu đồ từng tháng và tải báo cáo PDF hoặc CSV.

## Chạy thử tại TP.HCM cho ra con số bao nhiêu?

Ví dụ dưới đây là kết quả PVGIS 5.3 cho trung tâm TP.HCM (vĩ độ 10,776° Bắc, kinh độ 106,701° Đông, độ cao 14 m). Hệ thống thử dùng pin tinh thể silicon lắp thoáng gió, mái nghiêng 10°, hướng chính Nam (0°) và tổn hao 14%:

| Đầu ra từ PVGIS 5.3 (ERA5 2005–2023) | Hệ 1 kWp | Hệ 5 kWp |
|---|---|---|
| Sản lượng trung bình ngày (`E_d`) | 3,86 kWh/ngày | 19,29 kWh/ngày |
| Sản lượng trung bình tháng (`E_m`) | 117,36 kWh/tháng | 586,81 kWh/tháng |
| Tổng sản lượng trung bình năm (`E_y`) | 1.408,34 kWh/năm | 7.041,69 kWh/năm |
| Độ lệch chuẩn sản lượng năm (`SD_y`) | 35,14 kWh | 175,68 kWh |
| Bức xạ trên mặt phẳng tấm pin (`H(i)_y`) | 1.887,34 kWh/m²/năm | 1.887,34 kWh/m²/năm |

Ngoài 14% tổn hao đã nhập, dàn pin mất thêm 11,49% vì nóng và 2,75% vì nắng chiếu xiên. Gộp lại, PVGIS tính tổng hao hụt là 25,38%. Con số 1.408 kWh mỗi năm cho 1 kWp ở trên đã trừ hết các khoản này.

Tính cho 1 kWp tại tọa độ này, tháng 3 đạt tổng điện cao nhất năm với 142,65 kWh/tháng (4,60 kWh/ngày). Tháng 2 đạt mức trung bình ngày cao nhất là 4,61 kWh/ngày (129,14 kWh/tháng). Vào mùa mưa, sản lượng hạ xuống 104,24 kWh/tháng trong tháng 9 (3,47 kWh/ngày) và 104,56 kWh/tháng trong tháng 7 (3,37 kWh/ngày). Khi bật tự tìm góc tối ưu (nghiêng 9°, lệch Đông Nam -32°), sản lượng năm của 1 kWp đạt 1.412,09 kWh/năm.

## Dùng con số này thế nào khi làm việc với bên lắp đặt?

Bảng sản lượng từng tháng từ PVGIS giúp bạn so với hóa đơn tiền điện mùa nắng và mùa mưa. Từ đó, bạn chọn được cỡ dàn pin sát với lượng điện gia đình dùng ban ngày.

Dữ liệu vệ tinh chỉ tính được bóng núi đồi ở xa. Khi khảo sát mái thực tế, bạn nên xem kỹ bóng đổ của bồn nước, tum cầu thang, nhà kế bên và khe thoáng gió dưới khung pin.

## Nguồn tham khảo

- [European Commission Joint Research Centre – Photovoltaic Geographical Information System (PVGIS)](https://joint-research-centre.ec.europa.eu/photovoltaic-geographical-information-system-pvgis_en)
- [European Commission Joint Research Centre – PVGIS 5 User Manual](https://joint-research-centre.ec.europa.eu/photovoltaic-geographical-information-system-pvgis/using-pvgis-5/pvgis-5-user-manual_en)
- [European Commission Joint Research Centre – PVGIS API Non-Interactive Service](https://joint-research-centre.ec.europa.eu/photovoltaic-geographical-information-system-pvgis/using-pvgis-5/api-non-interactive-service_en)
- [U.S. Department of Energy – Solar Rooftop Potential](https://www.energy.gov/cmei/systems/solar-rooftop-potential)
