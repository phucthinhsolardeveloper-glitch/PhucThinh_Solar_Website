# Audit project, Admin và giao diện public — 2026-09-23

## Kết luận

Project local đang render được các trang chính, nhưng chưa thể kết luận “không còn lỗi”. Có lỗi tương tác bộ lọc tái hiện được, H1 trang chủ bị lặp, các field Admin không khớp consumer public và test hồi quy chưa theo kịp PDP mới. Khoảng lệch lớn nhất nằm ở trang chủ; catalog/PDP đã cải thiện nhưng vẫn còn điểm cần xử lý.

Đây là baseline audit; implementation follow-up được thực hiện ngày 2026-09-24 trong cùng worktree (không deploy, không import, không đổi record nghiệp vụ, không xóa upload). Truy vấn DB chỉ dùng SELECT và chỉ xuất số lượng tổng hợp. Browser fault injection chỉ giả lập response trong trình duyệt.

## Phạm vi và độ chắc chắn

- Đọc route, source, template, cấu hình type/menu/field Admin; đối chiếu các audit cũ với code hiện tại.
- Kiểm tra local tại `http://localhost:8080`, Chromium desktop/mobile. Không kiểm tra production trực tiếp.
- Đọc số liệu tổng hợp DB local; số liệu này không đại diện DB production.
- Chạy mới 24 test public rendering/JSON-LD/responsive; đo bổ sung DOM ở 320, 390, 1440px; tái hiện Back và API failure của bộ lọc.
- Không đăng nhập/sửa từng field Admin trong lần audit này. Kết luận mapping Admin dựa trên config → query → template đang chạy; không dùng query tồn tại làm bằng chứng rằng field đã được render.
- Kết quả predeploy trước đó trong cùng phiên (115 màn Admin, security, commerce và form PASS) là bằng chứng lịch sử gần nhất, **không phải bộ kiểm thử được chạy lại ở lần audit này**. Không chạy lại test tạo fixture/đơn/form khi yêu cầu hiện tại chỉ là audit.

Ưu tiên: P1 = nên xử lý ở đợt sửa kế tiếp; P2 = vận hành, nội dung hoặc chất lượng; P3 = backlog. Chưa phát hiện P0 trong phạm vi đã kiểm tra; đây không phải chứng nhận bảo mật toàn hệ thống.

## 1. Lỗi và khoảng lệch cần xử lý

