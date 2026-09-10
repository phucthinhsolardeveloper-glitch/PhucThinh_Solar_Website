-- Cho phép chọn RÕ nguồn kế thừa khi mô tả sản phẩm ở chế độ "Kế thừa" hoặc
-- "Nội dung riêng + kế thừa", thay vì luôn tự động ưu tiên hãng trước danh
-- mục. Yêu cầu chủ project 2026-09-10: hãng "xa" sản phẩm hơn danh mục, danh
-- mục cấp càng gần sản phẩm thì càng sát nội dung nên nên ưu tiên hơn; cũng
-- muốn có tuỳ chọn cộng dồn nhiều nguồn dù chưa chắc dùng tới.
--
-- Giá trị: 'auto' (mặc định, giữ đúng hành vi cũ: hãng > danh mục cấp 2 >
-- cấp 1) | 'brand' (chỉ hãng) | 'category' (chỉ danh mục, ưu tiên cấp 2, cấp
-- 1 nếu cấp 2 chưa có) | 'stack' (cộng dồn mọi nguồn có nội dung: danh mục
-- cấp 2 + cấp 1 + hãng, theo thứ tự gần tới xa).
ALTER TABLE `table_content_section`
    ADD COLUMN IF NOT EXISTS `inherit_source` VARCHAR(16) NOT NULL DEFAULT 'auto' AFTER `mode`;

-- Rollback: ALTER TABLE `table_content_section` DROP COLUMN `inherit_source`;
