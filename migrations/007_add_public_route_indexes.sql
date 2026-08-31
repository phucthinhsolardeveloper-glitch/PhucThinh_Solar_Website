-- Index cho route theo slug và danh sách public theo loại/thứ tự.

ALTER TABLE `table_product`
    ADD INDEX IF NOT EXISTS `idx_route_type_slug` (`type`, `slugvi`(191)),
    ADD INDEX IF NOT EXISTS `idx_type_numb_id` (`type`, `numb`, `id`);
ALTER TABLE `table_product_list`
    ADD INDEX IF NOT EXISTS `idx_route_type_slug` (`type`, `slugvi`(191)),
    ADD INDEX IF NOT EXISTS `idx_type_numb_id` (`type`, `numb`, `id`);
ALTER TABLE `table_product_cat`
    ADD INDEX IF NOT EXISTS `idx_route_type_slug` (`type`, `slugvi`(191)),
    ADD INDEX IF NOT EXISTS `idx_type_numb_id` (`type`, `numb`, `id`);
ALTER TABLE `table_product_item`
    ADD INDEX IF NOT EXISTS `idx_route_type_slug` (`type`, `slugvi`(191)),
    ADD INDEX IF NOT EXISTS `idx_type_numb_id` (`type`, `numb`, `id`);
ALTER TABLE `table_product_sub`
    ADD INDEX IF NOT EXISTS `idx_route_type_slug` (`type`, `slugvi`(191)),
    ADD INDEX IF NOT EXISTS `idx_type_numb_id` (`type`, `numb`, `id`);
ALTER TABLE `table_product_brand`
    ADD INDEX IF NOT EXISTS `idx_route_type_slug` (`type`, `slugvi`(191)),
    ADD INDEX IF NOT EXISTS `idx_type_numb_id` (`type`, `numb`, `id`);

ALTER TABLE `table_news`
    ADD INDEX IF NOT EXISTS `idx_route_type_slug` (`type`, `slugvi`(191)),
    ADD INDEX IF NOT EXISTS `idx_type_numb_id` (`type`, `numb`, `id`);
ALTER TABLE `table_news_list`
    ADD INDEX IF NOT EXISTS `idx_route_type_slug` (`type`, `slugvi`(191)),
    ADD INDEX IF NOT EXISTS `idx_type_numb_id` (`type`, `numb`, `id`);
ALTER TABLE `table_news_cat`
    ADD INDEX IF NOT EXISTS `idx_route_type_slug` (`type`, `slugvi`(191)),
    ADD INDEX IF NOT EXISTS `idx_type_numb_id` (`type`, `numb`, `id`);
ALTER TABLE `table_news_item`
    ADD INDEX IF NOT EXISTS `idx_route_type_slug` (`type`, `slugvi`(191)),
    ADD INDEX IF NOT EXISTS `idx_type_numb_id` (`type`, `numb`, `id`);
ALTER TABLE `table_news_sub`
    ADD INDEX IF NOT EXISTS `idx_route_type_slug` (`type`, `slugvi`(191)),
    ADD INDEX IF NOT EXISTS `idx_type_numb_id` (`type`, `numb`, `id`);

-- Rollback: DROP INDEX idx_route_type_slug, DROP INDEX idx_type_numb_id trên từng bảng.
