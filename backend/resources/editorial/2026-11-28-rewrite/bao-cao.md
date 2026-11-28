# Báo cáo rà soát và viết lại bài đang đăng đọc rời rạc (28/11/2026)

## 1. Tổng quan

- **Tổng số bài đã đọc rà soát:** 33 bài trong `backend/resources/editorial/2026-11-28-rewrite/input/`.
- **Bài mẫu chuẩn (không sửa theo đề bài):** `139`, `157`.
- **Số bài chọn viết lại vào `out/<ID>.html`:** 10 bài (`103`, `138`, `146`, `148`, `149`, `150`, `151`, `152`, `155`, `156`) — đều là các bài thực sự rời rạc (hàng chục đoạn văn 1 câu nối tiếp nhau hoặc câu cụt tách riêng thành đoạn), lặp ý nhiều lần giữa các mục/bảng, hoặc chèn bảng và câu dặn dò lạc đề.
- **Số bài giữ nguyên:** 23 bài (gồm 2 bài mẫu và 21 bài tuy có đoạn ngắn nhưng mạch bài rõ ràng, đi thẳng đúng câu hỏi của tiêu đề).
- **Kết quả chấm `python3 tools/editorial-voice/doc-giong.py`:** Cả 10/10 bài trong `out/` đều đạt **PASS**, giữ nguyên toàn bộ số liệu gốc, liên kết `href`, thẻ `<img>`, mục *Đọc thêm* và *Nguồn tham khảo*, bỏ dòng chú thích `<!-- -->` đầu tệp và không thêm `class`/`style`.

---

## 2. Chi tiết 10 bài đã viết lại (`out/<ID>.html`)

### Bài 155 — TOPCon, HJT, BC (Back Contact) khác nhau ra sao? *(bắt buộc)*
- **Lỗi của bản đang đăng:**
  - Cắt vụn thành 35 thẻ `<p>`, phần lớn là các đoạn 1 câu rời rạc không nối ý với nhau.
  - Lặp lại tóm tắt 3 công nghệ ở dòng 36 ngay sau khi đã có bảng so sánh ở đầu bài và các câu mô tả từng công nghệ.
  - Chèn nguyên bảng 4 ký hiệu điện áp/dòng điện (`Voc`, `Vmp`, `Isc`, `Imp`) vào giữa bài so sánh cấu trúc cell, sau đó là gần 20 câu đơn dặn dò rời rạc nhảy qua lại giữa: mã hàng trên báo giá (lặp 3 lần ở dòng 39, 88, 89), kích thước/lối bảo trì (lặp 3 lần ở dòng 38, 71, 76), bóng che và MPPT (lặp 4 lần ở dòng 35, 71, 77, 81), mô phỏng thời tiết và hồ sơ bảo hành.
- **Đã sửa và gộp gì:**
  - Gom các câu đơn ở mục 1 thành 3 đoạn văn hoàn chỉnh, mỗi đoạn giải thích trọn vẹn một cấu trúc cell (`TOPCon`, `HJT`, `BC`).
  - Gom phần đọc datasheet thành 2 đoạn mạch lạc: đoạn 1 nói về việc tra đúng mã hàng, phân biệt hiệu suất cell với hiệu suất toàn tấm và sự khác biệt vật liệu kính/khung; đoạn 2 nói về giả định phản xạ mặt sau của tấm hai mặt, kích thước/tải mái của tấm công suất cao và nguyên tắc không ghép lẫn cấu trúc cell vào cùng một chuỗi.
  - Gom phần mái nhà và bảo hành thành 2 đoạn: đoạn 1 đi theo từng điều kiện mái (mái chật, mái nóng, mái có bóng che một phần, mái gần biển/ăn mòn); đoạn 2 gom toàn bộ lưu ý khi chốt báo giá, điều khoản bảo hành sản phẩm/công suất và lưu hồ sơ nghiệm thu.
- **Đã cắt gì và vì sao (kèm dữ kiện kỹ thuật đã cắt):**
  - **Cắt bảng ký hiệu điện áp/dòng điện (`Voc`, `Vmp`, `Isc`, `Imp`)** và câu yêu cầu kỹ thuật viên tính điện áp hở mạch khi trời lạnh, dòng ngắn mạch khi bức xạ cao theo giới hạn inverter (dòng 40–70, 72): lạc đề trong bài so sánh công nghệ cell TOPCon/HJT/BC; nội dung này thuộc bài *Cách đọc datasheet tấm pin năng lượng mặt trời: 10 thông số quan trọng* đã được dẫn ở mục *Đọc thêm*.
  - **Cắt câu dặn ghi nguồn thời tiết, góc nghiêng, hướng mái và tổn hao giả định khi chạy mô phỏng** (dòng 73–74): lạc sang quy trình nhập liệu phần mềm mô phỏng sản lượng.
  - **Cắt câu lặp dòng 36** (`Với TOPCon, xem hiệu suất... Với HJT... Với BC...`) và các câu lặp về mã hàng, bóng che, lối bảo trì sau khi đã gộp ý vào đúng đoạn.
  - Không cắt bất kỳ con số nào của bài gốc (`620 W` được giữ nguyên).

