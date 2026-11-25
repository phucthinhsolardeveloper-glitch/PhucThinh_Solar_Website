> **Trạng thái sau release 498ab8f1 (25/11/2026):** đã xử lý các mục ở phần 5 (inverter hòa lưới nhận theo danh mục: 15/15 trên web thật không còn thẻ lưu trữ; khoảng giá pin lưu trữ 100 triệu / 1,5 tỷ; công cụ quét đã báo lỗi noindex và sai canonical). Các mục ở phần 4 **chưa sửa**.

# PTS SEO/Sales — audit độc lập và cross-review (25/11/2026)

Mốc: master `1acaa17e`. Nhánh Codex `pts-seo-sales-p0-20261125` được xem ở trạng thái **chưa commit lúc 11:28**.
Chỉ đọc: không sửa repo, không commit/deploy, không gửi form hay đơn. Với production chỉ gửi GET.

## 1. Nguồn bằng chứng và giới hạn

- Tự crawl 440/440 URL sitemap, đọc HTML SSR, feed `/google-shopping.xml`, một số API đọc `/_api/product-detail`.
- `npm test` frontend trên master: 543/543 đạt.
- **Chưa chạy:** Pest/Pint/PHPStan, `nuxi typecheck`, smoke trình duyệt (DB test dùng chung trong lúc Codex đang làm).
- **Chưa xác minh được:** số GSC và GA4 trong đề bài (không có quyền truy cập, dùng như số được cung cấp); cấu hình GTM, Merchant Center, mail nhận lead và công tắc tự chia lead trên production.

## 2. Kết quả đo

| Hạng mục | Kết quả |
|---|---|
| HTTP / canonical / robots | 440 đều 200, self-canonical, `index, follow` |
| Title / description / H1 | Không thiếu; mỗi trang 1 H1; title không trùng; 1 cặp description trùng (`/ho-gia-dinh`, `/doanh-nghiep`); 60 title > 65 ký tự |
| Ảnh | 10.660 thẻ `img` trong SSR, 0 thẻ thiếu thuộc tính `alt`. Trang chủ có 12/23 `alt` rỗng. Con số 16/1.777 có thể do tính `alt` rỗng; ảnh trang trí để rỗng là đúng, cần xét từng ảnh |
| Schema | Product 325 (đủ offers, sku, image; 9 thiếu brand; 3 thiếu description; 0 gtin/mpn/availability; 2 có rating), BreadcrumbList 439, NewsArticle 32, FAQPage 5 |
| Feed | 324 item, giá khớp schema 324/324, không trùng id; `in_stock` 324/324; `identifier_exists=no` 324/324; 319 có brand |

## 3. Đã có sẵn, không làm lại

- Từ khóa tìm kiếm không index, sản phẩm ẩn trả 410: `c66e0611`.
- Chặn crawl URL bộ lọc: `f910ab01`, `frontend/public/robots.txt`.
- GA4 gửi một lần, consent mặc định denied, lọc query nhạy cảm: `f15c6d19`; `app/utils/ga4Event.ts`, `analyticsPageContext.ts`.
- `purchase` một lần cho mỗi đơn, quyết định ở server; VNPay chỉ bắn khi đã trả: `Shop.php:384-415`, `ShopFlowTest.php:79-81,151-166`.
- Đơn hàng chống gửi trùng bằng checkout token: `Shop.php` (`placeOrder`, `persistedOrder`).
- Báo giá giỏ hàng lấy dữ liệu từ server: `380ca794`, `ea6e6f3d`, `QuoteCartSummary.php`.
- Schema không khai InStock/GTIN khi chưa xác minh: `42d60ab0`, `shared/product-structured-data.ts`; feed xác minh có cổng mặc định tắt: `e8d37dee`.
- Sitemap bỏ danh mục không có sản phẩm: `Sitemap.php`.

## 4. Việc cần sửa (master)

