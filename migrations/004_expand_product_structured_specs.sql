-- Chuyển thông số kỹ thuật sản phẩm sang các cột có cấu trúc.
-- Chạy script 005 ở chế độ dry-run trước khi ghi dữ liệu đã có.

ALTER TABLE `table_product`
    ADD COLUMN `light_source` VARCHAR(255) NULL DEFAULT NULL AFTER `panel_power`,
    ADD COLUMN `lamp_dimensions` VARCHAR(150) NULL DEFAULT NULL AFTER `light_source`,
    ADD COLUMN `color_temperature` VARCHAR(100) NULL DEFAULT NULL AFTER `lamp_dimensions`,
    ADD COLUMN `light_color` VARCHAR(100) NULL DEFAULT NULL AFTER `color_temperature`,
    ADD COLUMN `installation_height` VARCHAR(100) NULL DEFAULT NULL AFTER `lighting_area`,
    ADD COLUMN `panel_dimensions` VARCHAR(150) NULL DEFAULT NULL AFTER `installation_height`,
    ADD COLUMN `package_contents` TEXT NULL DEFAULT NULL AFTER `panel_dimensions`,
    ADD COLUMN `carton_quantity` VARCHAR(100) NULL DEFAULT NULL AFTER `package_contents`,
    ADD COLUMN `carton_dimensions` VARCHAR(150) NULL DEFAULT NULL AFTER `carton_quantity`,
    ADD COLUMN `warranty` VARCHAR(150) NULL DEFAULT NULL AFTER `carton_dimensions`,
    ADD COLUMN `additional_specs` MEDIUMTEXT NULL DEFAULT NULL AFTER `warranty`;

-- Rollback chỉ xóa các cột mới; thongsokythuatvi/en cũ vẫn được giữ làm bản lưu.
