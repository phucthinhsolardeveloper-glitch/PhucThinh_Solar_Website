# Phúc Thịnh Solar — V1 clone QA (26/10/2026)

## Phạm vi hoàn thành
**V1 STATIC VISUAL BASELINE: PASS** cho 11 mẫu trang trên Desktop 1280×900 và Mobile 390×844, dưới dạng HTML đã clone từ source thật trên phucthinhsolar.com (không phải bản AI tự tạo).

- Dự án Stitch PRIVATE: https://stitch.google.com/projects/5456517467698612459
- 11/11 HTML chuẩn nguồn tiếng Việt đã upload vào Canvas thành công; 0 cảnh báo asset.
- 22/22 ảnh full-page tham chiếu nguồn (11 route × desktop/mobile) đã upload; thêm state hamburger và tìm kiếm.
- Đã sửa đường dẫn ảnh/CSS tương đối và nhúng FontAwesome cùng đủ font Be Vietnam Pro (Việt + Latin) vào HTML local clone.
- Không sửa production, master PHP, nhánh chuyển Nuxt và không thay đổi database.

## Đối chiếu đã thực hiện
| Phép đo | Kết quả | Bằng chứng |
| --- | --- | --- |
| HTTP trang nguồn, lang=vi | 22/22 PASS | .stitch/qa/manifest.json và .stitch/qa-extra/manifest.json |
| Nội dung/thứ tự H1/H2/H3 | 22/22 PASS | .stitch/qa/fidelity-dom.json |
| X/Y/width/height của từng heading | 22/22 PASS, 0px chênh lệch | .stitch/qa/fidelity-dom.json |
| Tổng chiều cao DOM | 22/22 PASS, 0px chênh lệch | .stitch/qa/fidelity-dom.json |
| So ảnh **vùng nhìn đầu tiên** live vs HTML local | 22/22 PASS, 0 pixel chênh tại ngưỡng pixelmatch=0.15, bỏ qua anti-alias | .stitch/qa/pixel-all-final/report.json |
| Upload Stitch HTML bản final | 11/11 PASS, warnings=0 | .stitch/upload-final-logs/ |
| Menu mobile nguồn | Đã chụp | .stitch/qa/home-mobile-hamburger-vi.png |

**Cách hiểu phép đo pixel:** phép kiểm so sánh screenshot chỉ ở viewport đầu tiên, cùng browser/context, CSS animation tắt và ảnh render ổn định. Đây không phải bằng chứng pixel-perfect cho toàn bộ chiều cao trang, mọi thời điểm carousel, tương tác hay browser khác.

## Ranh giới chưa nghiệm thu (không che giấu)
- Các trạng thái chức năng: dropdown/mega menu, filter, sort, pagination, search, product gallery, form validation, carousel slide, CTA và các thao tác sâu chưa có test automation đầy đủ.
- Khi mở HTML tĩnh ngoài domain gốc, script endpoint /api/addons.php?type=script-main có thể bị CORS; vì vậy bản clone HTML trong Canvas là **tham chiếu thiết kế** chứ không phải deployment frontend hoạt động tương đương production.
- Ảnh chụp toàn trang có để làm reference nhưng chưa chạy pixelmatch full-page toàn bộ sections; chưa kiểm chứng tablet và multi-browser.
- Mọi bản V1 tạo bởi AI từ ảnh được gắn nhãn DRAFT, không thay thế bản HTML nguồn đã kiểm chứng.

## Gate cho giai đoạn V2
- Đủ cơ sở **khóa mốc giao diện tĩnh V1** trong tài liệu và tạo các màn Concept V2 *mới*, không thay đổi các screen V1 reference.
- Phương án V2 chỉ là thiết kế, cần QA responsive, content, UX/interaction và chọn ngôn ngữ thiết kế thống nhất trước khi handoff sang Nuxt.
- Chưa merge/deploy bất kỳ thay đổi giao diện/website nào.

Xem danh sách screen chính xác: SCREEN-INVENTORY.md. Mọi script và screenshot local ở thư mục .stitch/ (ignore Git).
