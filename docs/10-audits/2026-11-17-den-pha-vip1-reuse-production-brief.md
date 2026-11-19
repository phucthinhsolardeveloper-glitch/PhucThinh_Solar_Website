# KẾ HOẠCH TÁI THIẾT KẾ BỘ ẢNH ĐÈN PHA — KẾ THỪA CONCEPT VIP1
Ngày: 17/11/2026 | Trạng thái: DESIGN SPEC / CHƯA DUYỆT CHO PRODUCTION

## 0. Hiểu đúng phạm vi
VIP1 (19 thư mục, 209 file PNG gốc 900×900) là THƯ VIỆN PHONG CÁCH THAM CHIẾU, KHÔNG PHẢI NGUỒN ĐƯỢC PHÉP TỰ ĐỘNG GÁN CHÉO MÃ.
Dữ liệu audit tại docs/10-audits/2026-11-17-product-gallery-inventory.csv: 72 đèn pha; 35 mã có trong mapping VIP1, còn 37 mã đèn pha KHÔNG có mapping VIP1, gồm ORIANA 11, BLUE CARBON 11, JINDIAN 15. 37 mã này là mục tiêu triển khai đợt 1.
Số lượng ảnh hiện có được đo trên snapshot audit 17/11, không được coi là trạng thái hiện tại sau khi thay VIP1. Trước mọi cập nhật phải re-audit read-only production.

## 1. Điều rút ra từ VIP1
### Giữ làm design language
- Bộ ảnh vuông, có logo Phúc Thịnh Solar ở footer/đầu khi thật sự là poster biên tập; phối navy đậm, xanh công nghệ và gold nhấn, chữ rõ và nhiều khoảng thở.
- Kể câu chuyện sản phẩm theo chuỗi: nhận diện sản phẩm -> nhìn rõ bộ sản phẩm -> thông số xác thực -> cách hoạt động -> bố trí/lắp đặt thực tế -> chi tiết ảnh thật.
- Ảnh daylight/night tạo ngữ cảnh; tách bạch ảnh minh họa và ảnh dự án thật.
- Thước đo kích thước và bảng cấu hình theo từng SKU, chân đế/gá/remote ảnh thật, không chỉ poster quảng cáo.
### Sửa trước khi tái sử dụng
- Các trang “Chứng nhận & ủy quyền” lặp lại: KHÔNG tự chuyển thành lời chứng thực của SKU; chỉ xuất bản khi có tài liệu hiệu lực, đúng pháp nhân/phạm vi và quyền sử dụng.
- Banner tổng hợp 3–4 mã: dùng làm ảnh so sánh dòng nếu số liệu được xác minh; không gắn thành ảnh đặc tính riêng của 1 SKU.
- Các claim giờ sáng, thời gian sạc, lumen, số chip, IP, pin mAh, bảo hành, “cảm biến radar”, kích thước: dữ liệu cần nguồn per-SKU, không sao chép theo concept.
- Các poster có hình ảnh tự tạo 900 px không được tuyên bố là ảnh native 1600 px; 1600 là canvas xuất, không phải tăng chi tiết.
- Poster thông điệp mua hàng và bố cục tái sử dụng quá nhiều: nên giảm xuống 1 ảnh/album; tránh trùng nội dung với phần mô tả sản phẩm trên PDP.
- Đèn mặt trước, tấm pin, phụ kiện và góc lắp đặt phải khớp bộ được bán; ảnh mẫu series không dùng thay ảnh SKU khi cấu trúc vật lý khác.
- Ảnh cảnh ghép không được thể hiện giả là thi công thật, không bịa chứng nhận, công trình hoặc vùng chiếu sáng định lượng.

## 2. Chuẩn đầu ra đề xuất cho nhóm đèn pha
Canvas xuất web: 1600×1600 px, sRGB; JPG/WEBP cho poster/scene, PNG alpha cho ảnh cutout khi có nguồn cho phép; bản gốc bảo toàn. Bản master có layer/template + nguồn data; font, icon, ảnh và giấy phép nguồn được quản lý. Không AI thay đổi thân đèn, cell tấm pin, LED, logo, nhãn hay chi tiết phần cứng; AI CHỈ hỗ trợ nền cảnh và sáng tạo không phải bằng chứng thực tế.

