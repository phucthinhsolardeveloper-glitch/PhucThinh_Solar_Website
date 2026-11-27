# Báo cáo biên tập lô e (27/11/2026)

Thư mục bàn giao: `backend/resources/editorial/2026-11-27/e/`  
Các tệp đã tạo:
- `PTS-KT-029.md` và `PTS-KT-029.meta.json`
- `PTS-KT-041.md` và `PTS-KT-041.meta.json`
- `PTS-KT-044.md` và `PTS-KT-044.meta.json`
- `bao-cao.md`

---

## 1. Bài `PTS-KT-029`: Tấm pin N-type khác P-type thế nào?

- **Loại bài:** Giải thích khái niệm kỹ thuật & Công nghệ tế bào quang điện (`Kiến thức sản phẩm / Sản phẩm & kỹ thuật / Tấm pin năng lượng mặt trời`).
- **Tình trạng truy cập nguồn thực tế trong phiên làm việc (27/11/2026):**
  - Đã mở và tra cứu trực tiếp thành công (HTTP 200 OK):
    1. Clean Energy Reviews – *Solar PV cell construction*: `https://www.cleanenergyreviews.info/blog/solar-pv-cell-construction`
    2. Clean Energy Reviews – *Latest Solar Panel Technology*: `https://www.cleanenergyreviews.info/blog/latest-solar-panel-cell-technology`
    3. Clean Energy Reviews – *Most Efficient Solar Panels 2026*: `https://www.cleanenergyreviews.info/blog/most-efficient-solar-panels`
    4. Fraunhofer ISE – *Photovoltaics Report* (bản cập nhật tháng 7/2026): `https://www.ise.fraunhofer.de/en/publications/studies/photovoltaics-report.html`
    5. TaiyangNews – *Solar Technology Trends Report Maps Charts the Future of Solar PV* (tháng 5/2026): `https://taiyangnews.info/pressreleases/taiyangnews-solar-technology-trends-report-maps-charts-the-future-of-solar-pv`
  - Không có nguồn nào bị lỗi hoặc không mở được.
- **Số liệu đã dùng và nguồn gốc xuất xứ:**
  - `1% đến 3%`: Mức sụt giảm công suất do hiện tượng suy hao ánh sáng ban đầu (LID) ở tấm pin P-type pha bo truyền thống (theo Clean Energy Reviews – *Solar PV cell construction*).
  - `0,35% đến 0,40%/°C`: Hệ số suy giảm công suất theo nhiệt độ của cell P-type đơn tinh thể (theo Clean Energy Reviews – *Latest Solar Panel Technology*).
  - `0,25% đến 0,32%/°C` (và khoảng `0,26%/°C đến 0,30%/°C` ở cell HJT, IBC, TOPCon): Hệ số suy giảm công suất theo nhiệt độ của cell N-type (theo Clean Energy Reviews – *Latest Solar Panel Technology*).
  - `65% đến 70%`: Tỷ lệ phát điện mặt sau thông thường của cell P-type (theo Clean Energy Reviews – *Solar PV cell construction*).
  - `75% đến 85%`: Tỷ lệ phát điện mặt sau của cell N-type (theo Clean Energy Reviews – *Solar PV cell construction*).
  - `50 °C đến 65 °C`: Nhiệt độ bề mặt tấm pin thực tế trên mái nhà dưới trời nắng gắt (theo Clean Energy Reviews – *Latest Solar Panel Technology*).
  - `80%`: Thị phần toàn cầu của tấm pin P-type trước đây (khoảng năm 2017–2023, theo Clean Energy Reviews – *Latest Solar Panel Technology*).
  - `24% đến 25%`: Ngưỡng hiệu suất tấm pin thương mại của các dòng cell N-type hiện đại (theo Clean Energy Reviews – *Most Efficient Solar Panels 2026* và TaiyangNews Report 2026).
  - `1%` (sụt năm đầu) và `0,4%/năm` (suy hao các năm tiếp theo): Cam kết bảo hành hiệu suất điển hình của các dòng tấm pin N-type (so với mức 2% năm đầu và 0,55%/năm của P-type cũ, theo Clean Energy Reviews).