### Bài 156 — Điện mặt trời có làm mái nhà nóng hơn hay mát hơn?
- **Lỗi của bản đang đăng:**
  - Lặp ý nặng giữa các mục: đoạn cuối mục 2 (`Khe hở phải đi cùng cố định an toàn`, dòng 16–18) bỗng quay lại giải thích nguyên lý tấm pin che nắng, bức xạ làm nóng tấm và khe gió tản nhiệt (đã nói ở mở bài và dòng 4–6 của mục 1), rồi nói trước cách chọn vùng mái tương đồng để đo nhiệt độ mặt dưới mái và không khí trong phòng; ngay sau đó mục 3 (`Đo tại công trình thay vì đoán`, dòng 20–22) lặp lại y hệt các ý đo này.
  - Toàn bài bị chia thành 22 đoạn ngắn 1–2 câu rời nhau.
- **Đã sửa và gộp gì:**
  - Đưa toàn bộ cơ chế truyền nhiệt, che nắng trực tiếp và lưu thông gió ở hai đầu khe về đoạn 1 của mục 1; gom ảnh hưởng của từng loại vật liệu mái (mái tôn mỏng, mái bê tông, mái ngói) và quá trình nguội ban đêm vào đoạn 2 của mục 1.
  - Giữ mục 2 tập trung đúng vào yêu cầu cố định khung chịu lực, đi dây cáp, an toàn điện DC (đoạn 1) và những câu hỏi cần trao đổi kèm hình chụp khi khảo sát trước lắp đặt (đoạn 2).
  - Gom toàn bộ hướng dẫn đo thực tế tại công trình về mục 3 thành 2 đoạn mạch lạc (cách chọn hai vùng mái cùng hướng/vật liệu, đo cùng khung giờ trưa – chiều, giữ trạng thái cửa/điều hòa; cách dùng cảm biến hồng ngoại đo bề mặt và cảm biến không khí đo trong phòng).
- **Đã cắt gì và vì sao:**
  - Cắt các câu lặp lại nguyên lý khe thoáng/truyền nhiệt ở dòng 16–17 và câu lặp hướng dẫn đo ở dòng 18 (do đã gộp đầy đủ vào mục 1 và mục 3). Không mất dữ kiện hay con số nào.

### Bài 151 — Cách đọc hóa đơn tiền điện trước khi thiết kế hệ thống
- **Lỗi của bản đang đăng:**
  - Chia vụn thành 23 đoạn văn, hầu hết mỗi đoạn chỉ có 1 câu.
  - Câu kiểm tra tủ điện, dây dẫn, tiếp địa, chống sét và sức chịu lực mái (dòng 65) bị đặt lạc vào cuối mục *Ghi giờ chạy thiết bị*.
  - Mục cuối (*Chuẩn bị gì cho buổi khảo sát?*) lặp lại yêu cầu gom hóa đơn, bảng kWh, ghi chú bất thường, danh sách thiết bị lớn và giờ chạy đã nêu ở 3 mục trước, rồi chèn một câu cảnh báo thi công lắp đặt rời rạc (dòng 70).
- **Đã sửa và gộp gì:**
  - Gộp mục 1 và mục 2 thành một mạch liền lạc hướng dẫn đọc kỳ điện, số ngày, chia trung bình `kWh/ngày` (với ví dụ hai kỳ 900 kWh dài 28 ngày và 33 ngày), gom đủ 12 tháng và ghi chú các tháng biến động (sửa nhà, mở cửa hàng, bơm tưới, khách ở dài ngày, nhà vắng) ngay phía trên bảng mẫu 12 tháng (`01/2026` – `12/2026`).
  - Gom mục *Ghi giờ chạy thiết bị* thành 2 đoạn rõ ràng: đoạn 1 hướng dẫn chủ nhà ghi khung giờ thiết bị lớn (ví dụ điều hòa 10–15 giờ, bơm tưới buổi trưa, bình nước nóng 21 giờ) và lịch theo mùa; đoạn 2 nói về việc lắp thiết bị đo phụ tải cho xưởng nhỏ, quán hoặc máy bơm lớn.
  - Chuyển ý kiểm tra tủ điện, tiếp địa, chống sét, điểm đấu nối và sức chịu lực mái về đúng mục *Chuẩn bị gì cho buổi khảo sát?*, nối với việc chụp ảnh mái và che thông tin cá nhân trên hóa đơn.
