# Giao việc: viết bài kiến thức PTS Solar (27/11/2026)

Bạn viết bài cho website phucthinhsolar.com (công ty phân phối đèn và thiết bị năng lượng mặt trời tại Việt Nam).
Chủ website rất khó tính về giọng văn: từng chê bài là "thô, đọc phát bực" và "giống viết cho bọn ngu đọc". Bài kém sẽ bị trả lại.

## Đọc trước (bắt buộc, đọc hết)
1. `docs/07-features/editorial-voice-guide-20261118.md`: sổ tay giọng văn. Mục 4, 6, 10, 11, 12 là luật cứng.
2. `backend/resources/editorial/2026-11-27/input/GOLD-139.md` và `input/ARTICLE-146.md`: hai bài chủ website đã duyệt. Viết đúng giọng này.
3. `input/plan-rows.csv`: góc viết (cột angle) và nguồn chính/phụ của từng bài.

## Phạm vi của bạn
- Chỉ ghi file vào thư mục được giao: `backend/resources/editorial/2026-11-27/<thư mục của bạn>/`.
- KHÔNG sửa file nào khác, không git commit, không chạy docker, không ssh, không gọi máy chủ production, không đăng bài.

## Sự thật (vi phạm là làm lại cả bài)
- Mỗi con số, ngày, tên tiêu chuẩn và kết quả thử nghiệm phải lấy từ một nguồn bạn đã THỰC SỰ MỞ ĐỌC trong phiên này. Không nhớ, không ước.
- Nguồn nào không mở được: không dùng số từ nguồn đó. Ghi vào báo cáo.
- Không bịa trải nghiệm công ty ("chúng tôi đã lắp", "khách hàng của chúng tôi"), không bịa sản phẩm, giá hay bảo hành của Phúc Thịnh Solar.
- Ví dụ giả định thì ghi "giả sử..." và dùng phép tính đơn giản, đúng.
- Không chép nguyên văn nguồn tiếng Anh.

## Đầu ra cho mỗi bài
- `PTS-KT-0NN.md`: Markdown thuần, KHÔNG có H1, đoạn đầu trả lời luôn câu hỏi tiêu đề, đề mục `##` (3–5 đề mục), bảng khi thật sự so sánh. Cuối bài mục `## Nguồn tham khảo` gồm các liên kết `https://` bạn đã đọc.
- Độ dài 500–900 từ. Bài ngắn mà chắc tốt hơn bài dài mà loãng.
- `PTS-KT-0NN.meta.json`: `{"summary": "1–2 câu", "seo_description": "120–155 ký tự"}`.
- Tự chấm: `python3 tools/editorial-voice/doc-giong.py <bản gốc hoặc chính file mới> <file mới>`, sửa đến khi PASS. Rồi đọc lại cả bài như đang nói với một người bạn; câu nào thừa thì xóa.
- `bao-cao.md` (tiếng Việt): mỗi bài ghi nguồn đã mở được / không mở được, số liệu dùng và lấy từ đâu, điểm còn băn khoăn, kết quả doc-giong.

Không có ghi chú biên tập, "bài gốc", "bản nháp" hay lời thanh minh nào trong thân bài. Những thứ đó chỉ nằm trong bao-cao.md.

## Bài học lô 1 (bắt buộc tránh)
- Không ví von "bồn nước / vòi nước / ống nước". Chủ website đã chê kiểu này.
- Chỉ gán số cho nguồn khi chính trang đó có con số ấy. Lô 1 đã ghi "theo Clean Energy Reviews..." cho số mà trang không có: bị gỡ.
- Không đưa chi tiết dành cho kỹ sư vận hành (chỉ số giám sát, mã tiêu chuẩn phụ) nếu không giúp chủ nhà quyết định.
- Trong bảng, ô không kết thúc bằng dấu chấm.
- Bài tin về luật, nghị định: phải mở được văn bản gốc (thuvienphapluat, chinhphu.vn, moit.gov.vn...). Không mở được thì không viết, ghi lý do.
- Có bài cũ trên website cùng chủ đề (xem danh sách trong input/live-posts.txt): không lặp nội dung, chỉ ghi [[ID]] chỗ nên liên kết sang.
