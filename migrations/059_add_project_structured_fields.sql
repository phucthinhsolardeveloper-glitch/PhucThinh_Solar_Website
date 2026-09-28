-- Dữ liệu có cấu trúc cho Dự án. Giữ các cột address/ngayhopdong cũ để
-- tương thích dữ liệu và frontend legacy; không đổi URL/slug public.

ALTER TABLE `table_news`
    ADD COLUMN IF NOT EXISTS `project_city_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `address`,
    ADD COLUMN IF NOT EXISTS `project_ward_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `project_city_id`,
    ADD COLUMN IF NOT EXISTS `project_address_detail` VARCHAR(255) NULL AFTER `project_ward_id`,
    ADD COLUMN IF NOT EXISTS `project_start_date` DATE NULL AFTER `project_address_detail`,
    ADD COLUMN IF NOT EXISTS `project_end_date` DATE NULL AFTER `project_start_date`,
    ADD INDEX IF NOT EXISTS `idx_news_project_city` (`project_city_id`),
    ADD INDEX IF NOT EXISTS `idx_news_project_ward` (`project_ward_id`);

-- Rollback có chủ đích, chỉ chạy sau backup và xác nhận không cần dữ liệu mới:
-- ALTER TABLE `table_news`
--   DROP INDEX `idx_news_project_city`, DROP INDEX `idx_news_project_ward`,
--   DROP COLUMN `project_city_id`, DROP COLUMN `project_ward_id`,
--   DROP COLUMN `project_address_detail`, DROP COLUMN `project_start_date`,
--   DROP COLUMN `project_end_date`;
