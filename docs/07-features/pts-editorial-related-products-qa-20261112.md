# QA liên kết sản phẩm cho bài Kiến thức — 2026-11-12

Phạm vi: 21 bài ẩn `PTS-KT-001..021` và 12 bài live cũ. ID/tên sản phẩm trong cột đề xuất chỉ được chấp nhận khi truy vấn local xác nhận sản phẩm visible, danh mục visible, slug hợp lệ và có ảnh; không suy đoán theo tên. Manifest nguồn là `backend/resources/editorial/2026-11-12/related-products.json`.

| ID | Chủ đề | Liên kết hiện có | Đề xuất verified live (ID — tên) | Lý do / rủi ro / opt-out |
|---|---|---:|---|---|
| PTS-KT-001 | Chuỗi PV → inverter → tải, hybrid/lưu trữ | giữ nguyên nếu có | 820 — SolaX X1-BOOST-5-G4; 805 — X1-HYB-5.0-LV; 846 — T-BAT TSYS-D53 | minh họa đúng các thiết bị bài nói; không hàm ý cấu hình tương thích |
| PTS-KT-002 | Đọc datasheet module | giữ nguyên nếu có | 741 — LONGi 620WP; 744 — LONGi 650WP; 755 — Astronergy 625Wp; 753 — Astronergy 610WP | đối chiếu Voc/Isc/Vmp/hiệu suất; không xếp hạng chất lượng |
| PTS-KT-003 | Tin IEA PVPS | giữ nguyên | Không gắn SKU | tin ngành, gắn sản phẩm là ghép từ khóa; ưu tiên bài/giải pháp liên quan |
| PTS-KT-004 | kW/kWp/kWh | giữ nguyên nếu có | 755 — Astronergy 625Wp | trùng ví dụ 625 Wp; không gắn inverter ngoài nội dung |
| PTS-KT-005 | Chức năng và loại inverter | giữ nguyên nếu có | 820; 825 — X3-MIC-10; 805; 811 — X3-NEO-10K-LV | minh họa hòa lưới/hybrid; không khẳng định phù hợp công trình |
| PTS-KT-006 | Nhu cầu điện buổi tối | giữ nguyên nếu có | 846; 847 — T-BAT-SYS-LV D150 Pro; 805; 806 — X1-HYB-6.0-LV | bài nói lưu trữ/hybrid; cần khảo sát tải trước khi tư vấn |
| PTS-KT-007 | Hòa lưới/hybrid/độc lập | giữ nguyên nếu có | 820; 805; 846 | chỉ minh họa các nhánh quyết định, không phải BOM |
| PTS-KT-008 | Chọn đèn theo lumen/lux | giữ nguyên nếu có | 2201 — JINDIAN JD-19600L; 507 — ORIANA DB02-2; 610 — BLUE CARBON BCT-OLF-100W; 629 — JINDIAN JD-369 | đối chiếu thông số trang sản phẩm; không suy ra độ sáng thực địa |
| PTS-KT-009 | PV khi mây/ánh sáng yếu | giữ nguyên | Không gắn SKU | bài giải thích bức xạ và đo lường, chưa chọn model |
| PTS-KT-010 | Mất điện/chống đảo lưới/backup | giữ nguyên nếu có | 820; 806; 811 | minh họa khác biệt thiết bị; không cam kết cấp điện khi mất lưới |
| PTS-KT-011 | So 600–720 Wp và kích thước mái | giữ nguyên nếu có | 741; 744; 746; 753 | so công suất/kết cấu; không kết luận model nào tốt hơn |
| PTS-KT-012 | Dự báo IEA Renewables | giữ nguyên | Không gắn SKU | bản tin dự báo, không phải tư vấn thiết bị |
| PTS-KT-013 | Đọc nhu cầu từ hóa đơn | giữ nguyên | Không gắn SKU | cần dữ liệu tải và khảo sát; ưu tiên PTS-KT-016/giải pháp |
| PTS-KT-014 | So hòa lưới và hybrid | giữ nguyên nếu có | 820; 805; 825; 811 | cặp model cùng mức công suất để minh họa; không claim tương thích |
| PTS-KT-015 | Module đen/xanh | giữ nguyên nếu có | 744; 745; 746; 755 | so cấu tạo/thông số, không biến màu thành claim chất lượng |
| PTS-KT-016 | Chuẩn bị hóa đơn khảo sát | giữ nguyên | Không gắn SKU | quy trình thu thập dữ liệu, chưa đến bước chọn model |
| PTS-KT-017 | Watt/lumen/lux cho lối đi | giữ nguyên | Không gắn SKU | cần xác minh vùng sáng và thông số đèn theo vị trí |
| PTS-KT-018 | Lắp thêm tấm pin/điện tự dùng | giữ nguyên | Không gắn SKU | phụ thuộc mái, tải, inverter và kinh tế; không gắn cưỡng ép |
| PTS-KT-019 | Khảo sát mái tôn/ngói/bằng | giữ nguyên | Không gắn SKU | chưa có thuộc tính tương thích mái được xác minh |
| PTS-KT-020 | TOPCon/HJT/BC | giữ nguyên | Không gắn SKU | phải xác minh thuộc tính cell/model; không phân loại chỉ từ tên |
| PTS-KT-021 | Mái dưới module và thông gió | giữ nguyên | Không gắn SKU | không có bằng chứng SKU tạo mức mát cụ thể |

## 12 bài live cũ

Đã audit theo cùng bộ lọc: giữ nguyên mọi lựa chọn trong `post_products`; không tự thêm/xóa/reorder. Các liên kết hiện có chỉ được public khi `pp.is_visible=1`, `products.is_visible=1`, danh mục visible, slug hợp lệ và ảnh hợp lệ; truy vấn chi tiết của 12 bài cần chạy ở local DB và xuất cùng manifest report, không ghi con số suy đoán vào tài liệu.

## Guardrails và rollback

`pts:editorial-product-links` mặc định dry-run, hỗ trợ `--ids=PTS-KT-...`, tối đa 4 sản phẩm/bài, chỉ xem bài ẩn và không có lựa chọn hiện tại. `--apply` bị từ chối trên production; trước mỗi transaction ghi các dòng pivot hiện tại vào `storage/app/private/editorial-product-links` (hoặc `--backup`) và transaction dừng khi phát hiện drift. Không có DB apply trong task này.
