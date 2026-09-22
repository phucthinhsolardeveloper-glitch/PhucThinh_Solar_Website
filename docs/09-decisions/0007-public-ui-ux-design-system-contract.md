# ADR 0007: Một hợp đồng UI/UX dùng chung cho toàn bộ public site

- Ngày: 2026-09-22
- Trạng thái: Accepted
- Owner: Chủ project Phúc Thịnh Solar

## Context

Các quy tắc UI/UX đã tồn tại trong `AGENTS.md`, nhiều ADR, frontend guide,
stylesheet và audit. Mỗi nguồn chỉ bao phủ một phần. Hậu quả là các hub dùng
container, radius, icon, card, ảnh và khoảng cách khác nhau; lỗi phải sửa từng
trang và thường tái xuất hiện ở viewport Full HD/iMac.

## Evidence

- Nhiều thế hệ CSS và specificity/`!important` chồng lớp.
- `theme-v2.css` vẫn chứa radius 10–20px trong khi quy tắc đã duyệt là 2–5px.
- Breadcrumb, media project và container từng có implementation khác hợp đồng.
- Cùng vai trò màu từng có nhiều navy/gold gần nhau ở các hub.
- Tài liệu frontend cũ mô tả sai một phần thứ tự file CSS đang nạp.

## Decision

1. `docs/02-development/public-ui-ux-design-system.md` là nguồn chuẩn duy nhất
   về UI/UX public.
2. `frontend-guide.md` chỉ sở hữu cách triển khai/cascade, không tự định nghĩa
   lại hướng thiết kế.
3. Component dùng ở từ hai nơi trở lên phải có owner shared và test theo route.
4. Thay đổi hướng thiết kế/shared contract cần ADR; CSS cục bộ không được tạo
   ngoại lệ ngầm.
5. Legacy được migrate theo component/lát dọc, không refactor big-bang.

## Alternatives

- Chỉ mở rộng `AGENTS.md`: ngắn gọn nhưng không đủ component/state/test contract.
- Giữ rule trong từng CSS: mô tả implementation, không phân biệt hiện trạng sai
  với ý định đã duyệt.
- Viết guide riêng cho từng trang: tiếp tục tạo nhiều nguồn sự thật.

## Consequences

- Người và AI có điểm vào thống nhất trước khi sửa giao diện.
- Review có tiêu chí cụ thể cho container, component, responsive và accessibility.
- Cần audit/migrate dần các rule legacy; việc chấp nhận ADR không có nghĩa code
  hiện tại đã tự động tuân thủ toàn bộ.

## Rollback

Nếu mô hình này không phù hợp, revert ADR và các link điều hướng docs. Không có
thay đổi database, production data hoặc runtime từ riêng quyết định này.
