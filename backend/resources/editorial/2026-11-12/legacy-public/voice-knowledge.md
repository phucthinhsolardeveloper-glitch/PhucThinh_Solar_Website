# QA giọng văn và trùng lặp — bài Kiến thức 102, 104, 105, 106, 107, 110, 111

## Phạm vi và nguyên tắc

Đây là draft ẩn, không phải lệnh đồng bộ DB. Title đề xuất chỉ là phương án review; slug, URL cũ, ngày đăng, visibility và ảnh hiện hành chưa bị thay đổi. Văn bản ưu tiên câu trả lời trước, chia đoạn ngắn, không bịa số liệu/công trình/khách hàng và giữ cảnh báo an toàn ở đúng mức.

## Bản đồ ý định sau biên tập

| ID | Người đọc cần làm được gì | Ranh giới với bài khác |
|---:|---|---|
| 102 | Đánh giá một vị trí có hợp đèn mặt trời không | Guide nền tảng, không phải báo giá |
| 107 | Lập phương án cho nơi khó kéo điện | Ứng dụng địa hình, không lặp danh sách lợi ích |
| 104 | Hỏi đúng bốn câu trước khi mua | Checklist quyết định theo vị trí |
| 111 | So tổng chi phí vận hành | Không hứa tiết kiệm/hoàn vốn |
| 105 | Kiểm tra và vận hành an toàn | Không dạy đấu dây hay thi công trên cao |
| 106 | Kiểm tra hồ sơ đèn dự án | Không biến claim thương hiệu thành bằng chứng |
| 110 | Hiểu vai trò chip LED và bộ đèn | Không dùng “TOP 1” hay suy luận từ tên chip |

## Bằng chứng trùng lặp và cách xử lý

So sánh trực tiếp HTML/text DB đã xác nhận **102 và 107 giống 100%**: SequenceMatcher **1.000**, tập từ **1.000**; chỉ khác title “Những lý do nên chọn” và “Những lý do bạn nên chọn”. 104/111 cũng chồng lấn các ý tiết kiệm, môi trường, lắp đặt và tự động.

- **102 ↔ 107 — trùng tuyệt đối trong HTML/text DB.** Không viết hai bài bằng cách thay từ đồng nghĩa và không xuất bản bản 107 application hiện tại. Đối chiếu organic clicks, impressions, backlink chất lượng và chuyển đổi trước; URL tốt hơn làm canonical, URL còn lại 301 về đó. Không tự unpublish/redirect khi chưa có analytics. Nếu không có chênh lệch đáng kể, 102 là ứng viên hợp lý vì slug ngắn hơn.
- **104 ↔ 111 — trùng cao ở tiết kiệm điện, môi trường, lắp đặt và tự động.** 104 nay là checklist mua theo vị trí; 111 là total-cost-of-ownership với giả định cần thu thập. Link chéo chỉ khi có component liên quan, không copy đoạn.
- **105 ↔ 102/104 — trùng một phần về vị trí nắng và vận hành.** 105 giữ vai trò thao tác an toàn/vệ sinh; bỏ claim thời lượng chung và cấm DIY wiring.
- **106 ↔ 102/104/111 — trùng phần lợi ích chung.** 106 chuyển thành hồ sơ dự án, nghiệm thu và xác minh nguồn; không dùng slogan thương hiệu.
- **110 ↔ 106 — trùng Blue Carbon/CREE.** 110 chỉ giải thích cách đọc hiệu năng chip/bộ đèn; 106 chỉ kiểm tra hồ sơ dự án. Hai bài đều chặn claim chưa xác minh.

## Claim: đã kiểm chứng và chưa kiểm chứng

**Có thể giữ ở mức nguyên lý:** đèn dùng tấm pin, pin lưu trữ, bộ điều khiển và LED; bóng che/nắng, chế độ điều khiển và vệ sinh ảnh hưởng vận hành; không nối điện lưới không đồng nghĩa miễn kiểm tra cơ khí/an toàn.

**Chưa kiểm chứng, đã hạ khỏi bản draft:** tuổi thọ 12/25 năm, pin LiFePO4 8–12 năm, IP, sáng 8 giờ, nạp 6–8 giờ, bảo hành 5 năm, “hơn 100 quốc gia”, “nhiều dự án”, CREE TOP 1, CREE trên mọi đèn Blue Carbon, độc quyền Phúc Thịnh Solar, chứng chỉ/tiêu chuẩn và mọi con số tiết kiệm. Các mục này cần datasheet, chứng chỉ, hợp đồng/ủy quyền hoặc hồ sơ model trước khi dùng.

