# Biên bản chốt đợt SEO & Sales — nhánh `feat/pts-seo-sales-20261125` (gốc `532da75d`)

Cập nhật 26/11/2026. **Chưa gộp, chưa push, chưa deploy, chưa ghi production.** Web thật đang chạy `498ab8f1` (kiểm chỉ đọc 26/11: backend, worker, scheduler, Nuxt cùng bản).

## Kết luận

| Phần | Trạng thái |
|---|---|
| Website SEO & Sales (code đến `2c9e8eb1`) | **Sẵn sàng phát hành**, chờ chủ website cho phép |
| Chuyển lead sang ZCRM | **Chưa dùng được**: CRM chưa có endpoint. Phía website có sẵn, tắt |
| Nhập thông số sản phẩm (nhánh của Sol) | **Chưa sẵn sàng**, không nằm trong bản này |
| Sửa dữ liệu sản phẩm, chính sách | **Chờ chủ website** |

## Thay đổi so với bản đang chạy

1. Số điện thoại: lead, checkout, đánh giá nhận khoảng trắng / chấm / gạch / `+84`, lưu 10 chữ số; vẫn từ chối chữ và ký tự khác.
2. Form: hiện thông báo 422/429/419 của máy chủ; 419 thì lấy token mới và gửi lại đúng một lần.
3. GA4: `generate_lead` thêm `form_source`; thêm `estimator_result` (một lần mỗi lượt xem, chỉ khi khách tự đổi thông tin, không gửi tiền điện); `purchase.value` là tiền hàng, thêm `shipping`. `order_success.order_value` vẫn là tổng đơn.
4. Tìm kiếm: khi cả cụm không ra kết quả thì khớp từng từ và đúng công suất kèm đơn vị. Truy vấn đang có kết quả không đổi.
5. SEO: bộ lọc sai và trang vượt cuối danh sách trả 404; trang thương hiệu từ trang 2 được index với canonical riêng; hai nhóm giải pháp có mô tả khác nhau.
6. Riêng tư: ghi lead, đơn, chuông Admin hoặc gửi mail bị lỗi thì log và `outbox_messages.last_error` chỉ còn loại lỗi, mã lỗi, dòng code. Trước đây có thể chép tên, số điện thoại, email khách. Đổi lại: không còn đọc được nguyên văn câu trả lời của máy chủ mail, chỉ còn mã.
7. Chuyển lead sang CRM, **mặc định tắt** (`2026-11-25-lead-zcrm-handoff.md`).
8. Test: giả lập Nhất Tín giữ nguyên giờ hành trình; test ảnh bài viết không phụ thuộc volume upload của máy.

Commit, mới nhất trước: `2c9e8eb1` `a067eaab` `80a9b6ef` `5ca28652` `7945c32d` `4c846302` `e99c8e88` `088fc0b8` `25e1d6a1` `e2a5ffdf` `61587d16` `60b444a6` `91873fad`, và commit ghi biên bản này.

## Review của người thứ hai (Sol, chỉ đọc)

| Commit được xem | Phát hiện | Xử lý |
|---|---|---|
| `25e1d6a1`, `088fc0b8` | Vừa: lead lưu xong mà ghi outbox lỗi thì không bao giờ tới CRM | `7945c32d` |
| như trên | Nhẹ: tìm theo từng từ được quyết trước khi áp lọc hãng/giá | Giữ có chủ đích: từ khóa quyết định kiểu khớp, bộ lọc chỉ thu hẹp |
| `7945c32d`, `5ca28652` | Vừa: log lỗi CRM/lead có thể chứa dữ liệu khách | `a067eaab` |
| `a067eaab`, `80a9b6ef` | Vừa: lỗi mail lưu và ném nguyên văn, có thể chứa email người nhận | `2c9e8eb1` |
| `2c9e8eb1` | Đạt, không phát hiện mới | — |

Đã xem consent/tracking: không thấy dữ liệu khách trong `generate_lead`, `purchase`. Thẻ tùy chỉnh trong GTM vẫn phải kiểm có tôn trọng consent khi chạy thật.

## Kiểm tra

Máy cục bộ, bản sao database riêng, mail không gửi, vận chuyển giả lập; đã xóa môi trường thử sau khi chạy.

