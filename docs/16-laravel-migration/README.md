# Chuyển đổi backend sang Laravel 13

Tài liệu đặc tả gốc: [../12-ai/LARAVEL13-MIGRATION-CLAUDE-MASTER-SPEC.md](../12-ai/LARAVEL13-MIGRATION-CLAUDE-MASTER-SPEC.md).
Thư mục này ghi trạng thái thật của quá trình chuyển đổi.

## Nhánh và môi trường

| Thứ | Dùng gì |
|---|---|
| Production, GitHub `master`, git local `master`, container local đang chạy site | Bản mới nhất của `master` |
| Chuyển đổi Laravel | Nhánh `migration/laravel13-integration`, worktree `/home/admin01/src/pts-laravel13` |
| Database cho Laravel | `pts_laravel13_clone` trong container `pts-mariadb-local`, bản sao của `phucthinhs_local`. Không trỏ Laravel vào `phucthinhs_local` hay production |

Không code Laravel trên `master`. Mọi thay đổi trên `master` phải đưa sang nhánh
chuyển đổi bằng `scripts/sync-laravel-branch.sh` ngay sau khi push. Script dừng khi
có xung đột và không ép push.

## Trạng thái

| Phase | Trạng thái | Bằng chứng |
|---|---|---|
| 0 — Đóng băng và kiểm kê | Xong, 2026-11-07 (còn xác nhận bản đồ domain) | [phase-0/](phase-0/) |
| 1 — Nền Laravel | Xong, 2026-11-07 | Mục "Phase 1" bên dưới |
| 1b — Đăng nhập Admin mới bằng tài khoản cũ | Xong, 2026-11-07 | Mục "Đăng nhập" bên dưới |
| 2 — Lớp tương thích | Đang làm: xong khối nội dung sản phẩm | Mục "Phase 2" bên dưới |
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

Chụp lại golden master khi dữ liệu local đổi: chạy `tools/migration/golden/capture_*.php` trong
`pts-php81-local`, rồi tạo lại `pts_laravel13_clone` và `pts_laravel13_test` từ `phucthinhs_local`.
Hai bên phải cùng dữ liệu thì so sánh mới có nghĩa.

## Việc tiếp theo

Phần còn lại của Phase 2 theo thứ tự trang chi tiết sản phẩm cần: giá và chế độ ẩn giá, đường dẫn
ảnh và ảnh dự phòng, thông số theo hồ sơ PDP, cây danh mục và URL. Mỗi phần có golden master như trên.
