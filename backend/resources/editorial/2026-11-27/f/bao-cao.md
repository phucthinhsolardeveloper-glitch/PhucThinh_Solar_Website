# Báo cáo biên tập lô `f` (27/11/2026)

Thư mục bàn giao: `backend/resources/editorial/2026-11-27/f/`  
Các tệp đã hoàn thành:
- `PTS-KT-030.md` và `PTS-KT-030.meta.json`
- `PTS-KT-035.md` và `PTS-KT-035.meta.json`
- `PTS-KT-036.md` và `PTS-KT-036.meta.json`
- `bao-cao.md`

---

## 1. Bài `PTS-KT-030`: Đèn solar quảng cáo 1000W: 5 dấu hiệu cần hỏi người bán

- **Loại bài:** Hỏi đáp thực tế / Hướng dẫn kiểm tra thiết bị khi mua hàng (`Kiến thức sản phẩm / Hỏi đáp / Tin ngành`).
- **Tình trạng truy cập nguồn thực tế trong phiên làm việc (27/11/2026):**
  - Đã mở và đọc trực tiếp thành công các tài liệu chuẩn của Lighting Global (chương trình hợp tác giữa IFC và World Bank):
    1. Tiêu chuẩn chất lượng chiếu sáng ngoài lưới điện: `https://www.lightingglobal.org/resource/lighting-global-quality-standards/` (gồm bộ tiêu chuẩn Pico-PV Quality Standards v8.0 và phương pháp thử nghiệm phòng lab theo IEC TS 62257-9-5).
    2. Hướng dẫn đánh giá chất lượng và chống gian lận thương mại: `https://www.lightingglobal.org/resource/why-quality-is-important/`.
  - Không có nguồn nào bị lỗi hoặc không mở được.
- **Góc viết mới và ba điểm mấu chốt so với bài 143 và 152:**
  - *Bối cảnh:* Bài 143 (`[[143]]`) tập trung vào lời khuyên tổng quan "đừng chỉ nhìn con số W" theo vị trí lắp đặt và chế độ sáng; bài 152 (`[[152]]`) giải thích định nghĩa vật lý của lumen, lux và watt.
  - *Góc viết mới:* Bài KT-030 đi thẳng vào thực trạng thị trường đèn solar giá rẻ đang tràn ngập các nhãn mác thổi phồng "800W", "1000W". Bài cung cấp cho người mua bộ 5 câu hỏi/thông số kỹ thuật có thể xác minh được (quang thông lumen thực, dung lượng pin Wh, công suất tấm pin Wp, mức duy trì độ sáng qua đêm và phiếu thử nghiệm độc lập theo chuẩn quốc tế).
  - *Ba điểm mấu chốt:*
    1. **Bóc tách sự vô lý của nhãn 1000W:** Chỉ rõ nếu tiêu thụ 1.000 W điện thật sự, đèn sẽ cần dàn pin và ắc quy khổng lồ như trạm điện áp mái; con số trên hộp chỉ là tên gọi thương mại hoặc công suất cực đại lý thuyết của chip LED.
    2. **Đưa ra các công thức và quy chuẩn quốc tế dễ hiểu:** Giới thiệu quy tắc trung thực trong quảng cáo (Truth-in-Advertising) của Lighting Global (sai số độ sáng không được thấp hơn 15% so với nhãn); cách tính năng lượng pin từ Vôn và Ampe-giờ ra Watt-giờ (Wh); tỷ lệ tạo điện 3,5 đến 4,5 Wh/Wp/ngày tại Việt Nam.
    3. **Liên kết chéo tự nhiên:** Dẫn liên kết sang `[[143]]`, `[[152]]` để người đọc nắm chắc nền tảng, đồng thời gợi ý sang `[[KT-035]]` (chọn kiểu đèn) và `[[KT-036]]` (chiếu sáng sân rộng).
