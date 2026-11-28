ĐĂNG ĐƯỢC

## Đã kiểm chứng và sửa
1. **Kiểm tra nguồn tham khảo**:
   - Đã tải và đọc toàn bộ 12 trang PDF `IRENA_TEC_RPGC_2025_Executive_summary_2026.pdf` tại `/tmp/irena_exec_summary.pdf`. Toàn bộ số liệu trong bài và trong 2 bảng (LCOE, TIC, hệ số công suất của 7 công nghệ; LCOE theo 5 quốc gia Ấn Độ, Trung Quốc, Brazil, Hoa Kỳ, Đức; tỷ lệ 56% và 24% = 2,3 lần; pin lưu trữ 4 giờ 140 USD/kWh, dưới 70 USD/kWh tại Trung Quốc, firm LCOE 95% dưới 85 USD/MWh; 690 GW, 500 GW solar, 160 GW gió; điện khí Mỹ 2.400 USD/kW, Đức/Ý/Nhật sát 100 USD/MWh; giảm 89% từ 412 USD/MWh năm 2010 xuống 44 USD/MWh năm 2025; TIC giảm 88% giai đoạn 2010–2025 và dự báo giảm 40% giai đoạn 2026–2035; Trung Quốc bỏ hoàn thuế VAT xuất khẩu tháng 4/2026) đều khớp chính xác với báo cáo gốc.
   - Đã xóa liên kết thứ hai (`https://www.irena.org/Publications/2026/Jul/Renewable-Power-Generation-Costs-in-2025`) khỏi mục Nguồn tham khảo vì trang HTML này bị chặn bởi cổng thử thách JavaScript của Azure WAF, và toàn bộ số liệu đều đã nằm trong file PDF Executive Summary mở được trực tiếp.
2. **Sửa chi tiết không có chữ đỡ trong PDF**:
   - Trong file PDF không in chữ "July 2026" (chỉ ghi `© IRENA 2026`, `ISBN: 978-92-9260-749-4`, còn `Jul` nằm ở đường dẫn URL). Đã bỏ cụm `(tháng 7/2026)` ở câu mở đầu và đổi đề mục `## Báo cáo tháng 7/2026 ghi nhận những con số nào?` thành `## Báo cáo của IRENA ghi nhận những con số nào?`, đồng thời cập nhật `PTS-KT-033.meta.json`.
3. **Sửa lỗi đường dẫn nội bộ**:
   - Đã sửa lỗi chính tả slug liên kết nội bộ từ `/kw-kwp-va-kwh-khau-nhau-the-nao-7-vi-du-tranh-nham/` thành `/kw-kwp-va-kwh-khac-nhau-the-nao-7-vi-du-tranh-nham/` khớp với `input/live-posts.txt` (bài 139).
4. **Kiểm tra `doc-giong.py`**:
   - Chạy `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/h/PTS-KT-033.md backend/resources/editorial/2026-11-27/h/PTS-KT-033.md` đạt **PASS** (957 từ, 60 câu, trung bình 16.0 từ/câu).

## Điểm còn băn khoăn
- Không còn băn khoăn về số liệu hay nguồn dẫn.
