-- Khối nội dung chuẩn nullable; không backfill hay thay đổi HTML legacy.
ALTER TABLE `table_product`
    ADD COLUMN IF NOT EXISTS `highlightsvi` MEDIUMTEXT NULL AFTER `descvi`,
    ADD COLUMN IF NOT EXISTS `highlightsen` MEDIUMTEXT NULL AFTER `highlightsvi`,
    ADD COLUMN IF NOT EXISTS `applicationsvi` MEDIUMTEXT NULL AFTER `highlightsen`,
    ADD COLUMN IF NOT EXISTS `applicationsen` MEDIUMTEXT NULL AFTER `applicationsvi`,
    ADD COLUMN IF NOT EXISTS `installationvi` MEDIUMTEXT NULL AFTER `applicationsen`,
    ADD COLUMN IF NOT EXISTS `installationen` MEDIUMTEXT NULL AFTER `installationvi`;

-- Rollback chỉ khi các cột chưa có dữ liệu cần giữ:
-- ALTER TABLE `table_product` DROP COLUMN `highlightsvi`, DROP COLUMN `highlightsen`,
-- DROP COLUMN `applicationsvi`, DROP COLUMN `applicationsen`, DROP COLUMN `installationvi`, DROP COLUMN `installationen`;
