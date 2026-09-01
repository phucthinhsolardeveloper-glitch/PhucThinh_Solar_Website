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

### 1. Auth Admin tạo write coupling trên mọi full-page request — VERIFIED

Luồng full-page Admin:

```text
administrator/index.php
  -> libraries/requick.php
  -> SELECT table_user theo id
  -> kiểm tra session hash, timeout và user_token
  -> sinh token mới
  -> UPDATE table_user(lastlogin, user_token)
```

Khi session Admin active, `libraries/requick.php` luôn cập nhật `lastlogin` và `user_token`, kể cả trang nghiệp vụ chỉ dùng để xem. Vì vậy request Admin authenticated không phải read-only về database.

Tác động:

- Mỗi full-page request có thêm một auth `SELECT` và một auth `UPDATE` ngoài query nghiệp vụ.
- Token bị xoay theo từng page load.
- Hai request song song có thể cùng đọc token cũ rồi ghi hai token mới; session cuối cùng chỉ giữ một token, tạo khả năng cảnh báo concurrent-login giả hoặc lệch token ở request tiếp theo.
- Không thể dùng full-page authenticated request làm phép đo hoàn toàn read-only nếu chưa cô lập hoặc snapshot database.

Ảnh hưởng concurrency là suy luận trực tiếp từ thứ tự đọc–ghi trong code; chưa chạy test song song vì test đó sẽ ghi DB.

### 2. AJAX Admin dùng bootstrap khác — PARTIALLY VERIFIED

`administrator/api/config.php` không include `libraries/requick.php`, nên AJAX Admin không cập nhật `lastlogin` hoặc xoay `user_token` theo đường full-page.

Tuy nhiên, bootstrap AJAX gọi `Functions::checkLoginAdmin()`, thực hiện:

```sql
SELECT secret_key
FROM table_user
WHERE secret_key = ?
  AND FIND_IN_SET('hienthi', status)
```

Phân biệt chính xác:

- Full-page Admin: auth `SELECT` + auth `UPDATE`.
- AJAX Admin: ít nhất một auth `SELECT`, không xoay token.
- Login endpoint: cập nhật `login_session`, `lastlogin`, `user_token` và `secret_key`.

Nhận định “AJAX cũng đi qua bootstrap riêng” là đúng. Nhận định AJAX cũng cập nhật `lastlogin/user_token` như full-page là không được code hiện tại xác nhận.

### 3. Inventory bề mặt Admin và endpoint — VERIFIED

Static inventory tại thời điểm audit:

- 25 Admin source/controller.
- 70 Admin template `*_tpl.php`.
- 18 file trong `administrator/api/`, gồm bootstrap `config.php`.
- 13 public API endpoint.
- Handoff trước ghi nhận 65/65 route Admin đã render; audit này không render lại vì không muốn tạo auth write không cần thiết.

Hai endpoint legacy đã bị vô hiệu hóa bằng HTTP 404:

- `administrator/api/component.php`.
- `administrator/api/save_add_item.php`.

Các endpoint còn lại chủ yếu dùng session DB check; mutation chính đã có CSRF, permission và allowlist. Response contract vẫn không đồng nhất: JSON, HTML fragment, plain text, empty response và `die()`/`exit()` cùng tồn tại.

### 4. DB engine, index và ràng buộc — VERIFIED

`SHOW TABLE STATUS` xác nhận database local có 49 bảng và toàn bộ đều dùng MyISAM.

Index đáng chú ý:

- `table_product` và các bảng phân cấp route đã có `idx_route_type_slug` và `idx_type_numb_id` từ migration 007.
- `table_user` chỉ có primary key `id`.
- `table_order` chỉ có primary key `id`.
- `table_order_detail` chỉ có primary key `id`.
- Không có foreign key.

Hệ quả:

- Lookup AJAX theo `table_user.secret_key` không có index phù hợp. Local chỉ có một user nên chưa thể hiện chi phí scale.
- `table_order_detail` không có index cho `id_order` hoặc `id_product`.
- Quan hệ order/product/hierarchy chỉ được bảo vệ bằng code.
- Transaction PDO không cung cấp rollback thực trên MyISAM.

### 5. Checkout không atomic — VERIFIED

Trạng thái triển khai 2026-09-01: **MITIGATED ON LOCAL (INTERIM)** bởi ADM-P0-001. Checkout đã kiểm tra order và từng order detail, đọc lại product, dùng transaction best-effort và xóa bù trừ khóa theo order ID; email/thanks/cart clear chỉ chạy sau khi đủ N detail. Shutdown cleanup bảo vệ thêm đường PDODb `die()`. Atomicity thật vẫn chờ chuyển commerce sang InnoDB trong ADM-P1-004.

`sources/order.php` thực hiện tuần tự:

1. Insert `table_order`.
2. Lặp cart và insert từng `table_order_detail`.
3. Tiếp tục xử lý email và session.

