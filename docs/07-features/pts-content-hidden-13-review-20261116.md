# Rà soát biên tập độc lập: 13 bản nháp PTS-KT-009..021

**Ngày rà soát:** 2026-11-16  
**Phạm vi:** Chỉ đọc 13 Markdown trong `backend/resources/editorial/2026-11-12/articles/`, `calendar-180.csv`, chuẩn biên tập và snapshot công khai ngày 2026-11-16. Không đọc DB/SSH/production, không sửa nguồn, không kiểm tra độc lập URL, dữ liệu kỹ thuật, quyền ảnh hay claim. Đây là checklist ready-to-edit, không phải duyệt xuất bản.

## Cách đọc kết quả

Điểm theo thứ tự **dễ hiểu / thực dụng / tự nhiên / nguồn / khác biệt / bố cục**, mỗi mục 0–5; tổng /30. Điểm “nguồn” chỉ phản ánh cách nguồn được dẫn và mức có thể truy ngược ngay trong bản nháp, **không xác nhận nguồn đúng, URL còn hoạt động, hay claim được nguồn hỗ trợ**. Điểm kỹ thuật cũng không phải xác nhận kỹ thuật. Theo chuẩn nội bộ, cần tự nhiên và nguồn ít nhất 4, tổng ít nhất 24; hiện chưa có bài nào được coi là đạt ngưỡng nguồn đã xác minh.

`Giữ` nghĩa là giữ góc biên tập nhưng vẫn cần các bước QA. `Chỉnh` cần sửa bản thảo trước vòng QA tiếp theo. `Hold` cần dừng quyết định nội dung cho tới khi làm rõ claim/nguồn hoặc tài sản liên quan. Không trạng thái nào trong báo cáo này cho phép xuất bản.

## Tình trạng nguồn và giới hạn snapshot

- CSV của 009–021 ghi toàn bộ `draft_status = Chưa biên tập`, `tech_approval = Chưa duyệt`, `publish_status = Chưa đăng`. 009–015 có `image_status = Chưa chọn`; 016–021 là `Đã chọn - chờ duyệt`. Đây là trạng thái được ghi trong lịch, không phải xác nhận trạng thái CMS hiện tại.
- Snapshot có **33 bản ghi**: 20 bài cũ và 13 mục 144–156 cùng tiêu đề/slug với các nháp; trong đó các bài **136–143** có `published: true` và là nhóm công khai liên quan theo yêu cầu. Snapshot ghi rõ là dữ liệu Public API/HTML đã sanitize, không phải baseline DB thô. Phân tích cannibalization dưới đây dùng title, summary, SEO, link và phần body công khai sẵn có trong snapshot; không suy rộng về trạng thái ngoài snapshot.
- Chuẩn yêu cầu giữ title/slug lịch nếu chưa duyệt thay đổi. Markdown không có trường summary/meta description. Vì vậy SEO chưa ready: cần soát/cung cấp summary và metadata qua luồng quản lý phù hợp, không tự đổi slug.
- Chỉ 016 và 017 có liên kết nội bộ trong thân bài. Không thấy liên kết sản phẩm trong 13 Markdown. Điều này không chứng minh CMS không có trường liên kết sản phẩm hoặc bài liên quan.

## Đánh giá từng bài

### PTS-KT-009 — Trời nhiều mây, ánh sáng yếu: pin còn tạo điện thế nào?

**Điểm:** 4 / 4 / 3 / 2 / 4 / 4 = **21/30** · **Chỉnh**.  
**Bằng chứng:** Mở thẳng “Có, nhưng đừng dùng một tỷ lệ cố định”; chia ba lớp thời tiết/chuỗi ngày/dấu hiệu kiểm tra và có mẫu nhật ký. Cụ thể, dễ áp dụng, đúng góc CSV “không dùng số phần trăm chung”. Tuy vậy “ở Gia Lai và các khu vực Tây Nguyên” cùng cà phê/hồ tiêu là địa phương hóa nhưng chưa gắn nguồn/đối tượng cụ thể; câu “rìa mây đôi khi tạo đỉnh ngắn do phản xạ” là claim cần rà soát kỹ thuật. Hai nguồn DOE và PVGIS được liệt kê cuối bài nhưng chưa gắn claim nào với nguồn nào. Không có ảnh nội tuyến, link nội bộ hay sản phẩm; lịch để ảnh thực/stock nhưng chưa chọn. SEO: title/slug có trong CSV; summary/meta description không có trong Markdown.

**Ba việc sửa ưu tiên:** (1) Thêm một bước hành động sau nhật ký: dữ liệu nào người dùng tự xem, dấu hiệu nào chuyển cho kỹ thuật viên; giảm thuật ngữ MPPT/điện áp nếu không phục vụ bước đó. (2) Gắn claim về bức xạ tán xạ, đỉnh do rìa mây, khởi động inverter và so sánh mùa với nguồn/đoạn hỗ trợ; kiểm tra “Gia Lai/Tây Nguyên” có cần thiết không. (3) Chọn ảnh đúng cảnh nhiều mây hoặc sơ đồ có chú thích, bổ sung alt/credit và summary trong CMS.

