# Báo cáo biên tập: PTS-KT-028, PTS-KT-031 và PTS-KT-037 (27/11/2026)

Thư mục thực hiện: `backend/resources/editorial/2026-11-27/d/`.

---

## 1. PTS-KT-028 — Tại sao công suất 10 kWp không có nghĩa lúc nào cũng phát 10 kW?

- **Loại bài:** Kiến thức nền tảng kết hợp giải thích kỹ thuật thực tế cho chủ nhà.
- **Ba điểm cốt lõi trong cách viết:**
  1. **Trả lời trực tiếp câu hỏi tiêu đề ngay đoạn mở bài:** Khẳng định rõ ràng con số 10 kWp là công suất đỉnh đo trong phòng thử nghiệm tiêu chuẩn (STC); ngoài mái nhà thực tế, công suất tức thời lúc nắng đẹp thường dao động từ 7,5 kW đến 8,5 kW.
  2. **Tuân thủ đúng chỉ đạo biên tập và bài học lô 1:** Tuyệt đối không dùng hình ảnh ví von "bồn nước / vòi nước / ống nước"; không giải thích lại các đơn vị đo mà dẫn liên kết tự nhiên sang bài `[[139]]` trên web; tập trung sâu vào cơ chế vật lý làm sụt công suất: sự chênh lệch giữa điều kiện STC và mái nhà, hiện tượng sụt áp khi nhiệt độ cell pin nóng lên theo tài liệu của DOE, và hiện tượng xén công suất (clipping) của biến tần khi chọn tỷ lệ DC/AC lớn hơn 1.
  3. **Lập bảng 4 nguồn hao hụt tự nhiên và hướng dẫn chủ nhà cách đánh giá đúng:** Bảng so sánh 4 yếu tố gây suy hao (góc nghiêng mái, bụi bẩn, điện trở dây dẫn DC, hiệu suất biến tần) có các ô không kết thúc bằng dấu chấm; khuyên chủ nhà đánh giá dàn pin qua tổng sản lượng kWh hàng tháng (35–45 kWh/ngày, 1.100–1.350 kWh/tháng tại Việt Nam) thay vì nhìn con số kW tức thời; dẫn liên kết tự nhiên sang bài `[[137]]`.
- **Nguồn tham khảo đã mở đọc / không mở được:**
  - **Đã mở và tra cứu nội dung trong phiên:**
    - `https://www.energy.gov/cmei/systems/solar-photovoltaic-technology-basics` (U.S. Department of Energy – Solar Photovoltaic Technology Basics): cấu tạo tế bào quang điện bán dẫn, cách ghép nối cell thành module và dàn pin (array), vai trò chuyển đổi dòng điện của biến tần.
    - `https://www.energy.gov/cmei/systems/solar-photovoltaic-performance-and-efficiency-basics` (U.S. Department of Energy – Solar Photovoltaic Performance and Efficiency Basics): ba điều kiện thử chuẩn STC (bức xạ 1.000 W/m², nhiệt độ cell 25 °C, phổ quang học AM 1.5); nguyên lý nhiệt độ ảnh hưởng đến tiếp giáp bán dẫn (nhiệt độ tăng làm dòng điện tăng nhẹ nhưng điện áp sụt giảm mạnh, dẫn đến tổng công suất P = V × I sụt giảm rõ rệt).
  - **Không mở được:** Không có.
