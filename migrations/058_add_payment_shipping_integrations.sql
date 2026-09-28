-- Lõi đối soát thanh toán và vận chuyển đa nhà cung cấp.
-- Chỉ chạy sau backup + preflight trên staging/production.

ALTER TABLE `table_order`
    ADD COLUMN IF NOT EXISTS `payment_method_code` VARCHAR(40) NULL AFTER `order_payment`,
    ADD COLUMN IF NOT EXISTS `payment_transaction_ref` VARCHAR(120) NULL AFTER `payment_method_code`,
    ADD COLUMN IF NOT EXISTS `carrier_code` VARCHAR(40) NULL AFTER `payment_transaction_ref`,
    ADD COLUMN IF NOT EXISTS `carrier_order_code` VARCHAR(120) NULL AFTER `carrier_code`,
    ADD COLUMN IF NOT EXISTS `carrier_status` VARCHAR(40) NULL AFTER `carrier_order_code`;

CREATE TABLE IF NOT EXISTS `table_payment_transaction` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_order` INT UNSIGNED NOT NULL,
    `provider` VARCHAR(40) NOT NULL,
    `transaction_ref` VARCHAR(120) NOT NULL,
    `provider_transaction_ref` VARCHAR(160) NULL,
    `amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `payload_hash` CHAR(64) NULL,
    `payload` MEDIUMTEXT NULL,
    `created_at` INT UNSIGNED NOT NULL DEFAULT 0,
    `updated_at` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `ux_payment_provider_ref` (`provider`, `transaction_ref`),
    KEY `idx_payment_order` (`id_order`),
    KEY `idx_payment_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `table_shipping_shipment` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_order` INT UNSIGNED NOT NULL,
    `provider` VARCHAR(40) NOT NULL,
    `quote_ref` VARCHAR(120) NULL,
    `order_code` VARCHAR(120) NULL,
    `status` VARCHAR(40) NOT NULL DEFAULT 'draft',
    `fee` DECIMAL(18,2) NOT NULL DEFAULT 0,
    `cod_amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
    `payload` MEDIUMTEXT NULL,
    `created_at` INT UNSIGNED NOT NULL DEFAULT 0,
    `updated_at` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `ux_shipping_provider_order` (`provider`, `order_code`),
    KEY `idx_shipping_order` (`id_order`),
    KEY `idx_shipping_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rollback: chỉ thực hiện sau khi xác nhận không còn dữ liệu tích hợp cần giữ.
-- ALTER TABLE `table_order` DROP COLUMN `payment_method_code`, ...;
-- DROP TABLE `table_payment_transaction`, `table_shipping_shipment`;
