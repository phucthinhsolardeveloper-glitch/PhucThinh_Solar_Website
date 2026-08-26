-- Thêm cột công suất (W) cho sản phẩm, phục vụ bộ lọc theo công suất
-- trên danh mục "Đèn năng lượng mặt trời" (checklist ads mục 2).
-- Chạy 1 lần trên DB production trước khi chạy
-- migrations/002_backfill_product_cong_suat.php

ALTER TABLE `table_product`
    ADD COLUMN `cong_suat` DECIMAL(6,1) UNSIGNED NULL DEFAULT NULL
    COMMENT 'Công suất (W), tự dò từ tên SP + admin có thể sửa tay'
    AFTER `discount`;
