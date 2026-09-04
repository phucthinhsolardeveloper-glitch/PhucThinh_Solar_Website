-- Khối nội dung mô tả dùng chung / override.
-- Kế thừa khi hiển thị: sản phẩm > hãng > danh mục (product > brand > list).
-- Không backfill, không đụng contentvi legacy.
CREATE TABLE IF NOT EXISTS `table_content_section` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `scope` enum('product','brand','list') NOT NULL,
  `scope_id` int unsigned NOT NULL,
  `blocksvi` mediumtext NULL,
  `blocksen` mediumtext NULL,
  `status` varchar(64) NOT NULL DEFAULT 'hienthi',
  `date_updated` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_content_section_scope` (`scope`,`scope_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rollback: DROP TABLE `table_content_section`;
