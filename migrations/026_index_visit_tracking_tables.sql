-- Thêm index cho hai bảng theo dõi lượt truy cập.
--
-- getCounter() và getOnline() trong libraries/class/class.Statistic.php chạy
-- trên MỌI lượt xem trang công khai, nhưng cả hai bảng đều gần như không có
-- index:
--
--   table_counter      chỉ có PRIMARY(id). Truy vấn chống đếm trùng
--                      "where ip = ? and (tm + 900) > now" phải quét toàn bộ
--                      bảng — EXPLAIN cho type=ALL, rows=87000 — mỗi lượt xem
--                      trang. Bốn truy vấn thống kê theo ngày/tuần/tháng cũng
--                      lọc theo tm mà không có index.
--
--   table_user_online  không có index nào, kể cả khoá chính, dù mỗi lượt xem
--                      trang đều select/insert/update theo session.
--
-- Chỉ thêm index, không đổi cột, không đụng dữ liệu. Bảng đang là MyISAM nên
-- ALTER sẽ khoá bảng trong lúc dựng index; với cỡ hiện tại (87k và 113 dòng)
-- thao tác tính bằng giây, nhưng vẫn nên chạy lúc vắng khách.
--
-- Rollback:
--   ALTER TABLE table_counter DROP INDEX idx_counter_ip_tm, DROP INDEX idx_counter_tm;
--   ALTER TABLE table_user_online DROP INDEX idx_user_online_session, DROP INDEX idx_user_online_time;

-- Chống đếm trùng theo IP trong 15 phút: tra thẳng bằng index thay vì quét bảng.
ALTER TABLE `table_counter`
    ADD INDEX `idx_counter_ip_tm` (`ip`, `tm`),
    ADD INDEX `idx_counter_tm` (`tm`);

-- Tra phiên đang online theo session, và dọn phiên hết hạn theo time.
-- Dùng index thường chứ không phải UNIQUE: code hiện tại tự xử lý trùng session,
-- đặt UNIQUE có thể làm insert đang chạy bị lỗi nếu dữ liệu thật có trùng.
ALTER TABLE `table_user_online`
    ADD INDEX `idx_user_online_session` (`session`),
    ADD INDEX `idx_user_online_time` (`time`);
