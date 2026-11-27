# Báo cáo biên tập: PTS-KT-022 và PTS-KT-023 (27/11/2026)

## 1. PTS-KT-022 — Mái hướng Đông – Tây có lắp điện mặt trời hiệu quả không?

- **Loại bài:** Giải thích khái niệm kết hợp hướng dẫn khảo sát thực tế.
- **Ba thay đổi chính so với bản nháp cũ:**
  1. **Gỡ toàn bộ chi tiết địa phương cụ thể (Pleiku) và ghi chú biên tập/ảnh concept khỏi thân bài:** Thay vì nhắc đi nhắc lại Pleiku hay thanh minh về ảnh bìa concept như bản nháp cũ, bài giải thích nguyên lý chung rằng bức xạ thay đổi theo vĩ độ và tọa độ từng ngôi nhà tại Việt Nam, khác với các con số ước tính truyền miệng từ châu Âu.
  2. **Đổi giọng từ ra lệnh và rào chắn sang trò chuyện, giải thích thẳng:** Mở bài trả lời trực tiếp câu hỏi tiêu đề; thêm bảng so sánh trực quan giữa mái một hướng nắng trưa và mái hai mặt Đông – Tây theo khung giờ phát điện và lịch sinh hoạt; gom toàn bộ câu dặn dò rải rác thành một mục lời khuyên gọn ở cuối bài.
  3. **Liên kết tự nhiên sang bài KT-023:** Ở mục giải thích cách đấu hai mặt mái lệch nắng vào hai ngõ MPPT độc lập của inverter, bài dẫn tự nhiên sang `[[KT-023]]` để người đọc xem tiếp nguyên lý và cách tính chuỗi.
- **Nguồn tham khảo đã mở đọc / không mở được:**
  - **Đã mở và tra cứu nội dung:**
    - `https://www.energy.gov/cmei/systems/solar-rooftop-potential` (U.S. Department of Energy – Solar Rooftop Potential): bốn yếu tố quyết định tiềm năng điện mặt trời của một mái nhà gồm vị trí địa lý, hướng và độ nghiêng mái, diện tích trống liền mạch không vướng vật cản, và mức độ bóng che.
    - `https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics` (U.S. Department of Energy – Solar Photovoltaic System Design Basics): cấu tạo hệ thống điện mặt trời gồm dàn tấm pin, khung giá đỡ cố định theo hướng/góc nghiêng, inverter đổi điện DC sang AC và pin lưu trữ tùy chọn cho ban đêm hoặc khi mất lưới.
    - `https://joint-research-centre.ec.europa.eu/photovoltaic-geographical-information-system-pvgis_en` (European Commission Joint Research Centre – PVGIS): công cụ tính bức xạ và ước tính sản lượng điện mặt trời theo tọa độ địa lý, độ dốc và góc hướng mái.
    - `https://blog.fronius.com/solar-energy/en/know-how/solar-panels-orientation/` (Fronius – What is the best orientation for solar panels?): đặc tính đường phát điện trải rộng từ sáng đến chiều của hệ mái Đông – Tây giúp tăng tỷ lệ tự dùng ban ngày so với đỉnh nhọn buổi trưa.
  - **Không mở được:** Không có (tất cả nguồn chính và phụ trong `plan-rows.csv` và bản nháp đều tra cứu được).
- **Số liệu dùng trong bài và nguồn gốc:**
  - `15 giờ` (ví dụ mặt mái hướng Tây bị nhà cao tầng che nắng buổi chiều — giữ từ ví dụ tình huống của bản nháp cũ).
  - `20 giờ` (ví dụ cửa hàng mở cửa từ sáng đến tối tận dụng nắng cả hai sườn mái Đông và Tây — giữ từ ví dụ tình huống của bản nháp cũ).
  - `12 tháng` (hóa đơn tiền điện 12 tháng gần nhất để xem lịch dùng điện — giữ từ bản nháp cũ).
  - Các số thứ tự đánh mục `1, 2, 3, 4, 5, 6` trong phần checklist của bản nháp cũ đã được lược bỏ vì chuyển sang danh sách gạch đầu dòng tự nhiên; mã `[[KT-023]]` xuất hiện theo yêu cầu gắn liên kết chéo giữa hai bài.
- **Cần chủ website cung cấp chứng cứ / tư liệu:**
  - Bài không đưa ra lời khoe định lượng hay cam kết sản lượng của công ty. Khi đăng bài, chủ website có thể bổ sung ảnh chụp thực tế một mái nhà lắp hai hướng Đông – Tây tại Việt Nam để minh họa trực quan.
- **Đề xuất tiêu đề:** Giữ nguyên tiêu đề `Mái hướng Đông – Tây có lắp điện mặt trời hiệu quả không?`.
- **Điểm còn băn khoăn:** Bài giữ nguyên tắc không nêu một tỷ lệ phần trăm hao hụt cứng giữa mái Đông – Tây và mái hướng Nam vì tỷ lệ này thay đổi theo vĩ độ từng tỉnh thành và độ dốc mái thực tế.
- **Kết quả chạy `doc-giong.py`:**
  - Chạy `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/a/PTS-KT-022.md backend/resources/editorial/2026-11-27/a/PTS-KT-022.md`: **PASS** (`784 từ, 51 câu, trung bình 15.3 từ/câu`).
  - Khi chạy đối chiếu với bản nháp cũ `input/PTS-KT-022.md`, công cụ chỉ ghi nhận mã liên kết `023` (từ `[[KT-023]]`) và việc bỏ số thứ tự danh sách `1..6`, không có lỗi giọng văn, câu dài hay từ cứng.

---