| Cổng | Code | Kết quả |
|---|---|---|
| `php artisan test` toàn bộ, tuần tự (94 file, 5 đợt) | `2c9e8eb1` | **PASS** 542 đạt, 2 bỏ qua, 0 lỗi |
| Test mới cho log riêng tư và Nhất Tín | trước khi sửa | Đã thấy lỗi: 4/4 chỗ log lộ dữ liệu; Nhất Tín lưu 4 sự kiện thay vì 2 |
| Pint, 24 file PHP thay đổi | `2c9e8eb1` | **PASS** |
| PHPStan | `2c9e8eb1` | **Không phát sinh**: còn 9 lỗi có sẵn từ bản gốc |
| `git diff --check 532da75d` | `2c9e8eb1` | **PASS** |
| So API bản gốc với bản mới (`pts:snapshot`, cùng database) | `2c9e8eb1` | **PASS** 1.752/1.754 ca giống hệt trên 27 API; 2 ca khác ở `solutions` `category.description`, đúng thay đổi mục 5. Chạy lại bản gốc với chính nó: 1.754/1.754 |
| Bộ snapshot lưu trong repo | cả hai | Lệch sẵn ở bản gốc (dữ liệu đã đổi so với lúc lưu); **không ghi đè** |
| `npm test`, `nuxi typecheck` | frontend `25e1d6a1` (không đổi từ đó) | **PASS** 551/551; 0 lỗi |
| `nuxt build` | frontend như trên | **PASS** |
| Trình duyệt `consent-lead-purchase.cjs` (10 kiểm tra), `shop-flow.cjs`, `lead-form-errors.cjs` | backend `7945c32d` | **PASS**. Chưa chạy lại sau ba commit cuối: chỉ đổi nội dung log/lỗi, giờ giả lập và test |
| Chuyển lead CRM | `2c9e8eb1` | **PASS với endpoint giả**; chưa nối CRM nào |
| GA4/GTM, Merchant Center, Search Console | — | **NOT RUN**: không có quyền |
| Mail báo lead trên production, form/đơn thật | — | **NOT RUN**: cần chủ website cho phép |
| Kiểm tra riêng về trợ năng (accessibility) | — | **NOT RUN** trong đợt này |

## Đối chiếu báo cáo THY

Nhận qua trích dẫn của phiên điều phối (`THY-TECH-AUDIT.md`, `THY-CRO-AUDIT.md`); chưa đọc file gốc. Báo cáo chụp bản production trước đợt này.

| Mục | Nội dung | Trạng thái |
|---|---|---|
| TECH | 440/440 URL sitemap 200, đủ title/meta/H1/canonical/alt; phân trang và 3 trang mobile ổn | Không cần làm |
| TECH | 11 trang thương hiệu canonical về trang 1 và noindex | Đã sửa trong nhánh |
| TECH | Hai nhóm giải pháp trùng mô tả | Đã sửa, đã so API |
| TECH | 325/325 Offer thiếu `availability` | Giữ: chỉ thêm khi có tồn kho thật |
| C1 | Giá EPV300-4T30G đáng ngờ | Giữ: cần chủ website xác nhận giá |
| C2 | Bảo hành và đổi trả mâu thuẫn | Giữ: cần chính sách chốt |
| C3 | Tên và dung lượng AELIO không khớp | Giữ. Bản sao dữ liệu trên máy (có thể cũ hơn web): "AELIO P60B100 50KW" ghi pin 200 kWh, công suất 50 000 W; "P60B200 100KWH" ghi 100 kWh, công suất 60 000 W |
| C4 | Thông số X3-NEO-LV | Giữ: cần datasheet đúng phiên bản |
| C5 | Công suất danh định và thời gian sáng của đèn | Giữ: cần chứng từ |
| D1, D2 | Thông số tấm pin; tương thích thiết bị | Hoãn: phụ thuộc dữ liệu đã xác minh |
| S1 | Tìm kiếm | Xem bảng dưới |
| S2 | Khoảng giá | Đã sửa và đã lên web ở bản trước |
| Lọc kỹ thuật | | Hoãn: phụ thuộc dữ liệu |
| S3 | Thẻ pin lưu trữ hiện IP thay vì kWh | Hoãn: nhóm lưu trữ 25 sản phẩm (gồm cả biến tần hybrid), 12 có trường dung lượng, và còn C3 |
| S4 | Tồn kho, thời gian giao, quy mô dự án báo giá | Chủ website cung cấp |
| S5 | Email bắt buộc khi đặt hàng | Chủ website quyết |
| T1, T2 | Giả định công cụ ước tính; điều khoản đại lý | Chủ website quyết |
| Lỗi form | | Đã hiện thông báo lỗi trong nhánh |
| E1 | Logo thương hiệu dẫn tới URL bộ lọc bị robots chặn | Chưa đổi. Dải logo ở trang danh mục là bộ lọc, đúng thiết kế. Tường logo trang Giới thiệu dẫn `/san-pham/?brand=<id>`: đề xuất đổi sang `/thuong-hieu/<slug>/` ở đợt sau (đổi hợp đồng dữ liệu hai phía, Nuxt phải lên trước hoặc cùng lúc) |
| E2 | Trang thương hiệu rỗng trong sitemap | Giữ: chờ số liệu Search Console |

