# Trang Tin tức — triển khai 14/09/2026

## Kết quả và phạm vi

- Triển khai local theo ảnh tham chiếu: khối nổi bật 1 lớn + 2 nhỏ, thanh danh mục/tìm kiếm, lưới 3 cột, phân trang và CTA navy/gold. Mobile chuyển bố cục, danh mục cuộn ngang.
- Dùng bài viết, danh mục, ảnh, slug và popup tư vấn hiện có; không tạo nội dung giả, không đổi URL cũ, không sửa production.
- Tối đa 3 bài nổi bật; 6 bài/lượt trong lưới. Bài nổi bật không lặp trong lưới và không làm mất bài ở trang sau. Tìm kiếm và trang danh mục bao gồm cả bài nổi bật.
- Tìm kiếm tên/mô tả/nội dung, kết hợp danh mục và giữ từ khóa khi phân trang. SQL bind tham số, ký tự `%`, `_`, `!` được xử lý như văn bản. Trang tìm kiếm có `noindex,follow`.
- Thời gian đọc tự tính từ nội dung (220 từ/phút); ảnh lỗi/thiếu có placeholder. Giữ cơ chế thumbnail/watermark hiện có.
- CTA áp dụng cả danh sách và chi tiết Tin tức. Trang chi tiết có H1; không thay giao diện trang chính sách.

## Quản trị

Vào nhóm **Trang Tin tức** trong Admin:

| Phần | Nơi sửa và hành vi |
| --- | --- |
| Tiêu đề/mô tả mở đầu | Tin tức - Mở đầu (`news-intro`): tên, tiêu đề ngắn, mô tả; cờ Hiện bài nổi bật bật/tắt khối 1+2 |
| Bài viết | Tin tức (`tin-tuc`): tên, mô tả, nội dung, ảnh, ngày, danh mục, SEO và hiển thị như trước |
| Chọn bài nổi bật | Cờ Nổi bật trang Tin tức (`newshub`), lấy tối đa 3 bài hiển thị theo STT tăng, ngày mới nhất, ID giảm |
| Trang chủ | Cờ Nổi bật trang chủ (`noibat`) độc lập, không tự thay đổi lựa chọn cũ |
| Bộ lọc | Danh mục Tin tức: tên, ảnh/icon, mô tả, thứ tự và trạng thái; chỉ hiện bộ lọc có bài công khai |
| Dải tư vấn | Tin tức - CTA (`news-cta`): nhãn, tên/tiêu đề, mô tả, chữ trên nút, ảnh nền tùy chọn và hiển thị |
| Hotline | Cài đặt website hiện có; nút tư vấn mở popup liên hệ hiện có |

Mở đầu/CTA dùng bản ghi hiển thị có STT nhỏ nhất, ID mới nhất nếu trùng. Chưa có bản ghi thì dùng nội dung mặc định; đã có nhưng ẩn toàn bộ thì ẩn khối, không tự bật fallback. Khi thêm bản ghi mở đầu, chọn **Hiện bài nổi bật** nếu muốn giữ khối 1+2. H1 vẫn tồn tại cho accessibility khi ẩn phần mở đầu.

Chưa chọn cờ `newshub` nào thì dùng bài hiển thị theo STT/ngày làm fallback. Nếu đã chọn nhưng ẩn các bài đó, không thay bằng bài khác. Không dựng danh mục giả theo mockup: muốn Kiến thức/Sản phẩm/Hoạt động/Tuyển dụng thì quản trị danh mục và gán bài thật. Không đổi slug đã xuất bản chỉ để đổi nhãn.

## File liên quan

Gốc web: `domains/phucthinhsolar.com/public_html/`.

- Mới: `sources/news_hub.php`, `templates/news/news_card_tpl.php`, `templates/news/news_cta_tpl.php`, `assets/css/news-hub.css`.
- Tích hợp: `sources/news.php`, `templates/news/news_tpl.php`, `templates/news/news_detail_tpl.php`, `templates/layout/css.php`, `templates/layout/head.php`, `templates/layout/seo.php`, `libraries/config-type.php`, `libraries/type/config-type-news.php`.
- Quyền: `migrations/038_grant_news_hub_permissions.php`.
- Kiểm thử: `scripts/test-news-hub.php`, `scripts/test-admin-readiness.php`, `scripts/predeploy-local.sh`, `tools/visual-tests/tests/news-hub.spec.js`.
- Các file Project Hub và thay đổi đang có trước trong worktree được giữ nguyên; các file config/test dùng chung chứa cả hai đợt thay đổi.

## Kiểm thử

