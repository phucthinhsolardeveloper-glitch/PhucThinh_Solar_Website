-- Ảnh khuyến mãi/sự kiện riêng cho từng sản phẩm.
-- Khi cờ trạng thái "khuyenmai" bật và có ảnh này, card sản phẩm sẽ hiện ảnh
-- này thay ảnh sản phẩm thường (dùng cho các đợt khuyến mãi/sự kiện theo mùa,
-- ví dụ 2/9, tương tự cách Kitawa Solar Light thay ảnh card theo chiến dịch).
-- Cột nullable, không ảnh hưởng dữ liệu sản phẩm hiện có.
ALTER TABLE `table_product`
    ADD COLUMN IF NOT EXISTS `photo_khuyenmai` VARCHAR(255) NULL DEFAULT NULL AFTER `photo2`;

-- Rollback: ALTER TABLE `table_product` DROP COLUMN `photo_khuyenmai`;
