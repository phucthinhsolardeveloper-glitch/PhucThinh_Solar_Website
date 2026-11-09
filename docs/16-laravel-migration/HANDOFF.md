# Bàn giao nhánh Laravel (cập nhật 2026-11-09)

Đọc file này, rồi [`STATUS.md`](STATUS.md). Mục đã CHỐT trong STATUS không đọc lại trừ khi có lỗi mới.
Chọn model theo [`../12-ai/model-routing.md`](../12-ai/model-routing.md).

## Vị trí

| | |
|---|---|
| Worktree | `/home/admin01/src/pts-laravel13` |
| Nhánh | `migration/laravel13-integration`, đi trước `master` 117 commit, **chưa push** |
| Hệ thống | Nuxt 4 (`frontend/`), Laravel 13 + Filament (`backend/`, Admin ở `/admin`) |
| Thay thế | Toàn bộ backend PHP; cây PHP cũ đã xóa ở nhánh này, vẫn còn ở `master` để đối chiếu nghiệp vụ |
| Mã PHP cũ | `/home/admin01/src/Web_PTS_PHP/domains/phucthinhsolar.com/public_html` |

## Quy tắc của chủ website

- Commit local, **không push** cho tới khi chủ website xác nhận hoàn thiện. **Không đụng production, không deploy.**
- Báo cáo tiếng Việt, ngắn, dễ hiểu với người không làm kỹ thuật.
- Không gửi lead/đơn thật khi test. Không tự bịa dữ liệu. Không đổi mật khẩu thật. Mật khẩu mail chỉ nằm trong `.env`.
- Số đại lý, đánh giá, chứng nhận do chủ website đưa là đúng, không hỏi lại. Công ty: thành lập 2020, sáng lập Mai Anh Tuấn, chỉ năng lượng mặt trời.
- Giao diện: không có nhãn eyebrow/kicker trang trí, không dòng tự phủ nhận hay “prototype”; đầy đủ dữ kiện hơn ngắn gọn; form vừa một màn, chỉ trường bắt buộc, không ghi “không bắt buộc”; mobile bo nhẹ, không viên thuốc; ô đang chọn vàng kim gradient.
- Script đo để ở `tools/visual-tests/audit`, không dọn; thêm script thì thêm dòng vào README cùng thư mục.
- Làm xong phần nào: test → commit → ghi vào STATUS → dọn file tạm. Kết quả Codex ghi vào commit message.
- Không dựng Docker image song song (máy 7,7 GB RAM).
- Mọi chức năng phải có đủ nghiệp vụ của bản PHP và tốt hơn. Trước khi nói “xong” một module, đọc nguồn PHP tương ứng và đối chiếu.

## Chạy local

Container: `pts-laravel13-local` (Laravel, cổng 8090, mã nguồn mount vào `/app`) và `pts-mariadb-local`.
DB: `pts_app` (dev), `pts_app_test` (test), `pts_laravel13_clone` (bản sao dữ liệu PHP, connection `legacy`).

```bash
D=/mnt/c/Users/ADMIN/AppData/Local/Programs/DockerDesktop/resources/bin/docker.exe
$D exec pts-laravel13-local sh -c 'cd /app && vendor/bin/pint -q; vendor/bin/phpstan analyse --memory-limit=1G --no-progress --error-format=raw; php artisan test --compact; php artisan pts:snapshot | grep -c identical'
$D exec pts-laravel13-local sh -c 'cd /app && chown -R 1000:1000 app tests resources routes database'   # sau khi container tạo file
$D exec pts-laravel13-local sh -c 'cd /app && php artisan migrate --force; DB_DATABASE=pts_app_test php artisan migrate --force'           # có migration mới
```

