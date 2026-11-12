# Second-pass editorial QA — PTS-KT-011–021

Vòng hai đọc như một biên tập viên hiểu quyết định PV, không chỉ kiểm tra từ khóa. Mười một bản nháp vẫn giữ nguyên đường dẫn ảnh, credit inline, nguồn tham khảo, slug/ngày/lịch và trạng thái ẩn; không có DB write, sync, deploy hay thay đổi metadata dùng chung.

## PTS-KT-011

- **Vai đọc/việc cần làm:** chủ nhà có mái bị chia muốn biết tấm 600–720 W nào thật sự vừa mái trước khi so model.
- **Before → after:** phần mở cũ dễ trôi vào so watt; bản này đặt bản vẽ mái và kích thước đúng model lên trước, rồi mới đến hiệu suất, chi phí và Voc/MPPT.
- **Góc/format mới:** giải thích quyết định theo thứ tự “mái → hệ thống → điện áp”, kèm bảng hỏi báo giá ngắn.
- **Source doubt:** không có model cụ thể nên không thêm kích thước hay số liệu hãng; DOE/Fraunhofer/PVGIS chỉ hỗ trợ nguyên tắc, cần datasheet khi tư vấn.
- **QA:** 5/5 dễ hiểu, 5/5 hữu ích, 4/5 tự nhiên, 4/5 nguồn, 5/5 khác biệt/bố cục; **28/30**.

## PTS-KT-012

- **Vai đọc/việc cần làm:** người đọc tin ngành cần phân biệt số đã quan sát với dự báo IEA và hiểu vì sao lưới quan trọng.
- **Before → after:** bản này bỏ nhịp giáo trình, thành bản tin ngày 6/11/2026 với ba phát hiện, ba mốc và đoạn diễn giải phạm vi toàn cầu; thêm nhắc không trộn GW với TWh.
- **Góc/format mới:** news brief có bảng mốc, không CTA thương mại.
- **Source doubt:** phải fact-check trực tiếp bản IEA 2026 khi xuất bản, nhất là 4.800 GW, 57%, 33%, 43%/gần 45% và cách diễn đạt “hơn ba phần tư”; giữ nguyên mốc nguồn, không tự hợp nhất hai tỷ lệ.
- **QA:** 5/5 dễ hiểu, 4/5 hữu ích, 5/5 tự nhiên, 3/5 nguồn chờ xác minh, 5/5 khác biệt/bố cục; **27/30, needs_revision nếu chưa fact-check**.

## PTS-KT-013

- **Vai đọc/việc cần làm:** ba hộ cùng 450 kWh muốn hiểu vì sao giờ dùng điện và mục tiêu dự phòng dẫn đến hướng khác nhau.
- **Before → after:** bỏ cảm giác bài đọc hóa đơn dài; ba hồ sơ A/B/C giờ là ba câu chuyện về tải ban ngày, tải buổi tối và tải mùa vụ/backup, chỉ dùng hóa đơn làm điểm xuất phát.
- **Góc/format mới:** case comparison ba cột, không lặp quy trình thực hành của 016.
- **Source doubt:** 450 kWh/30 ngày và 15 kWh/ngày là ví dụ giả định; phải giữ nhãn này khi dựng infographic. PVGIS không thay khảo sát tải/mái.
- **QA:** 5/5, 5/5, 5/5, 4/5, 5/5; **29/30**.

## PTS-KT-014

- **Vai đọc/việc cần làm:** người đang cân nhắc backup cần biết khi nào hòa lưới đủ và khi nào hybrid đáng xem.
- **Before → after:** thay mở bài định nghĩa thiết bị bằng hai câu hỏi quyết định; bảng đặt trước phần kỹ thuật, đồng thời nói rõ hybrid không mặc định kèm pin hay cấp điện toàn nhà.
- **Góc/format mới:** decision table + yêu cầu tối thiểu trong báo giá.
- **Source doubt:** thời gian chuyển EPS, tải đỉnh, pin tương thích và khả năng chạy không pin phải kiểm tra đúng model; không suy từ nguồn tổng quát.
- **QA:** 5/5, 5/5, 5/5, 4/5, 5/5; **29/30**.

## PTS-KT-015

- **Vai đọc/việc cần làm:** người coi trọng thẩm mỹ mái cần phân biệt màu, ảnh quảng cáo và thông số model.
- **Before → after:** bản mới đi từ ảnh đúng sản phẩm và màu nhìn thấy đến bảng model, rồi mới giải thích ngắn vật liệu/nhiệt; không biến đen/xanh thành bảng xếp hạng chất lượng.
- **Góc/format mới:** visual buying guide, ưu tiên kiểm chứng ảnh và model.
- **Source doubt:** nghiên cứu BIPV màu không chứng minh mọi module mái; ảnh cần đối chiếu manifest/quyền sử dụng, không thêm claim giấy phép.
- **QA:** 5/5, 5/5, 5/5, 4/5, 5/5; **29/30**.

## PTS-KT-016

- **Vai đọc/việc cần làm:** chủ nhà cần gửi một bộ dữ liệu có ích trước buổi khảo sát.
- **Before → after:** biến bài thành phiếu thực hành: bốn vùng trên hóa đơn, bảng 12 tháng, lịch dùng điện và danh sách cần gửi; nêu rõ một hóa đơn không đại diện cho cả năm.
- **Góc/format mới:** workbook/checklist thực hành, tách hẳn khỏi 013.
- **Source doubt:** DOE/PVGIS hỗ trợ nguyên tắc, không cung cấp giá điện, công suất hay hoàn vốn cho người đọc; ví dụ đều là minh họa.
- **QA:** 5/5, 5/5, 5/5, 4/5, 5/5; **29/30**.