- **Đã cắt gì và vì sao (kèm dữ kiện kỹ thuật đã cắt):**
  - **Cắt câu cảnh báo thi công ở dòng 70** (`Đấu nối inverter, thay aptomat và kiểm tra điểm neo trên mái cần người có chuyên môn; tấm pin tạo điện DC khi có ánh sáng.`): lạc đề vì bài này hướng dẫn chủ nhà đọc hóa đơn tiền điện và chuẩn bị hồ sơ trước khi thiết kế, không phải bài hướng dẫn tự đấu nối điện trên mái.
  - **Cắt các cụm lặp** về gom hóa đơn, ghi chú bất thường và ghi lịch nhà vắng bị nhắc lại ở dòng 64 và 67. Giữ nguyên 100% con số (`900 kWh`, `28 ngày`, `33 ngày`, `12 tháng`, `01/2026`, `02/2026`, `12/2026`, `10 giờ`, `15 giờ`, `21 giờ`).

### Bài 152 — Lumen, lux và watt khác nhau thế nào khi chọn đèn?
- **Lỗi của bản đang đăng:**
  - 25 thẻ `<p>` ngắn 1–2 câu rời rạc.
  - Ý "hỏi công suất/lumen của cả bộ đèn theo từng chế độ sáng" bị lặp lại 3 lần (dòng 11, 16, 20).
  - Ý theo dõi, đo và ghi vùng tối sau khi lắp vừa nằm ở mục giải thích lux (dòng 17–19) vừa nhảy xuống cuối mục so thông số mua đèn (dòng 27).
  - Hai câu cuối bài (dòng 28–29) tự dưng dặn cách ghép pin khác hóa học/khác tuổi và xử lý khi pin phồng, dây nứt, mạch hở — lạc khỏi chủ đề phân biệt ba đơn vị lumen, lux và watt.
- **Đã sửa và gộp gì:**
  - Sắp xếp lại thành 3 mục theo đúng mạch nhận thức của người mua đèn:
    1. *Watt nói về điện tiêu thụ và dung lượng pin*: giải thích watt, con số “200 W” quảng cáo, chế độ giảm sáng (20 W xuống 8 W) và gom phép tính ví dụ (20 W × 5 giờ + 8 W × 7 giờ = 156 Wh/đêm) cùng thông số bộ pin `12 V–20 Ah` (`240 Wh`) và tấm pin `40 W` về chung một chỗ để thấy rõ mối liên hệ giữa watt và pin lưu trữ.
    2. *Lumen và lux cho biết vùng sáng dưới mặt đất*: giải thích lumen và lux kèm phép tính ví dụ lối đi `2 m` × `10 m` = `20 m²` nhận `2.000 lm` cho ra `100 lx`, sau đó nối với cách bố trí đèn đều dọc lối đi, cổng xe, bậc thềm, góc nghỉ.
    3. *So thông số cả bộ đèn và kiểm tra sau khi lắp*: đoạn 1 gom toàn bộ thông số cần đối chiếu khi mua (watt, lumen theo chế độ giảm sáng, điều kiện đo, dung lượng pin, tấm quang điện, thời lượng “sáng 12 giờ” ở mức 100% hay cảm biến); đoạn 2 gom toàn bộ cách theo dõi 1 tuần và đo lux sau khi lắp.
- **Đã cắt gì và vì sao (kèm dữ kiện kỹ thuật đã cắt):**
  - **Cắt 2 câu cuối bài về bảo trì điện/ghép pin lưu trữ (dòng 28–29):** `Đèn ngoài trời cần vỏ, dây và đầu nối phù hợp môi trường ẩm. Không ghép pin khác hóa học hoặc khác tuổi, không để đầu nối ở chỗ đọng nước; ngừng dùng nếu pin phồng, dây nứt hoặc mạch hở.` — lạc đề khỏi bài giải thích đơn vị chiếu sáng lumen, lux, watt (yêu cầu cấp bảo vệ IP đã được giữ ở đoạn so thông số).
  - Giữ nguyên 100% tập số liệu gốc (`2 m`, `10 m`, `20 m²`, `2.000 lm`, `100 lx`, `200 W`, `20 W`, `8 W`, `5 giờ`, `7 giờ`, `156 Wh`, `12 V`, `20 Ah`, `240 Wh`, `40 W`, `12 giờ`, `100%`).

