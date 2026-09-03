# Audit kỹ thuật hiện đại hóa Admin — 2026-09-01

## Phạm vi và nguyên tắc

Audit tiếp nối checkpoint từ phiên Codex trước, tập trung vào bề mặt Admin, endpoint, liên kết code → DB → runtime, query, validation, transaction, error handling, hiệu năng, coupling, legacy/duplication, testability và khoảng cách docs ↔ code.

Audit chỉ dùng thao tác đọc trên source/Git và các câu lệnh `SHOW`, `SELECT`, `EXPLAIN` trên MariaDB local. Không sửa source, không ghi database, không chạy request Admin authenticated có khả năng ghi DB và không dùng kết quả timing `curl -L` từng bị treo.

Trạng thái khi audit:

- Branch: `feature/ecommerce-upgrade`.
- Ahead `origin/feature/ecommerce-upgrade`: 11 commit.
- Tracked working tree sạch.
- `artifacts/` untracked và được giữ nguyên.
- Không tìm thấy báo cáo Claude riêng về Admin modernization trong Git hoặc `artifacts/`; các nhận định kỹ thuật có trong status/handoff được dùng như giả thuyết để kiểm chứng.

## Kết luận chính

### 1. Auth Admin tạo write coupling trên mọi full-page request — RESOLVED ON LOCAL

Luồng full-page Admin:

```text
administrator/index.php
  -> libraries/requick.php
  -> SELECT table_user theo id
  -> kiểm tra session hash, timeout và user_token
  -> nếu đủ 60 giây: conditional UPDATE table_user(lastlogin)
```

Baseline audit xác nhận `libraries/requick.php` từng cập nhật `lastlogin` và `user_token` trên mọi page load. Ngày 2026-09-03, auth full-page local đã được đổi theo contract sau:

- `user_token` chỉ được sinh khi login thành công; page load chỉ so sánh token để phát hiện một login khác.
- `lastlogin` chỉ được cập nhật tối đa một lần mỗi 60 giây.
- Activity update dùng điều kiện `id` + giá trị `lastlogin` đã đọc, nên hai request đồng thời không cùng ghi đè.
- Session hash, timeout tối thiểu 300 giây và cảnh báo concurrent login được giữ nguyên.

Characterization test phủ session hợp lệ/hết hạn, token khớp/khác, mốc throttle và câu update không chứa `user_token`. HTTP integration bằng fixture local xác nhận hai full-page request liên tiếp trả 200 mà không đổi `lastlogin` hoặc `user_token`.

### 2. AJAX Admin dùng bootstrap khác — PARTIALLY VERIFIED

`administrator/api/config.php` không include `libraries/requick.php`, nên AJAX Admin không cập nhật `lastlogin` hoặc xoay `user_token` theo đường full-page. Bootstrap local nay trả HTTP 401 + `AUTH_REQUIRED` khi phiên không hợp lệ; client có handler chung cảnh báo và chuyển về đăng nhập thay vì để request thất bại với body rỗng.

Tuy nhiên, bootstrap AJAX gọi `Functions::checkLoginAdmin()`, thực hiện:

```sql
SELECT secret_key
FROM table_user
WHERE secret_key = ?
  AND FIND_IN_SET('hienthi', status)
```

Phân biệt chính xác:

- Full-page Admin: auth `SELECT`; conditional activity `UPDATE` tối đa mỗi 60 giây.
- AJAX Admin: ít nhất một auth `SELECT`, không xoay token.
- Login endpoint: cập nhật `login_session`, `lastlogin`, `user_token` và `secret_key`.

Nhận định “AJAX cũng đi qua bootstrap riêng” là đúng. Nhận định AJAX cũng cập nhật `lastlogin/user_token` như full-page là không được code hiện tại xác nhận.

### 3. Inventory bề mặt Admin và endpoint — VERIFIED

Static inventory tại thời điểm audit:

- 25 Admin source/controller.
- 70 Admin template `*_tpl.php`.
- 18 file trong `administrator/api/`, gồm bootstrap `config.php`.
- 13 public API endpoint.
- Handoff trước ghi nhận 65/65 route Admin đã render; audit ban đầu không render lại vì auth khi đó còn ghi DB trên mọi page load.

Hai endpoint legacy đã bị vô hiệu hóa bằng HTTP 404:

- `administrator/api/component.php`.
- `administrator/api/save_add_item.php`.

Ngày 2026-09-03, cả hai vẫn bị chặn trước bootstrap/code legacy và trả thêm JSON `LEGACY_API_DISABLED` để client/monitor không còn nhận body rỗng.

