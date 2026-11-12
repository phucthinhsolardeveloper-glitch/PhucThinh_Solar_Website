# Đồng bộ bản nháp bài Kiến thức an toàn

`pts:editorial-copy-refresh` chỉ cập nhật `body`, `summary` và mô tả SEO cho bài
`PTS-KT-004..021`. Lệnh mặc định dry-run; luôn truyền baseline JSON tuyệt đối được
chụp từ bản ghi local trước khi chạy:

```bash
php artisan pts:editorial-copy-refresh --baseline=/abs/path/pts-editorial-copy-db-baseline-20261112.json
php artisan pts:editorial-copy-refresh --baseline=/abs/path/pts-editorial-copy-db-baseline-20261112.json --apply
```

`--apply` bị chặn ngoài `local`/`testing`. Lệnh kiểm tra liên kết kế hoạch, id,
slug, kind, ẩn/hiện và SHA-256 của body/summary/SEO so với baseline; drift sẽ bị
bỏ qua, không ghi đè. Mỗi lần có thay đổi tạo bản sao body/summary/SEO trong
`storage/app/private/editorial-copy-refresh/` (ngoài Git) rồi mới cập nhật trong
một transaction. Không thay đổi title, slug, lịch, ảnh, category hoặc liên kết
sản phẩm; chạy lại khi target đã khớp sẽ là no-op.

Baseline máy cụ thể không commit vào repository.