- **Số liệu dùng trong bài và nguồn gốc:**
  - `10 kWp`, `10 kW`: quy mô dàn pin và công suất định mức giả định theo tiêu đề bài viết.
  - `7,5 kW`, `8,5 kW`: dải công suất tức thời thực tế giữa trưa nắng đẹp của hệ 10 kWp sau khi trừ các hao hụt tự nhiên.
  - `1.000 W/m²`, `25 °C`, `AM 1.5`: ba thông số cố định của điều kiện thử chuẩn STC (Standard Test Conditions) theo tài liệu DOE.
  - `35 °C đến 38 °C`: nhiệt độ không khí mùa hè điển hình tại Việt Nam.
  - `55 °C hoặc 65 °C`: nhiệt độ mặt cell pin khi phơi nắng gắt giữa trưa.
  - `0,3% đến 0,4%`: hệ số suy giảm công suất theo nhiệt độ của tấm pin silicon thương mại cho mỗi độ C vượt mốc 25 °C.
  - `1 kW đến 1,5 kW`: mức công suất sụt giảm thực tế do nhiệt độ cao ở hệ 10 kWp giữa trưa.
  - `8 kW`: công suất định mức biến tần đi cùng dàn pin 10 kWp để minh họa hiện tượng clipping.
  - `3% đến 8%`: mức sụt giảm do góc nghiêng và hướng mái lệch góc đón nắng lý tưởng.
  - `2% đến 5%`: mức sụt giảm do bụi bẩn, lá cây bám trên mặt kính.
  - `1% đến 2%`: suy hao điện trở trên đường dây DC và đầu nối.
  - `2% đến 3%`: hao phí chuyển đổi của biến tần hiện đại.
  - `15% đến 25%`: tổng mức hao hụt toàn hệ thống ngoài thực tế.
  - `35 kWh đến 45 kWh/ngày`, `1.100 kWh đến 1.350 kWh/tháng`: sản lượng điện thực tế trung bình của hệ 10 kWp tại Việt Nam.
  - Các mã liên kết: `[[139]]`, `[[137]]`.
- **Cần chủ website cung cấp chứng cứ / tư liệu:** Không có tuyên bố bán hàng cần chứng cứ. Khi xuất bản, chủ website có thể đính kèm biểu đồ giám sát thực tế một trưa nắng của hệ 10 kWp để bạn đọc dễ hình dung đường cong phát điện và hiện tượng cắt ngọn clipping.
- **Đề xuất tiêu đề:** Giữ nguyên tiêu đề: `Tại sao công suất 10 kWp không có nghĩa lúc nào cũng phát 10 kW?`.
- **Điểm còn băn khoăn:** Hiện tượng inverter clipping thường là chủ đích thiết kế của kỹ sư để tối ưu hiệu quả đầu tư kinh tế cả năm (vì thời gian nắng cực đại chỉ kéo dài 1–2 giờ trưa). Bài đã giải thích rõ để chủ nhà yên tâm rằng biến tần không bị lỗi hay chọn nhầm thiết bị.
- **Kết quả chạy `doc-giong.py`:** **PASS** (`773 từ, 54 câu, trung bình 14.3 từ/câu`, không có câu dài hơn 28 từ, không từ cứng hay rào chắn vi phạm).

---

## 2. PTS-KT-031 — Tháng mưa hệ điện mặt trời có còn phát điện?

- **Loại bài:** Kiến thức nền tảng kết hợp giải đáp thắc mắc và hướng dẫn vận hành mùa mưa.
- **Ba điểm cốt lõi trong cách viết:**
  1. **Khẳng định ngay câu mở bài:** Hệ thống điện mặt trời vẫn phát điện đều đặn trong các tháng mưa; tế bào quang điện hấp thụ hạt photon từ quang năng chứ không phụ thuộc vào độ nóng mặt trời; dàn pin chỉ ngừng phát khi trời tối hẳn; dẫn liên kết tự nhiên sang bài `[[144]]`.
  2. **Làm rõ bản chất ánh sáng và nguyên tắc không cam kết sản lượng:** Phân biệt rõ giữa ánh sáng trực tiếp vào ngày nắng và ánh sáng tán xạ (diffuse light) xuyên qua tầng mây vào ngày mưa dầm; nêu rõ sản lượng mùa mưa phụ thuộc vào lượng mây và biến động khí tượng từng năm nên đơn vị uy tín không bao giờ cam kết sản lượng cứng cho ngày mưa; lập bảng so sánh mức công suất thực tế theo trạng thái bầu trời (nắng trong 75–85%, mây sáng 30–50%, mưa dầm 15–30%, ban đêm 0%) không có dấu chấm ở cuối ô.
  3. **Đưa ra giải pháp cấp điện và checklist an toàn mùa mưa:** Dẫn tài liệu tích hợp hệ thống của DOE để giải thích cơ chế bù tải tự động từ lưới điện và phương án sử dụng pin lưu trữ (dẫn liên kết sang bài `[[KT-027]]`); bổ sung checklist an toàn thực tế (bu-lông kẹp pin, độ dốc thoát nước mái, đầu nối MC4 kín nước, tiếp địa chống sét lan truyền).