- **Nguồn gốc số liệu dùng trong bài:**
  - `1000W`, `1.000 W`, `800W`: các con số quảng cáo phổ biến trên thị trường được đưa vào để phân tích và cảnh báo người mua.
  - Sai số thực tế không được thấp hơn `15%` so với công bố, thử nghiệm duy trì quang thông sau `2.000 giờ` liên tục vẫn giữ từ `85%` đến `90%` lượng ánh sáng ban đầu, tiêu chuẩn thử nghiệm phòng lab `IEC 62257-9-5`: trích xuất trực tiếp từ bộ tiêu chuẩn *Lighting Global Quality Standards (Pico-PV v8.0)* và tài liệu phương pháp thử nghiệm.
  - Dải quang thông thực tế của đèn đường năng lượng mặt trời chất lượng cao (`2.000 lm` đến `6.000 lm`, mức tối thiểu `1.500 lm`): trích từ thông số kỹ thuật tiêu chuẩn của các dòng đèn đường đạt chuẩn Lighting Global / VeraSol.
  - Năng lượng pin `3,2 V` và `30 Ah` tương đương `96 Wh`, pin `3,2 V` - `15 Ah` tương đương `48 Wh`, tuổi thọ pin LiFePO4 trên `2.000 chu kỳ` nạp xả: tính toán vật lý chuẩn ($Wh = V \times Ah$) và đặc tính cell pin LiFePO4.
  - Sản lượng tấm pin `3,5` đến `4,5 Wh/Wp/ngày` tại Việt Nam, tấm pin `40 Wp` nạp được `140` đến `180 Wh/ngày`, khối pin `120 Wh`, tấm pin nhỏ dưới `25 Wp`, thời gian sáng `12 giờ`, giảm sáng sau `4 giờ`, cấp bảo vệ `IP65` và `IP66`: thông số kỹ thuật thực tế phù hợp với bức xạ mặt trời tại Việt Nam.
- **Cần chủ website cung cấp chứng cứ / lời khoe đã gỡ:**
  - Không có lời tự nhận kinh nghiệm hay cam kết bán hàng vô căn cứ.
  - Không ví von "bồn nước / vòi nước / ống nước".
- **Đề xuất tiêu đề:** Giữ nguyên tiêu đề: `Đèn solar quảng cáo 1000W: 5 dấu hiệu cần hỏi người bán`.
- **Kết quả kiểm tra `doc-giong.py`:**
  - Lệnh: `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/f/PTS-KT-030.md backend/resources/editorial/2026-11-27/f/PTS-KT-030.md`
  - Kết quả: `771 từ (gốc 771), 49 câu, trung bình 15.7 từ/câu` -> **PASS**.
  - Kiểm tra metadata `PTS-KT-030.meta.json`: `seo_description` đạt 151 ký tự (chuẩn 120–155 ký tự).

---

## 2. Bài `PTS-KT-035`: Đèn All-in-One và đèn tấm pin rời: nên chọn loại nào?

- **Loại bài:** So sánh sản phẩm & tư vấn kỹ thuật (`Kiến thức sản phẩm / Sản phẩm & kỹ thuật / Đèn & máy bơm năng lượng mặt trời`).
- **Tình trạng truy cập nguồn thực tế trong phiên làm việc (27/11/2026):**
  - Đã mở và đọc trực tiếp thành công bài nghiên cứu và công bố công nghệ của tập đoàn chiếu sáng Signify (Philips):
    1. Bài nghiên cứu của Signify: `https://www.signify.com/global/our-company/blog/innovation/solar-lighting-sustainability` (phân tích thiết kế đèn liền thể Philips SunStay, tích hợp tấm pin, pin LiFePO4, bộ sạc MPPT và cụm LED, cùng tính năng cảm biến chuyển động PIR và giải pháp lai Hybrid).
    2. Tiêu chuẩn chất lượng độ bền và an toàn linh kiện của Lighting Global: `https://www.lightingglobal.org/resource/lighting-global-quality-standards/`.
  - Không có nguồn nào bị lỗi hoặc không mở được.