**Cần kiểm chứng:** DOE `solar-photovoltaic-technology-basics`; EC JRC PVGIS `https://re.jrc.ec.europa.eu/pvg_tools/en/`; claim đỉnh bức xạ ở rìa mây, ngưỡng dừng inverter, lời khuyên so sánh sản lượng theo mùa. Không xác minh các URL/claim trong vòng này.

### PTS-KT-010 — Vì sao có tấm pin nhưng mất điện lưới vẫn có thể bị cúp?

**Điểm:** 4 / 5 / 4 / 3 / 3 / 4 = **23/30** · **Chỉnh**.  
**Bằng chứng:** Mở bằng tình huống mất điện ngày nắng, phân biệt hòa lưới và nhánh dự phòng, có 4 câu hỏi đọc báo giá và cảnh báo an toàn hữu ích. Nội dung có sơ đồ `/upload/news/pts-explainer-grid-outage.webp`, nhưng chưa có bằng chứng file ảnh đã được rà soát. CSV ghi loại ảnh “Stock / infographic” và ghi chú “công nghệ cell phải thể hiện bằng sơ đồ có kiểm chứng”, không khớp với sơ đồ mất điện trong Markdown; cần sửa brief/alt hoặc ảnh. Hình chú thích “Phúc Thịnh Solar” cần xác định tác giả/credit thực tế. Không có link nội bộ hoặc sản phẩm. SEO title/slug có trong CSV; thiếu summary trong Markdown.

**Ba việc sửa ưu tiên:** (1) Đặt sơ đồ ngay cạnh phần A/B và kiểm tra nó thể hiện đúng cách cách ly lưới, nguồn cấp và tải; đồng bộ image brief với nội dung thật. (2) Làm rõ điều kiện “hybrid có thể tạo lưới cục bộ”, pin tương thích, EPS và tải dự phòng phụ thuộc đúng model; gắn nguồn sát claim. (3) Giảm trùng phần giải thích mất điện với bài công khai 142 bằng cách giữ trọng tâm ở câu hỏi “vì sao hệ đang có vẫn tắt” và chuyển lựa chọn ba loại hệ sang link bài 142.

**Cần kiểm chứng:** DOE `solar-and-resilience-basics`, DOE `solar-integration-inverters-and-grid-services-basics`, NREL PDF `https://www.nrel.gov/docs/fy22osti/77782.pdf`; claim anti-islanding, EPS một/ba pha, khả năng chạy khi pin cạn và yêu cầu cách ly/nối đất theo model. Kiểm tra sơ đồ/credit trước khi dùng.

### PTS-KT-011 — Tấm pin 600W, 620W, 650W và 720W: watt cao có tốt hơn?

**Điểm:** 4 / 5 / 4 / 2 / 3 / 4 = **22/30** · **Hold** phần thông số cho tới khi có model cụ thể.  
**Bằng chứng:** Mở bằng xung đột diện tích mái và Wp; hướng dẫn đặt kích thước tấm lên bản vẽ mái, sau đó mới xét hiệu suất/chi phí, phù hợp với người mua. Ví dụ 720 so với 600 Wp (+120 Wp, +20%) là số học từ nhãn; không phải so sánh model. Bài nói đúng rằng không có kích thước chung cho mọi mẫu, nhưng ảnh `/upload/news/pts-explainer-power-ratings.webp` và bảng 600/620/650/720 có thể tạo cảm giác các mức đã được so bằng dữ liệu model; chưa thấy model hoặc bảng số đo thực. Nguồn DOE/Fraunhofer/PVGIS ở cuối bài khá tổng quát; CSV ghi primary source Clean Energy Reviews dạng trang chủ. Không có link nội bộ/sản phẩm. SEO chỉ có title/slug theo lịch.

**Ba việc sửa ưu tiên:** (1) Bổ sung ít nhất một model cụ thể đã được cho phép dùng, có datasheet/phiên bản/ngày truy cập và kích thước/khối lượng; nếu chưa có, đổi biểu đồ thành minh họa rõ không đại diện model. (2) Gắn tham chiếu nguồn cho các đoạn hiệu suất, Wp/STC, sản lượng và kiểm tra Voc/Vmp/Isc/Imp; giữ cảnh báo không thay model nếu không xác minh chuỗi/inverter. (3) Rút bớt phần kỹ thuật MPPT cuối bài hoặc đóng thành hộp “để người thiết kế kiểm tra”; thêm link nội bộ tới bài 137 hoặc bài 155 nếu phù hợp để tránh giải thích lại thông số.

**Cần kiểm chứng:** DOE `solar-photovoltaic-system-design-basics`; Fraunhofer ISE Photovoltaics Report; JRC PVGIS User Manual; Clean Energy Reviews trang nguồn trong CSV. Cần đối chiếu datasheet thật cho từng claim về kích thước, hiệu suất và giới hạn điện áp/dòng; các nguồn hiện liệt kê chưa xác minh những mã cụ thể.