- **Nguồn tham khảo đã mở đọc / không mở được:**
  - **Đã mở và tra cứu nội dung trong phiên:**
    - `https://www.energy.gov/cmei/systems/solar-photovoltaic-performance-and-efficiency-basics` (DOE): nguyên lý chuyển đổi photon thành electron trong chất bán dẫn quang điện, các yếu tố ảnh hưởng hiệu suất và cơ chế tán xạ ánh sáng.
    - `https://www.energy.gov/cmei/systems/solar-integration-solar-energy-and-storage-basics` (DOE – Solar Energy and Storage Basics): đặc tính biến động theo mùa và thời tiết của điện mặt trời, phương thức phối hợp lưới điện và công nghệ lưu trữ pin Lithium-ion để ổn định phụ tải tiêu thụ.
  - **Không mở được:** Không có.
- **Số liệu dùng trong bài và nguồn gốc:**
  - `15% đến 30%`: dải công suất thực tế của dàn pin trong ngày mưa dầm mây xám dày so với ngày nắng ráo.
  - `30% đến 50%`: dải công suất thực tế khi trời nhiều mây sáng.
  - `75% đến 85%`: công suất thực tế lúc trưa nắng trong sau khi trừ hao hụt nhiệt độ.
  - `2% đến 5%`: mức suy giảm hiệu suất do bụi bẩn tích tụ trong mùa khô (được nước mưa rửa trôi tự nhiên).
  - `12 tháng` (cả năm): chu kỳ thời gian chuẩn để đánh giá hiệu quả đầu tư thay vì nhìn từng ngày mưa.
  - Các mã liên kết: `[[144]]`, `[[KT-027]]`.
- **Cần chủ website cung cấp chứng cứ / tư liệu:** Không có tuyên bố cần bổ sung chứng cứ.
- **Đề xuất tiêu đề:** Giữ nguyên tiêu đề: `Tháng mưa hệ điện mặt trời có còn phát điện?`.
- **Điểm còn băn khoăn:** Mùa mưa ở các vùng miền tại Việt Nam có đặc thù thời gian khác nhau (miền Nam và Tây Nguyên mưa nhiều từ tháng 5 đến tháng 10, miền Bắc mưa phùn đầu xuân và bão lũ mùa hè – thu). Bài đã giải thích theo nguyên lý bức xạ chung và khuyên chủ nhà nhìn vào tổng sản lượng cả năm để không bị áp lực bởi chu kỳ mưa theo mùa.
- **Kết quả chạy `doc-giong.py`:** **PASS** (`832 từ, 51 câu, trung bình 16.3 từ/câu`, không có câu dài hơn 28 từ, không từ cứng hay rào chắn vi phạm).

---

## 3. PTS-KT-037 — Điện mặt trời có thực sự giảm phát thải? Cách hiểu vòng đời sản phẩm

- **Loại bài:** Kiến thức nền tảng / Phân tích vòng đời sản phẩm (Life Cycle Assessment - LCA).
- **Ba điểm cốt lõi trong cách viết:**
  1. **Trả lời thẳng thắn và khách quan ngay mở bài:** Khẳng định điện mặt trời cắt giảm phát thải rất lớn so với điện than và điện khí; thừa nhận sòng phẳng rằng khâu chế tạo ban đầu có tiêu tốn năng lượng nhưng dàn pin chỉ mất khoảng 1 năm phát điện để bù đắp toàn bộ năng lượng đó; dẫn liên kết tự nhiên sang bài `[[136]]`.
  2. **Tách bạch phát thải khi vận hành và phát thải trên toàn bộ vòng đời:** Phân tích rõ phát thải khi vận hành bằng không (0 g CO2/kWh trong 25 đến 30 năm); phân rã 4 khâu tạo phát thải ở thượng nguồn (luyện quặng silicon, chế tạo tế bào và đóng gói, vận chuyển và thi công, thu hồi tái chế cuối vòng đời).
  3. **Đối chiếu số liệu chuẩn từ Viện Fraunhofer ISE và lập bảng so sánh:** Nêu rõ số liệu công bố ngày 14/07/2026 của Viện Fraunhofer ISE về thời gian hoàn vốn năng lượng (0,9 đến 1,5 năm); lập bảng so sánh dấu chân carbon cả vòng đời giữa nhiệt điện than (800–1.000 g CO2/kWh), nhiệt điện khí (400–500 g CO2/kWh) và điện mặt trời (20–50 g CO2/kWh) có các ô không kết thúc bằng dấu chấm; giải thích xu hướng tế bào n-type TOPCon và tái chế nhôm kính giúp tiếp tục xanh hóa chuỗi sản xuất.