| ID | Ưu tiên | Vấn đề | Bằng chứng / ảnh hưởng | Hướng xử lý |
|---|---|---|---|---|
| AUD-01 | P1 | Back/Forward không đồng bộ bộ lọc | Tái hiện: danh mục đèn có 133 kết quả → lọc thương hiệu còn 69 → Back về URL không có query nhưng vẫn hiển thị 69 và một checkbox còn chọn. `assets/js/functions.js:5–79` gọi `pushState`, chưa thấy xử lý `popstate` cho luồng này. | Khôi phục controls và tải lại kết quả từ URL khi Back/Forward; test đi/lùi với nhiều trạng thái. |
| AUD-02 | P1 | API bộ lọc lỗi làm mất lưới và kẹt loader | Giả lập HTTP 500 riêng tại browser: sau 1,4 giây vẫn có `#show-product #loader`, không có thông báo. `functions.js:81–123` thay lưới bằng loader nhưng chỉ có callback success. | Có error/timeout, thông báo thử lại, giữ hoặc khôi phục kết quả trước đó; đồng bộ trạng thái drawer/loading. |
| AUD-03 | P1 | Upload ảnh Hero Admin không đổi ảnh Hero public | `sources/index.php:109–145` đọc record `hero-carousel` nhưng gán `photo`/`upload` từ `$homeHeroDefault`, không dùng ảnh record. Admin lại mở upload và hướng dẫn dùng ảnh 1200×900 (`libraries/type/config-type-photo.php:32–55`); template render thumb 1600×720. | Quyết định một nguồn ảnh rõ ràng, nối upload vào render và thống nhất tỷ lệ/preview. Hiện 4 record Hero local đều bật. |
| AUD-04 | P1 | Công tắc Hiển thị không có nghĩa thống nhất | Hero thiếu một trong bốn nhóm nhận diện theo tên thì thay toàn bộ bằng bốn fallback. `home-about` và `home-about-point` chỉ query record bật rồi fallback khi trống; không phân biệt chưa cấu hình với chủ động tắt. `templates/index/index_tpl.php:316` vẫn render section. | Đọc cả record tắt để nhận biết configured/off; dùng khóa slot rõ ràng thay đoán tên. Không tự sửa dữ liệu trong audit. |
| AUD-05 | P2 | Trang chủ có hai H1 | DOM tại 320, 390, 1440px đều có 2 H1. `templates/layout/seo.php:7` tạo H1 SEO ẩn, `templates/index/index_tpl.php:166` có H1 Hero; source trang chủ chưa khai báo quyền sở hữu H1 như PDP/catalog. | Giữ một H1 có chủ đích; chặn H1 fallback khi page đã tự render. Không suy luận rằng lỗi này tự gây tụt hạng SEO. |
| AUD-06 | P2 | Có field Admin nhập được nhưng GUI không dùng | `home-about`: ảnh và nhãn nhỏ được tải vào biến nhưng template chỉ hiện title/desc/button. `home-about-point`: source đọc ảnh nhưng template luôn dùng `fa-check`. Cùng field này có desc trong source/render nhưng config `desc=false` tại `config-type-news.php:675`. | Ẩn/ghi rõ field không sử dụng hoặc nối vào UI; mở field desc nếu tiếp tục render. Tránh bắt nhân viên nhập nội dung vô tác dụng. |
| AUD-07 | P2 | Mục Admin còn tồn tại nhưng không có render tương ứng ở trang chủ hiện tại | `loi-ich` news/static, `so-lieu-thong-ke`, `home-proof-background`: source còn query nhưng không tìm thấy template dùng các biến `$loiich`, `$loiich_title`, `$solieu`, `$homeProofBackground`. `loi-ich` và nền chứng nhận có entry trong `libraries/config-type.php`. | Chuyển mục ngừng dùng sang khu legacy/ghi chú; chỉ archive sau kiểm kê consumer khác, backup và duyệt. Không xóa record vì audit. |
| AUD-08 | P2 | Nhiều ảnh/copy marketing public không có điều khiển Admin tương ứng | `index_tpl.php:26–58`: ảnh bốn slot panel/inverter/storage/pump được cố định, bỏ qua ảnh category; `:80–95` cố định ảnh tab công trình, label/chips. `:130` và `:299–313` có copy CTA đại lý/bullet hardcode. `product/product_tpl.php:27–34` hardcode bốn cam kết catalog. | Lập mapping slot và field Admin cho các nội dung cần biên tập; phân biệt text UI cố định với cam kết kinh doanh thay đổi. |
| AUD-09 | P2 | Chứng nhận sản phẩm bị đặt dưới nhãn Bảo hành | Admin gọi field `certificate` là “Giấy chứng nhận / kiểm nghiệm” (`administrator/templates/product/man/man_add_tpl.php:179–182`), PDP render nó dưới H2 “Chính sách bảo hành” (`product_detail_tpl.php:414–415`). | Tách heading chứng nhận khỏi chính sách; dùng nội dung thật để kiểm thử hai nhánh. Đây là mismatch ngữ nghĩa source, không khẳng định đã có chứng nhận public bị nhập sai. |
| AUD-10 | P1 | Bundle JS không tự đổi theo thay đổi nội dung source | `class.JsMinify.php:55` hash danh sách đường dẫn; TTL 30 ngày, không dùng mtime/hash từng source như CSS (`class.CssMinify.php:53–58`). Trong phiên trước đã gặp JS đếm kết quả mới nhưng browser nhận bundle cũ. | Thêm signature source cho JS. Quy trình deploy chính đã có bước dọn asset cache tại `scripts/deploy-production.sh:168–169`, nên không kết luận mọi deploy production đều lỗi; rủi ro còn ở cập nhật file ngoài quy trình/local. |
| AUD-11 | P2 | Test responsive PDP dùng DOM cũ | `responsive-architecture.spec.js:113–115` lấy computed style của `.tabs-pro-detail`; PDP v3 không có node này. Cùng test lỗi ở desktop và mobile. Các kiểm tra phía sau bị bỏ qua. | Viết lại assertion theo hành vi PDP v3; không đơn giản xóa assertion để lấy PASS. |
| AUD-12 | P2 | Tài liệu thiết kế và implementation mâu thuẫn | Design system §4.4 duyệt Hero nhưng §7 lại nói không render Hero. `templates/index.php:23` comment Hero đã tắt, trong khi runtime có Hero và các section giải pháp/quy trình/giới thiệu. Test `home-story-v4.spec.js` cũng đòi Hero. | Chuẩn hóa nguồn quyết định và mốc cập nhật. Không tự bỏ Hero dựa trên một dòng lịch sử khi cùng tài liệu có chỉ dẫn ngược lại. |

