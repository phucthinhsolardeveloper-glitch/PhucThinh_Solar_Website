# Second-pass editorial review — PTS-KT-011–021

Đây là review sau khi đọc lại thân bài và diff thật, không phải bảng tự chấm điểm. Không bài nào được đánh dấu “đã hoàn tất” chỉ vì đủ heading, nguồn hoặc số từ; các claim cần kiểm chứng vẫn được ghi rõ. Ảnh, URL ảnh, credit và nguồn tham khảo được giữ; không đổi DB, slug, lịch đăng, visibility hay metadata dùng chung.

## Quyết định theo từng bài

### PTS-KT-011 — thay lớn: bài đọc báo giá theo hình học mái

- **Vấn đề trước:** lập luận “kích thước trước watt” đúng nhưng còn là nguyên tắc chung; người đọc chưa thấy cách dùng nó khi hai báo giá đều có vẻ hợp lý.
- **After:** thêm tình huống mái bị cửa mái chia thành hai mảng, so sánh báo giá ít tấm công suất cao với nhiều tấm nhỏ hơn, rồi buộc quyết định đi qua sơ đồ mái, bóng, lối bảo trì, chuỗi và inverter.
- **Vai đọc/format:** chủ nhà đang cầm báo giá; decision guide theo trình tự mái → hệ thống → điện áp.
- **Nguồn doubt:** không có model nên cố ý không thêm kích thước, Voc hay sản lượng của hãng; datasheet đúng mã vẫn là điều kiện tư vấn.

### PTS-KT-012 — thay lớn: tin ngành có giới hạn rõ

- **Vấn đề trước:** phần cuối trượt sang hướng dẫn dùng infographic/ghi công, làm loãng bản tin IEA.
- **After:** bỏ filler đó, thêm mục “Ba điều bản tin này không thể trả lời”: không suy ra sản lượng mái Việt Nam, không chọn thay inverter/hybrid, không phải bảng xếp hạng hay bảo đảm giá điện. Giữ ngày 6/11/2026 và toàn bộ các mốc/số liệu nguồn trong thân bài.
- **Vai đọc/format:** độc giả tin ngành; news brief có ba phát hiện, bảng mốc và một đoạn “ý nghĩa/giới hạn”, không CTA.
- **Nguồn doubt:** trước xuất bản phải đối chiếu trực tiếp báo cáo IEA cho 4.800 GW, 57%, 33%, 43%/gần 45% và phạm vi “hơn ba phần tư”; không hợp nhất hai tỷ lệ thành một số mới.

### PTS-KT-013 — thay lớn: ba lịch dùng điện, không phải bài đọc hóa đơn

- **Vấn đề trước:** ba hồ sơ đã khác nhau nhưng còn mô tả khái quát, thiếu bước tiếp theo để người đọc phân biệt nhu cầu chuyển tải với backup.
- **After:** thêm tình huống dời bơm từ tối sang cuối buổi sáng, làm rõ lịch 18–23 giờ chưa đủ để chọn pin, và thêm bảng “việc nên chuẩn bị tiếp” cho A/B/C.
- **Vai đọc/format:** ba household stories cùng 450 kWh giả định; bài trả lời “vì sao cùng kWh vẫn khác hướng”, không hướng dẫn điền hóa đơn (để 016).
- **Nguồn doubt:** 450 kWh/30 ngày và 15 kWh/ngày là ví dụ giả định; PVGIS không thay dữ liệu tải, mái và thiết kế điện.

### PTS-KT-014 — thay vừa: decision scene cho hybrid

- **Vấn đề trước:** bảng quyết định tốt nhưng thiếu cảnh khiến người đọc hiểu hậu quả của lựa chọn hòa lưới.
- **After:** thêm tình huống mất điện lúc 12 giờ trưa khi mái vẫn có nắng; từ đó giải thích vì sao mục tiêu giảm tiền điện khác mục tiêu giữ tủ lạnh/modem.
- **Vai đọc/format:** người đang so hai phương án; decision table + một cảnh mất điện + yêu cầu tối thiểu trong báo giá.
- **Nguồn doubt:** EPS, thời gian chuyển, tải đỉnh, pin tương thích và khả năng chạy không pin phải kiểm tra đúng model.

### PTS-KT-015 — giữ cấu trúc, chỉ giữ lớp kiểm chứng cần thiết

- **Lý do giữ:** bài đã có góc riêng: màu là quyết định thẩm mỹ/model, không phải xếp hạng chất lượng; bảng model và phần nhiệt đủ dùng, không cần kéo dài thành bài vật liệu.
- **Đã giữ/chỉnh:** phân biệt ảnh quảng cáo với bằng chứng model và không suy chất lượng từ màu đen hơn.
- **Nguồn doubt:** nghiên cứu BIPV màu không chứng minh mọi module mái; ảnh cần đối chiếu manifest/quyền sử dụng.

