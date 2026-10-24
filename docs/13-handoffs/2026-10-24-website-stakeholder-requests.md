# Sổ yêu cầu phối hợp website Phúc Thịnh Solar — 24/10/2026

**Trạng thái:** Mới chuẩn bị nội dung để gửi, CHƯA gửi tới sếp/phòng ban. Deadline là mục tiêu đề xuất, chỉ có hiệu lực sau khi sếp chốt.
**Project owner kỹ thuật:** người phụ trách website. **Phạm vi:** UI/UX cohesion, storyline, product/solution/project proof, không thay đổi production.
**Đích nghiệm thu:** 06/11/2026 — release candidate, quyết định GO/NO-GO; deploy riêng khi được chấp thuận.

## Request / owner / deadline / proof

| ID | Người/phòng ban phụ trách | Yêu cầu cụ thể và format | Hạn đề xuất | Trạng thái | Nếu chưa đủ |
|---|---|---|---|---|---|
| ST-01 | Sếp | Chọn ưu tiên: **sản phẩm & phân phối**, **giải pháp & thi công**, hay **cân bằng**; duyệt 1 câu định vị và 3 trụ cột bằng chứng | 26/10 17:00 | Chưa gửi | Giữ wording đã duyệt, không thay hero copy |
| ST-02 | Sếp + Marketing | Chọn tối đa 2 hướng visual: **showroom kỹ thuật tinh gọn**, **corporate năng lực/chứng thực**; đồng ý scope trang cần thay mạnh hơn và trang phải giữ nguyên | 27/10 12:00 | Chưa gửi | Không implement concept mới khi chưa sign-off |
| ST-03 | Marketing | Brand kit gốc, logo/vector, ảnh showroom/kho/nhân viên/sản phẩm có quyền sử dụng, nội dung bản chuẩn từng trang, asset/source URL và người duyệt | 27/10 17:00 | Chưa gửi | Giữ ảnh/copy có nguồn hiện hữu, không dùng stock/AI chứng minh năng lực |
| ST-04 | Kinh doanh | Top 5 nhu cầu khách và câu hỏi thường gặp, top 20 SKU ưu tiên, lead CTA theo gia đình/doanh nghiệp/đại lý, ưu tiên trang và chính sách báo giá | 28/10 12:00 | Chưa gửi | Dùng CTA hiện hành, không hứa SLA/giá mới |
| ST-05 | Kho + Kỹ thuật | 20 SKU có mã + ảnh + 4 specs quyết định + chính sách bảo hành, phân nhóm sản phẩm ứng với 5 giải pháp, owner xác nhận tính đúng | 29/10 12:00 | Chưa gửi | Ẩn khối thiếu, không tự tạo thông số |
| ST-06 | Kỹ thuật + Sales + Marketing | 2–3 hồ sơ dự án xác minh: bài toán, phạm vi, thiết bị, địa điểm/chủ đầu tư được phép công bố, ảnh trước/sau và kết quả nếu có chứng cứ | 29/10 17:00 | Chưa gửi | Không tự sáng tác case study; giữ dự án thật có thông tin |
| ST-07 | Vận hành/pháp lý + Marketing | Kiểm nội dung các chính sách mới đang công bố; rà URL cũ 404 cần redirect/410/canonical, địa chỉ cơ sở, giờ làm và tuyên bố bảo hành | 29/10 17:00 | Chưa gửi | Không sửa điều khoản; giữ policy mới hiện có, báo rủi ro SEO |
| ST-08 | Dev + QA | Route map/screenshots cùng commit trên 390/768/1440/1920; nhận định có số đo, chi tiết QA API/form/SEO/interaction, bug P0/P1 và rollback | 02/11 17:00 | Đang audit read-only | Đánh dấu BLOCKED; không GO nếu không có staging/bản build đúng |
| ST-09 | Sếp + Dev + QA + đại diện dữ liệu | Duyệt storyline, visual, copy/assets, nghiệp vụ & bản release candidate; GO/NO-GO bằng chữ có lưu | 06/11 17:00 | Chưa bắt đầu | Chỉ hoàn tất RC local/staging, không deploy |

## Câu hỏi cần gửi sếp (chỉ 3 điều)

1. Website ưu tiên chứng minh Phúc Thịnh Solar là **nhà phân phối thiết bị**, **đơn vị tư vấn thi công**, hay **cân bằng hai vai trò**? Đâu là đối tượng mua hàng ưu tiên trong 3 tháng?
2. Sếp muốn hình ảnh hướng **showroom kỹ thuật tinh gọn** hay **doanh nghiệp năng lực/dự án thực tế**? Nhờ chỉ rõ 1–2 web/ảnh tham chiếu, những trang hiện tại sếp thích và không thích.
3. Sếp đồng ý mục tiêu **27/10 duyệt concept → 02/11 xem bản chạy local → 06/11 quyết định nghiệm thu** không? Hạng mục nào sếp muốn duyệt trực tiếp để tránh làm sai khẩu vị?

## Quy tắc chủ động

- Báo sớm khi thiếu asset/claim/quyết định; không dùng placeholder như bằng chứng.
- Mỗi checkpoint gửi sếp một note: xong gì, ảnh/URL minh chứng, bị chặn bởi ai, mốc phản hồi cần, phương án nếu trễ.
- Dữ liệu phản hồi dùng file chung có owner và nguồn; không để developer tự viết claim, số liệu, chính sách.
- Công việc kỹ thuật độc lập (audit CSS, đo page, accessibility, visual baselines) làm ngay; nội dung/chuyển layout chưa được duyệt tách khỏi nhánh release.
- Không merge/deploy trước QA và sign-off.