- **Ba điểm triển khai chính về giọng văn và cấu trúc:**
  1. **Tập trung vào công nghệ nền và cơ chế suy giảm, không lặp lại nội dung bài 155:** Mở bài trả lời thẳng vào sự khác biệt giữa hai loại hạt mang điện ở lớp đế (pha phốt pho tạo electron âm ở N-type so với pha bo tạo lỗ trống dương ở P-type). Giải thích cơ chế khiếm khuyết bo - oxy gây hiện tượng suy hao ánh sáng ban đầu (LID) ở P-type, và lý do N-type miễn nhiễm với khiếm khuyết này. Không lặp lại phần phân tích chi tiết cấu trúc TOPCon, HJT, BC mà dẫn liên kết sang bài `[[155]]`.
  2. **Liên kết chéo tự nhiên với bài kiểm định chất lượng Kiwa PVEL 2026:** Ở phần độ bền vật liệu đóng gói dài hạn, bài dẫn tự nhiên sang bài `[[KT-024]]` để người đọc nắm thêm dữ liệu về màng bọc và kính.
  3. **Lập bảng so sánh trực quan, ô bảng không có dấu chấm cuối câu, không dùng ví von vòi nước:** Lập bảng so sánh 5 tiêu chí kỹ thuật cốt lõi; dồn toàn bộ câu khuyến nghị mua hàng về một mục lời khuyên duy nhất ở cuối bài.
- **Cần chủ website cung cấp chứng cứ / Lời khoe đã gỡ:**
  - Bài không đưa ra các tuyên bố bán hàng, không tự nhận trải nghiệm thực tế chưa kiểm chứng.
- **Đề xuất tiêu đề:** Giữ nguyên tiêu đề theo kế hoạch: `Tấm pin N-type khác P-type thế nào?`.
- **Điểm còn băn khoăn:** Không có.
- **Kết quả `doc-giong.py`:**
  - Lệnh chạy: `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/e/PTS-KT-029.md backend/resources/editorial/2026-11-27/e/PTS-KT-029.md`
  - Kết quả: `890 từ (gốc 890), 52 câu, trung bình 17.1 từ/câu` -> **PASS**.
  - Kiểm tra `PTS-KT-029.meta.json`: `seo_description` dài 146 ký tự (nằm gọn trong khung 120–155 ký tự).

---

## 2. Bài `PTS-KT-041`: Tấm pin hai mặt kính và tấm pin hai mặt thu sáng: có giống nhau?

- **Loại bài:** Phân biệt khái niệm vật liệu đóng gói và khả năng quang điện (`Kiến thức sản phẩm / Sản phẩm & kỹ thuật / Tấm pin năng lượng mặt trời`).
- **Tình trạng truy cập nguồn thực tế trong phiên làm việc (27/11/2026):**
  - Đã mở và tra cứu trực tiếp thành công (HTTP 200 OK):
    1. pv magazine – *Parameter analysis of 842 bifacial solar modules from 236 manufacturers* (công bố nghiên cứu của Đại học Mỏ Saint Petersburg và Đại học Shiraz trên tạp chí *Renewable and Sustainable Energy Reviews*): `https://www.pv-magazine.com/2025/07/24/parameter-analysis-of-842-bifacial-solar-modules-from-236-manufacturers/`
    2. Fraunhofer ISE – *Photovoltaics Report*: `https://www.ise.fraunhofer.de/en/publications/studies/photovoltaics-report.html`
    3. Clean Energy Reviews – *Latest Solar Panel Technology*: `https://www.cleanenergyreviews.info/blog/latest-solar-panel-cell-technology`
  - Không có nguồn nào bị lỗi hoặc không mở được.
