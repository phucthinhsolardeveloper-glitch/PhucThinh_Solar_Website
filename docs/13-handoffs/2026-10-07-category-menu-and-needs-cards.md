# Audit menu danh mục và khối “Chọn đèn đúng nhu cầu” — 2026-10-07

## Phạm vi

- Mega-menu sản phẩm desktop theo mẫu: ba cột, tìm kiếm, flyout danh mục con,
  hỗ trợ lựa chọn và hai lối tắt ở chân panel.
- Khối cards `Chọn đèn đúng nhu cầu` trên danh mục Đèn năng lượng mặt trời.

## Phát hiện

- Local có `table_content_section` của `list=20` chứa một block `cards` với ba
  ảnh `den-nlmt-duong-giao-thong.png`, `den-nlmt-san-vuon.png` và
  `den-nlmt-cong-trinh.png`; ba file nằm trong `upload/product` của container
  local.
- Production có `content_section` của `list=20` nhưng thiếu block `cards`, và
  cả ba file ảnh không có trong `shared/upload/product`. Deploy source/Git
  không tự đồng bộ DB hoặc upload runtime, nên chỉ deploy code sẽ không làm
  khối xuất hiện.

## Thay đổi

- Mega-menu desktop dùng chiều rộng `min(1200px, 85vw)` cùng `clamp()` cho
  khoảng đệm, icon, tiêu đề nhóm và chiều cao dòng; breakpoint mobile hiện có
  vẫn giữ drawer riêng.
- Thêm `migrations/071_restore_lighting_needs_cards.php`: dry-run mặc định,
  `--apply` chỉ thêm block khi chưa tồn tại và giữ nguyên mọi block khác.
- Bổ sung kiểm thử cho ba card/ba ảnh và kiểm thử menu không tràn ngang ở
  viewport 1360px và 1920px.

## Quy trình production

1. Backup production đầy đủ trước khi ghi DB/upload.
2. Upload ba ảnh đúng checksum từ local vào `shared/upload/product`.
3. Chạy migration 071 ở chế độ preview, đối chiếu số block, rồi chạy `--apply`.
4. Xóa cache page/asset liên quan và kiểm tra HTML, HTTP status, ba ảnh và
   layout desktop/mobile trên URL `/danh-muc-san-pham/den-nlmt/`.

Rollback code bằng release trước. Rollback dữ liệu bằng bản backup production;
migration 071 không có thao tác xóa hoặc ghi đè block hiện hữu.

## Kiểm thử local

- Predeploy local PHP 8.1, regression, security/admin readiness, commerce smoke
  và restore rehearsal: PASS.
- Playwright menu desktop 1440px và responsive 1360/1920px: PASS.
- Playwright category desktop/mobile, gồm khối cards: PASS.
