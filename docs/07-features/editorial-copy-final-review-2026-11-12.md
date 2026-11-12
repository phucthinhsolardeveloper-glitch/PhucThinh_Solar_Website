# QA độc lập bản copy Kiến thức — 12/11/2026

Phạm vi: Markdown `PTS-KT-001..021`, bốn bộ proposal summary, đối chiếu
`/home/admin01/orca/artifacts/pts-editorial-copy-baseline-20261112.json` và
`calendar-180.csv`. QA này chỉ ghi nhận, không sửa thân bài hay metadata dùng
chung. Các đường dẫn dưới đây là line trong Markdown hiện tại.

## Kết quả tổng hợp

- **PASS:** 004–021 (18 bài Markdown đã biên tập, bao gồm 013 đã kiểm lại trên master).
- **NEEDS_REVISION:** 001, 002, 003 (chỉ preview; chưa xác nhận tương thích
  HTML-to-Markdown với bản DB).

## Kiểm tra chung

- Không thấy `PTS-KT-ID` hiển thị trong 21 Markdown; không thấy link `/kien-thuc/`
  trong thân bài.
- Không thấy heading cùng cấp rỗng. Nguồn tham khảo và credit nằm cuối các bài
  có source/image; URL và ảnh của 004–021 khớp baseline, ngoại trừ URL bổ sung
  nêu ở 006.
- H1 của 004–009 khớp title trong CSV. 010–021 chủ động mở bằng đoạn dẫn hoặc
  H2; đây là hợp lệ vì `EditorialCalendarImport` render title CMS thành H1 và
  chỉ loại H1 Markdown nếu source có H1, tránh duplicate H1.
- Các summary proposal đều là câu hướng lợi ích người đọc, không còn dạng kế
  hoạch nội bộ. Summary 012 giữ các số liệu được nêu là dự báo/bản tin; cần
  tiếp tục xác minh nguồn khi publish.

## Theo bài

| Bài | Kết quả | Bằng chứng / lý do |
|---|---|---|
| 001 | NEEDS_REVISION | `articles/PTS-KT-001.md:1`; H1 có mặt, nhưng chỉ là preview; cần đối chiếu body HTML DB trước khi nhận bản Markdown. |
| 002 | NEEDS_REVISION | `articles/PTS-KT-002.md:1`; H1 có mặt, nhưng chỉ là preview; cần đối chiếu body HTML DB và số liệu LONGi trước khi chốt. |
| 003 | NEEDS_REVISION | `articles/PTS-KT-003.md:1`; H1 có mặt, nhưng chỉ là preview; cần đối chiếu body HTML DB và dữ kiện IEA trước khi chốt. |
| 004 | PASS | `articles/PTS-KT-004.md:1`, `:49-52`; H1/CSV khớp, nguồn khớp baseline, ví dụ 16 kWh được đánh dấu giả định ở `:39-43`. |
| 005 | PASS | `articles/PTS-KT-005.md:1`, `:31-34`; H1/CSV khớp, source/credit đủ, không có claim số thiếu điều kiện. |
| 006 | PASS | `articles/PTS-KT-006.md:1`, `:29-33`; H1/CSV khớp, URL bổ sung `solar-and-resilience-basics` là nguồn DOE hợp lệ, source/credit đủ. |
| 007 | PASS | `articles/PTS-KT-007.md:1`, `:32-35`; H1/CSV khớp, nguồn khớp baseline, phân vai hòa lưới/hybrid/độc lập rõ. |
| 008 | PASS | `articles/PTS-KT-008.md:1`, `:33-36`; H1/CSV khớp, source/credit khớp baseline, summary hướng checklist mua đèn. |
| 009 | PASS | `articles/PTS-KT-009.md:1`, `:27-30`; H1/CSV khớp, source/credit khớp baseline, không hứa sản lượng. |
| 010 | PASS | `articles/PTS-KT-010.md:1`, `:38-42`; mở bằng tình huống, source/ảnh khớp baseline, H1 do template CMS render. |
| 011 | PASS | `articles/PTS-KT-011.md:1`, `:39-43`; source/ảnh khớp baseline, số liệu được đặt vào context model/datasheet. |
| 012 | PASS | `articles/PTS-KT-012.md:1`, `:39-43`; source/ảnh khớp baseline, số liệu IEA có nguồn chính thức và được ghi là dự báo. |
| 013 | PASS | Bản cuối kể ba gia đình cùng 450 kWh/tháng có giờ dùng khác nhau, ảnh và nguồn giữ nguyên; không còn heading rỗng hoặc mã nội bộ. Khác bài 016 về thực hành hóa đơn. |
| 014 | PASS | `articles/PTS-KT-014.md:1`, `:34-38`; source/ảnh khớp baseline, phân vai hybrid/hòa lưới rõ. |
| 015 | PASS | `articles/PTS-KT-015.md:1`, `:36-40`; source/ảnh khớp baseline, claim màu được giới hạn theo model/vật liệu. |
| 016 | PASS | `articles/PTS-KT-016.md:1`, `:66-69`; source khớp baseline, hướng dẫn thực hành và cảnh báo PII/an toàn rõ. |
| 017 | PASS | `articles/PTS-KT-017.md:1`, `:49-53`; source khớp baseline, link liên bài dùng slug root đúng. |
| 018 | PASS | `articles/PTS-KT-018.md:1`, `:49-53`; source khớp baseline, ví dụ giảm 20% được trình bày như câu hỏi kiểm tra. |
| 019 | PASS | `articles/PTS-KT-019.md:1`, `:53-57`; source khớp baseline và có cảnh báo an toàn. |
| 020 | PASS | `articles/PTS-KT-020.md:1`, `:52-56`; source khớp baseline, thuật ngữ có phần giải nghĩa. |
| 021 | PASS | `articles/PTS-KT-021.md:1`, `:45-49`; source khớp baseline, claim nhiệt được giới hạn theo điều kiện. |