### Bài 146 — Tấm pin 600W, 620W, 650W và 720W: watt cao có tốt hơn?
- **Lỗi của bản đang đăng:**
  - Mở bài bằng một câu mệnh lệnh cộc lốc mà chưa trả lời câu hỏi của tiêu đề.
  - Ý "đặt kích thước đúng model lên bản vẽ/sơ đồ mái" bị lặp lại 5 lần (mở bài, mục 1, mục 2, bảng 2 và kết bài).
  - Cuối bài đặt 2 bảng liên tiếp, trong đó bảng 2 (`Trên báo giá | Cần xem`, dòng 52–81) lặp lại hoàn toàn 4 dòng nội dung vừa nêu ở các mục trên và ở bảng 1 (`Kích thước, khối lượng`, `Hiệu suất`, `Voc, Vmp, Isc, Imp`, `Bảo hành`).
  - Các đoạn văn bị tách thành từng câu đơn rời rạc.
- **Đã sửa và gộp gì:**
  - Mở bài trả lời thẳng: tấm 720 W chưa hẳn tốt hơn tấm 600 W, 620 W hay 650 W cho mọi mái nhà, mà phụ thuộc vào kích thước xếp trên mái và độ khớp với inverter.
  - Gộp mục 1 và mục 2 thành một mục xuyên suốt (*Tấm nào vừa với mái nhà bạn?*), đưa ví dụ hai mảng mái (Báo giá A dùng ít tấm lớn bị dư dải trống so với Báo giá B dùng nhiều tấm nhỏ phủ đều hai mảng) vào ngay sau phần giải thích kích thước tấm để làm rõ luận điểm.
  - Gom mục *So hiệu suất và tổng chi phí hệ thống* thành một đoạn hoàn chỉnh.
  - Ở mục cuối, giữ bảng giải thích 4 thông số điện (`Voc`, `Vmp`, `Isc`, `Imp`) khi đổi từ tấm 600 W sang 720 W và chuyển nội dung ghi mã hàng đầy đủ cho “Tấm 650 W” trên báo giá thành đoạn kết mạch lạc.
- **Đã cắt gì và vì sao:**
  - **Cắt khung bảng thứ hai ở dòng 52–81** vì 4/5 hàng của bảng này lặp lại nguyên văn các mục vừa trình bày (`Kích thước, khối lượng`, `Hiệu suất`, `Voc, Vmp, Isc, Imp`, `Bảo hành`); riêng ví dụ `“Tấm 650 W”` và yêu cầu ghi rõ hãng, mã model đầy đủ, phiên bản datasheet, kích thước, khối lượng, phạm vi bảo hành được giữ nguyên trong đoạn văn kết bài. Không mất bất kỳ con số hay dữ kiện nào.

### Bài 150 — Tấm pin màu đen có luôn tốt hơn tấm pin màu xanh?
- **Lỗi của bản đang đăng:**
  - Mở bài bằng câu mệnh lệnh, chưa trả lời câu hỏi trên tiêu đề.
  - Đưa dòng `Voc, Vmp, Isc, Imp | Có nằm trong giới hạn chuỗi và inverter không?` vào bảng tiêu chí của bài hỏi về màu sắc tấm pin.
  - Lặp ý trong các câu đơn sát nhau: định nghĩa tấm pin đen (cell đen, nền tối, khung đen đồng màu so với khung bạc) nêu ở dòng 3 rồi lặp lại ở dòng 41; ý "thông số nằm trong datasheet" nêu ở dòng 40 rồi lặp ngay ở dòng 41; lời khuyên "xin/xem ảnh mã hàng" nêu ở dòng 43 rồi lặp ngay ở dòng 44.
- **Đã sửa và gộp gì:**
  - Mở bài trả lời thẳng: tấm pin màu đen không mặc nhiên tốt hơn tấm pin màu xanh; màu sắc chỉ là cách hoàn thiện bề mặt, còn hiệu suất và độ bền phải xem theo model trên datasheet.
  - Gộp định nghĩa thương mại “all-black” ở mở bài với đoạn giải thích cấu tạo màu và yếu tố quyết định hiệu suất (vật liệu bán dẫn, cấu trúc cell, tiếp điểm, kính, tổn hao) thành một đoạn văn trọn ý dưới bảng.
  - Gộp phần so sánh datasheet, giá, bảo hành và lưu ý xin ảnh chụp thực tế dưới nắng tự nhiên / hỏi màu khung nhôm thành đoạn thứ hai.
  - Gộp 3 câu đơn ở mục nhiệt độ vận hành thành một đoạn hoàn chỉnh.
