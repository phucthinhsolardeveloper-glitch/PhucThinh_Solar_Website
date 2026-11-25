# Bản chờ phát hành SEO & Sales — nhánh `feat/pts-seo-sales-20261125` (gốc `532da75d`)

**Chưa push, chưa deploy, chưa ghi production.**

## Thay đổi

1. Số điện thoại: lead, checkout, đánh giá nhận khoảng trắng / chấm / gạch / `+84`, lưu 10 chữ số; vẫn từ chối chữ và ký tự khác.
2. Form: hiện thông báo 422/429/419 của máy chủ; 419 thì lấy token mới và gửi lại đúng một lần.
3. GA4: `generate_lead` thêm `form_source`; thêm `estimator_result` (một lần mỗi lượt xem, chỉ khi khách tự đổi thông tin, không gửi tiền điện); `purchase.value` là tiền hàng, thêm `shipping`. `order_success.order_value` giữ nguyên là tổng đơn.
4. Tìm kiếm: khi cả cụm không ra kết quả thì khớp từng từ và đúng công suất kèm đơn vị. Truy vấn đang có kết quả không đổi.
5. SEO: bộ lọc sai và trang vượt cuối danh sách trả 404; trang thương hiệu từ trang 2 được index với canonical riêng; hai nhóm giải pháp có mô tả khác nhau.

## Kiểm tra (local, database test)

| Cổng | Kết quả |
|---|---|
| `php artisan test` | 515 đạt, 2 bỏ qua, 1 lỗi: `EditorialBatchTest` (máy thử có sẵn ảnh trong volume upload; lỗi có từ bản trước) |
| Pint, PHPStan | Có lỗi sẵn; kết quả giống hệt khi chạy trên `532da75d` |
| `pts:snapshot` | Bộ snapshot đã lệch sẵn ở bản gốc. So với bản gốc: khác chủ đích ở mô tả nhóm giải pháp; khác biệt ảnh do bản đối chiếu không mount assets. Không `--save` |
| `npm test` | 551/551 đạt |
| `nuxi typecheck`, `nuxt build` | Đạt |
| Trình duyệt: `shop-flow.cjs` | Đạt (sản phẩm → giỏ → trang thanh toán; không gửi đơn) |
| Trình duyệt: `lead-form-errors.cjs` | Đạt 5/5 (request lead được giả lập) |
| `estimator_result` | Đạt khi bật tạm công cụ ở database test rồi trả lại |
| `purchase` trên trình duyệt | **Chưa chạy**; mới có test API và test hàm tính |
| GA4/GTM thật, Search Console, mail lead production | **Chưa kiểm** (không có quyền) |
| Review chéo độc lập | **Chưa có** |

## Khi phát hành

- Gồm cả backend và Nuxt. Không có migration.
- Có lớp PHP mới (`SearchTerms`): image backend dùng classmap authoritative, kiểu build chồng lớp phải chạy `composer dump-autoload --no-dev --classmap-authoritative`.
- Nên phát hành backend trước hoặc cùng lúc. Nuxt mới chạy với backend cũ vẫn gửi `purchase` bằng tổng đơn.
- Sau khi lên: gửi một lượt tìm kiếm cho ba truy vấn lỗi; kiểm mã 404 của `?brand=abc`, `?page=999`; xem log lỗi. Thử form hoặc đơn thật chỉ khi chủ website cho phép.
- GA4: đăng ký custom dimension `form_source`, `building_type`; kiểm trong GTM có thẻ nào dùng `purchase.value` như tổng thanh toán không; đánh dấu `generate_lead` là key event.
- Doanh thu `purchase` trong GA4 sẽ không còn gồm phí ship kể từ ngày phát hành.

## Rollback

Chạy lại image backend và Nuxt `498ab8f1`. Không có thay đổi cấu trúc dữ liệu. Lead và đơn tạo trong lúc bản mới chạy giữ số 10 chữ số, bản cũ đọc bình thường.

## Chưa làm

- `THY-CRO-AUDIT.md`: không tìm thấy trên máy; Bridge cần đăng nhập nên chưa đọc.
- Nối ZCRM: mới thiết kế (`2026-11-25-lead-zcrm-handoff.md`).
- Sửa dữ liệu sản phẩm, bật feed Merchant xác minh: chờ chứng từ (`2026-11-25-pim-registry/`).
- Nội dung case study, trang Đại lý, tiêu đề theo CTR, trang thương hiệu rỗng: chờ số liệu GSC và chủ website.
- Chính sách lưu mã click quảng cáo khi khách từ chối cookie: chờ quyết định.