## Cặp trùng intent

- **008/017:** proposal tách đúng checklist mua đèn và giải nghĩa W/lumen/lux;
  link từ 017 sang 008 ở `PTS-KT-017.md:43` dùng slug root, không dùng
  `/kien-thuc/`. Cần giữ phân vai này khi merge.
- **013/016:** proposal tách ba hồ sơ gia đình và thực hành đọc hóa đơn; 013
  đã rút thành ba hồ sơ khác nhau, tách khỏi bài thực hành hóa đơn 016.
- **005/007/014:** proposal tách inverter là gì, chọn loại hệ, và hòa lưới vs
  hybrid; không phát hiện summary trùng nguyên kế hoạch nội bộ.

## Fact-check bổ sung trực tiếp tại nguồn gốc (12/11/2026)

- **PTS-KT-002 / LONGi LR7-72HVH-650M:** Manufacturer datasheet đúng model nêu STC 650 W, NOCT 495 W, 24,1%, Voc 53,90 V, Vmp 44,56 V, Isc 15,29 A, Imp 14,59 A, hệ số nhiệt Pmax -0,260%/°C, kích thước 2382 × 1134 × 30 mm, khối lượng 28,5 kg. Nguồn: https://static.longi.com/LR_7_72_HVH_630_650_M_30_30_and_15_Explorer_BGV_02_20241118_EN_Draft_426c93d9d1.pdf. Đây là bản BGV02 mẫu, không thay thế việc xác minh tài liệu lô hàng trước báo giá.
- **PTS-KT-003 / IEA PVPS Trends 2026:** nguồn chính IEA PVPS tháng 9/2026 ghi 2,96 TW lũy kế cuối 2025; khoảng 690 GW lắp mới năm 2025; tăng 15% so với 2024. Snapshot sơ bộ tháng 4/2026 ghi 698 GW; không được trộn hai bản. Nguồn: https://iea-pvps.org/trends_reports/trends-2026/ và https://iea-pvps.org/snapshot-reports/snapshot-2026/.
- **PTS-KT-012 / IEA Renewables 2026:** công bố 06/11/2026, dự báo gần 4.800 GW thêm trong 2026–2031, tăng 57% so với 6 năm trước; tỷ trọng điện tái tạo tăng từ 33% năm 2025 lên gần 45% năm 2031. Bài sử dụng khoảng 43% là điểm đọc chi tiết và nêu rõ tóm tắt ghi gần 45%. Nguồn: https://www.iea.org/reports/renewables-2026/executive-summary và https://www.iea.org/reports/renewables-2026.
- **Phạm vi xác minh:** ba nhóm dữ kiện chính đã được đối chiếu nguồn công khai. Những claim an toàn/thiết kế và datasheet từng model khác vẫn phải được kiểm tra trước khi xuất bản; không xem đây là giấy phép sử dụng lại ảnh hoặc toàn bộ báo cáo.

## Kết quả cập nhật local có kiểm soát

- Đã chạy bộ test Laravel đầy đủ: **390 pass, 1 skipped, 2704 assertions**.
- Lệnh dry-run xác định 18 bản ghi PTS-KT-004–021 cần chỉnh; apply thành công với baseline hash DB gốc và bản lưu riêng trong Laravel private storage; lần apply thứ hai **0 thay đổi**.
- Xác nhận sau apply: **18 body HTML và SEO summary khớp nguồn Git; 21 bài vẫn ẩn; 3 bài 001–003 không bị sửa; 38 liên kết sản phẩm không đổi; 6 ảnh inline giữ nguyên, URL source không mất**.
- Bản ghi đang ẩn có lịch dự kiến **không được tự công khai**; các bài 016–021 còn chờ ảnh bìa cuối được duyệt trước khi đăng.
- Không có ghi đè sản phẩm, ảnh bìa, category, slug hay publish_at; không push, không deploy production.

## Việc còn lại

Cần kiểm tra tương thích HTML-to-Markdown của 001–003 trước khi thay bản nháp trong CMS. Bài 013 đã được rà từ bản cuối trên master. Báo cáo QA không tự sửa DB, production hoặc trạng thái hiển thị.
