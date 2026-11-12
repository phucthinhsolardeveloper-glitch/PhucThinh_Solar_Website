# QA độc lập bản copy Kiến thức — 12/11/2026

Phạm vi: Markdown `PTS-KT-001..021`, bốn bộ proposal summary, đối chiếu
`/home/admin01/orca/artifacts/pts-editorial-copy-baseline-20261112.json` và
`calendar-180.csv`. QA này chỉ ghi nhận, không sửa thân bài hay metadata dùng
chung. Các đường dẫn dưới đây là line trong Markdown hiện tại.

## Kết quả tổng hợp

- **PASS:** 004, 005, 006, 007, 008, 009, 010, 011, 012, 014, 015, 016, 017,
  018, 019, 020, 021.
- **NEEDS_REVISION:** 001, 002, 003 (chỉ preview; chưa xác nhận tương thích
  HTML-to-Markdown với bản DB).
- **PENDING / NEEDS_REVISION:** 013 — bản master đang chờ delta copy desk; không
  chốt đạt trong đợt này.

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
| 013 | PENDING / NEEDS_REVISION | `articles/PTS-KT-013.md:1`, `:83-87`; copy desk đang tiếp tục chỉnh, không chốt bản hiện tại. Nguồn/ảnh khớp baseline. |
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
  đang pending copy desk nên chưa thể chốt cặp.
- **005/007/014:** proposal tách inverter là gì, chọn loại hệ, và hòa lưới vs
  hybrid; không phát hiện summary trùng nguyên kế hoạch nội bộ.

## Việc còn lại

Hoàn tất HTML-to-Markdown compatibility của 001–003; chờ delta 013 rồi chạy lại
QA cặp 013/016. Không có DB, production, metadata dùng chung hoặc thân bài nào
được sửa trong QA này.
