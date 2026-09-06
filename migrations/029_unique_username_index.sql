-- Ép username của tài khoản là duy nhất ở tầng database.
--
-- Đây là lớp phòng thủ thứ hai cho lỗ hổng đã vá ở code
-- (administrator/sources/user.php, màn hình tự sửa hồ sơ): trước đây nhân viên
-- gửi id của người khác để lách kiểm tra trùng tên, tạo được hai tài khoản
-- cùng username. Toàn bộ hệ thống định danh theo username, nên trùng username
-- là hỏng đăng nhập và ghi đè nhầm dữ liệu. Có UNIQUE index thì dù code có sơ
-- hở, database cũng từ chối.
--
-- QUAN TRỌNG - kiểm tra trùng TRƯỚC khi chạy lệnh ALTER. Nếu production đã lỡ
-- có username trùng (do lỗ hổng bị khai thác từ trước), lệnh ALTER sẽ báo lỗi
-- "Duplicate entry" và không chạy. Chạy câu SELECT này trước:
--
--   SELECT username, COUNT(*) c FROM table_user
--   GROUP BY username HAVING c > 1;
--
-- Nếu có kết quả: đổi tên hoặc xoá các tài khoản trùng cho tới khi câu trên
-- không trả dòng nào, rồi mới chạy ALTER bên dưới.
--
-- Rollback: ALTER TABLE `table_user` DROP INDEX `uniq_user_username`;

ALTER TABLE `table_user`
    ADD UNIQUE INDEX `uniq_user_username` (`username`);