## PTS-KT-017

- **Vai đọc/việc cần làm:** người mua đèn cần chọn ánh sáng cho lối đi, không bị dẫn bởi watt quảng cáo.
- **Before → after:** mở bằng một lối đi 2 × 10 m, giải nghĩa W/lm/lx trong cùng tình huống, rồi chuyển sang đọc datasheet và kiểm tra một tuần.
- **Góc/format mới:** explainer trực quan + phép tính minh họa có điều kiện.
- **Source doubt:** 2.000 lm/20 m², 20 W/5 giờ và 8 W/7 giờ là giả định; không biến thành mức sáng bắt buộc. IP, pin và thời lượng phải theo model.
- **QA:** 5/5, 5/5, 5/5, 4/5, 5/5; **29/30**.

## PTS-KT-018

- **Vai đọc/việc cần làm:** chủ nhà/xưởng muốn biết thêm tấm có tạo giá trị hay chỉ tạo điện dư.
- **Before → after:** đặt câu trả lời “không phải lúc nào” trước, so hai kịch bản tự dùng ban ngày/buổi tối, sau đó mới nói tính theo biên, inverter và pin.
- **Góc/format mới:** hai scenario giả định + khung tính phần tăng thêm, tránh ngôn ngữ tài chính hứa hẹn.
- **Source doubt:** 25 kWh/ngày, 18 kWh buổi tối, 500 kWh/năm và 60% là giả định; IRENA/PVGIS/DOE không xác nhận hoàn vốn cho công trình cụ thể.
- **QA:** 5/5, 5/5, 5/5, 4/5, 5/5; **29/30**.

## PTS-KT-019

- **Vai đọc/việc cần làm:** người chuẩn bị khảo sát mái cần nhận ra dấu hiệu phải dừng trước khi chọn số tấm.
- **Before → after:** mỗi loại mái gắn với cues có thể nhìn trên ảnh nhưng luôn dẫn về khảo sát kết cấu, nước, điện và vận hành; thêm nhắc ảnh đẹp không đủ quyết định.
- **Góc/format mới:** field guide theo ba loại mái + ngưỡng dừng an toàn.
- **Source doubt:** DOE là nguồn nguyên tắc chung; tải gió, ballast, chống dột, đầu nối và thủ tục đấu nối cần người có chuyên môn/đúng quy định địa phương.
- **QA:** 5/5, 5/5, 4/5, 4/5, 5/5; **28/30**.

## PTS-KT-020

- **Vai đọc/việc cần làm:** người mua muốn hiểu TOPCon/HJT/BC đủ để đọc hai datasheet, không cần học giáo trình vật liệu.
- **Before → after:** mở bằng lớp tóm tắt phổ thông, định nghĩa bốn từ, rồi giữ chiều sâu về kiến trúc cell và thứ tự so sánh mái/inverter/bảo hành.
- **Góc/format mới:** deep dive có lớp phổ thông; kết luận là thứ tự quyết định chứ không xếp hạng công nghệ.
- **Source doubt:** Fraunhofer/TaiyangNews hỗ trợ khái niệm; ưu thế hiệu suất, nhiệt, hai mặt và suy giảm phải đọc theo model, không lấy kỷ lục phòng thí nghiệm làm claim.
- **QA:** 5/5, 5/5, 4/5, 4/5, 5/5; **28/30**.

## PTS-KT-021

- **Vai đọc/việc cần làm:** chủ nhà hỏi mái có mát hơn sau khi lắp PV và cần biết cách kiểm chứng mà không hứa quá.
- **Before → after:** đối chiếu hai căn nhà có/không có đường thông gió, giải thích nhiệt qua mái, rồi đề xuất đo hai vùng tương đồng; không nêu mức giảm nhiệt.
- **Góc/format mới:** paired scenarios + field measurement note.
- **Source doubt:** DOE cung cấp nguyên tắc hệ PV/an toàn; không có nguồn nào trong bài chứng minh mức giảm nhiệt cố định. Kết quả đo phải gắn với công trình và điều kiện ghi nhận.
- **QA:** 5/5, 5/5, 5/5, 4/5, 5/5; **29/30**.

## QA chung

- Không bài nào mở bằng câu hỏi tu từ hoặc “Hãy...”; các mở bài lần lượt là mái, bản tin, ba hồ sơ, bộ lọc, ảnh/model, hóa đơn, lối đi, câu trả lời ngắn, khảo sát mái, lớp tóm tắt công nghệ và hai cấu tạo mái.
- Không dùng CTA chung ở cuối; mỗi bài kết bằng quyết định, giới hạn hoặc bước kiểm chứng riêng.
- Rà soát link nội bộ: các href đều là route gốc `/slug/`, không có `/kien-thuc/` và không đưa mã nội bộ ra mặt bài. Ảnh đều giữ URL/credit hiện có; không gán ảnh photorealistic là AI hay ngược lại.
- Không thay `written-articles.json`, DB local, slug, lịch đăng hoặc `is_visible=false`. Production state: chưa sync/publish/deploy. Rollback: revert commit này hoặc phục hồi ba file giao riêng.
