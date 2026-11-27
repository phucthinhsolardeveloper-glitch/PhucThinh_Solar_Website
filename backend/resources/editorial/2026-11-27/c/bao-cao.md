# Báo cáo biên tập lô C (27/11/2026)

Thư mục làm việc: `backend/resources/editorial/2026-11-27/c/`
Phạm vi: Viết mới hai bài kiến thức `PTS-KT-026` và `PTS-KT-027` theo đúng `BRIEF.md`, `docs/07-features/editorial-voice-guide-20261118.md` và `input/plan-rows.csv`.

---

## 1. Bài PTS-KT-026: Đèn năng lượng mặt trời sáng được mấy đêm mưa liên tiếp?

- **Loại bài:** Giải thích khái niệm kết hợp hướng dẫn chọn mua thực tế (Đèn & máy bơm năng lượng mặt trời).
- **File xuất bản:**
  - `backend/resources/editorial/2026-11-27/c/PTS-KT-026.md`
  - `backend/resources/editorial/2026-11-27/c/PTS-KT-026.meta.json`

### Nguồn tham khảo đã mở đọc / không mở được
- **Nguồn đã mở đọc trực tiếp trong phiên (HTTP 200):**
  1. Trang tài nguyên Lighting Global – Pico-PV Quality Standards: `https://www.lightingglobal.org/resource/lighting-global-quality-standards/` và tài liệu PDF tiêu chuẩn chính thức liên kết trực tiếp trên trang: `https://www.lightingglobal.org/wp-content/uploads/2018/12/Pico_MQS_v8_0.pdf` (*Pico-PV Quality Standards, Version 8.0, December 2018*).
  2. Trang tài nguyên Lighting Global – Solar Home System Kit Quality Standards: `https://www.lightingglobal.org/resource/solar-home-system-kit-quality-standards/` và tài liệu PDF tiêu chuẩn chính thức liên kết trực tiếp trên trang: `https://www.lightingglobal.org/wp-content/uploads/2018/12/SHS_MQS_v2_5.pdf` (*Solar Home System Kit Quality Standards, Version 2.5, December 2018*).
- **Nguồn không mở được:** Không có (tất cả nguồn giao trong `plan-rows.csv` và tài liệu PDF tiêu chuẩn đính kèm đều mở và đọc được đầy đủ).

### Số liệu đã dùng và xuất xứ
1. **Từ nguồn Lighting Global (Pico-PV Quality Standards v8.0 & SHS Kit Quality Standards v2.5):**
   - `IEC/TS 62257-9-5`: Phương pháp thử nghiệm chất lượng (Quality Test Method – QTM) dùng để đánh giá đèn và bộ điện mặt trời độc lập (trang 1 cả hai tài liệu).
   - `10 W`: Ngưỡng công suất đỉnh tối đa của nhóm đèn và thiết bị Pico-PV thuộc bộ tiêu chuẩn Pico-PV Quality Standards (trang 1).
   - `350 W`, `35 V`: Ngưỡng công suất đỉnh tối đa (350 W) và điện áp một chiều tối đa (35 V DC) của bộ Solar Home System Kit (trang 1 SHS Kit Quality Standards).
   - `15%`: Độ lệch tối đa cho phép giữa thông số công bố trên bao bì (độ sáng, thời gian chiếu sáng) so với kết quả đo thực tế tại phòng thử nghiệm; nếu chỉ ghi một con số thời gian sáng thì mặc định tính cho mức sáng cao nhất sau một ngày nạp nắng (trang 6–7 Pico-PV v8.0; trang 7 SHS v2.5).
   - `2,50 V`, `2,45 V`, `3,65 V`, `3,70 V`, `45 °C`: Ngưỡng điện áp ngắt bảo vệ xả sâu mặc định của pin LiFePO4 (khuyến nghị từ `2,50 V/cell`, tối thiểu `2,45 V/cell`) và ngưỡng bảo vệ quá sạc mặc định của pin LiFePO4 (khuyến nghị `≤ 3,65 V/cell`, tối đa `3,70 V/cell`, nhiệt độ sạc tối đa `45 °C`) trong Bảng 2 và Bảng 3 (trang 9 Pico-PV v8.0; trang 10 SHS v2.5).
   - `3,00 V`, `2,95 V`: Ngưỡng điện áp ngắt bảo vệ xả sâu mặc định của pin Lithium-ion thường (khuyến nghị `≥ 3,00 V/cell`, tối thiểu `2,95 V/cell`) trong Bảng 2 (trang 9 Pico-PV v8.0; trang 10 SHS v2.5).
   - `25%`: Mức sụt dung lượng trung bình tối đa cho phép của các mẫu pin sau phép thử độ bền lưu kho theo `IEC 62257-9-5 Annex BB` (trang 3 cả hai tài liệu).
   - `2 năm`, `80%`: Thời hạn bảo hành tối thiểu `2 năm` cho hệ thống chính của bộ SHS Kit, đi kèm giả định pin giữ lại ít nhất `80%` dung lượng gốc sau 2 năm (trang 5 SHS v2.5).