## 2. PTS-KT-023 — MPPT là gì? Vì sao quan trọng khi mái có nhiều hướng?

- **Loại bài:** Giải thích khái niệm kỹ thuật kết hợp hướng dẫn kiểm tra thiết kế.
- **Ba thay đổi chính so với bản nháp cũ:**
  1. **Giải thích thuật ngữ bằng hình ảnh quen thuộc và lập bảng thông số rõ ràng:** Mở bài trả lời thẳng chức năng của MPPT và ví với hộp số tự động trên xe; tách đoạn dồn bốn ký hiệu kỹ thuật (`Voc`, `Vmp`, `Isc`, `Imp`) thành bảng 3 cột (`Ký hiệu | Nghĩa trên bảng thông số | Vì sao bạn cần quan tâm`) đúng chuẩn sổ tay giọng văn.
  2. **Bỏ toàn bộ câu thanh minh về ví dụ và ghi chú thiếu infographic:** Ở ví dụ giả định mái nhà lắp `8` tấm hướng Đông và `8` tấm hướng Tây, bài đi thẳng vào cách nhân điện áp để kiểm tra ngưỡng khởi động của ngõ MPPT, bỏ hẳn câu rào chắn *"Ví dụ minh họa, không phải cấu hình thi công"* và *"Con số 8 chỉ là ví dụ..."*.
  3. **Giải thích rõ mối liên hệ giữa bóng che, đi-ốt bảo vệ (bypass diode) và tụt áp chuỗi, đồng thời liên kết tự nhiên về bài KT-022:** Nêu rõ cơ chế khi đi-ốt bảo vệ mở lối qua phần tấm pin bị che làm điện áp chuỗi sụt từng nấc, giải thích vì sao chuỗi quá ngắn dễ tụt dưới ngưỡng dò của inverter; gắn liên kết `[[KT-022]]` tự nhiên ở đoạn nói về mái nhà chia hai hướng Đông – Tây.
- **Nguồn tham khảo đã mở đọc / không mở được:**
  - **Đã mở và tra cứu nội dung:**
    - `https://www.energy.gov/cmei/systems/solar-photovoltaic-system-design-basics` (U.S. Department of Energy – Solar Photovoltaic System Design Basics): vai trò của inverter và việc phối hợp các thành phần trong hệ thống điện mặt trời.
    - `https://www.energy.gov/cmei/systems/solar-photovoltaic-technology-basics` (U.S. Department of Energy – Solar Photovoltaic Technology Basics): nguyên lý tế bào quang điện (solar cell) ghép nối tiếp thành tấm pin (module) và chuỗi/dàn pin (array) tạo dòng điện một chiều DC.
    - `https://www.cleanenergyreviews.info/blog/solar-hybrid-battery-ready-inverters` (Clean Energy Reviews – Hybrid & Battery-Ready Solar Inverters cùng tài liệu kỹ thuật của Clean Energy Reviews về MPPT string design & shading): dải điện áp làm việc của các ngõ MPPT, yêu cầu tách ngõ MPPT cho các hướng mái khác nhau, ảnh hưởng của nhiệt độ lên `Voc`/`Vmp`, và hiện tượng sụt áp chuỗi khi bypass diode kích hoạt do bóng râm.
    - `https://blog.fronius.com/solar-energy/en/know-how/solar-panels-orientation/` (Fronius – What is the best orientation for solar panels?): cách bố trí dàn pin nhiều hướng mái và theo dõi điểm công suất cực đại.
  - **Không mở được:** Không có.
- **Số liệu dùng trong bài và nguồn gốc:**
  - `8` (ví dụ giả định `8` tấm pin bên hướng Đông và `8` tấm bên hướng Tây — giữ nguyên con số ví dụ từ bản nháp cũ `input/PTS-KT-023.md`, có từ "Giả sử..." ở đầu câu).
  - Các số thứ tự `1, 2, 3, 4` của danh sách bước kiểm tra trong bản nháp cũ đã chuyển sang danh sách gạch đầu dòng; mã `[[KT-022]]` xuất hiện theo yêu cầu gắn liên kết chéo giữa hai bài.
- **Cần chủ website cung cấp chứng cứ / tư liệu:**
  - Bài không chứa tuyên bố bán hàng cần chứng cứ thêm. Khi đăng bài, đội kỹ thuật có thể bổ sung một sơ đồ nguyên lý đơn giản minh họa 2 chuỗi tấm pin Đông – Tây đấu vào 2 ngõ MPPT độc lập của inverter.
- **Đề xuất tiêu đề:** Giữ nguyên tiêu đề `MPPT là gì? Vì sao quan trọng khi mái có nhiều hướng?`.
- **Điểm còn băn khoăn:** Mỗi dòng inverter dân dụng (1 pha, 3 pha, áp thấp hay áp cao) có ngưỡng điện áp khởi động và dải điện áp MPPT khác nhau, nên bài hướng dẫn người đọc cách nhân điện áp tấm pin để so với datasheet thay vì đóng khung một con số điện áp cố định.
- **Kết quả chạy `doc-giong.py`:**
  - Chạy `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/a/PTS-KT-023.md backend/resources/editorial/2026-11-27/a/PTS-KT-023.md`: **PASS** (`894 từ, 56 câu, trung bình 15.9 từ/câu`).
  - Khi chạy đối chiếu với bản nháp cũ `input/PTS-KT-023.md`, công cụ chỉ ghi nhận mã liên kết `022` (từ `[[KT-022]]`) và việc bỏ số thứ tự danh sách `1..4`, độ dài bài mới bằng đúng bản nháp cũ (`894` từ) và không có lỗi giọng văn.
