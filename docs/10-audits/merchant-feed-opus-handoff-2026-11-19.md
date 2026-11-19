# Handoff Merchant Feed cho Claude Opus — 2026-11-19

## Phạm vi và trạng thái

- Worktree: `/home/admin01/orca/workspaces/Web_PTS_PHP/pts-merchant-fix-20261119`
- Nhánh: `pts-merchant-fix-20261119`, nền chưa commit `40e71bf7`.
- Chỉ xử lý Merchant Feed; không làm VietQR, không migrate/deploy, không thay DB production, không tạo đơn/lead, không push/merge. Không sửa Bridge worktree `/home/admin01/.hyans-mcp-bridge/worktrees/a2af3f1c`.
- WIP hiện có 19 file code/test/docs/config (16 tracked sửa, 3 file mới); chưa commit vì backend test chưa chạy được. Diff source đang có đầy đủ trong nhánh Orca.
- Đã nhận review Sonnet: gate mặc định OFF tốt; đề nghị fallback legacy nếu verified hụt ngưỡng, giữ legacy brand sanitizer, test numeric Excel IDs và coverage GTIN-8/14, brand rỗng + MPN, out_of_stock. Đã bổ sung fallback, `X-Feed-Mode`, guide XLSX/CSV, marker từ chối ô GTIN/MPN kiểu số, các test liên quan; legacy vẫn qua `self::plain()` và có regression assertion brand.

## Thay đổi theo file

### Backend

- `backend/app/Domain/Catalog/MerchantIdentifier.php` (mới): kiểm tra GTIN-8/12/13/14, check digit, GS1 restricted/coupon prefixes; giữ GTIN dạng chuỗi; chuẩn hoá MPN; tính `identifier_exists` có điều kiện.
- `backend/database/migrations/2026_11_19_000300_add_merchant_product_attributes.php` (mới): thêm availability, GTIN, MPN nullable và cờ “đã xác nhận thiếu mã” mặc định false; không backfill; dùng tiền tố riêng, không đụng migration kho `000100`.
- `backend/app/Domain/Catalog/MerchantFeed.php`: tách headers/output verified khỏi legacy; verified chỉ nhận availability `in_stock`; xuất GTIN/MPN và identifier_exists theo dữ liệu hợp lệ; legacy response giữ `in_stock`/`no` đúng hành vi cũ; brand được sanitizer.
- `backend/app/Http/Controllers/MerchantFeedController.php`: config gate + cache key verified riêng; nếu verified dưới ngưỡng thì dùng legacy cache/feed, đặt `X-Feed-Mode: legacy-fallback`, không cache feed verified rỗng; chỉ trả 503 nếu không có cache/feed legacy nào.
- `backend/config/services.php`, `deploy/laravel13/backend.env.example`: `MERCHANT_FEED_VERIFIED_ENABLED=0`, ngưỡng mặc định 286.
- `backend/app/Models/Product.php`: casts/attributes cho Merchant fields.
- `backend/app/Filament/Resources/Products/ProductResource.php`: Admin fields Merchant và validation.
- `backend/app/Filament/Pages/ProductBulkPage.php`: preview CSV verified riêng, hiển thị count/skip/threshold; không đổi public flag.
- `backend/app/Domain/Catalog/Bulk/BulkColumns.php`: cột availability/GTIN/MPN/xác nhận thiếu mã và loại dữ liệu.
- `backend/app/Domain/Catalog/Bulk/BulkCatalog.php`: đọc Merchant attrs khi export.
- `backend/app/Domain/Catalog/Bulk/BulkExporter.php`: workbook/CSV Merchant fields, text format, validations, CSV leading-zero protection, hướng dẫn ưu tiên XLSX.
- `backend/app/Domain/Catalog/Bulk/BulkReader.php`: đọc BOM trước header CSV; từ chối ô Excel GTIN/MPN lưu kiểu số vì số 0 đầu có thể đã mất.
- `backend/app/Domain/Catalog/Bulk/BulkPlanner.php`: validate availability/GTIN/MPN/cờ không mã và mâu thuẫn.
- `backend/app/Domain/Catalog/ProductDuplicator.php`: không sao chép các claim Merchant sang sản phẩm mới.

### Tests và tài liệu

- `backend/tests/Unit/MerchantIdentifierTest.php` (mới): check digit và GTIN-8/12/13/14, UPC-A `042100005264`, restricted/coupon, MPN + brand, no-ID.
- `backend/tests/Feature/ProductWorkbookTest.php`: migration/no-backfill/warehouse coexistence, XLSX/CSV round-trip số 0, numeric cell rejection, BOM quoted header, out_of_stock rejection, legacy/verified/fallback XML và CSV, quote-only.
- `backend/tests/Feature/ProductDuplicateTest.php`: đảm bảo duplicate không nhận Merchant claims.
- `docs/10-audits/2026-09-16-merchant-feed-and-product-schema-audit.md`: addendum schema/rollout/rollback và nguồn chuẩn.
- `deploy/laravel13/backend.env.example`: env gate.
- `docs/10-audits/merchant-feed-opus-handoff-2026-11-19.md`: bàn giao này.

## QA có bằng chứng