- PHP 7.4 lint các file PHP thay đổi: PASS.
- Bộ test PHP News Hub: PASS (14 bài fixture, phân trang không trùng/mất, tìm kiếm, escaping, thời gian đọc, fallback tiếng Việt, trạng thái CMS). Fixture tự xóa theo đúng ID sau test.
- `./scripts/predeploy-local.sh`: PASS, gồm lưu/ẩn block qua Admin, chọn/ẩn bài nổi bật, từ chối mutation thiếu CSRF, phân quyền, hồi quy commerce hiện có, smoke public, log và diễn tập phục hồi backup.
- Migration 038 đã áp dụng **local**: thêm 8 quyền cho mỗi nhóm đủ bốn quyền gốc Tin tức (2 nhóm), bỏ qua nhóm hẹp. Preview chạy lại không còn quyền cần thêm.
- Playwright riêng News Hub trước đó: PASS 2/2 (desktop + mobile).
- Lượt hồi quy kết hợp cuối (`news-hub`, `public-pages`, `project-hub`): 19/20 ca đạt ngay; 1 ca News Hub mobile báo lỗi console ngắn `La` và đạt khi retry. Tổng kết Playwright: 19 passed, 1 flaky, exit 0.
- **Cập nhật 14/09/2026 (Claude)**: điều tra lại flake `La` — chạy riêng `news-hub.spec.js --project=mobile-chromium` 3 lần và bộ kết hợp `news-hub + public-pages + project-hub` (đúng lệnh đã dùng) thêm 3 lần, tổng 6 lượt, **không tái hiện được**, toàn bộ `pageerror` rỗng ở mọi lượt. Không sửa/assertion vì chưa chứng minh được nguyên nhân (đúng nguyên tắc AGENTS.md "tránh nới lỏng assertion chỉ để làm xanh test"). Kết luận: nhiều khả năng là nhiễu tạm thời từ script legacy bên thứ 3 (thông điệp lỗi 2 ký tự kiểu biến đã minify), không phải lỗi của News Hub. Nếu tái xuất hiện, ưu tiên bắt trace/video ngay lúc đó thay vì suy đoán thêm.
- Chạy lại `scripts/test-news-hub.php` (qua `docker cp` + `docker exec`) độc lập: PASS toàn bộ 26 assertion.
- Chạy lại `LOCAL_ENFORCE_RBAC=1 ./scripts/predeploy-local.sh` toàn bộ: PASS sạch ("Pre-deploy local hoàn tất; regression, smoke, log và cleanup đều sạch"), bao gồm preflight quyền Trang Tin tức xác nhận không còn quyền nào cần bổ sung (migration 038 vẫn đang áp dụng đúng).
- `git diff --check`: PASS.

## Deploy và rollback

Chưa deploy production. Trước deploy cần backup release + DB, chuyển các file web trong danh sách cùng dependency của release hiện tại, chạy PHP 7.4 lint và các kiểm tra predeploy. Không upload scripts kiểm thử vào public webroot.

Migration không đổi schema hay nội dung bài viết. Trên môi trường có cấu trúc repo tương ứng, chạy CLI `php migrations/038_grant_news_hub_permissions.php` để xem trước; chỉ thêm `--apply` sau khi duyệt danh sách nhóm/quyền. Nếu hosting đặt mã nguồn khác cấu trúc repo, cần bố trí đường dẫn bootstrap config phù hợp trước khi chạy; không đưa migration vào URL public. Chạy lại preview phải không còn quyền cần bổ sung. Kiểm tra bằng tài khoản Admin được phân quyền, cả Tin tức, danh mục, chi tiết và popup tư vấn.

Rollback giao diện bằng release backup trước deploy, không dùng git reset trên worktree đang có công việc khác. Các bản ghi `news-intro`, `news-cta` và cờ `newshub` có thể giữ lại vì code cũ không sử dụng. Nếu cần thu hồi quyền, chỉ thu hồi các quyền mới đã ghi nhận trong dry-run/apply, không phục hồi đè toàn bộ DB vì có thể mất đơn hàng/nội dung mới.

## Lưu ý còn lại

- Ảnh bài cũ có chữ/tỷ lệ không đồng nhất nên chưa thể giống hoàn toàn bộ ảnh mockup. Nên thay ảnh bìa ngang chất lượng cao trong Admin; không tự thay ảnh/nội dung thật.
- Bộ AntiSQLInjection legacy có thể trả `404 Not found` cho chuỗi giống mã script trước khi tới tìm kiếm; giữ nguyên lớp bảo vệ này.
- SMTP local chưa cấu hình. Kiểm thử xác nhận popup và luồng xử lý hiện có, không khẳng định thư thực tế đã đến hộp thư; cần thử email trên môi trường triển khai đã cấu hình SMTP.
