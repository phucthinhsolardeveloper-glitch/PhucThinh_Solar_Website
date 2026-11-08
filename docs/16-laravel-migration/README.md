# Chuyển đổi backend sang Laravel 13

Tài liệu đặc tả gốc: [../12-ai/LARAVEL13-MIGRATION-CLAUDE-MASTER-SPEC.md](../12-ai/LARAVEL13-MIGRATION-CLAUDE-MASTER-SPEC.md).
Thư mục này ghi trạng thái thật của quá trình chuyển đổi.

**Cập nhật 2026-11-08:** HEAD bàn giao `7265b9ad`; các mục Phase 1–4 bên dưới
là nhật ký từng mốc và có đường dẫn/tên DB cũ. Runtime hiện tại dùng Laravel
13/PHP 8.4/Filament 5 ở `/admin`, DB `pts_app`, Nuxt chỉ đọc Laravel. Kiểm
chứng tiếp quản: 27 API snapshot 1.754/1.754 theo file hiện có, DB clone từ zero/import khớp 15
nhóm số dòng và 0 orphan đã kiểm; 46 màn Admin desktop/mobile; candidate
Caddy → Nuxt/Laravel chạy local, `site-audit` 0 lỗi trên 19 route × 4 viewport,
`shop-flow` đạt. Chi tiết Admin: [admin-capability-audit-2026-11-08.md](admin-capability-audit-2026-11-08.md).
Candidate deployment: [../../deploy/laravel13/README.md](../../deploy/laravel13/README.md).
**Production/master chưa chuyển Laravel.**

Admin sản phẩm hiện có CSV cập nhật **giá bán, giá khuyến mãi, chỉ báo giá và
hiển thị**: tại `/admin/products`, xuất mẫu, sửa file UTF-8, nhập để xem trước,
tải báo cáo từng dòng rồi xác nhận áp dụng trong 15 phút. ID/mã/tên dùng để đối
chiếu; ô trống giữ nguyên; `XÓA` trong giá khuyến mãi xóa giá đó. Tệp tối đa 2 MB
và 5.000 dòng. Một lỗi chặn toàn bộ file; nhập thiếu sản phẩm không xóa sản phẩm.
Luồng CSV giá không thêm sản phẩm hoặc thay ảnh.

CSV **Sản phẩm & ảnh** là luồng riêng tại `/admin/products`: xuất mẫu gồm ID,
mã, tên, slug, ID danh mục và các ảnh. Dòng có ID chỉ sửa ảnh; ô trống giữ
nguyên, `XÓA` bỏ ảnh hoặc thư viện ảnh. Dòng không ID tạo sản phẩm **ẩn**, cần
tên và ID danh mục; slug để trống sẽ tự sinh từ tên. Ảnh phải có sẵn dưới
`upload/product`, chỉ nhập tên file; thư viện ngăn cách bằng `|`. Admin xem trước,
tải báo cáo từng dòng và xác nhận trong 15 phút. Một lỗi chặn cả file, không
xóa file ảnh vật lý khi bỏ quan hệ. Tệp tối đa 2 MB và 5.000 dòng.

Quyền Admin theo từng thao tác: chủ website có toàn quyền; editor cần cả quyền
khu vực và quyền thao tác để nhập CSV/bulk sản phẩm (`catalog_bulk`), xuất bản
bài viết (`content_publish`), xác nhận tiền thủ công (`orders_payment`), duyệt
đánh giá (`reviews_moderate`) hoặc gửi email thử (`settings_mail_test`). Các
quyền này cấp ở **Tài khoản quản trị**. Editor import từ hệ cũ chỉ có quyền khu
vực, cần chủ website cấp thêm quyền thao tác tương ứng. Quyền `content_publish`
hiện bảo vệ workflow **bài viết**; các loại nội dung khác chưa áp dụng workflow.

Trong form sửa sản phẩm có **Tạo bản sao**: tạo sản phẩm ẩn, slug mới và mã
sản phẩm trống; giữ thông số, ảnh, tài liệu, khối nội dung và SEO để biên tập
trước khi công bố. Ảnh/tài liệu ở bản sao cùng tham chiếu file vật lý với bản gốc;
xóa bản ghi hoặc đổi media không tự xóa file gốc.

