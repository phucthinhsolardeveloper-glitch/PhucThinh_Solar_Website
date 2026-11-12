# Audit chất lượng nội dung Kiến thức — Phúc Thịnh Solar
Ngày rà soát: 12/11/2026 · Phạm vi: PTS-KT-001..021 · Chỉ đánh giá, **chưa sửa nội dung đăng**.

## 1. Dữ liệu đã xem

- PTS-KT-001..003: nội dung thực tế của Post liên kết trong MariaDB local qua EditorialPlanItem (đang ẩn).
- PTS-KT-004..021: bản thảo Markdown tại `backend/resources/editorial/2026-11-12/articles/`; mô tả SEO tại `written-articles.json`; tiêu đề/ngày lịch tại `calendar-180.csv`.
- Số lượng 21 bài đã có nội dung; 18 Markdown lưu cùng đợt editorial, ba bài đầu đang ở DB local. Không giả định mọi bản nháp trùng tuyệt đối với dữ liệu từng thời điểm trên production.

### Định lượng (nội dung Markdown 004..021; đếm từ gần đúng)

| Nhóm | Bài | Bình quân từ/bài | Bình quân từ/đoạn | "cần" (6 bài) |
|---|---|---:|---:|---:|
| Đợt 1 | 004..009 | ~856 | ~39 | 44 |
| Đợt 2 | 010..015 | ~1.341 | ~67 | 69 |
| Đợt 3 | 016..021 | ~979 | ~73 | 45 |

Những con số này không tự động đồng nghĩa “chất lượng thấp”; chúng xác nhận bài dài, đoạn dài và lối diễn đạt chỉ dẫn/thận trọng rất thường xuyên. “Khó đọc” cần được kiểm chứng tiếp bằng người đọc thật.

## 2. Chẩn đoán biên tập

**Đúng kiến thức nhưng nhầm tầng độc giả.** Nhiều đoạn viết ở mức kỹ sư tư vấn cho kỹ sư: “giới hạn dòng mỗi MPPT”, “Voc ở nhiệt độ thấp”, “lớp oxide đường hầm”, “hệ số nhiệt độ Pmax”. Những chi tiết đó đúng chỗ trong trang tra cứu chuyên sâu, nhưng khiến một bài hỏi đơn giản trở thành giáo trình.

**Cấu trúc lặp.** Mở bằng “không nên chỉ dựa vào X” → giải thích biến số → nói “phụ thuộc điều kiện” → khuyến cáo xem datasheet/khảo sát → kết luận cùng ý ban đầu. Không phải bài nào cũng cần một bảng 6 tiêu chí, 5 bước quyết định và đoạn an toàn điện dài.

**Tư duy phòng lỗi lấn át câu trả lời.** Viết quá nhiều “không thể khẳng định”, “không mặc định”, “cần” làm cho tác giả có vẻ ngại trả lời câu hỏi chính. Phải giữ những cảnh báo an toàn quan trọng, nhưng gom vào 1 đoạn riêng và chỉ khi liên quan.

**Thiếu con người, nhịp kể và cảnh cụ thể.** Các bài về mái tôn ở Gia Lai, bơm tưới, quán cà phê, nhà dùng điều hòa buổi tối… có sẵn chất liệu gần người đọc nhưng chưa được dùng thành một câu chuyện rõ đầu-cuối. “Gia Lai, bụi đỏ, mưa lớn, giông sét” lặp như một mẫu câu khi không gắn trực tiếp với quyết định.

**Mô tả SEO giống đề cương học phần.** Nhiều mô tả mở đầu bằng “Hướng dẫn…”, “Phân biệt…”, “So sánh…”, dùng 4–6 danh từ chuyên môn thay vì trả lời lợi ích của độc giả. Không có nhiều lý do để bấm đọc.

**Trùng chủ đề nhưng chưa phân vai:** 008/017 cùng watt–lumen–lux; 013/016 cùng hóa đơn và phụ tải; 005/007/014 cùng inverter và hệ thống. Giữ được cả hai/ba nếu chúng phục vụ các ý định đọc khác nhau và dẫn liên kết qua lại.

## 3. Audit từng bài (Ưu tiên: Cao = cần biên tập đáng kể; Vừa = cải thiện cấu trúc/ví dụ; Nhẹ = chủ yếu nhịp văn và SEO)

