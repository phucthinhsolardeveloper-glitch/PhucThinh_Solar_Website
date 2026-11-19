Ánh nắng chiếu vào tấm pin tạo ra điện một chiều (DC). Inverter đổi dòng điện đó thành điện xoay chiều (AC) để tủ lạnh, quạt, điều hòa trong nhà dùng được. Phần còn lại của hệ thống, gồm dây dẫn, thiết bị đóng cắt, chống sét, tiếp địa và cách đấu nối với lưới, quyết định điện đó được dùng an toàn, lúc nào và cho tải nào.

![Sơ đồ nguyên lý điện mặt trời gồm ánh sáng, tấm pin PV, inverter và thiết bị sử dụng](/upload/news/pts-diagram-solar-workflow.webp)

*Hình minh họa do Phúc Thịnh Solar biên soạn, dùng để giải thích nguyên lý; bản thiết kế của từng công trình sẽ chi tiết hơn.*

Chuỗi ngắn nhất cần nhớ: **ánh sáng → tấm pin tạo DC → inverter tạo AC → thiết bị sử dụng điện**. Các mục dưới đây đi qua từng mắt xích và những chỗ người mới hay hiểu nhầm.

## Bước 1: tấm pin biến ánh sáng thành điện

Tấm pin quang điện (photovoltaic, viết tắt PV) gồm nhiều tế bào làm từ vật liệu bán dẫn. Khi photon trong ánh sáng chiếu vào tế bào, năng lượng của nó có thể tạo ra các hạt mang điện; cấu trúc điện bên trong tế bào gom chúng thành dòng điện ở hai cực.

Hai điều hay bị nhầm:

- Tấm pin phản ứng với **ánh sáng**, không cần nhiệt để tạo điện. Ngày nắng gắt chưa chắc cho kết quả tốt hơn một ngày trời mát nhưng bức xạ mạnh, vì nhiệt độ tế bào tăng thường làm giảm công suất thực tế.
- Con số kWp là công suất danh định ở điều kiện thử nghiệm tiêu chuẩn (STC). Mười tấm 650 Wp cộng lại là 6,5 kWp, nhưng hệ thống không phát đều 6,5 kW suốt cả ngày. Bài [kW, kWp và kWh khác nhau thế nào](/kw-kwp-va-kwh-khac-nhau-the-nao-7-vi-du-tranh-nham) giải thích kỹ ba đơn vị này.

## Bước 2: inverter đổi DC thành AC

Phần lớn thiết bị gia đình chạy bằng điện xoay chiều. Inverter chuyển DC từ dàn pin thành AC phù hợp với hệ thống điện trong nhà. Nó còn dò điểm công suất tối đa (MPPT), theo dõi điện áp và dòng điện, và thực hiện các chức năng bảo vệ.

Có ba nhóm inverter thường gặp:

| Loại | Làm việc với | Dùng khi |
|---|---|---|
| Hòa lưới | Tấm pin + điện lưới | Muốn ưu tiên điện mặt trời để giảm điện mua vào giờ có nắng |
| Hybrid | Tấm pin + lưới + pin lưu trữ | Cần chuyển điện sang lúc khác hoặc dự phòng, khi được cấu hình đúng |
| Độc lập | Tấm pin + pin lưu trữ, không có lưới | Nơi không có lưới; phải thiết kế đủ nguồn phát và khả năng tích trữ |

Chức năng và cách đọc thông số inverter có ở bài [Inverter điện mặt trời là gì](/inverter-dien-mat-troi-la-gi-no-thuc-hien-nhung-chuc-nang-nao).

## Bước 3: điện đi đâu trong nhà

Ở hệ hòa lưới thông dụng, điện mặt trời cấp trước cho những thiết bị đang chạy. Tải dùng nhiều hơn mức pin đang tạo thì phần thiếu lấy từ lưới. Tạo nhiều hơn nhu cầu thì phần dư có thể bị hạn chế phát, được nạp vào pin lưu trữ hoặc đưa lên lưới, tùy cấu hình và điều kiện áp dụng.

**Đừng mặc định điện dư được bán lên lưới.** Cơ chế đấu nối và xử lý điện dư phải được kiểm tra theo quy định, hợp đồng và trạng thái đấu nối thực tế của công trình.

