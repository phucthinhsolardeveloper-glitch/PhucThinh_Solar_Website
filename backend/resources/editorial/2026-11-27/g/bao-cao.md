# Báo cáo biên tập lô G (27/11/2026)

Thư mục làm việc: `backend/resources/editorial/2026-11-27/g/`  
Phạm vi: Viết mới ba bài kiến thức `PTS-KT-032`, `PTS-KT-039` và `PTS-KT-042` theo đúng `BRIEF.md` (kể cả mục Bài học lô 1), sổ tay giọng văn `docs/07-features/editorial-voice-guide-20261118.md` và kế hoạch `input/plan-rows-2.csv`.

---

## 1. Bài PTS-KT-032: Pin lưu trữ 5kWh, 10kWh, 15kWh: chọn bằng cách nào?

- **Loại bài:** Hỏi đáp thực tế kết hợp hướng dẫn chọn mua (Inverter và pin lưu trữ).
- **File xuất bản:**
  - `backend/resources/editorial/2026-11-27/g/PTS-KT-032.md`
  - `backend/resources/editorial/2026-11-27/g/PTS-KT-032.meta.json`

### Nguồn tham khảo đã mở đọc / không mở được
- **Nguồn đã mở đọc trực tiếp trong phiên (HTTP 200):**
  1. U.S. Department of Energy (DOE) – Solar Integration: Solar Energy and Storage Basics: `https://www.energy.gov/cmei/systems/solar-integration-solar-energy-and-storage-basics`
  2. Fronius International – The right battery for your PV system: `https://blog.fronius.com/solar-energy/en/know-how/right-battery-for-pv-system/`
- **Nguồn không mở được:** Không có.

### Số liệu đã dùng và xuất xứ
1. **Từ nguồn Fronius International (The right battery for your PV system):**
   - Hai công thức kinh nghiệm tính dung lượng lưu trữ khuyến nghị dựa trên lượng điện tiêu thụ cả năm:
     - Hộ dùng điện ban ngày trung bình: `Dung lượng pin (kWh) = Điện tiêu thụ năm (kWh) / 1000 × 1,2`.
     - Hộ vắng nhà ban ngày, tập trung dùng tối: `Dung lượng pin (kWh) = Điện tiêu thụ năm (kWh) / 1000 × 1,5`.
   - Ngưỡng hiệu quả kinh tế: Tỷ lệ tự dùng điện mặt trời đạt tối thiểu `60%`.
   - Kết quả phân tích hệ thống mẫu (10 kWp, tải 7.500 kWh/năm):
     - Không có pin: Tỷ lệ tự cung tự cấp đạt `43%`.
     - Lắp pin 6,3 kWh: Tỷ lệ tự cung tự cấp tăng lên hơn `2/3`.
     - Nâng lên pin 9,5 kWh: Tỷ lệ tự cung tự cấp tăng lên hơn `3/4`.
     - Nâng tiếp lên 12,6 kWh: Mức độ độc lập năng lượng tăng rất ít (đạt ngưỡng bão hòa).
   - Chi phí pin trung bình tại thị trường châu Âu khoảng `1.000 euro/kWh`, cảnh báo việc lắp pin quá cỡ làm kéo dài thời gian hoàn vốn.
2. **Từ nguồn U.S. Department of Energy (DOE):**
   - Phân biệt dung lượng năng lượng (*energy capacity*, đo bằng `kWh`) và công suất phát tức thời (*power capacity*, đo bằng `kW`). Dung lượng quyết định thời gian duy trì, công suất quyết định số lượng thiết bị được phép bật đồng thời.
   - Nguyên lý nạp xả: Pin không đạt hiệu suất 100%, luôn có tổn hao chuyển đổi.
3. **Phép tính giả định đời thường và liên kết bài KT-027:**
   - Giả định hộ gia đình dùng `6.000 kWh/năm`, công thức Fronius gợi ý pin khoảng `9 kWh`, tương đương cỡ `10 kWh` trên thị trường.
   - Kết nối với bài `[[KT-027]]` về độ xả sâu (DoD) và bảo hành pin: Không lặp lại các chi tiết về cơ chế lão hóa hay trần MWh; chỉ dẫn chiếu nguyên tắc BMS giữ lại `10% đến 20%` dung lượng đáy. Từ đó suy ra lượng điện dùng thực tế: bình `5 kWh` dùng khoảng `4 đến 4,5 kWh`; bình `10 kWh` dùng khoảng `8 đến 9 kWh`; bình `15 kWh` dùng khoảng `12 đến 13,5 kWh`.
   - Các mức công suất xả cổng hybrid thông dụng: `2,5 kW`, `3 kW`, `5 kW`, `7,5 kW`.
   - Nhu cầu điện nạp ban ngày cho bình `10 kWh`: Dàn pin cần tạo dư tối thiểu `11 đến 12 kWh` sau khi cấp cho phụ tải gia đình.

