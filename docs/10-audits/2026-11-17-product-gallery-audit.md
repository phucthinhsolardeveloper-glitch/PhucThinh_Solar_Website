# Audit ảnh sản phẩm · Phúc Thịnh Solar

Snapshot production: 17/11/2026, sau lô 4 gallery SolaX.

## Kết quả

- Tổng đang hiển thị: 374
- Không ảnh: 53
- Đúng 1 ảnh: 249
- Đúng 2 ảnh: 20
- Có từ 3 ảnh: 52
- Đã bổ sung 17/11/2026: 4 SKU (#811, #812, #813, #814).

## Phân bổ theo danh mục

|Danh mục|Tổng|0 ảnh|1 ảnh|2 ảnh|3+ ảnh|
|---|---:|---:|---:|---:|---:|
|Đèn trụ cổng|53|3|50|0|0|
|Đèn Đường|57|12|31|2|12|
|Tấm pin năng lượng mặt trời|29|10|18|0|1|
|Đèn Pha|72|0|26|14|32|
|Biến tần bơm NLMT|23|0|23|0|0|
|Bộ điều khiển sạc PWM|22|0|22|0|0|
|Bộ điều khiển sạc MPPT|17|3|14|0|0|
|Hòa lưới|15|0|15|0|0|
|Phụ kiện đèn NLMT|14|13|1|0|0|
|Đèn treo tường|9|0|9|0|0|
|Hybrid / lưu trữ|12|3|5|4|0|
|Hệ thống ESS|7|0|7|0|0|
|Quạt năng lượng mặt trời|7|0|6|0|1|
|Đèn sân vườn / đèn nấm|5|1|4|0|0|
|Tiếp địa & ống bảo vệ|4|0|4|0|0|
|Pin / Battery|4|1|3|0|0|
|Giám sát / đo lường|4|1|3|0|0|
|Cáp & dây điện|3|0|3|0|0|
|Máy bơm NLMT|3|2|1|0|0|
|Vật tư phụ trợ|4|2|0|0|2|
|Đèn UFO|3|0|2|0|1|
|BMS / Rack & phụ kiện|2|2|0|0|0|
|Đèn Ốp Trần|3|0|1|0|2|
|Kẹp tấm pin|1|0|1|0|0|
|Đèn cắm cỏ|1|0|0|0|1|

## Phương pháp

- Đếm unique URL có file thực: products.image, products.image_secondary, product_images visible; không tính ảnh Shopping.
- Ảnh đại diện hiện hữu của 4 SKU SolaX đã giữ nguyên. Bổ sung 4 infographic kỹ thuật ở bảng product_images.
- Nguồn thông số 4 infographic: https://vn.solaxpower.com/products/x3-neo-lv.html
- Độ đầy đủ thông số kỹ thuật cần đánh giá theo từng ngành hàng riêng. Số trường chung hiện chỉ là số lượng thô, KHÔNG phải tiêu chuẩn hoàn thiện.
- Ảnh cũ từ file báo giá có nhiều ảnh độ phân giải thấp; chưa được phép coi tất cả là ảnh xuất bản.

## Tệp

- 2026-11-17-product-gallery-inventory.csv: đầy đủ 374 SKU.
- 2026-11-17-product-gallery-audit.html: xem, tìm, lọc tất cả 374 SKU.

## Việc tiếp theo

1. 53 SKU không ảnh: săn ảnh hãng và tài nguyên nội bộ hợp lệ trước.
2. 249 SKU chỉ một ảnh: tiếp tục tìm góc chụp thật, sơ đồ thiết bị và infographic kỹ thuật theo model.
3. Audit field specs theo templates category rồi bổ sung thông số từng SKU có datasheet.
4. Mỗi batch chạy backup, ghi gallery idempotent, kiểm tra API và trang public, không thay đổi avatar/Shopping nếu không cần thiết.
## Cảnh báo chất lượng ảnh cần kiểm tra riêng (không được tự sửa)

- 6 mã LONGi khác model đang chung chính xác avatar `lr8-66hgd-620m-6450-chuan1600.jpg`: ID 740, 741, 742, 743, 744, 745; ảnh có tên theo LR8-66HGD-620M dù gán sang tấm 540, 620, 645 và 650W. Rủi ro sai SKU/khác công nghệ tấm; cần ảnh đúng từ datasheet hãng cho từng mã.
- 5 mã Astronergy có avatar chung `astro-n7-630w-7642.png`: ID 752, 753, 754, 755, 756; cần xác minh 595, 610, 615, 625, 630W.
- Product ID 384, SKU JD-188L, có avatar file `jd-198l-3448-chuan1600.jpg` không trùng mã đang bán. Có thể nhầm ảnh JD-198L.
- Đếm ảnh không phát hiện những lỗi sai model kể trên: cần thêm rule cross-model image reuse và human review trước khi nạp media.
- Sơ bộ API SolaX X3-NEO-LV chưa điền nhiều field spec chuẩn hóa; ảnh thông số tạo đúng từ nguồn hãng nhưng bước tiếp cần đồng bộ bảng thông số hiển thị trong PDP.
