CREATE TABLE IF NOT EXISTS `table_url_redirect` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `old_path` varchar(512) NOT NULL,
  `entity_table` varchar(32) NOT NULL,
  `entity_id` int unsigned NOT NULL,
  `date_created` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_url_redirect_old_path` (`old_path`),
  KEY `idx_url_redirect_entity` (`entity_table`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rollback: DROP TABLE `table_url_redirect`;