- `git diff --check` → exit code `0` (PASS; whitespace/conflict-marker check).
- `docker exec pts-laravel13-local php artisan test --filter='MerchantIdentifierTest|ProductWorkbookTest|ProductDuplicateTest|WarehousesTest'` → exit code `1`, Docker không kết nối được socket `/var/run/docker.sock`; Laravel/Pest không khởi chạy, do đó `0` test thực thi, `0 PASS`, `0 FAIL`, trạng thái NOT RUN/BLOCKED, không được báo là test pass.
- Pint/PHPStan chưa chạy; không có Docker daemon để vào môi trường backend, không dùng PHP host.
- Không sửa DB/cache production, không chạy migration/deploy, không commit/push/merge; rollback source hiện tại là reset về `40e71bf7` sau khi đối chiếu WIP.

## Gate, rủi ro và dữ liệu còn thiếu

- `MERCHANT_FEED_VERIFIED_ENABLED` phải giữ `0` cho tới khi owner/admin nhập và xác nhận tồn kho thật; không suy diễn `in_stock` cho NULL. Threshold 286 chỉ dựa snapshot owner-provided 315/286, phải đối chiếu preview CSV ID/count trước khi bật.
- GTIN/MPN chỉ được nhập từ nhãn/datasheet hãng; chưa có dữ liệu thật để điền hoặc tuyên bố định danh sản phẩm đã xác minh.
- Legacy route vẫn giữ điều kiện hiện hành `pricesArePublic()`: nếu giá sản phẩm website đang tắt, route vẫn trả 404 như trước. Đây là rule hiện hữu; cần owner xác nhận chính sách giá nếu muốn thay đổi, không tự sửa trong task này.
- Migration chỉ thêm cột nullable, không backfill. Rollback DB `down()` sẽ xóa các cột Merchant; trước khi cân nhắc down phải export dữ liệu quản trị đã nhập.

## TODO cho Opus/QA

1. Rà soát toàn bộ diff trong worktree này, đặc biệt ProductResource/warehouse conflict, BulkReader numeric-cell sentinel, MerchantFeed fallback/cache, migration idempotency và tests.
2. Khi Docker test engine khả dụng, xác nhận DB profile là test riêng trước khi chạy `php artisan test --filter='MerchantIdentifierTest|ProductWorkbookTest|ProductDuplicateTest|WarehousesTest'`; sau đó chạy Pint test và PHPStan theo repo contract. Ghi exit code và test count thật.
3. Sửa lỗi P0/P1 có bằng chứng; không thay dữ liệu production hoặc bật cờ. Chạy `git diff --check` lại. Chỉ commit sau QA có thể xác minh; commit message ghi rõ các lệnh và exit code, gồm NOT RUN nếu Docker còn chặn.
4. Chỉ bàn giao READY-TO-MERGE khi backend QA đạt; nếu Docker tiếp tục unavailable, giữ trạng thái partial/blocked và ghi rõ còn test nào.

## Opus review và port — 2026-11-19 (sau WSL OOM)

- Port nguyên diff Codex (`git diff` + 4 file mới) sang nhánh `pts-opus-lead-20261119`; worktree Codex nguồn giữ nguyên, không sửa.
- Rà theo yêu cầu: gate `MERCHANT_FEED_VERIFIED_ENABLED` mặc định OFF; legacy feed giữ đúng hàng cũ (`in_stock`/`no`, brand qua `self::plain()`); verified mode bỏ sản phẩm có `merchant_availability` NULL/khác `in_stock`, không tự nhận tồn kho; fallback legacy khi dưới ngưỡng; code chạy được khi cột chưa migrate (`?? ''` trên stdClass); migration `000300` sau `000100` kho / `000200` users, chỉ thêm cột nullable.
- Sửa P1 (test sai, sẽ FAIL khi Pest chạy) trong `backend/tests/Unit/MerchantIdentifierTest.php`, kiểm bằng mô phỏng Python cùng thuật toán: `12345671` sai check digit (đúng `12345670`, thêm `96385074`); `14006381333937` sai (đúng `14006381333938`); `050000000005` là UPC hệ số 0 hợp lệ — coupon UPC-A là `500000000005`; `980000000005` là UPC hệ 9 — mã GTIN-13 coupon là `9800000000007`; `exists('', '', false, true, false)` code trả `no` (đúng quy tắc Google: không GTIN và không đủ brand+MPN) nên test sửa thành `no`.
- Sửa P2 `MerchantIdentifier`: GTIN-8 có bảng tiền tố riêng — chặn `0xx`, `2xx` (RCN-8) và `97–99`; trước đó áp nhầm bảng GTIN-13 nên `01234565` lọt qua.
- Còn để ý (P2, chưa sửa): ô Excel GTIN/MPN kiểu số bị thay bằng 71 chữ X nên thông báo lỗi là "vượt quá ký tự" thay vì "hãy nhập dạng văn bản"; MPN thuần số hợp lệ cũng bị từ chối nếu ô là Number.
- Pest/Pint/PHPStan: **BLOCKED** — `docker info` exit 1 (daemon không chạy), WSL không có `php`. Không tự khởi động Docker. 0 test PHP đã chạy.
