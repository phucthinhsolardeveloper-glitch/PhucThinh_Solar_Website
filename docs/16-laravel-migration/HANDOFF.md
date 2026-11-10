# Bàn giao nhánh Laravel (cập nhật sau deploy 2026-11-10)

## Trạng thái hiện tại

- Repo: `/home/admin01/src/Web_PTS_PHP`, branch `master`, commit `d2c529e0`;
  đã push `origin/master` và deploy production.
- Production images: `pts-laravel13-backend:d2c529e0-candidate` và
  `pts-laravel13-nuxt:d2c529e0-candidate`. Migrations `001700`–`001900` đã chạy;
  68 đèn sân vườn đã vào bốn danh mục con. Xem
  [`deployment-log.md`](../06-operations/deployment-log.md).
- Backup trước deploy: `/srv/pts-laravel13/backups/20261110T085016Z-full`;
  rollback env và image IDs cũ ở `/srv/pts-laravel13/releases/rollback-d2c529e0/`.
- Tài khoản `admin-local` chỉ có trong DB local đã được tạo theo yêu cầu chủ
  website; mật khẩu đã gửi trực tiếp, không ghi vào tài liệu.
- Phần dưới là trạng thái tại mốc bàn giao 2026-11-09. Đường dẫn worktree và
  chỉ dẫn chưa push/chưa deploy đã cũ; dùng trạng thái và nhật ký release mới.

Đọc file này, rồi [`STATUS.md`](STATUS.md). Mục đã CHỐT trong STATUS không đọc lại trừ khi có lỗi mới.
Chọn model theo [`../12-ai/model-routing.md`](../12-ai/model-routing.md).

## Vị trí tại mốc bàn giao cũ (lịch sử)

| | |
|---|---|
| Worktree tại thời điểm bàn giao cũ | `/home/admin01/src/pts-laravel13` |
| Nhánh tại thời điểm bàn giao cũ | `migration/laravel13-integration`, đi trước `master` 117 commit |
| Hệ thống | Nuxt 4 (`frontend/`), Laravel 13 + Filament (`backend/`, Admin ở `/admin`) |
| Thay thế | Toàn bộ backend PHP; cây PHP cũ đã xóa ở nhánh này, vẫn còn ở `master` để đối chiếu nghiệp vụ |
| Mã PHP cũ | `/home/admin01/src/Web_PTS_PHP/domains/phucthinhsolar.com/public_html` |

## Quy tắc tại mốc bàn giao cũ

- Chủ website đã cập nhật chỉ đạo ngày 2026-11-10 và cho phép push/deploy release `d2c529e0`; lần cho phép đó không tự động áp dụng cho các release tương lai.
- Báo cáo tiếng Việt, ngắn, dễ hiểu với người không làm kỹ thuật.
- Không gửi lead/đơn thật khi test. Không tự bịa dữ liệu. Không đổi mật khẩu thật. Mật khẩu mail chỉ nằm trong `.env`.
- Số đại lý, đánh giá, chứng nhận do chủ website đưa là đúng, không hỏi lại. Công ty: thành lập 2020, sáng lập Mai Anh Tuấn, chỉ năng lượng mặt trời.
- Giao diện: không có nhãn eyebrow/kicker trang trí, không dòng tự phủ nhận hay “prototype”; đầy đủ dữ kiện hơn ngắn gọn; form vừa một màn, chỉ trường bắt buộc, không ghi “không bắt buộc”; mobile bo nhẹ, không viên thuốc; ô đang chọn vàng kim gradient.
- Script đo để ở `tools/visual-tests/audit`, không dọn; thêm script thì thêm dòng vào README cùng thư mục.
- Làm xong phần nào: test → commit → ghi vào STATUS → dọn file tạm. Kết quả Codex ghi vào commit message.
- Không dựng Docker image song song (máy 7,7 GB RAM).
- Mọi chức năng phải có đủ nghiệp vụ của bản PHP và tốt hơn. Trước khi nói “xong” một module, đọc nguồn PHP tương ứng và đối chiếu.

## Chạy local

**DB và ảnh của local bằng đúng production (nạp lại 2026-11-10 12:50).** `pts_app` là dump production
cùng ngày, kho ảnh đã bù đủ 255 file còn thiếu nên không còn ảnh vỡ; `pts:data-health` cho kết quả
giống hệt production. Trên nền đó có thêm dữ liệu thử của `pts:demo-data` (6 đơn, 6 đánh giá, 4 liên
hệ, 2 album, 3 video) và tài khoản `kiemtra-local`. Muốn về đúng production thì `pts:demo-data --remove`.

MariaDB local là 10.4, production là 10.11 — stack ứng viên local (`pts_laravel13_candidate-*`) mới
là bản dựng giống production, dùng nó khi cần đo đúng hành vi máy chủ.

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
- Mốc so sánh API: `backend/resources/api-snapshots` (có trong git). **Từ 2026-11-10 mốc này chụp trên
  đúng dữ liệu production** (DB dev đã nạp dump production cùng ngày), nên chênh lệch lần sau là do
  đổi mã, không phải do dữ liệu khác nhau. Chạy `pts:demo-data --remove` trước khi chụp lại. `storage/app/snapshots` là bản cũ, bỏ qua. Đổi API có chủ đích thì `pts:snapshot <api> --save`.
