# Hình ảnh bài viết Phúc Thịnh Solar — Photo-first editorial (12/11/2026)

## Nguyên tắc cốt lõi

Ưu tiên **ảnh chụp thật, có nguồn gốc rõ ràng và quyền sử dụng**. Ảnh phải giúp người đọc hiểu bài, không phải chỉ để lấp chỗ trống. Góc flycam trang trại điện mặt trời, mái nhà, người đang làm việc, thiết bị, ánh sáng và quy mô thực tế thường hữu ích hơn hình AI cùng một bố cục lặp đi lặp lại.

Không cố định một tỷ lệ cứng cho mọi chuyên mục. **Ảnh chụp thật nên chiếm phần lớn ảnh bìa** khi có ảnh đúng nội dung và quyền sử dụng; infographic là lựa chọn đầu tiên khi chính chủ đề cần so sánh số liệu/sơ đồ; AI chỉ dùng khi không có cách minh họa tốt và ảnh tạo ra có chất lượng biên tập đủ cao. Series chuyên đề có thể dùng một hệ thiết kế nhất quán; bài đơn lẻ nên có bố cục riêng.

Không biến 180 bìa concept `pts-plan-NNN.webp` thành hình xuất bản mặc định: **165 slot chưa viết vẫn ở mức mockup**. Lúc bài chuẩn bị duyệt, biên tập viên chọn ảnh riêng hoặc infographic phù hợp, rồi mới xuất bản. Một bài kiến thức có thể mở bằng ảnh thật, giữa bài có sơ đồ gốc rồi kết thúc bằng bảng so sánh: đa dạng cách trình bày, không lặp một mẫu.

## 6 kiểu hình nên luân phiên

- **Toàn cảnh / flycam:** nhà máy điện mặt trời, công trình quy mô lớn, cấu trúc dãy pin; đặc biệt tốt cho tin ngành, thị trường, xu hướng.
- **Không gian sử dụng thật:** mái nhà dân dụng, xưởng, nông trại, đèn công viên; dùng cho bài ứng dụng/lựa chọn giải pháp.
- **Cận cảnh kỹ thuật:** bề mặt module, đầu nối, inverter, kỹ thuật viên; phù hợp hướng dẫn model và vận hành.
- **Ảnh người thật đang làm việc:** thao tác thi công, kiểm tra, bảo trì; phải ghi rõ ảnh nguồn, không nhận đó là nhân viên Phúc Thịnh Solar.
- **Sơ đồ/infographic gốc:** so sánh kWp/kWh, inverter hybrid/hòa lưới, mốc dữ liệu dự báo; phải chính xác, ít chữ, đọc tốt trên mobile.
- **Biểu đồ có nguồn:** ghi rõ dữ liệu/đơn vị/niên độ, ai lập đồ thị; không dựng ảnh không có căn cứ hoặc tạo giả vật lý thực tế.

## Quy chuẩn thực hiện

1. **Chọn ảnh theo nội dung, không theo từ khóa chung.** Bài mất điện lưới cần mái nhà/inverter/backup thực, không lấy một ảnh trạm pin khổng lồ vô nghĩa. Tin thị trường cần ảnh có quy mô, sức sống và tính thời sự.
2. **16:9 cho ảnh bìa** khoảng 1600 × 900 WebP. Không kéo giãn, không dùng nền blur hai bên để cố nhét ảnh dọc. Nếu ảnh dọc không crop ổn, dùng làm ảnh bên trong bài thay vì bìa.
3. **Giữ ảnh nguồn trong trẻo**, chỉnh sáng/cân bằng màu nhẹ khi cần. Không gắn logo lớn lên ảnh stock hoặc ảnh công trình bên ngoài, tránh gây hiểu nhầm chủ sở hữu. Logo đã có trong giao diện website.
4. **Chú thích dưới ảnh một dòng**: `Ảnh: Tác giả / Pexels`, `Ảnh: Tác giả / Unsplash`, hoặc `Hình minh họa: Phúc Thịnh Solar`; liên kết nguồn khi có. Các điều khoản/license chi tiết để trong registry nội bộ.
5. **Chỉ dùng ảnh có quyền rõ ràng.** URL Google Images hay báo tin không phải giấy phép sử dụng. Ghi tên tác giả/nguồn không thay cho quyền; các trường hợp có người, thương hiệu hoặc quyền tài sản cần xem thêm bối cảnh. Không bê nguyên bài có bản quyền; tham khảo và viết mới.
6. **Không tự nhận ảnh stock là công trình công ty.** Nếu ảnh minh họa, chú thích đúng; tuyệt đối không gán vị trí, công suất hay model không thể xác minh.
7. **Đánh giá thủ công bằng mắt** ảnh trước khi duyệt: chủ thể chính, ánh sáng, độ nét, màu, điểm nhìn, crop thumbnail mobile và sự khác biệt với 6 bài gần nhất.

