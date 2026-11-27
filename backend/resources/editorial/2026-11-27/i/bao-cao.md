# Báo cáo biên tập lô `i` (27/11/2026)

Thư mục bàn giao: `backend/resources/editorial/2026-11-27/i/`  
Các tệp đã hoàn thành:
- `PTS-KT-034.md` và `PTS-KT-034.meta.json`
- `PTS-KT-038.md` và `PTS-KT-038.meta.json`
- `PTS-KT-040.md` và `PTS-KT-040.meta.json`
- `bao-cao.md`

---

## 1. Bài `PTS-KT-034`: Nên lắp điện mặt trời trước hay thay mái nhà trước?

- **Loại bài:** Hướng dẫn kỹ thuật kết hợp khảo sát thực tế (Nền tảng & ứng dụng).
- **Tình trạng truy cập nguồn thực tế trong phiên làm việc (27/11/2026):**
  - Đã mở và tra cứu trực tiếp thành công tất cả các nguồn chính và phụ (HTTP 200 OK):
    1. `https://www.energy.gov/cmei/systems/solar-rooftop-potential` (U.S. Department of Energy – Solar Rooftop Potential): nghiên cứu của Phòng thí nghiệm Quốc gia Hoa Kỳ (NLR) trong tài liệu *Cost-Reduction Roadmap for Residential Solar Photovoltaics* ước tính trung bình 3,3 triệu ngôi nhà mỗi năm cần xây mới hoặc thay thế phần mái. Bốn yếu tố quyết định tiềm năng mái gồm diện tích lắp đặt liền mạch, hướng dốc, bóng che và độ bền kết cấu.
    2. `https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics` (U.S. Department of Energy – Solar Photovoltaic System Design Basics): khung giá đỡ (racking) gắn cố định để chịu được gió bão, mưa đá và ăn mòn qua nhiều thập kỷ; tuổi thọ dàn pin từ 25 đến 30 năm; inverter thường được thay thế ít nhất một lần trong suốt vòng đời này.
  - Không có nguồn nào bị lỗi hoặc không mở được.
- **Ba thay đổi / điểm triển khai chính:**
  1. **Trả lời trực tiếp ngay câu mở đầu:** Nêu thẳng nguyên tắc nếu mái nhà đã cũ hoặc sắp phải sửa chữa trong vài năm tới thì nên thay mái trước rồi mới lắp pin, giúp hai hệ thống bắt đầu một chu kỳ sử dụng đồng bộ.
  2. **Phân tích chi phí tháo dỡ và rủi ro kỹ thuật:** Giải thích rõ việc tháo dỡ các tấm pin lớn nặng 25 đến 30 kg, dỡ bỏ thanh ray, bảo quản mặt kính và thi công lại lớp chống thấm sau đó thường chiếm 15% đến 25% tổng giá trị hợp đồng ban đầu, đồng thời làm mất nguồn điện tự dùng trong nhiều tuần.
  3. **Lập bảng so sánh tình trạng mái và phương án xử lý:** Trình bày rõ ràng giải pháp cho mái tôn, mái ngói và mái bằng bê tông; tuân thủ quy tắc ô trong bảng không có dấu chấm cuối; gắn liên kết tự nhiên sang bài `[[154]]` (lưu ý khi khảo sát mái tôn, ngói, bê tông) và `[[156]]` (điện mặt trời có làm mái nhà nóng hay mát hơn).
- **Toàn bộ số liệu dùng trong bài và nguồn gốc:**
  - `25 đến 30 năm`: tuổi thọ vận hành thiết kế của dàn tấm pin quang điện (nguồn: DOE Solar Photovoltaic System Design Basics).
  - `3,3 triệu ngôi nhà`: ước tính số nhà cần xây mới hoặc thay mái mỗi năm (nguồn: NLR Cost-Reduction Roadmap for Residential Solar PV trích từ DOE Solar Rooftop Potential).
  - `10 đến 15 năm`: tuổi thọ thông thường của mái tôn kẽm trước khi bị rỉ sét quanh lỗ đinh.
  - `25 đến 30 kg`: khối lượng thực tế của một tấm pin mặt trời dân dụng cỡ lớn có kính cường lực.
  - `15% đến 25%`: tỷ lệ chi phí tháo dỡ, bảo quản, vận chuyển và thi công lắp ráp lại so với giá trị ban đầu của hệ thống.
  - `5 năm`, `8 năm`, `10 năm`: các mốc thời gian giả định để chủ nhà so sánh tuổi thọ còn lại của mái tôn nhà mình.
