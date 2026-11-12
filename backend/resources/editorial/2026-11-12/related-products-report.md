# Đối chiếu sản phẩm liên quan PTS-KT-001..015

- Nguồn: `backend/resources/editorial/2026-11-12/related-products.json`
- Lệnh: `php artisan pts:editorial-product-links` (môi trường local, DB `pts_app`, 12/11/2026 10:45 UTC)
- Điều kiện gắn: bài ẩn, chưa có sản phẩm (kể cả trong bản nháp chờ duyệt); sản phẩm hiển thị, danh mục hiển thị, có ảnh và slug hợp lệ; chủ đề phải được phát hiện trong nội dung bài.

## PTS-KT-001 — Điện mặt trời hoạt động như thế nào? Từ ánh nắng đến điện sử dụng trong nhà

- Bài #136 · Quyết định: gắn sản phẩm · Trạng thái: SẼ GẮN 3 sản phẩm
- Lý do: Bài giải thích chuỗi tấm pin → inverter → tải, có mục riêng về hệ hòa lưới và nhắc nhiều lần hệ hybrid, pin lưu trữ.

| ID | Sản phẩm | Chủ đề | Lý do | Kiểm tra |
|---|---|---|---|---|
| 820 | [Inverter SolaX X1-BOOST-5-G4](/san-pham/inverter-solax-x1-boost-5-g4) | Inverter hòa lưới | Inverter hòa lưới 1 pha 5 kW (thông số trên trang sản phẩm), minh họa thiết bị chuyển DC sang AC mà bài mô tả. | hợp lệ |
| 805 | [Inverter SolaX X1-HYB-5.0-LV Hybrid 5kw](/san-pham/inverter-solax-x1-hyb-50-lv-hybrid-5kw) | Inverter hybrid | Inverter hybrid 5 kW cho phương án có pin lưu trữ mà bài nhắc tới. | hợp lệ |
| 846 | [Pin lưu trữ/Hệ thống lưu trữ SolaX T-BAT TSYS-D53 Áp thấp Triple LD 53](/san-pham/pin-luu-truhe-thong-luu-tru-solax-t-bat-tsys-d53-ap-thap-triple-ld-53) | Pin lưu trữ | Pin lưu trữ áp thấp LiFePO4 thuộc nhóm thiết bị lưu trữ bài giải thích. | hợp lệ |

## PTS-KT-002 — Cách đọc datasheet tấm pin năng lượng mặt trời: 10 thông số quan trọng

- Bài #137 · Quyết định: gắn sản phẩm · Trạng thái: SẼ GẮN 4 sản phẩm
- Lý do: Bài hướng dẫn đọc datasheet tấm pin (Pmax, Voc, Isc, Vmp, hiệu suất); liên kết các module có thông số này trên trang sản phẩm để người đọc thực hành.

| ID | Sản phẩm | Chủ đề | Lý do | Kiểm tra |
|---|---|---|---|---|
| 741 | [Tấm pin năng lượng mặt trời LONGi 620WP - 2 MẶT KÍNH](/san-pham/tam-pin-nang-luong-mat-troi-longi-620wp-2-mat-kinh) | Tấm pin năng lượng mặt trời | Trang sản phẩm ghi Voc 48.78 V, Isc 16.05 A, hiệu suất 23% — đúng nhóm thông số bài hướng dẫn đọc. | hợp lệ |
| 744 | [Tấm pin năng lượng mặt trời LONGi 650WP - 1 MẶT KÍNH](/san-pham/tam-pin-nang-luong-mat-troi-longi-650wp-1-mat-kinh) | Tấm pin năng lượng mặt trời | Trang sản phẩm ghi Voc 53.9 V, Isc 15.29 A, hiệu suất 24.1%, có thể đối chiếu với cách đọc trong bài. | hợp lệ |
| 755 | [Tấm pin năng lượng mặt trời Astronergy 625Wp](/san-pham/tam-pin-nang-luong-mat-troi-astronergy-625wp) | Tấm pin năng lượng mặt trời | Trang sản phẩm có cả Voc, Isc, Vmp 41.69 V và loại cell N-Type TOPCon — đủ để luyện đọc Vmp mà bài nêu. | hợp lệ |
| 753 | [Tấm pin năng lượng mặt trời Astronergy 610WP - 2 MẶT KÍNH](/san-pham/tam-pin-nang-luong-mat-troi-astronergy-610wp-2-mat-kinh) | Tấm pin năng lượng mặt trời | Module 2 mặt kính có Voc, Isc, hiệu suất 22.6% để so với module 1 mặt kính khi đọc datasheet. | hợp lệ |