Định nghĩa vị trí:
P01 — Ảnh chính: bộ được bán thực tế trên trắng; camera trực diện mặt đèn, tấm pin nếu thuộc kit. Logo/nền trang trí không được che sản phẩm; cạnh lớn bộ kit mục tiêu 70–82% khung. Không làm lại sản phẩm từ ảnh AI.
P02 — Ảnh kit: ảnh thật các món theo packing list SKU; nếu kit thiếu ảnh thì chỉ “giữ chỗ, cần ảnh nguồn”, không dựng phụ kiện giả.
P03 — Kích thước/lắp đặt: sơ đồ kích thước đúng mm của từng component và khoảng cách gá; lấy từ datasheet hoặc đo kiểm, không suy ra từ công suất.
P04 — Cận cấu tạo: LED, vỏ, lens, jack nối, mặt sau, góc chi tiết từ ảnh thật. Chỉ gắn nhãn cho thuộc tính đã kiểm chứng.
P05 — Ứng dụng/ánh sáng: cảnh sân, cổng, kho, lối đi, đường nội bộ phù hợp hình dạng/mount; hình minh họa mang tính trực quan, không định lượng lux/radius/bảo hành.
P06 — Sơ đồ tính năng: sơ đồ pin -> sạc -> bộ điều khiển -> đèn / remote hoặc cảm biến dựa trên cấu hình xác thực; khác model thì khác dữ liệu, không copy chung.
P07 — Tùy chọn so sánh trong dòng, hình thức lắp đặt thật hoặc chỉ dẫn vận hành hữu ích. Không bắt buộc, không dùng giấy chứng nhận chung để lấp chỗ.

Mục tiêu 5–7 ảnh có giá trị cho mỗi SKU (1 ảnh đại diện + 4–6 gallery). Không thêm ảnh vô nghĩa cho đủ số. Giữ gallery nhiều ảnh đã tốt; chỉ thay vị trí thiếu, sai hoặc trùng. Không tự đảo vị trí ảnh hiện hữu nếu chưa QA.

## 3. Công thức layout (áp dụng mọi concept)
- Hệ thống thiết kế: navy #0A2251 / #061537; gold #C9962E, vàng nhạt #E0B654; nền trắng cho dữ liệu, cảnh xanh dịu cho lifestyle; KHÔNG dùng vàng gradient cho mọi khối.
- Poster headline một câu (6–12 từ), một tiêu điểm thị giác, 3–4 fact tối đa; khoảng an toàn 72 px quanh khung 1600.
- Product P01 không chèn khung quảng cáo/lời khẳng định; chỉ sản phẩm thật, chú trọng thấy rõ ngoại hình.
- Mọi ảnh có SKU và tên hãng trong metadata/file manifest; chữ mặt ngoài ảnh được lấy từ dữ liệu đã xác minh; nhãn biến thể rõ: ánh sáng vàng/trắng, MONO/poly, -S, -NEW, PRO.
- Dùng type scale poster 1600: headline 78–104 px, fact label 36–46 px, chú thích ≥28 px; xem được cả mobile thumbnail 390 px.
- Dùng HTML/SVG template để dựng chữ, icon, đường kích thước; không sinh chữ tiếng Việt bằng image AI.
- Sản phẩm nội bộ (ảnh gốc) và ảnh cảnh có nguồn khác nhau phải ghi provenance/rights trong manifest; claim “đã lắp đặt”, “đã kiểm nghiệm”, “được ủy quyền” cần chứng cứ trước khi công bố.