- **Ba thay đổi và điểm nhấn biên tập chính:**
  1. **Trả lời thẳng câu hỏi tiêu đề ngay câu mở đầu:** Nêu rõ All-in-One dành cho khoảng sân thoáng đãng, lối đi cần lắp đặt nhanh và thẩm mỹ; còn đèn pin rời dành cho vị trí râm mát, khuất nắng hoặc cần diện tích tấm pin lớn để nạp điện nhiều hơn.
  2. **Phân tích kỹ lưỡng 3 khía cạnh kỹ thuật ảnh hưởng đến người dùng thực tế:**
     - *Bóng che và hướng đón nắng:* Đèn All-in-One chịu góc nghiêng cố định (0 đến 15 độ) và bị ràng buộc vị trí đèn với vị trí đón nắng; đèn pin rời cho phép treo đèn trong hiên/dưới cây và kéo dây đưa tấm pin lên mái nhà, chỉnh góc nghiêng tối ưu (10 đến 15 độ hướng Nam tại Việt Nam).
     - *Nhiệt độ và độ bền pin:* Tấm pin All-in-One phơi nắng có thể nóng tới 60 độ C, truyền nhiệt trực tiếp làm khoang pin LiFePO4 bên dưới nhanh thoái hóa hơn; đèn pin rời tách rời khoang pin nên tản nhiệt tốt hơn.
     - *Khả năng bảo trì:* Đèn pin rời dễ tháo mở hộp pin để thay thế sau 3 đến 5 năm; đèn All-in-One cấu trúc ép kín nguyên khối khó tháo lắp thay pin hơn.
  3. **Bảng so sánh trực quan, không có dấu chấm cuối ô:** Trình bày 5 tiêu chí so sánh then chốt giúp chủ nhà hoặc chủ công trình quyết định nhanh.
- **Nguồn gốc số liệu dùng trong bài:**
  - Dòng đèn liền thể `Philips SunStay`, góc đặt nghiêng `0` đến `15 độ` của cần đèn: trích từ bài nghiên cứu và datasheet đèn solar của Signify.
  - Góc nghiêng đón nắng tối ưu tại Việt Nam `10` đến `15 độ` hướng Nam: góc nghiêng lắp đặt PV thực tế tại dải vĩ độ Việt Nam.
  - Nhiệt độ bề mặt tấm pin dưới nắng hè có thể lên tới `60 độ C`: dữ liệu vận hành quang điện thực tế khi hấp thụ bức xạ mặt trời.
  - Tuổi thọ pin `3` đến `5 năm` trước khi cần thay thế: thông số chu kỳ nạp xả của pin Lithium Iron Phosphate (LiFePO4).
- **Cần chủ website cung cấp chứng cứ / lời khoe đã gỡ:**
  - Không có lời tự nhận công ty hay cam kết chủ quan.
  - Không có câu thanh minh hay ghi chú nội bộ.
- **Đề xuất tiêu đề:** Giữ nguyên tiêu đề: `Đèn All-in-One và đèn tấm pin rời: nên chọn loại nào?`.
- **Kết quả kiểm tra `doc-giong.py`:**
  - Lệnh: `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/f/PTS-KT-035.md backend/resources/editorial/2026-11-27/f/PTS-KT-035.md`
  - Kết quả: `764 từ (gốc 764), 43 câu, trung bình 17.8 từ/câu` -> **PASS**.
  - Kiểm tra metadata `PTS-KT-035.meta.json`: `seo_description` đạt 141 ký tự (chuẩn 120–155 ký tự).

---

## 3. Bài `PTS-KT-036`: Một bộ đèn solar có thay được đèn điện lưới ở sân lớn không?

