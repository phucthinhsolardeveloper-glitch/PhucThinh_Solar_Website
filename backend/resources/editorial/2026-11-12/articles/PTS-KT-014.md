Inverter là thiết bị chuyển đổi và điều khiển dòng điện từ các tấm pin. Khi chọn thiết bị, hai thuật ngữ thường gặp là **inverter hòa lưới** và **inverter hybrid**. Khác biệt quan trọng nhất không nằm ở tên gọi hay số cổng trên mặt máy, mà ở cách thiết bị phối hợp với lưới, pin lưu trữ và tải dự phòng.

Khả năng thực tế khác nhau theo model. “Hybrid” không có nghĩa mọi máy đều kèm pin, tự động cấp điện khi mất lưới hoặc hỗ trợ toàn bộ tải trong nhà.


![Bảng so sánh chức năng inverter hòa lưới và inverter hybrid](/upload/news/pts-explainer-inverter-types.webp)

*Hình minh họa: Phúc Thịnh Solar*

## Inverter hòa lưới làm việc ra sao?

Trong hệ hòa lưới, inverter chuyển điện DC từ dàn pin thành AC đồng bộ với lưới và cấp cho phụ tải tại chỗ. Khi điện mặt trời thiếu, phụ tải có thể lấy phần thiếu từ lưới; cách xử lý điện dư phụ thuộc cấu hình, thiết bị và quy định kết nối.

Inverter hòa lưới thông thường dựa vào tín hiệu lưới để vận hành. Khi phát hiện lưới mất, cơ chế chống đảo lưới khiến thiết bị ngừng cấp điện vào đường AC. Do đó, nếu nhà cần nguồn dự phòng khi cúp điện, một inverter hòa lưới thuần túy thường không đáp ứng nhu cầu đó.

Một số người nhầm rằng ban ngày có nắng thì inverter vẫn chạy độc lập. Điều này chỉ có thể xảy ra nếu thiết bị được thiết kế cho chế độ độc lập hoặc backup, có phương án tạo lưới cục bộ và cách ly phía lưới. Không nên suy luận từ riêng việc inverter có màn hình hoặc nhiều MPPT.

## Inverter hybrid bổ sung khả năng phối hợp pin

Inverter hybrid thường được thiết kế để quản lý nhiều nguồn và tải: dàn PV, lưới, pin lưu trữ và đôi khi máy phát. Tùy thiết bị, nó có thể sạc pin từ PV, lấy điện từ pin vào thời điểm phù hợp, hoặc cấp điện cho một nhánh tải dự phòng.

Khả năng điều khiển phụ thuộc phần cứng, firmware, pin tương thích, cấu hình và sơ đồ đấu nối. Một số model có thể vận hành không pin ở chế độ hòa lưới nhưng cần pin để tạo nguồn backup; model khác có chức năng riêng. Cần xem tài liệu của chính mã sản phẩm, không suy từ nhãn “hybrid”.

Khi mất lưới, inverter cần tách phần backup khỏi lưới ngoài rồi mới tạo nguồn cho nhánh tải dự phòng. Quy trình chuyển nguồn, thời gian chuyển mạch, công suất liên tục và khả năng quá tải ngắn hạn đều do nhà sản xuất công bố cho từng thiết bị. Một số tải nhạy có thể cần bộ lưu điện riêng nếu yêu cầu chuyển mạch rất ngắn.

## Lựa chọn theo mục tiêu sử dụng

| Nhu cầu | Hướng cấu hình cần tìm hiểu | Điểm cần xác nhận |
|---|---|---|
| Dùng điện mặt trời cho tải ban ngày, không cần backup | Inverter hòa lưới có thể phù hợp | Mức tự dùng, giới hạn lưới, hướng mái và khả năng giám sát |
| Lưu điện mặt trời để dùng buổi tối | Hệ có khả năng tích hợp pin, có thể là hybrid | Pin tương thích, dung lượng khả dụng, công suất sạc/xả và tổn thất |
| Duy trì một số tải khi mất điện | Inverter có chế độ backup và thiết kế nhánh tải phù hợp | Cổng EPS, cách ly, thời gian chuyển, công suất liên tục/đỉnh |
| Vận hành không có lưới điện ổn định | Hệ độc lập hoặc giải pháp chuyên biệt | Dự phòng nhiều ngày, nguồn cân bằng và quản lý tải |