- **Đã cắt gì và vì sao (kèm dữ kiện kỹ thuật đã cắt):**
  - **Cắt hàng `Voc, Vmp, Isc, Imp | Có nằm trong giới hạn chuỗi và inverter không?` trong bảng tiêu chí** (dòng 27–28): lạc đề trong bài giải đáp sự khác nhau giữa tấm pin màu đen và màu xanh (việc tính chuỗi điện áp/dòng điện với inverter đã có ở bài đọc datasheet và bài chọn công suất tấm pin trong mục *Đọc thêm*).
  - **Cắt các câu lặp** về cấu tạo màu tấm pin đen và xem ảnh mã hàng.

### Bài 149 — Inverter hòa lưới và hybrid khác nhau thế nào?
- **Lỗi của bản đang đăng:**
  - Lặp nguyên một đoạn giải thích chống đảo lưới: ngay dưới bảng mở đầu (dòng 34–36) đã giải thích tấm pin vẫn nhận nắng nhưng inverter hòa lưới phải ngắt đầu ra khi mất lưới để chống điện chạy ngược vào đường dây đang sửa chữa và cần xác định tải ưu tiên; ngay mục kế tiếp (`Hòa lưới dừng cấp điện khi mất lưới`, dòng 38–41) lại viết lại từ đầu chính ý đó.
  - Hai mục sau gồm 11 đoạn văn 1 câu rời rạc.
- **Đã sửa và gộp gì:**
  - Mở bài nêu thẳng điểm khác biệt cốt lõi giữa inverter hòa lưới và hybrid (khả năng phối hợp pin lưu trữ và cấp điện khi mất lưới) trước khi dẫn vào bảng 4 nhu cầu.
  - Gộp phần giải thích bị lặp ở dòng 34–35 vào mục *Hòa lưới dừng cấp điện khi mất lưới* thành một đoạn duy nhất, giữ nguyên ví dụ mất điện lúc 12 giờ trưa khi mái còn nắng và yêu cầu nhánh dự phòng cho tủ lạnh, modem.
  - Gộp 5 câu đơn ở mục *Hybrid cần đúng pin và sơ đồ dự phòng* thành một đoạn văn hoàn chỉnh; gộp 4 câu đơn ở mục *Đọc báo giá cùng sơ đồ và datasheet* thành một đoạn văn hoàn chỉnh.
- **Đã cắt gì và vì sao:**
  - Cắt đoạn lặp giải thích chống đảo lưới nằm chơ vơ giữa bảng mở đầu và mục H2 thứ nhất (dòng 34–36) sau khi đã hợp nhất đủ ý vào mục 1 và mục 2. Giữ nguyên mọi con số và dữ kiện kỹ thuật.

### Bài 148 — Nhà dùng bao nhiêu điện mỗi tháng thì phù hợp lắp năng lượng mặt trời?
- **Lỗi của bản đang đăng:**
  - Mở bài lặp câu: dòng 2 viết *"Hai gia đình cùng dùng 450 kWh trong 30 ngày vẫn có thể cần hệ điện mặt trời khác nhau"*, đến dòng 4 lại viết *"Giả sử mỗi nhà dùng 450 kWh trong 30 ngày, trung bình 15 kWh/ngày. Lịch dùng điện của mỗi hộ vẫn khác nhau"*.
  - Mục 2 lặp câu: dòng 12 viết *"Pin có dung lượng sử dụng, công suất sạc-xả, tổn hao và chi phí"*, ngay dòng 13 lặp lại *"Chọn dung lượng pin từ biểu đồ tải, công suất sạc-xả và tổn hao"*.
  - Toàn bài chia thành 17 đoạn 1–2 câu rời rạc, cuối bài có 3 câu đơn nối đuôi sau bảng tổng hợp.
- **Đã sửa và gộp gì:**
  - Gộp 3 câu mở bài thành 1 đoạn 2 câu gọn rõ, đưa luôn con số `450 kWh trong 30 ngày, trung bình 15 kWh/ngày` vào câu đầu.
  - Gộp mỗi hồ sơ gia đình (Hộ A dùng ban ngày, Hộ B dùng buổi tối 18–23 giờ, Hộ C tải bơm tưới theo mùa và cần dự phòng) thành một đoạn văn hoàn chỉnh dưới từng đề mục H2.
  - Gộp 3 câu đơn cuối bài (diện tích mái và hướng mái, công cụ PVGIS, khảo sát tải và an toàn) thành một đoạn kết mạch lạc ngay dưới bảng tổng hợp.
