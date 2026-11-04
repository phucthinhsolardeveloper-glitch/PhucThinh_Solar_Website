# Documentation changelog

## 2026-11-04

- Đồng bộ chỉ dẫn thiết kế theo ngôn ngữ PTS hiện hành: design language 2026-11
  là authority cho thị giác; public UI/UX contract tiếp tục giữ quy tắc về dữ
  liệu, CMS, IA, URL/SEO, media, responsive, accessibility và behavior.
- Phân biệt rõ PHP visual parity cho migration/parity với creative redesign;
  đánh dấu các điều khoản flat-only, radius 2–5px và snapshot audit cũ là lịch
  sử thay vì ràng buộc cho thiết kế mới.
- Thêm ADR 0010 và cập nhật entrypoint/changelog để không làm mất các yêu cầu
  trang chủ, commerce, dữ liệu thật hoặc bảo vệ URL khi đổi presentation.
- Chỉ thay đổi tài liệu; không đổi runtime, production, database hoặc deploy.

## 2026-10-30

- Tích hợp nâng cấp giao diện Nuxt từ checkpoint `feature/ui-layout-upgrade-20261029`:
  hero cross-fade, hiệu ứng tương tác, gallery/lightbox PDP, TOC bám nội dung,
  modal tư vấn/đại lý, badge giảm giá, chọn chi nhánh và metadata bài viết.
- Giữ các trạng thái loading/mutation giỏ hàng, fallback ảnh, kích thước nút chạm
  trên mobile và scrollspy hiện hành; nội dung cam kết PDP dùng lại câu chữ PHP.
- Xác minh cây đã gộp: `npm test` 409/409, `npm run typecheck` PASS,
  `npm run build` PASS. Chỉ cập nhật local rehearsal; không thay đổi production.

## 2026-09-22

- Chuẩn hóa catalogue còn 9 danh mục cấp 1; ẩn `Hệ khung & phụ kiện tấm pin`,
  chuyển ba nhóm con và 12 sản phẩm sang `Tấm pin năng lượng mặt trời`, không
  tạo cấp 3; giữ URL cũ bằng redirect 301.
- Đồng bộ thứ tự 9 danh mục cho header desktop/mobile, landing sản phẩm và rail
  trang chủ; cập nhật rail từ 10 sang 9 ô và bổ sung regression cho URL cũ.
- Thêm nguồn chuẩn duy nhất cho UI/UX public: định hướng, token, layout,
  component, media, responsive, accessibility, ownership và Definition of Done.
- Thêm ADR 0007 để ngăn từng trang tự tạo container/ngôn ngữ thiết kế riêng.
- Cập nhật README, Sổ tay, AI context và `AGENTS.md` để route mọi thay đổi giao
  diện qua Design System trước frontend implementation guide.
- Sửa thứ tự CSS trong frontend guide theo runtime hiện tại và bổ sung addendum
  audit container Full HD/iMac.

## 2026-08-26

- Tạo documentation bootstrap từ source/schema/backup/runtime evidence.
- Thêm fact map, architecture, data/API/auth/security/operations/features/integrations, audit và AI operating rules.
- Không thay đổi PHP, database hoặc production behavior.

# 2026-08-29

- Chuyển thông số kỹ thuật sản phẩm từ bảng HTML sang 19 trường chuẩn và danh sách thông số riêng.
- Admin và Excel import/export nhận từng thông số theo ô; frontend dựng lại bảng từ dữ liệu cấu trúc.
- Thêm migration dry-run/apply, không xóa nội dung HTML cũ.
- Chuẩn hóa parser thông số giữa CRUD form và import; giữ đủ dữ liệu legacy trùng nhãn trên frontend/export.
- Bulk commit dùng transaction kèm rollback thủ công cho database engine legacy; bổ sung test XLS, CSV, `__CLEAR__`, fault rollback và fallback HTML cũ.
- Thêm migration 006 dry-run để chuyển nhãn đồng nghĩa vào trường chuẩn và đổi tên nhãn trùng mà không mất giá trị.
- Tối giản trải nghiệm Excel: chỉ mã/tên bắt buộc khi tạo mới, tô màu cột theo vai trò và viết lại sheet hướng dẫn ngắn gọn về ô trống, dữ liệu tự động, `__CLEAR__` và trạng thái website.
- Hardening local: xóa đơn POST-only; CSRF cho mutation đơn hàng, checkout và review; allowlist API Admin; kiểm MIME/size upload và chặn thực thi script trong `upload/`.
- Bổ sung regression test security cho token, allowlist, rate limit, ảnh hợp lệ/file giả ảnh; HTTP test xác nhận request thiếu token bị chặn và token hợp lệ vẫn đi qua validation nghiệp vụ.
- Chuyển rate limit checkout/review từ session sang DB và bổ sung cho contact, tư vấn sản phẩm, đăng ký đại lý; tách bucket băm theo form/IP, mặc định 5 lượt/10 phút và không lưu nội dung form.
- Thêm validation server-side cho CRUD sản phẩm: định dạng giá/công suất, giá không âm và giá mới không vượt giá bán; không thay đổi dữ liệu sản phẩm hiện có.
- Khóa mass-assignment khi Admin cập nhật đơn: chỉ nhận bốn field nghiệp vụ được phép và xác minh trạng thái đơn/thanh toán phía server.
- Sửa upload đính kèm liên hệ dùng validator tài liệu; mở allowlist MIME cho PDF/Office/archive/ảnh, vẫn từ chối PHP giả, MIME sai và file vượt dung lượng.
- Bổ sung CSRF cho liên hệ, tư vấn sản phẩm và đăng ký đại lý; request thiếu token bị chặn trước khi ghi DB.
- Thêm `scripts/predeploy-local.sh` làm cổng kiểm tra một lệnh trước deploy: build, lint, security/RBAC/Excel regression, frontend smoke, log và cleanup.
- Thêm chế độ báo giá toàn website: giữ giá nội bộ, frontend/API/schema không công khai giá, gỡ lọc/sắp xếp giá và chặn cart/checkout cho đến khi được duyệt niêm yết.
