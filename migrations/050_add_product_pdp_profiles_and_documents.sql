-- Profile dữ liệu cho layout chi tiết sản phẩm dùng chung.
-- NULL/rỗng = kế thừa từ danh mục; danh mục rỗng = suy ra từ slug legacy.
ALTER TABLE `table_product_list`
  ADD COLUMN IF NOT EXISTS `pdp_profile` varchar(32) NULL DEFAULT NULL AFTER `type`;

ALTER TABLE `table_product`
  ADD COLUMN IF NOT EXISTS `pdp_profile` varchar(32) NULL DEFAULT NULL AFTER `type`;

-- Tài liệu chỉ render khi có bản ghi đang hiển thị và đường dẫn file hợp lệ.
CREATE TABLE IF NOT EXISTS `table_product_document` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_product` int unsigned NOT NULL,
  `document_type` varchar(32) NOT NULL DEFAULT 'datasheet',
  `titlevi` varchar(255) NOT NULL,
  `titleen` varchar(255) NULL,
  `file_path` varchar(500) NOT NULL,
  `numb` int NOT NULL DEFAULT 0,
  `status` varchar(64) NOT NULL DEFAULT 'hienthi',
  `date_created` int unsigned NOT NULL DEFAULT 0,
  `date_updated` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_product_document_product` (`id_product`,`status`,`numb`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ngữ cảnh lead giúp Admin biết khách đang hỏi sản phẩm/nhu cầu nào.
-- Đơn/lead mới dùng đúng mô hình hai cấp Tỉnh/TP -> Phường/Xã.
ALTER TABLE `table_newsletter`
  ADD COLUMN IF NOT EXISTS `product_id` int unsigned NOT NULL DEFAULT 0 AFTER `type`,
  ADD COLUMN IF NOT EXISTS `profile_key` varchar(32) NULL DEFAULT NULL AFTER `product_id`,
  ADD COLUMN IF NOT EXISTS `need_code` varchar(64) NULL DEFAULT NULL AFTER `profile_key`,
  ADD COLUMN IF NOT EXISTS `details` text NULL AFTER `need_code`,
  ADD COLUMN IF NOT EXISTS `city_id` int unsigned NOT NULL DEFAULT 0 AFTER `details`,
  ADD COLUMN IF NOT EXISTS `ward_id` int unsigned NOT NULL DEFAULT 0 AFTER `city_id`;

-- Rollback (chỉ thực hiện khi đã sao lưu dữ liệu mới):
-- DROP TABLE `table_product_document`;
-- ALTER TABLE `table_product_list` DROP COLUMN `pdp_profile`;
-- ALTER TABLE `table_product` DROP COLUMN `pdp_profile`;
-- ALTER TABLE `table_newsletter` DROP COLUMN `product_id`, DROP COLUMN `profile_key`,
--   DROP COLUMN `need_code`, DROP COLUMN `details`, DROP COLUMN `city_id`, DROP COLUMN `ward_id`;
