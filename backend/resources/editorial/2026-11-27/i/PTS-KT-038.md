Đối chiếu Voc và Isc với giới hạn inverter giúp chuỗi pin không bị quá áp khi trời rét, và không quá dòng khi nắng gắt.

Nhiều người lắp điện mặt trời chỉ nhìn tổng công suất kWp mà bỏ qua điện áp và dòng điện. Inverter là bộ não của cả hệ thống. Đấu nối chuỗi pin vượt ngưỡng chịu đựng sẽ làm máy tự ngắt hoặc hỏng linh kiện bên trong.

## Ý nghĩa của Voc và Isc trên bảng thông số

Bảng thông số của tấm pin và inverter luôn nêu rõ bốn thông số điện đo ở điều kiện chuẩn 25 °C:

| Ký hiệu | Ý nghĩa trên tấm pin | Giới hạn cần so trên inverter |
|---|---|---|
| Voc | Điện áp hở mạch khi không tải ở điều kiện chuẩn | Phải thấp hơn điện áp DC tối đa của máy khi trời lạnh |
| Vmp | Điện áp làm việc tại điểm công suất cực đại | Tổng áp chuỗi phải nằm trong dải làm việc khi trời nóng |
| Isc | Dòng điện ngắn mạch cao nhất của tấm pin | Phải dưới dòng ngắn mạch tối đa của từng ngõ vào |
| Imp | Dòng điện làm việc tại điểm công suất cực đại | Cần phù hợp dòng vào định mức để không bị cắt công suất |

Chỉ số Voc quyết định độ an toàn điện áp của dàn pin. Trong khi đó, dòng điện Isc quyết định cỡ dây dẫn và thiết bị bảo vệ.

## Vì sao nhiệt độ làm điện áp tấm pin thay đổi?

Theo Bộ Năng lượng Hoa Kỳ (DOE), tế bào quang điện làm việc tốt nhất ở nhiệt độ mát mẻ. Khi mặt pin bị nung nóng trưa hè, đặc tính bán dẫn thay đổi khiến dòng điện tăng nhẹ nhưng điện áp lại sụt rất mạnh.

Vào mùa đông trời lạnh và nắng trong, điện áp tấm pin sẽ tăng vọt lên cao. Mức điện áp thực tế lúc này cao hơn con số đo ở 25 °C trên nhãn.

Độ biến thiên này tính theo hệ số nhiệt độ của điện áp hở mạch ghi trên nhãn tấm pin. Nếu chỉ lấy điện áp ở 25 °C nhân với số tấm pin, dàn điện rất dễ bị quá áp khi trời trở rét.

## Phép tính chuỗi theo nhiệt độ bằng một ví dụ giả định

Giả sử bạn dùng tấm pin 550 Wp có điện áp hở mạch ở 25 °C là 49,8 V và hệ số nhiệt độ là -0,26%/°C.

Vào sáng mùa đông rét 10 °C (thấp hơn điều kiện chuẩn 15 °C), mức tăng điện áp là 3,9%. Điện áp thực tế của mỗi tấm pin tăng lên 51,74 V, cao hơn gần 2 V so với trên nhãn.

Nếu thợ ghép nối tiếp 12 tấm thành một chuỗi, tổng điện áp lúc trời rét sẽ là 12 nhân 51,74 V, bằng 620,88 V.

Giả sử dòng inverter bạn chọn có ngưỡng chịu áp tối đa là 600 V. Chuỗi 12 tấm này sẽ vượt ngưỡng an toàn của máy hơn 20 V. Quá áp có thể khiến máy báo lỗi dừng chạy, hoặc làm hỏng linh kiện đầu vào. Để an toàn, người thiết kế phải giảm chuỗi xuống còn 11 tấm. Khi đó tổng điện áp ở 10 °C là 569,14 V, hoàn toàn dưới mức 600 V.

Vào mùa hè, mặt pin trên mái tôn bị nung nóng làm điện áp tụt giảm mạnh. Thợ phải tính toán để tổng điện áp lúc nóng nhất vẫn cao hơn ngưỡng khởi động của máy.

Khi chia chuỗi cho các mặt mái lệch hướng như bài [[KT-023]], kỹ thuật viên cần tính toán riêng cho từng ngõ theo dõi. Việc này giúp các chuỗi pin vận hành an toàn trong mọi thời tiết. Bạn có thể xem thêm bài [[137]] về cách đọc datasheet tấm pin, hoặc bài [[140]] về các tính năng bảo vệ của inverter.

## Giới hạn dòng điện Isc khi ghép song song chuỗi pin

Mỗi ngõ vào trên inverter đều có giới hạn dòng làm việc và dòng ngắn mạch tối đa. Theo Clean Energy Reviews, nhiều dòng tấm pin mới hiện có dòng định mức từ 13 A trở lên. Mức này dễ vượt quá giới hạn ngõ vào của các mẫu inverter đời cũ, do đó bạn cần kiểm tra kỹ thông số trước khi đấu nối.

Khi ghép song song hai chuỗi vào chung một ngõ vào, dòng điện sẽ tăng gấp đôi. Giả sử mỗi tấm pin có dòng ngắn mạch 14 A, hai chuỗi song song sẽ đưa vào ngõ máy dòng 28 A. Thợ kỹ thuật phải so mức 28 A này với dòng ngắn mạch tối đa của máy để tránh quá tải.

## Những điểm cần thợ kỹ thuật giải thích rõ trước khi lắp đặt

Để an tâm khi nghiệm thu, bạn nên trao đổi với đơn vị thi công ba nội dung kỹ thuật:

- Bảng tính điện áp hở mạch cao nhất của chuỗi pin ở nhiệt độ thấp nhất trong năm tại địa phương.
- Ngưỡng điện áp làm việc thấp nhất của chuỗi pin vào buổi trưa mùa hè có nằm trong dải làm việc của máy hay không.
- Sơ đồ đấu nối dây DC và cách phân chia các chuỗi pin vào từng ngõ vào riêng biệt của inverter.

## Nguồn tham khảo

- [U.S. Department of Energy – Solar Photovoltaic Performance and Efficiency Basics](https://www.energy.gov/cmei/systems/solar-photovoltaic-performance-and-efficiency-basics)
- [Clean Energy Reviews – Grid-Connect Solar Inverters Comparison](https://www.cleanenergyreviews.info/blog/best-grid-connect-solar-inverters-sma-fronius-solaredge-abb)