- Mốc cũ tạo tài khoản Admin thử `kiemtra-local`; thông tin user trong dòng này không còn là inventory hiện tại. Tài khoản local hiện dùng là `admin-local`; mật khẩu không ghi vào docs/Git.
- Logo đối tác: `php artisan pts:brand-logos` đưa 20 logo trong `backend/resources/brand-logos` vào kho upload và gắn vào thương hiệu (giữ logo đã chọn trong Admin, trừ khi `--force`). Tự chạy trong `pts:import-legacy`. DB dev chưa chạy vì `/srv/upload` mount chỉ đọc, nên trang Giới thiệu local chỉ hiện 3 logo.

- Chuyển khoản: chủ website khai ngân hàng, số tài khoản, tên chủ tài khoản ở Admin → Thông tin website. Chưa khai thì checkout không hiện Chuyển khoản. Thử đặt đơn trên Nuxt local: chạy Nuxt với `NUXT_LOCAL_API_PROXY=1` để `/api/v1/*` chuyển sang Laravel (production có Caddy làm việc này, không bật biến này).
- Nhất Tín: mặc định `NTL_MODE=mock`, không gọi ra ngoài. Admin → Thông tin website → Kho gửi hàng phải khai phường/xã gửi. Lên sandbox: điền `NTL_USERNAME/PASSWORD/PARTNER_ID`, đối chiếu mã phường với danh mục Nhất Tín rồi bật `NTL_LOCATION_CODES_VERIFIED=1`; tạo/hủy vận đơn cần thêm `NTL_WRITE_OPERATIONS_ENABLED=1`. Production cần `NTL_PRODUCTION_UNLOCKED=1`. Trên server (`APP_ENV=production`) chế độ mock tự tắt: trang thanh toán dùng bảng phí theo khu vực, Admin không tạo vận đơn MOCK. Vận đơn "Chưa rõ kết quả" (timeout khi tạo) gỡ bằng Admin → đơn hàng → Vận chuyển → Đối chiếu vận đơn chưa rõ. Webhook: `POST /api/integrations/ntl/webhook`, header `Authorization` = `NTL_WEBHOOK_TOKEN`.
- Công cụ ước tính (`/uoc-tinh-dien-mat-troi/`): mọi con số ở Admin → Bán hàng → Công cụ ước tính, chỉ `SolarEstimator` tính (Nuxt không giữ công thức). Mặc định **tắt**: trang 404, sitemap và nút trên trang Giải pháp ẩn. DB dev đang bật để xem. Lead đi qua form liên hệ với nội dung là bản tóm tắt do server tạo.
- Thông số dự án (loại hệ, kWp, kWh lưu trữ, sản lượng, tiết kiệm, ngày thi công): mục "Thông số hệ thống" trong form Dự án và bản nháp; trống thì không hiện.
- Giả lập VNPay/Nhất Tín/Mailpit cho bản thử nghiệm: `tools/sandbox-sim/README.md`. `runtime/backend.env` của ứng viên đang dùng cấu hình giả lập; bản phát hành ở `runtime/backend.env.before-sim` (cổng `verify-candidate` chạy với bản này).
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
- Cổng vận hành còn lại sau khi production đã chuyển: staging VPS riêng và diễn tập khôi phục/rollback qua Caddy. VNPay sandbox/mail thật vẫn cần mailbox và tài khoản thử; outbox/hàng đợi đã xong (`bff35f79`).

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

## Mốc 2026-11-10 (candidate `f5aac548`)

- Thông số sản phẩm có một cách viết (`App\Domain\Catalog\SpecFormat`), áp ở model saving, ô Admin (khi rời ô),
  xem trước Excel (hai phía cùng chuẩn hóa nên xuất rồi nhập lại không báo đổi), import cũ và lệnh
  `pts:normalize-specs`. **Không đổi tên dòng thông số**: trang sản phẩm ẩn/gộp dòng theo tên, đổi tên làm mất hoặc
  nhân đôi dòng (đã đo).
- Đo lường: `useDataLayer` (GA4), plugin `contact-clicks`, `lead-attribution` (localStorage 30 ngày, không cookie).
  Không đưa tên/SĐT/email vào dataLayer. GTM id ở settings `gtm_id`, chỉ render trên phucthinhsolar.com.
- Feed Merchant: `GET /google-shopping.xml` (cache 1 giờ), cùng điều kiện với CSV trong Admin.
- Chưa làm, cần chủ website quyết: Consent Mode v2 / banner đồng ý, Enhanced Conversions (băm SĐT), lưu gclid vào đơn hàng.

## Việc tiếp theo

Danh sách "Chưa làm" trong `STATUS.md` đã hết. Bố cục trang (Task 5) nằm ở `App\Domain\Pages\PageLayouts` (danh mục khối `PAGES`, khớp `frontend/shared/page-layout.ts`; thêm khối mới phải sửa cả hai). Việc tiếp theo là chuẩn bị chuyển hệ thống: khai tài khoản nhận chuyển khoản, kho gửi hàng, cân nặng sản phẩm, tài khoản sandbox Nhất Tín.