Admin **Mẫu email** tại `/admin/mau-email` cho người có quyền settings sửa lời
văn của 5 email giao dịch, xem trước bằng dữ liệu giả. Người có thêm quyền
`settings_mail_test` gửi bản thử tới địa chỉ
nhập trong form (tối đa 5 lần/giờ cho mỗi người). SMTP vẫn cấu hình qua env;
việc gửi qua SMTP thật cần thử ở staging mailbox.

Admin đơn hàng chỉ cho sửa trạng thái xử lý và ghi chú. `payment_status` là
thông tin đọc; VNPay chỉ cập nhật từ callback có chữ ký. Với đơn thanh toán
thủ công chưa trả, người có quyền Orders và `orders_payment` có thể chọn **Xác nhận đã nhận tiền**,
nhập mã giao dịch/phiếu thu; hệ thống lưu payment transaction và người xác nhận.
Chưa có luồng hoàn tiền tự động trong Admin.

Admin **Liên hệ & tư vấn** có thể phân công người có quyền Leads, đặt hạn theo
dõi, lọc lead quá hạn và xem lịch sử đổi trạng thái/phân công/hạn. Ghi chú chỉ
lưu ở lead hiện tại; lịch sử ghi việc đổi ghi chú, không sao chép nội dung khách.
Lead đã xong hoặc bị đánh dấu rác sẽ xóa lịch hẹn theo dõi.

Admin **Đánh giá sản phẩm** cho xem ảnh/video trước khi duyệt, duyệt hoặc ẩn
từng đánh giá hay theo lô, sửa nội dung và trả lời đánh giá đã duyệt. Các thao
tác ghi qua service có kiểm quyền Reviews và `reviews_moderate`, khóa bản ghi; ẩn đánh giá gốc làm
đánh giá cùng câu trả lời biến mất khỏi API public.

Bài viết đã có workflow biên tập trong Filament: từ trang sửa bài chọn **Soạn
bản nháp**, sửa và lưu trong **Bản nháp & lịch sử bài viết**, mở xem trước Nuxt
bằng liên kết ký 15 phút, rồi **Xuất bản**. Bản nháp không đổi trang public;
publish lưu phiên bản trước đó và kiểm xung đột; phiên bản cũ có thể khôi phục
thành bản nháp để kiểm tra trước khi xuất bản lại. Trang xem trước noindex,
no-store và không gửi referrer. Form sửa bài gốc vẫn là đường sửa trực tiếp
public đối với người có quyền `content_publish`; editor chỉ có quyền Content
phải dùng workflow bản nháp và không thể lưu trực tiếp bài đã xuất bản. Giải pháp,
Dự án, Chính sách và section tree chưa vào workflow này.

Địa chỉ giao hàng có màn **Tỉnh/thành phố** tại `/admin/provinces` để tạo/sửa
tên, mã, thứ tự, hiển thị và màn **Phí giao hàng** tại `/admin/wards` để tạo/sửa
phường/xã, tỉnh trực thuộc, phí. Tắt tỉnh sẽ ẩn toàn bộ phường/xã của tỉnh khỏi
API chọn địa chỉ; không có thao tác xóa tỉnh/phường trong Admin vì đơn hàng và
dữ liệu địa chỉ có thể còn tham chiếu.

## Nhánh và môi trường

| Thứ | Dùng gì |
|---|---|
| Production, GitHub `master`, git local `master`, container local đang chạy site | Bản mới nhất của `master` |
| Chuyển đổi Laravel | Nhánh `migration/laravel13-integration`, worktree `/home/admin01/src/pts-laravel13` |
| Database cho Laravel hiện tại | `pts_app` trong `pts-mariadb-local`; test `pts_app_test`. Nguồn legacy chỉ đọc: `pts_laravel13_clone`. Rehearsal mới tạo DB riêng có tên ghi ở `/tmp/pts-laravel13-rehearsal-db`. Không trỏ Laravel vào production |

Không code Laravel trên `master`. Mọi thay đổi trên `master` phải đưa sang nhánh
chuyển đổi bằng `scripts/sync-laravel-branch.sh` ngay sau khi push. Script dừng khi
có xung đột và không ép push.

