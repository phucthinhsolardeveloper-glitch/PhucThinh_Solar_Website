MPPT (Maximum Power Point Tracking) là bộ theo dõi điểm công suất cực đại nằm trong inverter. Bộ phận này liên tục chỉnh điện áp và dòng điện để lấy ra mức công suất cao nhất mà chuỗi tấm pin đang tạo được.

Nắng trên mái thay đổi suốt ngày khi mây bay qua, tấm pin nóng lên hoặc có bóng râm. Bạn cứ hình dung MPPT như hộp số tự động trên xe. Khi đường bằng hay lúc lên dốc, nó tự sang số để máy luôn chạy ở điểm lợi sức nhất.

## Vì sao mái nhiều hướng cần các ngõ theo dõi riêng?

Các tấm pin mắc nối tiếp trong cùng một chuỗi phải đi chung một dòng điện. Nếu cả chuỗi nằm trên một mặt mái phẳng và đón nắng như nhau, các tấm sẽ cùng lên công suất một nhịp.

Chuyện khác đi khi mái nhà chia làm nhiều hướng, chẳng hạn một mặt quay hướng Đông và một mặt quay hướng Tây như trong bài [[KT-022]]. Buổi sáng mặt Đông nắng mạnh nhưng mặt Tây còn khuất nắng. Nếu mắc nối tiếp hai mặt vào chung một chuỗi, phía mái ít nắng sẽ bóp nghẹt dòng điện của cả dàn.

Để giải quyết, nhiều dòng inverter có từ hai ngõ MPPT độc lập trở lên. Chuỗi tấm pin bên Đông được đấu vào ngõ thứ nhất, còn chuỗi bên Tây đấu vào ngõ thứ hai. Nhờ vậy, mỗi mặt mái tự chạy theo lượng nắng của riêng mình.

## Các ký hiệu điện áp và dòng điện trên bảng thông số

Tách mỗi mặt mái ra một ngõ riêng chỉ chạy tốt khi số tấm pin trên mặt đó tạo đủ điện áp cho inverter làm việc. Trên bảng thông số (datasheet) của tấm pin và inverter có bốn ký hiệu quyết định cách chia chuỗi:

| Ký hiệu | Nghĩa trên bảng thông số | Vì sao bạn cần quan tâm |
|---|---|---|
| Voc | Điện áp hở mạch của tấm pin | Trời lạnh làm điện áp tăng. Tổng áp chuỗi phải thấp hơn mức chịu áp tối đa của inverter |
| Vmp | Điện áp lúc phát công suất cao nhất | Trời nóng làm điện áp giảm. Tổng áp làm việc phải nằm lọt trong dải dò của ngõ vào |
| Isc | Dòng điện ngắn mạch của tấm pin | Khi ghép song song hai chuỗi vào một ngõ, tổng dòng phải dưới mức chịu dòng của ngõ đó |
| Imp | Dòng điện lúc phát công suất cao nhất | Cần nằm trong ngưỡng dòng của ngõ vào để máy không cắt bớt công suất lúc nắng gắt |

Mỗi ngõ vào trên inverter đều có một ngưỡng điện áp khởi động và một dải điện áp làm việc. Chuỗi quá ngắn sẽ không đủ áp để máy chạy, còn chuỗi quá dài lại gây quá áp vào ngày trời lạnh.

## Một ví dụ chia tấm pin trên hai mặt mái và tránh bóng râm

Giả sử mái nhà bạn đặt được 8 tấm pin bên hướng Đông và 8 tấm bên hướng Tây. Với inverter có hai ngõ MPPT riêng, thợ điện đấu chuỗi 8 tấm bên Đông vào ngõ một và chuỗi 8 tấm bên Tây vào ngõ hai.

Trước khi đấu dây, người thiết kế lấy điện áp làm việc của một tấm nhân với 8 và trừ hao theo nhiệt độ lúc mái nóng. Phép tính này cho biết chuỗi 8 tấm có vượt ngưỡng điện áp tối thiểu của ngõ vào hay không. Với mặt mái phụ quá hẹp chỉ vừa vài tấm, thợ sẽ dùng bộ tối ưu công suất (optimizer) hoặc biến tần vi mô (microinverter).

Bóng râm từ bồn nước, ăng-ten hay cây xanh cũng kéo tụt điện áp chuỗi. Khi một phần tấm pin bị che, đi-ốt bảo vệ (bypass diode) trong tấm sẽ mở lối cho dòng điện đi vòng qua vùng bị tối. Nhờ vậy tấm pin không bị nóng hỏng, nhưng mỗi lần đi-ốt mở thì điện áp chuỗi lại sụt một nấc. Chuỗi vốn đã ngắn sẽ dễ tụt dưới ngưỡng làm việc của inverter khi gặp bóng đổ.

## Nên hỏi kỹ thuật viên điều gì trước khi chốt sơ đồ đấu dây?

Một báo giá chỉ ghi tổng số tấm pin và công suất inverter chưa cho thấy các mặt mái phối hợp ra sao. Khi xem bản vẽ cho mái nhiều hướng, bạn nên hỏi rõ bốn điểm sau:

- **Mỗi mặt mái đấu vào ngõ nào.** Xem sơ đồ chia chuỗi đã tách riêng các mặt mái lệch hướng vào từng ngõ độc lập hay chưa.
- **Điện áp của chuỗi ngắn nhất.** Mặt mái ít tấm pin nhất đạt bao nhiêu vôn (V) lúc trời nắng nóng, có nằm trong dải dò của inverter không.
- **Điện áp của chuỗi dài nhất.** Tổng điện áp hở mạch vào sáng sớm mùa lạnh có nằm dưới ngưỡng chịu áp tối đa của máy không.
- **Cách chừa khoảng tránh bóng đổ.** Các tấm đặt gần bồn nước, tum thang hoặc tường nhà kế bên cần lùi khỏi vùng bóng râm sáng và chiều.

## Nguồn tham khảo

- [U.S. Department of Energy – Solar Photovoltaic System Design Basics](https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics)
- [U.S. Department of Energy – Solar Photovoltaic Technology Basics](https://www.energy.gov/cmei/systems/solar-photovoltaic-technology-basics)
- [Clean Energy Reviews – Hybrid & Battery-Ready Solar Inverters](https://www.cleanenergyreviews.info/blog/solar-hybrid-battery-ready-inverters)
- [Fronius – What is the best orientation for solar panels?](https://blog.fronius.com/solar-energy/en/know-how/solar-panels-orientation/)