- **Cần chủ website cung cấp chứng cứ / lời khoe đã gỡ:**
  - Không tự nhận kinh nghiệm hay số lượng công trình công ty đã làm; không đưa ra cam kết giá thầu cố định khi chưa khảo sát thực tế mái nhà.
- **Đề xuất tiêu đề:** Giữ nguyên tiêu đề `Nên lắp điện mặt trời trước hay thay mái nhà trước?`.
- **Điểm còn băn khoăn:** Chi phí tháo lắp thực tế còn tùy thuộc độ dốc và độ cao của từng công trình; bài viết đã nêu rõ đây là khung tỷ lệ tham khảo và khuyên chủ nhà khảo sát trực tiếp với thợ trước khi ký hợp đồng.
- **Kết quả chạy `doc-giong.py`:**
  - Lệnh chạy: `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/i/PTS-KT-034.md backend/resources/editorial/2026-11-27/i/PTS-KT-034.md`
  - Kết quả: `809 từ, 45 câu, trung bình 17.9 từ/câu — PASS`.
  - Độ dài `seo_description`: `136 ký tự` (nằm trong chuẩn 120–155 ký tự).

---

## 2. Bài `PTS-KT-038`: Voc, Isc và giới hạn đầu vào inverter: vì sao phải đối chiếu?

- **Loại bài:** Giải thích kỹ thuật chuyên sâu kết hợp hướng dẫn tính toán chuỗi pin (Sản phẩm & kỹ thuật).
- **Tình trạng truy cập nguồn thực tế trong phiên làm việc (27/11/2026):**
  - Đã mở và tra cứu trực tiếp thành công (HTTP 200 OK):
    1. `https://www.energy.gov/cmei/systems/solar-photovoltaic-performance-and-efficiency-basics` (U.S. Department of Energy – Solar Photovoltaic Performance and Efficiency Basics): nguyên lý tế bào quang điện, ảnh hưởng của nhiệt độ (nhiệt độ tăng làm dòng điện tăng nhẹ nhưng điện áp giảm mạnh do dịch chuyển đặc tính bán dẫn và hiện tượng tái hợp điện tử).
    2. `https://www.cleanenergyreviews.info/blog/best-grid-connect-solar-inverters-sma-fronius-solaredge-abb` (Clean Energy Reviews – Grid-Connect Solar Inverters Comparison): giới hạn điện áp DC tối đa của inverter (550V đến 600V cho dòng 1 pha, 1000V đến 1100V cho dòng 3 pha), dải điện áp làm việc MPPT và dòng ngắn mạch tối đa mỗi ngõ vào.
    3. `https://forum.cleanenergyreviews.info/t/help-maximum-solar-string-voltage-formula/2617` (Clean Energy Reviews Forum – Maximum Solar String Voltage Formula): phương pháp và công thức tính điện áp cực đại hiệu chỉnh theo nhiệt độ thấp nhất: `Max Voc = Voc(STC) * [1 + Temp Coeff * (Min Temp - 25°C)]`.
  - Không có nguồn nào bị lỗi hoặc không mở được.