### PTS-KT-012 — IEA công bố Renewables 2026: triển vọng điện mặt trời đến 2031

**Điểm:** 4 / 3 / 3 / 2 / 4 / 3 = **19/30** · **Hold** cho tới khi đối chiếu bản báo cáo và biểu đồ.  
**Bằng chứng:** Mở với ngày công bố và phân biệt forecast/thực tế, tách ba phát hiện và giới hạn suy luận cho mái nhà. Tuy nhiên đây là bài tin với nhiều số liệu trọng tâm: gần 4.800 GW, +57%, hơn 3/4, 33% năm 2025, 43%/“gần 45%” năm 2031, gió+mặt trời vượt 28%. Nội dung tự nêu hai cách diễn đạt 43% và gần 45%, cần giải thích có phải khác phạm vi/làm tròn hay không. Biểu đồ inline `/upload/news/pts-explainer-iea-renewables-2026.webp` tuyên bố 33% và khoảng 43%; số liệu/nguồn cần duyệt trước sử dụng. Nguồn IEA report, executive summary và press release được dẫn nhưng chưa xác minh. Không có link nội bộ/sản phẩm. SEO thiếu summary trong Markdown.

**Ba việc sửa ưu tiên:** (1) Lập bảng đối chiếu từng số với trang/bảng/định nghĩa trong báo cáo IEA, ghi rõ phạm vi (renewables hay solar; capacity hay generation), kỳ so sánh và cách làm tròn; giải quyết 43%/gần 45%. (2) Kiểm tra biểu đồ, nhãn trục, caption, năm nền/dự báo và alt với số đã đối chiếu; không dùng biểu đồ nếu chưa khớp. (3) Rút phần giải thích lặp lại giới hạn toàn cầu/địa phương, giữ bản tin gọn và thêm giá trị riêng cho người đọc Việt Nam chỉ khi có dữ liệu địa phương được dẫn.

**Cần kiểm chứng:** IEA `https://www.iea.org/reports/renewables-2026`, `/executive-summary`, thông cáo `https://www.iea.org/news/energy-security-concerns-spur-faster-renewables-growth-across-a-widening-constellation-of-countries`; toàn bộ số 4.800 GW, 57%, 3/4, 33%, 43%/gần 45%, 28%, ngày 06/11/2026 và caption biểu đồ. Báo cáo là nguồn được nêu trong file, chưa được kiểm chứng tại đây.

### PTS-KT-013 — Nhà dùng bao nhiêu điện mỗi tháng thì phù hợp lắp năng lượng mặt trời?

**Điểm:** 4 / 4 / 3 / 2 / 2 / 3 = **18/30** · **Chỉnh**; nguy cơ cannibalization cao.  
**Bằng chứng:** Ba hồ sơ giả định cùng 450 kWh/tháng cho thấy tổng kWh không biểu thị giờ dùng. Đây là ví dụ rõ nhưng trùng trực tiếp bài công khai 141 (lịch tải quyết định hướng hệ) và chạm phần thu thập hóa đơn bài công khai 143? Nhóm 016 mới là hóa đơn chi tiết. Bài 141 công khai dùng 600 kWh, ba lựa chọn hòa lưới/hybrid/backup và bài tập 15 phút; 013 đang làm lại cùng lập luận “cùng tổng nhưng lịch khác”, cùng giải pháp phân loại tải. Hình `/upload/news/pts-explainer-load-profile.webp` chưa có hồ sơ asset trong phạm vi đọc. Nguồn DOE rooftop potential và PVGIS phù hợp ở mức chủ đề nhưng chưa map claim. Không thấy link đến bài 141 hoặc sản phẩm. SEO title/slug theo CSV, summary thiếu.

**Ba việc sửa ưu tiên:** (1) Chọn một nhiệm vụ khác rõ ràng so với 141: quy convertir tổng hóa đơn thành dữ liệu cần gom/đọc, hoặc rút bài này và hợp nhất vai trò sau quyết định biên tập. Không xuất bản hai bài trả lời cùng câu hỏi. (2) Giữ nhãn “giả định” cạnh 450 kWh và từng lịch A/B/C; tránh giọng như case khách hàng thực. (3) Nếu giữ bài: link rõ tới 141 cho quyết định theo giờ tải, nối tới 016 cho cách đọc hóa đơn; kiểm tra sơ đồ và nguồn.

**Cần kiểm chứng:** DOE `solar-rooftop-potential`; JRC PVGIS và User Manual; cách diễn giải rằng kWh tháng không xác định công suất hệ, đại diện 450 kWh/15 kWh/ngày là phép tính giả định (450/30), mọi kết luận tải/pin. Không đánh giá tính đúng của khuyến nghị thiết kế.

### PTS-KT-014 — Inverter hòa lưới và hybrid khác nhau thế nào?