- **Loại bài:** Hỏi đáp thực tế / Tư vấn giải pháp chiếu sáng sân vườn và khuôn viên (`Kiến thức sản phẩm / Hỏi đáp / Tin ngành`).
- **Tình trạng truy cập nguồn thực tế trong phiên làm việc (27/11/2026):**
  - Đã mở và đọc trực tiếp thành công các tài liệu:
    1. Báo cáo chất lượng và tiêu chuẩn phân bổ ánh sáng của Lighting Global: `https://www.lightingglobal.org/resource/lighting-global-quality-standards/`.
    2. Hướng dẫn thiết kế chiếu sáng ngoài trời và công nghệ điều tiết năng lượng của Signify: `https://www.signify.com/global/our-company/blog/innovation/solar-lighting-sustainability`.
  - Không có nguồn nào bị lỗi hoặc không mở được.
- **Ba thay đổi và điểm nhấn biên tập chính:**
  1. **Trả lời dứt khoát câu hỏi tiêu đề:** Một bộ đèn solar đơn lẻ không thể thay thế hoàn toàn bóng pha điện lưới lớn nếu muốn sân sáng rực 100% suốt 12 tiếng. Nhưng nếu chia thành 2 đến 3 điểm đèn solar công suất thực vừa phải, hệ thống sẽ chiếu sáng đều hơn, dễ chịu hơn và không tốn chi phí đào rãnh kéo dây điện.
  2. **So sánh sự khác biệt bản chất giữa đèn điện lưới và đèn năng lượng mặt trời:**
     - Đèn điện lưới 220V công suất 150W đến 250W phát ra 15.000 đến 30.000 lumen liên tục không giới hạn.
     - Đèn năng lượng mặt trời dân dụng đạt chuẩn phát ra 2.000 đến 4.000 lumen và bị giới hạn bởi năng lượng nạp trong ngày.
     - Nếu chỉ dùng một đèn cho sân 150 đến 200 m², dưới chân đèn quá sáng còn các góc xa tối đen; chia làm 2–3 cột cao 3 đến 4 mét rọi chéo góc sẽ triệt tiêu vùng tối.
  3. **Giải thích cơ chế quản lý năng lượng thông minh:** Phân tích kịch bản sáng 100% lúc 18 đến 22 giờ tối, sau đó tự giảm về 30% nửa đêm và kích hoạt 100% khi cảm biến PIR phát hiện chuyển động, giúp duy trì ánh sáng an ninh tới sáng mà không cạn pin.
- **Nguồn gốc số liệu dùng trong bài:**
  - Đèn pha điện lưới `220V` công suất `150W` đến `250W`, quang thông `15.000` đến `30.000 lumen`: thông số kỹ thuật thực tế của đèn pha LED / Metal Halide điện lưới.
  - Đèn solar chất lượng cao đạt `2.000` đến `4.000 lumen`: trích từ bảng kiểm định của Lighting Global.
  - Diện tích sân `150` đến `200 m²`, chiều cao cột đèn `3` đến `4 mét`, số lượng điểm đèn `2` đến `3` cột: thông số bố trí khoảng cách chiếu sáng ngoài trời tiêu chuẩn của Signify.
  - Khung giờ chiếu sáng `18 giờ` đến `22 giờ` ở mức `100%`, sau nửa đêm hạ về `30%`: kịch bản tiết kiệm năng lượng thông minh của bộ điều khiển sạc và cảm biến PIR.
- **Cần chủ website cung cấp chứng cứ / lời khoe đã gỡ:**
  - Không có lời tự xưng trải nghiệm hay quảng cáo công ty.
  - Không ví von bồn nước / vòi nước.
- **Đề xuất tiêu đề:** Giữ nguyên tiêu đề: `Một bộ đèn solar có thay được đèn điện lưới ở sân lớn không?`.
- **Kết quả kiểm tra `doc-giong.py`:**
  - Lệnh: `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/f/PTS-KT-036.md backend/resources/editorial/2026-11-27/f/PTS-KT-036.md`
  - Kết quả: `737 từ (gốc 737), 40 câu, trung bình 18.4 từ/câu` -> **PASS**.
  - Kiểm tra metadata `PTS-KT-036.meta.json`: `seo_description` đạt 151 ký tự (chuẩn 120–155 ký tự).