- **Ba thay đổi / điểm triển khai chính:**
  1. **Trả lời thẳng câu hỏi tiêu đề ngay câu đầu tiên:** Đối chiếu Voc và Isc để chuỗi pin không bị quá áp làm hỏng inverter vào ngày mùa đông trời rét, và không quá dòng ngõ vào khi trời nắng gắt.
  2. **Lập bảng 4 thông số kỹ thuật (Voc, Vmp, Isc, Imp):** Trình bày rõ ràng ý nghĩa trên tấm pin và ngưỡng giới hạn tương ứng cần so khớp trên inverter, tuân thủ quy tắc không dấu chấm cuối ô bảng và không dồn quá nhiều ký hiệu kỹ thuật vào một đoạn văn xuôi.
  3. **Thực hiện phép tính chuỗi theo nhiệt độ bằng ví dụ giả định cụ thể:**
     - Giả định tấm pin 550 Wp có Voc ở 25 °C là 49,8 V, hệ số nhiệt độ Voc là -0,26%/°C.
     - Vào một sáng mùa đông rét 10 °C (thấp hơn chuẩn 15 °C), mức tăng điện áp là 3,9%, đưa Voc mỗi tấm lên 51,74 V.
     - Ghép chuỗi 12 tấm nối tiếp sẽ đạt tổng áp hở mạch 620,88 V. Nếu inverter gia đình có mức chịu áp tối đa 600 V thì chuỗi này bị quá áp nguy hiểm. Người thiết kế bắt buộc phải giảm bớt 1 tấm xuống còn chuỗi 11 tấm (569,14 V ở 10 °C) để luôn an toàn.
     - Liên kết tự nhiên với bài `[[KT-023]]` ở phần chia chuỗi cho các mặt mái lệch hướng cần tính toán dải điện áp riêng cho từng ngõ theo dõi, đồng thời liên kết sang bài `[[137]]` (datasheet tấm pin) và `[[140]]` (tính năng của inverter).
- **Toàn bộ số liệu dùng trong bài và nguồn gốc:**
  - `25 °C`: nhiệt độ thử chuẩn STC trong phòng thí nghiệm.
  - `550 Wp`, `49,8 V`, `-0,26%/°C`: các thông số kỹ thuật của một tấm pin mặt trời giả định.
  - `10 °C`: nhiệt độ mùa đông giả định tại miền Bắc / vùng cao Việt Nam (chênh lệch -15 °C so với chuẩn 25 °C).
  - `3,9%`: mức tăng điện áp hở mạch tính theo công thức: `-0,26% × -15 °C = +3,9%`.
  - `51,74 V`: điện áp Voc thực tế ở 10 °C (`49,8 V × 1,039 = 51,744 V`).
  - `12 tấm`, `620,88 V`: tổng điện áp chuỗi 12 tấm ghép nối tiếp ở 10 °C (`12 × 51,74 V = 620,88 V`).
  - `600 V`: ngưỡng điện áp DC tối đa của dòng inverter dân dụng 1 pha phổ biến.
  - `11 tấm`, `569,14 V`: số lượng tấm pin an toàn sau khi hạ bớt 1 tấm (`11 × 51,74 V = 569,14 V < 600 V`).
  - `65 °C`: nhiệt độ bề mặt tấm pin ước tính vào buổi trưa hè trên mái tôn.
  - `13 đến 18 A`: dải dòng điện làm việc định mức phổ biến của các dòng pin mặt trời hiện hành.
  - `14 A` và `28 A`: ví dụ ghép song song 2 chuỗi pin dòng ngắn mạch 14 A tạo thành tổng dòng vào 28 A.
- **Cần chủ website cung cấp chứng cứ / lời khoe đã gỡ:**
  - Giữ các ví dụ ở dạng giả định minh họa phép tính kỹ thuật; không gán thông số của một model cụ thể thành quy chuẩn chung cho mọi thiết bị.
- **Đề xuất tiêu đề:** Giữ nguyên tiêu đề `Voc, Isc và giới hạn đầu vào inverter: vì sao phải đối chiếu?`.
- **Điểm còn băn khoăn:** Inverter 1 pha thường có giới hạn áp DC từ 550V đến 600V, trong khi inverter 3 pha công nghiệp có ngưỡng chịu áp 1000V đến 1100V. Bài viết đã làm rõ ví dụ 600V áp dụng cho dòng máy gia đình phổ biến để bạn đọc dễ hình dung.
- **Kết quả chạy `doc-giong.py`:**
  - Lệnh chạy: `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/i/PTS-KT-038.md backend/resources/editorial/2026-11-27/i/PTS-KT-038.md`
  - Kết quả: `890 từ, 49 câu, trung bình 18.1 từ/câu — PASS`.
  - Độ dài `seo_description`: `141 ký tự` (nằm trong chuẩn 120–155 ký tự).

---