2. **Từ phép tính ví dụ giả định bằng Wh (ghi rõ "Giả sử..." theo đúng yêu cầu đề bài):**
   - Giả sử pin LiFePO4 `3,2 V`, dung lượng `15 Ah` → tổng điện tích khi sạc đầy: `3,2 × 15 = 48 Wh`.
   - Giả sử mạch bảo vệ chừa phần điện đáy bình và cho phép xả tối đa `40 Wh`, thời gian chiếu sáng mỗi đêm là `10 giờ`, lượng điện nạp bù trong một ngày mưa âm u giả sử là `10 Wh`:
     - Kịch bản bật sáng mạnh `4 W` suốt `10 giờ` = `40 Wh/đêm` → sáng trọn `1 đêm` đầu; đêm mưa kế tiếp với `10 Wh` nạp bù chỉ sáng được `2,5 giờ`.
     - Kịch bản giảm sáng theo giờ còn trung bình `2 W` suốt `10 giờ` = `20 Wh/đêm` → `40 Wh` trong pin đủ sáng `2 đêm`; cộng thêm `10 Wh` nạp ngày mưa thì sáng thêm nửa đêm thứ ba (`3 đêm`).
     - Kịch bản sáng dịu chờ cảm biến `1,2 W` suốt `10 giờ` = `12 Wh/đêm` → đủ duy trì qua `3 đêm` mưa liên tiếp.
   - Các con số đời thường giải thích thói quen dùng và nhãn thương mại: dòng chữ quảng cáo `2 đến 3 ngày mưa`, chu kỳ hạ công suất sau `4 đến 5 giờ` đầu buổi tối, nhãn tên mẫu thương mại `100 W`, `300 W`.

### Ba điểm triển khai chính về giọng văn và cấu trúc
1. Mở bài trả lời thẳng câu hỏi tiêu đề ngay câu đầu tiên, dùng hình ảnh đời thường (bồn nước, ống hứng nước, vòi xả) để giải thích mối quan hệ giữa dung lượng pin (Wh), tấm pin nạp bù và công suất xả của LED.
2. Lập bảng so sánh 3 kịch bản xả trên cùng một viên pin giả định `48 Wh` (`40 Wh` dùng được) để người mua thấy ngay vì sao chế độ giảm sáng quyết định số đêm trụ qua mưa.
3. Đưa lời khuyên hỏi người bán về một đoạn duy nhất ở cuối bài (hỏi điện áp `V`, dung lượng `Ah`, công suất tấm pin `Wp` và quy luật giảm sáng), không rải câu mệnh lệnh hoặc từ rào chắn.

### Cần chủ website cung cấp chứng cứ / Đề xuất tiêu đề
- Không có lời khoe thiếu chứng cứ trong bài; không tự nhận trải nghiệm lắp đặt hay thông số riêng của một mẫu đèn chưa kiểm định.
- Giữ nguyên tiêu đề của kế hoạch nội dung: *Đèn năng lượng mặt trời sáng được mấy đêm mưa liên tiếp?*

### Điểm còn băn khoăn
- Không có. Nếu sau này chủ website muốn gắn liên kết nội bộ tới một mẫu đèn cụ thể đang bán có bảng đo Wh và Wp thực tế, có thể bổ sung thêm đường dẫn sản phẩm ở cuối bài.

### Kết quả chấm `doc-giong.py`
- Lệnh chạy: `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/c/PTS-KT-026.md backend/resources/editorial/2026-11-27/c/PTS-KT-026.md`
- Kết quả: `851 từ (gốc 851), 54 câu, trung bình 15.5 từ/câu — PASS`
- Độ dài `seo_description`: `141 ký tự` (nằm trong khung 120–155 ký tự).

---

## 2. Bài PTS-KT-027: Có nên sử dụng hết công suất pin lưu trữ mỗi ngày?

- **Loại bài:** Hỏi đáp thực tế / Giải thích kỹ thuật vận hành & bảo hành (Pin lưu trữ cho hệ điện mặt trời gia đình).
- **File xuất bản:**
  - `backend/resources/editorial/2026-11-27/c/PTS-KT-027.md`
  - `backend/resources/editorial/2026-11-27/c/PTS-KT-027.meta.json`