### Dấu hiệu cần xác minh thêm, chưa xếp thành lỗi ổn định

- Test responsive desktop ở viewport 320px ghi nhận overflow 371px một lần. Cùng ma trận chạy với mobile profile PASS; đo lại trang chủ 320/390/1440px đều overflow 0. Cần xem trace/timing font và carousel để phân biệt lỗi layout thoáng qua với phép đo quá sớm. Không báo “responsive hoàn toàn sạch”, cũng không khẳng định mọi điện thoại đều tràn.
- AJAX bộ lọc chưa có cơ chế chống response về sai thứ tự trong đoạn `doSearch`; cần test hai request có độ trễ đảo ngược trước khi kết luận lỗi race thực tế.
- Widget GTranslate bị chặn trong test public như cấu hình test hiện hữu. PASS không bao gồm dịch tự động hoặc lỗi do script bên thứ ba đó.

## 2. Ma trận Admin ↔ GUI hiện tại

| Khu vực | Có nguồn Admin | Kết luận audit |
|---|---|---|
| Hero trang chủ V4 | `hero-carousel` | Có màn nhập nhưng ảnh không được dùng; tắt/thiếu slot làm fallback toàn bộ; ratio ghi trong Admin lệch render. AUD-03/04. |
| Hero legacy | `home_hero_mode`, `slide`, `home-hero-bg` | `templates/index.php` không include `layout/slide.php`; lựa chọn không điều khiển V4. Admin đã có note giữ để rollback, nên đây là tính năng legacy cần thu gọn, không phải mất dữ liệu. |
| Campaign trang chủ | `home-campaign` | Có consumer trong section lighting và ảnh category đầu tiên. Không liệt kê toàn bộ type này là dư thừa. |
| Danh mục nổi bật trang chủ | `product_list` + cờ noibat/major | Tên/link lấy taxonomy, nhưng template giữ 5 slot cố định và nhiều ảnh cố định. Đổi ảnh taxonomy không đảm bảo đổi ảnh slot. |
| Giới thiệu ngắn trang chủ | `home-about`, `home-about-point` | Title/desc/button và nhãn điểm nhấn có consumer; ảnh/eyebrow/icon upload không có consumer tương ứng; field mô tả điểm nhấn bị tắt ở Admin. |
| Lợi ích / số liệu trang chủ | `loi-ich`, `so-lieu-thong-ke` | Có query/dữ liệu legacy, chưa có render hiện hành tìm thấy. Không coi query là tích hợp hoàn chỉnh. |
| Nền chứng nhận trang chủ | `home-proof-background` | Có slot/menu/query, chưa có consumer template hiện tại tìm thấy. Chứng nhận ở trang Giới thiệu là consumer khác, không được xóa type `chungnhan`. |
| Catalog | taxonomy, product, `product-category-hero`, `catalog-campaign`, `content_section` | Bộ lọc/lưới, hero, CTA và FAQ danh mục có nguồn; CTA chọn record bật đầu tiên, đã ghi rõ trong Admin. Dải cam kết vẫn hardcode. |
| PDP | product/gallery/structured specs/policy/video/certificate/content blocks | Phần chính có nguồn Admin. FAQ PDP tự sinh từ specs, chưa có trình biên tập riêng cho FAQ này. Ứng dụng phụ thuộc heading chứa “ứng dụng”/“phù hợp”, tối đa 3; thiếu hướng dẫn rõ về quy tắc trích xuất cho biên tập viên. |
| Giải pháp | `solution-intro`, `giai-phap`, `solution-cta` | Có mapping nguồn và cấu hình field. Render public test PASS; chưa lặp lại save/toggle từng field trong audit này. |
| Dự án | `project-intro`, `du-an`, `project-cta`, `project-benefit`, `project-trust` | Có mapping; các lợi ích từng bị báo hardcode trong audit cũ đã có Admin. Không mở lại lỗi cũ như chưa sửa. |
| Kiến thức | `news-intro`, `tin-tuc`, `news-cta` | Có mapping intro/bài/CTA; test render PASS. |
| Giới thiệu | `lich-su`, `about-team`, `about-principle`, `ly-do-chon-chung-toi`, `su-menh`, `chungnhan`, `about-cta` | Có nguồn theo section; có fallback legacy. Cần kiểm thử toggle từng block nếu chuẩn hóa hợp đồng “tắt là ẩn” toàn site. |
| Đại lý | `dealer-hero`, `dai-ly-1/2/3`, gallery, cam kết, phản hồi, số liệu | Có source và consumer; không coi type ít dữ liệu là thừa. |
| Liên hệ/footer | settings, `contact-visual`, static footer, cửa hàng/chính sách | Có nguồn Admin và render; chưa audit từng shortcode/script tùy chỉnh. |
| Giỏ hàng/checkout/Admin đơn | product pricing, payment news type, order/order_detail | Chưa tái chạy tạo đơn ở lần này. Dùng kết quả commerce predeploy trước đó như lịch sử, không tuyên bố audit giao dịch mới. |