## PTS-KT-003 — Điện mặt trời toàn cầu đạt 2,96 TW: Xu hướng nổi bật từ IEA PVPS 2026

- Bài #138 · Quyết định: không gắn · Trạng thái: KHÔNG GẮN SẢN PHẨM
- Lý do: Tin thị trường toàn cầu theo số liệu IEA PVPS, không bàn về chọn thiết bị; gắn sản phẩm sẽ là khớp theo từ chung.

## PTS-KT-004 — kW, kWp và kWh khác nhau thế nào? 7 ví dụ tránh nhầm

- Bài #139 · Quyết định: gắn sản phẩm · Trạng thái: SẼ GẮN 1 sản phẩm
- Lý do: Bài dùng ví dụ 8 tấm pin 625 Wp tạo dàn 5 kWp; liên kết đúng một module cùng mức công suất có trong catalog, không gắn thêm thiết bị bài không bàn.

| ID | Sản phẩm | Chủ đề | Lý do | Kiểm tra |
|---|---|---|---|---|
| 755 | [Tấm pin năng lượng mặt trời Astronergy 625Wp](/san-pham/tam-pin-nang-luong-mat-troi-astronergy-625wp) | Tấm pin năng lượng mặt trời | Module 625 Wp trùng mức công suất trong ví dụ tính kWp của bài. | hợp lệ |

## PTS-KT-005 — Inverter điện mặt trời là gì? Nó thực hiện những chức năng nào?

- Bài #140 · Quyết định: gắn sản phẩm · Trạng thái: SẼ GẮN 4 sản phẩm
- Lý do: Bài giải thích chức năng inverter và phân biệt inverter hòa lưới với hybrid; liên kết mỗi loại một model 1 pha và một model 3 pha.

| ID | Sản phẩm | Chủ đề | Lý do | Kiểm tra |
|---|---|---|---|---|
| 820 | [Inverter SolaX X1-BOOST-5-G4](/san-pham/inverter-solax-x1-boost-5-g4) | Inverter hòa lưới | Inverter hòa lưới 1 pha 5 kW, 2 MPPT (theo trang sản phẩm) — minh họa mục MPPT và chuyển DC/AC. | hợp lệ |
| 825 | [Inverter SolaX X3-MIC-10](/san-pham/inverter-solax-x3-mic-10) | Inverter hòa lưới | Inverter hòa lưới 3 pha 10 kW, 2 MPPT, giám sát WiFi — minh họa mục giám sát và số pha. | hợp lệ |
| 805 | [Inverter SolaX X1-HYB-5.0-LV Hybrid 5kw](/san-pham/inverter-solax-x1-hyb-50-lv-hybrid-5kw) | Inverter hybrid | Inverter hybrid 5 kW có thông số công suất dự phòng trên trang sản phẩm, đúng nhóm hybrid bài phân biệt. | hợp lệ |
| 811 | [Inverter SolaX X3-NEO-10K-LV Hybrid 10kw](/san-pham/inverter-solax-x3-neo-10k-lv-hybrid-10kw) | Inverter hybrid | Inverter hybrid 10 kW, công suất dự phòng 11 kW theo trang sản phẩm. | hợp lệ |

## PTS-KT-006 — Có nên lắp điện mặt trời khi nhà chỉ dùng điện nhiều vào buổi tối?

- Bài #141 · Quyết định: gắn sản phẩm · Trạng thái: SẼ GẮN 4 sản phẩm
- Lý do: Bài bàn dùng pin lưu trữ và hệ hybrid để chuyển điện ban ngày sang buổi tối; liên kết pin lưu trữ áp thấp và inverter hybrid hộ gia đình.