**Điểm:** 4 / 4 / 4 / 2 / 2 / 4 = **20/30** · **Chỉnh**; ranh giới với 010 và bài công khai 140/142 chưa đủ sắc.  
**Bằng chứng:** Câu mở tập trung nhu cầu mất điện và chi phí pin; bảng quyết định cùng cảnh báo “hybrid” không tự bảo đảm pin/backup hữu ích. Dù vậy, 010 đã giải thích tình huống cúp điện và 142 công khai đã so hòa lưới/hybrid/độc lập, pin, nhánh dự phòng và mất lưới; 140 công khai giải thích chức năng inverter/MPPT. Phần sơ đồ dây theo plan chưa hiện thành hình kỹ thuật; ảnh trong Markdown là bảng so sánh `/upload/news/pts-explainer-inverter-types.webp`. Nguồn gồm Clean Energy Reviews blog, Fronius, DOE; chưa kiểm chứng. Không có link tới bài 142/140 hoặc sản phẩm. SEO summary thiếu.

**Ba việc sửa ưu tiên:** (1) Định vị hẹp: checklist đối chiếu báo giá/model inverter hòa lưới và hybrid, không lặp bảng chọn ba loại hệ của 142 hay nguyên lý inverter của 140. (2) Thay/hoàn thiện ảnh thành sơ đồ luồng điện đúng cấu hình, gắn nguồn và model minh họa; hoặc bỏ nếu không có bản kỹ thuật được duyệt. (3) Thêm link ngữ cảnh tới 142 (lựa chọn hệ) và 010 (lý do cúp điện) sau khi xác định đây là bài phụ trợ.

**Cần kiểm chứng:** Clean Energy Reviews `solar-hybrid-battery-ready-inverters`; Fronius `what-is-an-inverter`; DOE `solar-photovoltaic-system-design-basics`; các claim về hybrid/battery-ready, EPS, pin tương thích, tải dự phòng và vận hành khi mất lưới cho từng model.

### PTS-KT-015 — Tấm pin màu đen có luôn tốt hơn tấm pin màu xanh?

**Điểm:** 4 / 3 / 4 / 2 / 4 / 4 = **21/30** · **Chỉnh**.  
**Bằng chứng:** Mở bằng khác biệt nhìn thấy được giữa khung/nền đen và cell sẫm; kết luận không suy công nghệ hay hiệu suất từ màu, bám đúng angle lịch. Có bảng tiêu chí mua và phần ngắn về nhiệt. Claim “IEA PVPS và Fraunhofer ISE cho thấy…” chưa dẫn tới tài liệu cụ thể trong đoạn; danh mục gồm Photovoltaics Report chung, IEA PVPS Task 15 về coloured BIPV và một bài BUILD UP, nên cần xác định phạm vi BIPV màu có áp dụng tới loại module được bàn hay không. Hình `/upload/news/pts-explainer-module-colors.webp` có credit Phúc Thịnh Solar nhưng lịch yêu cầu sơ đồ công nghệ có kiểm chứng; ảnh cụ thể chưa đối chiếu. Không có link nội bộ/sản phẩm. SEO summary thiếu.

**Ba việc sửa ưu tiên:** (1) Thu hẹp claim nghiên cứu màu/nhiệt về đúng công nghệ và cấu hình; phân biệt BIPV màu với module rooftop thông thường, hoặc bỏ claim nếu không có nguồn phù hợp. (2) Bổ sung model cụ thể khi nói “all-black”, màu, hiệu suất và nhiệt; nếu chưa có thì giữ ở mức tiêu chí hỏi nhà cung cấp. (3) Xác nhận ảnh là so sánh minh họa hay ảnh sản phẩm, cập nhật caption/alt/credit và brief; không để ảnh minh họa bị hiểu là model bán thực tế.

**Cần kiểm chứng:** Fraunhofer ISE Photovoltaics Report; IEA PVPS Task 15 report; BUILD UP `interplay-between-aesthetics-and-energy-performance-bipv-modules`; claim về hấp thụ/phản xạ, hiệu suất/nhiệt và cách dùng kết luận BIPV cho pin mái. Link/claim chưa xác minh.

### PTS-KT-016 — Cách đọc hóa đơn tiền điện trước khi thiết kế hệ thống

**Điểm:** 5 / 5 / 4 / 3 / 3 / 4 = **24/30** · **Giữ góc, chỉnh nhẹ**.  
**Bằng chứng:** Bắt đầu bằng một tác vụ cụ thể, phân vùng hóa đơn, bảng 12 tháng, giờ dùng, dữ liệu gửi tư vấn và bảo vệ PII. Nêu giới hạn hóa đơn rõ. Rủi ro khác biệt: bài 141 công khai đã có “bài tập 15 phút với hóa đơn” và yêu cầu lấy 12 tháng/viết lịch tải; cần giữ 016 ở mức hướng dẫn đọc/chuẩn bị hồ sơ chi tiết, không lặp quyết định hòa lưới/hybrid. Liên kết nội bộ hiện có `/nha-dung-bao-nhieu-dien-moi-thang-thi-phu-hop-lap-nang-luong-mat-troi/`, trỏ slug nháp 013, không phải bài công khai 141; đây là vấn đề URL cần xử lý. Hình cover Pexels ghi tên Mikhail Nilov và yêu cầu credit/photo-cover-selections.json, nhưng chưa xem file lựa chọn/giấy phép; không có ảnh nội tuyến hoặc link sản phẩm. SEO thiếu summary trong Markdown.

