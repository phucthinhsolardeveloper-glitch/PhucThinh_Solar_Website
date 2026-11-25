# Bản chờ phát hành SEO & Sales — nhánh `feat/pts-seo-sales-20261125` (gốc `532da75d`)

**Chưa gộp, chưa push, chưa deploy, chưa ghi production.**
Trạng thái: **chưa đủ cổng để phát hành** — còn `nuxt build` và các bước trình duyệt trên code cuối.

## Thay đổi

1. Số điện thoại: lead, checkout, đánh giá nhận khoảng trắng / chấm / gạch / `+84`, lưu 10 chữ số; vẫn từ chối chữ và ký tự khác.
2. Form: hiện thông báo 422/429/419 của máy chủ; 419 thì lấy token mới và gửi lại đúng một lần.
3. GA4: `generate_lead` thêm `form_source`; thêm `estimator_result` (một lần mỗi lượt xem, chỉ khi khách tự đổi thông tin, không gửi tiền điện); `purchase.value` là tiền hàng, thêm `shipping`. `order_success.order_value` giữ nguyên là tổng đơn.
4. Tìm kiếm: khi cả cụm không ra kết quả thì khớp từng từ và đúng công suất kèm đơn vị. Truy vấn đang có kết quả không đổi.
5. SEO: bộ lọc sai và trang vượt cuối danh sách trả 404; trang thương hiệu từ trang 2 được index với canonical riêng; hai nhóm giải pháp có mô tả khác nhau.
6. Chuyển lead sang CRM: phía website, **mặc định tắt**, chưa có endpoint phía CRM (`2026-11-25-lead-zcrm-handoff.md`). Là commit riêng `088fc0b8`, bỏ commit này thì bỏ được tính năng.

## Review độc lập (so với `532da75d`)

Đã đọc code thay đổi của backend, frontend và test trong bốn commit `91873fad`..`e2a5ffdf`. Đã sửa ở `25e1d6a1`:

| Phát hiện | Sửa |
|---|---|
| Bộ lọc chỉ Laravel từ chối (ví dụ `need` lặp) trả 400, cầu Nitro đổi thành 502 | Hai cầu catalog và thương hiệu chuyển tiếp 400, trang trả 404 |
| `pattern` trên bốn form lead chặn số máy chủ chấp nhận (`+84 944 - 555 - 957`) | Nới `pattern` theo quy tắc máy chủ |
| `pim-registry.csv` xuống dòng CRLF làm `git diff --check` báo lỗi | Đổi sang LF, nội dung 337 dòng không đổi |

## Kiểm tra (máy cục bộ, database sao chép riêng)

| Cổng | Kết quả |
|---|---|
| `php artisan test` (code của `088fc0b8`) | **530 đạt, 2 bỏ qua, 1 lỗi**: `EditorialBatchTest` (volume upload máy thử có sẵn ảnh; cùng lỗi đã ghi khi kiểm bản `498ab8f1`, không chạy lại trên bản gốc trong đợt này). Chạy thành 5 đợt vì máy ít RAM |
| `NhatTinShippingTest` | Một lần lỗi 1 ca khi chạy chung nhóm vận chuyển, hai lần sau đạt; chưa tìm nguyên nhân |
| Pint | **Không phát sinh**: 6 file lỗi sẵn, giống bản gốc |
| PHPStan | **Không phát sinh**: 9 lỗi sẵn, giống bản gốc |
| `git diff --check 532da75d` | Đạt |
| `npm test` | **551/551 đạt** |
| `nuxi typecheck` | **Đạt**, không lỗi |
| `nuxt build` trên code cuối | **Chưa chạy xong**: hai lần bị dừng vì máy cạn RAM (hết 15 phút; sau đó bị hệ thống dừng). Bản `e2a5ffdf` đã build đạt ở phiên trước |
| Trình duyệt `consent-lead-purchase.cjs` (cookie, lead, đặt hàng, `purchase`, `estimator_result`, mã 404) | **Chưa chạy** (cần bản build) |
| Trình duyệt `shop-flow.cjs`, `lead-form-errors.cjs` | Đạt ở `e2a5ffdf` (phiên trước); **chưa chạy lại** sau `25e1d6a1` |
| `pts:snapshot` | Phiên trước: bộ snapshot lệch sẵn ở bản gốc, khác biệt chủ đích chỉ ở mô tả nhóm giải pháp. Chưa chạy lại |
| Chuyển lead CRM | Chỉ thử với endpoint giả trong test; **chưa nối CRM nào** |
| GA4/GTM, Merchant Center, Search Console, mail lead production | **Chưa kiểm** (không có quyền) |

## Còn lại trước khi phát hành

Chạy khi máy đủ RAM (nên tắt bớt phiên AI khác), trên database riêng:

1. `npx nuxi build` ở `frontend/`.
2. API và Nuxt cục bộ, GTM giả trong database thử, rồi `tools/visual-tests/audit/consent-lead-purchase.cjs` (hướng dẫn ở đầu file; script từ chối chạy ngoài localhost và `.test`).
3. `shop-flow.cjs` và `lead-form-errors.cjs`.

`consent-lead-purchase.cjs` mới viết, chưa chạy lần nào: có thể phải chỉnh selector.

## Khi phát hành

- Gồm cả backend và Nuxt. Không có migration.
- Lớp PHP mới (`SearchTerms`, `CrmLeadForwarding`): image backend dùng classmap authoritative, kiểu build chồng lớp phải chạy `composer dump-autoload --no-dev --classmap-authoritative` và `php artisan package:discover`.
- Nên phát hành backend trước hoặc cùng lúc. Nuxt mới chạy với backend cũ vẫn gửi `purchase` bằng tổng đơn.
- Không đặt `CRM_LEADS_*` trên production.
- Sau khi lên: thử tìm kiếm ba truy vấn lỗi; kiểm mã 404 của `?brand=abc`, `?page=999`; xem log lỗi và `php artisan pts:outbox`. Thử form hoặc đơn thật chỉ khi chủ website cho phép.
- Việc GA4, Merchant, Search Console: `2026-11-25-ga4-merchant-gsc-checklist.md`. Doanh thu `purchase` trong GA4 không còn gồm phí giao kể từ ngày phát hành.

## Rollback

Chạy lại image backend và Nuxt `498ab8f1`. Không có thay đổi cấu trúc dữ liệu. Lead và đơn tạo trong lúc bản mới chạy giữ số 10 chữ số, bản cũ đọc bình thường.

## Chờ chủ website hoặc bên ngoài

- Báo cáo CRO và kỹ thuật của THY: không có trên máy, phiên làm việc không có công cụ đọc qua Bridge.
- CRM: endpoint `POST /api/public/leads`, khóa phạm vi `leads.write`, CRM đích và người nhận lead.
- Sửa dữ liệu sản phẩm, bật feed Merchant xác minh: cần chứng từ (`2026-11-25-pim-registry/`).
- Case study, trang Đại lý, tiêu đề theo CTR, trang thương hiệu rỗng: cần số liệu Search Console và nội dung thật.
- Cam kết gọi lại (15 phút hay 2 giờ), bảo hành Sharp 550W, tồn kho/MPN/GTIN, chính sách cookie: chờ quyết định.
