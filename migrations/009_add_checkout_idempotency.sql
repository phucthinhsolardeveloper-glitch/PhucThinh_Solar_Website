-- Khóa idempotency bền vững cho checkout public.
-- Chỉ chạy sau migration 008 để unique insert và order detail cùng nằm trong transaction InnoDB.

ALTER TABLE `table_order`
    ADD COLUMN IF NOT EXISTS `checkout_token` CHAR(64) NULL AFTER `code`,
    ADD UNIQUE INDEX IF NOT EXISTS `ux_order_checkout_token` (`checkout_token`);

-- Giá trị NULL giữ tương thích cho đơn legacy. Token mới là 32 byte random dạng hex.
-- Rollback (mất khả năng nhận diện request checkout đã commit):
--   ALTER TABLE `table_order`
--     DROP INDEX `ux_order_checkout_token`,
--     DROP COLUMN `checkout_token`;
