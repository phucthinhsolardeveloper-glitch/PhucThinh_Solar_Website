# Audit index route public — 2026-08-31

Router tìm slug tuần tự trong các bảng phân cấp sản phẩm/tin tức. Trước migration, 11 bảng chỉ có primary key; `EXPLAIN` cho truy vấn chi tiết sản phẩm và giải pháp đều là full table scan.

Migration `007_add_public_route_indexes.sql` bổ sung:

- `idx_route_type_slug (type, slugvi(191))` cho lookup route;
- `idx_type_numb_id (type, numb, id)` cho danh sách theo loại và thứ tự.

Prefix 191 ký tự giữ kích thước index tương thích MyISAM/utf8mb4 hiện tại. Migration dùng `ADD INDEX IF NOT EXISTS`, đã chạy lại hai lần trên MariaDB 10.4 local mà không tạo index trùng.

Sau migration, lookup route chuyển từ `ALL`/ước tính 81–82 dòng sang `ref` qua `idx_route_type_slug`/ước tính 1 dòng. Sitemap vẫn có thể filesort do trạng thái lưu dạng chuỗi và lọc bằng `FIND_IN_SET`; chưa thay schema status trong đợt này vì ảnh hưởng rộng.

Migration chỉ được áp dụng trên Docker local sau backup. Production/staging phải backup database, kiểm tra index hiện có và chạy migration riêng ngoài archive webroot. Rollback bằng cách drop hai index trên từng bảng; không thay đổi dữ liệu nghiệp vụ.

