-- Chế độ nội dung mô tả theo từng phạm vi.
--   own    : dùng khối của chính bản ghi (mặc định khi có khối)
--   inherit: sản phẩm kế thừa hãng -> danh mục (mặc định khi không có khối)
--   hide   : không hiện phần mô tả dài (kể cả kế thừa lẫn đoạn tự sinh)
ALTER TABLE `table_content_section`
    ADD COLUMN IF NOT EXISTS `mode` VARCHAR(16) NOT NULL DEFAULT 'own' AFTER `scope_id`;

-- Rollback: ALTER TABLE `table_content_section` DROP COLUMN `mode`;