Ví dụ giả định để thấy khác biệt: nhà A có người ở cả ngày, buổi trưa chạy điều hòa, tủ lạnh, máy bơm và máy tính. Nhà B đi làm cả ngày, tối mới dùng điện. Cùng một dàn pin, nhà A tận dụng trực tiếp được nhiều hơn. Nếu bạn giống nhà B, đọc thêm bài [nhà chủ yếu dùng điện buổi tối](/co-nen-lap-dien-mat-troi-khi-nha-chi-dung-dien-nhieu-vao-buoi-toi).

## Ban đêm và ngày nhiều mây

Ban đêm tấm pin không có nguồn sáng để phát điện đáng kể. Hệ không có pin lưu trữ vẫn lấy điện từ lưới bình thường nếu lưới đang hoạt động.

Trời nhiều mây, tấm pin vẫn tạo điện từ ánh sáng tán xạ nhưng công suất thường thấp hơn lúc nắng tốt. Sản lượng còn chịu ảnh hưởng của bụi bẩn, bóng che, hướng và độ nghiêng mái, nhiệt độ tế bào và hiệu suất inverter.

Pin lưu trữ chỉ **tích trữ điện**, nó không phải một loại tấm pin tạo điện. Dùng được bao lâu vào ban đêm phụ thuộc dung lượng khả dụng, hiệu suất, công suất xả và những thiết bị đang cần điện.

## Vì sao mất điện lưới mà hệ hòa lưới cũng dừng

Hệ hòa lưới thông dụng có chức năng chống đảo lưới: khi lưới mất điện, inverter phải ngừng phát lên lưới để bảo vệ người đang sửa đường dây và giữ an toàn cho hệ thống. Vì vậy **có tấm pin trên mái không đồng nghĩa với có điện khi cúp**.

Muốn giữ một số tải quan trọng lúc mất điện, cần thiết kế nhánh dự phòng với inverter và thiết bị chuyển mạch phù hợp. Không phải hybrid nào cũng mặc định cấp điện cho cả nhà khi mất lưới; kỹ sư phải tính công suất khởi động, nhóm tải ưu tiên và thời gian dự phòng. Bảng so sánh ba lựa chọn nằm ở bài [hệ hòa lưới, hybrid, độc lập](/he-hoa-luoi-hybrid-doc-lap-nen-chon-loai-nao).

## Phần còn lại của một hệ PV an toàn

Ngoài tấm pin và inverter, thiết kế cần xét dây dẫn DC và AC, thiết bị đóng cắt, chống sét lan truyền, tiếp địa, kết cấu khung, thông gió, đường đi dây và cách bảo trì. Yêu cầu cụ thể phụ thuộc quy mô công trình, tiêu chuẩn áp dụng và hướng dẫn của hãng.

Với nhà mái tôn hoặc mái cũ, khảo sát kết cấu và chống thấm là việc cần làm trước khi chốt số tấm pin.

## Trước khi hỏi "nên lắp bao nhiêu kWp"

Số tiền điện một tháng chưa đủ để chọn công suất. Chuẩn bị tối thiểu bốn dữ liệu:

1. Sản lượng điện (kWh) theo từng tháng trên hóa đơn.
2. Giờ chạy của các thiết bị lớn, đặc biệt phần dùng ban ngày.
3. Diện tích mái dùng được, tình trạng mái và bóng che.
4. Có cần điện dự phòng và pin lưu trữ hay không.

Có đủ bốn dữ liệu đó, đơn vị thiết kế mới có cơ sở tính công suất, ước lượng sản lượng và hiệu quả tài chính. Lắp nhiều hơn chưa chắc hoàn vốn nhanh hơn nếu phần điện tăng thêm không được dùng hiệu quả.

## Nguồn tham khảo

- [U.S. Department of Energy – How Does Solar Energy Work?](https://www.energy.gov/cmei/systems/how-does-solar-energy-work)
- [U.S. Department of Energy – PV Cells 101](https://www.energy.gov/cmei/systems/articles/pv-cells-101-primer-solar-photovoltaic-cell)