## Đợt áp dụng thực tế: PTS-KT-004 đến PTS-KT-015

- Đã chọn **11 ảnh Pexels nguồn gốc cụ thể** và **1 infographic gốc cho bài so sánh inverter**.
- Danh sách ảnh, tên tác giả, liên kết ảnh gốc, giấy phép, alt: `backend/resources/editorial/2026-11-12/photo-cover-selections.json`.
- Dòng ghi nguồn frontend lấy từ `backend/resources/editorial/2026-11-12/image-credits.json` thông qua `EditorialImageCredits`.
- File WebP đã được chuẩn hóa 1600×900: `frontend/public/assets/images/editorial/pts-photo-*.webp`; infographic `pts-explainer-inverter-types.webp`.
- Tạo lại bộ ảnh ảnh chụp từ nguồn có phép: `node scripts/content/prepare-editorial-photo-covers.mjs`; không tạo trực tiếp trên production.
- Import local/production qua `php artisan pts:editorial-calendar --apply`. Với bản đã có, chỉ thay bìa generic khi kế hoạch còn `image_status=mockup`, Post vẫn ẩn, cả hai còn dùng đúng ảnh `pts-plan-NNN.webp`. Nếu editor đã đổi ảnh, giữ nguyên không ghi đè.
- Quy tắc *không tự xuất bản* vẫn giữ: `is_visible=false` cho toàn bộ 15 bản nháp.
- 165 kế hoạch chưa viết vẫn có ảnh concept nội bộ để tìm kiếm/biên tập, không được coi đó là lựa chọn ảnh cuối cùng.

## Nguồn giấy phép tham khảo

- Pexels: https://www.pexels.com/license/ ; https://www.pexels.com/terms-of-service/
- Unsplash: https://unsplash.com/license
- Wikimedia Commons: đọc **giấy phép tại trang file cụ thể**, các giấy phép CC BY/SA có yêu cầu khác nhau.

Những ảnh từ press kit của hãng chỉ dùng khi điều khoản cho phép; ưu tiên phân loại rõ `official-asset` và ghi đúng tên hãng. **Không mặc định ảnh trên website hãng được phép tải về đăng lại.**

## Checklist một phút trước khi duyệt bài

- [ ] Bìa có giá trị thông tin, sáng rõ, chất lượng cao và khác biệt với các bài liền kề.
- [ ] Chủ thể chính không bị crop sai trên mobile; không có viền mờ nhân tạo.
- [ ] Có tác giả, URL nguồn và giấy phép nếu dùng ảnh ngoài.
- [ ] Không gắn logo công ty lên ảnh nguồn gây hiểu nhầm.
- [ ] Nếu ảnh tự tạo: chú thích “Hình minh họa: Phúc Thịnh Solar”.
- [ ] Ảnh kỹ thuật và biểu đồ khớp nội dung; không bịa thông số.
- [ ] Ảnh `pts-plan-NNN.webp` không được coi là ảnh xuất bản mặc định.
