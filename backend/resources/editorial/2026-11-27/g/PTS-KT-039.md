Tủ lạnh và modem Wi-Fi chỉ được ưu tiên cấp điện khi mất lưới nếu hệ thống có pin lưu trữ. Ngoài ra, bạn cần inverter hybrid có cổng tải dự phòng riêng. Nếu chỉ lắp hệ hòa lưới thông thường, toàn bộ thiết bị đều tắt ngay khi mất điện dù trời đang nắng gắt.

## Vì sao mất điện lưới thì inverter thông thường cũng tắt?

Theo Bộ Năng lượng Hoa Kỳ (DOE), hệ thống điện mặt trời hòa lưới thông thường luôn phụ thuộc vào lưới điện để phát điện. Khi mất lưới, thiết bị được thiết kế để tự động ngắt ngay vì lý do an toàn, tránh phát điện ngược ra đường dây đang sửa chữa.

Muốn có điện khi sự cố xảy ra, ngôi nhà cần inverter hybrid và hệ thống pin lưu trữ. Theo DOE, hệ thống có pin lưu trữ sẽ tự động nhận biết mất lưới. Thiết bị lập tức chuyển sang chế độ độc lập (islanded mode) để giữ nguồn điện trong nhà. Bạn có thể xem thêm cơ chế làm việc của inverter trong bài [[140]] và bài [[145]].

## Tách riêng tủ điện tải ưu tiên để bảo vệ nguồn pin

Inverter không tự nhận biết ổ cắm nào đang cấp cho tủ lạnh hay modem mạng. Để ưu tiên điện khi mất lưới, thợ điện tách các mạch thiết yếu vào tủ điện phụ. Tủ này được đấu thẳng vào cổng tải dự phòng của inverter.

Nhánh tải ưu tiên chỉ nên giữ các thiết bị cần thiết:
- Thiết bị mạng và an ninh: modem Wi-Fi, camera an ninh, máy tính làm việc.
- Bảo quản đồ ăn: tủ lạnh gia đình.
- Sinh hoạt cơ bản: quạt mát, vài bóng đèn LED và ổ sạc điện thoại.

Các đồ điện ngốn nhiều công suất như bếp từ, bình nóng lạnh hay máy điều hòa lớn nên để ở nhánh điện chính. Nếu đưa tải nặng vào cổng dự phòng, pin sẽ cạn rất nhanh hoặc làm ngắt mạch bảo vệ của inverter.

## Lưu ý công suất khởi động của máy nén tủ lạnh

Khác với modem Wi-Fi tiêu thụ điện đều đặn, tủ lạnh có động cơ máy nén đóng ngắt theo chu kỳ làm lạnh. Đây là điểm kỹ thuật nhiều người hay bỏ qua khi tính nguồn dự phòng.

Theo DOE, hệ thống pin cần hai yếu tố: dung lượng (kWh) và công suất phát (kW). Dung lượng giúp duy trì thiết bị theo thời gian. Trong khi đó, công suất tức thời giúp nguồn điện ổn định khi phụ tải biến động. Khi tủ lạnh đã mát, mức tiêu thụ điện trung bình khá thấp. Nhưng mỗi lần máy nén khởi động lại, dòng điện tức thời sẽ vọt lên cao hơn nhiều so với lúc chạy ổn định.

Cổng tải dự phòng cần công suất đỉnh đủ lớn cho nhịp khởi động này. Nhờ đó, máy nén chạy êm mà không làm nhảy rơ-le bảo vệ của inverter.

## Tính thời gian pin duy trì tủ lạnh và mạng Wi-Fi

Theo DOE, điện mặt trời kèm pin lưu trữ giúp nhà bạn duy trì điện qua đợt mất điện dài. Hệ thống không cần tiếp nhiên liệu liên tục như máy phát chạy dầu diesel.

Giả sử nhà bạn duy trì các thiết bị ưu tiên gồm:
- Một modem Wi-Fi công suất 15 W, chạy 24 giờ hết 360 Wh.
- Một tủ lạnh gia đình có công suất tiêu thụ trung bình 50 W, chạy 24 giờ hết 1,2 kWh.
- Ba bóng đèn LED và sạc điện thoại dùng khoảng 340 Wh mỗi ngày.

Tổng lượng điện cho cả ngày đêm là 1,9 kWh. Như phân tích trong bài [[KT-027]], pin lưu trữ luôn giữ một ngưỡng dự trữ đáy. Giả sử bộ pin 5 kWh còn 4 kWh dùng thực tế. Nguồn này đủ nuôi tủ lạnh, đèn và Wi-Fi suốt hai ngày mất điện liên tiếp. Phép tính này chưa kể hao hụt chuyển đổi và điện nạp bù ban ngày.

| Nhóm thiết bị | Đặc điểm vận hành | Nhánh đấu nối | Mức ưu tiên khi mất lưới |
|---|---|---|---|
| Modem Wi-Fi và camera | Công suất nhỏ, chạy liên tục không vọt dòng | Tủ điện tải ưu tiên | Rất cao để giữ liên lạc và giám sát |
| Tủ lạnh gia đình | Chạy ngắt quãng nhưng có dòng khởi động máy nén | Tủ điện tải ưu tiên | Cao để bảo quản thực phẩm |
| Đèn LED và quạt mát | Công suất vừa phải, bật theo giờ sinh hoạt | Tủ điện tải ưu tiên | Cao để duy trì sinh hoạt cơ bản |
| Bếp từ và bình nóng lạnh | Công suất rất lớn, rút cạn pin nhanh | Tủ điện nhánh chính | Không đấu vào cổng dự phòng |

## Nguồn tham khảo

- [U.S. Department of Energy – Solar and Resilience Basics](https://www.energy.gov/cmei/systems/solar-and-resilience-basics)
- [U.S. Department of Energy – Solar Integration: Solar Energy and Storage Basics](https://www.energy.gov/cmei/systems/solar-integration-solar-energy-and-storage-basics)