## Trạng thái

| Phase | Trạng thái | Bằng chứng |
|---|---|---|
| 0 — Đóng băng và kiểm kê | Xong, 2026-11-07 (còn xác nhận bản đồ domain) | [phase-0/](phase-0/) |
| 1 — Nền Laravel | Xong, 2026-11-07 | Mục "Phase 1" bên dưới |
| 1b — Đăng nhập Admin mới bằng tài khoản cũ | Xong, 2026-11-07 | Mục "Đăng nhập" bên dưới |
| 2 — Lớp tương thích | Xong phần trang sản phẩm cần | Mục "Phase 2" bên dưới |
| 3 — Toàn bộ API công khai trên Laravel | Xong, 2026-11-07: 27 API đọc khớp PHP 1.751/1.751 ca; ghi (form, đánh giá, giỏ hàng, đặt hàng, VNPay) có test | Mục "Phase 3" bên dưới |
| 4 — Nuxt chỉ dùng Laravel | Xong, 2026-11-07: không còn đường gọi PHP, không còn lớp preview cũ | Mục "Phase 4" bên dưới |
| 5 — Filament Admin | Có 23 màn, đọc/sửa nhiều domain; chưa parity thao tác đầy đủ | `admin-capability-audit-2026-11-08.md` |
| 11 — Schema mới và import | Schema mới + import đã có; rehearsal zero DB đạt trên clone local | `/tmp/pts-laravel13-rehearsal/` |
| 13 — Staging/cutover rehearsal | Candidate stack local chạy qua Caddy; staging VPS, restore và rollback route chưa có | `deploy/laravel13/README.md` |
| 14 — Production | Chưa làm, cần duyệt rõ | Không có deploy production |
| Phản biện đặc tả | Có hiệu lực | [spec-review.md](spec-review.md) |
| Kiến trúc đích (dựng theo Nuxt, tùy biến sâu) | Có hiệu lực | [target-architecture.md](target-architecture.md), [phase-0/usage-map.md](phase-0/usage-map.md) |

## Gate Phase 0

- [x] Mốc Git và trạng thái worktree: `phase-0/baseline.json`
- [x] Dấu vân tay schema và số dòng từng bảng: `phase-0/schema.md`
- [x] Database riêng cho chuyển đổi, cùng dấu vân tay schema với bản local
- [x] Danh sách API PHP, cầu nối Nitro, contract TypeScript: `phase-0/api-inventory.md`
- [x] Ma trận chức năng Admin legacy: `phase-0/admin-capabilities.md`
- [x] Bản đồ file → domain (bản nháp theo tên file): `phase-0/domain-map.md`
- [x] Kiểm kê media local: `phase-0/media.md`
- [x] Mốc test hiện tại và chênh lệch production/source: `phase-0/test-and-runtime-baseline.md`
- [ ] Chủ lane xác nhận bản đồ domain (32 file chưa phân loại)
- Không có thay đổi production nào trong Phase 0. Không có secret hay dữ liệu cá nhân trong các file trên.

Chạy lại kiểm kê: `python3 tools/migration/phase0_inventory.py` từ gốc worktree.

## Nhật ký Phase 1–4 (mốc lịch sử, không phải lệnh chạy hiện tại)

Các tên `lv_*`, `/admin-next`, `docker-compose.laravel.yml` và cầu PHP dưới đây
đã được thay ở các commit sau. Dùng bảng trạng thái ở đầu file và code hiện hành
khi chạy môi trường mới.

## Phase 1 — nền Laravel

- `backend/`: Laravel 13.40, PHP 8.4.27, Filament 5.11, Pest 4, Larastan 3 (mức 5), Pint.
- Chạy: `docker compose -f docker-compose.laravel.yml up -d --build`, container `pts-laravel13-local`,
  `http://localhost:8090/health`, Admin thử `http://localhost:8090/admin-next`.
- Database: kết nối mặc định vào `pts_laravel13_clone`, tiền tố `lv_`; kết nối `legacy` cùng database,
  không tiền tố, chỉ đọc. User `pts_laravel` chỉ có quyền trên database bản sao và database test.
  Mật khẩu chỉ nằm trong `backend/.env`, không commit.