**Ba việc sửa ưu tiên:** (1) Thay link nội bộ sang slug công khai 141 nếu liên kết tới bài đó; xác nhận chiến lược URL 013 trước khi đặt link tới bản chưa xuất bản. (2) Rút phần “Gửi gì”/“Mang gì” đang lặp checklist, giữ một danh sách duy nhất; xác định đây là tài liệu chuẩn bị, không phải hướng dẫn thiết kế. (3) Xác minh phép tính ví dụ 900 kWh/28–33 ngày, thông tin hóa đơn tại Việt Nam và khuyến nghị đo tải; duyệt credit/giấy phép cover.

**Cần kiểm chứng:** DOE rooftop potential; JRC PVGIS; claim về các trường hóa đơn, giá điện/bậc giá tại thời điểm lập hóa đơn, phép quy đổi kWh/ngày, thiết bị đo tải và an toàn đấu đo. Không xác minh quy định/biểu giá hiện hành trong review này.

### PTS-KT-017 — Lumen, lux và watt khác nhau thế nào khi chọn đèn?

**Điểm:** 4 / 5 / 3 / 2 / 2 / 4 = **20/30** · **Chỉnh**; trùng chủ đề mua đèn đã công khai 143 nhưng có thể làm bài giải nghĩa bổ trợ.  
**Bằng chứng:** Mở bằng ví dụ 200 W/20 W rồi định nghĩa W/lm/lx, đưa ví dụ giả định 2.000 lm trên 20 m² và 156 Wh/đêm, có link tới bài mua đèn 143. Người đọc có tác vụ rõ. Tuy nhiên phần giải nghĩa và checklist quảng cáo/báo giá lặp một số ý bài 143 (W không đủ, Wh, thử nghiệm, vị trí chiếu); bài dài và nhiều phép tính có thể lấn vai trò buying guide. Nguồn DOE về bóng tiết kiệm năng lượng và Lighting Global standard cần được map cụ thể tới đại lượng quang học/đèn solar; nguồn chưa xác minh. Không có ảnh inline dù angle lịch nói làm đồ họa; ảnh Pexels cover chờ duyệt. Không có product link.

**Ba việc sửa ưu tiên:** (1) Dùng một ví dụ hình học ngắn để phân biệt lm/lx, bỏ hoặc rút phép tính năng lượng 156 Wh nếu không cần cho mục tiêu quang học. (2) Giữ 143 làm checklist chọn/mua; link bài 143 ở cuối phần khái niệm, bỏ checklist báo giá trùng nhau. (3) Làm đồ họa đơn giản có chú giải/đơn vị/giả định và nguồn, hoặc bỏ nếu không có dữ liệu kiểm chứng; thêm summary SEO.

**Cần kiểm chứng:** DOE `purchasing-energy-efficient-light-bulbs`; Lighting Global Quality Standards/Why Quality Is Important; định nghĩa W, lumen, lux; 2.000/20 = 100 lx chỉ là phân bố lý tưởng; 20×5 + 8×7 = 156 Wh chưa tính tổn hao. Kiểm tra cách gắn tiêu chuẩn Lighting Global với đèn cụ thể.

### PTS-KT-018 — Lắp tấm pin càng nhiều có luôn rút ngắn hoàn vốn?

**Điểm:** 4 / 4 / 3 / 2 / 4 / 3 = **20/30** · **Hold** các kết luận kinh tế cho tới khi kiểm tra mô hình số.  
**Bằng chứng:** Câu trả lời đầu tiên phủ định khái quát, nhấn vào phần điện tăng thêm có ích; hai kịch bản được khai báo giả định. Bài dùng nhiều số 25 kWh/ngày, 18 kWh buổi tối, 500 kWh/năm, 60% tự dùng, 300 kWh và chi phí/biên nhưng hiện không có bảng giả định đầy đủ hoặc công thức hoàn vốn xuyên suốt trong trích xuất file. Nguồn IRENA về chi phí phát điện quy mô lớn không tự chứng minh kinh tế hệ mái hộ gia đình; cần tránh dùng như nguồn trực tiếp cho payback. Không ảnh inline/link nội bộ/sản phẩm; cover Pexels được ghi nhưng chưa xác minh giấy phép. SEO summary thiếu.

**Ba việc sửa ưu tiên:** (1) Tạo bảng đầu vào và phép tính tái tạo cho từng kịch bản (sản lượng, tự dùng, dư, biểu giá, chi phí tăng thêm, tuổi thọ/thay thế, thời gian) và ghi rõ không phải báo giá. (2) Tách “sản lượng tăng” khỏi “giá trị tiết kiệm”; không kết luận nhanh/chậm hoàn vốn nếu chưa nêu giá điện và cơ chế ghi nhận điện dư tại thời điểm/địa điểm áp dụng. (3) Đổi hoặc giới hạn nguồn IRENA; dùng nguồn phù hợp cho hiệu suất/yield, còn giả định kinh tế phải ghi rõ lấy từ đâu; link tới 016 về dữ liệu hóa đơn và 141 về giờ dùng nếu hữu ích.