Các màn “không dùng trong GUI hiện tại” nên được ghi nhãn legacy/ẩn khỏi luồng thường dùng. Không xóa upload, bảng hoặc quyền ngay: record lịch sử và consumer ở trang khác phải được kiểm tra trước.

## 3. Dữ liệu local còn thiếu

SELECT mới tại thời điểm audit: **417 sản phẩm tổng, 415 đang Hiển thị**.

| Chỉ số trên 415 sản phẩm đang bật | Số lượng | Cách hiểu |
|---|---:|---|
| Không có ảnh đại diện trong field photo | 210 | 50,6%; đây là thiếu dữ liệu ảnh chính, không đồng nghĩa mọi sản phẩm đó không có gallery. Card dùng photo nên cần nguồn ảnh thật để hoàn thiện. |
| Có tên ảnh nhưng file ảnh chính không tồn tại | 0 | Kiểm tra đường dẫn upload/product của các record có ảnh. |
| Mô tả ngắn rỗng | 202 | Có thể có nội dung kế thừa/specs; không tự bịa mô tả. |
| Legacy contentvi rỗng | 362 | **Không được diễn giải thành 362 PDP trắng**: còn content_section kế thừa và thông số cấu trúc. |
| Công suất không dương/rỗng | 195 | Nhiều loại phụ kiện không cần công suất; phải phân loại theo danh mục trước khi coi thiếu. |
| Có video_url | 0 | Field/video renderer có sẵn nhưng chưa có dữ liệu; không phải tính năng thừa hay bug. |

Một số record legacy đang bật nhưng không có consumer trang chủ tìm thấy: news `loi-ich` 7, static `loi-ich` 1, news `so-lieu-thong-ke` 4. Có 4 `hero-carousel` bật; `home-hero-bg` có 4 record/3 bật; `slide` có 4/1 bật. Snapshot không có record `home-about`, `home-about-point`, `catalog-campaign`, `home-proof-background`: frontend đang đi qua fallback ở các phần liên quan.

Các khoảng trống nội dung đã được chủ project xác nhận là chờ nguồn thật. Không coi chúng là blocker kỹ thuật/deploy, không tự nhập mẫu để làm đẹp số liệu.

## 4. Tồn đọng cần tách khỏi lỗi vừa xác nhận

1. **PDF/catalogue sản phẩm:** chưa có field và luồng tài liệu riêng tương ứng với mẫu đã tham chiếu. Là backlog tính năng, không phải nút tải hiện có bị hỏng. Cần tài liệu thật, validation extension/MIME/size và quyền upload.
2. **Off-site backup:** status ngày 20/09 ghi chưa cấu hình; `deploy/vps/scripts/backup.sh:63–66` chỉ gọi hook khi tồn tại, nếu không cảnh báo. Chưa truy cập VPS trong audit này nên không xác nhận trạng thái vận hành hôm nay; cần kiểm tra bản sao và restore ngoài VPS.
3. **Runtime PHP 7.4 / thư viện legacy:** Docker vẫn pin `php:7.4-apache`; đây là khoản nợ runtime cần kế hoạch nâng cấp và regression. Đợt audit này chưa quét CVE/dependency mới và không khẳng định đang có lỗ hổng khai thác cụ thể.
4. **Release tracking:** worktree chứa thay đổi Admin/content block/router/PDP/catalog, có file CSS và handoff chưa track. Báo cáo local PASS không chứng minh production đã có cùng code/schema/data. Cần chốt manifest/commit và kiểm tra migration cần thiết trước release.
5. **Tài liệu cũ:** số sản phẩm, số màn Admin, URL Giới thiệu và trạng thái production trong các báo cáo khác ngày có thể khác nhau. Không dùng tổng số cũ để đánh giá dữ liệu hôm nay; status hiện là log tích lũy và có nhiều mục lịch sử.
6. **Chưa xác minh mới:** SMTP thật, reCAPTCHA thật, Safari/iOS thật, email/đơn thực, tải Word/Excel Admin, quyền theo từng tài khoản nghiệp vụ production và backup/restore production. Không gắn PASS cho những phần chưa chạy.

