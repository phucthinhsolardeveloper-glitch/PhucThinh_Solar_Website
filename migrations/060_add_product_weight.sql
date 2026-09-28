-- Cân nặng sản phẩm (gram) - cần cho tính phí Nhất Tín/nhà xe thật theo cân
-- nặng sau này. Hiện DB chỉ có carton_dimensions (text tự do), không có cột
-- số nào để tính phí vận chuyển theo khối lượng.
-- Chỉ chạy sau backup + preflight trên staging/production.

ALTER TABLE `table_product`
    ADD COLUMN IF NOT EXISTS `weight` INT UNSIGNED NULL AFTER `carton_dimensions`;

-- Rollback: chỉ thực hiện sau khi xác nhận không còn dữ liệu cân nặng cần giữ.
-- ALTER TABLE `table_product` DROP COLUMN `weight`;