Các endpoint còn lại chủ yếu dùng session DB check; mutation chính đã có CSRF, permission và allowlist. Response contract vẫn không đồng nhất: JSON, HTML fragment, plain text, empty response và `die()`/`exit()` cùng tồn tại.

### 4. DB engine, index và ràng buộc — RESOLVED ON LOCAL FOR COMMERCE

Baseline audit xác nhận database local có 49 bảng và toàn bộ dùng MyISAM. Ngày 2026-09-02, migration 008 đã chuyển riêng `table_order` và `table_order_detail` sang InnoDB trên Docker local; các bảng khác không đổi.

Index đáng chú ý:

- `table_product` và các bảng phân cấp route đã có `idx_route_type_slug` và `idx_type_numb_id` từ migration 007.
- `table_user` chỉ có primary key `id`.
- `table_order` chỉ có primary key `id`.
- `table_order_detail` có primary key `id`, `idx_order_detail_order (id_order)` và `idx_order_detail_product (id_product)` sau migration 008.
- Không có foreign key.

Hệ quả:

- Lookup AJAX theo `table_user.secret_key` không có index phù hợp. Local chỉ có một user nên chưa thể hiện chi phí scale.
- Lookup `table_order_detail` theo `id_order` và `id_product` đã có index trên local.
- Quan hệ order/product/hierarchy chỉ được bảo vệ bằng code.
- Transaction PDO nay rollback thật cho hai bảng commerce trên local; production chưa được migrate.

### 5. Checkout atomicity và idempotency — RESOLVED ON LOCAL

Trạng thái triển khai 2026-09-02: **RESOLVED ON LOCAL** bởi ADM-P0-001 và ADM-P1-004. Checkout kiểm tra order và từng order detail, đọc lại product, dùng transaction thật trên InnoDB và vẫn xóa bù trừ theo order ID; email/thanks/cart clear chỉ chạy sau khi đủ N detail. Shutdown cleanup bảo vệ thêm đường PDODb `die()`. `scripts/test-commerce-transaction.php` đã chứng minh rollback xóa cả order/detail và không đổi tổng dữ liệu. Production chưa chạy migration 008.

`sources/order.php` thực hiện tuần tự trong một transaction:

1. Insert `table_order`.
2. Lặp cart và insert từng `table_order_detail`.
3. Tiếp tục xử lý email và session.

Mỗi insert đều được kiểm tra; lỗi ở bất kỳ detail nào rollback toàn bộ transaction trên InnoDB local và chạy cleanup phòng vệ. Production chỉ có bảo đảm này sau khi migration 008 được duyệt và áp dụng.

Ngày 2026-09-03, migration 009 và checkout local bổ sung token idempotency 256-bit có unique index. Replay cùng token tái sử dụng ID/code của đơn đã commit, không tạo thêm order detail hoặc gửi email lần hai. Source checkout mới chỉ được deploy sau khi migration 008 và 009 đã áp dụng; production quote-only hiện chưa bị tác động.

Product bulk có gọi transaction nhưng code đã nhận biết engine legacy không rollback được và thực hiện phục hồi thủ công. Đây là cơ chế bù trừ có test, không phải atomic transaction từ database.

### 6. Integrity dữ liệu giá — VERIFIED

Trạng thái triển khai 2026-09-01: **RESOLVED ON LOCAL** bởi ADM-P0-002/ADM-P0-003. CRUD thủ công và bulk import hiện gọi cùng `libraries/admin_product_pricing.php`; product #316 đã được submit lại qua form Admin và discount được chuẩn hóa thành 10. Hai integrity query sau sửa đều trả 0.

Các truy vấn được chạy lại riêng lẻ:

```text
invalid_price_relation = 0
discount_mismatch = 1
```

Dòng mismatch:

| Trường | Giá trị |
|---|---:|
| Product ID | 316 |
| Code | rỗng |
| regular_price | 500000 |
| sale_price | 450000 |
| discount lưu trong DB | 0 |
| discount tính từ hai mức giá | 10 |

Không sửa dữ liệu.

Nguyên nhân code có khả năng cao:

- Bulk importer kiểm tra `sale_price <= regular_price` và tự tính lại `discount`.
- CRUD sản phẩm thủ công chỉ kiểm tra từng trường giá/discount có phải số hay không.
- CRUD thủ công không kiểm tra quan hệ giữa hai giá và không tự đồng bộ discount.

Như vậy cùng một entity đang có hai đường ghi với invariant khác nhau.

### 7. Orphan và duplicate trọng yếu — VERIFIED trên local

| Kiểm tra | Kết quả |
|---|---:|
| Product orphan `id_list` | 0 |
| Product orphan `id_cat` | 0 |
| Product orphan `id_brand` | 0 |
| Nhóm trùng product code không rỗng | 0 |
| Nhóm trùng product slug VI trong type `san-pham` | 0 |
| Order detail orphan order | 0 |
| Order detail orphan product | 0 |