- **Đã cắt gì và vì sao:**
  - Chỉ cắt các vế câu bị lặp nguyên ý ở dòng 4 và dòng 13. Giữ nguyên 100% số liệu (`450 kWh`, `30 ngày`, `15 kWh/ngày`, `18–23 giờ`) và bảng 3 hồ sơ A, B, C.

### Bài 138 — Điện mặt trời toàn cầu đạt 2,96 TW: Xu hướng nổi bật từ IEA PVPS 2026
- **Lỗi của bản đang đăng:**
  - Bản tin thị trường toàn cầu nhưng nửa sau bài (dòng 40–53) bị cắt thành 12 đoạn 1 câu rời rạc.
  - Lặp lại 3 lần các câu rào chắn/phủ định dặn dò hộ gia đình: dòng 5 (*"không phải dự báo cho riêng Việt Nam"*), dòng 44 (*"Tên công nghệ không thay thế thông số của đúng model như hiệu suất, hệ số nhiệt và điều kiện bảo hành"*), dòng 48–49 (*"Báo cáo không đưa ra một phương án chung cho mọi hộ..."*), dòng 52–53 (*"Nhưng các số liệu toàn cầu không tự trả lời hệ thống tại Việt Nam nên có công suất bao nhiêu hay cần pin lưu trữ không..."*).
- **Đã sửa và gộp gì:**
  - Giữ mở bài và mục bảng số liệu 5 quốc gia dẫn đầu thật gọn, đúng giọng bản tin; đưa thông tin *"số liệu năm 2025 được IEA PVPS công bố năm 2026"* vào câu dẫn trước bảng để mạch bài tự nhiên.
  - Gộp mục *Điện mặt trời phân tán và tấm pin N-type mở rộng* thành 1 đoạn văn trọn vẹn nói về xu hướng điện áp mái tự dùng ban ngày và sự dịch chuyển sang silicon N-type (TOPCon, HJT, Back Contact).
  - Gộp hai mục cuối thành 1 đoạn văn mạch lạc giải thích vì sao lưu trữ (BESS) và điều chỉnh phụ tải đi cùng nguồn phát mới, đồng thời nêu thẳng một lần ở cuối bài về các dữ liệu cần tính cho công trình thực tế tại Việt Nam (hóa đơn điện, giờ chạy tải, mái nhà, quy định đấu nối).
- **Đã cắt gì và vì sao:**
  - **Cắt câu lạc đề về đọc datasheet ở dòng 44** (`Tên công nghệ không thay thế thông số của đúng model như hiệu suất, hệ số nhiệt và điều kiện bảo hành.`) vì đây là bản tin tổng hợp thị trường toàn cầu của IEA PVPS.
  - **Cắt các câu phủ định lặp lại** ở dòng 5, 48 và 52 (*"không phải dự báo cho riêng Việt Nam"*, *"Báo cáo không đưa ra một phương án chung cho mọi hộ"*, *"các số liệu toàn cầu không tự trả lời..."*), chỉ giữ lại một câu khẳng định ở cuối bài. Giữ nguyên 100% số liệu (`9/2026`, `2,96 TW`, `2025`, `690 GW`, `15%`, `2024`, `2026`, `415 GW`, `54 GW`, `43 GW`, `18 GW`, `14 GW`, `60%`, `36`, `1 GW`).

### Bài 103 — Phó Thủ tướng: Cần giải pháp thúc đẩy điện mặt trời mái nhà
- **Lỗi của bản đang đăng:**
  - Toàn bài gồm 18 thẻ `<p>` ngắn 1–2 câu.
  - Câu ở dòng 14 (`<p>Gồm Công Thương, Tài chính, Kế hoạch và Đầu tư, Tài nguyên và Môi trường, cùng Bộ Công an.</p>`) bị cắt rời khỏi câu dòng 13 thành một đoạn văn cụt không có chủ ngữ – vị ngữ.
  - Trình tự thời gian và ý bị nhảy cóc: nêu mục tiêu Quy hoạch điện VIII ở mở bài (dòng 3–5), sang giữa mục sau (dòng 10) lại quay về giới thiệu ưu tiên của Quy hoạch điện VIII, rồi cuối bài (dòng 16–18) đột ngột nhảy lùi về cuộc họp ngày 3/6 với 3 câu đơn rời nhau.
