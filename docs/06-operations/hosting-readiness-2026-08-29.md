# Báo cáo sẵn sàng đưa website lên hosting — 2026-08-29

## 1. Mục tiêu và phạm vi

Tài liệu này là cổng kiểm soát trước khi đưa code và dữ liệu lên hosting production. Phạm vi hiện tại mới được xác minh trên Git và Docker local PHP 7.4; chưa có quyền triển khai, chạy migration hoặc import dữ liệu thật trên production.

Website chỉ được mở traffic/chạy quảng cáo khi toàn bộ mục **P0 — bắt buộc** đạt, có backup phục hồi được và chủ project phê duyệt rõ ràng. Không upload nguyên database local/staging đè production.

## 2. Trạng thái đã hoàn thành

- Hoàn thiện thông số sản phẩm có cấu trúc, form Admin, frontend và nhập/xuất CSV/XLS/XLSX; vẫn giữ fallback HTML legacy.
- Quy trình Excel có preview, kiểm tra ID/mã/slug/danh mục/giá, phát hiện xung đột, checkbox xác nhận đã kiểm tra, transaction rollback và cleanup dữ liệu thử.
- Không chạy migration 006 apply; dữ liệu chuẩn sẽ nhận từ file Excel bên ngoài.
- Parameterized các truy vấn quan trọng đã phát hiện trong filter public, comment và quản trị đơn hàng.
- Bổ sung CSRF cho checkout, review, đơn hàng và toàn bộ 42 form POST Admin.
- Chuyển mutation Admin legacy `save/delete/upload/sync/create` sang POST + CSRF; bảo vệ API comment, sortable, upload và gallery.
- API Admin chính có allowlist bảng/identifier; API mutation chính đã có mapping quyền action khi RBAC được bật.
- Upload kiểm tra extension, MIME/nội dung thật, dung lượng; thư mục upload chặn thực thi script.
- API bảo trì database chỉ nhận `ANALYZE`, `OPTIMIZE`, `CHECK`, `REPAIR`, có CSRF và yêu cầu quyền cấu hình khi RBAC bật.
- File manager và API legacy không còn call site đã bị chặn HTTP 404 nhưng chưa xóa code để hỗ trợ rollback/đối chiếu.
- Build Docker sạch, PHP 7.4 lint, JavaScript check, security regression, Excel regression và frontend smoke đều đạt; dữ liệu sản phẩm thử còn lại bằng 0.
- Hoàn thành test local ma trận RBAC tạm cho nhóm sản phẩm, đơn hàng, nội dung và chỉ xem; test từ chối quyền chéo module và tự dọn tài khoản/nhóm test.
- Login Admin đã có POST + CSRF, thông báo lỗi chung, rate limit DB theo IP và hash tài khoản, trusted-proxy allowlist, session ID regeneration và cookie `HttpOnly`/`SameSite=Lax`.
- Retest tự động xác nhận 42/42 form POST Admin có CSRF; request thiếu token trả 403, token hợp lệ đi qua lớp bảo vệ và form authenticated render token.

## 3. Điểm chưa hoàn thành và rủi ro còn lại

- RBAC vẫn đang tắt. Ma trận bằng tài khoản tạm đã đạt trên local nhưng chưa được lặp lại với tài khoản/quy trình thật trên staging.
- Rate limit checkout, review, contact và newsletter đã dùng bộ đếm DB theo IP/bucket; cần xác minh proxy chain và ngưỡng thực tế trên staging.
- Đã kiểm tự động cấu trúc 42 form và luồng HTTP đại diện; chưa retest thao tác nghiệp vụ đầy đủ bằng trình duyệt trên staging.
- Chưa xác minh cấu hình hosting thật: PHP extension, rewrite/`.htaccess`, quyền thư mục, giới hạn upload, cron, timezone và proxy/header.
- Chưa xác minh SMTP production và reCAPTCHA bằng thông tin thật.
- Chưa nhận/preview/import file Excel dữ liệu thật trên local hoặc staging.
- Nội dung chính sách, ảnh/video/chứng nhận và nhóm sản phẩm chạy quảng cáo chưa được chủ project duyệt hoàn chỉnh.
- Chưa có backup production kèm checksum và chưa diễn tập restore trên staging.
- PHP 7.4, password hash và một số dependency vẫn là legacy; đây là hạng mục nâng cấp sau khi có baseline production ổn định, trừ khi retest phát hiện lỗi chặn deploy.

## 4. Checklist ưu tiên trước khi đưa lên host

### P0 — Bắt buộc, thiếu một mục thì không mở production

#### A. Staging và môi trường hosting