**Cần kiểm chứng:** IRENA Renewable Power Generation Costs in 2025, JRC PVGIS, DOE system design basics; mọi số kWh/tỷ lệ 60%/giá trị điện dư/chi phí hệ thống/tuổi thọ; công thức hoàn vốn và giả định giá điện. Không đánh giá hiệu quả tài chính thực tế.

### PTS-KT-019 — Mái tôn, mái ngói, mái bằng: lưu ý khi khảo sát điện mặt trời

**Điểm:** 4 / 5 / 4 / 2 / 4 / 4 = **23/30** · **Giữ góc, chỉnh nhẹ**.  
**Bằng chứng:** Mở bằng ba câu hỏi mái chịu lực/chống dột/bảo trì, sau đó chia theo mái và kết thúc bằng câu hỏi trước báo giá; thực dụng, hạn chế hướng dẫn thi công tự làm. Claim về tải, chống thấm, neo, dây và nguy cơ dột mang tính an toàn nên nguồn cuối bài cần map và kỹ thuật duyệt. Không có ảnh inline; cover Pexels Los Muertos Crew được plan ghi nhưng chưa đối chiếu asset/license. Không có link nội bộ/sản phẩm. SEO title/slug theo lịch, summary thiếu. Khác 021: 019 là checklist khảo sát cấu trúc/an toàn; 021 là tác động nhiệt.

**Ba việc sửa ưu tiên:** (1) Gắn nguồn hoặc đánh dấu yêu cầu kỹ sư cho các câu về tải mái, tải gió, điểm neo, chống thấm và lối bảo trì; tránh đưa hướng dẫn/kích thước chung. (2) Cân nhắc rút câu kết luận lặp lại đặc điểm từng mái; giữ checklist câu hỏi có thể mang đi khảo sát. (3) Duyệt ảnh minh họa đúng loại mái và caption “ảnh minh họa”, kiểm tra credit/license; thêm summary và link sang bài 021 chỉ khi giúp độc giả tìm hiểu phần nhiệt.

**Cần kiểm chứng:** DOE `solar-photovoltaic-system-design-basics`, `guide-fire-safety-solar-systems`, `solar-rooftop-potential`; mọi gợi ý kết cấu/chống dột/tải gió/dây DC và phạm vi áp dụng ở Việt Nam. Các link hiện có không được mở/xác minh.

### PTS-KT-020 — TOPCon, HJT, BC (Back Contact) khác nhau ra sao?

**Điểm:** 4 / 4 / 3 / 2 / 4 / 3 = **20/30** · **Hold** phần so sánh công nghệ cho tới khi có nguồn/model cụ thể.  
**Bằng chứng:** Mở dễ hiểu: ba nhãn không phải ba cấp chất lượng; chia TOPCon/HJT/BC rồi hướng người đọc so cùng bộ thông số, đúng angle kế hoạch. Người đọc phổ thông vẫn gặp nhiều thuật ngữ và các heading kỹ thuật nối tiếp nhau; chuẩn nội bộ cho phép bài chuyên sâu nhưng cần lớp phổ thông rõ hơn. Danh sách nguồn Fraunhofer, TaiyangNews ở cuối chưa chỉ rõ claim nào dựa vào nguồn nào, có thể không bao quát mọi biến thể/model. Không có ảnh inline dù chủ đề công nghệ có thể cần sơ đồ; cover Pexels được chọn/chờ duyệt nhưng không chứng minh cấu trúc cell. Không có link nội bộ hoặc sản phẩm. SEO summary thiếu.

**Ba việc sửa ưu tiên:** (1) Thêm đoạn tóm tắt ngắn bằng ngôn ngữ phổ thông trước từng công nghệ, rồi để chi tiết tiếp xúc/cấu trúc vào phần đọc thêm. (2) Gắn từng claim về kiến trúc, hệ số nhiệt, hiệu suất/diện tích và bảo hành với tài liệu hãng/model hoặc nguồn nghiên cứu có phạm vi rõ; không tạo bảng so sánh định lượng nếu chưa có datasheet cùng điều kiện. (3) Dùng sơ đồ tự dựng có kiểm chứng/credit hoặc bỏ hình công nghệ không liên quan; thêm link sang bài 137/011 cho việc đọc thông số nếu tránh lặp.

**Cần kiểm chứng:** Fraunhofer Photovoltaics Report và TOPCon project; TaiyangNews report; các khái niệm TOPCon/HJT/BC, so sánh mức hiệu suất/hệ số nhiệt, tính đại diện nghiên cứu và mọi claim gắn với model. Không xác minh nguồn ngoài.

### PTS-KT-021 — Điện mặt trời có làm mái nhà nóng hơn hay mát hơn?