| ID | Sản phẩm | Chủ đề | Lý do | Kiểm tra |
|---|---|---|---|---|
| 846 | [Pin lưu trữ/Hệ thống lưu trữ SolaX T-BAT TSYS-D53 Áp thấp Triple LD 53](/san-pham/pin-luu-truhe-thong-luu-tru-solax-t-bat-tsys-d53-ap-thap-triple-ld-53) | Pin lưu trữ | Pin lưu trữ áp thấp LiFePO4, tuổi thọ 6000 chu kỳ theo trang sản phẩm — nhóm thiết bị bài mục 3 bàn tới. | hợp lệ |
| 847 | [Pin lưu trữ/Hệ thống lưu trữ SolaX T-BAT-SYS-LV D150 Pro](/san-pham/pin-luu-truhe-thong-luu-tru-solax-t-bat-sys-lv-d150-pro) | Pin lưu trữ | Pin lưu trữ áp thấp khác trong cùng danh mục Pin / Battery để so sánh dung lượng. | hợp lệ |
| 805 | [Inverter SolaX X1-HYB-5.0-LV Hybrid 5kw](/san-pham/inverter-solax-x1-hyb-50-lv-hybrid-5kw) | Inverter hybrid | Inverter hybrid 5 kW cho kịch bản có pin mà bài so sánh. | hợp lệ |
| 806 | [Inverter SolaX X1-HYB-6.0-LV Hybrid 6kw](/san-pham/inverter-solax-x1-hyb-60-lv-hybrid-6kw) | Inverter hybrid | Inverter hybrid 6 kW, công suất dự phòng 6.6 kW theo trang sản phẩm. | hợp lệ |

## PTS-KT-007 — Hệ hòa lưới, hybrid, độc lập: nên chọn loại nào?

- Bài #142 · Quyết định: gắn sản phẩm · Trạng thái: SẼ GẮN 3 sản phẩm
- Lý do: Bài so sánh hệ hòa lưới, hybrid và độc lập; liên kết thiết bị chính của hai cấu hình phổ biến (inverter hòa lưới, inverter hybrid + pin lưu trữ).

| ID | Sản phẩm | Chủ đề | Lý do | Kiểm tra |
|---|---|---|---|---|
| 820 | [Inverter SolaX X1-BOOST-5-G4](/san-pham/inverter-solax-x1-boost-5-g4) | Inverter hòa lưới | Inverter hòa lưới 1 pha 5 kW cho mục hệ hòa lưới. | hợp lệ |
| 805 | [Inverter SolaX X1-HYB-5.0-LV Hybrid 5kw](/san-pham/inverter-solax-x1-hyb-50-lv-hybrid-5kw) | Inverter hybrid | Inverter hybrid 5 kW cho mục hệ hybrid. | hợp lệ |
| 846 | [Pin lưu trữ/Hệ thống lưu trữ SolaX T-BAT TSYS-D53 Áp thấp Triple LD 53](/san-pham/pin-luu-truhe-thong-luu-tru-solax-t-bat-tsys-d53-ap-thap-triple-ld-53) | Pin lưu trữ | Pin lưu trữ áp thấp cho mục hệ hybrid có lưu trữ. | hợp lệ |

## PTS-KT-008 — Mua đèn năng lượng mặt trời: đừng chỉ nhìn con số W quảng cáo

- Bài #143 · Quyết định: gắn sản phẩm · Trạng thái: SẼ GẮN 4 sản phẩm
- Lý do: Bài hướng dẫn chọn đèn theo lumen, dung lượng pin, tấm pin sạc thay vì watt quảng cáo; liên kết đèn có các thông số đó trên trang sản phẩm.

| ID | Sản phẩm | Chủ đề | Lý do | Kiểm tra |
|---|---|---|---|---|
| 2201 | [Đèn Đường Năng Lượng Mặt Trời JINDIAN JD-19600L (600W)](/san-pham/den-duong-nang-luong-mat-troi-jindian-jd-19600l-600w) | Đèn năng lượng mặt trời | Tên ghi 600W nhưng trang sản phẩm ghi quang thông 2800 lm, pin 3.2V/22.000mAh — đúng tình huống bài khuyên đọc lumen thay vì watt. | hợp lệ |
| 507 | [Đèn Đường Năng Lượng Mặt Trời ORIANA DB02-2 (60W)](/san-pham/den-duong-nang-luong-mat-troi-oriana-db02-2-60w) | Đèn năng lượng mặt trời | Tên ghi 60W, trang sản phẩm ghi quang thông 7800–8400 lm, pin 3.2V/100Ah, tấm pin 6V/120W để so với mẫu ghi watt cao. | hợp lệ |
| 610 | [Đèn Đường Năng Lượng Mặt Trời BLUE CARBON BCT-OLF-100W](/san-pham/den-duong-nang-luong-mat-troi-blue-carbon-bct-olf-100w) | Đèn năng lượng mặt trời | Trang sản phẩm ghi 8000 lm, pin 3.2V/200Ah, khoảng cách lắp 45–50 m — đủ dữ kiện bài yêu cầu hỏi người bán. | hợp lệ |
| 629 | [Đèn đường năng lượng mặt trời 300W JINDIAN JD-369](/san-pham/den-duong-nang-luong-mat-troi-300w-jindian-jd-369) | Đèn năng lượng mặt trời | Trang sản phẩm ghi diện tích chiếu 300 m², pin 25Ah, tấm pin 6V/25W để đối chiếu dung lượng pin với tấm sạc. | hợp lệ |

