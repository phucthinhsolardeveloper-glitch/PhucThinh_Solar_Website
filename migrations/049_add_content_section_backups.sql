-- Lưu bản nội dung ngay trước khi xoá hoặc ghi đè để Admin có thể phục hồi
-- bản gần nhất. Không backfill dữ liệu cũ; chỉ phát sinh từ thao tác mới.
CREATE TABLE IF NOT EXISTS `table_content_section_backup` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `content_section_id` int unsigned NOT NULL DEFAULT 0,
  `scope` varchar(16) NOT NULL,
  `scope_id` int unsigned NOT NULL,
  `blocksvi` mediumtext NULL,
  `blocksen` mediumtext NULL,
  `mode` varchar(16) NOT NULL DEFAULT 'own',
  `inherit_source` varchar(16) NOT NULL DEFAULT 'auto',
  `status` varchar(64) NOT NULL DEFAULT 'hienthi',
  `date_updated` int unsigned NOT NULL DEFAULT 0,
  `action` varchar(24) NOT NULL DEFAULT 'delete',
  `date_created` int unsigned NOT NULL DEFAULT 0,
  `created_by` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_content_section_backup_scope` (`scope`,`scope_id`,`id`),
  KEY `idx_content_section_backup_section` (`content_section_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rollback: DROP TABLE `table_content_section_backup`;