| ID | Chủ đề | Độc giả/định dạng nên hướng đến | Vấn đề cụ thể | Ưu tiên |
|---|---|---|---|---|
| 001 | Điện mặt trời hoạt động ra sao | Người mới, 5 phút hiểu hệ thống | Đưa photon, DC/AC, MPPT và loại inverter quá gần phần mở; chuyển sang sơ đồ từ nắng đến thiết bị và 1 ví dụ gia đình | Vừa |
| 002 | Cách đọc datasheet LONGi | Người chuẩn bị mua / kỹ thuật viên, **chuyên sâu** | Đúng là bài kỹ thuật, không nên làm sơ sài; cần “đọc nhanh 5 chỉ số đầu tiên”, chú giải từng viết tắt và bảng mã máy | Vừa |
| 003 | Số liệu IEA PVPS 2026 | Tin mới nhất, dạng bản tin số liệu | Viết như tóm lược báo cáo; thiếu “vì sao người đọc Việt Nam quan tâm” ở mở bài và infographic trực quan | Vừa |
| 004 | kW/kWp/kWh | Người mới, bài giải thích nhanh | 7 ví dụ tốt, nhưng lời dẫn và kết dài; có thể rút, trình bày một khung so sánh 3 khái niệm ngay đầu | Nhẹ |
| 005 | Inverter làm gì | Người mới | MPPT, DC/AC, bảo vệ, thông số xuất hiện dày; mở bằng vai trò gần gũi, dành MPPT cho mục “muốn hiểu sâu” | Cao |
| 006 | Nhà dùng điện buổi tối | Hộ gia đình, tình huống thực tế | Câu hỏi rất tốt nhưng kết luận thận trọng; nên có ba lựa chọn thực tế và ví dụ giả định, dễ so | Vừa |
| 007 | Hòa lưới/hybrid/độc lập | Người chọn hệ, bài so sánh | Trùng nội dung với 005/014; tập trung bảng “nhu cầu → loại hệ”, lược phần thông số inverter | Vừa |
| 008 | Mua đèn solar | Người mua hàng, checklist thực dụng | Dùng lumen/lux/Wh nhiều; chuyển trọng tâm sang sân, lối đi, cổng, đường nội bộ và câu hỏi người bán; 017 sẽ chịu phần thuật ngữ | Vừa |
| 009 | Trời mây có phát điện? | Người quan tâm thời tiết Tây Nguyên | Có câu trả lời tốt; bỏ bớt MPPT, thêm một lịch theo dõi ứng dụng hoặc ảnh mây/nắng thực tế | Nhẹ |
| 010 | Mất điện dù có tấm pin | Người muốn dự phòng | Tình huống mở rất tốt, sau đó lại đậm inverter/EPS/UPS; dùng sơ đồ hai trường hợp trước, phần thuật ngữ xuống cuối | Vừa |
| 011 | Tấm pin 600–720W | Người chọn sản phẩm & kỹ thuật | Dài và dày module/MPPT/Voc; cần hình so kích thước thật, “720W có vừa mái nhà tôi không?” trước thông số | Cao |
| 012 | Dự báo IEA Renewables 2026 | Tin mới nhất, bản tin giải thích | Giọng phân tích phương pháp báo cáo; cần 3 con số/điểm mới được xác minh, tác động thực tế và biểu đồ dễ hiểu | Cao |
| 013 | Hóa đơn điện & quy mô solar | Hộ gia đình, “có nên lắp?” | Trùng khá nhiều với 016. Chuyển thành 3 hồ sơ gia đình có cùng kWh nhưng quyết định khác nhau | Cao |
| 014 | Inverter hòa lưới vs hybrid | Người đang so hai lựa chọn thiết bị | Nặng về model/tải/MPPT; nên có bảng “có cần pin và điện khi cúp không?”; dẫn người muốn biết inverter là gì về 005 | Cao |
| 015 | Pin đen hay xanh | Người mua thích so thẩm mỹ | Quá sâu vào cell/quang học/vật liệu; trả lời nhanh bằng ảnh thật 2 model, 5 tiêu chí mua, thuật ngữ để khung chuyên sâu | Vừa |
| 016 | Đọc hóa đơn | Người chuẩn bị nhận tư vấn, hướng dẫn từng bước | Trùng 013; nên thành bài thực hành: lấy hóa đơn, đánh dấu 4 ô, điền bảng 12 tháng, biết cần gửi gì | Cao |
| 017 | Watt, lumen, lux | Người chọn đèn, học khái niệm | Trùng 008; giữ 017 là bài giáo dục ba đơn vị với ảnh đo độ rọi/ví dụ sân 20m², dẫn sang checklist mua 008 | Vừa |
| 018 | Nhiều tấm có hoàn vốn nhanh? | Chủ nhà/xưởng, kinh tế | “tính theo biên”, “phụ tải”, “DC/AC” làm câu chuyện tài chính khó theo; thay bằng bài toán hai cấu hình, hai hóa đơn giả định | Vừa |
| 019 | Mái tôn/ngói/bằng | Người khảo sát lắp đặt | Chủ đề rất phù hợp; đổi từ danh sách kỹ thuật thành 3 nhóm ảnh mái + dấu hiệu cần kiểm tra, giữ cảnh báo an toàn riêng | Vừa |
| 020 | TOPCon/HJT/BC | Người mua tìm hiểu sâu | **Bản chất là bài chuyên sâu**, được phép có thuật ngữ nhưng cần nhãn đối tượng, phần “khách hàng cần nhớ 3 điều”, sơ đồ cell và glossary | Cao (để đúng tầng) |
| 021 | Solar làm mái nóng/mát | Chủ nhà, bài khoa học đời sống | Khởi đầu “tùy…” thiếu hấp dẫn, giải thích ba đường nhiệt khá khô; thay bằng câu trả lời nhanh + hai trường hợp có thông gió và bí gió | Vừa |

