# Đợt email, phiếu đơn hàng, đo lường và SEO — nhánh `feat/pts-commerce-growth-20261126`

Gốc `cc5011b6` (bản chờ phát hành, giữ nguyên). **Chưa gộp, chưa push, chưa deploy, chưa ghi production, chưa gửi email hay đơn thật.** Web thật chạy `498ab8f1`.

## Kết luận

| Phần | Trạng thái |
|---|---|
| `cc5011b6` | 3 bài trình duyệt chạy lại ngày 26/11 trên đúng commit: **PASS**. Phát hành được độc lập với nhánh này |
| Nhánh này (code `3aa0225e`) | Test cục bộ **PASS**, review người thứ hai **PASS**. Chờ chủ website cho phép phát hành |
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

## Rà soát vận hành trong đợt chốt

| Phát hiện | Mức | Xử lý |
|---|---|---|
| Chữ dài không có dấu cách (email, tên, địa chỉ, yêu cầu, tên hàng) đẩy cột giá ra ngoài phiếu PDF | Cao | Sửa `27d74562`, test đo mép phải (bản cũ 5.100 pt, trang 595 pt) |
| Cùng dữ liệu làm email rộng gấp nhiều lần màn hình (có từ trước) | Vừa | Sửa `d548d487`, đo 390 px không tràn |
| Ảnh logo cực lớn có thể làm hết bộ nhớ lúc đặt hàng | Vừa | Bỏ ảnh trên 8 triệu điểm ảnh, `27d74562` |
| Tài khoản đã khóa còn đăng nhập vẫn mở được phiếu và trang in đơn (trang in có từ trước) | Vừa | Chặn, `27d74562` |
| `league/csv` 9.29.0 có cảnh báo từ chối dịch vụ ở bộ đổi bảng mã; chỉ chức năng Import của Filament dùng, website không dùng | Thấp | Nâng đúng một bản vá 9.29.1, `90b4bf93`; `composer audit` sạch |
| Chữ nhạt ở bước đặt hàng, giỏ hàng, 3 liên kết vàng (2,4–4,3:1) | Thấp | Đậm lên tối thiểu 4,5:1, `4192367e` |

Đã xem, không cần sửa: mỗi bước một email nhờ khóa chống trùng; gửi lỗi thử lại tối đa 6 lần, thất bại hiện ở "Việc cần làm"; payload lưu trong cột `longtext`; phiếu không nêu trạng thái thanh toán và ghi rõ không thay hóa đơn GTGT; phí Nhất Tín giả lập không bao giờ hiện cho khách trên production.
Còn lại: email khách gõ sai sẽ gửi đơn tới người khác (như hiện nay); chưa có nút gửi lại email thất bại.

## Bật thanh toán và vận chuyển (chủ website; chưa làm gì)

| Mục | Cần trước khi bật | Cách kiểm |
|---|---|---|
| Chuyển khoản | Admin → Thông tin website: ngân hàng, số tài khoản, tên chủ tài khoản (chỉ tài khoản chủ sửa được) | Một đơn thử được đồng ý, quét QR, đối chiếu tên và số tiền, hủy đơn |
| Phí giao hàng | Admin → Phí giao hàng; phường/xã để 0 sẽ ghi "Chờ xác nhận" | Chọn vài địa chỉ ở trang thanh toán |
| Email | Gửi thử từ Admin → Mẫu email tới hộp thư của mình; xem hộp đến và spam; kiểm thư khách trả lời về đâu | Có thư |
| VNPay (đang tắt) | Hợp đồng, mã merchant, khóa, địa chỉ return và IPN; thử sandbox trước; đặt biến `VNPAY_*` trên máy chủ | Đơn sandbox: về trang cảm ơn, đơn chuyển "đã thanh toán" một lần |
| Nhất Tín (đang giả lập) | Tài khoản API, đối chiếu mã tỉnh/phường, cân nặng sản phẩm; sandbox; sau cùng mới `NTL_MODE=production`, `NTL_PRODUCTION_UNLOCKED=1`, `NTL_WRITE_OPERATIONS_ENABLED=1` | Vận đơn sandbox đúng địa chỉ và cước |

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
Nguồn quảng cáo đã được giữ ở lead (`source_url`, 30 ngày trên thiết bị, kể cả khi khách từ chối cookie: quyết định này vẫn treo). **Đơn hàng không lưu nguồn, giữ tắt.**
Phương án an toàn khi chủ website quyết: chỉ khi khách bấm "Đồng ý", lưu `utm_source`, `utm_medium`, `utm_campaign` (không lưu mã click) vào một cột của đơn; từ chối thì để trống.

