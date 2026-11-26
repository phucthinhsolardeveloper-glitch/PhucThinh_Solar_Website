# Đợt email, phiếu đơn hàng, đo lường và SEO — nhánh `feat/pts-commerce-growth-20261126`

Gốc `cc5011b6` (bản chờ phát hành, giữ nguyên). **Chưa gộp, chưa push, chưa deploy, chưa ghi production, chưa gửi email hay đơn thật.** Web thật chạy `498ab8f1`.

## Kết luận

| Phần | Trạng thái |
|---|---|
| `cc5011b6` | 3 bài trình duyệt chạy lại ngày 26/11 trên đúng commit: **PASS**. Phát hành được độc lập với nhánh này |
| Nhánh này | Test cục bộ ở bảng dưới; **chờ người thứ hai review và chủ website cho phép** |
| Gửi email thật, GA4/GTM, Merchant, Search Console | NOT RUN |
| Hóa đơn MISA | Chỉ thiết kế: `../08-integrations/misa-invoice-handoff.md` |

## Cấu hình web thật (đọc ngày 26/11, chỉ các cờ không bí mật)

| Mục | Giá trị | Nghĩa |
|---|---|---|
| Mail | `smtp`, có người nhận quản trị | Đã cấu hình; chưa xác nhận thư tới hộp nhận |
| VNPay | tắt | Không thanh toán online |
| Nhất Tín | `mock`, chưa mở production | Giả lập; code chặn tạo vận đơn giả trên production |
| reCAPTCHA | bật | |
| Phí giao theo đơn | bật | Đơn không tính được phí ghi "Chờ xác nhận" |
| CRM | chưa đặt | Tắt |
| Đĩa máy chủ | 81%, còn 23 GB | Đủ để phát hành; theo dõi |

Tài khoản nhận chuyển khoản trên web thật: **chưa kiểm** (nằm trong database).

## Phiếu đơn hàng và email

- Khi khách đặt, nội dung phiếu được lưu vào `order_documents` (có mã kiểm tra). Đổi giá, tên hàng, thông tin công ty không làm đổi phiếu cũ. Không lưu file: PDF dựng khi cần, hai lần dựng chỉ khác mã định danh file.
- Admin → đơn hàng: nút **Phiếu PDF**, mục **Email và phiếu đơn hàng** (đã xếp gửi gì, tình trạng). "Máy chủ mail đã nhận" không có nghĩa khách đã nhận.
- Đơn cũ không có phiếu lưu: dựng từ dữ liệu hiện tại và ghi rõ trên phiếu.
- Admin → Hệ thống → Mẫu email: 4 công tắc, **mặc định tắt**, đổi thì ghi nhật ký:

| Công tắc | Gửi khi | Không gửi khi |
|---|---|---|
| Đính kèm phiếu | Email xác nhận đặt hàng gửi khách | Email gửi cửa hàng (không bao giờ đính kèm) |
| Đơn được xác nhận | Trạng thái chuyển "Đã xác nhận" | Email khách không hợp lệ |
| Đang giao | Trạng thái chuyển "Đang giao" | Đơn có vận đơn giả lập hoặc thử |
| Đã nhận tiền | Nhân viên xác nhận tiền đơn thủ công | VNPay (đã có email xác nhận lúc nhận tiền) |

Mỗi đơn tối đa một email mỗi bước. Bật công tắc không gửi lại việc đã xảy ra. Đơn hủy, hoàn tất, hoàn tiền không có email mới.

- Phiếu hoặc email lỗi: ghi log không kèm dữ liệu khách, đơn vẫn lưu, email xác nhận vẫn đi (không đính kèm).
- Thư viện: `dompdf/dompdf` 3.1.6 (LGPL-2.1) và 4 gói phụ (MIT, LGPL); không tải tài nguyên ngoài, không chạy PHP/JS trong phiếu; font DejaVu có tiếng Việt. `composer audit` báo một cảnh báo ở `league/csv`, gói có sẵn từ trước, không do đợt này thêm.
- Quyền: tải phiếu cần quyền đơn hàng; phiếu mẫu cần quyền cài đặt; trả `no-store, private`.