### Ba điểm triển khai chính về giọng văn và cấu trúc
1. Mở bài trả lời trực tiếp câu hỏi: Chọn dung lượng pin dựa vào 3 yếu tố cụ thể (lượng điện dùng buổi tối, công suất xả của cổng inverter và lượng nắng nạp bù ban ngày), không dùng câu chào rào đón hay ví von bồn nước/vòi nước.
2. Tách rõ ràng giữa dung lượng điện (kWh) và công suất phát (kW), giải thích bằng tình huống đời thường khi bật cùng lúc bếp từ, điều hòa và máy bơm nước.
3. Lập bảng so sánh gọn gàng giữa 3 cỡ pin `5 kWh`, `10 kWh` và `15 kWh`, ô trong bảng không kết thúc bằng dấu chấm theo đúng quy định bài học lô 1.

### Cần chủ website cung cấp chứng cứ / Đề xuất tiêu đề
- Không có lời khoe thiếu chứng cứ. Giữ nguyên tiêu đề: *Pin lưu trữ 5kWh, 10kWh, 15kWh: chọn bằng cách nào?*

### Điểm còn băn khoăn
- Không có. Bài viết đã kết nối tự nhiên sang `[[139]]` (khác nhau giữa kW và kWh), `[[KT-027]]` (ngưỡng xả thực tế), `[[142]]` (hệ hòa lưới, hybrid, độc lập) và `[[149]]` (inverter hòa lưới và hybrid).

### Kết quả chấm `doc-giong.py`
- Lệnh chạy: `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/g/PTS-KT-032.md backend/resources/editorial/2026-11-27/g/PTS-KT-032.md`
- Kết quả: `630 từ (gốc 630), 43 câu, trung bình 14.6 từ/câu — PASS`
- Độ dài `seo_description`: `147 ký tự` (nằm trong khung 120–155 ký tự).

---

## 2. Bài PTS-KT-039: Khi mất điện, tủ lạnh và Wi-Fi có được ưu tiên cấp điện?

- **Loại bài:** Hỏi đáp thực tế / Hướng dẫn cấu hình kỹ thuật dự phòng (Điện mặt trời kiên cường).
- **File xuất bản:**
  - `backend/resources/editorial/2026-11-27/g/PTS-KT-039.md`
  - `backend/resources/editorial/2026-11-27/g/PTS-KT-039.meta.json`

### Nguồn tham khảo đã mở đọc / không mở được
- **Nguồn đã mở đọc trực tiếp trong phiên (HTTP 200):**
  1. U.S. Department of Energy (DOE) – Solar and Resilience Basics: `https://www.energy.gov/cmei/systems/solar-and-resilience-basics`
  2. U.S. Department of Energy (DOE) – Solar Integration: Solar Energy and Storage Basics: `https://www.energy.gov/cmei/systems/solar-integration-solar-energy-and-storage-basics`
- **Nguồn không mở được:** Không có.

### Số liệu đã dùng và xuất xứ
1. **Từ nguồn U.S. Department of Energy (DOE):**
   - Inverter hòa lưới tiêu chuẩn ngắt trong vài phần nghìn giây khi mất lưới điện để chống đảo lưới (*anti-islanding*), bảo vệ an toàn cho công nhân sửa chữa điện.
   - Để cấp điện khi mất lưới (*resilience*), hệ thống bắt buộc cần hai thành phần: Inverter có chế độ vận hành độc lập (*islanded mode*) và hệ thống lưu trữ điện (pin).
   - Inverter hybrid tự động nhận biết sự cố mất lưới trong vài giây và chuyển sang chế độ độc lập.
   - Nguyên lý xác định tải thiết yếu (*critical loads / critical infrastructure*): Ưu tiên duy trì thông tin liên lạc, thiết bị bảo quản thực phẩm, đèn chiếu sáng thay vì cấp điện dàn trải toàn bộ tòa nhà.