## 5. Kết quả kiểm tra mới

Lệnh: `npx playwright test tests/public-pages.spec.js tests/responsive-architecture.spec.js --workers=2 --retries=0`.

- Tổng **24 test: 21 PASS, 3 FAIL**.
- `public-pages.spec.js`: **18/18 PASS**, gồm tám trang public × desktop/mobile và JSON-LD sản phẩm × hai profile. Không thấy lỗi JavaScript/local HTTP >=400/ảnh hỏng trong phạm vi assertions này.
- `responsive-architecture.spec.js`: **3 PASS, 3 FAIL**. Hai FAIL cùng nguyên nhân selector PDP cũ; một FAIL là overflow 320px chưa tái hiện lại.
- Đo DOM bổ sung ba route trang chủ/catalog/PDP ở 320/390/1440: overflow 0 cả chín lượt; trang chủ 2 H1, catalog/PDP 1 H1.
- Probe bộ lọc: Back sai trạng thái và lỗi API kẹt loader đều tái hiện như AUD-01/02.
- `git diff --check`: PASS tại thời điểm audit.

Artifact Playwright nằm ở `tools/visual-tests/test-results/` và `playwright-report/`, có thể bị lượt test kế tiếp ghi đè. Probe tạm: `/tmp/pts-audit-browser-20260923.cjs`; script SELECT: `/tmp/pts-audit-readonly-20260923.php`. Báo cáo này lưu kết quả để không phụ thuộc artifact tạm.

## 6. Thứ tự xử lý đề xuất và điều kiện nghiệm thu

1. **Bộ lọc + cache JS:** sửa AUD-01/02/10. Back/Forward phải khớp URL/checkbox/count/cards; API lỗi có thông báo/thử lại; thay source JS phải sinh bundle mới.
2. **Admin trang chủ:** sửa AUD-03/04/06/07/08. Lập bảng từng field → section; kiểm thử upload/toggle/name/desc bằng fixture local có cleanup; field ngừng dùng phải được nhận diện rõ. Không vô tình bật lại dữ liệu Admin đã tắt.
3. **H1 + ngữ nghĩa PDP:** sửa AUD-05/09; một H1, chứng nhận và bảo hành có nhãn đúng với field biên tập.
4. **Test và tài liệu:** sửa AUD-11/12; điều tra trace overflow; chốt một hợp đồng Hero duy nhất. Chạy lại bộ test liên quan trước khi tuyên bố hết lỗi.
5. **Nội dung và vận hành:** giao danh sách thiếu ảnh cho người có nguồn ảnh; lập kế hoạch PDF, runtime và xác minh off-site theo đợt riêng.

Không có deploy ở đợt audit. Rollback báo cáo chỉ là bỏ file Markdown này; implementation/DB/upload hiện hữu không bị audit sửa đổi. Mọi đợt triển khai sau cần backup, manifest chính xác và kiểm thử tương ứng với phạm vi thay đổi.

## 7. Follow-up đã xử lý — 2026-09-24

- Bộ lọc có `popstate`, khôi phục checkbox/count/cards theo URL; AJAX có timeout, abort request cũ, trạng thái lỗi và nút thử lại.
- Bundle JS dùng hash nội dung từng file; cache PHP dùng `@unlink` khi file hết hạn/race để không đẩy Warning vào HTML.
- Hero đọc ảnh thật từ `hero-carousel` đang Hiển thị theo STT; chỉ fallback khi chưa có record bật. H1 trang chủ được đánh dấu owner để không sinh H1 SEO thứ hai. Admin note Hero đã đồng bộ 16:7.
- Chứng nhận sản phẩm được tách khỏi heading Chính sách bảo hành; field mô tả điểm nhấn home-about-point đã bật đúng với consumer và icon ảnh được render khi có.
- Test responsive đổi sang selector PDP v3; CSS catalog bỏ surface trắng/shadow của filter/card theo chuẩn flat UI.

Kết quả follow-up: PHP lint các file nguồn/template/class thay đổi PASS; probe filter Back/Forward và API lỗi PASS; responsive architecture 6/6 PASS; content-surface 2/2 PASS; home-story 6 PASS (2 test mobile được skip theo cấu hình suite). Chưa deploy production.
