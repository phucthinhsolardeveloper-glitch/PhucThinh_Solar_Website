-- Gán ảnh đại diện đã được chủ project làm sạch vào trường ảnh Shopping.
-- Không sao chép file: photo_feed dùng cùng filename với photo; bộ feed vẫn
-- kiểm tra file tồn tại và kích thước tối thiểu 500x500 trước khi xuất.
-- Chỉ áp dụng cho sản phẩm đang hiển thị và có ảnh đại diện.
UPDATE `table_product`
SET `photo_feed` = `photo`
WHERE `type` = 'san-pham'
  AND FIND_IN_SET('hienthi', `status`)
  AND `photo` IS NOT NULL
  AND `photo` <> '';

-- Rollback (chỉ chạy nếu cần hoàn tác quyết định dùng ảnh đại diện sạch):
-- UPDATE `table_product` SET `photo_feed` = NULL WHERE `type` = 'san-pham';