### P0-1. Số điện thoại hợp lệ theo giao diện bị máy chủ từ chối
- Bằng chứng: `LeadSubmission.php:299-303` đòi đúng 10 ký tự liền. `thanh-toan.vue:202` nhận khoảng trắng, chấm, gạch, +84 và đưa ví dụ `0944 555 957`. HomeContact, ContactLeadForm, DealerLeadForm, ProductConsultationForm có `pattern` cũng nhận dạng này. Hai modal gửi số nguyên văn. Chỉ `SolarEstimatorPage.vue` bỏ dấu trước khi gửi. Checkout dùng cùng hàm (`Shop.php:230`).
- Kiểm bằng regex tương đương: `0944555957` đạt; `0944 555 957`, `0944.555.957`, `+84944555957` trượt.
- Sửa: chuẩn hóa ở backend (bỏ khoảng trắng/.-, đổi +84 thành 0), kiểm và lưu bản chuẩn, áp cho cả lead và checkout.
- Nghiệm thu: 4 dạng trên đều lưu `0944555957`; số 9/11 chữ số và chữ cái vẫn bị từ chối.
- Test: Pest dataset cho `LeadSubmissionTest` (3 kind) và `ShopFlowTest`.
- Liên quan có thể có với 1 `generate_lead`, nhưng **chưa đo** được bao nhiêu khách bị chặn.

### P1-2. Nuốt thông báo lỗi của máy chủ
- `useLeadForm.ts:88` ném `new Error(message)`, còn `HomeContact.vue:81`, `ContactLeadForm.vue:98`, `DealerLeadForm.vue:80`, `ProductConsultationForm.vue:124` đọc `reason.data.statusMessage`, nên luôn hiện câu chung.
- Nghiệm thu: 422/429/419 hiện đúng thông báo. Test: contract test cho 4 component. Làm cùng P0-1.

### P1-3. Tab mở quá 2 giờ không gửi lại được
- `FormToken` sống 7.200 giây; `useLeadForm` giữ token trong state, không làm mới khi 419. Giỏ hàng đã có retry (`useShopCart.ts`).
- Nghiệm thu: gặp 419 thì lấy token mới, thử lại đúng một lần. Test: unit mock 419 rồi 200.

### P1-4. Feed Merchant lệch dữ liệu đã xác minh
- `MerchantFeed.php:185,219`: chế độ cũ khai `in_stock` + `identifier_exists=no` cho tất cả; trang và schema không khai availability.
- Google yêu cầu availability nhất quán với trang; `no` chỉ dành cho hàng thật sự không có mã định danh. Hàng SolaX/LONGi/Sharp nhiều khả năng có MPN, cần đối chiếu từng mã.
- Việc: chủ website xác nhận tồn kho; nhập MPN/GTIN từ nhãn hoặc datasheet; rồi mới bật cổng verified (ngưỡng 286). Không dùng mã `PTS-…` làm MPN, không bịa GTIN. Không bật cổng khi dữ liệu chưa đủ.
- Nghiệm thu: `MerchantReadinessCommand` đạt; feed, trang, schema khớp. Nếu Merchant Center đang chạy quảng cáo thì nâng lên P0.

### P1-5. Đo lường GA4
- `Shop.php:423` + `OrderThanksPage.vue:81`: `value` là tổng đơn gồm phí ship, không có `shipping`. Tài liệu GA4: value = tổng price × quantity, không gồm shipping/tax. Sửa: gửi value = subtotal, shipping riêng; giữ `order_value` cho GTM nếu có thẻ đang dùng. Test: đơn có phí ship.
- `useLeadForm.ts:93`: form liên hệ, modal, báo giá giỏ, calculator đều là `form_kind=contact`. Thêm giá trị phân biệt (home, contact_page, modal, cart_quote, estimator, product, dealer); đăng ký custom dimension. Không gửi PII, không gán giá trị tiền tự nghĩ.
- Calculator chưa có sự kiện: thêm `estimator_result` (một lần mỗi lượt xem) để có phễu kết quả → lead.
- Trong GA4: đánh dấu `generate_lead` là key event; cân nhắc `click_call`, `click_zalo` (click chưa phải lead).
- Không dùng 29 `form_start` / 1 `form_submit` làm phễu: là sự kiện enhanced measurement, tính mọi form (cả tìm kiếm), `form_start` theo lần tương tác đầu trong phiên.
- Consent mặc định denied: ping không cookie có thể không hiện đủ trong báo cáo. So `purchase`/`generate_lead` với đơn và lead trong Admin cùng kỳ trước khi kết luận tỷ lệ chuyển đổi.