**Điểm:** 4 / 5 / 3 / 2 / 3 / 3 = **20/30** · **Chỉnh**; một số ý lặp ở mở bài/kết luận.  
**Bằng chứng:** Mở bằng câu trả lời có điều kiện, so hai cấu hình mái, đề nghị đo trong điều kiện tương đồng và nhấn an toàn. Đoạn mở nói kết quả phụ thuộc khe/không khí/mái/trần; phần “hai căn nhà” lặp lại gần nguyên luận điểm, phần cuối lại chốt cùng điều kiện. Nhiều khẳng định về đường nhiệt, gió, tôn/ngói/bê tông và phép đo hồng ngoại cần nguồn phù hợp; DOE design/rooftop/fire safety được dẫn chung, chưa thể hiện chúng hỗ trợ claim nhiệt nào. Không ảnh inline; cover Pexels Janick Bunzel được ghi chọn/chờ duyệt nhưng chưa xác minh. Không có link tới 019 (khảo sát mái), không có sản phẩm. SEO summary thiếu.

**Ba việc sửa ưu tiên:** (1) Hợp nhất mở bài với “Hai căn nhà” và rút kết luận lặp; giữ hai kịch bản và mấu chốt nhiệt độ mặt mái khác nhiệt độ phòng. (2) Gắn nguồn nghiên cứu/đo đạc cho các claim về khe thông gió, loại mái và nhiệt; làm rõ quy trình so sánh chỉ là phép kiểm tra tại chỗ, không đại diện mọi nhà. (3) Thêm link sang 019 về đánh giá mái/an toàn; duyệt ảnh để không ngụ ý là bằng chứng công trình/đo nhiệt.

**Cần kiểm chứng:** DOE `solar-rooftop-potential`, `solar-photovoltaic-system-design-basics`, `guide-fire-safety-solar-systems`; claim về nhiệt qua mái, thông gió mặt sau module, tác động theo vật liệu, emissivity khi đo hồng ngoại và tính đại diện của phép đo. Nguồn trong file chưa được xác minh là hỗ trợ các claim nhiệt.

## Đối chiếu cannibalization với nhóm đã công khai

| Nháp | Bài công khai gần nhất trong snapshot | Rủi ro và ranh giới biên tập đề xuất |
|---|---|---|
| 009 | 136 (nguyên lý, vai trò pin/inverter); 138 (bản tin thị trường, không cùng câu hỏi) | Thấp–vừa. Giữ câu hỏi hiệu suất khi nhiều mây và nhật ký theo thời tiết; không dạy lại nguyên lý pin/inverter của 136. |
| 010 | 142 (chọn hòa lưới/hybrid/độc lập; mất lưới), 136 (tổng quan) | Cao. 142 đã nói hòa lưới dừng và backup cần nhánh riêng. Giữ 010 là lời giải cho “vì sao có nắng mà vẫn mất điện” với sơ đồ an toàn; link 142 cho quyết định loại hệ. |
| 011 | 137 (10 thông số datasheet), 139 (kW/kWp/kWh), một phần 140 | Vừa. 011 nên xử lý fit mái/diện tích/mức Wp theo đúng model; 137 giữ vai trò giải mã thông số. Không lặp bài giảng Voc/MPPT. |
| 012 | 138 (IEA PVPS 2026, 2,96 TW/690 GW và xu hướng thị trường) | Vừa. Cả hai là tin IEA/toàn cầu nhưng khác báo cáo/kỳ/câu hỏi. Phải giữ phạm vi Renewables 2026 và không lặp các số 2025 của PVPS 138; phân biệt công suất mới với tỷ trọng sản lượng. |
| 013 | 141 (cùng kWh, lịch tải khác; lựa chọn hòa lưới/hybrid/backup) | Rất cao. Cùng nhiệm vụ/luận điểm. Cần đổi 013 thành một bài có nhiệm vụ độc lập hoặc không phát hành như bài riêng sau quyết định biên tập. |
| 014 | 140 (inverter làm gì/MPPT), 142 (chọn loại hệ), 010 (cúp điện) | Cao. 014 chỉ nên là checklist đối chiếu báo giá/model hòa lưới-vs-hybrid; không lặp nguyên lý inverter hay ma trận chọn hệ của 142. |
| 015 | 137 (datasheet, hiệu suất/thông số), 143 không liên quan trực tiếp | Thấp–vừa. Khác biệt thẩm mỹ/màu là độc lập; link 137 để người đọc so thông số, không lặp giải thích datasheet. |
| 016 | 141 (bài tập hóa đơn 12 tháng và giờ dùng); 136/139 ít liên quan | Vừa–cao. Có thể bổ trợ bằng “cách chuẩn bị bộ dữ liệu” chi tiết, nhưng tránh lặp checklist 12 tháng/lịch tải trong 141; cập nhật link nội bộ đang trỏ slug 013. |
| 017 | 143 (hướng dẫn mua đèn, vùng sáng, watt/Wh/lumen/lux) | Cao. 143 đã nêu W/lumen/lux và tiêu chí mua. 017 chỉ nên là bài giải nghĩa đại lượng bằng một ví dụ đo/đọc nhãn; liên kết 143, không lặp checklist mua. |
| 018 | 141 (tự dùng theo lịch tải), 138 (thị trường, không cùng bài toán) | Vừa. Giữ marginal economics/điện tăng thêm có ích; không làm lại hướng chọn theo lịch tải. Số hoàn vốn cần nguồn và giả định rõ. |
| 019 | Chưa thấy bài 136–143 tương đương | Thấp. Góc khảo sát mái riêng; giữ giới hạn không hướng dẫn thi công. Có thể link 021 cho nhiệt mái. |
| 020 | 137 (đọc datasheet), 138 (xu hướng toàn cầu) | Vừa. Khác biệt công nghệ cell là độc lập nếu có dữ liệu; 020 giải thích kiến trúc TOPCon/HJT/BC, 137 giúp đọc con số trên datasheet. Tránh tuyên bố công nghệ nào tốt hơn chung. |
| 021 | Không có bài 136–143 cùng chủ đề; 019 gần ở loại mái nhưng nhiệm vụ khác | Thấp. 021 nói nhiệt/thoải mái; 019 nói khảo sát kết cấu/an toàn. Giữ hai bài phân vai và nối link khi cần. |

