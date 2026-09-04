-- Quan hệ nhiều-nhiều giữa sản phẩm và Dự án.
-- Không sao chép tên, slug hoặc ảnh; public luôn đọc dữ liệu hiện hành từ bảng gốc.

CREATE TABLE IF NOT EXISTS `table_product_project` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_product` INT UNSIGNED NOT NULL,
    `id_project` INT UNSIGNED NOT NULL,
    `numb` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` VARCHAR(50) NOT NULL DEFAULT 'hienthi',
    PRIMARY KEY (`id`),
    UNIQUE KEY `ux_product_project_pair` (`id_product`, `id_project`),
    KEY `idx_product_project_project` (`id_project`, `numb`, `id`),
    KEY `idx_product_project_product` (`id_product`, `numb`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rollback chỉ thực hiện khi đã xác nhận bảng chưa có dữ liệu cần giữ:
-- DROP TABLE `table_product_project`;