## 4. Phân vai những bài đang gần trùng nhau

- **008** là checklist *mua đèn phù hợp nhu cầu*, 4–6 câu hỏi cho người bán; **017** là bài *hiểu watt/lumen/lux* với ví dụ trực quan. Đặt liên kết qua lại, không lặp nguyên 70% định nghĩa.
- **013** là bài *nhà dùng bao nhiêu kWh thì nên cân nhắc lắp điện mặt trời*, dùng 3 chân dung khách hàng; **016** là *tự đọc 12 hóa đơn và chuẩn bị khảo sát*, dùng bảng mẫu có thể tải.
- **005** là *inverter làm nhiệm vụ gì*; **007** là *3 loại hệ thống cho 3 nhu cầu*; **014** là *khi nào chọn inverter hybrid thay cho hòa lưới*; một câu chuyện mỗi bài, không tự dạy lại toàn bộ kiến thức từ đầu.
- **003/012** là tin ngành, không nên có cùng công thức kết bài “hãy hỏi người tư vấn”. Tin mới nhất cần dữ kiện đúng thời điểm, cái mới, ai bị ảnh hưởng, liên hệ Việt Nam ở mức có bằng chứng.

## 5. Quy chuẩn giọng văn đề xuất

**Phúc Thịnh Solar:** hiểu nghề, nói chuyện dễ hiểu, có dẫn chứng, tư vấn không ép mua. Không dùng từ ngữ quảng cáo phóng đại; cũng không dùng ngôn ngữ quy trình kỹ thuật ở mọi câu.

1. **Mở bằng câu trả lời hoặc tình huống.** Ví dụ: “Hai gia đình đều dùng 800 kWh/tháng nhưng có thể cần hai hệ điện mặt trời rất khác nhau”, sau đó mới phân tích ban ngày/ban đêm. Không bắt đầu bằng danh mục định nghĩa dài.
2. **Một bài = một câu hỏi chính.** Mọi đoạn phải góp phần trả lời câu hỏi đó, phần kỹ thuật nâng cao có thể là mục “Nếu bạn muốn tìm hiểu sâu”.
3. **Giải nghĩa ngay lần đầu.** “Phụ tải” → “các thiết bị đang dùng điện”; “module” → “tấm pin”; “datasheet” → “bảng thông số hãng”; “MPPT” chỉ cần xuất hiện nếu thực sự giải thích một giới hạn quan trọng.
4. **Có một ví dụ đời thường có số liệu minh họa** nếu đề tài là tiền điện, giờ dùng, ánh sáng, công suất. Đánh dấu rõ giả định, tránh biến ví dụ thành cam kết.
5. **Dùng câu ngắn và đoạn 2–4 câu.** Không tăng độ dài để đủ 1.000–1.500 từ; bài cơ bản 600–900 từ cũng tốt nếu giải quyết xong câu hỏi.
6. **Thận trọng đúng chỗ.** Giữ số liệu, điều kiện giả định, quy chuẩn, cảnh báo an toàn thực sự liên quan; chuyển chú giải kỹ thuật dài xuống sau, không lặp lại khắp bài.
7. **CTA theo nhu cầu**, không bắt buộc luôn “Phúc Thịnh Solar khuyến khích… khảo sát…”. Bài về hóa đơn: gửi 12 tháng tiêu thụ; bài về đèn: chụp khu vực lắp; bài về TOPCon: đối chiếu 2 datasheet; tin IEA có thể không cần CTA bán hàng.
8. **Bài chuyên sâu có nhãn:** “Dành cho người đang so sánh thiết bị / kỹ thuật viên”, nhưng vẫn có tóm tắt dễ hiểu ở đầu.
9. **Ảnh theo bài:** tấm pin thật, mái thực tế, infographic điện/nắng, biểu đồ đơn giản; ảnh phải giúp người đọc *hiểu vấn đề* chứ không chỉ làm đẹp bìa. Giữ credit và quyền sử dụng riêng.

