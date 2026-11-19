# PTS Floodlight Image Composer

Dựng bộ ảnh bằng nguồn sản phẩm và dữ liệu trong `input/den-pha-72.json`. Mọi ảnh hoàn thiện nằm ở `output/` ngoài git. Mỗi mã có 7 JPG; thêm `08.jpg` khi thương hiệu có giấy ủy quyền đã cung cấp.

## Chạy

```bash
npm install --prefix tools/product-image-composer
node tools/product-image-composer/build.mjs --code BCT-WR1.0
node tools/product-image-composer/qa.mjs --code BCT-WR1.0
node tools/product-image-composer/build.mjs --brand "BLUE CARBON"
node tools/product-image-composer/build.mjs --all
```

Có thể truyền `--input-root` và `--output-root` để đổi thư mục artifacts. Bộ chạy hàng loạt nên chỉ được dùng sau khi mã thử đạt QA trực quan, so sánh mẫu và DOM.

## Dựng ảnh đại diện đèn pha

Ảnh avatar dùng nền đã làm sạch từ `input/mau-7-anh/1.png`, ghi JPG và QA vào `output/avatar/`. Chạy toàn bộ 72 mã theo thứ tự hãng:

```bash
node tools/product-image-composer/avatar.mjs \
  --shopping-codes-file /home/admin01/orca/artifacts/pts-floodlight-images-20261117/output/_codes-39.txt
```

File mã truyền vào `--shopping-codes-file` sẽ dùng thứ tự nguồn `feed_image` → `image_secondary` → `gallery` → `image`; các mã còn lại giữ `image`. Mã không có ảnh nguồn đạt điều kiện sẽ vào `output/_can-xu-ly.md`, không xuất ảnh avatar. Chạy riêng một mã bằng `--code BCT-WR1.0`; chỉ tạo lại nền từ mẫu 1 khi thật sự cần bằng `--rebuild-plate`.

## Ghi chú renderer và kiểm tra

Bản trong sandbox này xuất SVG bằng `sharp` rồi mã hóa JPG sRGB 1600×1600 chất lượng 90. `playwright` được khai báo cho kiểm tra DOM nhưng Chromium trong môi trường hiện tại không khởi chạy được (sandbox chặn thao tác tiến trình); vì vậy `qa.mjs` ghi `DOM=NOT_RUN` và không cho qua cổng QA. Khi chạy ở máy có Chromium hoạt động, thêm bước Playwright đo bounding box text, margin, overlap và overflow trước khi coi kết quả là đạt.

Ảnh sản phẩm alpha được giữ nguyên làm nguồn. Ảnh nền trắng dùng flood-fill cạnh, ngưỡng RGB>245; cần đánh giá riêng từng mã vì vùng trắng sáng của thiết bị có thể nối với nền. Không xuất bộ khi ảnh sản phẩm không tách sạch. Họa tiết nền chung và prompt được lưu trong `output/nen/`; nội dung tĩnh nằm trong mã và nguồn chữ được ghi vào `qa.json`.

Phông đang dùng khi render fallback là Liberation Sans (gần sans đậm của mẫu, đủ glyph tiếng Việt trong môi trường). Trang web lưu Be Vietnam Pro dạng WOFF2; `sharp`/librsvg của sandbox không nạp được WOFF2. Khi render bằng trình duyệt, chuyển template sang Be Vietnam Pro 700/800 để khớp font website hơn.
