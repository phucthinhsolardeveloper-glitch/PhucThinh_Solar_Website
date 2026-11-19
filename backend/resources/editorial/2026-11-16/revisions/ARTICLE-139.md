**kW** cho biết công suất tại một thời điểm, **kWp** cho biết quy mô danh định của dàn tấm pin, còn **kWh** cho biết lượng điện đã dùng hoặc đã tạo ra trong một khoảng thời gian. Ba ký hiệu này thường nằm cạnh nhau trên cùng một báo giá nhưng trả lời ba câu hỏi khác nhau.

| Ký hiệu | Trả lời câu hỏi | Ví dụ |
|---|---|---|
| **kW** | Mức công suất tại một thời điểm | Máy bơm đang chạy 1 kW |
| **kWp** | Quy mô đỉnh danh định của dàn pin tại điều kiện thử nghiệm tiêu chuẩn (STC) | 10 tấm 600 Wp = 6 kWp DC |
| **kWh** | Lượng điện tích lũy theo thời gian | Máy 1 kW chạy 3 giờ ≈ 3 kWh |

## Công thức dùng được ngay trên hóa đơn

**Điện năng (kWh) = Công suất (kW) × Thời gian (giờ).**

Hóa đơn ghi 450 kWh là tổng điện của cả kỳ, không phải nhà đang chạy ở 450 kW. Nhiều thiết bị như tủ lạnh, điều hòa chạy theo chu kỳ bật tắt, nên công suất thực tế của chúng thay đổi liên tục trong ngày.

Chữ "p" trong kWp là "peak", nghĩa là đỉnh. Nó nhắc rằng đây là công suất đo ở điều kiện chuẩn, không phải mức dàn pin phát ra mọi lúc. Cách điện mặt trời đi từ tấm pin tới ổ cắm có ở bài [Điện mặt trời hoạt động như thế nào](/dien-mat-troi-hoat-dong-nhu-the-nao).

## Bảy tình huống đọc nhanh

1. **Inverter ghi 5 kW:** đó là công suất AC danh định, không phải sản lượng mỗi giờ.
2. **Dàn 5 kWp:** tổng công suất DC danh định của các tấm ở điều kiện thử nghiệm.
3. **Pin lưu trữ 10 kWh:** dung lượng danh định; dùng được bao lâu còn tùy tải và dung lượng khả dụng.
4. **Ứng dụng báo 25 kWh hôm nay:** tổng điện đã tạo trong ngày, không phải 25 kW liên tục.
5. **Máy bơm 1,5 kW chạy 2 giờ:** ví dụ lý tưởng là 3 kWh; thực tế máy có thể chạy theo chu kỳ.
6. **Tám tấm 625 Wp:** cộng công suất danh định được 5 kWp, chưa phải sản lượng ngày.
7. **Hệ 5 kWp tạo ít điện hơn hôm trước dù trời nắng:** bức xạ, nhiệt độ, bóng che, hướng mái và hiệu suất inverter làm kết quả thay đổi.

## Ba chỗ hay nhầm trên báo giá

**kWp của dàn pin và kW của inverter không nhất thiết bằng nhau.** Một báo giá có thể ghi dàn 6 kWp đi với inverter 5 kW. Đó không phải lỗi đánh máy; tỷ lệ giữa hai con số là một lựa chọn thiết kế và phải nằm trong giới hạn của đúng model inverter. Bài [Inverter điện mặt trời là gì](/inverter-dien-mat-troi-la-gi-no-thuc-hien-nhung-chuc-nang-nao) giải thích phần giới hạn này.

**kWh của pin lưu trữ có hai loại: danh định và khả dụng.** Ví dụ giả định: pin ghi 10 kWh nhưng hãng chỉ cho dùng 90% để bảo vệ tuổi thọ, thì phần khả dụng là 9 kWh; tổn hao khi sạc xả còn làm số điện thực nhận thấp hơn. Tỷ lệ thật phải lấy từ datasheet của đúng model.

**kW của pin lưu trữ cũng quan trọng như kWh.** kWh cho biết pin chứa bao nhiêu điện, kW cho biết pin xả nhanh được bao nhiêu. Pin đủ kWh nhưng công suất xả nhỏ vẫn không chạy được cùng lúc điều hòa, bếp và máy bơm.

## Một phép tính minh họa, không phải dự báo

Giả sử tải 2 kW chạy đều trong 3 giờ: điện năng là 2 × 3 = 6 kWh. Đây chỉ là phép đổi đơn vị.

Không lấy 5 kWp nhân một số giờ cố định để hứa sản lượng, vì điều kiện mái và thời tiết thay đổi từng ngày. Ước lượng sản lượng cần dữ liệu bức xạ nơi lắp, hướng mái, bóng che và tổn hao của hệ thống.

## Tự kiểm tra trong 1 phút

- Hóa đơn tháng ghi 600 kWh. Trung bình mỗi ngày nhà dùng bao nhiêu? *Khoảng 20 kWh nếu tháng có 30 ngày.*
- Người bán nói "hệ 5 kWp phát 5 kW cả ngày". Đúng không? *Không; 5 kWp là mức danh định ở điều kiện chuẩn.*
- Đèn 50 W bật 10 giờ dùng bao nhiêu điện? *50 W × 10 h = 500 Wh = 0,5 kWh.*

## Bốn dòng nên ghi riêng khi so báo giá

1. Công suất AC của inverter (kW).
2. Công suất DC của dàn pin (kWp).
3. Dung lượng danh định và khả dụng của pin lưu trữ, nếu có (kWh), kèm công suất xả (kW).
4. Điện năng nhà đang dùng theo tháng, lấy từ hóa đơn (kWh).

Tách bốn dòng này giúp tránh so những con số vốn đo những việc khác nhau. Nếu nhà dùng điện chủ yếu sau giờ nắng, đọc thêm bài [nhà chủ yếu dùng điện buổi tối](/co-nen-lap-dien-mat-troi-khi-nha-chi-dung-dien-nhieu-vao-buoi-toi).

## Nguồn tham khảo

- [U.S. Department of Energy – Solar Photovoltaic System Design Basics](https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics)
- [U.S. Department of Energy – Solar Photovoltaic Technology Basics](https://www.energy.gov/cmei/systems/solar-photovoltaic-technology-basics)
- [U.S. Department of Energy – Solar Photovoltaic Performance and Efficiency Basics](https://www.energy.gov/cmei/systems/solar-photovoltaic-performance-and-efficiency-basics)