- Nếu `docker.exe` báo `UtilAcceptVsock ... accept4 failed 110`: socket WSL của phiên đã chết. Thử `for s in $(ls -t /run/WSL/*_interop); do WSL_INTEROP=$s timeout 8 /mnt/c/Windows/System32/cmd.exe /c ver && break; done` rồi `export WSL_INTEROP=$s`. Không cần `wsl --shutdown`.
- Nuxt: trong `frontend/`, `npm test`, `npx nuxi typecheck`, `npm run build`, rồi `NITRO_PORT=3110 NUXT_API_ORIGIN=http://127.0.0.1:8090 node .output/server/index.mjs`. Tiến trình 3110 thuộc phiên cũ, có thể đã tắt.
- Đừng chạy `pkill -f <chuỗi>` khi chuỗi đó xuất hiện trong chính lệnh: shell tự tắt (mã 144).
- Mốc so sánh API: `backend/resources/api-snapshots` (có trong git). `storage/app/snapshots` là bản cũ, bỏ qua. Đổi API có chủ đích thì `pts:snapshot <api> --save`.
- Tài khoản Admin để test: tạo tạm bằng tinker (`username` = `kiemtra-local`, `role` = `owner`, mật khẩu ngẫu nhiên), xóa sau khi xong. Hiện có đúng 2 tài khoản thật: `admin`, `quoccuong123`.
- Logo đối tác: `php artisan pts:brand-logos` đưa 20 logo trong `backend/resources/brand-logos` vào kho upload và gắn vào thương hiệu (giữ logo đã chọn trong Admin, trừ khi `--force`). Tự chạy trong `pts:import-legacy`. DB dev chưa chạy vì `/srv/upload` mount chỉ đọc, nên trang Giới thiệu local chỉ hiện 3 logo.
- Bản ghi trang Đại lý (`page_items` 2167, `dealer.intro`) đã bị tài khoản `admin` xóa trên DB dev lúc 11:55 ngày 09/11; snapshot `dealer` vì thế lệch, chưa lưu đè.
- Chuyển khoản: chủ website khai ngân hàng, số tài khoản, tên chủ tài khoản ở Admin → Thông tin website. Chưa khai thì checkout không hiện Chuyển khoản. Thử đặt đơn trên Nuxt local: chạy Nuxt với `NUXT_LOCAL_API_PROXY=1` để `/api/v1/*` chuyển sang Laravel (production có Caddy làm việc này, không bật biến này).
- Nhất Tín: mặc định `NTL_MODE=mock`, không gọi ra ngoài. Admin → Thông tin website → Kho gửi hàng phải khai phường/xã gửi. Lên sandbox: điền `NTL_USERNAME/PASSWORD/PARTNER_ID`, đối chiếu mã phường với danh mục Nhất Tín rồi bật `NTL_LOCATION_CODES_VERIFIED=1`; tạo/hủy vận đơn cần thêm `NTL_WRITE_OPERATIONS_ENABLED=1`. Production cần `NTL_PRODUCTION_UNLOCKED=1`. Webhook: `POST /api/integrations/ntl/webhook`, header `Authorization` = `NTL_WEBHOOK_TOKEN`.
- Dữ liệu mẫu: `php artisan pts:demo-data`, xóa bằng `--remove`. Hiện đã xóa. Dữ liệu mẫu có đánh giá đã duyệt, phải xóa trước khi chạy `pts:snapshot`.
- Audit: trong `tools/visual-tests`: `AUDIT_BASE=http://127.0.0.1:3110 node audit/site-audit.cjs`, `audit/shop-flow.cjs`, `DEEP=1 ADMIN_USER=… ADMIN_PASS=… node audit/admin-screens.cjs <thư mục>`. Bộ `visual-tests` cũ nhắm vào PHP nên đỏ trên Nuxt, không dùng làm cổng phát hành.

## Bằng chứng lần chạy cuối (HEAD trước commit tài liệu: `c661d78b`)

- Backend: 180 test qua, 1 bỏ qua; phpstan sạch; `pts:snapshot` 27/27 khớp.
- Admin: 83 màn và hộp thoại, 0 lỗi (`DEEP=1`).
- Frontend: 479 test qua lần chạy gần nhất ở `cdfccdda`; từ đó không đổi file frontend nào.

## Chưa được thử thật

- Nhập Excel: xem trước đã thử bằng tệp thật 399 sản phẩm qua trình duyệt; bước áp dụng mới có test tự động, chưa áp dụng tệp lớn trên dữ liệu dev.
- ZIP ảnh, feed Google, Excel và phiếu in đơn hàng, kéo-thả thứ tự: có test hoặc mở màn, chưa bấm thử hết qua trình duyệt.
- Feed: 185 sản phẩm có `feed_image`, 180 trùng đúng ảnh chính; 5 sản phẩm (321, 506, 507, 595, 596) trỏ tới tệp không có ở local. Chủ website cần xác nhận các ảnh đó thật sự sạch trước khi gửi Google.
- Cổng trước khi chuyển production: staging VPS, VNPay sandbox, mail thật, thử khôi phục/rollback. Outbox và hàng đợi đã xong (`bff35f79`).

## Cấu trúc đáng biết

| Việc | Vị trí |
|---|---|
| Nhập/xuất sản phẩm | `app/Domain/Catalog/Bulk/*`, màn `app/Filament/Pages/ProductBulkPage.php` |
| Thao tác hàng loạt | `app/Filament/Resources/Products/ProductBulkActions.php`, `app/Domain/Catalog/ProductBulkUpdate.php` |
| Feed Google | `app/Domain/Catalog/MerchantFeed.php` |
| Bản nháp và lịch sử | `app/Domain/Content/PostRevisions.php` |
| Slug, chuyển hướng 301 | `app/Support/Slugs.php`, `app/Domain/Routing/Redirects.php`, `frontend/server/plugins/moved-pages.ts` |
| Kho media | `app/Domain/Media/*`, lệnh `pts:media` |
| Email hàng đợi | `app/Domain/Outbox/Outbox.php`, lệnh `pts:outbox` |
| Vòng đời đơn, nhật ký | `app/Domain/Commerce/OrderLifecycle.php`, `app/Domain/Audit/Audit.php` |
| Xuất đơn hàng | `app/Domain/Commerce/OrderExporter.php` |
| Nhập dữ liệu từ PHP | `app/Console/Commands/ImportLegacyCommand.php` (`pts:import-legacy`) |
| Cấu hình toàn Admin | `app/Providers/AppServiceProvider.php`, `app/Providers/Filament/AdminPanelProvider.php` |

## Việc tiếp theo

Theo thứ tự trong `STATUS.md`, mục “Chưa làm”. Task 5 gồm: `Page`, `PageRevision`, `PageSection` có kiểu; registry section; Nuxt render theo type + variant + props; thử trên Home và About với sắp xếp, ẩn hiện, variant, nháp, xem trước, xuất bản, rollback; giữ tương thích `PageItems`.