- **Đã sửa và gộp gì:**
  - Gom toàn bộ thông tin về cuộc họp ngày 10/6/2023 và mục tiêu, trọng tâm của Quy hoạch điện VIII (giai đoạn 2021–2030, tầm nhìn 2050, ưu tiên mái nhà dân và công trình xây dựng ở nơi có nguy cơ thiếu điện như miền Bắc, mục tiêu năm 2030 đạt 50% tòa nhà công sở và 50% nhà dân tự sản tự tiêu) về đoạn mở bài.
  - Sắp xếp mục *Vướng mắc pháp lý và các đề xuất tháo gỡ* thành 2 đoạn theo đúng trình tự thời gian và nhóm việc:
    - Đoạn 1: nhắc mốc giao việc tại cuộc họp ngày 3/6 (nghiên cứu giải pháp cung ứng, tiết kiệm điện và đề xuất cơ chế tự sản, tự tiêu trong tháng cho các nhóm đơn vị), nối sang báo cáo vướng mắc Quyết định 13/2020/QĐ-TTg tại cuộc họp ngày 10/6/2023 và yêu cầu thí điểm cơ chế mới.
    - Đoạn 2: gom toàn bộ các chỉ đạo giao Bộ Công Thương và các bộ liên quan (khuyến khích đầu tư, khung giá phát lên lưới, hướng dẫn kỹ thuật/an toàn, giảm thuế, rà soát quy chuẩn) và nối liền câu cụt dòng 14 vào câu đơn giản hóa thủ tục tại 5 bộ.
- **Đã cắt gì và vì sao:**
  - Không cắt bất kỳ dữ kiện, tên cơ quan, mốc ngày tháng hay con số nào (`10/6/2023`, `2021–2030`, `2050`, `2030`, `50%`, `13/2020/QĐ-TTg`, `3/6`); chỉ sắp xếp lại đúng trình tự thời gian và nối câu cụt thành đoạn hoàn chỉnh.

---

## 3. Lý do 23 bài còn lại không sửa

Theo đúng yêu cầu *"Chỉ chọn bài THỰC SỰ rời rạc, lặp hoặc lạc đề (tối đa 10 bài, bài 155 bắt buộc). Bài đoạn ngắn nhưng mạch rõ thì để nguyên"*, 23 bài dưới đây được giữ nguyên:

1. **Bài `139` (*kW, kWp và kWh khác nhau thế nào? 7 ví dụ tránh nhầm*) và Bài `157` (*Mái hướng Đông – Tây có lắp điện mặt trời hiệu quả không?*):** Hai bài mẫu chuẩn theo `BRIEF.md`, không sửa.
2. **Nhóm bài thông báo phân phối và giới thiệu hợp tác ngắn (`100`, `101`, `106`, `108`, `109`):**
   - `100` (*Phúc Thịnh Solar phân phối tấm pin Sharp tại Việt Nam*): Bài thông báo ngắn (20 dòng), chia rõ 2 mục: chính sách hợp tác/thông số dòng Half-Cell và lịch sử/tiêu chuẩn của Sharp; mạch thông tin đi thẳng, không có đoạn lạc đề.
   - `101` (*Blue Carbon thông báo Phúc Thịnh Solar là nhà phân phối độc quyền tại Việt Nam*): Tin thông báo ngắn (3 đoạn văn + 1 ảnh), mạch rõ ràng.
   - `106` (*Đèn năng lượng mặt trời Blue Carbon cho dự án*): Bài giới thiệu ngắn gồm 2 mục rõ ràng (nguyên lý/thông số đèn Blue Carbon và thông tin hợp tác dự án).
   - `108` (*Được gì khi hợp tác cùng Phúc Thịnh Solar*): Bài mời hợp tác đại lý ngắn gọn (17 dòng), liệt kê thẳng quyền lợi đối tác, chứng từ CO/CQ/VAT và thông số chip CREE / pin LiFePO4.
   - `109` (*Phúc Thịnh Solar chính thức là đối tác phân phối độc quyền đèn Blue Carbon*): Bản tin ngắn (4 đoạn văn + 2 ảnh), câu chữ liền mạch.
