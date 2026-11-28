# Giao việc: sửa các bài đang đăng đọc rời rạc (28/11/2026)

Chủ website chê một số bài đọc rời rạc: nhiều đoạn một câu không ăn nhập, ý lặp lại, lạc đề (ví dụ bài 155: bảng ký hiệu điện áp trong bài so sánh công nghệ cell, sau đó là chuỗi câu dặn dò không nối nhau).

Đọc trước: `docs/07-features/editorial-voice-guide-20261118.md` (mục 4, 6, 10, 11, 12 là luật cứng).
Bài đang đăng: `backend/resources/editorial/2026-11-28-rewrite/input/<ID>.html` (33 bài). Bài 157 và 139 là mẫu tốt, không sửa.

## Việc
1. Đọc hết 33 bài. Chỉ chọn bài THỰC SỰ rời rạc, lặp hoặc lạc đề (tối đa 10 bài, bài 155 bắt buộc). Bài đoạn ngắn nhưng mạch rõ thì để nguyên.
2. Viết lại bài được chọn vào `out/<ID>.html`: mỗi đoạn một ý trọn vẹn, câu nối câu, bỏ câu lặp, bỏ phần lạc đề.

## Luật cứng
- KHÔNG thêm dữ kiện, con số, tên chuẩn, lời khuyên mới không có trong bài gốc. Chỉ sắp xếp, nối, gộp, cắt.
- Cắt phần nào có con số hoặc dữ kiện thật thì ghi rõ trong báo cáo. Không đổi nghĩa, không đổi phạm vi (số của nước nào, điều kiện nào).
- Giữ nguyên mọi liên kết `href`, mục "Đọc thêm", "Nguồn tham khảo", và mọi thẻ ảnh/iframe.
- Giữ dạng HTML như bài gốc (thẻ p, h2, h3, ul, table, a). Không thêm class, style. Không giữ dòng chú thích `<!-- -->` đầu tệp.
- Tự chấm từng bài: `python3 tools/editorial-voice/doc-giong.py <input> <out>` đến khi PASS.
- `bao-cao.md` (tiếng Việt): mỗi bài sửa gì, cắt gì và vì sao; bài còn lại vì sao không sửa.

## Phạm vi
Chỉ ghi trong `backend/resources/editorial/2026-11-28-rewrite/out/` và `bao-cao.md` cùng thư mục cha. Không sửa file khác, không git, không docker, không ssh, không truy cập website.