- [ ] Tạo staging subdomain và database riêng; không dùng database production để thử nghiệm.
- [ ] Xác minh document root là `domains/phucthinhsolar.com/public_html` hoặc cập nhật kế hoạch theo đường dẫn thật.
- [ ] Xác minh PHP đúng phiên bản mục tiêu và đủ extension: PDO MySQL, mysqli, mbstring, GD, ZIP, fileinfo, exif và `calendar`.
- [ ] Xác minh MariaDB charset/collation, timezone và `sql_mode` tương thích.
- [ ] Xác minh Apache rewrite, `.htaccess`, HTTPS, redirect HTTP → HTTPS và header/proxy chain.
- [ ] Xác minh giới hạn `upload_max_filesize`, `post_max_size`, `max_execution_time`, `memory_limit` phù hợp import Excel/video.
- [ ] Xác minh quyền ghi chỉ cho cache/upload/thumb cần thiết; upload không thực thi PHP/script.
- [ ] Kiểm tra dump, archive, log và file backup không truy cập được từ web.

#### B. Code, cấu hình và secret

- [ ] Chốt commit/release sẽ deploy và lưu `git log`/checksum của gói bàn giao.
- [ ] So sánh cấu hình production với staging, không copy credential local lên host.
- [ ] Kiểm tra base URL, SSL, database, SMTP, reCAPTCHA và OneSignal bằng biến/cấu hình production.
- [ ] Không ghi credential, token, password hash hoặc PII vào tài liệu triển khai.
- [ ] Build staging từ source sạch; không mang cache, file test hoặc `artifacts/` vào webroot.
- [ ] Chạy PHP lint, JavaScript check và security regression trên đúng bản staging.

#### C. Database và dữ liệu sản phẩm

- [ ] Backup database staging trước mọi migration/import; lưu checksum và vị trí ngoài webroot.
- [ ] Xác định migration nào production đã có; không chạy lại migration không idempotent.
- [ ] Không chạy migration 006 apply nếu dữ liệu Excel thật đã được chuẩn hóa và chưa có phê duyệt riêng.
- [ ] Khi nhận Excel: lưu file gốc + checksum, chỉ preview lần đầu, chưa commit.
- [ ] Rà số dòng tạo/sửa/ẩn/không đổi, lỗi, cảnh báo và xung đột.
- [ ] Đối chiếu sản phẩm mẫu: ID, mã, tên, slug, danh mục, giá, trạng thái, thông số và ảnh.
- [ ] Chỉ đánh dấu xác nhận và áp dụng trên staging sau khi preview sạch và có backup.
- [ ] Xác nhận không có mã trùng, dòng test hoặc dữ liệu import dở dang.

#### D. Security và tài khoản Admin

- [x] Lập và test tự động ma trận local: sản phẩm, đơn hàng, nội dung, chỉ xem và từ chối chéo module.
- [ ] Tạo tài khoản staging cho từng vai trò; không dùng chung tài khoản production.
- [ ] Test xem/thêm/sửa/xóa và gọi API trực tiếp cho từng vai trò.
- [ ] Chỉ bật `permission.active` trên staging sau khi ma trận quyền đạt; chưa bật thẳng production.
- [x] Test local CSRF: đủ 42/42 form; thiếu token trả 403, token hợp lệ đi qua lớp bảo vệ.
- [x] Contact và hai form newsletter public phát CSRF; HTTP test xác nhận thiếu token không ghi dữ liệu.
- [ ] Test session hết hạn, đăng nhập lại, hai tab Admin và hai thiết bị.
- [ ] Xác minh trên HTTPS staging cookie Admin có `Secure`; local đã đạt `HttpOnly`, `SameSite=Lax` và đổi session ID sau đăng nhập.
- [x] Bổ sung rate limit login bền vững theo IP và hash tài khoản; không tin forwarded IP ngoài trusted proxy.
- [x] Bổ sung rate limit DB theo IP, tách bucket cho checkout, review, contact và hai loại newsletter; mặc định 5 lượt/10 phút.
- [x] Retest local upload ảnh/PDF, sai MIME, PHP giả PDF/tên kép và vượt dung lượng; file test được dọn sạch.
- [ ] Retest video MP4/MKV dung lượng thực qua UI staging theo giới hạn hosting.
- [ ] Xác nhận endpoint legacy đã khóa vẫn trả 404 trên staging/production.

#### E. Nội dung và tích hợp