- **Số liệu đã dùng và nguồn gốc xuất xứ:**
  - `842` mã tấm pin hai mặt từ `236` nhà sản xuất tại `39` quốc gia (cơ sở dữ liệu nghiên cứu của nhóm tác giả V.V. Starshaia đăng trên pv magazine tháng 7/2025).
  - `70% đến 85%`: Hệ số phát điện mặt sau (bifaciality factor) điển hình của các dòng cell N-type TOPCon và HJT trong nghiên cứu trên pv magazine.
  - `93%` mẫu BOM đạt giải trong báo cáo Kiwa PVEL 2026 dùng kính mặt sau (glass-glass), chỉ `7%` còn dùng tấm nền nhựa backsheet (đối chiếu trực tiếp với bài `[[KT-024]]`).
  - `2,0 mm`: Độ dày kính cường lực mặt sau phổ biến của tấm pin hai mặt kính (theo phân tích cấu tạo BOM của Kiwa PVEL và Fraunhofer ISE).
  - Tỷ lệ phản xạ bề mặt (albedo): `50% đến 60%` với nền sơn trắng/màng sáng màu, `20% đến 30%` với sàn bê tông sáng màu/cát/sỏi, và `10% đến 20%` với mái tôn sẫm màu/ngói đỏ/thảm cỏ (theo tài liệu thiết kế hệ thống pv magazine và Fraunhofer ISE).
  - `0,5 m đến 1 m`: Khoảng cách nâng cao tối thiểu khuyến nghị từ mặt sàn lên khung giàn để đón ánh sáng phản xạ và tán xạ vào mặt sau tấm pin.
- **Ba điểm triển khai chính về giọng văn và cấu trúc:**
  1. **Trả lời dứt khoát ngay câu đầu tiên:** Khẳng định hai khái niệm hoàn toàn khác nhau; hai mặt kính (glass-glass) là cấu trúc đóng gói bảo vệ cơ học, còn hai mặt thu sáng (bifacial) là khả năng quang điện của tế bào.
  2. **Thuyết minh rõ điều kiện lắp đặt và nền phản xạ:** Chỉ rõ khi nào tấm pin hai mặt thu sáng bị vô hiệu hóa (khi lắp áp sát mái tôn/ngói dân dụng vì mặt sau bị tối hoàn toàn), nhưng nhấn mạnh lúc đó cấu tạo hai mặt kính vẫn mang lại giá trị lớn về chống ẩm, chống cháy lan và độ bền cơ học.
  3. **Lập bảng đối chiếu rõ ràng và gắn liên kết chéo hữu ích:** Bảng so sánh 5 tiêu chí tách biệt giữa đóng gói và quang điện; liên kết tự nhiên sang bài `[[KT-024]]` (tỷ lệ 93% kính sau), bài `[[137]]` (đọc thông số datasheet), và bài `[[KT-025]]` (tính sản lượng bằng PVGIS).
- **Cần chủ website cung cấp chứng cứ / Lời khoe đã gỡ:**
  - Không có lời khoe thiếu chứng cứ; không khẳng định mọi mái nhà đều tăng được sản lượng từ mặt sau.
- **Đề xuất tiêu đề:** Giữ nguyên tiêu đề kế hoạch: `Tấm pin hai mặt kính và tấm pin hai mặt thu sáng: có giống nhau?`.
- **Điểm còn băn khoăn:** Không có.
- **Kết quả `doc-giong.py`:**
  - Lệnh chạy: `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/e/PTS-KT-041.md backend/resources/editorial/2026-11-27/e/PTS-KT-041.md`
  - Kết quả: `848 từ (gốc 848), 51 câu, trung bình 16.6 từ/câu` -> **PASS**.
  - Kiểm tra `PTS-KT-041.meta.json`: `seo_description` dài 146 ký tự (nằm gọn trong khung 120–155 ký tự).

---

## 3. Bài `PTS-KT-044`: Đấu nối chuỗi tấm pin nối tiếp và song song khác nhau thế nào?