`click_zalo` có thể xuất hiện cùng sự kiện bấm liên kết ra ngoài của Enhanced measurement; hai tên khác nhau, đừng cộng lại.

### Ghi kết quả bán hàng, dùng Admin sẵn có

Admin → Liên hệ & tư vấn: giao **Người phụ trách**, đặt **Hẹn theo dõi**, đổi tình trạng. Khi đóng, dòng đầu **Ghi chú xử lý** ghi theo mẫu:
`KQ: chốt | mất | đã báo giá — <giá trị, đồng> — <lý do hoặc mã đơn>`.
Mỗi tuần đọc lại cùng email báo cáo tuần. Thêm trường kết quả và giá trị chỉ khi mẫu này đã được dùng đều.

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
- Hết liên kết đi qua chuyển hướng do code sinh; phân trang bài viết index được; thư viện trống và trang thương hiệu chưa có sản phẩm không vào sitemap, `noindex` đến khi có nội dung.
- Điện thoại: nút "Mua ngay" và "Nhận báo giá" dưới cùng bấm được ở trang đã thử (390 px). Không cần sửa.

## Kiểm tra

Code cuối `13bced76`; các commit sau nó chỉ sửa tài liệu. Máy cục bộ, bản sao database riêng (đã xóa), mail không gửi ra ngoài, vận chuyển giả lập, VNPay và CRM tắt.

| Cổng | Kết quả |
|---|---|
| 3 bài trình duyệt trên bản chờ phát hành `cc5011b6` | **PASS** |
| `php artisan test` toàn bộ (96 file, 5 đợt tuần tự) | **PASS** 563 đạt, 2 bỏ qua, 0 lỗi. Lần chạy đầu đợt 2 lỗi 1 test do cache view còn bản phiếu cũ mà bước kiểm chứng test đã biên dịch; xóa cache (`view:clear`) chạy lại cả đợt thì đạt |
| Pint trên file PHP thay đổi | **PASS** |
| PHPStan | **Không phát sinh** (9 lỗi có sẵn, chạy lại trên code cuối) |
| `composer audit` | **PASS** không còn cảnh báo |
| `npm test`, `nuxi typecheck`, `nuxt build` | **PASS** 562/562; 0 lỗi; build được |
| Trình duyệt: `consent-lead-purchase` (11), `shop-flow`, `lead-form-errors` (6), `growth-links` (6), `lead-attribution` | **PASS**. `lead-attribution` sửa script đọc nơi lưu cũ; hành vi web không đổi |
| `a11y-basics.cjs` 7 trang | **PASS** |
| Crawl lại bản trên máy | **PASS** 457/457; 0 liên kết chuyển hướng hoặc hỏng |
| So API với `cc5011b6` (`pts:snapshot`, cùng database; bản gốc với chính nó 1.754/1.754) | **PASS** 1.386 giống; 368 khác đúng chủ đích: `product.brandUrl` 349, `article.category.url` 12, liên kết thẻ danh mục 5, `brandPath` 1, menu 1 |
| Migration lên, lùi, lên | **PASS** |
| Bản `cc5011b6` gửi 3 loại thư do bản mới xếp hàng | **PASS** (mailer giả), phiếu đính kèm bị bỏ |
| Phiếu, email, màn Admin | Đã xem ảnh chụp, gồm trường hợp chữ rất dài |
| Review của người thứ hai (chỉ đọc) | `cc5011b6..6971212d`: 1 lỗi vừa (sự kiện form bỏ sót nút chọn và lần gửi bị chặn), sửa ở `3aa0225e`; xem lại `6971212d..3aa0225e`: **PASS**, không còn lỗi cao hay vừa. Review không thay việc chủ website cho phép phát hành |
| Gửi SMTP thật, GA4/GTM/Merchant, Lighthouse, trình đọc màn hình | **NOT RUN** |