### Độ dài theo mục đích (định hướng, không phải quota)

- Giải đáp nhanh: 500–800 từ.
- Hướng dẫn lựa chọn: 700–1.100 từ.
- So sánh nhiều phương án / bài có số liệu: 800–1.300 từ.
- Chuyên sâu: 1.000–1.600 từ nếu thật sự cần.

## 6. Ba ví dụ cụ thể: từ đề cương kỹ thuật sang lời người đọc

**PTS-KT-016, mô tả hiện tại:** “Hướng dẫn đọc hóa đơn điện theo kWh, số ngày và thời điểm dùng điện để đặt mục tiêu thiết kế solar, kiểm tra mái và đấu nối an toàn tại Gia Lai.”

**Đề xuất:** “Nhà bạn dùng bao nhiêu điện mỗi tháng, nhiều vào ban ngày hay buổi tối? Chỉ với vài thông tin trên hóa đơn, bạn đã có thể chuẩn bị tốt hơn trước khi lắp điện mặt trời.”

**PTS-KT-011, mô tả hiện tại:** “So sánh tấm pin 600W, 620W, 650W và 720W theo kích thước, hiệu suất, giới hạn điện áp, mái nhà và inverter.”

**Đề xuất:** “Tấm pin 720W có chắc tốt hơn 620W? Không hẳn. Xem vì sao diện tích mái, kích thước từng tấm và bộ biến tần mới quyết định lựa chọn phù hợp.”

**PTS-KT-020, mô tả hiện tại:** “Giải thích TOPCon, HJT và BC theo kiến trúc cell, ưu nhược điểm và cách so datasheet, hệ số nhiệt, bảo hành, inverter thay vì chạy theo tên công nghệ.”

**Đề xuất:** “TOPCon, HJT và BC xuất hiện ngày càng nhiều trên bảng báo giá tấm pin. Chúng khác nhau ở đâu, và người mua thực sự cần quan tâm thông số nào?”

## 7. Quy trình biên tập lại, tránh sửa hàng loạt thành một công thức mới

1. **P0:** Sửa mô tả SEO và mở bài 21 bài (giữ đúng dữ kiện, nguồn, đường dẫn).
2. **P1:** Viết lại sâu 005, 011, 012, 013, 014, 016, 020; kiểm soát trùng nội dung trong các cặp 008/017, 005/007/014 và 013/016.
3. **P2:** Biên tập nhịp văn, ví dụ, tiêu đề phụ và CTA các bài còn lại; giữ lại những đoạn chuyên môn hữu ích cho người muốn nghiên cứu sâu.
4. QA hai vai độc giả: người chưa biết điện mặt trời có thể kể lại được 3 ý chính? Một nhân viên kỹ thuật có thấy số liệu/khuyến cáo sai hoặc thiếu điều kiện?
5. Kiểm tra SEO, nguồn, quyền ảnh, product links, preview, cặp nội dung trùng; **không đổi slug/ngày xuất bản/trạng thái ẩn** trong quá trình biên tập.
6. So sánh diff bài trước/sau, duyệt từng nhóm trước khi chạy importer cho bản nháp local. Không tự publish hoặc deploy.

### Nguyên tắc chốt

Không nên “bình dân hóa” toàn bộ bài bằng cách xóa thông số kỹ thuật. Cần **đặt thông số đúng vị trí, đúng độc giả**. Website của doanh nghiệp điện mặt trời có quyền nói sâu; nhưng một người đọc đang băn khoăn về hóa đơn, mái nhà hay giá thiết bị phải hiểu câu trả lời chính trong 30–60 giây đầu.
