-- Cho phép khối nội dung gắn vào danh mục cấp 2 (product_cat).
-- Thứ tự kế thừa mới: sản phẩm > hãng > danh mục cấp 2 > danh mục cấp 1.
ALTER TABLE `table_content_section`
    MODIFY COLUMN `scope` ENUM('product','brand','list','cat') NOT NULL;

-- Rollback (chỉ khi chưa có dòng scope='cat'):
-- ALTER TABLE `table_content_section` MODIFY COLUMN `scope` ENUM('product','brand','list') NOT NULL;
