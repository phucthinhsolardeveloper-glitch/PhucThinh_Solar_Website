# Bàn giao Claude → Codex (2026-11-10, tối)

Đọc trước: `AGENTS.md` → `docs/12-ai/context-map.md`. File này chỉ ghi trạng thái lúc bàn giao.

## Cập nhật sau khi tiếp quản — 2026-11-10

- Chủ website đã cho phép push/deploy. Release `d2c529e0` hiện chạy production;
  branch `master` đã sync `origin/master` tại commit này.
- Migrations `001700`–`001900` đã chạy. 68 đèn chuyển vào bốn danh mục con;
  counts 53/9/5/1, total 399, orphan 0. URL cũ trả 301.
- Backup trước deploy: `/srv/pts-laravel13/backups/20261110T085016Z-full`;
  images cũ và bản `stack.env` trước deploy còn ở
  `/srv/pts-laravel13/releases/rollback-d2c529e0/`.
- Kiểm tra 293 backend tests, Pint 392 file, PHPStan 0; Nuxt 500 tests,
  typecheck/build; candidate snapshot 1.754/1.754; post-deploy routes và health
  đều qua. Xem deployment log để biết chi tiết và giới hạn.
- Phần dưới phản ánh trạng thái trước khi tiếp quản, được giữ làm lịch sử; các
  chỉ dẫn “chưa push/chưa deploy” đã được thay thế.

## Trạng thái production tại thời điểm bàn giao cũ (đã superseded)

- phucthinhsolar.com chạy Laravel 13 + Nuxt từ 11:12 ngày 2026-11-10, images `pts-laravel13-*:f5aac548-candidate`,
  stack `/srv/pts-laravel13` trên VPS (`ssh pts-prod`). Nhật ký: `docs/06-operations/deployment-log.md`.
- Khi file này được viết, chủ website chưa cho phép deploy; sau đó đã có lệnh rõ ràng và release `d2c529e0` đã triển khai.
- Stack PHP cũ đã xoá; bản sao lưu nằm ở máy chủ website `D:\ProjectBackup\`.

## Commit local tại mốc bàn giao (đã push/deploy trong release d2c529e0)

| Commit | Nội dung |
|---|---|
| 610ffb71 | Admin: chuông thông báo (bảng `notifications`, migration `2026_11_10_001700`), nút "Xem trên website" ở mọi tài nguyên, thông báo lưu ghi rõ "Đã đổi: …" |
| 2d16ba37 | Danh mục sản phẩm dạng bảng: xếp theo cây, có cấp, số SP cả nhánh, lọc theo nhánh |
| ac2df8f2 | Migration dữ liệu `2026_11_10_001800_tidy_product_categories` (chủ website đã duyệt): gộp "Đèn sân vườn" cấp 1 rỗng vào "Đèn NLMT › Đèn Sân Vườn", "Kẹp tấm pin" sang "Vật tư phụ trợ", 2 băng keo vào "Vật tư phụ trợ"; link cũ 301. **Không** đổi thứ tự 5 loại đèn (chủ website giữ nguyên menu) |
| (commit cuối của file này) | Trang cây danh mục kéo thả (mô tả bên dưới) |

Đã chạy migration ở DB local (sao lưu trước: `~/local-db-move/pre-tidy-categories.sql`). Lúc deploy: backup DB
production trước, migration 001800 chạy được lặp lại an toàn (tìm theo slug, lần hai không làm gì).

## Trang cây danh mục (Admin › Danh mục sản phẩm)

- `/admin/categories` = cây bậc thang (`ManageCategoryTree`, view `resources/views/filament/resources/categories/`),
  `/admin/categories/bang` = dạng bảng cũ.
- Mặc định **khóa**: chỉ xem, thu gọn/mở rộng (nhớ theo trình duyệt). Bấm "Sắp xếp danh mục" mới hiện tay kéo; "Khóa lại"
  bỏ thay đổi chưa lưu. Kéo để đổi thứ tự hoặc chuyển cấp, tối đa 4 cấp; bấm "Lưu sắp xếp" mới ghi DB.
- Lưu qua `App\Domain\Catalog\CategoryTreeEditor::apply()`: kiểm tra đủ mọi danh mục, ≤4 cấp, cập nhật parent/depth/sort_order,
  slug trùng anh em thêm `-id`, link cũ 301 (`Redirects::record`, prefix), ghi audit `catalog.category_tree`.
- Đã thử trên trình duyệt: khóa ẩn tay kéo, mở khóa kéo được, thanh lưu hiện, không lỗi console. Chưa bấm Lưu thật trên
  trình duyệt (đã có test Livewire `saveTree`).

## Kiểm tra đã chạy (local)

`vendor/bin/pint`, `phpstan` 0 lỗi, `php artisan test`: 284 passed, 1 skipped.

## Việc còn lại sau release d2c529e0

1. Diễn tập restore/rollback đầy đủ qua Caddy trên staging VPS; production đã có backup và candidate DB rehearsal nhưng chưa thử route production quay lui.
2. Rà lại overflow 24 px trong bảng Products Admin trên desktop.
3. Consent Mode / Enhanced Conversions: chờ chủ website quyết.
4. Chủ website cần tự chạy `scripts/maintenance/Compact-WslDisks.ps1` (PowerShell admin) để thu nhỏ ổ ảo WSL.

## Môi trường local

- Docker: `/mnt/c/Users/ADMIN/AppData/Local/Programs/DockerDesktop/resources/bin/docker.exe` (luôn `< /dev/null`).
  Compose `pts-local`: `pts-laravel13-local` (127.0.0.1:8090) + `pts-mariadb-local`.
- Nuxt: `cd frontend && NUXT_LOCAL_API_PROXY=1 NITRO_HOST=0.0.0.0 NITRO_PORT=3110 NUXT_API_ORIGIN=http://127.0.0.1:8090 node .output/server/index.mjs`.
- Kiểm tra: `docker exec pts-laravel13-local sh -c 'cd /app && vendor/bin/pint && vendor/bin/phpstan analyse --memory-limit=1G && php artisan test --compact'`.
- Không build Docker song song; user Admin tạm phải xoá sau khi dùng; không gửi lead/đơn thật; không gọi API Nhất Tín production.

## Cách báo kết quả

Ghi kết quả và bằng chứng (lệnh đã chạy, số test, URL đã kiểm) vào commit message; báo cáo chủ website bằng tiếng Việt.
