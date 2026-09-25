# Kế hoạch media theo ngữ cảnh PDP — 2026-09-25

## Mục tiêu

Mỗi vị trí ảnh trên trang chi tiết sản phẩm phải đúng với nội dung mà nó minh
họa. Ảnh sản phẩm, chứng nhận, dự án và video vẫn là dữ liệu thật từ Admin;
ảnh AI chỉ dùng cho khối “Ứng dụng” khi được ghi rõ là ảnh minh họa, không dùng
làm bằng chứng khách hàng/công trình/chứng nhận.

## Inventory hiện tại

- Gallery đầu trang: `photo` và gallery sản phẩm, khung 1:1 `contain`.
- Khối ứng dụng: `.pts-pdp-use-case-visual`, 3 ô/profile, tỉ lệ 8:5.
- Hướng dẫn lựa chọn: hiện dùng icon và thông số thật, không nên đưa ảnh cảnh
  vào nếu ảnh không bổ sung quyết định mua.
- Video: thumbnail/khung video phải đi cùng video thật; không tạo ảnh giả thay
  video.
- Chứng nhận: ảnh/HTML chứng nhận thật, `contain`, không crop.
- Dự án liên quan: ảnh dự án xác minh được, tỉ lệ 4:3; không dùng ảnh AI.
- Sản phẩm liên quan: ảnh sản phẩm thật, tỉ lệ 1:1.

## Khoảng lệch đã xác nhận

`productPdpUseCaseDefaults()` đang dùng lại `energy-system.png` cho inverter và
storage, `water-pump.png` cho cả pump controller và pump hardware, và dùng
`lighting-campus.png` cho quạt ngoài trời. Các ảnh hiện có đúng tỉ lệ nhưng
chưa đủ phân biệt ngữ cảnh kỹ thuật.

## Lộ trình render

1. Pilot đèn năng lượng mặt trời: đường nội bộ, nhà & lối đi, khuôn viên công
   trình.
2. Tấm pin: mái nhà dân dụng, mái nhà xưởng, trang trại điện mặt trời.
3. Biến tần: phòng điện gia đình, phòng điện thương mại, hệ có lưu trữ/dự
   phòng.
4. Pin lưu trữ: phòng kỹ thuật gia đình, nguồn dự phòng công trình, hệ hybrid
   điện mặt trời.
5. Bơm: tách riêng bộ điều khiển bơm (tủ điều khiển/điểm điều khiển) và máy bơm
   (ruộng tưới/bồn nước/nhà kính hoặc ao hồ).
6. Quạt: phòng ở, gian hàng/chốt trực, hiên có mái; không dùng ảnh chiếu sáng
   khuôn viên.
7. Phụ kiện: tủ bảo vệ/phân phối, kết nối trên mái, lắp đặt ngoài trời; không
   mô phỏng logo hay chuẩn kỹ thuật chưa có trong datasheet.
8. Đã render đủ 45 ảnh PNG (gồm bộ profile nền, 2 ảnh legacy riêng cho “Lối đi
   bên hông nhà”/“Nhà vườn” và 19 ảnh biến thể theo danh mục), tạo đủ 45
   sidecar WebP và nối mapping fallback trong `libraries/product_pdp_profiles.php`;
   ảnh Admin nhập riêng vẫn được ưu tiên nếu không bị lặp.
   Dữ liệu local hiện chưa có sản phẩm đang gán rõ `pump_hardware`; bộ ảnh đã
   sẵn để dùng khi Admin gán profile này.
   Profile `general` không tự gán ảnh minh họa chung để tránh sai ngữ cảnh.

## Quy chuẩn prompt và nghiệm thu

- 8:5, chủ thể nằm trong vùng an toàn trung tâm 70–75%, không chữ/logo/
  watermark, không nhãn hãng.
- Fallback minh hoạ hiện phát WebP và giữ PNG cùng tên làm fallback trong
  `picture`; không xoá bản PNG gốc để tránh lỗi trình duyệt/cached URL.
- Mỗi profile có bối cảnh riêng; không tái sử dụng một ảnh cho hai mô tả khác
  nhau nếu không cùng ý nghĩa.