Không có transaction thực và không kiểm tra thành công của từng insert detail. Nếu một lần insert detail lỗi, database có thể giữ order thiếu dòng hoặc thiếu một phần chi tiết.

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

- Mỗi full-page Admin có auth write ngoài query nghiệp vụ.
- Status API ở scope selected vẫn đọc rồi update theo từng ID, tối đa 500 ID.
- Gallery/sortable reorder update tuần tự theo từng row.
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

Khoảng trống quan trọng là invariant không thống nhất giữa CRUD thủ công và bulk importer, nổi bật ở quan hệ giá và discount. Validation không nên chỉ tồn tại trong từng controller riêng lẻ nếu nhiều đường có thể ghi cùng entity.

### 10. Error handling — VERIFIED

`class.PDODb.php` gọi `die()` khi connect, prepare hoặc query lỗi. Điều này tạo các vấn đề:

- API có thể trả HTML lỗi thay vì JSON contract.
- Controller không có exception boundary để rollback/bù trừ hoặc chọn HTTP status phù hợp.
- Data layer trực tiếp quyết định output HTTP.
- Negative-path integration test khó kiểm soát.

Các endpoint còn dùng nhiều `exit()` không body hoặc plain-text error. Chưa có error envelope, exception boundary hoặc API router chung.

### 11. Testability — VERIFIED

Điểm đã có:

- Regression scripts cho security, Admin readiness và bulk import.
- Product bulk có fault injection để test rollback bù trừ.
- Playwright phủ nhiều public interaction và visual baseline.
- Profiling authenticated bốn danh sách Admin đã có số liệu local.

Khoảng trống:

- Chưa có test auth token race/concurrent full-page request.
- Chưa có test checkout partial write hoặc idempotency.
- Chưa có contract test thống nhất cho lỗi API.
- Chưa có test xác nhận CRUD thủ công và bulk importer áp cùng invariant sản phẩm.
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
- Tài liệu auth chưa ghi việc full-page Admin xoay token và ghi `table_user` trên mỗi authenticated request.
- Tài liệu performance chưa tính auth write vào query/runtime profile.
- Tài liệu DB mô tả dump ban đầu có 48 bảng; DB local hiện có 49 bảng và route indexes từ migration 007.
- Chưa có tài liệu về invariant khác nhau giữa CRUD sản phẩm thủ công và bulk importer.
- Không có báo cáo Claude Admin modernization riêng để kiểm chứng toàn bộ. Các giả thuyết kỹ thuật tìm thấy trong status/handoff đã được đối chiếu trực tiếp với code và DB.

## Phân loại finding

### VERIFIED

- Full-page auth luôn đọc và cập nhật `table_user` khi session active.
- AJAX auth đọc `table_user` nhưng không chạy đường xoay token của `requick.php`.
- Toàn bộ 49 bảng local dùng MyISAM.
- Thiếu FK và thiếu index nghiệp vụ ở commerce/auth.
- Checkout không atomic.
- Có đúng một discount mismatch; không có price relation sai.
- Các orphan/duplicate đã kiểm tra đều bằng 0.
- API/error format không đồng nhất.

### PARTIALLY VERIFIED

- Nguy cơ token race/concurrent-login giả: được code path chứng minh về mặt thứ tự đọc–ghi, chưa chạy runtime concurrency test vì sẽ ghi DB.
- Capacity Admin: local không còn N+1 trên bốn danh sách đã đo, nhưng dataset quá nhỏ để suy rộng lên hosting.

### NOT VERIFIED

- Timing từ lần `curl -L` bị treo.
- Kết luận rằng AJAX Admin cũng cập nhật `lastlogin/user_token` như full-page.

### NEEDS IMPLEMENTATION INVESTIGATION

- Thiết kế heartbeat hoặc throttling thay cho auth write trên mọi page load.
- Quy tắc concurrent session/token rõ ràng và test race.
- Chuyển engine/index/FK theo migration có rollback.
- Checkout atomicity và idempotency.
- Hợp nhất validation/invariant CRUD và bulk.
- Exception boundary và API response contract.
- Batch/set-based update cho status/reorder khi phù hợp.

## Ưu tiên kỹ thuật đề xuất

1. Tách xác thực request khỏi việc xoay token; chỉ cập nhật activity theo khoảng thời gian hoặc cơ chế heartbeat có điều kiện.
2. Đưa invariant sản phẩm dùng chung vào một validator/service được cả CRUD và bulk gọi.
3. Thiết kế migration InnoDB + index/FK cho commerce trên clone/staging; không đổi engine trực tiếp production.
4. Làm checkout atomic và idempotent trước khi mở commerce production.
5. Chuẩn hóa error boundary/JSON response cho Admin API.
6. Bổ sung test concurrency, partial failure và contract test trước refactor lớn.
7. Profile lại Admin trên staging có dataset gần production.

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