### Tìm kiếm (bản sao dữ liệu trên máy, 26/11)

| Truy vấn | Bản đang chạy | Bản mới | Đối chiếu với tên/mã sản phẩm đang hiện |
|---|---:|---:|---|
| đèn đường 100w / den duong 100w | 0 | 5 | Đủ 5 |
| tấm pin 550w | 0 | 1 | Đủ 1 |
| bơm 7.5kw | 0 | 2 | Đủ 2 |
| inverter hybrid 10kw | 0 | 2 | Đủ 2 |
| solax 10kw | 0 | 2 | **Sót 2**: X1-SMART-10-G2 và X3-MIC-10 có trường công suất 10 000 W nhưng tên không ghi "kw" |
| biến tần 3 pha | 11 | 11 | Không đổi; chưa đối chiếu từng sản phẩm |
| pin 48v | 3 | 3 | Không đổi; chưa đối chiếu từng sản phẩm |
| pin lithium | 107 | 107 | Không đổi; chưa đối chiếu từng sản phẩm |

Cách bù 2 máy bị sót: thêm "10kW" vào tên trong Admin, hoặc cho tìm kiếm khớp cả trường công suất sau khi dữ liệu trường này được rà.

## Dữ liệu và nhập thông số (không thuộc bản này)

Nhánh `feat/spec-enrichment-import-20261125` của Sol, đầu nhánh `b1ced70e` khi ghi biên bản.

| Số liệu | Ý nghĩa |
|---|---|
| 1.259 dòng / 324 sản phẩm | Hàng đợi nghiên cứu |
| 427 dòng | Nghiên cứu ban đầu đánh dấu đã xác minh |
| **23 dòng / 14 sản phẩm** | Qua kiểm duyệt, "sẵn sàng nhập". Chỉ thử trên **dữ liệu giả dựng từ chính file CSV**: chứng minh đường ghi và nhập lặp, **không** chứng minh sản phẩm thật sẽ nhận |
| **404 dòng / 109 sản phẩm** | Đã nghiên cứu nhưng giữ lại, chưa nhập |
| Yêu cầu chứng từ nhà cung cấp | ORIANA: 128 mã; Jindian/Blue Carbon và khác: 194 trường trên 70 mã |

Thiếu: bản xuất chỉ đọc **chỉ gồm danh mục** từ database không phải production (cột cần có ghi trong `scripts/spec-enrichment/README.md` của nhánh đó). Chưa có quy trình ghi vào database chung hay production được duyệt.

## ZCRM

Kiểm lại chỉ đọc 26/11: `zalo-crm-solar` tại `19f2f344` **chưa có** `POST /api/public/leads` và phạm vi `leads.write`. Không gọi CRM, không gửi dữ liệu.

Cần: (1) CRM làm endpoint đúng hợp đồng; (2) quản trị CRM cấp khóa chỉ có `leads.write`; (3) chủ website chốt CRM và tổ chức nhận, người nhận lead, cách xử lý khách trùng. Chỉ khi đủ mới đặt `CRM_LEADS_*`.

## Chủ website cần quyết