Hệ hòa lưới thường đơn giản hơn khi mục tiêu chính là tự dùng phần điện tạo ra vào ban ngày. Hybrid đáng xem xét khi nhu cầu lưu trữ hoặc dự phòng có giá trị thực tế, nhưng chi phí, hiệu suất, độ phức tạp và tuổi thọ pin phải được tính riêng.

## Sơ đồ tải dự phòng cần làm rõ

Trước khi đặt hàng, yêu cầu người thiết kế vẽ sơ đồ một đường thể hiện dàn pin, inverter, pin lưu trữ, lưới, tủ điện chính, tủ tải dự phòng và thiết bị cách ly. Sơ đồ này giúp nhìn ra thiết bị nào được cấp điện trong từng chế độ và đường nào bị ngắt khi lưới mất.

Lập danh sách tải dự phòng theo thứ tự ưu tiên. Ví dụ, mạng internet, đèn, tủ lạnh và camera có thể cần duy trì; bếp điện, máy lạnh, bình nước nóng và máy bơm có thể tiêu thụ nhiều hơn hoặc có dòng khởi động đáng kể. Không nên gom tất cả vào nhánh backup mà chưa tính tổng công suất đồng thời.

Nếu công trình ba pha, cần kiểm tra cách phân bổ tải giữa các pha. Một số inverter backup chỉ cấp một pha hoặc có giới hạn riêng cho từng pha. Thiết bị ba pha như động cơ cần kiểm tra đặc tính khởi động và yêu cầu điều khiển. Tải tổng thấp không đồng nghĩa mọi pha đều nằm trong giới hạn.

## Những thông số phải kiểm tra trên datasheet

- Số MPPT, dải điện áp, điện áp DC tối đa và dòng tối đa mỗi MPPT.
- Công suất PV khuyến nghị và giới hạn quá tải DC, nếu có.
- Công suất AC liên tục, công suất đỉnh trong khoảng thời gian cho phép và mức lệch pha.
- Công suất sạc/xả pin, dải điện áp pin và danh sách pin được hỗ trợ.
- Có cần pin để bật chế độ backup hay không và điều kiện khởi động lại.
- Công suất tối đa, số pha và thời gian chuyển ở cổng backup/EPS.
- Điều kiện nhiệt độ, cấp bảo vệ môi trường, thông gió và khoảng hở lắp đặt.
- Bảo hành, cập nhật phần mềm và dịch vụ hỗ trợ tại thị trường lắp đặt.

Datasheet cho biết giới hạn danh định, còn thiết kế phải tính điều kiện tại công trình. Đặc biệt, điện áp chuỗi thay đổi theo nhiệt độ; cấu hình phải nằm trong giới hạn inverter trong các điều kiện dự kiến. Việc đấu nối, cài đặt bảo vệ và chạy thử cần do người có chuyên môn thực hiện theo quy định hiện hành.

## So sánh chi phí theo giá trị nhận được

Không nên chỉ so giá inverter. Với hệ hybrid, tổng chi phí có thể gồm pin, thiết bị chuyển nguồn, tủ tải riêng, bảo vệ bổ sung, dây dẫn và công lắp đặt. Pin còn có giới hạn công suất, dung lượng khả dụng, hiệu suất, chu kỳ vận hành và yêu cầu bảo hành.

Nếu mất điện hiếm khi xảy ra và tải ban ngày cao, lợi ích backup có thể khác với nơi thường xuyên cần điện liên tục. Nếu mục tiêu là giảm hóa đơn, cần xem giá trị điện tự dùng và điện mua thay thế; nếu mục tiêu là duy trì hoạt động, cần đánh giá chi phí của thời gian gián đoạn. Không có kết luận kinh tế chung nếu chưa có dữ liệu tải và giá đầu tư thực tế.

## Kết luận

Inverter hòa lưới thường tập trung chuyển đổi điện mặt trời để dùng cùng lưới; inverter hybrid bổ sung khả năng phối hợp pin và có thể hỗ trợ backup tùy model. Hãy chốt theo mục tiêu, sơ đồ tải, thông số thiết bị và điều kiện đấu nối. Chỉ datasheet của đúng model và thiết kế được kiểm tra mới xác nhận được khả năng cấp điện khi mất lưới.

### Nguồn tham khảo

- [Clean Energy Reviews – Solar hybrid and battery-ready inverters](https://www.cleanenergyreviews.info/blog/solar-hybrid-battery-ready-inverters)
- [Fronius – What is an inverter?](https://blog.fronius.com/solar-energy/en/know-how/what-is-an-inverter/)
- [U.S. Department of Energy – Solar Photovoltaic System Design Basics](https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics)
