ĐĂNG ĐƯỢC

## Đã kiểm chứng và sửa
1. **Kiểm tra nguồn tham khảo**:
   - Đã tải và đọc toàn bộ văn bản gốc Nghị định 243/2026/NĐ-CP (`/tmp/nd243.pdf`, 76 trang), Nghị định 58/2025/NĐ-CP (`/tmp/nd58.pdf`, 50 trang) và bài tin Bộ Công Thương (`moit.gov.vn`).
   - Liên kết thứ tư (`https://phapluat.gov.vn/tin-tuc/dien-mat-troi-mai-nha-nang-ty-le-san-luong-dien-du-duoc-phep-ban-len-toi-50`) trả về lỗi `403 Forbidden` (chặn IP). Đã xóa liên kết này khỏi mục Nguồn tham khảo theo đúng quy tắc (mọi thông tin về tỷ lệ 50% đã được chứng minh trực tiếp từ văn bản gốc Nghị định 243/2026/NĐ-CP).
2. **Sửa các chi tiết dẫn chiếu điều khoản và diễn đạt cho khớp nguồn**:
   - Câu *"công suất lắp đặt không được vượt mức cực đại ($P_{max}$) trên nhãn công tơ hiện hữu (khoản 5 Điều 2)"*: trên nhãn công tơ chỉ ghi $U_{đdmax}$ và $I_{đdmax}$, còn $P_{max}$ được tính theo công thức tại khoản 3 Điều 11 (được sửa đổi bởi khoản 5 Điều 2 Nghị định 243/2026/NĐ-CP). Đã sửa thành *"công suất lắp đặt không được vượt mức cực đại ($P_{max}$) tính theo thông số trên nhãn công tơ hiện hữu (khoản 3 Điều 11)"*.
   - Câu *"Quy định cũng cấm nhập khẩu tấm quang điện và bộ inverter đã qua sử dụng"*: chỉ rõ nguồn tại Mục II Cam kết của Mẫu số 01, 02, 03 Phụ lục III Nghị định 243/2026/NĐ-CP (*"Theo cam kết tại Mẫu số 01 và Mẫu số 02 Phụ lục III, bạn không được nhập khẩu tấm quang điện và bộ inverter đã qua sử dụng"*).
   - Câu *"Ngày 18/9/2026, Bộ Công Thương ban hành Công văn 7404/BCT-DL..."*: trên trang `moit.gov.vn`, ngày 18/09/2026 là ngày đăng bản tin (*"Bộ Công Thương vừa ban hành Công văn số 7404/BCT-DL..."*). Đã sửa thành *"Tháng 9/2026, Bộ Công Thương ban hành Công văn 7404/BCT-DL..."* để không gán ngày đăng bài báo thành ngày ký công văn.
   - Câu *"Chính sách này khuyến khích lắp đặt hệ thống gắn với pin lưu trữ theo khoản 2 Điều 22 Luật Điện lực năm 2024"*: trong bài tin Bộ Công Thương, khoản 2 Điều 22 Luật Điện lực năm 2024 là thẩm quyền ban hành chính sách hỗ trợ hộ gia đình lắp điện mặt trời mái nhà tự sản xuất, tự tiêu thụ, còn việc tuyên truyền hỗ trợ lắp kèm hệ thống lưu trữ điện (BESS) được nêu theo Nghị định 243/2026/NĐ-CP. Đã tách rõ hai ý cho trung thực với nguồn.
3. **Kiểm tra `doc-giong.py`**:
   - Chạy `python3 tools/editorial-voice/doc-giong.py backend/resources/editorial/2026-11-27/h/PTS-KT-043.md backend/resources/editorial/2026-11-27/h/PTS-KT-043.md` đạt **PASS**.

## Điểm còn băn khoăn
- Không còn băn khoăn về tính xác thực của văn bản pháp luật và số liệu.