## Đo lường (GA4/GTM)

| Sự kiện | Khi nào | Tham số | Luồng |
|---|---|---|---|
| `view_item`, `add_to_cart`, `begin_checkout` | Có sẵn | sản phẩm, giá đang hiện | Bán lẻ |
| `purchase` | Một lần trên trang cảm ơn | giá trị = tiền hàng, `shipping` riêng | Bán lẻ |
| `lead_form_start` **mới** | Khách gõ hoặc chọn trong form, một lần mỗi form | `form_source`, `form_kind`, `page_path` | Tư vấn |
| `lead_form_submit` **mới** | Mỗi lần gửi | như trên | Tư vấn |
| `lead_form_error` **mới** | Gửi không thành | thêm `error_type`: `validation`, `session`, `rate_limit`, `server`, `network`, `other` | Tư vấn |
| `generate_lead` | Máy chủ đã nhận, một lần | `form_source`, `form_kind`, `page_path` | Tư vấn; `form_source=cart_quote` là xin báo giá giỏ hàng |
| `estimator_result` | Có sẵn | loại công trình, có pin | Tư vấn |
| `click_call`, `click_zalo` | Bấm số hoặc Zalo | trang | **Chỉ là ý định**, không phải cuộc gọi hay đơn |

Lý do thêm 3 sự kiện: biết khách bỏ form ở bước nào và gửi lỗi vì sao. Không sự kiện nào chứa tên, số điện thoại, email, nội dung.
Nguồn quảng cáo đã được giữ ở lead (`source_url`, 30 ngày trên thiết bị). **Đơn hàng không lưu nguồn**; lưu hay không phụ thuộc quyết định cookie còn treo.

### Việc chủ website làm trong GA4/GTM (chưa làm)

1. GA4 → Custom definitions: thêm chiều sự kiện `form_source`, `form_kind`, `error_type`.
2. Key events: `generate_lead`, `purchase`. Không đặt `click_call`, `click_zalo`, `lead_form_*` làm key event.
3. GTM: **không** tạo thẻ GA4 Event trùng tên (code đã gửi thẳng). Thẻ quảng cáo thêm sau phải kiểm consent.
4. Enhanced measurement "Form interactions" tự sinh `form_start`, `form_submit`: tên khác nên không đếm trùng; tắt nếu thấy rối.
5. Dùng DebugView thử một form sau khi phát hành.
6. Phễu: tư vấn = `lead_form_start` → `lead_form_submit` → `generate_lead`; bán lẻ = `view_item` → `add_to_cart` → `begin_checkout` → `purchase`. Chốt đơn tư vấn vẫn ghi tay.

## SEO

Chi tiết và số đo: `2026-11-26-seo-crawl-verification.md`.

- Tên thương hiệu trên trang sản phẩm và tường logo trang Giới thiệu dẫn tới trang thương hiệu.
- Danh mục chưa có mô tả riêng được mô tả bằng số sản phẩm, nhóm, thương hiệu thật.
- Hết liên kết đi qua chuyển hướng do code sinh; phân trang bài viết index được; thư viện trống không vào sitemap.
- Điện thoại: nút "Mua ngay" và "Nhận báo giá" dưới cùng bấm được ở trang đã thử (390 px). Không cần sửa.

## Kiểm tra

Máy cục bộ, bản sao database riêng (xóa sau khi chạy), mail không gửi ra ngoài, vận chuyển giả lập, VNPay và CRM tắt.

