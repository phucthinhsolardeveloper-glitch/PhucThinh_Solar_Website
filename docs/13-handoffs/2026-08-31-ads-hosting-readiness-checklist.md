# Checklist sẵn sàng chạy quảng cáo và cập nhật hosting — 2026-08-31

Phạm vi thực hiện hiện tại là source, Git và Docker local PHP 7.4. Không deploy, không ghi production và không nhập dữ liệu thật khi chưa có phê duyệt riêng.

## Nguyên tắc điều phối

Tài liệu này là checklist cha và là ưu tiên triển khai chính cho đến khi website đủ điều kiện cập nhật hosting. Các yêu cầu phát sinh về giao diện, nội dung hoặc lỗi trong quá trình thực hiện là task con: xử lý trong phạm vi phù hợp rồi quay lại mục đang làm của checklist này. Chỉ đổi ưu tiên khi chủ project yêu cầu rõ ràng.

Mọi hạng mục phải đồng thời hướng tới website nhẹ và phản hồi nhanh nhưng không được đánh đổi tính năng, giao diện, dữ liệu hoặc độ ổn định. Tối ưu chỉ được áp dụng sau khi có baseline, xác minh phạm vi ảnh hưởng và regression tương ứng.

## 1. Giao diện và thương hiệu

- [ ] Kiểm tra trang chủ, danh mục, chi tiết sản phẩm, giải pháp, dự án, tin tức và liên hệ trên desktop/mobile.
- [ ] Kiểm tra header, menu, breadcrumb, CTA, footer, popup và trạng thái rỗng.
- [x] Chuẩn hóa mọi wordmark “Phúc Thịnh Solar” trong template public sang vàng kim gradient, có màu fallback.
- [x] Khóa lưới Tin tức, Giải pháp và Dự án theo 3/2/1 cột; card đơn không kéo giãn toàn hàng.
- [ ] Kiểm tra ảnh, font, icon, khoảng cách, tràn ngang và console trình duyệt.
- [ ] Kiểm tra landing page sản phẩm chạy quảng cáo sau khi có danh sách sản phẩm được duyệt.

## 2. Tính năng cốt lõi

- [x] Smoke danh mục → sản phẩm → CTA báo giá trong chế độ ẩn giá trên local.
- [x] Xác nhận không lộ giá, Offer schema hoặc thao tác mua hàng khi `PUBLIC_PRODUCT_PRICES=false`.
- [x] Regression local đạt cho tìm kiếm/bộ lọc, phân trang, CSRF và rate limit của form public.
- [x] Regression local đạt cho đăng nhập Admin, sản phẩm, Excel, quyền API và đơn hàng đại diện.
- [ ] Kiểm tra email SMTP và reCAPTCHA trên staging bằng thông tin thật.

## 3. Bảo mật

- [x] CSRF, rate limit, upload validation, session hardening và RBAC regression đạt trên local.
- [x] Canonical, noindex trang nội bộ, robots và sitemap đạt trên local.
- [x] Crawl 141 URL sitemap; loại 4 URL 404 do danh mục ẩn/trang tuyển dụng chưa hoạt động và thêm regression chống tái phát.
- [x] Chặn file nhạy cảm và bổ sung HTTP security headers trên local.
- [ ] Retest tài khoản và phân quyền nghiệp vụ trên staging trước khi bật RBAC.
- [ ] Xác minh HTTPS, cookie Secure, proxy chain, HSTS và header trên hosting.
- [ ] Retest video upload thật và toàn bộ endpoint legacy trên staging.

## 4. Dữ liệu

- [x] Import/export Excel có preview, xác nhận, conflict detection và rollback trong lượt xử lý.
- [x] Chế độ báo giá chỉ ẩn giá public; giá nội bộ trong Admin/Excel được giữ nguyên.
- [ ] Nhận file Excel thật, lưu checksum và chỉ preview lần đầu.
- [ ] Đối chiếu sản phẩm quảng cáo: mã, slug, danh mục, trạng thái, thông số, ảnh và nội dung.
- [x] Local không còn dữ liệu test, mã/slug trùng, ảnh đại diện thiếu hoặc liên kết danh mục mồ côi.
- [ ] Bổ sung 5 chính sách, 78 mô tả ngắn, 6 nội dung chi tiết và 16 bộ thông số còn thiếu.
- [ ] Xác nhận không còn dữ liệu test, mã trùng hoặc import dở dang trên staging.

## 5. Backup, deploy và rollback

- [x] Kiểm tra script backup không ghi file vào webroot và không ghi credential vào metadata.
- [x] Tạo backup local kiểm thử gồm database, upload và metadata checksum.
- [x] Diễn tập restore vào database/thư mục tạm độc lập và đối chiếu checksum: 49 bảng, 775 file upload.
- [x] Có công cụ tạo release từ commit Git, danh sách file, checksum, metadata và hướng dẫn rollback; commit deploy cuối cùng chỉ chốt sát thời điểm bàn giao.
- [ ] Xác minh document root, PHP extension, quyền ghi, upload limit và rewrite trên staging.
- [ ] Chỉ cập nhật production sau khi toàn bộ P0 đạt và chủ project phê duyệt.

## 6. Hiệu năng và dung lượng

- [x] Đo baseline local ba lượt cho bảy trang public trọng yếu; TTFB warm 20–32 ms, HTML khoảng 60–161 KB. Hosting vẫn phải đo lại riêng.
- [ ] Rà CSS/JS/ảnh/video legacy, xác minh tham chiếu trước khi loại hoặc tách khỏi gói release.
- [x] Audit 137 trang, source và SQL: tách `assets/wp-content/uploads` không được tham chiếu khỏi archive; gói thử giảm khoảng 183 MB xuống 49 MB, không xóa source.
- [x] Inventory 76 CSS/JS local không thiếu file; xác nhận gzip/cache local và thêm regression header static asset.
- [x] Conditional loading đợt 1: trang chủ giảm 9 request CSS/JS và khoảng 347 KB trước gzip; giữ đủ asset cho sản phẩm/dự án và pre-deploy đạt.
- [x] Conditional loading đợt 2: giới hạn Jssor/Fancybox; trang chủ giảm lũy kế 12 request và khoảng 428 KB, pre-deploy đạt.
- [ ] Kiểm tra cache/compression ảnh và static asset trên staging/hosting.
- [ ] Chạy lại toàn bộ regression chức năng và visual sau mỗi đợt tối ưu.

## Điều kiện kết luận

- **Sẵn sàng local:** toàn bộ test tự động và smoke local đạt, không có log lỗi, dữ liệu test được dọn sạch.
- **Sẵn sàng staging:** tích hợp thật, phân quyền, HTTPS, upload và restore đạt trên staging.
- **Sẵn sàng production:** có backup phục hồi được, release/rollback rõ ràng và phê duyệt triển khai.