- Không tạo con số công suất, hiệu suất, sản lượng hoặc chứng nhận trong ảnh.
- Ảnh Admin nhập cho content block luôn ưu tiên hơn fallback minh họa.
- Nếu nhiều content block dùng cùng một file, card đầu tiên giữ ảnh Admin;
  các card lặp được thay bằng fallback theo tiêu đề/profile để tránh hiển thị
  một cảnh ba lần.
- Kiểm tra `naturalWidth`, ảnh hỏng, crop 8:5, responsive 320px/desktop lớn và
  `git diff --check` trước khi nối vào template.

## Audit sau khi nối asset — 2026-09-25

- Quét 415 sản phẩm đang hiển thị: 415/415 PDP có khối ứng dụng dùng
  `pdp-usecase-v2`, 0 PDP còn trỏ thư mục fallback cũ.
- Kiểm tra ảnh trong 3 card ứng dụng trên từng PDP: 0 trang bị trùng `src` giữa
  các card sau khi bật cơ chế chống lặp.
- Quét `table_content_section` và gallery sản phẩm: chưa phát hiện media trùng
  trong cùng một nhóm PDP.
- Screenshot cũ vẫn có thể xuất hiện nếu đang xem production/cache chưa nhận
  source mới; chưa deploy production trong lượt này.

## Kế hoạch tách biến thể theo danh mục — 2026-09-25

Audit dữ liệu cho thấy profile cha `lighting` đang bao phủ nhiều kiểu thiết bị
khác nhau. Riêng local có 133 sản phẩm ở `den-nlmt`, 68 ở `den-san-vuon` và 25
ở `tru-den`; ngoài ra 66 phụ kiện đang trộn dây/cáp, bộ điều khiển sạc và
thiết bị đo. Vì vậy chỉ kiểm tra ảnh trùng là chưa đủ.

Lộ trình xử lý:

1. Resolver biến thể đọc tên/slug, slug danh mục và trường Ứng dụng/Lắp đặt
   trong Admin, ưu tiên biến thể cụ thể hơn profile cha: đèn đường, đèn pha,
   đèn sân vườn, đèn trụ cổng, đèn treo tường, đèn ốp trần, đèn UFO; phụ kiện
   cáp/kết nối và bộ điều khiển sạc/đo lường.
2. Đã render và nối 3 ảnh ứng dụng cho từng biến thể; không lấy ảnh sân vườn
   cho đèn đường hoặc ảnh cảnh quan cho bộ điều khiển sạc. Sản phẩm chỉ có mã
   model nhưng trường Ứng dụng nêu rõ “đường nội bộ” cũng được đưa vào nhóm
   đèn đường thay vì rơi về ảnh chung.
3. Sản phẩm mới chưa có tín hiệu phân loại sẽ dùng fallback trung tính của
   profile và được audit lại khi thêm danh mục/metadata mới.
4. Đã audit lại toàn bộ PDP: không có biến thể thiếu asset, không trùng ảnh,
   asset WebP/PNG đều tồn tại và HTTP render không vỡ.

Bộ biến thể đã render và nối vào PDP:

- `lighting_street`: đường nội bộ, bãi xe, đường khuôn viên.
- `lighting_flood`: sân/kho, nhà xưởng, khu vực ngoài trời cần phủ sáng.
- `lighting_garden`: lối đi sân vườn, courtyard, homestay/resort.
- `lighting_pole`: cổng/hàng rào, lối vào, khuôn viên cảnh quan.
- `lighting_wall`: tường rào, lối đi hẹp, hiên/mặt tiền.
- `lighting_ceiling`: hiên có mái, sảnh, kho/xưởng có mái.
- `lighting_ufo`: kho/xưởng cao, sân sản xuất, khu vực rộng cần đèn treo.
- `accessory_cable`: tuyến cáp, ống bảo vệ, điểm tiếp địa ngoài trời.
- `accessory_controller`: tủ điều khiển sạc, điểm giám sát, phòng kỹ thuật.

### Kết quả phân loại sau khi render

- `lighting_street`: 57 sản phẩm; `lighting_flood`: 72; `lighting_garden`: 6;
  `lighting_pole`: 78; `lighting_wall`: 9; `lighting_ceiling`: 3;
  `lighting_ufo`: 1.
- `accessory_cable`: 23 sản phẩm; `accessory_controller`: 43.
- 415/415 sản phẩm đang hiển thị có bộ ảnh fallback hợp lệ; 0 asset biến thể
  bị thiếu.
