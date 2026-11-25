# Bản chờ phát hành SEO & Sales — nhánh `feat/pts-seo-sales-20261125` (gốc `532da75d`)

**Chưa gộp, chưa push, chưa deploy, chưa ghi production.**
Trạng thái: **build, test và ba bài thử trình duyệt trên code cuối đã đạt (cục bộ), chờ chủ website cho phép phát hành.** Chưa kiểm: `pts:snapshot`, tài khoản ngoài, CRM thật.

## Thay đổi

1. Số điện thoại: lead, checkout, đánh giá nhận khoảng trắng / chấm / gạch / `+84`, lưu 10 chữ số; vẫn từ chối chữ và ký tự khác.
2. Form: hiện thông báo 422/429/419 của máy chủ; 419 thì lấy token mới và gửi lại đúng một lần.
3. GA4: `generate_lead` thêm `form_source`; thêm `estimator_result` (một lần mỗi lượt xem, chỉ khi khách tự đổi thông tin, không gửi tiền điện); `purchase.value` là tiền hàng, thêm `shipping`. `order_success.order_value` giữ nguyên là tổng đơn.
4. Tìm kiếm: khi cả cụm không ra kết quả thì khớp từng từ và đúng công suất kèm đơn vị. Truy vấn đang có kết quả không đổi.
5. SEO: bộ lọc sai và trang vượt cuối danh sách trả 404; trang thương hiệu từ trang 2 được index với canonical riêng; hai nhóm giải pháp có mô tả khác nhau.
6. Chuyển lead sang CRM: phía website, **mặc định tắt**, chưa có endpoint phía CRM (`2026-11-25-lead-zcrm-handoff.md`). Gồm hai commit `088fc0b8` và `7945c32d` (gửi bù), bỏ cả hai thì bỏ được tính năng.

## Review độc lập (so với `532da75d`)

Đã đọc code thay đổi của backend, frontend và test trong bốn commit `91873fad`..`e2a5ffdf`. Đã sửa ở `25e1d6a1`:

| Phát hiện | Sửa |
|---|---|
| Bộ lọc chỉ Laravel từ chối (ví dụ `need` lặp) trả 400, cầu Nitro đổi thành 502 | Hai cầu catalog và thương hiệu chuyển tiếp 400, trang trả 404 |
| `pattern` trên bốn form lead chặn số máy chủ chấp nhận (`+84 944 - 555 - 957`) | Nới `pattern` theo quy tắc máy chủ |
| `pim-registry.csv` xuống dòng CRLF làm `git diff --check` báo lỗi | Đổi sang LF, nội dung 337 dòng không đổi |

Phiên Sol (người thứ hai) review chỉ đọc `25e1d6a1` và `088fc0b8`, sau đó xem thêm consent/tracking (không thấy dữ liệu khách trong `generate_lead`, `purchase`; thẻ tùy chỉnh trong GTM vẫn phải kiểm có tôn trọng consent khi chạy thật):

| Phát hiện của Sol | Xử lý |
|---|---|
| Vừa: lead lưu xong mà ghi outbox lỗi thì không bao giờ tới CRM | `7945c32d`: lượt quét outbox xếp bù, từ mốc bắt buộc `CRM_LEADS_SINCE` |
| Nhẹ: tìm theo từng từ được quyết trước khi áp lọc hãng/giá, nên cụm khớp nhưng bị lọc hết sẽ ra 0 | Giữ nguyên có chủ đích: từ khóa quyết định kiểu khớp, bộ lọc chỉ thu hẹp. Ghi chú trong code |

`7945c32d` **chưa có người thứ hai review**; nó chỉ chạy khi bật CRM.

## Kiểm tra (máy cục bộ, database sao chép riêng)

| Cổng | Kết quả |
|---|---|
| `php artisan test` (code `7945c32d`) | **537 đạt, 2 bỏ qua, 1 lỗi** do máy thử: `EditorialBatchTest` đòi volume upload không có ảnh mà volume thử có. Chạy riêng file này với thư mục upload trống: 2/2 đạt |
| `NhatTinShippingTest` lỗi chập chờn | **Đã tìm nguyên nhân, chưa sửa** (có sẵn, ngoài phạm vi nhánh): `NtlMock` đặt giờ hành trình theo `now()`, hai lần `refresh` rơi vào hai giây khác nhau tạo 4 sự kiện thay vì 2. Tái hiện được bằng cách tua 1 giây. Chỉ ảnh hưởng chế độ giả lập |
| Pint | Không phát sinh: bản trước đó giống bản gốc (6 file lỗi sẵn); các file sửa thêm đạt |
| PHPStan | Không phát sinh: 9 lỗi sẵn, giống bản gốc |
| `git diff --check 532da75d` | Đạt |
| `npm test` | 551/551 đạt (frontend không đổi từ đó) |
| `nuxi typecheck` | Đạt (frontend không đổi từ đó) |
| `nuxt build` | **Đạt** (48 giây) |
| Trình duyệt `consent-lead-purchase.cjs` | **Đạt cả 10 kiểm tra**: cookie từ chối/đồng ý giữ qua tải lại; lead thật vào database thử, một `generate_lead`; đặt đơn, một `purchase` = tiền hàng, có `shipping`, tải lại không gửi lần hai, không có dữ liệu khách; `estimator_result` một lần; 13 địa chỉ đúng mã 200/404; không lỗi 5xx. Lần chạy đầu script dừng vì không phân giải được tên `.test` ngoài trình duyệt, đã sửa script. `estimator_result` cần bật công cụ ước tính trong database thử |
| Trình duyệt `shop-flow.cjs`, `lead-form-errors.cjs` | **Đạt** |
| `pts:snapshot` | Chưa chạy lại trong đợt này. Phiên trước: bộ snapshot lệch sẵn ở bản gốc, khác biệt chủ đích chỉ ở mô tả nhóm giải pháp |
| Chuyển lead CRM | `CrmLeadForwardingTest` 22/22 với endpoint giả; **chưa nối CRM nào** |
| GA4/GTM, Merchant Center, Search Console, mail lead production | **Chưa kiểm** (không có quyền) |

Môi trường thử: container, database sao chép và mạng riêng, GTM giả, chặn máy chủ Google trong trình duyệt, mail không gửi, vận chuyển giả lập; đã xóa sau khi chạy.

## Khi phát hành

- Cần chủ website cho phép. `master` vẫn ở gốc `532da75d`, gộp được bằng fast-forward (kiểm lại lúc gộp).
- Gồm cả backend (kèm worker, scheduler cùng image) và Nuxt. Không có migration.
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