### Nguồn tham khảo đã mở đọc / không mở được
- **Nguồn đã mở đọc trực tiếp trong phiên (HTTP 200):**
  1. IEA PVPS Task 13 – *Assessing the Reliability of Battery Systems in Solar Power Plants in Operation*: trang giới thiệu báo cáo `https://iea-pvps.org/key-topics/assessing-reliability-battery-systems-plants-2026/` và tài liệu PDF Executive Summary chính thức đính kèm trên trang `https://iea-pvps.org/wp-content/uploads/2026/07/IEA-PVPS-T13-35-2026-EXEC-SUMM-PV-BESS-2026.pdf` (Báo cáo `IEA-PVPS T13-35:2026`, xuất bản tháng `7/2026`).
  2. Clean Energy Reviews – *Home solar battery systems*: `https://www.cleanenergyreviews.info/blog/home-solar-battery-systems`.
  3. Clean Energy Reviews – *Lithium Battery Warranties and What You Should Know* (trang chuyên đề bảo hành pin lithium liên kết trực tiếp từ bài Home solar battery systems của Clean Energy Reviews): `https://www.cleanenergyreviews.info/blog/lithium-battery-warranties-explained` cùng trang hướng dẫn chi tiết `https://www.cleanenergyreviews.info/blog/home-solar-battery-cost-guide`.
- **Nguồn không mở được:** Không có.

### Số liệu đã dùng và xuất xứ
1. **Từ báo cáo IEA-PVPS T13-35:2026 (tháng 7/2026):**
   - Mã báo cáo `IEA-PVPS T13-35:2026`, thời điểm công bố tháng `7/2026` của nhóm công tác `IEA PVPS Task 13` (trang 1 PDF).
   - Pin `LFP (LiFePO4)` hiện chiếm thị phần lớn nhất trong các hệ thống lưu trữ năng lượng (`BESS`) ngành điện mặt trời (trang 2 PDF).
   - Hai cơ chế lão hóa cộng gộp của pin lưu trữ: lão hóa theo thời gian (*calendar ageing*, phụ thuộc chính vào nhiệt độ và mức tích điện `SoC`) và lão hóa theo chu kỳ (*cyclic ageing*, phụ thuộc vào chế độ nạp xả *duty cycle* của từng ứng dụng); với hệ tự dùng (*self-consumption*), chế độ điển hình là một hoặc vài chu kỳ xả sâu mỗi ngày (trang 2–4 PDF, Bảng 1).
   - `6 chỉ số` vận hành cốt lõi (*six core performance indicators*) để theo dõi độ khỏe (`SoH`): dung lượng thực (*capacity*), khả năng chịu công suất (*power tolerance*), điện trở trong (*internal resistance*), hiệu suất nạp xả hai chiều (*round-trip efficiency*), thời gian đáp ứng (*response time*) và tổn hao chờ (*standby losses*) (trang 2 & trang 4 PDF).
