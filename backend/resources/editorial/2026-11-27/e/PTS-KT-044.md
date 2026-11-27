Nối tiếp làm tăng điện áp của dàn pin trong khi dòng điện giữ nguyên. Ngược lại, đấu song song làm tăng dòng điện và giữ nguyên điện áp. Kỹ sư phối hợp hai cách ghép này để đưa điện áp và dòng điện về đúng dải làm việc an toàn của inverter.

## Nối tiếp: cộng dồn điện áp để kích hoạt inverter

Đấu nối tiếp là cách nối cực dương (+) của tấm pin này vào cực âm (-) của tấm pin kế tiếp. Cách đấu này tạo thành một chuỗi tấm pin (thường gọi là string).

Khi các tấm pin mắc nối tiếp, điện áp của từng tấm sẽ cộng dồn lại với nhau. Dòng điện chạy qua cả chuỗi giữ nguyên bằng dòng điện của một tấm pin.

Giả sử bạn ghép nối tiếp 10 tấm pin có điện áp làm việc 40 V và dòng điện 13 A. Tổng điện áp của cả chuỗi sẽ đạt 400 V, còn dòng điện của chuỗi vẫn giữ ở mức 13 A.

Inverter hòa lưới cần một mức điện áp tối thiểu để khởi động và phát điện. Việc mắc nối tiếp giúp điện áp dàn pin nhanh chóng đạt ngưỡng làm việc của ngõ MPPT ngay từ sáng sớm.

Tuy nhiên, cách mắc nối tiếp có một điểm yếu. Toàn chuỗi chạy chung một dòng điện. Nếu một tấm pin bị bóng cây che râm hoặc bám bụi bẩn, dòng điện cả chuỗi có thể bị kéo tụt theo tấm yếu nhất. Bạn có thể xem kỹ cách ngõ MPPT xử lý bóng che trong bài [[KT-023]].

## Đấu song song: tăng dòng điện và giữ nguyên điện áp

Đấu song song là cách gom các cực dương (+) lại với nhau, và gom các cực âm (-) lại với nhau.

Khi đấu song song, điện áp của dàn pin giữ nguyên bằng điện áp của một nhánh. Dòng điện của các nhánh sẽ cộng dồn lại với nhau.

Giả sử bạn có hai chuỗi pin độc lập, mỗi chuỗi có điện áp 400 V và dòng điện 13 A. Khi ghép song song, điện áp vẫn ở mức 400 V, nhưng dòng điện tổng đưa về máy tăng lên 26 A.

Cách ghép này thường dùng khi mái nhà rộng và cần lắp nhiều tấm pin. Nếu nối tiếp tất cả, điện áp sẽ vượt quá ngưỡng chịu đựng của inverter.

Khi dòng điện tăng lên, dây dẫn và thiết bị bảo vệ phải to hơn để tránh nóng dây và giảm hao tổn điện năng.

| Tiêu chí | Đấu nối tiếp (Chuỗi) | Đấu song song |
|---|---|---|
| Cách đấu nối | Dương (+) tấm này vào âm (-) tấm kia | Nối chung các cực cùng dấu |
| Tổng điện áp | Bằng điện áp từng tấm cộng dồn lại | Giữ nguyên bằng điện áp của một chuỗi |
| Tổng dòng điện | Giữ nguyên bằng dòng của một tấm | Bằng dòng điện các nhánh cộng dồn lại |
| Ảnh hưởng khi bị bóng che | Một tấm bị che có thể làm giảm cả chuỗi | Nhánh nào bị che chỉ giảm nhánh đó |
| Tiết diện dây dẫn | Dây tiêu chuẩn do dòng điện thấp | Cần dây lớn hơn do dòng điện cao |
| Mục tiêu chính khi thiết kế | Đạt dải điện áp làm việc của inverter | Tăng công suất mà không quá áp inverter |

## Vì sao cần phối hợp cả hai cách khi thiết kế hệ thống?

Hầu hết các hệ điện mặt trời gia đình sử dụng cách ghép hỗn hợp: nối tiếp các tấm thành từng chuỗi rồi đưa về inverter.

Mỗi model inverter đều có giới hạn điện áp tối đa và dải điện áp làm việc MPPT. Kỹ sư tính số tấm trong mỗi chuỗi để vào ngày lạnh nhất, điện áp không vượt trần inverter. Khi mái nhà rộng, các chuỗi bằng nhau sẽ được ghép song song vào cùng một ngõ MPPT.

Bạn có thể xem vai trò biến đổi điện của inverter trong bài [[140]]. Ý nghĩa các chỉ số điện áp và dòng điện được giải thích trong bài [[146]].

## Lưu ý an toàn điện: chỉ nắm nguyên lý, không tự đấu nối

Các tấm pin mặt trời liên tục tạo ra điện một chiều (DC) khi có ánh sáng chiếu vào. Bạn không thể ngắt nguồn điện này bằng công tắc thông thường ở đầu tấm pin.

Một chuỗi pin nối tiếp trên mái nhà tạo ra mức điện áp một chiều rất cao, chẳng hạn 400 V như ở ví dụ trên. Điện áp một chiều cao có thể gây nguy hiểm trực tiếp đến tính mạng và phát sinh hồ quang điện nếu đấu sai kỹ thuật.

Mọi việc bấm đầu nối, kéo dây và đo đạc phải do thợ điện chuyên nghiệp thực hiện. Chủ nhà chỉ nên nắm vững nguyên lý để hiểu rõ bản vẽ thiết kế trên báo giá.

## Nguồn tham khảo

- [U.S. Department of Energy – Solar Photovoltaic System Design Basics](https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics)
- [Clean Energy Reviews – Designing off-grid and hybrid solar systems](https://www.cleanenergyreviews.info/blog/designing-off-grid-hybrid-solar-systems)
