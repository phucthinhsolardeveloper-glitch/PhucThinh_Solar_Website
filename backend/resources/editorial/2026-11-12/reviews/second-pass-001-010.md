# Second-pass deep editorial review — PTS-KT-001–010

Ngày rà soát: 12/11/2026. Phạm vi thân bài chỉ gồm 001–010; metadata nằm riêng trong `second-proposals-001-010.json`. Không sửa `written-articles.json`, DB, slug, ngày, status, product association hay đường dẫn media.

## Quyết định source-of-truth

001–003 đã được kiểm tra lại trực tiếp trên Markdown hiện hành. Cả ba đạt về góc độc giả và cấu trúc; không lặp khái niệm đến mức gây nhiễu, nên **giữ vì đạt**. 004–010 đã viết lại theo góc riêng, giữ title canonical, ảnh và link nguồn hiện hữu.

## Nhật ký theo bài

| Bài | Góc độc giả và dạng trình bày | Before → after (trích đoạn) | Claim/QA và lợi ích |
|---|---|---|---|
| 001 | Người mới; hành trình một kWh + bảng bốn chặng | “Buổi trưa, mái nhà nhận ánh nắng…” → giữ, vì đi thẳng vào dòng điện; chỉnh nhẹ câu ví dụ để bớt mệnh lệnh | Giữ nguyên ảnh `/upload/news/pts-diagram-solar-workflow.webp` và 2 link DOE. Không lặp khái niệm: phần pin, lịch tải, mây/pin và chống đảo lưới có vai trò riêng. Cần fact-check ảnh Pexels/licence trước publish. |
| 002 | Người kỹ thuật; quy trình đọc datasheet và phép kiểm chuỗi | “Hai tấm pin cùng ghi…” → giữ, đổi sang giọng ghi chú kỹ thuật; bỏ “Bài này dùng” | Đã đối chiếu LONGi BGV02: 650 W, 24,1%, NOCT 495 W, Voc 53,90 V, Vmp 44,56 V, Isc 15,29 A, Imp 14,59 A, -0,260%/°C, 2.382 × 1.134 × 30 mm, 28,5 kg đều khớp nguồn chính thức. Vẫn cần xác minh datasheet đúng lô hàng và quyền dùng infographic/ảnh. |
| 003 | Độc giả tin ngành; bản tin theo mốc thời gian + bảng “không nên suy ra” | “Trong bản Trends…” → giọng bản tin rõ thời điểm, tách Trends và Snapshot | IEA PVPS xác nhận 2,96 TW, khoảng 690 GW, tăng 15%, Trung Quốc 415 GW, Ấn Độ 54, Mỹ 43, Đức 18, Pakistan 14 và khoảng 36 quốc gia trên 1 GW. Cần fact-check credit/giấy phép infographic; giữ mốc Snapshot 698 GW như số sơ bộ, không trộn hai báo cáo. |
| 004 | Người đọc báo giá; bảng đơn vị + bảy tình huống | “Ba ký hiệu này cùng xuất hiện…” → “Muốn đọc một báo giá, chỉ cần tách ba câu hỏi…” | Giữ title, 1 link DOE; bỏ nhịp giáo trình và biến công thức thành phép đọc hóa đơn. Ví dụ 2 kW × 3 giờ được ghi rõ là giả định. Người đọc có bốn dòng cần tách khi so báo giá. |
| 005 | Người chọn thiết bị; bảng tình huống mái và câu hỏi trước khi chốt model | “Phần quan trọng nhất của inverter…” → “Nếu tấm pin là nơi tạo DC…” | Giữ title, nguồn Fronius/DOE; không lặp bài 007 về loại hệ. MPPT, giới hạn DC và anti-islanding vẫn được giải thích. Người đọc biết hỏi đúng dải điện áp, dòng vào, backup và tải khởi động. |
| 006 | Hộ gia đình; bài tập hóa đơn 15 phút | “Có thể, nhưng hệ hòa lưới…” → “Một gia đình có thể dùng 600 kWh/tháng…” | Giữ title, 3 link DOE; ví dụ 600 kWh được gắn nhãn minh họa, không hứa hoàn vốn. Người đọc tách mục tiêu tiết kiệm khỏi mục tiêu backup và có cách ghi lịch tải. |
| 007 | Quyết định hệ; bảng so sánh ba loại và tình huống mất điện | “Hãy bắt đầu từ câu hỏi…” → “Chọn theo lúc cần điện…” | Giữ title, 2 link DOE; không dạy lại toàn bộ inverter 005. Anti-islanding và giới hạn off-grid vẫn đủ. Người đọc có bốn câu hỏi để đem đi khảo sát. |
| 008 | Người mua đèn; phiếu mua hàng theo vị trí + sáu câu hỏi | “Một chiếc đèn hợp sân trước…” → “Trước khi so watt, hãy chụp lại nơi đèn sẽ chiếu…” | Giữ title, ảnh không có trong bản hiện hành và 2 nguồn Lighting Global/Signify; giữ intent checklist mua. Không biến lumen/lux thành phần giải thích dài của 017. Người đọc biết yêu cầu điều kiện của claim “12 giờ”. |
| 009 | Chủ hệ mùa mưa; nhật ký 14 ngày và ba lớp quan sát | “Có. Tấm pin vẫn có thể…” → “Có, nhưng đừng dùng một tỷ lệ cố định…” | Giữ title, 2 link DOE/European Commission; giữ ví dụ Gia Lai/Tây Nguyên, không gán tỷ lệ giảm cố định. Người đọc biết phân biệt mây, chuỗi ngày và dấu hiệu kỹ thuật. |
| 010 | Người đang cúp điện; hai sơ đồ vận hành + phân biệt kW/kWh | “Sáng chủ nhật…” → giữ tình huống mở đầu, thêm “Hai sơ đồ, một nguyên tắc an toàn” | Giữ ảnh và 3 nguồn DOE/NREL; anti-islanding được giữ nguyên, không làm yếu cảnh báo. Người đọc biết tải backup không đồng nghĩa toàn nhà có điện và hỏi được công suất lẫn thời gian duy trì. |