## 4. Map 37 mã đèn pha NGOÀI VIP1 theo 11 nhánh thiết kế
| Nhánh | SKU mục tiêu | VIP1 học theo | Lưu ý riêng |
|---|---|---|---|
| ORIANA DB7 | DB-760, DB-7500, DB-71000 | F02 DB7100/7200/7300 | Đối chiếu hình mặt đèn, số LED, panel; có ứng viên EMF nội bộ; không dùng chung 3 công suất khi khác form |
| ORIANA DB9 | DB-9200, DB-91000 | F03 DB9100/9300/9600/9800 | Nguồn nhóm DB9 481×321 từng có nghi vấn ảnh lặp; đối chiếu nhãn thật |
| ORIANA DB8 | DB-8150, DB-8200, DB-8300, DB-8300-S, DB-8500, DB-8500-S | F06 DB8100/8100P | -S và khác panel/chip phải tách biến thể vật lý |
| BLUE CARBON WR | BCT-WR1.0, BCT-WR2.0, BCT-WR3.0, BCT-WR4.0 | F10 BCT-WR5.0/WR6.0 | Hình 720px hãng đã có cho WR1/4, còn kiểm quyền; không gán sang WR2/3 |
| BLUE CARBON WW | BCT-WW1.0, BCT-WW3.0, BCT-WW4.0, BCT-WW5.0, BCT-WW6.0, BCT-WW6.0-MONO | F07 WW2.0 / F08 WW5.0-MONO | Tách MONO/không MONO và loại pin theo chứng cứ; ảnh góc lắp đặt |
| BLUE CARBON FLRP | BCT-FLRP2.0 | F07/08 (ngôn ngữ thị giác thôi) | Hình dáng khác; không copy cấu tạo WW |
| JINDIAN JD18 | JD-18200, JD-18300, JD-18500 | F11 JD-V / F15 JDE chỉ để tham khảo layout | JD-18300 từng có ảnh bị gán nhầm JD-18200/JDE-8300 — BLOCK ảnh này |
| JINDIAN chóa | JD-6128 | F11 cho thiết kế, không cho cấu tạo | Ảnh chóa thật 1620×1080, layout riêng theo hình tròn và góc treo |
| JINDIAN ánh sáng vàng | JD-8860L-VANG, JD-8800L-VANG, JD-8200L-VANG, JD-8300L-VANG | F20 JD-8800L/8200L/8300L | Không đổi màu LED giả từ biến thể ánh sáng trắng; cần ảnh/video Kelvin thực |
| JINDIAN JDL | JD-8500L, JD-8500L-NEW, JD-8860L | F20 JD-L | NEW khác thế hệ; đối chiếu nhãn model và phụ kiện |
| JINDIAN T/V | JD-T100, JD-T200, JD-T300, JD-V100 | F11 JD-V200/300/500 | Chỉ tái sử dụng ý đồ thiết kế; ảnh thân, panel/khớp gá khác nhau |

## 5. Quy trình làm ảnh theo từng mã
1. READ-ONLY inventory API/DB hiện hành, tải file ảnh gốc sẵn có; giữ old paths/sha256.
2. Match source SKU từ nhãn thật/cấu tạo, datasheet hãng/nguồn nội bộ. Đánh dấu PROVEN, UNKNOWN, CONTRADICTORY.
3. Quyết định album thiếu P02–P07 nào, không tự thay P01 đúng và đẹp. Có ảnh thương mại cũ tốt -> giữ.
4. Lấy ảnh nguồn ưu tiên PTS tự chụp/nguồn do công ty cấp > ảnh hãng được phép sử dụng > nhà phân phối có quyền cấp > reference chỉ để nghiên cứu. Không tự gỡ watermark.
5. Tạo asset trung gian tách nền sạch, master/lay-out PNG/SVG/HTML, cảnh AI chỉ là background; giới hạn góc khác nguồn.
6. Xuất file mã SKU__Pxx__v01, manifest trường: product_id, SKU, slot, source, license, verified facts, native-resolution, rendered-resolution, SHA256, status.
7. QA tự động (kích thước, alpha, hash, trùng pHash, file missing, text OCR thủ công khi cần) + QA hình học/đúng SKU/chú thích + 2 người duyệt visual/fact.
8. Dựng trang so sánh desktop/mobile, chỉ lập dry-run manifest; không áp database đến khi P01/P02/P03 đạt và quyền ảnh rõ. Khi nhập: backup mapping cũ, transaction/idempotency, giữ old assets, verify API/PDP, có rollback.