## Checklist ready-to-edit và vấn đề Admin cần xác nhận

### Sửa ngay theo mức rủi ro

1. **Hold số liệu/claim:** 012 (toàn bộ số liệu IEA, đặc biệt 43%/gần 45%); 018 (mô hình kinh tế/hoàn vốn); 011 (so sánh mức công suất nếu không có model); 020 (so sánh kiến trúc/công nghệ); 015 (claim màu-nhiệt/BIPV); các claim an toàn điện/mái ở 010/019/021 cần reviewer kỹ thuật.
2. **Giải cannibalization trước khi hoàn thiện:** 013 so với 141; 017 so với 143; 010/014 so với 142 và nhau; 016 so với phần hóa đơn trong 141. Phân vai cụ thể đã nêu ở bảng trên.
3. **Sửa liên kết/asset:** 016 link hiện tại trỏ slug nháp 013, cần thay bằng URL công khai đúng nếu mục tiêu là bài 141. Kiểm tra ảnh/alt/credit ở 010–012, 015–021 và chọn ảnh cho 009; đặc biệt brief ảnh 010 và 011 không khớp mô tả ảnh trong nội dung.
4. **Dọn cấu trúc/nội dung:** rút lặp ở 016, 021; tinh gọn 017; phân biệt rõ ví dụ giả định với dữ kiện ở 013/018; không biến ví dụ thành case khách hàng thực.
5. **SEO và liên kết:** chuẩn bị summary/description theo chuẩn voice, kiểm slug so với CSV trước khi nhập. Bổ sung internal link chỉ khi làm rõ hành trình đọc. Các Markdown không có product links; không thêm link sản phẩm nếu chưa có model phù hợp và đích đến được duyệt.

### Phân loại “thiếu trong file” và “thiếu tính năng Admin”

- **Thiếu dữ liệu biên tập trong file:** summary/meta description không hiện diện trong Markdown; 009 không có ảnh chọn, 010–015 status ảnh “chưa chọn” theo CSV; các nháp không có product links; phần lớn không có internal link. Đây là thiếu hoặc chưa điền dữ liệu của từng bài, chưa đủ chứng cứ để kết luận Admin không hỗ trợ trường tương ứng.
- **Cần xác nhận trong Admin/workflow trước nhập:** có trường SEO title/description/slug và preview canonical; trường ảnh cover với alt, credit/license và trạng thái duyệt; trường ảnh nội dung/caption/credit; liên kết bài liên quan/sản phẩm; trạng thái review nguồn/technical approval; preview internal URL để tránh link nháp. Nếu Admin đã có các trường này thì đây là checklist nhập liệu/QA; nếu không có, đề xuất ghi nhận yêu cầu tính năng thay vì tự suy đoán hoặc thay đổi hệ thống.
- **Không có bằng chứng thiếu tính năng cụ thể:** không đọc mã Admin hoặc giao diện quản trị theo phạm vi giao; do đó không kết luận tính năng nào thực sự chưa tồn tại. Không dùng các trường rỗng trong Markdown để suy ra schema hoặc khả năng CMS.

## Kết luận bàn giao

Ưu tiên cho biên tập viên: đối chiếu số liệu có rủi ro cao trước (012, 018), cắt trùng intent với bài đã công khai (013, 017, 010/014, 016), sau đó sửa asset/link/SEO và tinh gọn các bài có phần lặp (016, 021). Toàn bộ 13 mục vẫn ở trạng thái chưa biên tập/chưa duyệt theo CSV; không bài nào được review này phê duyệt xuất bản. Kết quả này là đọc nội dung repo và snapshot tại thời điểm ghi trên, không phải kiểm tra production hay xác minh độc lập nguồn/claim.
