# Phúc Thịnh Solar — V2 concept review

Ngày: 26/10/2026. Đây là **thiết kế**, không phải code production, không được dùng để tuyên bố chức năng đã hoàn thành.

## Bản thiết kế kiểm tra
- **V1 chuẩn**: [Home HTML final](https://stitch.google.com/projects/5456517467698612459?node-id=13025497894490543111) — trang chủ thật, đã QA source.
- Concept A Stitch gốc: https://stitch.google.com/projects/5456517467698612459?node-id=a136d61bf2a24449b17709c8c4d81887
- **Concept A đã thay tài sản thật**: https://stitch.google.com/projects/5456517467698612459?node-id=8829550416884793261
- Concept B Stitch: https://stitch.google.com/projects/5456517467698612459?node-id=743a02bce7244d618bd529ea6515571d

## Review kiểm chứng được
| Tiêu chí | A Technical Premium | B Calm Commerce |
| --- | --- | --- |
| Mạch thông tin từ nguồn | Tương đối đầy đủ: sản phẩm, giải pháp, quy trình, đại lý, tư vấn, footer | Rút gọn; thiếu hoặc giảm đáng kể một số nhóm sản phẩm/section nguồn |
| Nhận diện vàng & navy | Đúng hướng thiết kế kỹ thuật | Đúng bảng màu, thông tin điều hướng rõ |
| Logo thật | **Bản A gốc: sai**, đã thay bằng logo thật trên bản đã sửa | Logo PTS giả — chưa xử lý |
| Hình ảnh thật | **Bản A gốc: 10 ảnh không thuộc domain site**; đã thay cả 10 bằng ảnh nguồn đúng chủ đề | 2 ảnh cùng nguồn Stitch, chưa đạt yêu cầu |
| Nội dung tiếng Việt | Có | Có nhưng tự sinh/bỏ bớt mô tả |
| Thông tin không chắc chắn | Còn cần kiểm tra từng chính sách, thông số, liên kết và microcopy | Có văn bản năm 2025 trong HTML — cần loại bỏ |
| Khả năng nghiệm thu | **Candidate** sau khi sửa nguồn ảnh/logo; tiếp tục QA desktop/mobile | Reference visual only, chưa duyệt |

## Tài sản nguồn trên V2-A đã sửa
- Header dùng wordmark/logo gold thật của Phúc Thịnh Solar.
- Footer dùng biểu tượng logo thật.
- 10 ảnh chính V2-A được ánh xạ từ hero, nhóm sản phẩm, giải pháp và ảnh dự án/ảnh NLMT của chính phucthinhsolar.com. Dữ liệu mapping lưu local ở `.stitch/generated/V2-A/asset-map.json` (không commit asset và file font).
- Không dùng ảnh công trình giả/Stitch-generated trong bản V2-A đã sửa.

## Ngôn ngữ thiết kế đề xuất
**Precision Industrial**: mạnh kỹ thuật, không phô trương; navy + vàng kim + nền trắng; vuông vức bo nhẹ; phân cấp typographic và khoảng trắng có chủ đích; component search/filter/card/gallery/form đồng nhất; hiệu ứng 120–240ms, không gradient AI hay blur/animation nặng.

## Điều kiện chưa đạt
- V2-A mới là concept; **chưa phê duyệt cuối**, chưa kiểm tra ảnh toàn trang theo từng section và chưa test hoạt động UI.
- Logo/hình ảnh thật đã được mapping vào bản A đã sửa; vẫn phải kiểm tra chất lượng crop và tỷ lệ trong từng viewport.
- Trạng thái responsive mobile/listing/PDP và hành vi lọc, gallery, form phải được kiểm tra khi các màn được sinh.
- Không tự tạo dữ liệu giá, kho, chứng chỉ, KPI, địa chỉ hoặc chính sách vượt quá nội dung source.

## Tiếp theo
Dựa vào bản A đã sửa (không dựa vào A gốc), đối chiếu 3 luồng home mobile / category / product detail, chỉnh asset đúng nguồn, rồi hệ thống hóa tokens/components trước khi handoff sang Nuxt. Không merge/deploy.