| Việc | Hiện tại | Cần |
|---|---|---|
| MPN/GTIN | Không mục nào trong feed có mã | Nhãn, datasheet, hoặc xác nhận không có mã |
| Tồn kho | Feed khai `in_stock` cho mọi mục; Offer không khai | Tồn kho thật |
| 50 đèn đường đang "Liên hệ báo giá" | Feed 324 còn 274 mục | Hiện giá lại hay không |
| Bảo hành | Sharp 550W ghi 10 năm và "2 đến 5 năm"; C2 | Phiếu bảo hành, chính sách chốt |
| Cookie | Mã click quảng cáo khi khách từ chối | Lưu hay không |
| Cam kết gọi lại | Popup 15 phút, hạn nội bộ 2 giờ làm việc | Một con số |
| Email khi đặt hàng | Bắt buộc | Giữ hay bỏ |
| Trang Đại lý, công cụ ước tính | Đoạn "Năm 2021"; giả định chưa duyệt | Nội dung, điều khoản, giả định |
| Trang thương hiệu rỗng, tiêu đề | Giữ nguyên | Số liệu Search Console |

## Khi phát hành

- Cần chủ website cho phép. `master` và `origin/master` ở `532da75d`, gộp được bằng fast-forward (kiểm lại lúc gộp).
- Gồm backend (kèm worker, scheduler cùng image) và Nuxt. Không có migration. `deploy/laravel13/backend.env.example` chỉ thêm biến CRM để trống.
- Lớp PHP mới `SearchTerms`, `CrmLeadForwarding`, `LogSafe`: image backend dùng classmap authoritative, kiểu build chồng lớp phải chạy `composer dump-autoload --no-dev --classmap-authoritative` và `php artisan package:discover`.
- Phát hành backend trước hoặc cùng lúc với Nuxt. Nuxt mới chạy với backend cũ vẫn gửi `purchase` bằng tổng đơn.
- Không đặt `CRM_LEADS_*` trên production.
- Việc GA4, Merchant, Search Console: `2026-11-25-ga4-merchant-gsc-checklist.md`. Doanh thu `purchase` trong GA4 không còn gồm phí giao kể từ ngày phát hành.

### Kiểm sau khi lên

1. Backend, worker, scheduler, Nuxt cùng bản mới, healthy; trang chủ, danh mục, sản phẩm, giỏ trả 200.
2. Tìm `đèn đường 100w`, `tấm pin 550w`, `bơm 7.5kw`: có kết quả.
3. `/san-pham/?brand=abc` và `/san-pham/?page=999` trả 404; `/san-pham/?utm_source=x` trả 200.
4. `/ho-gia-dinh` và `/doanh-nghiep` mô tả khác nhau.
5. Log backend, worker không có lỗi mới; `php artisan pts:outbox` không tăng lỗi.
6. Gửi một lead và một đơn thử (số điện thoại có dấu cách) **chỉ khi chủ website đồng ý**, tên ghi rõ là thử; xem email và sự kiện trong GA4 DebugView.

### Rollback

Chạy lại image backend và Nuxt `498ab8f1` (file `stack.env.before` mà lần phát hành mới lưu lại). Không đổi cấu trúc dữ liệu. Lead và đơn tạo trong lúc bản mới chạy giữ số 10 chữ số, bản cũ đọc bình thường. Gỡ riêng CRM bằng revert sẽ xung đột với `a067eaab`; tắt bằng cấu hình là đủ.

## Còn lại và rủi ro

- Log cũ trên production có thể đã chứa dữ liệu khách từ những lần ghi lỗi trước đây; chưa kiểm.
- Lỗi không được bắt ở nơi khác vẫn được Laravel ghi log nguyên văn.
- Docker Desktop trên máy thử bị tắt một lần lúc khoảng 23:50 ngày 25/11, không rõ nguyên nhân; đã mở lại, kết quả ở trên chạy sau đó.

## Checklist nghiệm thu

- [x] Test backend toàn bộ, PHPStan, Pint, diff check trên code cuối
- [x] Test frontend, typecheck, build
- [x] Ba bài thử trình duyệt (trước ba commit cuối)
- [x] So API bản gốc và bản mới
- [x] Review của người thứ hai, mọi phát hiện đã xử lý hoặc ghi lý do giữ
- [x] Đối chiếu trích đoạn THY, ghi mục hoãn
- [ ] Chủ website cho phép phát hành
- [ ] Kiểm sau phát hành
- [ ] GA4/GTM, Merchant, Search Console
- [ ] CRM: endpoint, khóa, quyết định
- [ ] Nhập thông số: bản xuất danh mục, quy trình duyệt
