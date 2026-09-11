-- Anh rieng cho quang cao Google Shopping / Facebook Catalog: nen sach,
-- khong logo, khong chu, khong khung/huy hieu (yeu cau ben quang cao
-- 2026-09-11). De trong -> khi xuat feed dung anh chinh (`photo`) thay the.
ALTER TABLE `table_product`
    ADD COLUMN IF NOT EXISTS `photo_feed` VARCHAR(255) NULL DEFAULT NULL AFTER `photo_khuyenmai`;

-- Rollback: ALTER TABLE `table_product` DROP COLUMN `photo_feed`;
