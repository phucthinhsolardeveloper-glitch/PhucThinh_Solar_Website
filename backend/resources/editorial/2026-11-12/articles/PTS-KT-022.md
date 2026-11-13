## Mái hướng Đông – Tây có lắp điện mặt trời hiệu quả không?

Có. Mái Đông–Tây không tự động là lựa chọn kém; nó thường tạo ra hai đỉnh phát điện khác nhau trong ngày thay vì dồn nhiều điện vào khoảng giữa trưa. Hiệu quả thực tế phải tính từ vị trí, bóng che, độ nghiêng, diện tích dùng được và lịch sử dụng điện của công trình. Không nên lấy một tỷ lệ tham khảo của châu Âu làm định mức cho Pleiku hay bất kỳ địa điểm nào ở Việt Nam.

### Điều hướng mái thay đổi điều gì?

Mặt mái hướng Đông nhận nắng buổi sáng, còn mặt Tây nhận nắng buổi chiều. Nếu hai mặt có cùng diện tích và ít bị che, sản lượng ngày có thể được phân bổ rộng hơn. Điều đó có thể phù hợp với nhà dùng điện khi bắt đầu ngày và cuối buổi chiều, nhưng chưa nói được tổng điện năm sẽ là bao nhiêu.

DOE lưu ý tiềm năng điện mặt trời trên mái phụ thuộc vào kích thước, hướng, bóng che và vị trí địa lý. Với một hệ lắp thật, cần thêm góc nghiêng, loại module, nhiệt độ, tổn hao dây và giới hạn inverter. Một mái hướng Tây bị nhà bên cạnh che từ 15 giờ có thể kém hơn một mái Đông–Tây thoáng, dù tên hướng nghe giống nhau.

### Vì sao Pleiku cần số liệu riêng?

Bức xạ thay đổi theo tọa độ, thời tiết và thời điểm trong năm. Vì vậy, thay vì nói “hướng Đông–Tây giảm X phần trăm”, hãy nhập tọa độ công trình, công suất dự kiến, hướng và góc nghiêng vào PVGIS hoặc công cụ mô phỏng tương đương. Kết quả đó vẫn là ước tính, không phải cam kết sản lượng; nó chỉ có ý nghĩa khi thông số đầu vào đúng.

Khi lập một mô phỏng cho Pleiku, nên lưu lại: tọa độ, công suất DC, loại module, góc nghiêng, phương vị từng mặt mái, tổn hao hệ thống, dữ liệu thời tiết và khoảng thời gian mô phỏng. Nếu chưa có khảo sát bóng che, phải ghi rõ phần thiếu này. Một kết quả đẹp nhưng bỏ qua cây, bồn nước hoặc nhà cao tầng không đủ để chốt báo giá.

### Tách hai câu hỏi: tổng điện hay điện đúng giờ?

Một gia đình có điều hòa và bơm nước hoạt động buổi trưa có thể ưu tiên công suất phát ở giữa ngày. Một cửa hàng mở sớm và đóng lúc 20 giờ lại quan tâm đến cả buổi sáng lẫn chiều. Mái Đông–Tây có thể giúp đường phát điện khớp hơn với lịch tải, nhưng nếu công trình dùng nhiều điện ban đêm thì cần xem thêm pin lưu trữ hoặc điện lưới.

Hãy lập bảng theo giờ cho những tải lớn: điều hòa, bơm, bình nước nóng, máy nén và thiết bị sản xuất. Ghi công suất định mức chỉ là bước đầu; thời gian chạy và dòng khởi động cũng ảnh hưởng đến thiết kế. Đừng suy ra khả năng dự phòng khi mất điện chỉ từ hướng mái: inverter hòa lưới thông thường vẫn phải dừng khi lưới mất để chống đảo lưới.

### Bố trí hai mặt mái thế nào?

Không nên nối các tấm có điều kiện bức xạ rất khác nhau vào cùng một MPPT nếu điều đó vượt hướng dẫn của inverter. Có thể cần tách các mặt mái thành các dải MPPT riêng; số lượng tấm mỗi dải phải nằm trong dải điện áp và dòng điện cho phép của đúng model inverter. Đây là điểm cần kiểm tra bằng datasheet, không thể quyết định bằng số tấm tổng cộng.

Khảo sát cũng phải kiểm tra lối đi, thoát nước, kết cấu, khoảng cách mép mái và bóng đổ theo mùa. Ảnh stock hoặc bìa concept chỉ minh họa chủ đề, không chứng minh công trình tại Pleiku. Bài này chưa gắn ảnh thực; cần chờ ảnh có quyền sử dụng hoặc sơ đồ tự thiết kế được kỹ thuật kiểm tra.

### Checklist trước khi kết luận

1. Ghi tọa độ công trình và đo phương vị từng mặt mái.
2. Chụp bóng che vào buổi sáng, trưa và chiều; ghi vật cản cố định.
3. Lấy ít nhất 12 tháng hóa đơn và lịch tải theo giờ.
4. Mô phỏng từng mặt mái bằng dữ liệu địa phương, lưu giả định và tổn hao.
5. Đối chiếu số tấm, Voc, Vmp, Isc, Imp với từng MPPT của inverter.
6. Tách rõ sản lượng ước tính, mức tự dùng và khả năng cấp điện khi mất lưới.

### Kết luận

Mái Đông–Tây có thể là phương án hợp lý, nhất là khi diện tích mái rộng hoặc nhu cầu điện trải từ sáng đến chiều. Không có một tỷ lệ hiệu quả chung cho mọi nhà. Với Pleiku, hãy dùng mô phỏng theo tọa độ và điều kiện mái thật, sau đó kiểm tra bóng che, lịch tải và cấu hình MPPT trước khi quyết định.

### Nguồn tham khảo

* [Solar Rooftop Potential — U.S. Department of Energy](https://www.energy.gov/cmei/systems/solar-rooftop-potential)
* [Solar Photovoltaic System Design Basics — U.S. Department of Energy](https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics)
* [PVGIS — European Commission Joint Research Centre](https://joint-research-centre.ec.europa.eu/photovoltaic-geographical-information-system-pvgis_en)

> Ghi chú biên tập: chưa có mô phỏng PVGIS cụ thể cho một địa chỉ nên bài không nêu sản lượng hay tỷ lệ phần trăm cho Pleiku. Bìa hiện tại là concept, cần giữ trạng thái chờ duyệt quyền ảnh.