### P1-6. Bàn giao CRM
- Không thấy tích hợp CRM ngoài trong `backend/app`, `config`, `routes`: lead vào bảng `leads`, chuông Admin, mail qua Outbox.
- `LeadResource.php:58`: chỉ có Mới / Đã liên hệ / Đã xong / Rác, không phân biệt chốt hay mất, nên không đo được lead → doanh thu.
- Tự chia lead mặc định tắt (`LeadRouting.php`); lead không có khóa chống gửi trùng.
- `StorefrontConsultationModal.vue:128,145` hứa gọi trong 15 phút; hạn nội bộ là 2 giờ làm việc. Chủ website cần chốt cam kết đúng.
- Việc: quyết định CRM đích; thêm kết quả won/lost; kiểm mail nhận và người phụ trách trên production.
- Cần quyết thêm: `lead-attribution.client.ts` lưu gclid/fbclid 30 ngày kể cả khi khách từ chối cookie.

### P2-7. 13 trang thương hiệu rỗng vẫn index và nằm trong sitemap
- canadian-solar, aesolar, tcl-solar, sungrow, huawei, growatt, goodwe, sofar, anker-solix, deye, sigenergy, leader-solar, phuc-thinh-solar. `Sitemap.php:50`, `BrandDetailPage.vue:99-113`.
- **Xem GSC trước:** nếu truy vấn "Canadian720" (212 hiển thị) đang rơi vào trang thương hiệu thì chọn: bổ sung sản phẩm/nội dung có thật, hoặc `noindex,follow` + bỏ khỏi sitemap. Không tạo sản phẩm 720W nếu không bán. Bài so sánh 600–720W hiện không link tới sản phẩm nào, chỉ link danh mục.
- Nghiệm thu: mọi trang thương hiệu trong sitemap có sản phẩm hoặc nội dung riêng. Test: `SitemapTest`.

### P2-8. Mã trạng thái sai
- `?brand=sharp`, `?brand=abc`, `?price=abc` trả **502** (`catalog.get.ts:40,42` ném 400; `CatalogPage.vue:132` đổi thành 502). Nên 404, hoặc bỏ qua tham số sai.
- `/san-pham/?page=999` và `?page=99` của danh mục pin: 200, 0 sản phẩm, index, self-canonical. Nên 404 khi page > totalPages (`CatalogPage.vue:263,294`).

### P2-9. Dữ liệu cần chủ website xác nhận
- Trang Sharp 550W: ghi "Bảo hành 10 năm" và cả "Bảo hành chính hãng 2 năm đến 5 năm (tùy sản phẩm)". Đoạn chính sách chung này có trên 96 trang; chỉ xác nhận mâu thuẫn ở trang Sharp, các trang khác chưa đối chiếu đủ.
- Tên "NU - JD 550 L" và mã "JD-550L": đối chiếu datasheet trước khi đổi title.
- Dự án Thu Hồng: tiêu đề "Thu Hồng", trường khách hàng "Nhà nghỉ Thu Hiền" (có một dự án Thu Hiền riêng).

### P2-10. Case study và B2B
- Trang dự án đã đọc không có link sản phẩm; hai dự án điện mặt trời dùng cùng đoạn tổng quan.
- Bổ sung khi có chứng cứ: công suất, thiết bị, thời điểm, phạm vi, ảnh được phép công bố; link tới sản phẩm/giải pháp. Không suy sản lượng hay tiết kiệm.
- `/dai-ly/` còn đoạn thị trường "Năm 2021": rà lại.