**Không downgrade warning:** bài 105 vẫn yêu cầu kỹ thuật viên cho làm việc trên cao, dây hỏng, chống sét và đấu nối; bài 106 vẫn yêu cầu hồ sơ nghiệm thu; bài 110 vẫn yêu cầu kiểm tra bộ nguồn/tản nhiệt thay vì tin tên chip.

## Before / after giọng văn

**102 — trước:** “Có phải bạn đang cố gắng để giảm hóa đơn… đèn… không tốn tiền điện.”  
**Sau:** “Đèn năng lượng mặt trời phù hợp khi vị trí cần sáng ở ngoài trời, khó kéo dây hoặc muốn tách khỏi mạch điện lưới… chọn đúng bắt đầu từ vị trí, thời lượng và điều kiện nắng.”  
Thay lời hứa chung bằng điều kiện ra quyết định.

**105 — trước:** “nên lắp đặt… ánh sáng trực tiếp từ 6 đến 8 giờ mỗi ngày” và “hoạt động kéo dài ít nhất 8 giờ”.  
**Sau:** “Thời gian sáng phụ thuộc dung lượng pin, chế độ điều khiển, nhiệt độ, mây và mức tiêu thụ; không dùng mốc… nếu datasheet không ghi rõ điều kiện.”  
Giữ cảnh báo, bỏ số chưa gắn model.

**110 — trước:** “CHIP LED CREE THƯƠNG HIỆU TOP 1!!”  
**Sau:** “Tên chip là một tín hiệu để bắt đầu tìm hiểu, không phải kết luận rằng toàn bộ đèn sẽ sáng hơn hoặc bền hơn.”  
Loại superlative và chuyển sang tiêu chí kiểm tra.

## Checklist reviewer (0–5)

| Tiêu chí | Điểm tự kiểm | Ghi chú |
|---|---:|---|
| Dễ hiểu | 5 | Mở bằng quyết định/tình huống cụ thể |
| Hữu ích thực tế | 5 | Có câu hỏi, giới hạn hoặc bước kiểm tra |
| Tự nhiên | 4 | Tránh opening rập khuôn và cụm lặp |
| Chính xác nguồn | 4 | Claim thương hiệu/model vẫn chờ xác minh |
| Khác biệt/bố cục | 4 | 102/107 và 104/111 đã tách intent |

Tổng sơ bộ 22/25 (quy đổi 26.4/30); không đánh dấu ready-to-publish cho 106/110 cho đến khi hoàn tất source/rights check. Reviewer cần trả lời thêm 5 câu hỏi editorial standard: câu đầu có ích không, có đoạn liệt kê máy móc không, người đọc biết áp dụng gì không, còn sai kỹ thuật/bản quyền/nguồn không, có giống bài cạnh bên không?

## Canonical và old links

Giữ nguyên bảy slug cũ trong draft và không đổi canonical trực tiếp. Với 102/107, trước hết lấy analytics; sau khi owner duyệt URL thắng, dùng 301 từ URL còn lại, cập nhật internal links/canonical/sitemap và theo dõi 404; không tự unpublish. 104 và 111 giữ URL riêng vì đã có intent khác sau biên tập. 106/110 chỉ phát hành sau review nguồn, không dùng ảnh hoặc claim làm bằng chứng khi quyền sử dụng chưa rõ.

## URL nguồn và tình trạng hình ảnh

Baseline xác nhận cả 7 bài không có URL nguồn bên ngoài trong HTML. Bản draft bổ sung nguồn tham khảo chính thức DOE, Lighting Global và Cree LED ở cuối các bài liên quan; các nguồn này chỉ chứng minh nguyên lý/khung kiểm thử, không xác minh thông số Blue Carbon hay model cụ thể.

- 102, 104, 105, 107, 111: chỉ có filename ảnh trong baseline; chưa có source, license hoặc credit, cần bổ sung hồ sơ quyền trước publish.
- 106: giữ remote image URL/alt gốc để không mất asset; URL không phải bằng chứng quyền sử dụng, cần xác minh ownership/license/credit.
- 110: giữ remote certificate image URL/alt gốc để kiểm tra; cần xác minh tính xác thực, liên quan model và quyền sử dụng trước publish.
