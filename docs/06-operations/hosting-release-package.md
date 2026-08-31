# Gói release đưa lên hosting

Script `scripts/package-hosting-release.sh` tạo gói source từ đúng một commit Git, đặt ngoài webroot tại `backup/releases/`. Gói không lấy file chưa được Git theo dõi nên không chứa cấu hình thật, dữ liệu upload, log, cache, backup hoặc thư mục bàn giao cục bộ. Media legacy trong `assets/wp-content/uploads` vẫn được đóng gói vì nội dung public cần fallback từ URL WordPress cũ.

## Tạo và kiểm tra gói

```bash
./scripts/package-hosting-release.sh
cd backup/releases/<release>
sha256sum -c SHA256SUMS
```

Mỗi release gồm archive `public_html-<commit>.tar.gz`, danh sách file, metadata commit/rollback và checksum. Script từ chối chạy khi source tracked chưa sạch hoặc archive chứa đường dẫn nhạy cảm đã quy định.

## Nguyên tắc triển khai

- Giải nén thử vào thư mục staging độc lập; không giải nén trực tiếp đè production.
- Giữ nguyên `libraries/config.php` và các thư mục dữ liệu runtime của hosting: `upload`, `thumbs`, `watermark`, `caches`, `logs`.
- Giữ nguyên `/wp-content/uploads` trên hosting. Source còn có fallback media tĩnh trong `/assets/wp-content/uploads` để phục hồi URL WordPress cũ bị thiếu.
- Backup code, database và upload production trước cutover; xác minh checksum trước khi dùng gói.
- Các file trong `migrations/` không nằm trong archive webroot. Chỉ chạy migration riêng sau khi đọc nội dung, backup database và xác nhận migration nào production còn thiếu.
- Sau triển khai, chạy smoke P0, kiểm tra log và chỉ mở traffic khi kết quả sạch.

## Rollback

Triển khai lại archive release trước có checksum đã lưu. Không tự động rollback database: chỉ phục hồi từ backup pre-deploy đã kiểm chứng nếu release thực sự thay đổi schema/data và việc hoàn nguyên đã được đánh giá. Giữ nguyên upload phát sinh sau cutover, trừ khi có kế hoạch đối soát riêng.