### P2-11. CTR
- Trang Sharp (529 hiển thị/12 click), danh mục đèn (245/6), danh mục pin (200/4): xem vị trí và truy vấn trong GSC trước. Title/description mới chỉ dùng dữ kiện đã có (model, giá đang hiển thị, số sản phẩm). Đo lại sau 28 ngày.

## 5. Cross-review Codex (chưa commit, 11:28)

### CHẶN — sửa inverter hòa lưới không tác động dữ liệu thật
- `ProductApplications.php:28` chỉ đổi khi `inverter_type` = "hòa lưới".
- Production: cả **27/27** trang `/san-pham/inverter-…` thuộc nhóm đã kiểm có "Loại biến tần" **rỗng** (15 thuộc `hoa-luoi`, 12 còn lại cũng rỗng). **15 inverter hòa lưới vẫn hiện "Lưu trữ & dự phòng".**
- Test mới đạt vì tự cấp `inverter_type`.
- Sửa: nhận diện theo chuỗi danh mục có slug `hoa-luoi` (`$list` hiện chỉ là danh mục gốc, `ProductDetailAssembler.php` đoạn gọi `cards`, nên phải truyền thêm), hoặc nhập loại biến tần từ nguồn đã xác minh. So chuỗi cần nhận cả "Hoà lưới".
- Test cần thêm: `inverter_type` rỗng + danh mục hòa lưới; hybrid vẫn giữ thẻ lưu trữ. Thẻ lấy từ nội dung bài (`fromContent`) không bị bộ lọc này chặn.

### CẦN SỬA — khoảng giá pin lưu trữ có nhóm rỗng
- 10 giá (triệu đồng): 31; 54; 57; 1.025; 1.039; 1.285; 1.413; 1.693; 1.838; 1.853.
- Mốc mới <100 / 100–500 / >500 cho **3 / 0 / 7**. Gợi ý <100 / 100–1.500 / >1.500 cho **3 / 4 / 3**.
- Hybrid <50 / 50–100 / >100 cho 6 / 4 / 2: ổn.
- Mốc ghi cứng trong code, sẽ cũ khi giá đổi. Link `?price=` cũ đổi nghĩa (trang lọc noindex, chấp nhận được). Test nên kiểm số sản phẩm mỗi nhóm > 0 trên fixture và kiểm lọc thật.

### CẦN SỬA — `tools/visual-tests/audit/sitemap-crawl.py` (chưa track)
- Chỉ coi status khác 200 hoặc redirect là lỗi: noindex, canonical sai, thiếu title vẫn exit 0.
- `xRobotsTag` luôn rỗng vì không đọc header.
- Title cộng cả `<title>` của SVG nếu có.
- Báo cáo mặc định ghi vào `/tmp`; không kiểm description, H1, schema.

Chưa chạy test của nhánh Codex. Cần review lại sau khi commit.

## 6. Thứ tự đề xuất và cổng release

1. P0-1 + P1-2 + P1-3 (một đợt form).
2. Sửa phần chặn của Codex và khoảng giá.
3. P1-5; song song chủ website cấp dữ liệu cho P1-4, P1-6, P2-9.
4. P2-7, P2-8, sau đó nội dung.

Cổng: `php artisan test`, `pint --test`, `phpstan`, `npm test`, `nuxi typecheck`, `shop-flow.cjs` trên local/clone. Không đặt đơn thật.

## Tài liệu Google đã dùng

- https://developers.google.com/analytics/devguides/collection/ga4/reference/events
- https://support.google.com/analytics/answer/9216061
- https://support.google.com/merchants/answer/6324448
- https://support.google.com/merchants/answer/6324478
- https://developers.google.com/tag-platform/security/concepts/consent-mode
- https://developers.google.com/search/docs/appearance/structured-data/merchant-listing