Bản xem trước (dữ liệu giả) trên máy chủ website: `C:\Users\ADMIN\Downloads\pts-xem-truoc-26-11\`; tạo lại bằng `php artisan pts:mail-preview <thư mục>`.

Commit: `e05b80f7` phiếu và email, `3a40044b` sự kiện form, `38c5d9c5` thương hiệu và danh mục, `ce76fc88` liên kết và phân trang, `70f09a1e`, `95feff83` trang thương hiệu trống, `27d74562` sửa phiếu và quyền, `90b4bf93` league/csv, `d548d487` email chữ dài, `4192367e` tương phản, `b2e7f65f`, `13bced76` script kiểm.

## Trợ năng và tốc độ (máy cục bộ, điện thoại 390 px, chưa có cache)

`a11y-basics.cjs` trên trang chủ, danh sách, danh mục, sản phẩm, giỏ, thanh toán, liên hệ: 0 ô nhập hay nút không tên, 0 ảnh thiếu `alt`, 0 tràn ngang, 0 chữ dưới chuẩn tương phản trên nền trơn, các điểm dừng Tab đầu tiên đều thấy được. Chữ nằm trên ảnh và bên trong khung nhúng **chưa đo**; chưa thử trình đọc màn hình.
LCP đo trên máy 0,26–1,9 giây, CLS 0, tải 0,9–2,0 MB mỗi trang: **số phòng thử, không phải số người dùng thật**. Lighthouse: NOT RUN (không có công cụ).
Chủ website xem Search Console → Core Web Vitals (di động và máy tính) trước và 28 ngày sau khi phát hành.

## HSTS và cache (chỉ đề xuất, chưa đổi)

Web thật không gửi HSTS; HTTP đã chuyển 308; `/_nuxt/` cache một năm, ảnh 14 ngày, Admin `no-store`. Cache không cần đổi.
Khi được đồng ý, trong khối `phucthinhsolar.com` của Caddy ngoài cùng thêm `header Strict-Transport-Security "max-age=86400"`, kiểm `curl -I`, một tuần sau tăng `604800`, một tháng sau `31536000`. Không dùng `includeSubDomains`, `preload`. Rút lại: đặt `max-age=0` và giữ đủ lâu để trình duyệt cũ ghé lại.

## Phát hành

### Bước 1 — `cc5011b6` (không đổi database)

1. Chủ website cho phép. Gộp fast-forward vào `master`.
2. Build backend và Nuxt; không đặt `CRM_LEADS_*`.
3. Chạy dưới khóa vận hành; giữ `stack.env.before` để lùi về `498ab8f1`.
4. Kiểm theo biên bản `2026-11-25-seo-sales-release-candidate.md`.

### Bước 2 — nhánh này (sau khi bước 1 ổn định và đã có review)

**Bắt buộc sau khi đổi container, cả lúc lùi:** `docker exec pts-laravel13-backend php artisan view:clear`, rồi khởi động lại worker. View đã biên dịch nằm trong volume `app_storage`, sống qua các lần phát hành; nhánh này đổi view email và Admin, bản biên dịch cũ có thể mới hơn file trong image nên không tự dựng lại. `cc5011b6` không đổi view nào.


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
- Tồn kho, GTIN, bảo hành: chờ chủ website. Đánh giá sản phẩm đã được xác nhận là khách thật (26/11).