## PTS-KT-009 — Trời nhiều mây, ánh sáng yếu: pin còn tạo điện thế nào?

- Bài #144 · Quyết định: không gắn · Trạng thái: KHÔNG GẮN SẢN PHẨM
- Lý do: Bài giải thích bức xạ, nhiệt độ và giám sát sản lượng theo mùa, không đề xuất loại thiết bị cụ thể.

## PTS-KT-010 — Vì sao có tấm pin nhưng mất điện lưới vẫn có thể bị cúp?

- Bài #145 · Quyết định: gắn sản phẩm · Trạng thái: SẼ GẮN 3 sản phẩm
- Lý do: Bài giải thích chống đảo lưới của inverter hòa lưới và thành phần cần có để cấp tải dự phòng khi mất lưới (bài khuyên hỏi rõ cổng EPS/backup của inverter).

| ID | Sản phẩm | Chủ đề | Lý do | Kiểm tra |
|---|---|---|---|---|
| 820 | [Inverter SolaX X1-BOOST-5-G4](/san-pham/inverter-solax-x1-boost-5-g4) | Inverter hòa lưới | Inverter hòa lưới 1 pha — loại thiết bị bài giải thích sẽ ngừng phát khi mất lưới. | hợp lệ |
| 806 | [Inverter SolaX X1-HYB-6.0-LV Hybrid 6kw](/san-pham/inverter-solax-x1-hyb-60-lv-hybrid-6kw) | Inverter hybrid | Inverter hybrid có thông số công suất dự phòng 6.6 kW trên trang sản phẩm, liên quan mục tải dự phòng. | hợp lệ |
| 811 | [Inverter SolaX X3-NEO-10K-LV Hybrid 10kw](/san-pham/inverter-solax-x3-neo-10k-lv-hybrid-10kw) | Inverter hybrid | Inverter hybrid có thông số công suất dự phòng 11 kW trên trang sản phẩm, cho mục công suất dự phòng. | hợp lệ |

## PTS-KT-011 — Tấm pin 600W, 620W, 650W và 720W: watt cao có tốt hơn?

- Bài #146 · Quyết định: gắn sản phẩm · Trạng thái: SẼ GẮN 4 sản phẩm
- Lý do: Bài so sánh tấm pin 600–720 W; liên kết module có công suất trùng mức bài nêu (620 Wp, 650 Wp) với các cấu tạo khác nhau.

| ID | Sản phẩm | Chủ đề | Lý do | Kiểm tra |
|---|---|---|---|---|
| 741 | [Tấm pin năng lượng mặt trời LONGi 620WP - 2 MẶT KÍNH](/san-pham/tam-pin-nang-luong-mat-troi-longi-620wp-2-mat-kinh) | Tấm pin năng lượng mặt trời | Module 620 Wp, 132 cell, trùng mức 620 W bài so sánh. | hợp lệ |
| 744 | [Tấm pin năng lượng mặt trời LONGi 650WP - 1 MẶT KÍNH](/san-pham/tam-pin-nang-luong-mat-troi-longi-650wp-1-mat-kinh) | Tấm pin năng lượng mặt trời | Module 650 Wp 1 mặt kính, 144 cell, trùng mức 650 W bài so sánh. | hợp lệ |
| 746 | [Tấm pin năng lượng mặt trời LONGi 650WP -2 MẶT KÍNH](/san-pham/tam-pin-nang-luong-mat-troi-longi-650wp-2-mat-kinh) | Tấm pin năng lượng mặt trời | Module 650 Wp 2 mặt kính: cùng công suất nhưng Isc 19,03 A khác bản 1 mặt kính — minh họa mục giới hạn dòng với inverter. | hợp lệ |
| 753 | [Tấm pin năng lượng mặt trời Astronergy 610WP - 2 MẶT KÍNH](/san-pham/tam-pin-nang-luong-mat-troi-astronergy-610wp-2-mat-kinh) | Tấm pin năng lượng mặt trời | Module 610 Wp nằm trong dải 600–650 W, hãng khác để so hiệu suất (22.6%). | hợp lệ |

## PTS-KT-012 — IEA công bố Renewables 2026: triển vọng điện mặt trời đến 2031