| Cổng | Code | Kết quả |
|---|---|---|
| 3 bài trình duyệt trên bản chờ phát hành | `cc5011b6` | **PASS** |
| `php artisan test` toàn bộ (96 file, 5 đợt tuần tự) | `b2e7f65f` | **PASS** 558 đạt, 2 bỏ qua, 0 lỗi |
| Test liên quan sau commit sửa liên kết thẻ | `70f09a1e` | **PASS** 19 đạt |
| Pint file PHP thay đổi | `70f09a1e` | **PASS** |
| PHPStan | `70f09a1e` | **Không phát sinh**: còn 9 lỗi có sẵn |
| `npm test`, `nuxi typecheck`, `nuxt build` | frontend `ce76fc88` | **PASS** 561/561; 0 lỗi; build được |
| `consent-lead-purchase.cjs` (11 kiểm tra, gồm phễu form) | `ce76fc88` | **PASS** |
| `shop-flow.cjs`, `lead-form-errors.cjs` (6), `growth-links.cjs` (6) | `ce76fc88` | **PASS** |
| So API với `cc5011b6` (`pts:snapshot`, cùng database) | `70f09a1e` | **PASS** 1.386/1.754 giống; 368 khác đúng chủ đích: `product.brandUrl` 349, `article.category.url` 12, liên kết thẻ danh mục 5, `brandPath` 1, menu 1 |
| Bản cũ gửi thư do bản mới xếp hàng (đường lùi) | cũ `cc5011b6` | **PASS** 3 loại thư gửi được (mailer giả), phiếu đính kèm bị bỏ |
| Phiếu PDF, email, màn Admin | dữ liệu giả | Đã xem ảnh chụp; 1 và 3 trang, không tràn ngang trên 390 px |
| Review của người thứ hai | — | **Chưa có** |
| Gửi SMTP thật, GA4/GTM, Lighthouse, trợ năng | — | **NOT RUN** |

Bản xem trước (dữ liệu giả) trên máy chủ website: `C:\Users\ADMIN\Downloads\pts-xem-truoc-26-11\`; tạo lại bằng `php artisan pts:mail-preview <thư mục>`.

Commit: `e05b80f7` phiếu và email, `3a40044b` sự kiện form, `38c5d9c5` thương hiệu và mô tả danh mục, `ce76fc88` liên kết và phân trang, `b2e7f65f` script kiểm, `70f09a1e` giữ liên kết còn sống.

## Phát hành

- Cần chủ website cho phép. Nên phát hành `cc5011b6` trước, theo dõi, rồi mới tới nhánh này.
- **Có migration** (thêm bảng `order_documents`): sao lưu database, chạy `php artisan migrate --force`.
- **Thêm thư viện PHP**: image backend phải build lại từ `composer.lock` (`composer install`), không dùng kiểu chồng lớp code lên image cũ.
- Backend, worker, scheduler cùng image. Nuxt cũ và mới chạy được với cả backend cũ và mới.
- Sau khi lên: mở "Xem phiếu đơn hàng mẫu"; đặt một đơn thử khi được đồng ý (công tắc vẫn tắt), tải Phiếu PDF; kiểm sitemap không còn `/thu-vien-anh/`, `/video/` nếu vẫn trống; bật "Đính kèm phiếu" khi đã xem email thật.
- Rollback: chạy lại image trước đó. Bảng `order_documents` để nguyên, code cũ không đọc. Thư đã xếp hàng vẫn được bản cũ gửi, nhưng **không kèm phiếu**; nên để hàng đợi gửi hết trước khi lùi (`php artisan pts:outbox`).

## Còn lại và rủi ro

- Chưa gửi email thật: chưa biết thư có đính kèm vào hộp đến hay spam.
- Thông tin công ty trên phiếu lấy từ Admin; chủ website cần xem phiếu mẫu.
- Thẻ danh mục có liên kết cũ được đổi theo bảng chuyển hướng trong Admin (trừ danh mục còn sản phẩm).
- Phí giao bằng 0 luôn hiện "Chờ xác nhận", kể cả khi thật sự miễn phí (giữ cách hiểu hiện có).
- Trang thương hiệu rỗng, địa chỉ công ty, tồn kho, GTIN, bảo hành: chờ chủ website. Đánh giá sản phẩm đã được xác nhận là khách thật (26/11).