### PTS-KT-016 — giữ cấu trúc: workbook thực hành

- **Lý do giữ:** bài đã thực sự khác 013: người đọc khoanh bốn vùng trên hóa đơn, lập bảng 12 tháng, ghi lịch tải và chuẩn bị hồ sơ gửi tư vấn; không suy công suất từ một hóa đơn.
- **Ranh giới:** không đưa thêm scenario tải vào đây để tránh lấn vai 013; ví dụ bảng vẫn là ô trống, không giả dữ liệu khách hàng.
- **Nguồn doubt:** DOE/PVGIS chỉ hỗ trợ nguyên tắc; không suy giá điện, sản lượng hay hoàn vốn cho một địa chỉ.

### PTS-KT-017 — giữ cấu trúc: giải thích bằng lối đi

- **Lý do giữ:** 017 đã có format riêng (2 × 10 m, W/lm/lx, đọc datasheet, kiểm tra một tuần) và không trùng 008 là checklist mua đèn.
- **Nguồn doubt:** các số 2.000 lm, 20 W/5 giờ, 8 W/7 giờ là giả định; không biến thành mức sáng bắt buộc hay hiệu năng model.

### PTS-KT-018 — giữ cấu trúc: hai scenario tài chính giả định

- **Lý do giữ:** bài đã đi theo “phần điện tăng thêm có ích bao nhiêu”, tách nhà dùng tối với xưởng dùng ban ngày, rồi mới nói inverter, chi phí ẩn và pin; không hứa hoàn vốn.
- **Kiểm tra:** mọi số 25 kWh/ngày, 18 kWh buổi tối, 500 kWh/năm và 60% phải tiếp tục mang nhãn giả định.

### PTS-KT-019 — giữ cấu trúc: field guide mái và ngưỡng dừng

- **Lý do giữ:** bài đã có cues nhìn từ ảnh cho mái tôn/ngói/bằng, bốn lớp khảo sát, chống dột, an toàn DC và thời điểm dừng chọn số tấm; không nên rút thành checklist mua hàng.
- **Nguồn doubt:** tải gió, ballast, chống dột, đầu nối và thủ tục đấu nối cần hồ sơ đúng công trình/người có chuyên môn.

### PTS-KT-020 — thay vừa: lớp phổ thông trước deep dive

- **Vấn đề trước:** giải thích đúng nhưng người mua phải đi qua nhiều đoạn kỹ thuật trước khi biết nên nhìn gì trên datasheet.
- **After:** thêm bảng ba hàng “tên công nghệ → tóm tắt dễ hiểu → trường cần nhìn trước”, rồi mới đi sâu TOPCon/HJT/BC. Bảng được ghi rõ không phải xếp hạng.
- **Vai đọc/format:** người mua có nền kỹ thuật vừa phải; deep dive hai lớp, lớp đọc nhanh và lớp kiểm chứng model.
- **Nguồn doubt:** ưu thế hiệu suất, nhiệt, hai mặt và suy giảm phải đọc theo model; không dùng kỷ lục phòng thí nghiệm làm claim thương mại.

### PTS-KT-021 — giữ cấu trúc: paired scenarios và cách đo

- **Lý do giữ:** hai căn nhà (khe thông gió/giữ nhiệt), diễn giải truyền nhiệt và phép đo hai vùng đã trả lời đúng câu hỏi mà không hứa mức giảm nhiệt cố định.
- **Nguồn doubt:** DOE là nguồn nguyên tắc hệ PV/an toàn; kết quả đo chỉ có nghĩa trong điều kiện công trình được ghi nhận.

## QA thực tế sau follow-up

- Diff thân bài hiện có thay đổi nội dung ở 011, 012, 013, 014 và 020; 015, 016, 017, 018, 019, 021 được giữ vì đã có format và giá trị riêng, chỉ giữ các chỉnh sửa kiểm chứng cần thiết từ vòng trước.
- 013 dùng ba hồ sơ theo giờ/tải/mục tiêu; 016 dùng hóa đơn và bộ dữ liệu trước khảo sát. 008/017 không bị nhập vai: 008 là checklist chọn đèn, 017 là giải nghĩa W/lm/lx bằng lối đi.
- 011/015/020 có thuật ngữ nhưng đều có lớp diễn giải cho người mua trước phần kỹ thuật. 016–021 không bị ép cùng một template; heading đã giảm nơi phù hợp, còn các mục dài được giữ khi cần cho thao tác/an toàn.
- Không có CTA chung, không có mã bài trong thân bài, không có href `/kien-thuc/`; link nội bộ giữ route gốc `/slug/`. Không tự xác nhận giấy phép ảnh hay claim AI; credit hiện có được giữ nguyên.
- **Chưa làm:** không chạy test ứng dụng/DB vì đây là Markdown review; chưa sync, publish, deploy. Cần fact-check nguồn IEA 012 và datasheet đúng model cho các bài kỹ thuật trước khi CMS sync.
