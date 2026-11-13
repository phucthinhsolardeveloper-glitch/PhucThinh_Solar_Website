## MPPT là gì? Vì sao quan trọng khi mái có nhiều hướng?

MPPT là bộ theo dõi điểm công suất cực đại (Maximum Power Point Tracking) trong inverter. Nó liên tục tìm vùng điện áp và dòng điện mà chuỗi tấm pin đang cho công suất phù hợp nhất trong điều kiện nắng, nhiệt độ và bóng che hiện tại. MPPT không làm tấm pin mạnh hơn định mức và cũng không xóa được tổn hao do mái bị che; nó giúp inverter khai thác đúng điểm làm việc của chuỗi.

### Hãy hình dung bằng một con dốc

Tấm pin có thể tạo ra các cặp điện áp–dòng điện khác nhau. Công suất là tích của hai giá trị đó. Điểm có tích lớn nhất thay đổi khi mây đi qua, nhiệt độ tăng hoặc bóng che xuất hiện. MPPT thử và đo trong giới hạn an toàn để tìm điểm phù hợp, thay vì giữ một điện áp cố định bất kể thời tiết.

Trong một hệ thống thực, MPPT làm việc cùng các giới hạn khác: dải điện áp hoạt động, điện áp khởi động, dòng điện tối đa và số lượng MPPT của inverter. Vì vậy “inverter có MPPT” chưa đủ để kết luận một cấu hình đúng.

### Nhiều hướng mái thì vấn đề nằm ở đâu?

Hai mặt mái Đông và Tây nhận bức xạ ở các thời điểm khác nhau. Nếu ghép chúng vào cùng một chuỗi, một mặt có thể kéo điểm làm việc của mặt kia lệch khỏi điều kiện tối ưu. Mức ảnh hưởng phụ thuộc thiết kế cụ thể, không nên tự gán một phần trăm suy giảm chung.

Giải pháp thường được xem xét là đưa các mặt mái khác hướng vào các MPPT riêng. Mỗi MPPT vẫn phải có số tấm đủ để đạt dải điện áp làm việc của inverter. Nếu chuỗi quá ngắn, điện áp có thể thấp hơn ngưỡng; nếu chuỗi quá dài, điện áp hở mạch ở ngày lạnh có thể vượt giới hạn DC. Dòng khi ghép song song cũng phải nằm trong giới hạn đầu vào.

### Ví dụ minh họa, không phải cấu hình thi công

Giả sử một mái có 8 tấm hướng Đông và 8 tấm hướng Tây. Con số 8 chỉ là ví dụ để minh họa cách đặt câu hỏi, không phải khuyến nghị số tấm. Kỹ sư cần lấy Voc, Vmp, Isc, Imp của đúng model, rồi tính theo nhiệt độ thấp nhất và cao nhất dự kiến. Sau đó, kiểm tra mỗi chuỗi với datasheet inverter: dải MPPT, điện áp tối đa, dòng mỗi MPPT và số MPPT độc lập.

Nếu inverter chỉ có một MPPT hoặc 8 tấm chưa đủ điện áp tối thiểu, có thể phải xem model khác, tối ưu cách ghép hoặc giải pháp điện tử ở cấp module. Không được đấu nối chỉ vì tổng công suất kWp vừa với quảng cáo inverter.

### MPPT không thay thế khảo sát bóng che

Một cây che một phần tấm pin có thể tạo ra điều kiện rất khác theo giờ. Bypass diode trong module có vai trò giới hạn ảnh hưởng của vùng che trong một số tình huống, nhưng không biến một vị trí bị che thành vị trí không bị che. Microinverter hoặc optimizer có thể là phương án để đánh giá, song chúng vẫn cần datasheet, chi phí, khả năng bảo trì và tương thích cụ thể.

Hãy ghi bóng che theo khung giờ và mùa, không chỉ chụp một ảnh giữa trưa. Với mái có bồn nước, ăng-ten hoặc nhà kế bên, cần thể hiện vật cản trên bản vẽ bố trí. Đây là dữ liệu để mô phỏng, không phải việc có thể suy ra từ số MPPT trên nhãn sản phẩm.

### Bốn bước kiểm tra trước khi duyệt cấu hình

1. Xác định từng mặt mái, hướng, góc nghiêng và các vật cản.
2. Chọn đúng mã module, lấy Voc/Vmp/Isc/Imp và hệ số nhiệt từ datasheet.
3. Tính điện áp chuỗi ở điều kiện nóng/lạnh, dòng khi ghép và dải MPPT.
4. Đối chiếu sản lượng mô phỏng với lịch tải; ghi rõ phần chưa kiểm chứng.

Khi kiểm tra, hãy yêu cầu sơ đồ một sợi và bảng chuỗi. Người mua có thể hỏi: “Chuỗi này nối vào MPPT nào?”, “Điện áp thấp nhất và cao nhất là bao nhiêu?”, “Bóng che được xử lý thế nào?” Đây là những câu hỏi hữu ích hơn việc chỉ hỏi inverter bao nhiêu kW.

### Kết luận

MPPT là cơ chế tìm điểm công suất phù hợp của chuỗi pin, còn thiết kế nhiều hướng mái là bài toán phân tách điều kiện làm việc. Các mặt mái khác hướng thường cần được xem xét trên các MPPT riêng, nhưng chỉ được chốt sau khi kiểm tra điện áp, dòng điện, bóng che và đúng model thiết bị. Nội dung bài chưa gắn infographic; cần sơ đồ tự thiết kế được kỹ thuật kiểm tra trước khi dùng.

### Nguồn tham khảo

* [Solar Photovoltaic System Design Basics — U.S. Department of Energy](https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics)
* [Solar Photovoltaic Technology Basics — U.S. Department of Energy](https://www.energy.gov/cmei/systems/solar-photovoltaic-technology-basics)

> Ghi chú biên tập: không nêu giới hạn điện áp/dòng cụ thể vì chưa có mã inverter và module của một công trình; các giá trị phải lấy từ datasheet đúng phiên bản.
