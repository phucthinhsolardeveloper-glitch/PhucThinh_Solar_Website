# Đồng bộ bản nháp bài Kiến thức an toàn

Lệnh `pts:editorial-copy-refresh` cập nhật nội dung (body), summary và mô tả SEO cho
bài **PTS-KT-004..021** theo mặc định. Để cập nhật **PTS-KT-001..003** từ HTML
cũ sang Markdown đã biên tập, bắt buộc dùng thêm flag `--include-initial-three`.

Luôn truyền baseline JSON tuyệt đối được chụp **từ DB local trước khi cập nhật**.
Không commit file baseline có dữ liệu nội bộ vào Git.

```bash
php artisan pts:editorial-copy-refresh --baseline=/abs/path/pts-kt-round2-baseline.json
php artisan pts:editorial-copy-refresh --baseline=/abs/path/pts-kt-round2-baseline.json --include-initial-three
# Chỉ sau khi dry-run đúng, quyền biên tập cho phép và dữ liệu không drift:
php artisan pts:editorial-copy-refresh --baseline=/abs/path/pts-kt-round2-baseline.json --include-initial-three --apply
```

`--apply` bị chặn ngoài môi trường `local`/`testing`. Lệnh kiểm tra
EditorialPlanItem, post ID/slug/kind, trạng thái ẩn, SHA-256 body/summary/SEO,
và sau đó mới ghi bản sao trong Laravel private storage tại
`storage/app/private/editorial-copy-refresh/` trước transaction cập nhật.
Chạy lại với nội dung đã khớp là no-op, không bị coi là editor drift.
Không thay title, slug, lịch, category, ảnh bìa, quan hệ sản phẩm hoặc visibility.

## Bài 001–003: điều kiện bắt buộc trước khi đổi HTML

Đã đối chiếu đầu vào HTML cũ với Markdown biên tập vòng hai:
- 001: giữ nguyên URL liên kết, ảnh và bảng khi render.
- 002: giữ nguyên URL liên kết, ảnh và bảng; thay đổi cơ cấu heading theo guide đọc datasheet.
- 003: giữ nguyên URL liên kết, ảnh và bảng; thay đổi cơ cấu heading theo bản tin IEA PVPS.

Không dùng flag `--include-initial-three` trên production hoặc khi bài đã công khai.
Nếu editor thay đổi bất kỳ body, summary hoặc SEO mô tả sau snapshot, preflight
sẽ chặn ghi đè. Việc render Markdown từ HTML làm thay đổi cấu trúc trình bày;
reviewer vẫn phải xem preview trước khi bật bài.

## Kết quả local (12/11/2026)

- Đã cập nhật đủ **21/21** bài PTS-KT-001–021 qua hai lượt, mỗi lượt dùng baseline riêng.
- Đối chiếu dữ liệu sau cập nhật: 21 body HTML trùng bản render Markdown,
  21 summary/SEO description trùng nội dung đã duyệt; 21 bài vẫn ẩn.
- Giữ 9 ảnh inline và 38 liên kết sản phẩm; không thay đổi ngày, slug hoặc ảnh bìa.
- Chạy lại có 0 thay đổi; bản sao backup đặt ngoài Git.
- Test `EditorialCopyRefreshTest` sau bổ sung opt-in: 3 passed, 29 assertions.
- Chưa push hoặc triển khai production; 12 bài công khai cũ xử lý bằng
  quy trình thẩm định riêng `pts:content-rework-apply`, không tự động cập nhật.

Nếu rollback, lấy bản sao có timestamp từ private Laravel storage,
kiểm tra ID/slug/hash hiện tại và khôi phục bằng transaction đã kiểm duyệt;
không nên ghi đè thủ công khi có sửa mới của biên tập viên.
