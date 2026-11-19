# PTS Solar — STOP-SHIP REVIEW: Chất lượng content từ góc nhìn độc giả
**Ngày:** 16/11/2026 · **Trạng thái:** CHƯA ĐƯỢC PHÉP XUẤT BẢN · **Người đánh giá:** phản biện độc lập trong phiên làm việc, không tự phê duyệt nội dung

## 1. Phạm vi & giới hạn

- Đối chiếu snapshot API của 20 bài đang công khai: \`docs/07-features/content-public-snapshot-2026-11-16.json\` với các bản nháp mới nhất ở \`backend/resources/editorial/2026-11-16/revisions/\` và 7 bản V2 ở \`reader-v2/\`. Bài 107 được HOLD vì gần trùng bài 102. Bài 101 là bản nháp cũ bị HOLD.
- Đây là **đánh giá biên tập và tính hữu ích dự đoán**, không phải nghiên cứu có người dùng thật, không có dữ liệu GA4/GSC, click map, thời gian đọc, phản hồi khách hàng, thực địa, hay xác nhận các thông số sản phẩm.
- Đã xem cách mở bài, tiêu đề, đề mục, lời khuyên và hình/nguồn theo file hiện có. Kết quả đếm dựa trên Markdown, không phản ánh ảnh bìa hay đề xuất sản phẩm động của CMS.
- **Không sửa DB, CMS, code sản xuất, URL, trạng thái bài, nội dung cũ, hoặc tự deploy**. Bản nháp 19/20 hoàn thành về mặt file, **0 bản được cho phép tự xuất bản**.

## 2. Kết luận quyết định

**STOP SHIP — không đưa cả 19 bản nháp lên production.** Tỷ lệ hoàn thành file, số từ, tình trạng URL 200, số liên kết và 6/6 unit test của lệnh update KHÔNG phải bằng chứng chất lượng nội dung.

Quan sát định lượng từ 19 Markdown hiện có (dùng phiên bản V2 nếu có):
- **3/19 bài có ảnh nội tuyến trong thân bài** (136, 137, 138); **7/7 bài V2 không có ảnh nội tuyến**. Ảnh cover của CMS không tính và chưa kiểm tra bản quyền/độ phù hợp.
- Những bài câu hỏi phổ thông bị kéo dài: 139 (887 từ, 7 H2) để phân biệt ba đơn vị; 143 (881 từ, 6 H2) để giải thích đọc nhãn đèn; 136 (1.162 từ, 8 H2) cho nguyên lý điện mặt trời. Độ dài không tự động làm bài kém; đây là cảnh báo về tầng thông tin và mật độ.
- 137 là dạng tài liệu kỹ thuật dài khoảng **1.685 từ, 14 H2**, có ví dụ datasheet LONGi cụ thể và infographic: **không xếp hạng kém chỉ vì dài**. Nên thiết kế “đọc nhanh/chuyên sâu” và giữ giá trị tra cứu.
- Các URL 100 và 109 đang nói về **phân phối độc quyền**, trong khi V2 chuyển thành **cẩm nang chọn sản phẩm**; 110 là tiêu đề “TOP 1” nhưng bài là hướng dẫn đánh giá chip. Đây là rủi ro niềm tin và tìm kiếm cao.
- 102, 104, 107, 111 nhắc lại cùng lợi ích đèn năng lượng mặt trời ở nhiều góc. Việc tồn tại nhiều bài không chứng minh chắc chắn cannibalization trong SERP, nhưng có rủi ro cao; cần dữ liệu GSC trước khi merge/301.

## 3. Vì sao khách hàng không muốn đọc?

1. **Sai lời hứa từ tiêu đề:** tìm thông báo hợp tác, mở ra một bài tư vấn mua hàng không liên quan đến thông báo. Tình trạng này làm nội dung trở nên thiếu trung thực dù các lời khuyên có thể đúng.
2. **Thông tin nguyên bản quá ít:** phần lớn “hãy yêu cầu bảng thông số”, “cần hỏi nhà cung cấp”, “nên so sánh báo giá”. Người đọc mong ở PTS một bài có bảng thông số của 2 model thực tế, hình bố trí, ảnh thực/ghi chú kỹ sư, hoặc bảng tính giải quyết tình huống; việc dặn người đọc tự đi tìm chưa cung cấp được giá trị của chuyên gia.
3. **AI được thưởng vì làm đúng form:** đủ đề mục, checklist, lưu ý, nguồn, SEO, CTA; không có câu hỏi “đọc xong tôi biết gì mà đối thủ chưa dạy?” hay “thành quả cụ thể người đọc có thể mang theo?”.
4. **Rào an toàn bị biến thành văn phong:** vì tránh claim thiếu căn cứ nên đoạn nào cũng chứa “tùy model”, “cần xác minh”, “không mặc định”, “hãy hỏi”. Đây là ghi chú fact-check cần xử lý trước khi viết, không phải để phân tán trong mọi đoạn.
5. **Các bài tự cạnh tranh cùng ý định tìm kiếm:** 102/104/107/111; 136/140/142 có phần giải thích inverter/mất điện; 106/109 đều về Blue Carbon dự án.
6. **Một cỡ bài cho mọi nhu cầu:** tin dữ liệu 138 không nên giống tutorial; câu hỏi 139 nên có giải đáp nhanh + visual; datasheet 137 cần lớp chuyên sâu; bài đối tác 108 phải nói đúng lợi ích và điều khoản hiện có.
7. **Quá nhiều chữ, ít chứng cứ có thể nhìn/so sánh:** thiếu ảnh sản phẩm rõ mã, screenshot datasheet, sơ đồ tự giải thích, bảng so sản phẩm đã xác minh và tình huống có dữ liệu thực.
8. **“SEO” bị hiểu như quy trình tạo chữ:** traffic hữu ích cần nội dung giải quyết intent và bổ sung giá trị mới; không phải số từ hoặc tần suất đăng 2–3 bài/ngày.

## 4. Ma trận 20 bài đang live — phán quyết biên tập (KHÔNG phải phán quyết SEO dựa trên dữ liệu traffic)

| ID | Loại bài/intent đúng | Lỗi bản nháp mới nhất | Quyết định |
|---:|---|---|---|
| 100 | Thông báo quan hệ Sharp có bằng chứng **hoặc** cẩm nang chọn Sharp trên URL đúng intent | H1 hiện tại “phân phối độc quyền 2024” nhưng V2 chỉ dạy đọc mã tấm pin, không chứng cứ hợp tác, 0 ảnh | **HOLD nghiêm trọng**; không thay body khi chưa chọn chiến lược URL, chứng cứ và nội dung |
| 101 | Văn bản thông báo Blue Carbon, tin doanh nghiệp | Bản nháp còn nguyên giọng “trang này lưu…”; cùng intent với 109 | **HOLD**; kiểm chứng chứng từ, rà GSC, không tự 301 |
| 102 | “Vị trí nào phù hợp lắp đèn NLMT?” — bài chủ đạo cho khách mới | Hướng thực tế có ích nhưng 713 từ, chỉ dựa trên giả định, 0 ảnh minh họa vị trí nắng/bóng; chồng 104/111 | **REDESIGN** dựa trên ảnh vị trí thật, bảng phân nhóm và ví dụ cụ thể |
| 103 | Bản tin lịch sử về chỉ đạo ngày 10/6/2023 | V2 đã chuyển về tin đúng loại, nhưng cần kiểm tra nguồn, ngày và câu chuyện; không được biến chỉ đạo 2023 thành quy định 2026 | **CONDITIONAL KEEP** sau xác minh nguồn, nhãn lưu trữ tin |
| 104 | “4 lợi ích” đèn — ý định gần như bài 102/111 | Liệt kê lợi ích đã biết, 7 H2, giả định dài, ít lợi ích độc quyền cho người đọc | **MERGE CANDIDATE** với 102, nghiên cứu GSC trước khi redirect |
| 105 | Chẩn đoán lỗi/bảo trì đèn dân dụng | Có bước hữu ích nhưng 6 H2, 0 ảnh lỗi thật, 0 quy trình xử lý theo triệu chứng, nguy cơ chỉ là danh sách lưu ý | **REBUILD** dạng “triệu chứng → kiểm tra an toàn → hành động/điểm dừng” với ảnh |
| 106 | Hồ sơ dự án đèn Blue Carbon | V2 thực chất là danh sách tài liệu “hãy xin”, không có ví dụ sơ đồ bố trí, IES/LDT hay mẫu nghiệm thu thật | **RESEARCH/REDESIGN** với model, hồ sơ được phép và người kỹ thuật duyệt |
| 107 | Trùng gần nguyên nội dung 102 | Không có bản nháp độc lập; không nên công bố thêm | **HOLD/SEO DECISION**; kiểm tra traffic/backlink trước merge/301 |
| 108 | “Được gì khi hợp tác cùng PTS?” — trang B2B bán hàng | V2 lại dạy đại lý “cách đọc báo giá”, không trả lời PTS hỗ trợ gì thật, chính sách nào có hiệu lực | **HOLD** cho đến khi có chính sách hợp tác hiện hành và bằng chứng |
| 109 | Tin đối tác Blue Carbon, quyền phân phối | V2 là bài khác: mẹo lắp đèn công trình. Sai intent, overlap 106, title vẫn claim độc quyền | **HOLD nghiêm trọng**; chốt chứng từ thương mại/URL/SEO trước |
| 110 | Đánh giá claim “Cree TOP 1” và chất lượng bộ đèn | Hướng nội dung V2 đúng hơn, nhưng chưa có model đèn dùng chip Cree xác minh, bài sát intent 143 | **MERGE/RESEARCH**; bỏ claim TOP 1, nghiên cứu dữ liệu chip và đèn |
| 111 | “8 lý do” dùng đèn NLMT | 8 ý khá chung, gần 813 từ, nhiều điều đã nói 102/104, không có góc mới | **MERGE CANDIDATE**; giảm trùng, dùng cho FAQ hoặc series |
| 136 | Điện từ tấm pin đến thiết bị sử dụng | Có sơ đồ nội tuyến, kiến thức nền đúng hướng, 8 H2/1.162 từ quá rộng cho entry-level; lan sang mất điện và chọn loại hệ | **KEEP CORE / TRIM** thành sơ đồ 4 bước, tách nhánh sang 142 |
| 137 | Datasheet tấm pin LONGi cụ thể | **Bài có chất liệu tốt nhất**: model, số liệu, PDF nguồn, infographic; nhưng 14 H2/1.685 từ cần 2 lớp thông tin và đối chiếu model | **PRIORITY KEEP**; bảo vệ dữ liệu, cải thiện trình bày chuyên sâu, fact-check |
| 138 | Tin IEA PVPS 2026 | Dữ liệu và nguồn cụ thể, nhưng 8 H2/1.222 từ, tràn sang nhiều ngách, yếu “độc giả Việt Nam được gì” | **FOCUS / TRIM** 3–4 phát hiện + 1 đồ thị rõ mốc, tránh diễn giải vượt dữ liệu |
| 139 | kW/kWp/kWh cho người mới | Câu trả lời hay trong 40 từ đầu, sau đó 887 từ và 7 H2 dễ làm phức tạp vấn đề | **REDESIGN** thành 1 visual + 3 ví dụ + 2 lỗi thường gặp; phần chi tiết có thể gập |
| 140 | Inverter là gì cho chủ nhà | Tóm cơ chế được, nhưng phần MPPT/ghép chuỗi trùng 136/137/142, chưa gắn ví dụ inverter thực | **KEEP/REFOCUS** vào 3 chức năng người mua phải hiểu, có ảnh/sơ đồ |
| 141 | Nhà dùng nhiều điện ban đêm nên lắp solar không? | Ý định rất thực tế, có tình huống hai nhà 600 kWh, nhưng giả định thay cho dữ liệu phụ tải thật; thiếu visual lịch dùng điện | **PILOT STRONG**; tập trung quyết định và biểu đồ ngày/tối, không phán chắc hoàn vốn |
| 142 | Chọn hòa lưới, hybrid hay độc lập | Ý định quyết định rất rõ, có cảnh báo backup đúng; nhưng đòi hỏi sơ đồ nhánh điện và phụ tải, không chỉ chữ | **PILOT STRONG**; cây quyết định + ví dụ tải từ tài liệu đã duyệt |
| 143 | Đọc quảng cáo “200W/300W” đèn NLMT | Vấn đề khách hàng thật; phân biệt W, Wh, lumen tốt nhưng 881 từ, không có so sánh 2 SKU có dữ liệu xác thực | **PILOT BEST**; lấy 2 SKU thực, ảnh nhãn, thông số đối chiếu, kết luận theo tình huống |

**Lưu ý:** “MERGE CANDIDATE” không có nghĩa được phép xóa/redirect ngay. Phải xem Search Console và URL/backlink hiện tại.

## 5. Ba ví dụ lỗi cốt lõi

**Bài 100 (lỗi chiến lược nội dung):**
- Người đọc thấy H1: “PHÚC THỊNH SOLAR PHÂN PHỐI ĐỘC QUYỀN TẤM PIN SHARP ...”
- Bài V2 mở: “Một mã tấm pin chỉ có ích khi khớp với mái nhà và inverter dự kiến.”
- Cả hai có thể hữu ích riêng nhưng ghép cùng URL là sai nhiệm vụ. **Không thể chữa bằng cách làm câu đầu hấp dẫn hơn.**

**Bài 108 (lỗi lời hứa với khách):**
- Người tìm “Được gì khi hợp tác cùng PTS?” cần thông tin xác thực: đối tượng, danh mục/nguồn hàng, hỗ trợ, công nợ/giao hàng/bảo hành, kênh liên hệ, tiêu chí làm đại lý.
- Bản V2 có 5 H2 về những gì **đại lý nên tự kiểm tra**, nhưng chưa nói được quyền lợi có văn bản của PTS. Đây là bài chung chung đổi tên công ty là dùng được ở mọi nơi. **Không được xuất bản thay landing hợp tác.**

**Bài 137 (có chất liệu, chưa có thiết kế đọc):**
- Có datasheet mã LONGi cụ thể, ảnh infographic, các thông số thực sự có thể kiểm tra. Đây là lợi thế cần bảo tồn.
- Nên có bảng 5 dòng, ảnh chú thích thông số trên PDF, đường dẫn mở bản PDF, phân lớp “người mua/thiết kế”; nhiều nội dung sâu không sai nếu điều hướng rõ.
- **Không cắt số liệu hãng chỉ vì quá dài**; tối ưu theo nhu cầu từng nhóm độc giả.

## 6. Quy trình mới — sửa đầu vào và khâu phán quyết, không “thay prompt AI”

### Gate A — brief có sản phẩm/độc giả/đầu ra cụ thể
Bắt buộc định nghĩa: (1) người đọc, (2) tình huống thực, (3) câu hỏi duy nhất, (4) điều họ làm được sau đọc, (5) bài này khác trang hiện có, (6) asset nguyên bản sẽ có. Không có asset/original insight -> **chưa đặt lịch xuất bản**, dù AI viết được 2.000 từ.

### Gate B — chứng cứ chuyên ngành tối thiểu
Lấy hồ sơ cho đúng loại bài: model + datasheet + ảnh nhãn, ảnh lắp có quyền, mẫu báo giá được ẩn danh/được phép, sơ đồ, bài test, biểu đồ sản lượng có nguồn, tư vấn kỹ thuật thật được kiểm duyệt; không tự dựng case PTS giả. Với bài giải thích khái niệm có thể đủ sơ đồ nguyên bản + nguồn uy tín; không bắt buộc cần trải nghiệm công trình giả.

### Gate C — viết một phiên bản độc giả hiểu trong 30 giây
Câu đầu trả lời, minh họa quan trọng nhất lên trước, có góc nhìn cụ thể. Tùy loại có thể là tin ngắn, how-to 4 bước, guide so sánh 2 SKU, case study, FAQ, hoặc tài liệu tra cứu sâu. Không áp một bố cục 6 H2 cho mọi bài. Không tự ràng buộc số từ để “chuẩn SEO” (Google không có số từ ưu tiên chung).

### Gate D — phản biện như người mua và kiểm tra sự thật
Reviewer độc lập đóng vai khách: đọc title, 1 màn hình đầu và 1 phút lướt. Trả lời rõ:
- Có gặp nội dung đúng như tiêu đề đã hứa không?
- Bài giải đáp được câu hỏi nào ngay, có làm tôi hiểu/chọn được thứ gì không?
- Tôi có học được điều **chưa dễ tìm trên bài khác**, hay chỉ được yêu cầu tự hỏi nhà bán?
- Có nguồn trực tiếp cho thông số và tuyên bố nhãn hàng không?
- Ảnh/bảng có giúp ra quyết định, chứ không phải lấp chỗ trống?
- Đọc xong có muốn lưu lại hoặc gửi người thân/đồng nghiệp không?
Nếu không, **REJECT**, không sửa lặt vặt câu từ để giả đạt.

### Gate E — publish pilot và đo phản hồi thật
Sau khi người thật phê duyệt, chỉ xuất bản một bài pilot, kiểm tra giao diện mobile/desktop, URL, canonical, schema, sự phù hợp image/credit. Thu dữ liệu thực theo thời gian ở Search Console (impressions, CTR, queries) và GA4 (engagement, scroll, CTA), cùng phản hồi sale/khách. Đừng phán xếp hạng/retention từ dữ liệu chưa có.

## 7. Lệnh dừng vận hành được khuyến nghị

- **Không chạy** \`pts:content-batch-patch --apply\` trên bất kỳ bản nháp nào trong loạt này.
- **Không đặt KPI xuất bản 2–3 bài/ngày** cho đến khi một bài pilot đạt chuẩn reader test + technical/source review. KPI chuyển sang nội dung được duyệt và có kết quả hữu ích.
- **Không sửa ngầm title/slug/301** của 100/101/107/109/110/111; phải có chiến lược SEO từ GSC và chứng từ thương mại.
- 13 bài ẩn 144–156 giữ nguyên, rà soát bằng tiêu chí mới trước khi xuất bản.
- Bảo toàn file bản nháp và snapshot cho đối chiếu; mọi sửa sau này tạo V3 theo brief mới, không ghi đè bản đã lưu.

## 8. Thử nghiệm nên làm đầu tiên

**Bài 143: “Đừng chọn đèn NLMT chỉ theo số W trên quảng cáo”.**

Yêu cầu tối thiểu trước khi viết:
1. Chọn **hai SKU đèn bán thật** từ danh mục PTS, có ảnh/nhãn/model chính xác; lấy **datasheet hãng** đối chiếu công suất đèn thực, lumen (nếu có), pin Wh, panel W, mode sáng, IP/bảo hành. Nếu thiếu mục nào phải ghi “không công bố” thay vì bịa.
2. Làm **một bảng so sánh có cột nguồn chứng cứ**, không là bảng “những câu hỏi nên hỏi người bán”.
3. Vẽ sơ đồ 1 hình “W là gì – Wh là gì – lumen là gì” hoặc chụp cắt nhãn thực tế có bản quyền.
4. Ghi 2–3 trường hợp sử dụng (cổng, sân, đường nội bộ) với hướng chọn có điều kiện, không bịa lux/giờ sáng hay test thực.
5. Có câu trả lời + hình chính trong màn hình đầu; 1 CTA nhẹ đến sản phẩm thực có model và stock được xác nhận.
6. Bắt buộc qua biên tập viên + kỹ thuật + người phụ trách dữ liệu sản phẩm trước khi xuất bản.

**Không có hai SKU/datasheet đủ thông tin?** Không tự viết bài “so sánh thực tế”; chuyển pilot sang 139 với infographic rõ và phép tính có kiểm chứng.

## 9. Nguồn chuẩn đối chiếu

- Google Search Central – People-first: https://developers.google.com/search/docs/fundamentals/creating-helpful-content
- Chuẩn đã có trong repo: \`docs/07-features/editorial-voice-and-review-standard.md\`
- Snapshot: \`docs/07-features/content-public-snapshot-2026-11-16.json\`
- Batch QA trước: \`docs/07-features/pts-content-20-batch-audit-20261116.md\`

**Chốt:** Đây là phản biện đầu vào, chưa phải chứng nhận bài nào “chuẩn SEO”; không có dữ liệu người dùng hay GSC trong vòng này, không có quyền kết luận thứ hạng thực.
