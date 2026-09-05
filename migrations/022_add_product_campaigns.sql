-- Thư viện khung chiến dịch và phạm vi áp dụng cho card sản phẩm.
-- Migration chỉ thêm bảng; không sửa/backfill dữ liệu sản phẩm hoặc ảnh cũ.

CREATE TABLE IF NOT EXISTS `table_product_campaign` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `photo` VARCHAR(255) NOT NULL,
    `priority` INT NOT NULL DEFAULT 0,
    `start_at` INT UNSIGNED NOT NULL DEFAULT 0,
    `end_at` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` VARCHAR(64) NOT NULL DEFAULT '',
    `date_created` INT UNSIGNED NOT NULL DEFAULT 0,
    `date_updated` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_product_campaign_active` (`status`, `start_at`, `end_at`, `priority`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `table_product_campaign_scope` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_campaign` INT UNSIGNED NOT NULL,
    `scope_type` ENUM('all','list','cat','brand','product') NOT NULL,
    `scope_id` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_product_campaign_scope` (`id_campaign`, `scope_type`, `scope_id`),
    KEY `idx_product_campaign_scope_target` (`scope_type`, `scope_id`, `id_campaign`),
    CONSTRAINT `fk_product_campaign_scope_campaign`
        FOREIGN KEY (`id_campaign`) REFERENCES `table_product_campaign` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rollback an toàn của code: rollback commit, giữ hai bảng và dữ liệu chiến dịch.
-- Chỉ DROP hai bảng khi đã xác nhận không có dữ liệu cần giữ:
-- DROP TABLE `table_product_campaign_scope`;
-- DROP TABLE `table_product_campaign`;
