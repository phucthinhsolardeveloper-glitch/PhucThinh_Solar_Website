# FE/BE gap audit — 2026-10-28 (Claude)

Phạm vi: rà soát gap FE (Nuxt preview, `feature/pts-nuxt-integration-20261027`
@ `2eab5e6`) ↔ BE (PHP) ngoài SEO parity (đã có báo cáo riêng ở
`SEO-PARITY-AUDIT-20261028.md`). Môi trường: worktree riêng
`pts-nuxt-fe-be-gap-20261028`, container PHP riêng `pts-php-nuxt-preview-claude88`
(port 8088, dừng sau audit), Nuxt dev riêng port 3105 (dừng sau audit).
Không đụng `pts-php81-local` (8080) hay worktree Codex.

## Kết luận chung

Không phát hiện FE-bug hay BE-gap nghiêm trọng mới trong các route/luồng đã
kiểm tra (catalog, PDP, lead-form adapter). Hai phát hiện dưới đây đều là
**minor/code-quality**, không chặn cutover.

## Phát hiện

### 1. [minor, code-quality] Lỗi allowlist origin bị che bởi catch chung

- File: `frontend/server/utils/migration-api.ts:34` (`allowedPort = origin.port
  === '8080' || origin.port === '8088'`) kết hợp với pattern `catch { throw
  createError({statusCode: 502, statusMessage: '... unavailable' }) }` lặp lại
  ở 12 file trong `frontend/server/routes/_migration/*.ts` (ví dụ
  `lead-form.get.ts:11`, `cart.get.ts`, `checkout.post.ts`, `review.post.ts`,
  `shipping-quote.post.ts`, `order-thanks.get.ts`, …).
- Tái hiện: khi `NUXT_PHP_ORIGIN` trỏ tới port ngoài allowlist (tự kiểm chứng
  với port 8089 trong lúc dựng môi trường audit), `migrationPhpEndpoint()` throw
  `500 Untrusted PHP migration origin` ngay trong hàm gọi PHP, nhưng catch
  chung ở route handler nuốt lỗi gốc và trả về `502` với message chung
  ("Lead form metadata unavailable" / tương tự ở các route khác) — không có
  cách nào từ response phân biệt "PHP origin bị chặn do allowlist" với "PHP
  thật sự down".
- Ảnh hưởng thực tế: KHÔNG ảnh hưởng production vì allowlist chỉ chấp nhận
  8080/8088 — đúng với giá trị chạy thật. Chỉ gây khó debug khi vận hành/QA
  dùng origin khác allowlist (như audit này gặp lúc đầu).
- Đề xuất (chưa tự sửa vì chạm 12 file, cần review phạm vi rộng hơn task này):
  giữ catch nhưng log lỗi gốc (ví dụ `console.error` phía server, không lộ ra
  client) trước khi trả 502, hoặc phân biệt lỗi 500 "Untrusted origin" ra khỏi
  nhóm "upstream unavailable".

### 2. [observation, không phải bug] `sort=price` đặt sản phẩm `regularPrice=null` lên đầu danh sách ascending

- File: `domains/phucthinhsolar.com/public_html/libraries/pts_public_catalog_api.php:173`
  (`case 'price': return $pricesVisible ? 'p.regular_price asc' : null;`).
- Hành vi: MySQL `ORDER BY ... ASC` xếp `NULL` trước mọi giá trị số, nên sản
  phẩm không có giá công khai (`regularPrice: null`) hiển thị đầu tiên khi sort
  tăng dần. Đã xác minh bằng dữ liệu thật (sản phẩm đầu tiên trong tập test có
  `regularPrice: null`).
- Đây là hành vi SQL mặc định, code đã có comment xác nhận chủ ý "không tự suy
  đoán sắp xếp theo giá ẩn" — không phải lỗi cần sửa, chỉ ghi lại để UX/PM biết
  nếu muốn đổi thứ tự (ví dụ đẩy null xuống cuối bằng `ORDER BY regular_price
  IS NULL, regular_price ASC`) thì là quyết định sản phẩm, không phải bug kỹ
  thuật.

## Đã kiểm tra PASS (không lặp lại trong SEO audit)

- Catalog API (`pts_v1_catalog.php`): filter `brand` theo id, `sort=price`
  (giá trị hợp lệ theo whitelist `default/abc/abc-desc/price/price-desc/new/new-desc`,
  không phải `price_asc` như đoán ban đầu — đã tự sửa test, không phải bug),
  pagination, price ranges — khớp dữ liệu PHP thật.
- PDP preview (`migration-product-preview`): giá hiển thị `1.235.000` khớp
  đúng `regularPrice` từ API catalog; route trả 200, nội dung đầy đủ (so với
  `LOCAL-PREVIEW.md` đã ghi).
- Lead-form adapter (`contact`/`dealer`): GET `/_migration/lead-form` trả đúng
  `csrfToken` + danh sách tỉnh/thành từ PHP thật (`pts_v1_lead_form.php`); POST
  với CSRF token giả bị PHP/adapter từ chối đúng (400 Invalid lead form
  security data) — xác nhận adapter thực sự gọi qua PHP, không phải no-op.
- `pts_v1_product_detail.php` + `pts_v1_product_extras.php`: warranty, related
  (kèm pricing từng related item), reviews, consultation, commitments đều có
  dữ liệu thật; giá của sản phẩm chính không nằm trong 2 endpoint này mà lấy từ
  catalog API — đúng kiến trúc "catalog = pricing authority" đã ghi trong
  `NEXT-EXECUTION-PLAN.md`, không phải thiếu field.

## Ghi chú ngoài phạm vi

- `feature/pts-nuxt-gallery-video-20261027` chưa merge vào integration — thuộc
  task khác (Codex/gate riêng), không audit trong lượt này.
- Không chạy Playwright/visual diff (console error, overflow, screenshot) do
  giới hạn thời gian của lượt audit này — nếu cần, nên giao task riêng dùng
  `tools/visual-tests/scripts/migration-compare.cjs` đã có sẵn trong
  `NEXT-EXECUTION-PLAN.md`.