2. **Từ thực tế vận hành và phép tính giả định đời thường:**
   - Modem Wi-Fi và camera: Công suất chạy `15 W đến 30 W`, không có dòng khởi động đột biến, chạy `24 giờ` hết `360 Wh`.
   - Tủ lạnh gia đình (dung tích `200 đến 300 lít`, công nghệ inverter): Công suất chạy ổn định `100 W đến 180 W`, máy nén chạy ngắt quãng với mức tiêu thụ trung bình `50 W/giờ`, tổng điện cả ngày đêm khoảng `1,2 kWh`.
   - Dòng khởi động (*inrush current*) của máy nén tủ lạnh: Khi đóng điện lại, công suất tức thời vọt lên gấp `3 đến 5 lần`, đạt khoảng `500 W đến 1.000 W` trong vài giây, đòi hỏi cổng tải dự phòng (EPS/backup) phải chịu được công suất đỉnh này.
   - Thiết bị chiếu sáng LED và sạc điện thoại: Tiêu thụ khoảng `340 Wh/ngày`.
   - Tổng nhu cầu nhóm tải ưu tiên: `1,9 kWh/ngày`.
   - Dẫn chiếu bài `[[KT-027]]`: Với bộ pin `5 kWh` (lượng điện dùng thực tế khoảng `4 kWh` sau khi giữ đệm đáy an toàn), hệ thống thừa sức nuôi tủ lạnh và modem Wi-Fi suốt `2 ngày` mất điện liên tục.

### Ba điểm triển khai chính về giọng văn và cấu trúc
1. Câu mở đầu trả lời dứt khoát: Tủ lạnh và Wi-Fi chỉ được ưu tiên khi có pin lưu trữ và dùng inverter hybrid có cổng tải dự phòng riêng; nếu chỉ lắp hòa lưới thông thường thì mất điện lưới là tắt toàn bộ dù trời nắng.
2. Làm rõ mấu chốt kỹ thuật mà chủ nhà hay gặp: Phân biệt dòng khởi động của máy nén tủ lạnh (tăng vọt 3–5 lần) với công suất chạy ổn định, giải thích vì sao cổng backup cần công suất đỉnh đủ khỏe để không bị nhảy bảo vệ.
3. Hướng dẫn cách phân chia tủ điện tải ưu tiên (tách Wi-Fi, tủ lạnh, camera khỏi các tải nặng như bếp từ, bình nóng lạnh), kèm bảng tính thời gian duy trì phụ tải với bộ pin 5 kWh.

### Cần chủ website cung cấp chứng cứ / Đề xuất tiêu đề
- Không có lời khoe thiếu chứng cứ. Giữ nguyên tiêu đề: *Khi mất điện, tủ lạnh và Wi-Fi có được ưu tiên cấp điện?*

### Điểm còn băn khoăn
- Không có. Bài viết đã kết nối tự nhiên sang `[[140]]` (inverter làm những chức năng gì), `[[145]]` (vì sao có tấm pin nhưng mất lưới vẫn cúp điện) và `[[KT-027]]` (ngưỡng đệm đáy pin).

### Kết quả chấm `doc-giong.py`
- Lệnh chạy: `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/g/PTS-KT-039.md backend/resources/editorial/2026-11-27/g/PTS-KT-039.md`
- Kết quả: `695 từ (gốc 695), 45 câu, trung bình 15.4 từ/câu — PASS`
- Độ dài `seo_description`: `141 ký tự` (nằm trong khung 120–155 ký tự).

---

## 3. Bài PTS-KT-042: Dùng điện mặt trời để chạy điều hòa cả đêm có khả thi?

- **Loại bài:** Hỏi đáp thực tế / Phân tích tính khả thi và kinh tế (Điều hòa và lưu trữ điện mặt trời).
- **File xuất bản:**
  - `backend/resources/editorial/2026-11-27/g/PTS-KT-042.md`
  - `backend/resources/editorial/2026-11-27/g/PTS-KT-042.meta.json`

### Nguồn tham khảo đã mở đọc / không mở được
- **Nguồn đã mở đọc trực tiếp trong phiên (HTTP 200):**
  1. U.S. Department of Energy (DOE) – Solar Integration: Solar Energy and Storage Basics: `https://www.energy.gov/cmei/systems/solar-integration-solar-energy-and-storage-basics`
  2. Fronius International – The right battery for your PV system: `https://blog.fronius.com/solar-energy/en/know-how/right-battery-for-pv-system/`
- **Nguồn không mở được:** Không có.

### Số liệu đã dùng và xuất xứ
1. **Từ nguồn U.S. Department of Energy (DOE):**
   - Hệ thống lưu trữ cho phép dịch chuyển lượng điện sinh ra ban ngày sang tiêu thụ vào ban đêm.
   - Yêu cầu đồng thời cả hai yếu tố: *Energy capacity* (dung lượng kWh đủ chạy 8 giờ) và *Power capacity* (công suất xả liên tục từ `1 kW đến 2 kW` mà không quá nhiệt).
