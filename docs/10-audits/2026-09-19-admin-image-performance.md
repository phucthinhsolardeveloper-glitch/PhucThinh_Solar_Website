# Rà soát hiệu năng Admin và ảnh — 2026-09-19

## Hiện tượng và nguyên nhân

- Form sửa sản phẩm `#381` không lưu được ảnh vì dữ liệu legacy có hai thông số
  cùng nhãn `Cánh quạt`; validator dừng toàn bộ request trước bước upload.
- Mỗi thumbnail động giữ khóa PHP session và quét đệ quy toàn bộ cây cache để
  xóa file cũ. Nhiều ảnh tải song song vì vậy bị xếp hàng và tốn I/O lặp lại.
- Lần render HTML có thể tự mã hóa ảnh gốc sang WebP nếu sidecar chưa tồn tại,
  làm TTFB tăng không ổn định; tải lại nhanh hơn vì sidecar đã được tạo.
- Lần tạo thumbnail đầu bị chính sách `no-store` của PHP nên trình duyệt không
  cache phản hồi động. Admin cũng tải ApexCharts và CKEditor ở màn không dùng.
- Khi submit hợp lệ, overlay tiến trình bị đóng trước khi upload/xử lý xong nên
  người dùng thấy trang đứng yên và dễ bấm Lưu nhiều lần.

## Thay đổi

- CRUD sản phẩm cho phép bảo toàn nhãn thông số trùng; bulk import vẫn từ chối
  nhãn trùng theo mặc định.
- Thumbnail/API chỉ đọc giải phóng khóa session sớm.
- Dọn cache ảnh có `flock` và marker giới hạn tần suất thay vì chạy mỗi request;
  thumbnail được ghi qua file tạm rồi rename, không quét zero-byte toàn cây.
- HTML chỉ dùng WebP sidecar hợp lệ đã có; encode WebP tiếp tục thực hiện ở lúc
  upload. Thumbnail động phát cache header 30 ngày ngay từ request đầu.
- Preview ảnh Admin dùng thumbnail 250×250; ApexCharts chỉ nạp ở dashboard và
  CKEditor chỉ nạp ở các form có editor.
- Submit hợp lệ giữ overlay và khóa thao tác lặp trong lúc chờ chuyển trang.

## Kiểm thử và rollback

- `./scripts/predeploy-local.sh`: PASS toàn bộ regression, Admin HTTP, commerce,
  migration preflight, smoke public và restore rehearsal.
- Test mục tiêu: parser giữ hai dòng `Cánh quạt`; bulk vẫn chặn duplicate; cleanup
  chỉ chạy khi đến hạn; WebP sidecar stale bị bỏ qua; thumbnail động trả WebP và
  `Cache-Control: public, max-age=2592000, immutable`.
- Đo local: thumbnail đầu `~133 ms`, request kế tiếp từ file cache `~2.5 ms`.
- Rollback code: dùng `deploy/vps/scripts/rollback-release.sh`; thay đổi này không
  có migration schema. Ảnh/DB production phải rollback theo manifest và backup
  pre-deploy riêng, không xóa upload cũ.
