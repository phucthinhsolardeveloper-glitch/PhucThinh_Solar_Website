# Audit dependency và asset legacy — 2026-08-31

## Kết quả

Đã lập map CSS, JS, font, ảnh và video dựa trên layout include, source/template/API, cấu hình type, Git inventory, filesystem local và baseline browser hiện có. Không xóa, di chuyển hoặc tách asset trong đợt này.

- Public đang nạp 76–77 CSS/JS local, khoảng 3,31 MB trước gzip. Nhiều plugin có consumer trong markup động/AJAX nên chưa có asset nào đủ bằng chứng để loại chỉ bằng static scan.
- Git theo dõi khoảng 250,6 MB/1.438 entry trong `assets/`. `assets/wp-content/uploads` chứa fallback đang phục vụ 85 URL legacy đã xác minh, bắt buộc giữ trong release hiện tại.
- Storage local đo được: `upload/` 157 MB, `thumbs/` 75 MB, `wp-content/` root 1,2 GB và `caches/` 364 KB. Đây là các lớp dữ liệu/runtime khác nhau, không được gom vào một thao tác cleanup.
- Phát hiện ứng viên dung lượng lớn gồm MP4 8–16 MB, PNG 9–15 MB và font SVG trùng giữa hai cây theme. Chưa có phê duyệt xóa và chưa chứng minh mọi CSS path/DB consumer đã thay thế.
- Video còn ít nhất bốn luồng: YouTube sản phẩm, gallery video bài viết, thư viện Video legacy và MP4 upload/comment. Staging vẫn phải retest upload/playback và endpoint legacy.

Map chi tiết và cổng xác minh nằm tại [Dependency và asset map](../01-architecture/dependency-map.md).

## Giới hạn

Dataset/filesystem local không thay thế inventory production. External CDN, CSS path tương đối, nội dung HTML trong DB và tương tác chỉ xuất hiện sau JavaScript có thể tạo consumer mà static scan không thấy. Vì vậy hạng mục này hoàn tất ở mức lập bản đồ local, chưa cho phép archive media hoặc conditional loading mới.

## Rollback

Chỉ hoàn nguyên tài liệu của batch này; không có thay đổi runtime, database hoặc asset.