Đây chỉ là bằng chứng của dataset local hiện tại. Database không có FK hoặc unique constraint để ngăn tái phát.

### 8. Query và hiệu năng — VERIFIED/PARTIAL

Các kết quả đã được audit trước và được đối chiếu với code/tài liệu hiện tại:

- Admin Product đã bỏ tối đa 20 query review theo từng row.
- Profiling authenticated local trước đó ghi nhận:
  - Product: 21 query.
  - Order: 22 query sau tối ưu địa giới.
  - Contact: 17 query.
  - News: 18 query.
- Không còn N+1 theo từng row trên bốn danh sách đã profile.

Phần còn lại:

- Mỗi full-page Admin có auth `SELECT`; auth `UPDATE` chỉ phát sinh tối đa mỗi 60 giây.
- Status API dùng một count + một update set-based cho scope selected có trạng thái đích, tối đa 500 ID; toggle legacy không có giá trị đích vẫn tuần tự để giữ tương thích.
- Gallery reorder đã chuyển sang một `UPDATE ... CASE` parameterized, giới hạn 200 ID, kiểm tra đúng toàn bộ album và xác minh kết quả; ba bảng sortable menu/index/detail dùng transaction với validation danh sách.
- `FIND_IN_SET()` trên status xuất hiện rộng và khó tận dụng index.
- Dataset local nhỏ; chưa đủ kết luận capacity hosting.

Kết quả timing `curl -L` từng bị treo bị loại hoàn toàn và không được dùng làm evidence.

### 9. Validation — VERIFIED

Trạng thái triển khai 2026-09-01: **PRICE INVARIANT RESOLVED ON LOCAL**. Invariant `regular_price`/`sale_price`/`discount` đã được gom vào helper chung và có characterization, bulk regression cùng authenticated HTTP smoke.

Các mutation Admin quan trọng đã được cải thiện bằng:

- CSRF.
- Session và permission check.
- Allowlist table/column/action.
- ID normalization và giới hạn batch.
- Parameterized values trong các đường đã harden.

Invariant giá đã dùng chung helper giữa CRUD thủ công và bulk importer. Các entity khác vẫn cần được đánh giá theo từng đường ghi trước khi gom validation rộng hơn.

### 10. Error handling — RESOLVED ON LOCAL (DATA LAYER)

Trạng thái triển khai 2026-09-03: ADM-P1-001 đã thay toàn bộ 15 nhánh `die()` trong `class.PDODb.php` bằng `PDODbException`. Exception có message công khai trung tính, giữ operation/SQLSTATE/driver message/query template riêng cho lớp xử lý lỗi và không ghép giá trị bind. Regression chứng minh lỗi SQL có thể được catch, rollback và tiếp tục dùng kết nối; full pre-deploy đạt.

Data layer không còn tự render HTML hoặc kết thúc request, nên controller hiện có có thể rollback/bù trừ. Bootstrap Admin API local nay có exception boundary chung: rollback transaction đang mở, log chi tiết nội bộ và trả HTTP 500 `INTERNAL_ERROR` trung tính. Endpoint vẫn có thể bắt lỗi riêng khi cần mã lỗi nghiệp vụ cụ thể.

Các batch local đã chuẩn hóa dần mutation JSON, gồm `login/status/upload`, database, numb, copy, slug, sortable, comment và gallery. Gallery nay xác minh sửa metadata/xóa/reorder trước khi client cập nhật DOM, chuẩn hóa mọi request sai và cô lập output buffer legacy để HTML fragment không phá JSON. Hai endpoint dropdown category/place giữ HTML thành công nhưng trả JSON 400/405 cho input sai. Exception ngoài dự kiến đã có boundary cấp bootstrap; một số endpoint scalar/legacy và API router chung vẫn chưa chuẩn hóa.

### 11. Testability — VERIFIED

Điểm đã có:

- Regression scripts cho security, Admin readiness và bulk import.
- Product bulk có fault injection để test rollback bù trừ.
- Playwright phủ nhiều public interaction và visual baseline.
- Profiling authenticated bốn danh sách Admin đã có số liệu local.

Khoảng trống:

- Đã có characterization cho token ổn định/throttle và HTTP integration cho full-page request liên tiếp; chưa có stress test request song song thật.
- Đã có test checkout partial write/shutdown, replay và hai tiến trình ghi đồng thời bằng idempotency key bền vững trong database; chưa có stress test qua hai HTTP request thật trên staging.
- Chưa có contract test thống nhất cho lỗi API.
- Đã có test xác nhận CRUD thủ công và bulk importer áp cùng invariant giá sản phẩm.
- Chưa profile Admin trên dataset staging gần production.