- Test chạy trên `pts_laravel13_test`, bản sao riêng, được phép ghi.
- Lệnh kiểm tra trong container: `php artisan test`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`.
- Tài khoản Admin thử chưa tạo. Tạo bằng `php artisan make:filament-user` khi cần; tài khoản nằm trong
  `lv_users` của bản sao và sẽ bỏ khi Filament đọc `table_user`.
- Site cũ không đổi: Nuxt 3100 và PHP 8088 vẫn chạy; database `phucthinhs_local` không có bảng `lv_`.

## Đăng nhập

- Filament `/admin-next` dùng guard `admin`, đọc `table_user` qua `App\Legacy\Auth\LegacyAdminUserProvider`.
- Mật khẩu kiểm như PHP cũ (`LegacyPasswordVerifier`): hash hiện đại qua `password_verify`, hash cũ
  `md5(secret . mật khẩu . salt)`. `LEGACY_ADMIN_SECRET` và `LEGACY_ADMIN_SALT` lấy từ `libraries/config.php`,
  chỉ nằm trong `backend/.env`.
- Chỉ tài khoản có `hienthi` trong `status` được vào. Đăng nhập bằng tên đăng nhập, không phải e-mail.
- Không ghi gì vào `table_user`: không băm lại mật khẩu, không đổi `user_token`, `login_session`, `lastlogin`.
  Mô hình `AdminUser` chặn mọi lệnh lưu. Lý do: PHP cũ kiểm tra token để chặn đăng nhập song song;
  nếu Laravel đổi token thì người đang dùng Admin cũ bị đẩy ra.
- Quyền: `role = 3` toàn quyền; các vai trò khác đọc `table_permission` của nhóm đang bật, giống
  `adminPermissionGranted()`. Chưa áp quyền vào màn hình nào vì chưa có màn hình.

## Phase 2

Cách kiểm: golden master. Code PHP cũ chạy trên dữ liệu thật, in ra kết quả (chỉ hash, không chép
nội dung), Laravel tính lại trên bản sao cùng dữ liệu và phải ra giống hệt.

| Phần | Code Laravel | Golden master | Ghi chú |
|---|---|---|---|
| Chọn khối mô tả sản phẩm (riêng, kế thừa hãng/danh mục, ẩn, cộng dồn) | `App\Legacy\Catalog\ProductContentResolver` | 399/399 sản phẩm khớp | Dữ liệu thật chỉ dùng "riêng" (393) và "kế thừa danh mục" (6); 8 chế độ còn lại có test riêng |
| Giá và chế độ báo giá (gộp 3 cách tính của PHP làm một) | `App\Domain\Catalog\PricingPolicy`, `Money` | 399/399 khớp cả trang sản phẩm, danh mục và `public_pricing.php` | 394 có giá, 5 báo giá |
| Chọn ảnh sản phẩm (ảnh chính, rồi thư viện ảnh) | `App\Legacy\Catalog\ProductImageResolver` | 399/399 khớp | Đọc volume ảnh của PHP ở chế độ chỉ đọc; 76 sản phẩm local không có ảnh nào |
| Cột `status` dạng "hienthi,noibat" | `App\Legacy\Support\StatusFlags` | | |
| Khối JSON lưu dạng HTML-escape | `App\Legacy\Support\ContentBlocks` | | |
| Hồ sơ PDP, thông số, gói sản phẩm, chip nổi bật, chỉ số quyết định | `App\Domain\Catalog\ProductDetail\ProductSpecs`, `config/pdp.php`, `config/pdp_metrics.php` | Qua `pts:parity` | `config/pdp.php` xuất tự động từ PHP bằng `tools/migration/export_pdp_config.php`, không sửa tay |
| Thẻ ứng dụng, gợi ý chọn mua, tài liệu | `App\Domain\Catalog\ProductDetail\ProductApplications` | Qua `pts:parity` | Ảnh minh họa đọc thư mục `assets/` của bản PHP, gắn chỉ đọc vào `/legacy/assets` |

Chụp lại golden master khi dữ liệu local đổi: chạy `tools/migration/golden/capture_*.php` trong
`pts-php81-local`, rồi tạo lại `pts_laravel13_clone` và `pts_laravel13_test` từ `phucthinhs_local`.
Hai bên phải cùng dữ liệu thì so sánh mới có nghĩa.

## Phase 3

`GET /api/v1/product-detail?slug=...` trên Laravel trả đúng hợp đồng
`frontend/shared/contracts/public-product-detail.ts`, cùng dữ liệu với `api/pts_v1_product_detail.php`.

`GET /api/v1/catalog` trả đúng `frontend/shared/contracts/public-catalog.ts`: danh mục theo đường dẫn
slug, lọc hãng/giá/loại/nhu cầu, sắp xếp, tìm từ khóa, phân trang, ảnh đầu trang, thẻ chọn theo nhu
cầu, FAQ, banner hỗ trợ, SEO. Code ở `app/Domain/Catalog/Listing/`.

So song song trong `pts-laravel13-local`:

| Lệnh | Ca thử | Kết quả 2026-11-07 |
|---|---|---|
| `php artisan pts:parity product-detail` | Mọi sản phẩm đang hiện | 375/375 giống hệt |
| `php artisan pts:parity catalog` | Mọi đường danh mục cấp 1–2, 20 kiểu lọc/sắp xếp/trang/từ khóa trên trang gốc và 4 danh mục, 14 truy vấn sai | 152/152 giống hệt |

Bẫy đã gặp:
- PHP có hai cách tra nhãn thông số. Bảng thông số thử từng từ khóa trên mọi dòng; chỉ số quyết định
  duyệt từng dòng với mọi từ khóa. Khi có cả "Lumen" và "Quang thông", hai cách cho hai giá trị.
  `ProductSpecs::lookup()` giữ cả hai, có test.
- Khối mô tả bỏ phần "Ứng dụng" chỉ khi có thẻ ứng dụng, nên thẻ ứng dụng phải tính trước.

## Phase 4: Nuxt chỉ dùng Laravel

Nhánh này chuyển hẳn sang Laravel, không chạy song song với PHP và không có công tắc
quay về. Production vẫn chạy `master` cho tới khi nhánh này hoàn thiện và được đổi hẳn.
Chỉ commit ở local, chưa push.

| Phần | Trạng thái |
|---|---|
| Cầu Nitro | `frontend/server/routes/_api/*` gọi `/api/v1/*` trên `NUXT_API_ORIGIN` |
| Phiên | Cookie `pts_session` của Laravel; Nuxt chỉ chuyển tiếp cookie này |
| Form tư vấn, đánh giá | Trình duyệt gửi thẳng `/api/v1/lead-form`, `/api/v1/review` để Laravel thấy IP thật |
| Đặt hàng | Form `/thanh-toan/` gửi thẳng `/api/v1/checkout`, Laravel trả chuyển hướng |
| Ảnh | Laravel phục vụ `/upload/*`, `/assets/images/site-visuals/*`, tạo `/thumbs/*` |
| Đã xóa | 27 trang `migration-*-preview`, layout và shell preview, mọi nhánh `isMigrationPreview`, các cầu POST 405/410 |

Kiểm trên Nuxt :3110 + Laravel :8090: `site-audit` 0 lỗi trên 19 trang × 4 độ rộng;
`audit/shop-flow.cjs` thêm hàng vào giỏ, giỏ giữ qua cookie, form thanh toán trỏ đúng API.

Mốc API: `php artisan pts:parity <api> --save` lưu câu trả lời hiện tại (đã khớp PHP) vào
`backend/storage/app/snapshots/`. Sau khi đổi cấu trúc dữ liệu, `--against-snapshot` so
với mốc này mà không cần PHP.

## Việc tiếp theo

1. Đóng gap Admin theo ma trận: create/delete/upload/permission theo action,
   product bulk import/export/price/image, rich editor, Visual Studio draft/preview.
2. Kiểm commerce/security sâu với order/review fixture, callback song song,
   backup/restore; carrier API chờ mapping kho/dịch vụ/kiện hàng.
3. Chạy staging VPS và benchmark runtime cùng dữ liệu; diễn tập route/image và
   DB rollback trước khi trình phương án cutover. Không deploy production khi
   chưa được chủ project duyệt rõ.
