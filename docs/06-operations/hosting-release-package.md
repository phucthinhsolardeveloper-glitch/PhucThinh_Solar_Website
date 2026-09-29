# Gói release đưa lên hosting

Script `scripts/package-hosting-release.sh` tạo hai archive từ đúng một commit
Git, đặt ngoài webroot tại `backup/releases/`. Gói không lấy file chưa được Git
theo dõi nên không chứa cấu hình thật, dữ liệu upload, log, cache, backup hoặc
thư mục bàn giao cục bộ. Media legacy trong `assets/wp-content/uploads` vẫn được
đóng gói vì nội dung public cần fallback từ URL WordPress cũ.

## Tạo và kiểm tra gói

```bash
./scripts/package-hosting-release.sh
cd backup/releases/<release>
sha256sum -c SHA256SUMS
```

Mỗi release gồm:

- `public_html-<commit>.tar.gz`: code webroot, không chứa config/runtime.
- `deployment-tools-<commit>.tar.gz`: migration, script kiểm tra/deploy, bộ icon
  danh mục và runbook DirectAdmin.
- Danh sách file, metadata commit/rollback và checksum cho cả hai archive.

Script từ chối chạy khi source tracked chưa sạch hoặc archive webroot chứa
đường dẫn nhạy cảm đã quy định.

Bộ ảnh ORIANA lớn được đóng gói riêng để không lẫn với release code:

```bash
./scripts/package-oriana-media.sh
cd backup/media-releases/<release>
sha256sum -c SHA256SUMS
```

Script chỉ nhận đúng bộ 102 file ảnh/98 nội dung duy nhất và kèm importer ghép
sản phẩm theo `code`, không theo ID của DB local.

## Nguyên tắc triển khai

- Giải nén thử vào thư mục staging độc lập; không giải nén trực tiếp đè production.
- Giữ nguyên `libraries/config.php` và các thư mục dữ liệu runtime của hosting: `upload`, `thumbs`, `watermark`, `caches`, `logs`.
- Giữ nguyên `/wp-content/uploads` trên hosting. Source còn có fallback media tĩnh trong `/assets/wp-content/uploads` để phục hồi URL WordPress cũ bị thiếu.
- Backup code, database và upload production trước cutover; xác minh checksum trước khi dùng gói.
- Các file trong `migrations/` không nằm trong archive webroot mà ở archive
  deployment tools. Chạy `000_check_hosting_runtime.php` và
  `000_preflight_production.php` trước; chỉ chạy migration production còn thiếu.
- Icon danh mục và ảnh ORIANA là thay đổi dữ liệu/media riêng: luôn dry-run,
  backup DB + upload và không ghi đè ảnh quản trị viên đã chọn.
- Sau triển khai, chạy smoke P0, kiểm tra log và chỉ mở traffic khi kết quả sạch.

## Rollback

Triển khai lại archive release trước có checksum đã lưu. Không tự động rollback database: chỉ phục hồi từ backup pre-deploy đã kiểm chứng nếu release thực sự thay đổi schema/data và việc hoàn nguyên đã được đánh giá. Giữ nguyên upload phát sinh sau cutover, trừ khi có kế hoạch đối soát riêng.

## Chính sách lưu giữ release và backup

- Giữ 1–2 full backup ứng dụng gần nhất, đã kiểm checksum; backup CloudFly là
  lớp snapshot VPS riêng do nhà cung cấp quản lý.
- Trên VPS, giữ release hiện tại, release trước đó và tối đa một release rollback
  thêm. `incoming/` chỉ là staging và có thể dọn archive cũ sau khi deploy,
  smoke test và checksum đã hoàn tất.
- Không xóa `shared/upload` hoặc dữ liệu runtime đang được mount. Chỉ dọn
  thumbnail/cache/log khi đã kiểm tra chúng có thể tái tạo theo runbook.
