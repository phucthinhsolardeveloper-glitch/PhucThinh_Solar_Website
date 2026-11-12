# Pin lưu trữ và hệ thống BESS

“Pin lưu trữ” là cách gọi dễ hiểu cho phần tích trữ điện. BESS (Battery Energy Storage System) là một hệ thống rộng hơn: bộ pin, bộ quản lý pin, bộ chuyển đổi công suất, bảo vệ, điều khiển, giám sát, tủ/vỏ và các giao diện kết nối cần thiết. Một bộ pin không tự biến thành BESS chỉ nhờ tăng số module.

## Chọn theo việc cần làm

Gia đình có thể cần chuyển một phần điện mặt trời sang buổi tối hoặc giữ một số tải quan trọng trong thời gian mất điện. Cơ sở thương mại có thể cần san thời điểm dùng điện, giới hạn công suất đỉnh hoặc phối hợp với nguồn tại chỗ. Mỗi mục tiêu dẫn đến cách chọn công suất, dung lượng, chế độ dự phòng và sơ đồ kết nối khác nhau.

## kWh và kW không phải một con số

Dung lượng (kWh) cho biết hệ thống có thể lưu bao nhiêu năng lượng; công suất (kW) cho biết có thể nạp/xả nhanh đến mức nào tại một thời điểm. Một hệ thống có nhiều kWh nhưng inverter kW thấp sẽ không chạy được tải khởi động lớn; ngược lại, công suất cao với ít kWh sẽ hết năng lượng nhanh. Cần lập danh sách tải, công suất khởi động, thời gian chạy và mức ưu tiên trước khi chốt cấu hình.

## Tương thích là điều kiện, không phải phụ lục

Pin phải tương thích với inverter/PCS, điện áp làm việc, giao thức BMS, thiết bị bảo vệ và chiến lược điều khiển. Nếu tích hợp điện mặt trời hiện hữu, cần kiểm tra model inverter, sơ đồ AC/DC, giới hạn đấu nối và khả năng cập nhật firmware. Hồ sơ cần nêu rõ tải nào được cấp khi mất điện, thời gian chuyển mạch, giới hạn công suất và điều kiện nhiệt độ lắp đặt.

## Từ pin đến hệ thống an toàn

Vị trí đặt cần xét thông gió, nhiệt, nước, lối tiếp cận, khoảng cách, chống cháy và phương án cô lập. Không tự lắp, mở vỏ, ghép các module khác chuẩn hoặc bỏ qua cảnh báo BMS. Thiết kế phải có bảo vệ, tiếp địa, chống sét, giám sát và quy trình xử lý sự cố; yêu cầu của công trình và thiết bị cụ thể quyết định chi tiết.

![Tủ lưu trữ năng lượng — ảnh minh họa BESS hiện có của website](/assets/images/site-visuals/solution-bess-v2.jpg)

## Hai tầng lựa chọn: pin gia đình và BESS

**Pin gia đình** thường xoay quanh việc dùng điện mặt trời sau giờ nắng hoặc giữ một nhóm tải nhỏ. Câu hỏi chính là dung lượng dùng được, công suất xả, vị trí đặt và mạch backup. **BESS thương mại** là một bài toán hệ thống: có thể cần PCS/inverter công suất lớn, tủ pin theo rack, BMS cấp hệ thống, EMS, điều khiển phụ tải, phối hợp bảo vệ và yêu cầu an toàn của công trình. BESS không chỉ là “pin gia đình cỡ lớn”.

Ví dụ giả định: một cửa hàng muốn giảm đỉnh công suất vào giờ cao điểm, còn một hộ muốn giữ tủ lạnh khi mất điện. Cửa hàng phải xem phụ tải theo thời gian, giới hạn công suất và chế độ điều khiển; hộ gia đình phải xem tải ưu tiên và thời gian backup. Hai mục tiêu có thể dùng thiết bị khác nhau dù đều gọi là lưu trữ.

| Mục tiêu | Tầng giải pháp | Dữ liệu quyết định |
|---|---|---|
| Chuyển điện mặt trời sang buổi tối | Pin gia đình/hybrid | kWh dùng buổi tối, kW tải, chu kỳ |
| Giữ một nhóm tải khi mất điện | Pin + mạch ưu tiên | tải khởi động, chuyển mạch, thời gian |
| San đỉnh hoặc điều khiển tải cơ sở | BESS thương mại | phụ tải 15 phút, PCS, EMS, đấu nối |
| Tích hợp hệ thống hiện hữu | BESS tương thích | model inverter, điện áp, BMS, bảo vệ |

Tham khảo [Battery Storage in the United States — U.S. Energy Information Administration](https://www.eia.gov/todayinenergy/detail.php?id=46756) và [Energy Storage Safety — U.S. Department of Energy](https://www.energy.gov/oe/energy-storage-systems-safety). Datasheet và tiêu chuẩn của đúng nhà sản xuất vẫn là căn cứ nghiệm thu.

Phúc Thịnh Solar bắt đầu bằng mục tiêu sử dụng, hồ sơ tải và hệ thống hiện hữu, sau đó mới phân biệt phương án pin gia đình với BESS thương mại. Bản đề xuất nên ghi rõ dung lượng sử dụng thực tế, công suất liên tục/đỉnh, tải ưu tiên, độ sâu xả theo thiết bị, điều kiện bảo hành và giới hạn dự kiến. Không có một kích thước BESS phù hợp cho mọi nơi.