### 12. Legacy và duplication candidates — NEEDS IMPLEMENTATION INVESTIGATION

Các ứng viên cần điều tra trước khi triển khai, không được cleanup ngay:

- Hai bootstrap auth khác nhau giữa full-page và AJAX.
- Validation sản phẩm bị lặp và khác hành vi giữa CRUD/bulk.
- Nhiều endpoint file PHP tự bootstrap và tự định dạng response.
- Nhiều đường update status/reorder theo từng row.
- Error/output coupling trong PDODb.
- Endpoint legacy đã khóa nhưng code vẫn còn để phục vụ rollback/evidence.
- Asset/file manager legacy phải giữ theo dependency map cho tới khi staging/browser evidence đủ.

## Docs ↔ code gaps

- `docs/04-api/overview.md` nói chưa có CSRF chung; đúng ở cấp kiến trúc, nhưng mutation Admin chính hiện đã được harden đáng kể. Tài liệu nên phân biệt phần đã phủ và endpoint còn thiếu.
- Tài liệu status và audit đã ghi contract token ổn định + activity throttle của full-page Admin.
- Performance profile staging cần tính auth `SELECT` ở mọi request và auth `UPDATE` định kỳ.
- Tài liệu DB mô tả dump ban đầu có 48 bảng; DB local hiện có 49 bảng và route indexes từ migration 007.
- Chưa có tài liệu về invariant khác nhau giữa CRUD sản phẩm thủ công và bulk importer.
- Không có báo cáo Claude Admin modernization riêng để kiểm chứng toàn bộ. Các giả thuyết kỹ thuật tìm thấy trong status/handoff đã được đối chiếu trực tiếp với code và DB.

## Phân loại finding

### VERIFIED

- Full-page auth luôn đọc `table_user`; local chỉ cập nhật `lastlogin` khi đủ 60 giây và không xoay token theo page load.
- AJAX auth đọc `table_user` nhưng không chạy đường xoay token của `requick.php`.
- Baseline có 49 bảng MyISAM; local hiện đã chuyển riêng hai bảng commerce sang InnoDB bằng migration 008.
- Thiếu FK và thiếu index nghiệp vụ ở commerce/auth.
- Checkout production chưa atomic cho đến khi migration 008 được phê duyệt và áp dụng; Docker local đã có transaction thật.
- Có đúng một discount mismatch; không có price relation sai.
- Các orphan/duplicate đã kiểm tra đều bằng 0.
- API/error format không đồng nhất.

### PARTIALLY VERIFIED

- Capacity Admin: local không còn N+1 trên bốn danh sách đã đo, nhưng dataset quá nhỏ để suy rộng lên hosting.

### NOT VERIFIED

- Timing từ lần `curl -L` bị treo.
- Kết luận rằng AJAX Admin cũng cập nhật `lastlogin/user_token` như full-page.

### NEEDS IMPLEMENTATION INVESTIGATION

- Thiết kế FK sau khi có contract xóa/cascade rõ ràng.
- Stress test checkout đồng thời trên staging sau khi áp dụng migration 008/009.
- Exception boundary và API response contract.
- Batch/set-based update cho sortable/reorder khi phù hợp; status có giá trị đích đã được xử lý.

## Ưu tiên kỹ thuật đề xuất

1. Stress test checkout đồng thời trên staging sau khi áp dụng migration 008/009.
2. Tiếp tục chuẩn hóa các endpoint scalar/legacy của Admin API.
3. Bổ sung contract/concurrency test trước refactor lớn.
4. Profile lại Admin trên staging có dataset gần production.

## Test và giới hạn

Đã chạy:

- Git branch/status/log/diff verification.
- Static source/bootstrap/endpoint inspection.
- `SHOW COLUMNS`.
- `SHOW TABLE STATUS`.
- `SHOW INDEX`.
- Các `SELECT` integrity riêng lẻ.
- `EXPLAIN` auth lookup.

Không chạy:

- Authenticated full-page runtime vì bootstrap sẽ ghi DB.
- Timing thay thế cho lần `curl -L` hỏng.
- Mutation test, import hoặc commerce smoke vì audit này read-only.
- Test trên production/staging.

## Ảnh hưởng, deploy và rollback

- Source: không thay đổi trong quá trình audit.
- Database: không thay đổi.
- Production: không ảnh hưởng.
- Deploy: không áp dụng cho audit.
- Rollback: không áp dụng; tài liệu audit có thể được hoàn nguyên độc lập nếu cần.