2. **Từ Clean Energy Reviews (Home Solar Battery Systems, Detailed Home Solar Battery Guide & Lithium Battery Warranties Explained):**
   - Ví dụ định nghĩa độ xả sâu (`DoD`): bộ pin `10 kWh` xả ra `8 kWh` tương ứng `80% DoD`.
   - Ngưỡng thiết kế xả hằng ngày: hầu hết pin lithium gia đình cho phép xả tới `80%` hoặc `90%` tổng dung lượng, trong khi ắc quy chì truyền thống (`Gel, AGM`) thường không xả thấp hơn mức còn lại `60%` điện trong bình (*Detailed Home Solar Battery Guide*, mục *Depth of discharge and cycle life*).
   - Tốc độ suy giảm dung lượng tự nhiên của pin lithium (nhất là LFP) khi lắp đặt và dùng điều độ: trung bình `2% đến 3%` mỗi năm (*Lithium Battery Warranties Explained*, mục *Battery Degradation Explained*).
   - Tuổi thọ vận hành thực tế của pin `LFP` và `NMC`: khoảng `10 đến 15 năm` (*Detailed Home Solar Battery Guide*, mục *Battery Comparison Criteria*).
   - Chu kỳ sạc đầy đỉnh để `BMS` cân bằng cell (*cell balancing*) và hiệu chuẩn `SoC`: nhiều hãng quy định trong điều kiện bảo hành phải sạc đầy ít nhất một lần mỗi `7 đến 14 ngày` (*Lithium Battery Warranties Explained*, mục *Warranty Conditions - The Fine Print*).
   - Ngưỡng nhiệt độ vận hành và bảo hành: pin LFP không được sạc dưới `0 °C` (tránh hiện tượng kết tủa lithium – *lithium plating* làm hỏng cell) và giới hạn nhiệt độ làm việc tối đa ở `45 °C đến 50 °C`; phơi nắng trực tiếp làm quá nhiệt sẽ bị từ chối bảo hành (*Lithium Battery Warranties Explained*, mục *Operating Temperature Range* & *Sub-Zero Temperatures*).
   - Các mốc bảo hành tiêu chuẩn: thời hạn `10 năm` hoặc tới `6.000 chu kỳ`, dung lượng cam kết cuối kỳ (`EoL`) từ `60% đến 80%` dung lượng gốc (phổ biến là `70%`, tức pin `10 kWh` sau `10 năm` vẫn còn ít nhất `7 kWh` dùng được mỗi chu kỳ).
   - Phép tính tổng năng lượng thông qua (*Minimum Throughput*):
     - `1 chu kỳ/ngày` ở `80% DoD` trên bộ pin `10 kWh` = `8 kWh/ngày`. Sau `10 năm` (`3.650 ngày`), tổng năng lượng thông qua là `8 kWh × 3.650 = 29,2 MWh` (nằm trong gói bảo hành `10 năm` hoặc `30 MWh`).
     - Nếu gói bảo hành chỉ giới hạn trần `24 MWh`, chạy `8 kWh/ngày` sẽ chạm mốc `24 MWh` sau `3.000 ngày` (khoảng năm thứ `8`), tức hết bảo hành trước mốc `10 năm`.
     - Nếu chạy `2 chu kỳ/ngày` (ngày sạc điện mặt trời xả tối, đêm sạc điện lưới giờ thấp điểm xả sáng) ở mức `8 kWh/chu kỳ` = `16 kWh/ngày`, sau `10 năm` tổng sản lượng thông qua lên tới `58,4 MWh`, nên sẽ chạm trần `30 MWh` chỉ sau khoảng `5 năm`.
   - Gợi ý cài đặt ngưỡng dự trữ đáy bình khi vận hành hằng ngày: chừa lại `15% đến 20%` dung lượng (tương ứng mức xả `80% đến 85% DoD`) khi điện lưới ổn định, hoặc nâng phần dự trữ lên `25% đến 30%` ở nơi hay mất điện đột xuất.

### Ba điểm triển khai chính về giọng văn và cấu trúc
1. Trả lời trực tiếp ngay câu mở đầu: dùng hết dung lượng khả dụng mỗi tối là hoàn toàn được vì BMS đã khóa vùng đệm an toàn, nhưng chỉ rõ sự đánh đổi giữa xả kịch trần / chạy 2 vòng mỗi ngày với tốc độ suy giảm dung lượng và giới hạn MWh bảo hành.
2. Tách bạch rõ 3 lớp kiến thức mà chủ nhà hay nhầm lẫn: cơ chế ngắt bảo vệ và cân bằng cell của BMS, hai cơ chế lão hóa pin theo báo cáo IEA PVPS 2026, và công thức tính tổng sản lượng MWh thông qua trong hợp đồng bảo hành.
3. Trình bày bảng so sánh 3 kịch bản khai thác bộ pin `10 kWh` (`1 chu kỳ/ngày` gói `30 MWh`, `1 chu kỳ/ngày` gói `24 MWh`, và `2 chu kỳ/ngày`) để người đọc tự nhẩm ngay trên báo giá thực tế.

### Cần chủ website cung cấp chứng cứ / Đề xuất tiêu đề
- Không có lời khoe thiếu chứng cứ; không gán chính sách bảo hành của một hãng cho mọi hãng.
- Giữ nguyên tiêu đề của kế hoạch nội dung: *Có nên sử dụng hết công suất pin lưu trữ mỗi ngày?* (Trong bài đã làm rõ đây là việc xả hết dung lượng lưu trữ kWh hằng ngày của bộ pin gia đình).

### Điểm còn băn khoăn
- Tiêu đề gốc trong kế hoạch dùng cụm từ đời thường của khách hàng là *"sử dụng hết công suất pin lưu trữ mỗi ngày"* (người mua thường gọi chung dung lượng điện tích trữ kWh là "công suất pin"). Trong thân bài đã giải thích tự nhiên theo đúng đơn vị dung lượng (`kWh`) và độ xả sâu (`DoD`) để khách hàng vừa dễ hiểu vừa chuẩn kỹ thuật.

### Kết quả chấm `doc-giong.py`
- Lệnh chạy: `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/c/PTS-KT-027.md backend/resources/editorial/2026-11-27/c/PTS-KT-027.md`
- Kết quả: `884 từ (gốc 884), 52 câu, trung bình 16.8 từ/câu — PASS`
- Độ dài `seo_description`: `146 ký tự` (nằm trong khung 120–155 ký tự).
