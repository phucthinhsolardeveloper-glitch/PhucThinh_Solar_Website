-- ADM-P1-004: transaction thật cho đường ghi checkout.
-- Preflight bắt buộc trước khi chạy:
--   1. Backup database và xác minh checksum/restore trên local hoặc staging clone.
--   2. Orphan table_order_detail -> table_order và -> table_product đều bằng 0.
--   3. Dừng checkout trong thời gian ALTER trên production vì ALTER có thể khóa bảng.

ALTER TABLE `table_order` ENGINE=InnoDB;

ALTER TABLE `table_order_detail`
    ENGINE=InnoDB,
    ADD INDEX IF NOT EXISTS `idx_order_detail_order` (`id_order`),
    ADD INDEX IF NOT EXISTS `idx_order_detail_product` (`id_product`);

-- Không thêm foreign key trong migration này: hành vi xóa/cascade của code legacy
-- chưa có contract nghiệp vụ đủ rõ để enforce ở tầng database.
-- Rollback engine (mất transaction thật):
--   ALTER TABLE `table_order_detail` ENGINE=MyISAM;
--   ALTER TABLE `table_order` ENGINE=MyISAM;
-- Hai index có thể giữ lại; nếu cần hoàn nguyên đúng schema cũ:
--   ALTER TABLE `table_order_detail`
--     DROP INDEX `idx_order_detail_order`,
--     DROP INDEX `idx_order_detail_product`;