- [ ] Chủ project duyệt 5 trang chính sách và thông tin doanh nghiệp/hotline/địa chỉ.
- [ ] Duyệt ảnh, video, mô tả, giá và chứng nhận của sản phẩm chạy quảng cáo.
- [x] Chế độ hiện tại ẩn toàn bộ giá public và hiển thị “Liên hệ”; giá Admin/Excel vẫn được giữ nội bộ.
- [ ] Chỉ chọn “Hiển thị giá” ở Admin → Thiết lập thông tin (khóa `public_product_prices`) khi công ty duyệt chính sách niêm yết và đã retest cart/checkout.
- [ ] Xác minh SMTP production bằng mailbox kiểm thử; kiểm tra email đơn hàng/liên hệ.
- [ ] Xác minh reCAPTCHA thật và hành vi khi CAPTCHA lỗi/hết hạn.
- [ ] Xác minh OneSignal nếu production tiếp tục sử dụng; không gửi thử đến tập khách hàng thật.

#### F. Smoke test bắt buộc

- [ ] Commerce: danh mục → sản phẩm → giỏ hàng → checkout → confirmation → Admin đơn hàng.
- [x] Local smoke xác nhận sản phẩm có giá nội bộ vẫn chỉ hiện “Liên hệ”, không xuất Offer schema/nút mua và API cart trả 409.
- [ ] Admin: tạo/sửa/xóa sản phẩm, danh mục, bài viết, gallery và trạng thái đơn.
- [ ] Form: contact, newsletter, review và email bằng dữ liệu test có kiểm soát.
- [ ] Kiểm tra desktop/mobile, menu, tìm kiếm, filter, console trình duyệt và log PHP/Apache.
- [ ] Dọn toàn bộ đơn hàng, liên hệ, review, subscriber và sản phẩm test sau kiểm tra.

#### G. Backup, cutover và rollback

- [ ] Backup code, database và upload production ngay trước cutover.
- [ ] Ghi owner, thời điểm, checksum và vị trí backup ngoài webroot.
- [ ] Diễn tập restore bản backup gần nhất trên staging và ghi kết quả.
- [ ] Chuẩn bị danh sách file/schema/data thay đổi và lệnh rollback tương ứng.
- [ ] Chốt maintenance window, người triển khai, người duyệt và người quyết định rollback.
- [ ] Chỉ deploy khi chủ project phê duyệt rõ ràng.
- [ ] Sau deploy: clear cache có mục tiêu, smoke P0, theo dõi log/error và giữ đội rollback trực trong cửa sổ giám sát.

## 5. P1 — Nên hoàn thành trước khi chạy quảng cáo

- [x] Rate limit login/form public dùng DB chung, chỉ lưu khóa băm theo form/IP và bộ đếm, không lưu nội dung form.
- [ ] Retest toàn bộ Security Review và đóng/mở lại finding theo bằng chứng staging.
- [ ] Xác nhận quy trình xử lý đơn hàng và menu cho nhân viên vận hành.
- [ ] Kiểm tra SEO: canonical, sitemap, robots, metadata, redirect và URL/slug cũ.
- [ ] Kiểm tra hiệu năng trang đích, ảnh, cache và Core Web Vitals trên mobile.
- [ ] Thiết lập giám sát uptime, lỗi PHP/Apache, dung lượng đĩa và backup định kỳ.

## 6. P2 — Nâng cấp sau khi production ổn định

- [ ] Nâng PHP và dependency theo từng giai đoạn có regression baseline.
- [ ] Migration password hash legacy sang `password_hash` bằng quy trình tương thích.
- [ ] Thêm CI: PHP lint, JavaScript check, security regression và commerce smoke.
- [ ] Thêm audit trail thay đổi trạng thái đơn hàng và mutation quan trọng.
- [ ] Tinh chỉnh Theme V2 bằng đối chiếu trực quan desktop/mobile.
- [ ] Lập mapping usage trước khi archive route, media, API hoặc asset legacy.

## 7. Điều kiện Go/No-Go

**GO** khi toàn bộ P0 đã đánh dấu đạt, staging smoke sạch, backup đã restore thử thành công và chủ project duyệt release.

**NO-GO** nếu còn một trong các tình trạng: chưa có backup phục hồi được; import/migration chưa preview; lỗi checkout/confirmation/Admin order; upload có thể thực thi script; SMTP/reCAPTCHA chưa xác minh; log có fatal; quyền tài khoản chưa rõ; hoặc chưa có kế hoạch rollback.

## 8. Rollback tối thiểu

- Code: quay về commit/release trước đó và triển khai lại đúng gói đã lưu checksum.
- Database: restore backup trước cutover; không tự drop cột hoặc chạy SQL đảo ngược nếu chưa xác minh dữ liệu phát sinh.
- Upload: phục hồi snapshot upload tương ứng với database.
- Import Excel: khôi phục DB trước import hoặc dùng báo cáo/backup của batch; revert code không tự hoàn tác dữ liệu.
- Sau rollback: clear cache có mục tiêu, chạy lại smoke commerce và xác nhận log sạch trước khi mở traffic.
