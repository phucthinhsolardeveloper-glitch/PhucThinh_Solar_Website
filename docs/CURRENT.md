# Trạng thái hiện hành — Website Phúc Thịnh Solar

Cập nhật: 2026-11-28 15:02 (+07). Snapshot mã ứng dụng local: master `596ce498`; commit tài liệu có thể mới hơn.

## Production đã xác minh

VPS: backend, Nuxt, worker và scheduler đều có OCI revision `596ce4984602c1047a7c74228989694594d407fd`. Release log ngày 28/11: 12 URL HTTP 200, 5 feature check PASS, 1 WARN (lệnh editorial-drafts đã xác nhận tồn tại khi kiểm lại, không chạy import). Backend và Nuxt healthy, worker/scheduler running; worker restart count 5, nguyên nhân chưa xác định, không thấy lỗi trong log 30 phút được kiểm. Đĩa 76%. Backup trước release có tại `backups/20261128-596ce498-predeploy/`.

CMS liên kết nguồn nội dung, bộ lọc và lịch bài viết; tra cứu đơn hàng; chỉnh khối đại lý trang chủ; nội dung mới và bản viết lại ở trạng thái chờ duyệt theo báo cáo phát hành. Không tự xuất bản. Đã merge CMS/order lookup/editorial, 3D vẫn chưa commit.

Nguồn: [release/deploy record](06-operations/deployment-log.md). Không suy ra revision production từ HEAD.

## Việc tiếp theo

- Đối soát thanh toán thủ công và Nhất Tín (cần thông tin nghiệp vụ/provider), finance/payment đang lưu archive; không tự bật API ngân hàng.
- Nghiệm thu tra cứu đơn thật bởi chủ website, quyền editor, các bộ lọc đơn hàng còn lệch số đếm; preview toàn bộ nội dung chưa hoàn tất.
- Solar Visual Calculator: worktree riêng còn WIP, không merge/xóa khi chưa nghiệm thu.
- Duyệt bài và bản nháp, xác nhận số liệu bằng nguồn thật. Theo dõi worker restart và kiểm tra email thật khi được phép.
- Kiểm tra Git status, remote và worktree trực tiếp; không dùng tài liệu như số đếm realtime.

Đọc [sổ tay](SO-TAY-CHU-PROJECT.md) để tìm code và kiểm thử. Trạng thái task chi tiết nằm ở issue/worktree/handoff hiện có.

## Quy tắc cập nhật
Khi merge: sửa snapshot local, scope, việc chưa xong; khi deploy: ghi SHA/image, môi trường, thời điểm, health/test, backup/rollback và điều chưa xác minh vào release log. Không ghi secret hoặc dữ liệu khách.