## Tự phản biện năm cliché đã xử lý

1. Mở bài “Trong bối cảnh ngày càng…”: bỏ hoàn toàn; các bài mở bằng một quyết định hoặc cảnh cụ thể.
2. Chuỗi “định nghĩa → 5 điểm → kết luận”: thay bằng bảng đơn vị (004), datasheet (002), bản tin theo mốc (003), nhật ký (009).
3. Lặp “hãy/cần/không nên”: rút câu mệnh lệnh, chỉ giữ cảnh báo an toàn hoặc bước kiểm có ích; rà lại còn một số câu hướng dẫn cần thiết.
4. Ví dụ địa phương hóa máy móc: 009 chỉ giữ Gia Lai/Tây Nguyên vì gắn với nhịp mưa–khô thật; không rải “bụi đỏ/giông sét” vào các bài khác.
5. Kết bài CTA công ty: bỏ lời mời khảo sát chung; kết thúc bằng phép kiểm, câu hỏi báo giá hoặc ngưỡng an toàn phù hợp từng bài.

## Chấm QA vòng này

001: `pass_editorial`, chờ kiểm tra licence ảnh Pexels và đối chiếu HTML-to-Markdown CMS trước sync.

002: `pass_editorial`, specs khớp LONGi BGV02; vẫn cần xác minh đúng lô hàng, phiên bản datasheet và licence ảnh/infographic.

003: `pass_editorial`, giọng bản tin và thống kê khớp IEA PVPS Trends 2026; vẫn cần xác minh licence infographic và giữ rõ phân biệt Trends 690 GW với Snapshot sơ bộ 698 GW.

004–010: `needs_revision` cho đến khi owner duyệt vòng copy và kiểm tra claim theo model/điều kiện thực tế; không phát hiện thay đổi URL, ảnh, slug hay trạng thái. Riêng 010 phải giữ nguyên yêu cầu anti-islanding trong mọi lần chỉnh sau.

Rollback point: hoàn nguyên các file 004–010 và hai file review/proposals về commit trước task; không có DB/production write, cleanup hay backup bị tác động.
