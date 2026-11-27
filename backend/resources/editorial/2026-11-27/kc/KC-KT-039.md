ĐĂNG ĐƯỢC

### Đã kiểm chứng và sửa:
- Đã mở và kiểm tra 2 nguồn tham khảo từ Bộ Năng lượng Hoa Kỳ (DOE):
  + U.S. Department of Energy – Solar and Resilience Basics (HTTP 200).
  + U.S. Department of Energy – Solar Integration: Solar Energy and Storage Basics (HTTP 200).
- Các khẳng định kỹ thuật đều có đoạn trích trong tài liệu DOE làm căn cứ:
  + Hệ thống điện mặt trời hòa lưới thông thường phụ thuộc vào lưới điện và tự ngắt vì lý do an toàn khi mất lưới khớp tài liệu DOE (*Solar and Resilience Basics*).
  + Muốn tự phát điện khi mất lưới cần inverter được cấu hình phù hợp và hệ thống lưu trữ (*properly configured inverter and a storage system*).
  + Hệ thống tự động phát hiện mất điện lưới trong vài giây và chuyển sang chế độ vận hành độc lập (*islanded mode*) khớp tài liệu DOE.
  + Sự phân biệt giữa dung lượng lưu trữ (kWh) và công suất xả tức thời (kW) khớp tài liệu DOE (*Solar Energy and Storage Basics*).
  + Ưu thế cấp điện liên tục không cần tiếp tế nhiên liệu so với máy phát điện diesel khớp tài liệu DOE.
  + Phép tính giả định cho phụ tải ưu tiên (modem Wi-Fi 15 W chạy 24h = 360 Wh; tủ lạnh 50 W chạy 24h = 1,2 kWh; đèn + sạc = 340 Wh; tổng 1,9 kWh/ngày; pin 5 kWh khả dụng 4 kWh duy trì được 2 ngày) hoàn toàn chính xác về mặt số học và thực tế sử dụng.
- Đã sửa 6 câu dài hơn 28 từ thành các câu ngắn gọn, mạch lạc, hạ độ dài câu trung bình từ 20.4 từ xuống 16.7 từ/câu.
- Đã chạy `python3 tools/editorial-voice/doc-giong.py` đạt kết quả PASS (821 từ, 49 câu, trung bình 16.7 từ/câu, 0 lỗi).

### Băn khoăn còn lại:
- Không còn băn khoăn. Bài viết giải thích đúng nguyên lý kỹ thuật, giúp người dùng phân biệt rõ giữa dung lượng kWh và công suất tức thời kW khi chạy tủ lạnh bằng pin dự phòng.