- Bài #147 · Quyết định: không gắn · Trạng thái: KHÔNG GẮN SẢN PHẨM
- Lý do: Tin dự báo thị trường IEA Renewables 2026, không bàn về thiết bị.

## PTS-KT-013 — Nhà dùng bao nhiêu điện mỗi tháng thì phù hợp lắp năng lượng mặt trời?

- Bài #148 · Quyết định: không gắn · Trạng thái: KHÔNG GẮN SẢN PHẨM
- Lý do: Bài hướng dẫn xác định quy mô từ hóa đơn và biểu đồ tải trước khi tư vấn; chưa đến bước chọn model, gắn sản phẩm sẽ gợi ý cấu hình chưa được khảo sát.

## PTS-KT-014 — Inverter hòa lưới và hybrid khác nhau thế nào?

- Bài #149 · Quyết định: gắn sản phẩm · Trạng thái: SẼ GẮN 4 sản phẩm
- Lý do: Bài so sánh trực tiếp inverter hòa lưới và hybrid, có mục pin lưu trữ và tải dự phòng.

| ID | Sản phẩm | Chủ đề | Lý do | Kiểm tra |
|---|---|---|---|---|
| 820 | [Inverter SolaX X1-BOOST-5-G4](/san-pham/inverter-solax-x1-boost-5-g4) | Inverter hòa lưới | Inverter hòa lưới 1 pha 5 kW để so với hybrid cùng công suất. | hợp lệ |
| 805 | [Inverter SolaX X1-HYB-5.0-LV Hybrid 5kw](/san-pham/inverter-solax-x1-hyb-50-lv-hybrid-5kw) | Inverter hybrid | Inverter hybrid 5 kW cùng mức công suất AC với model hòa lưới bên cạnh, có thêm thông số công suất dự phòng. | hợp lệ |
| 825 | [Inverter SolaX X3-MIC-10](/san-pham/inverter-solax-x3-mic-10) | Inverter hòa lưới | Inverter hòa lưới 3 pha 10 kW. | hợp lệ |
| 811 | [Inverter SolaX X3-NEO-10K-LV Hybrid 10kw](/san-pham/inverter-solax-x3-neo-10k-lv-hybrid-10kw) | Inverter hybrid | Inverter hybrid 10 kW, công suất dự phòng 11 kW, để so với model hòa lưới 10 kW. | hợp lệ |

## PTS-KT-015 — Tấm pin màu đen có luôn tốt hơn tấm pin màu xanh?

- Bài #150 · Quyết định: gắn sản phẩm · Trạng thái: SẼ GẮN 4 sản phẩm
- Lý do: Bài khuyên so thông số module (hiệu suất, cấu tạo, bảo hành) thay vì màu; liên kết các biến thể cùng công suất khác cấu tạo để so sánh.

| ID | Sản phẩm | Chủ đề | Lý do | Kiểm tra |
|---|---|---|---|---|
| 744 | [Tấm pin năng lượng mặt trời LONGi 650WP - 1 MẶT KÍNH](/san-pham/tam-pin-nang-luong-mat-troi-longi-650wp-1-mat-kinh) | Tấm pin năng lượng mặt trời | Module 650 Wp 1 mặt kính, hiệu suất 24.1%, bảo hành hiệu suất 30 năm theo trang sản phẩm. | hợp lệ |
| 745 | [Tấm pin năng lượng mặt trời LONGi 650WP - TRÀN VIỀN](/san-pham/tam-pin-nang-luong-mat-troi-longi-650wp-tran-vien) | Tấm pin năng lượng mặt trời | Cùng 650 Wp nhưng kiểu tràn viền — khác cấu tạo khung, cùng hiệu suất ghi 24.1%. | hợp lệ |
| 746 | [Tấm pin năng lượng mặt trời LONGi 650WP -2 MẶT KÍNH](/san-pham/tam-pin-nang-luong-mat-troi-longi-650wp-2-mat-kinh) | Tấm pin năng lượng mặt trời | Cùng 650 Wp, cấu tạo 2 mặt kính — minh họa lớp mặt sau mà bài nói ảnh hưởng hơn màu sắc. | hợp lệ |
| 755 | [Tấm pin năng lượng mặt trời Astronergy 625Wp](/san-pham/tam-pin-nang-luong-mat-troi-astronergy-625wp) | Tấm pin năng lượng mặt trời | Module N-Type TOPCon của hãng khác, hiệu suất 23.1%, để so thông số thay vì so màu. | hợp lệ |