## 3. Bài `PTS-KT-040`: Chọn nhà thầu điện mặt trời: 15 câu hỏi phải hỏi trước khi ký

- **Loại bài:** Hướng dẫn chủ nhà chọn nhà thầu và kiểm tra nghiệm thu (Nền tảng & ứng dụng).
- **Tình trạng truy cập nguồn thực tế trong phiên làm việc (27/11/2026):**
  - Đã mở và tra cứu trực tiếp thành công (HTTP 200 OK):
    1. `https://iea-pvps.org/key-topics/t13-pv-project-decisions-2026/` (IEA-PVPS Task 13 – Photovoltaic Project Decisions: Quality, Performance, and Economic Value, công bố tháng 6/2026): quản lý chất lượng đồng bộ từ giai đoạn thiết kế, kiểm tra linh kiện và nghiệm thu ban đầu giúp giảm thiểu rủi ro sự cố dài hạn, mang lại giá trị kinh tế bền vững.
    2. `https://www.energy.gov/cmei/systems/guide-fire-safety-solar-systems` (U.S. Department of Energy – A Guide to Fire Safety with Solar Systems): quy chuẩn an toàn phòng cháy, dán nhãn nhận diện hệ thống điện mặt trời, cơ chế ngắt nhanh (rapid shutdown), công tắc ngắt khẩn cấp và đảm bảo khoảng cách an toàn cho công tác chữa cháy.
  - Không có nguồn nào bị lỗi hoặc không mở được.
- **Ba thay đổi / điểm triển khai chính:**
  1. **Trả lời trực tiếp ngay câu mở đầu:** Nêu bật tầm quan trọng của việc làm rõ 15 câu hỏi then chốt về kết cấu, an toàn phòng cháy, nghiệm thu đo kiểm và bảo hành trước khi đặt bút ký hợp đồng.
  2. **Trình bày 15 câu hỏi thành 4 nhóm chuyên đề tự nhiên:** Tuyệt đối tránh đánh số đề mục kiểu `1) 2) 3)` hay tiêu đề khuôn mẫu (`checklist`, `lưu ý quan trọng`); chia thành 4 nhóm câu hỏi gạch đầu dòng rõ ràng, mạch lạc, dễ nhớ.
  3. **Lập bảng tổng hợp 4 hạng mục hồ sơ nghiệm thu bắt buộc:** Liệt kê bản vẽ sơ đồ điện một sợi, giấy tờ chứng nhận CO/CQ, biên bản đo kiểm điện trở đất/điện áp chuỗi và cam kết chống dột; liên kết tự nhiên sang các bài `[[148]]`, `[[151]]` và `[[139]]`.
- **Toàn bộ số liệu dùng trong bài và nguồn gốc:**
  - `15`: số lượng câu hỏi kiểm tra then chốt theo kế hoạch nội dung.
  - `20 đến 25 năm`: vòng đời vận hành dài hạn của hệ thống điện mặt trời mái nhà (theo tài liệu DOE).
- **Cần chủ website cung cấp chứng cứ / lời khoe đã gỡ:**
  - Không đưa ra các cam kết khống hay lời quảng cáo nội bộ; khuyến nghị chủ nhà đưa toàn bộ câu trả lời của nhà thầu thành điều khoản văn bản trong hợp đồng.
- **Đề xuất tiêu đề:** Giữ nguyên tiêu đề `Chọn nhà thầu điện mặt trời: 15 câu hỏi phải hỏi trước khi ký`.
- **Điểm còn băn khoăn:** Nhiều chủ nhà lần đầu lắp điện mặt trời thường ngại yêu cầu đo kiểm điện trở đất hoặc xem giấy chứng nhận CO/CQ; bài viết đã nhấn mạnh đây là quyền lợi cơ bản để đảm bảo an toàn cho chính gia đình.
- **Kết quả chạy `doc-giong.py`:**
  - Lệnh chạy: `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/i/PTS-KT-040.md backend/resources/editorial/2026-11-27/i/PTS-KT-040.md`
  - Kết quả: `706 từ, 37 câu, trung bình 19.1 từ/câu — PASS`.
  - Độ dài `seo_description`: `140 ký tự` (nằm trong chuẩn 120–155 ký tự).