## 6. Gating
- PASS_SOURCE: hình và SKU xác nhận, quyền dùng rõ, không viền lỗi, không giả tạo phần cứng.
- PASS_FACT: số trên ảnh có nguồn và đồng nhất với PDP, claim bảo hành/phân phối không giả định.
- PASS_DESIGN: cùng hệ visual, không trùng poster, mobile đọc được, ảnh kit thật.
- PASS_PRODUCTION: admin + Laravel API + Nuxt PDP + thumbnail đúng, kiểm tra 390/1440, rollback ready.
- BLOCK: sai SKU, bất đồng datasheet, logo đối tác chưa cho phép, ảnh nguồn quá thấp để đọc chi tiết, ảnh ánh sáng vàng giả, nguồn không được phép sử dụng.
- Mỗi SKU có thể ở trạng thái: PLAN -> SOURCE_REQUIRED -> COMPOSITE_READY -> QA_REVIEW -> OWNER_APPROVED -> DEPLOYED. Kết quả chưa được kiểm chứng không được đánh PASS.

## 7. Ưu tiên thực thi
P0 nguồn thiếu 1 ảnh (15 mã ngoài VIP1), trong đó DB-760/7500/71000 và JD-6128 có nội bộ để thử; WR1/4 có nguồn hãng đang chờ quyền; JD-18300 block nhầm model.
P1 ảnh ít 2–3, có thể cải thiện số lượng có ích trong thời gian ngắn (13 mã).
P2 đang 5+ ảnh (9 mã), kiểm chất lượng/trùng/variant, không mặc nhiên bổ sung thêm.
Ước tính lỗ hổng tối thiểu theo số ảnh của snapshot: 124 slot để mọi mã ngoài VIP1 có 6 ảnh, CHƯA tính thay thế ảnh sai/trùng và CHƯA xác nhận snapshot sau VIP1.
Không triển khai đại trà tới khi 3 concept mẫu đúng định hướng: DB-760 (ORIANA), BCT-WR1.0 (Blue Carbon), JD-18300 (Jindian nhưng CHỜ nguồn chuẩn). Có thể dùng JD-6128 làm mẫu JINDIAN trong lúc JD-18300 chưa đạt.

## 8. Nguồn dữ liệu nội bộ kiểm tra
- VIP1 folder máy công ty: /mnt/c/Users/ADMIN/Downloads/VIP1_PHÚC THỊNH SOLAR_12TR5-20261117T050129Z-1-001/VIP1_PHÚC THỊNH SOLAR_12TR5
- VIP1 map tham chiếu: /home/admin01/orca/artifacts/pts-photo-vip1-20261117/apply-plan.json
- Kho inventory: docs/10-audits/2026-11-17-product-gallery-inventory.csv
- QA 26 SKU 1 ảnh: docs/10-audits/2026-11-17-den-pha-26-image-qa.csv
- Concept 5 ảnh trước đây: /home/admin01/orca/artifacts/pts-image-audit-20261116/01-PHAN-TICH-VA-CONCEPT-ANH-SAN-PHAM.md
- Ảnh gốc QA: backend/resources/product-gallery/2026-11-17/den-pha-qa-batch-1

## 9. Phạm vi phiên triển khai này
CHỈ tạo brief + kế hoạch kiểm kê 37 SKU + bảng assignment; tuyệt đối không chạm DB, upload production, ảnh VIP1 đã thay, không sửa giá/specs. Kết quả phải được xem như pre-production.
## 10. NGOẠI LỆ HÌNH ẢNH PHÁT HIỆN KHI XEM TRỰC TIẾP (cần audit VIP1 riêng, không tự sửa)
- F07 / 97.png: SKU BCT-WW2.0 (BLUE CARBON), poster clean-white lại có logo “ORIANA SOLAR LIGHT” trên đầu. Đây là lỗi gắn nhãn thương hiệu nhìn thấy trực tiếp; không dùng hình này làm mẫu brand lockup và phải kiểm tra có đang xuất hiện trên PDP không.
- F14 / 166.png: SKU BCT-OLMJ1.0 (BLUE CARBON), poster clean-white lại có logo “ORIANA SOLAR LIGHT”. Không dùng hình này làm mẫu nhãn.
- Dù 2 ảnh nằm trong VIP1, nhiệm vụ hiện tại chỉ phân tích/tạo kế hoạch 37 SKU NGOÀI VIP1, không ghi đè/đăng lại ảnh VIP1 khi chưa kiểm tra và duyệt riêng.
- Cổng bắt buộc bổ sung: automated brand-vs-overlay check trong manifest + duyệt brand logo nhìn bằng mắt đối chiếu SKU. Nếu thương hiệu trong hình khác dữ liệu sản phẩm -> STOP SHIP.