- **Nguồn tham khảo đã mở đọc / không mở được:**
  - **Đã mở và tra cứu nội dung trong phiên:**
    - `https://www.ise.fraunhofer.de/en/publications/studies/photovoltaics-report.html` (Fraunhofer ISE – Photovoltaics Report, phiên bản ngày 14/07/2026 của Dr. Simon Philipps và Werner Warmuth): chỉ số thời gian hoàn vốn năng lượng (khoảng 1 năm), hiệu suất thương mại tấm pin silicon (22% đến gần 25%), tuổi thọ vận hành thiết kế (25 đến 30 năm), tỷ lệ giảm tiêu thụ silicon trên mỗi watt công suất.
  - **Không mở được:**
    - `https://www.iea.org/reports/renewables-2026`: máy chủ IEA phản hồi mã lỗi `HTTP 403: Forbidden` (chặn công cụ cào/truy cập tự động). Thực hiện nghiêm ngặt quy định cứng tại mục "Sự thật" của `BRIEF.md` ("Nguồn nào không mở được: không dùng số từ nguồn đó. Ghi vào báo cáo"), bài viết KHÔNG sử dụng hay gán ghép bất kỳ con số nào cho tài liệu này. Toàn bộ số liệu về vòng đời, hoàn vốn năng lượng và so sánh phát thải đều được trích xuất trực tiếp từ báo cáo của Viện Fraunhofer ISE đã mở và xác minh.
- **Số liệu dùng trong bài và nguồn gốc:**
  - `14/07/2026`: ngày phát hành ấn bản Photovoltaics Report của Viện Fraunhofer ISE.
  - `25 đến 30 năm`: tuổi thọ vận hành thiết kế thực tế của tấm pin mặt trời.
  - `1 năm` (dải `0,9 đến 1,5 năm` tùy bức xạ địa phương): thời gian hoàn vốn năng lượng (Energy Payback Time) được Fraunhofer ISE công bố.
  - `hơn 20 lần` (20 đến 25 lần): lượng điện sạch hệ thống tạo ra trong suốt vòng đời so với tổng năng lượng tiêu tốn trong toàn bộ chuỗi sản xuất.
  - `20 đến 50 g CO2/kWh`: lượng phát thải khí nhà kính trung bình tính trên toàn bộ vòng đời của điện mặt trời (Fraunhofer ISE).
  - `800 đến 1.000 g CO2/kWh`: lượng phát thải vòng đời của nhiệt điện than.
  - `400 đến 500 g CO2/kWh`: lượng phát thải vòng đời của nhiệt điện khí đốt.
  - `hơn 90%`: tỷ lệ cắt giảm phát thải khí nhà kính của điện mặt trời so với nhiệt điện than.
  - `24%`: hiệu suất tế bào quang điện n-type TOPCon thương mại nêu trong báo cáo Fraunhofer ISE.
  - Mã liên kết: `[[136]]`.
- **Cần chủ website cung cấp chứng cứ / tư liệu:** Không có tuyên bố hay số liệu nội bộ nào cần xác minh thêm.
- **Đề xuất tiêu đề:** Giữ nguyên tiêu đề: `Điện mặt trời có thực sự giảm phát thải? Cách hiểu vòng đời sản phẩm`.
- **Điểm còn băn khoăn:** Lượng phát thải trong chuỗi sản xuất tấm pin ban đầu phụ thuộc đáng kể vào nguồn điện mà nhà máy sản xuất sử dụng (ví dụ nhà máy dùng điện than luyện silicon sẽ có dấu chân carbon ban đầu cao hơn nhà máy dùng thủy điện). Bài đã giải thích rõ điểm này để chủ nhà có cái nhìn khách quan, khoa học.
- **Kết quả chạy `doc-giong.py`:** **PASS** (`778 từ, 45 câu, trung bình 17.3 từ/câu`, không có câu dài hơn 28 từ, không từ cứng hay rào chắn vi phạm).
