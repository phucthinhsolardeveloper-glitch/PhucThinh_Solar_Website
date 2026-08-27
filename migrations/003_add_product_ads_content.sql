-- Bổ sung dữ liệu phục vụ trang chi tiết sản phẩm theo checklist quảng cáo.
-- Chạy sau migration 001 trên database đích.

ALTER TABLE `table_product`
    ADD COLUMN `video_url` VARCHAR(500) NULL DEFAULT NULL AFTER `cong_suat`,
    ADD COLUMN `battery_capacity` VARCHAR(100) NULL DEFAULT NULL AFTER `video_url`,
    ADD COLUMN `panel_power` VARCHAR(100) NULL DEFAULT NULL AFTER `battery_capacity`,
    ADD COLUMN `material` VARCHAR(255) NULL DEFAULT NULL AFTER `panel_power`,
    ADD COLUMN `charging_time` VARCHAR(100) NULL DEFAULT NULL AFTER `material`,
    ADD COLUMN `lighting_time` VARCHAR(100) NULL DEFAULT NULL AFTER `charging_time`,
    ADD COLUMN `waterproof_rating` VARCHAR(50) NULL DEFAULT NULL AFTER `lighting_time`,
    ADD COLUMN `led_chip` VARCHAR(100) NULL DEFAULT NULL AFTER `waterproof_rating`,
    ADD COLUMN `lighting_area` VARCHAR(100) NULL DEFAULT NULL AFTER `led_chip`,
    ADD COLUMN `certificatevi` MEDIUMTEXT NULL DEFAULT NULL AFTER `lighting_area`,
    ADD COLUMN `certificateen` MEDIUMTEXT NULL DEFAULT NULL AFTER `certificatevi`;

-- Rollback (chỉ dùng khi chắc chắn chưa có dữ liệu cần giữ):
-- ALTER TABLE `table_product` DROP COLUMN `video_url`, DROP COLUMN `battery_capacity`, DROP COLUMN `panel_power`, DROP COLUMN `material`, DROP COLUMN `charging_time`, DROP COLUMN `lighting_time`, DROP COLUMN `waterproof_rating`, DROP COLUMN `led_chip`, DROP COLUMN `lighting_area`, DROP COLUMN `certificatevi`, DROP COLUMN `certificateen`;