- **Loại bài:** Giải thích nguyên lý kỹ thuật điện & Hướng dẫn đọc thiết kế chuỗi (`Kiến thức sản phẩm / Sản phẩm & kỹ thuật / Kỹ thuật chuyên sâu`).
- **Tình trạng truy cập nguồn thực tế trong phiên làm việc (27/11/2026):**
  - Đã mở và tra cứu trực tiếp thành công (HTTP 200 OK):
    1. U.S. Department of Energy (DOE) – *Solar Photovoltaic System Design Basics*: `https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics`
    2. Clean Energy Reviews – *Designing off-grid and hybrid solar systems*: `https://www.cleanenergyreviews.info/blog/designing-off-grid-hybrid-solar-systems`
    3. Clean Energy Reviews – *Best grid connect solar inverters*: `https://www.cleanenergyreviews.info/blog/best-grid-connect-solar-inverters-sma-fronius-solaredge-abb`
  - Không có nguồn nào bị lỗi hoặc không mở được.
- **Số liệu đã dùng và nguồn gốc xuất xứ:**
  - `10 tấm pin`, `40 V`, `13 A` -> `400 V`, `13 A`: Ví dụ giả định nối tiếp 10 tấm pin giúp người đọc nhẩm nhanh nguyên lý cộng dồn điện áp và giữ nguyên dòng điện (ghi rõ "Giả sử...").
  - `2 chuỗi pin`, `400 V`, `13 A` -> `400 V`, `26 A`: Ví dụ giả định ghép song song hai chuỗi pin giúp người đọc thấy dòng điện tăng gấp đôi trong khi điện áp giữ nguyên (ghi rõ "Giả sử...").
  - `300 V đến 600 V`: Mức điện áp một chiều (DC) thực tế của một chuỗi pin dân dụng nối tiếp trên mái nhà (theo tài liệu thiết kế hệ thống của DOE và Clean Energy Reviews).
- **Ba điểm triển khai chính về giọng văn và cấu trúc:**
  1. **Chỉ giải thích nguyên lý điện học, tuyệt đối không hướng dẫn tự đấu nối:** Mở bài trả lời ngay sự khác biệt về điện áp và dòng điện của hai phương pháp. Cảnh báo rõ nguy hiểm của dòng điện một chiều DC điện áp cao (lên tới 600 V) và nhấn mạnh việc đấu nối, bấm đầu cáp MC4 phải do thợ điện chuyên nghiệp thực hiện.
  2. **Không dùng hình ảnh ví von "bồn nước / vòi nước / ống nước" theo đúng bài học lô 1:** Dùng phép tính số học trực tiếp, rõ ràng với các ví dụ điện thế và dòng điện cụ thể để người đọc hình dung cách các con số biến đổi.
  3. **Lập bảng so sánh 6 tiêu chí và gắn liên kết nội bộ tự nhiên:** Bảng so sánh phương thức đấu, điện áp, dòng điện, ảnh hưởng bóng râm, tiết diện dây và mục tiêu thiết kế (ô không kết thúc bằng dấu chấm). Gắn liên kết sang bài `[[KT-023]]` (cách MPPT xử lý bóng che), bài `[[140]]` (chức năng biến tần inverter), và bài `[[146]]` (thông số Voc, Vmp, Isc, Imp).
- **Cần chủ website cung cấp chứng cứ / Lời khoe đã gỡ:**
  - Không có lời khoe thương mại hay trải nghiệm công ty tự phong trong bài.
- **Đề xuất tiêu đề:** Giữ nguyên tiêu đề kế hoạch: `Đấu nối chuỗi tấm pin nối tiếp và song song khác nhau thế nào?`.
- **Điểm còn băn khoăn:** Không có.
- **Kết quả `doc-giong.py`:**
  - Lệnh chạy: `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/e/PTS-KT-044.md backend/resources/editorial/2026-11-27/e/PTS-KT-044.md`
  - Kết quả: `844 từ (gốc 844), 50 câu, trung bình 16.9 từ/câu` -> **PASS**.
  - Kiểm tra `PTS-KT-044.meta.json`: `seo_description` dài 141 ký tự (nằm gọn trong khung 120–155 ký tự).