3. **Nhóm bài hướng dẫn ngắn về đèn năng lượng mặt trời (`102`, `104`, `105`, `110`, `111`, `143`):**
   - `102` (*Đèn năng lượng mặt trời hoạt động thế nào và cần xem gì?*): Chia 3 mục theo đúng trình tự vị trí lắp -> thông số cần xem -> cách lắp và vệ sinh; các câu nối ý tự nhiên.
   - `104` (*Đèn năng lượng mặt trời nạp pin ban ngày, tự bật khi trời tối*): Bài tóm tắt rất ngắn (3 đoạn văn), đúng trọng tâm.
   - `105` (*Lắp đặt và sử dụng đèn năng lượng mặt trời: 4 lưu ý*): Bố cục 4 mục H2 tương ứng đúng 4 lưu ý trên tiêu đề (đặt nơi có nắng, nạp 1–2 chu kỳ đầu, tránh nguồn sáng ban đêm, lau mặt tấm pin), mỗi mục 2–3 đoạn ngắn đúng chủ đề.
   - `110` (*Chip LED CREE trong đèn năng lượng mặt trời: cần xem thông số nào?*): Giải thích rõ vai trò của chip LED CREE so với thông số toàn bộ đèn và giới thiệu nguồn gốc thương hiệu Blue Carbon, không bị rối mạch.
   - `111` (*Đèn năng lượng mặt trời có ích ở đâu và cần lưu ý gì?*): Chia 4 mục ngắn gọn theo từng lợi ích và lưu ý chi phí/bảo hành, mạch đọc rõ ràng.
   - `143` (*Mua đèn năng lượng mặt trời: đừng chỉ nhìn con số W quảng cáo*): Có bảng chọn đèn theo 4 vị trí (sân, cổng, lối đi, đường nội bộ) và 3 gạch đầu dòng thông tin cần có trong báo giá, cấu trúc rất sáng sủa.
4. **Nhóm bài kiến thức nền tảng và vận hành điện mặt trời (`136`, `137`, `140`, `141`, `142`, `144`, `145`, `147`, `153`, `154`):**
   - `136` (*Điện mặt trời hoạt động như thế nào? Từ ánh nắng đến điện sử dụng trong nhà*): Mạch bài đi tuần tự từ tấm pin -> inverter -> dùng ban ngày -> ban đêm/mất điện -> an toàn và chọn công suất.
   - `137` (*Cách đọc datasheet tấm pin năng lượng mặt trời: 10 thông số quan trọng*): Lấy xuyên suốt một mẫu datasheet thật (`LONGi Hi-MO X10 Explorer LR7-72HVH-650M BGV02`) để giải thích tuần tự từng thông số, chia mục H2/H3 và bảng số liệu rõ ràng.
   - `140` (*Inverter điện mặt trời là gì? Nó thực hiện những chức năng nào?*): Chia 4 mục rõ ràng (3 chức năng chính, bảng chọn theo mái/tải, bảng thông số điện, trường hợp mất lưới), câu chữ gọn và bám sát chủ đề inverter.
   - `141` (*Có nên lắp điện mặt trời khi nhà chỉ dùng điện nhiều vào buổi tối?*): Cấu trúc chặt chẽ gồm bảng định hướng theo lịch dùng điện, yêu cầu dung lượng/công suất xả của pin, ví dụ so sánh 2 hộ cùng dùng 600 kWh/tháng và 4 bước kiểm tra hóa đơn trong 15 phút.
   - `142` (*Hệ hòa lưới, hybrid, độc lập: nên chọn loại nào?*): Bảng đối chiếu 3 hệ thống theo 5 câu hỏi thực tế tại công trình, tiếp theo là phân tích khi mất điện và 4 câu hỏi chốt trước khi lắp.
   - `144` (*Trời nhiều mây, ánh sáng yếu: pin còn tạo điện thế nào?*): Mạch bài rõ từ phân biệt mây mỏng/mây dày, 4 dấu hiệu cần gọi kỹ thuật viên, bảng nhật ký theo dõi 14 ngày đến nguyên lý MPPT khi nắng yếu.
   - `145` (*Vì sao có tấm pin nhưng mất điện lưới vẫn có thể bị cúp?*): Giải thích trực diện cơ chế chống đảo lưới, giới hạn của nhánh tải dự phòng, phân biệt kW với kWh và 4 câu hỏi chốt thiết bị dự phòng.
   - `147` (*IEA công bố Renewables 2026: triển vọng điện mặt trời đến 2031*): Bản tin ngắn (25 dòng), số liệu dự báo giai đoạn 2026–2031 được trình bày gọn theo 3 mục ngắn, không lặp ý.
   - `153` (*Lắp tấm pin càng nhiều có luôn rút ngắn hoàn vốn?*): Tuy dùng các đoạn văn ngắn nhưng mạch lập luận đi rất rõ từ ví dụ so sánh Nhà A – Xưởng B -> cách tính riêng phần công suất tăng thêm (ví dụ 500 kWh/năm, tự dùng 60% = 300 kWh) -> giới hạn inverter và mái -> quyết định pin lưu trữ -> 5 bước tính và 3 câu hỏi cho người bán.
   - `154` (*Mái tôn, mái ngói, mái bằng: lưu ý khi khảo sát điện mặt trời*): Chia tách rõ ràng theo 3 loại mái (mái tôn, mái ngói, mái bằng) và 1 mục kiểm tra chung về dây điện, vị trí xuyên mái, lối bảo trì; không bị nhảy ý hay lạc đề.