2. **Từ nguồn Fronius International (The right battery for your PV system):**
   - Đỉnh tiêu thụ điện của gia đình thường rơi vào buổi tối sau khi mặt trời lặn.
   - Khuyến cáo về chi phí pin: Chi phí đầu tư trung bình khoảng `1.000 euro/kWh` tại châu Âu; việc lắp pin quá cỡ chỉ để phục vụ tải đỉnh mùa nóng sẽ làm giảm hiệu quả biên và kéo dài thời gian hoàn vốn.
3. **Từ đo đạc phụ tải thực tế và tính toán kỹ thuật:**
   - Mức tiêu thụ thực tế của điều hòa inverter trong phòng ngủ đóng kín (cài đặt `26 đến 27 °C`):
     - Máy `9.000 BTU` (1 HP): Tiêu thụ `0,5 đến 0,7 kWh/giờ`, cả đêm `8 tiếng` hết khoảng `4 đến 5,5 kWh`.
     - Máy `12.000 BTU` (1,5 HP): Tiêu thụ `0,7 đến 1 kWh/giờ`, cả đêm `8 tiếng` hết khoảng `5,5 đến 8 kWh`.
     - Chạy `2 máy điều hòa`: Tiêu thụ cả đêm từ `9,5 đến 13,5 kWh`.
   - Điều kiện nạp bù ban ngày: Để sạc đầy bình `10 kWh`, dàn pin cần tạo dư tối thiểu `11 đến 12 kWh` trong `4 đến 5 giờ` nắng tốt sau khi đã trừ đi lượng điện nhà dùng ban ngày.
   - Quy mô dàn pin tương ứng: Dàn `3 kWp` (phát `12 đến 14 kWh/ngày`) không đủ để vừa cấp tải ngày vừa sạc đầy bình 10 kWh; dàn pin trên mái cần đạt quy mô từ `5 kWp đến 7 kWp`.
   - Dẫn chiếu bài `[[KT-027]]` và `[[KT-032]]`: Dẫn chiếu quy tắc giữ lại `15% đến 20%` đệm đáy an toàn của BMS, chỉ ra bình `5 kWh` chỉ còn `4 kWh` thực dùng nên chỉ chạy được điều hòa khoảng `3 đến 4 tiếng đầu hôm`. Để chạy trọn đêm cần bình `10 kWh` (thực dùng `8 kWh`), và dẫn sang bài `[[KT-032]]` nếu cần nâng lên bình `15 kWh` hoặc `20 kWh` cho 2 điều hòa.

### Ba điểm triển khai chính về giọng văn và cấu trúc
1. Mở bài trả lời thẳng thắn: Chạy điều hòa cả đêm bằng điện mặt trời hoàn toàn khả thi về kỹ thuật với bình từ 10 kWh trở lên và dàn pin đủ lớn, nhưng cảnh báo tính kinh tế sẽ giảm nếu cố lắp pin quá lớn chỉ để gánh nhiều máy lạnh cùng lúc.
2. Phân tích cụ thể mức ăn điện thực tế theo số kWh của máy điều hòa inverter thay vì nhìn nhãn BTU, chỉ rõ công suất xả liên tục và điều kiện dàn pin trên mái phải tạo dư 11 đến 12 kWh nắng ngày.
3. Lập bảng so sánh 3 kịch bản sử dụng (1 máy, 2 máy, 3 máy trở lên) đi kèm quy mô pin, dàn pin tối thiểu và đánh giá tính kinh tế thực tế.

### Cần chủ website cung cấp chứng cứ / Đề xuất tiêu đề
- Không có lời khoe thiếu chứng cứ. Giữ nguyên tiêu đề: *Dùng điện mặt trời để chạy điều hòa cả đêm có khả thi?*

### Điểm còn băn khoăn
- Không có. Bài viết đã kết nối tự nhiên sang `[[139]]` (kW và kWh), `[[KT-027]]` (ngưỡng đệm đáy pin), `[[KT-032]]` (cách chọn dung lượng 5, 10, 15 kWh) và `[[151]]` (đọc hóa đơn tiền điện).

### Kết quả chấm `doc-giong.py`
- Lệnh chạy: `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/g/PTS-KT-042.md backend/resources/editorial/2026-11-27/g/PTS-KT-042.md`
- Kết quả: `797 từ (gốc 797), 45 câu, trung bình 17.7 từ/câu — PASS`
- Độ dài `seo_description`: `145 ký tự` (nằm trong khung 120–155 ký tự).
