# ADR 0007: Một hợp đồng UI/UX dùng chung cho toàn bộ public site

- Ngày: 2026-09-22
- Trạng thái: Accepted for durable UX/implementation contracts; visual-authority clause superseded 2026-11-04 by ADR 0010
- Owner: Chủ project Phúc Thịnh Solar

## Context

Các quy tắc UI/UX đã tồn tại trong `AGENTS.md`, nhiều ADR, frontend guide,
stylesheet và audit. Mỗi nguồn chỉ bao phủ một phần. Hậu quả là các hub dùng
container, radius, icon, card, ảnh và khoảng cách khác nhau; lỗi phải sửa từng
trang và thường tái xuất hiện ở viewport Full HD/iMac.

## Evidence

- Nhiều thế hệ CSS và specificity/`!important` chồng lớp.
- Historical evidence: `theme-v2.css` contained radii that differed from the then-current 2–5px rule. That comparison is not a current design requirement.
- Breadcrumb, media project và container từng có implementation khác hợp đồng.
- Cùng vai trò màu từng có nhiều navy/gold gần nhau ở các hub.
- Tài liệu frontend cũ mô tả sai một phần thứ tự file CSS đang nạp.

## Decision

1. Tài liệu `docs/02-development/public-ui-ux-design-system.md` sở hữu các hợp
   đồng UX, dữ liệu, URL/SEO, media, responsive, accessibility và behavior.
   Ngôn ngữ thị giác hiện hành do
   `docs/15-design-delivery/12-PTS-DESIGN-LANGUAGE-2026-11.md` sở hữu.
2. `frontend-guide.md` sở hữu cách triển khai/cascade, không tự định nghĩa lại
   hướng thiết kế.
3. Component dùng ở từ hai nơi trở lên cần owner shared và kiểm tra trên các
   route sử dụng.
4. Thay đổi token/hợp đồng thị giác dùng chung cần owner direction và được ghi
   nhận; composition riêng của một route nằm trong phạm vi redesign được giao
   không phải ngoại lệ token ngầm.
5. Legacy được migrate theo component/lát dọc, không refactor big-bang.

## Alternatives

- Chỉ mở rộng `AGENTS.md`: ngắn gọn nhưng không đủ component/state/test contract.
- Giữ rule trong từng CSS: mô tả implementation, không phân biệt hiện trạng sai
  với ý định đã duyệt.
- Viết guide riêng cho từng trang: tiếp tục tạo nhiều nguồn sự thật.

## Consequences

- Người và AI có điểm vào thống nhất cho từng loại quyết định: design language
  cho thị giác, public UI contract cho nghiệp vụ và behavior.
- Review có tiêu chí cụ thể cho container, component, responsive và accessibility.
- Cần audit/migrate dần các rule legacy; việc chấp nhận ADR không có nghĩa code
  hiện tại đã tự động tuân thủ toàn bộ.

## Rollback

Nếu mô hình này không phù hợp, revert ADR và các link điều hướng docs. Không có
thay đổi database, production data hoặc runtime từ riêng quyết định này.
