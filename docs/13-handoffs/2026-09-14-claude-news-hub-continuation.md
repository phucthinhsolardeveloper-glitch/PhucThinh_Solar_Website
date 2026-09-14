# Bàn giao cho Claude — tiếp tục News Hub

Thời điểm dừng: 14/09/2026. Workspace: `/home/admin01/Web_PTS_PHP`.

## Mục tiêu người dùng

Hoàn thiện trang Tin tức sát mockup đã cung cấp, đồng bộ ngôn ngữ thiết kế navy/gold của các trang mới, giữ mọi chức năng public hiện có và cho phép quản trị nội dung/phần mới trong Admin. Chỉ làm local cho tới khi chủ project cho phép deploy production.

## Trạng thái hiện tại

- Implementation News Hub đã hoàn thành ở local: featured 1+2, bộ lọc danh mục, tìm kiếm, card 3 cột, phân trang 6 bài, CTA, responsive và tích hợp trang chi tiết.
- Admin đã có `news-intro`, `news-cta`, cờ bài viết `newshub`, mô tả/icon danh mục và nhóm menu Trang Tin tức.
- Tìm kiếm dùng query bind, escape wildcard; trang kết quả tìm kiếm đặt `noindex,follow`.
- Không đổi slug/URL public, dữ liệu bài cũ, ảnh cũ hoặc cờ nổi bật trang chủ `noibat`.
- Migration quyền 038 đã áp dụng trên **DB local**: nhóm #1 và #3 mỗi nhóm được thêm 8 quyền; nhóm #2 bị bỏ qua đúng chủ đích. Dry-run sau đó báo không còn quyền cần thêm.
- Chưa deploy production, chưa commit. Worktree còn nhiều thay đổi của các đợt About/Solution/Project Hub; tuyệt đối không reset hoặc ghi đè chúng.

Đọc báo cáo đầy đủ trước khi sửa: `docs/13-handoffs/2026-09-14-news-hub-redesign-report.md`.

## File News Hub chính

- `domains/phucthinhsolar.com/public_html/sources/news_hub.php`
- `domains/phucthinhsolar.com/public_html/templates/news/news_tpl.php`
- `domains/phucthinhsolar.com/public_html/templates/news/news_card_tpl.php`
- `domains/phucthinhsolar.com/public_html/templates/news/news_cta_tpl.php`
- `domains/phucthinhsolar.com/public_html/assets/css/news-hub.css`
- `domains/phucthinhsolar.com/public_html/libraries/type/config-type-news.php`
- `domains/phucthinhsolar.com/public_html/libraries/config-type.php`
- `domains/phucthinhsolar.com/public_html/sources/news.php`
- `domains/phucthinhsolar.com/public_html/templates/news/news_detail_tpl.php`
- `domains/phucthinhsolar.com/public_html/templates/layout/css.php`
- `domains/phucthinhsolar.com/public_html/templates/layout/head.php`
- `domains/phucthinhsolar.com/public_html/templates/layout/seo.php`
- `migrations/038_grant_news_hub_permissions.php`
- `scripts/test-news-hub.php`
- `tools/visual-tests/tests/news-hub.spec.js`

Các file dùng chung `scripts/predeploy-local.sh` và `scripts/test-admin-readiness.php` chứa cả thay đổi News Hub lẫn công việc trước đó; chỉnh theo diff nhỏ, không thay nguyên file.

## Kiểm thử đã đạt

- `./scripts/predeploy-local.sh`: PASS toàn bộ, gồm PHP 7.4 lint, Admin save/hide, CSRF, phân quyền, News/Project Hub fixtures, commerce regressions, public smoke, log và restore rehearsal.
- Test PHP News Hub: PASS; fixture tự xóa theo ID.
- Playwright News Hub chạy riêng: PASS desktop + mobile.
- Lượt kết hợp `news-hub.spec.js public-pages.spec.js project-hub.spec.js`: 19 passed, 1 flaky, exit 0. Flake duy nhất ở News Hub mobile: cuối test mảng lỗi nhận `"La"`; retry lập tức PASS. Trước đó có một lượt thất bại toàn bộ do Chromium sandbox (`sandbox_host_linux.cc ... Operation not permitted`), không phải lỗi website; chạy browser ngoài sandbox thì hoạt động.
- `git diff --check`: PASS tại thời điểm bàn giao.

## Việc nên làm tiếp theo

1. Đọc `AGENTS.md`, báo cáo News Hub và diff các file dùng chung để phân biệt thay đổi của Project Hub.
2. Chạy lại riêng mobile News Hub vài lần để xác định lỗi `La` là console chập chờn từ asset/script legacy hay lỗi mới. Xem trace/video trong `tools/visual-tests/test-results` nếu còn; lần retry PASS có thể đã làm thay đổi artifact.
3. Nếu sửa, chỉ sửa nguyên nhân đã chứng minh; tránh nới lỏng assertion chỉ để làm xanh test. Chạy lại `news-hub.spec.js`, rồi `public-pages.spec.js` và `project-hub.spec.js` vì dùng layout/config chung.
4. Kiểm tra trực quan lại desktop/mobile với dữ liệu thật. Mockup dùng ảnh sạch; dữ liệu thật hiện có nhiều ảnh chứa chữ/tỷ lệ cũ, nên không tự thay nội dung hay ảnh.
5. Cập nhật dòng test trong báo cáo nếu flake được giải quyết; chạy `git diff --check` và `./scripts/predeploy-local.sh` cuối cùng.
6. Chỉ chuẩn bị deploy sau khi chủ project yêu cầu rõ ràng. Trước production phải backup code + DB, dry-run migration 038, duyệt nhóm quyền rồi mới `--apply`.

Lệnh browser đã dùng:

```bash
cd /home/admin01/Web_PTS_PHP/tools/visual-tests
PLAYWRIGHT_BROWSERS_PATH=.browsers npx playwright test tests/news-hub.spec.js --workers=1
PLAYWRIGHT_BROWSERS_PATH=.browsers npx playwright test tests/news-hub.spec.js tests/public-pages.spec.js tests/project-hub.spec.js --workers=1
```

Nếu Chromium báo `Operation not permitted`, đó là hạn chế sandbox của môi trường agent; cần chạy với quyền thực thi browser phù hợp, không sửa application để né lỗi này.

## Rủi ro/giới hạn đã biết

- AntiSQLInjection legacy trả `404 Not found` cho truy vấn giống mã script trước khi request tới source; giữ nguyên. Test PHP đã xác minh query bind và wildcard literal ở tầng source.
- SMTP local chưa cấu hình; popup mở và handler không vỡ, nhưng phải thử gửi thật trên môi trường có SMTP.
- Không có seed intro/CTA thật. Khi chưa có record, frontend dùng mặc định. Nếu Admin tạo intro thì cần bật cờ `hero` để hiện khối featured; nếu đã có record nhưng ẩn hết thì section đó chủ ý bị ẩn.
- Migration 038 thêm permission rows, không đổi schema. Rollback UI bằng release backup; không restore đè toàn DB và không dùng `git reset --hard` trong worktree này.
