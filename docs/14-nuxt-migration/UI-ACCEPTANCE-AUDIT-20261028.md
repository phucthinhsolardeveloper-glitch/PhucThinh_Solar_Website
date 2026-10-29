# Nuxt UI acceptance audit — 2026-10-28

## Kết luận

Đã audit 26 entry trong manifest `tools/visual-tests/fixtures/migration-routes.json` và xử lý các gap P1 có thể chứng minh từ PHP template, API, Admin field và DTO. Đây là **audit source/code**, chưa phải nghiệm thu visual trên runtime mới nhất.

- Historical source checkpoint: `npm test` **179/179 PASS**, `npm run typecheck` **PASS**, `npm run build` **PASS** (27.6s). The current 2026-10-29 checkpoint is recorded below; it supersedes those counts for the changed worktree.
- Preview hiện không thể nối backend: `127.0.0.1:3100/` trả 404; `/_migration/site` trả 502; PHP `8080` và API `8088` không listen; PHP CLI không có.
- Preflight vẫn báo flag public OFF và pilot chưa sẵn sàng. Không bật route, không sửa production, không rebuild container.
- Lane 1 screenshots/audit trước đó được giữ làm baseline lịch sử; chúng chụp build cũ. Đợt media-proxy đó xác nhận 0 ảnh `<img>` hỏng, nhưng không chứng minh layout/section parity hiện tại.

## Sẵn sàng route public: Home, Contact, Dealer

Ba public wrapper đã có: `/` (`app/pages/index.vue`), `/lien-he/`
(`app/pages/lien-he.vue`) và `/dai-ly/` (`app/pages/dai-ly.vue`). Mỗi wrapper
dùng lại page-content component của preview để không nhân đôi template. Middleware
kiểm tra lần lượt `homePublicRouteEnabled`, `contactPublicRouteEnabled` và
`dealerPublicRouteEnabled`; cả ba cờ cùng cờ indexable đều mặc định `false`.
Khi cờ route tắt, middleware trả hướng điều hướng ngoài 307 về đúng PHP path.
Preview luôn `noindex`; public content chỉ index khi cờ indexable tương ứng bật.

Trạng thái này xác nhận **code wrapper và fallback đã có**, không xác nhận runtime,
visual parity hoặc cho phép bật cờ. Static tests cho wrapper/fallback đã PASS;
chưa chạy browser hay PHP local cho ba route trong lượt này. Contact/Dealer form
vẫn dùng adapter hiện có; chưa submit form thật. Vì vậy cả ba route còn chờ live
CMS/API, visual review và smoke test an toàn trước khi được xem là sẵn sàng cutover.

## Ma trận route

`CODE FIXED` nghĩa là gap cụ thể đã được sửa trong working tree và có frontend test/typecheck/build. `VISUAL PENDING` nghĩa là phải xem lại ảnh/DOM ở PHP và Nuxt cùng viewport sau khi local API hoạt động. `NEEDS LIVE DATA` cần CMS/DB thật, không được suy đoán.

| Route ID / trang | Section / chức năng đã audit | Trạng thái source | Còn lại để nghiệm thu |
|---|---|---|---|
| `home` — `/` | danh mục, sản phẩm, form tư vấn | CODE FIXED: danh mục CMS hiển thị thành rail một hàng cuộn ngang; form gửi `address` và `subject` qua adapter/endpoint đã validate. Không Hero là chủ ý product-first theo design system. | VISUAL PENDING: mobile/desktop rail và form; xác minh dữ liệu CMS. |
| `catalog` — `/san-pham/` | campaign, benefit, category nav, filter, lưới, CTA | CODE FIXED: tìm tên/mã, filter nhu cầu đèn, filter/CTA/banner đã có trong integration. | VISUAL PENDING: campaign crop, lưới, sidebar/mobile và phân trang. |
| `catalog-category` — danh mục đèn NLMT | banner, subtype nav, filter, use-case/FAQ/CTA | CODE FIXED: category data và nhu cầu dùng query whitelist; CTA/FAQ/category sections đã có. | VISUAL PENDING; xác nhận đúng bản ghi/use-case từ CMS. |
| `product-detail` — PDP | gallery, breadcrumb, metrics, package, giá/CTA | CODE FIXED: metrics/profile, nội dung bộ sản phẩm và breadcrumb hierarchy từ quan hệ danh mục. | VISUAL PENDING: thứ tự section/tabs và field rỗng theo sản phẩm CMS; backend GET chưa chạy. |
| `search` — `/tim-kiem/` | heading, kết quả, filter | Không phát hiện gap P1 mới trong code/API review; kết quả dùng dữ liệu catalog PHP. | VISUAL PENDING: kiểm tra số lượng, filter và query thật. |
| `brand-root` — `/thuong-hieu/` | listing root | Dùng catalog route/DTO hiện có, không tạo surface brand-root riêng. | Xác minh resolver/redirect PHP hiện hành; alias status còn là route gate. |
| `brand-detail` — `/thuong-hieu/{slug}/` | sidebar, giá, sort, sản phẩm | CODE FIXED: category/brand links và price filters trong phạm vi brand; sort theo key PHP. | VISUAL PENDING; xác nhận public-price flag và brand records. |
| `solutions` — `/giai-phap/` | intro, card giải pháp, TOC, liên kết sản phẩm/CTA | Không phát hiện gap P1 mới trong lượt review source; gallery/content theo Admin DTO. | VISUAL PENDING: TOC desktop/mobile, ảnh và nhịp section. |
| `solution-detail` | hero, CKEditor, equipment, CTA | CODE FIXED: CTA campaign cuối trang lấy Admin `solution-cta`, giữ trạng thái hiển thị/hotline/ảnh fallback; TOC hiển thị và xếp dọc trên mobile. | VISUAL PENDING: kiểm tra bố cục TOC, ảnh và CTA với CMS thật. |
| `projects` — `/du-an-da-thuc-hien/` | intro, lợi ích, featured, cards, CTA | CODE FIXED: intro/benefit từ Admin có fallback PHP, mô tả project và CTA đã được projection. | VISUAL PENDING; xác minh số liệu/ảnh CMS. |
| `project-detail` | facts, ảnh/video, dự án liên quan, CTA | CODE FIXED: gallery tabs/lightbox/video dialog, related projects, CTA và trust items Admin/fallback. | VISUAL PENDING: keyboard/focus ở browser thật và trường hợp thiếu media. |
| `news` — `/tin-tuc/` | featured, filters/search, feed, paging, CTA | Source hiện có các khối; không phát hiện gap P1 bổ sung. | VISUAL PENDING: ảnh 16:9, filter rail và responsive density. |
| `news-detail` | metadata, cover, TOC, nội dung, share/related | CODE FIXED: TOC từ sanitized headings, related sau bài viết, optional quote `content1vi` đã sanitize. | NEEDS LIVE DATA: xác minh `content1vi`; VISUAL PENDING. |
| `about` — `/gioi-thieu/` | chứng nhận, CTA, Zalo | CODE FIXED: gallery chứng nhận accessible, featured-first, Zalo URL từ cấu hình CMS. | NEEDS LIVE DATA: hotline/Zalo/certificate; VISUAL PENDING. About hero không được đổi ngoài quyết định design hiện hành. |
| `dealer` — `/dai-ly/` | form, support, stats | CODE FIXED: form responsive, support heading/description bố cục theo PHP; stats là dữ liệu động `solieu`. | NEEDS LIVE DATA: xác nhận stats và ảnh CMS; chưa được tự chọn giữa các bộ số liệu đang tranh luận. |
| `contact` — `/lien-he/` | hero, form, map, thông tin chi nhánh | CODE FIXED: hotline/email/giờ làm việc từ DTO; map dùng URL CMS đã validate; loại đoạn intro trùng. | VISUAL PENDING; map/chi nhánh cần nguồn local. |
| `gallery` — `/thu-vien-anh/` | danh sách, phân trang, empty state | CODE FIXED: bỏ copy marketing viết cứng, empty state khớp template PHP. | VISUAL PENDING theo album thật. |
| `video` — `/video/` | danh sách, phân trang, empty state | CODE FIXED: bỏ copy marketing viết cứng. | VISUAL PENDING theo video thật. |
| `policy-detail` | metadata, TOC, body, share/related | CODE FIXED: giữ ID heading CMS khi tạo TOC; metadata/share/related hiện có. | NEEDS LIVE DATA: `content1vi`; VISUAL PENDING về chiều rộng nội dung/tỷ lệ ảnh liên quan. |
| `cart` — `/gio-hang/` | cart, cập nhật/xóa, điều hướng | CODE FIXED: link resolver public/preview đúng; lỗi mutation được hiển thị; flag route public vẫn OFF. | Cần smoke test session/cart local; không dùng để tạo đơn. |
| `checkout-preview` | dữ liệu checkout/form/submit | CODE FIXED: preview chỉ đọc; bỏ form/order submit; `POST /_migration/checkout` trả 410 và không gọi adapter PHP. | Không phải checkout public; quyết định sản phẩm/luồng thanh toán còn ngoài gate UI. |
| `order-success` — `/cam-on/` | trạng thái xác nhận và link tiếp tục | Source link đi qua route resolver, token không bị làm rơi; route noindex. | NEEDS SAFE SESSION FIXTURE để kiểm tra state thành công, không tạo đơn thật. |
| `legacy-about-alias` | `/ve-chung-toi/` redirect | Hành vi UI sau redirect dùng About preview. | NEEDS HTTP VERIFY: Lane 1 trước đó thấy PHP trả redirect 200 dù fixture kỳ vọng 301. |
| `legacy-dealer-alias` | `/he-thong-dai-ly/` redirect | Hành vi UI sau redirect dùng Contact preview. | NEEDS HTTP VERIFY: Lane 1 trước đó thấy PHP trả redirect 200 dù fixture kỳ vọng 301. |
| `legacy-product-root-alias` | `/product/` redirect | Nuxt mapping có catalog preview; PHP Lane 1 trước đó trả 404 trong khi fixture kỳ vọng 301. | NEEDS HTTP VERIFY/fix route decision; chưa thay resolver/production. |
| `legacy-product-detail-alias` | `/product/{slug}` redirect | PDP preview có canonical slug. | NEEDS HTTP VERIFY: Lane 1 trước đó thấy redirect 200 dù fixture kỳ vọng 301. |

## Các rủi ro còn mở

1. Chưa có browser/visual sign-off sau các thay đổi hiện tại; historical 78 screenshot không được dùng thay nghiệm thu mới.
2. PHP API change chưa được `php -l` hoặc chạy contract PHP vì không có PHP CLI/container. Các test PHP mới có mặt nhưng chưa thực thi.
3. Không kiểm tra DB/CMS, không submit contact/dealer form, không mutate cart/order. Các giá trị About/Dealer/Policy cần xác nhận bằng GET local khi runtime trở lại.
4. Các route public vẫn feature flag OFF; preflight chặn pilot About. Không coi preview 200 là hoàn tất cutover.
5. Build có cảnh báo hiện hữu `INEFFECTIVE_DYNAMIC_IMPORT` và `PLUGIN_TIMINGS`; build exit 0.

## Checkpoint và rollback

- Branch/worktree: `feature/pts-nuxt-integration-20261027`, base HEAD `8133b8b`; source audit đang là diff chưa commit.
- Production, DB, container, Caddy, feature flags và volumes không bị tác động. Không cleanup/backup nào được chạy.
- Rollback checkpoint: bỏ hoặc revert riêng diff audit trong worktree này sau khi lưu lại nếu cần; không dùng reset/clean vì phải giữ file WIP.
- Bước kế tiếp: khôi phục PHP preview/API loopback → `php -l` và chạy PHP contracts → build/restart preview Nuxt local → chụp/review 26 route ở 390/768/1440 → chỉ sửa gap còn thấy trong browser → nghiệm thu lại route/SEO/commerce trước mọi flag change.

## Bổ sung SEO/status — checkpoint source ngày 2026-10-29

Đây là cập nhật source-only trên working tree; giữ nguyên kết quả audit và ảnh
baseline phía trên. Nguồn chọn canonical origin là `arrayDomainSSL` trong
`domains/phucthinhsolar.com/public_html/libraries/config.php` cùng sitemap SEO
đã kiểm tra tại `docs/14-nuxt-migration/SEO-PARITY-AUDIT-20261028.md`. Nuxt chỉ
phát canonical/OG cho origin HTTPS chính xác `phucthinhsolar.com`; không suy ra
domain từ Host header. Route preview tắt metadata public.

| Route / nhóm route | Metadata/status trong source | Trạng thái nghiệm thu |
|---|---|---|
| Home, About, Solutions hub/detail, Contact, Dealer | canonical, OG/Twitter có điều kiện theo public flag và payload; About lỗi upstream trả 404/502 và noindex; lỗi Contact/Dealer giữ noindex | SOURCE PATCH; runtime visual pending |
| Catalog/category, PDP, Brand | canonical + OG/Twitter; filter/query catalog và brand noindex; catalog/brand/PDP lỗi resource hoặc API không soft-200 | SOURCE PATCH; runtime visual pending |
| Search | canonical + OG/Twitter khi payload hợp lệ; luôn noindex; lỗi API public trả 502 | SOURCE PATCH; runtime visual pending |
| News/projects/gallery/video hubs và detail; policy detail | canonical + OG/Twitter theo public flag/payload; trang lọc/phân trang noindex; detail thiếu/invalid payload trả 502 và noindex | SOURCE PATCH; runtime visual pending |
| `/product/{slug}` | Nuxt alias dùng middleware PDP hiện có: route Nuxt bật thì 301 sang `/san-pham/{slug}`; khi tắt thì defer tới PHP route PDP | SOURCE PATCH; HTTP/runtime verify pending |
| Cart, thank-you và các trang migration preview | noindex; không thêm canonical public cho luồng giao dịch hoặc preview | Giữ nguyên chủ ý |

Focused SEO tests `node --test tests/public-seo.test.mjs`: **8/8 PASS**. Final worktree
verification: `npm test` **245/245 PASS**, `npm run typecheck` **PASS**,
`npm run build` **PASS** (19.9s), `git diff --check` **PASS**. Lần build đầu
phát hiện import tương đối trong social-meta composable không được Nitro bundle;
đổi sang alias `#shared/public-seo`, sau đó build sạch. Build còn cảnh báo
`INEFFECTIVE_DYNAMIC_IMPORT` cho middleware được import rõ trong page wrappers
và `PLUGIN_TIMINGS`; không làm build thất bại.

`node scripts/nuxt-cutover-preflight.mjs`: 8 static checks **PASS**, sau đó kết
thúc `BLOCKED` có chủ ý vì About pilot chưa sẵn sàng và feature flag vẫn OFF.
Preflight xác nhận production Caddy/Compose chưa có Nuxt upstream/service; không
thay đổi config hay service.

Đã bổ sung source fixes cho hit target/header/footer keyboard, Contact CTA theo
CMS, Dealer section links/form recovery, About main landmark, metadata/status,
query noindex và `/product/{slug}` alias. Không có visual/runtime verification:
Nuxt `127.0.0.1:3100/` vẫn trả 404, `/_migration/site` trả 502; PHP CLI không có
và Docker Desktop không tích hợp vào WSL. Vì vậy không thể đối chiếu PHP↔Nuxt,
chạy PHP contract/DB, hoặc rebuild container trong lượt này. Public feature flags
tiếp tục OFF; không có deploy hay database/container mutation.

## Bổ sung nghiệm thu route và UI — 2026-10-29

| Phạm vi | Phát hiện / xử lý | Bằng chứng mới | Trạng thái |
|---|---|---|---|
| `/giai-phap/{slug}/` | Route tree lồng slug dưới `giai-phap.vue`, nhưng parent trước đó không render `<NuxtPage>` nên trả HTML hub 200 dù API detail lỗi. Parent nay chỉ fetch/status/meta cho hub và render child outlet cho slug. | Build SSR flag ON: hub và detail đều 502/noindex khi PHP API tắt; detail hiển thị error state của detail, không hiển thị H1 hub. URL thiếu slash 301 tới canonical. | CODE FIXED; CMS/visual pending |
| `/danh-muc-san-pham/{slug}/` | Vue Router đặt catch-all param thành `['den-nlmt', '']` khi path có trailing slash; parser cũ từ chối và fallback nhầm `/san-pham/`. Middleware nay parse slug đã validate từ `to.path`. | Build SSR flag OFF trả 307 đúng `/danh-muc-san-pham/den-nlmt/`; flag ON tới catalog và trả 502/noindex khi API vắng. | CODE FIXED; CMS/visual pending |
| Bare slug bài viết/dự án/album | Khi mọi detail flag OFF, route không cần PHP classifier để biết nội dung: nay chuyển safe slug thẳng về PHP. Khi flag ON, classifier lỗi vẫn fail closed 502. | Build SSR flag OFF: slug bài viết và dự án trả 307 đúng cùng path về PHP thay vì 502 do Nuxt gọi resolver vô ích. | CODE FIXED; live PHP resolver pending |
| Catalog/search/brand fallback query | Chỉ chuyển filter được hỗ trợ, đổi sang tên query PHP (`p`, `is_orderby`, `is_brand`, `is_price`, `keyword`); bỏ query không hợp lệ. | HTTP smoke có `page=2`, `sort=price-desc`, `brand=11`, `keyword=den nlmt`; redirect giữ giá trị và mã hóa URL đúng. | CODE FIXED; PHP behavior pending |
| PDP/cart | Dùng chung `QuantityStepper`, chuẩn hóa 1–999, loại metric không có value/placeholder và hiển thị metric một cột trên mobile; cart đưa subtotal trước quantity trên desktop. | Regression tests commerce UI/fallback pass. | CODE FIXED; session/CMS visual pending |
| Solutions/news/about/projects mobile | Bỏ spacer rỗng 45vh/30vh, ô tìm kiếm Tin tức và intro About đạt 16px, TOC/quick-nav đạt hit-area 44px. | Regression tests editorial/mobile pass. | CODE FIXED; browser visual pending |
| 26 public route mappings | Đã gọi từng `legacyPath` qua build SSR cô lập với flags OFF. 26/26 route có fallback 307, ngoại trừ hai alias About/Dealer trả first-hop 301 nội bộ tới canonical rồi canonical route fallback về PHP; query routing được kiểm riêng. | Không theo redirect đến PHP vì `8080/8088` không listen. Đây chỉ là kiểm tra Nuxt route/middleware, không phải crawler PHP-vs-Nuxt. | ROUTE GATE PASS; PHP chain pending |
| Test/build | Checkpoint trước vòng source re-audit tiếp theo: `npm test` **263/263 PASS**; `npm run typecheck` **PASS**; `npm run build` **PASS** (20.8s); `git diff --check` **PASS**. | Kết quả mới hơn ở bảng bổ sung 2026-10-29 phía dưới là 274/274. | HISTORICAL CHECKPOINT — superseded below |
| Runtime/visual sign-off | PHP/API local không chạy; Nuxt cũ ở `3100` trả 404; không có PHP CLI. Các server cô lập `3101/3102` đã dừng sau smoke test. | Không có ảnh mới được chụp/review bằng mắt; CMS/media thật không tải được. | BLOCKED — cần khôi phục preview PHP/API |

Các cờ bật trong HTTP smoke chỉ nằm ở environment của process build server cô
lập; cấu hình runtime dùng chung giữ nguyên OFF. Không rebuild/restart container,
không đổi Caddy, DB, volumes, production, không stage/commit/push/deploy. Các
trang vẫn chưa được nghiệm thu thị giác và chưa thể kết luận migration đã hoàn
tất cả gate.

## Bổ sung source audit và kiểm chứng fallback — 2026-10-29

| Route/nhóm | Section hoặc hành vi | Kết quả xử lý | Bằng chứng/kiểm tra | Còn phải xác minh |
|---|---|---|---|---|
| `/du-an-da-thuc-hien/` | Phân trang và bộ lọc danh mục | Đã nối `?p=` public vào tham số `page` của DTO; render trước/số trang/tiếp và giữ category trên cả route public/preview. | `projects-pagination.test.mjs` 2/2 PASS; `npm test` 274/274 PASS. | Chưa kiểm tra tập bản ghi/page thật từ CMS. |
| Solution detail | TOC và anchor H2/H3 trong `content` + `extraContent` | ID và TOC được dựng trước SSR bằng helper dùng chung, ID sinh không trùng giữa hai fragment; giữ ID CMS hợp lệ. | `editorial-toc.test.mjs`; kiểm tra duplicate heading/explicit ID và markup trước hydration. | Chưa đối chiếu bài giải pháp thật trong browser. |
| Catalog/category | Slug danh mục hợp lệ nhưng không tồn tại/đang ẩn | PHP trả `404 CATEGORY_NOT_FOUND`; Nitro giữ nguyên 404; CatalogPage ánh xạ 404 thay vì 502. | `catalog-not-found-status.test.mjs` 1/1 PASS. | PHP CLI/API và DB local chưa hoạt động để xác minh response trực tiếp. |
| Cart | Add theo product ID và quote-only session | Add chỉ chấp nhận record `type=san-pham` có status `hienthi`; quote-only từ chối 409 nhưng không xóa giỏ hiện có. | Assertion source-level thêm vào `test-pts-public-api-integration.php`; không gửi mutation hợp lệ. | Chưa chạy PHP lint/integration; PHP CLI và DB chưa khả dụng. |
| Cart/session cookie | Cookie PHP qua Nitro | Khi request Nuxt là HTTPS, cookie trả về có `Secure`; local HTTP preview vẫn dùng được cookie không Secure. Cookie vẫn HttpOnly/SameSite=Lax/Path=/ và session ID được kiểm tra. | `migration-cart-cookie.test.mjs` 3/3 PASS. | Chưa xác minh qua Caddy/TLS triển khai thực tế. |
| News/Projects/Gallery/Video | Fallback 307 khi public flag OFF | Giữ query public được hỗ trợ: News `p/q/category`, Projects `p/category`, Gallery/Video `p`; loại array, query dư và giá trị sai định dạng. | `public-route-query.test.mjs` 3/3 PASS; SSR HTTP smoke trên process cô lập `127.0.0.1:3101` cho 5 URL trả Location đúng; process đã dừng. | PHP đích `:8080` không listen nên chưa kiểm tra redirect chain tới cuối. |
| CSS nền tảng | Chú thích Tailwind/cascade | Xóa chú thích lỗi thời nói còn `shell.css`/`*-preview.css`; giữ nguyên thứ tự nạp `main.css` rồi `tokens.css` và không đổi rule CSS. | Danh sách asset CSS hiện chỉ còn `main.css`, `tokens.css`; build PASS. | Visual cascade vẫn cần browser review khi preview dữ liệu thật chạy lại. |
| Frontend gates | Toàn bộ test/typecheck/build ở source hiện tại | `npm test` **274/274 PASS**; `npm run typecheck` **PASS**; `npm run build` **PASS** (20.5s). | Build cảnh báo bundler `INEFFECTIVE_DYNAMIC_IMPORT` và `PLUGIN_TIMINGS`, không fatal. | Không thay cho PHP contract, API integration hoặc visual review. |
| Manifest 26 route | Page wrapper, middleware/route resolver, Nitro adapter và owner PHP | Audit source không thấy route nào thiếu wrapper hoặc GET adapter; account/Admin/API/media transforms và public SEO authority vẫn do PHP sở hữu. | Đối chiếu toàn bộ `migration-routes.json`, `ROUTE_PARITY_MATRIX.md`, page/middleware/adapter files. | HTTP redirect chain, status với CMS/DB, media, SEO runtime và visual 3 viewport vẫn chưa nghiệm thu. |

Runtime tại thời điểm bổ sung: PHP/API `127.0.0.1:8080` và `:8088` không listen;
PHP CLI không có; Docker Desktop daemon pipe không khả dụng từ WSL. Server Nuxt
cô lập chạy ở `:3101` chỉ để kiểm tra fallback GET, đã dừng. Preview cũ `:3100`
không bị chạm. Các public flag tiếp tục OFF; không sửa DB, container, Caddy,
production hay volume; không stage/commit/push/deploy. Audit này vẫn là source
acceptance, chưa phải sign-off giao diện hoặc cutover.

## Bổ sung audit và sửa lỗi có bằng chứng — 2026-10-29

| Phạm vi | Phát hiện | Xử lý | Kiểm chứng | Còn lại |
|---|---|---|---|---|
| Contact / Dealer | Contact nuốt lỗi tải phiên bảo mật; Dealer báo lỗi khi danh sách tỉnh rỗng nhưng không có retry thực sự. | Báo lỗi rõ, khóa submit khi thiếu CSRF/session, cho refresh lại security + cities. | `corporate-ui-gaps.test.mjs` cùng suite hiện tại; agent focused suite 41/41 PASS. | Chưa chạy GET/POST với PHP/CMS local; không gửi form. |
| About CTA | Nuxt bỏ ảnh nền CTA CMS mà PHP template đang dùng. | Render ảnh CTA theo DTO cùng lớp phủ tương phản. | Regression source test đối chiếu `about_tpl.php` và `about-v2.css`. | Cần xem ảnh CMS thật trong browser. |
| Solution equipment media | Hàm URL nhận thư mục upload nhưng luôn trả `/upload/news/`, làm sai URL ảnh sản phẩm liên quan. | Dùng đúng upload directory truyền vào; giữ `/upload/product/` cho equipment. | `solution-media-adapter.test.mjs`; media/contract focused suite 66/66 PASS. | PHP contract chưa chạy trong môi trường này. |
| Brand / policy canonical | GET production ghi nhận brand và policy trả 200 nhưng canonical lần lượt rơi về `/san-pham/` và slug trần; cả hai sai route contract/manifest. | PHP helper nay map `idb` về `/thuong-hieu/{slug}/` và policy về `/ho-tro/{slug}/`. | Thêm `scripts/test-public-canonical-paths.php` dùng mock query read-only; chưa chạy vì thiếu PHP CLI. | Chạy `php -l` + test mock khi có PHP 7.4/8.1; xác minh HTTP sau khi source lên môi trường phù hợp. |
| PHP bridge redirect | Nitro `$fetch` có thể tự follow redirect upstream; chặn redirect để không rời origin PHP đã allowlist. | `redirect: 'manual'` trong migration bridge. | `migration-api-redirect.test.mjs`; security lane xác nhận suite 278/278 PASS. | Runtime origin escape test chưa chạy với PHP proxy local. |
| Public route fallback | Kiểm tra lại build mới với 26 `legacyPath`, toàn bộ nhận 307 về đúng PHP path; hai alias About/Dealer nhận 301 nội bộ trước khi đến route canonical. | Không thay public flag; giữ fallback chính xác. | Build SSR mới chạy cô lập trên `127.0.0.1:3101`; **26/26 PASS**, process đã dừng. | PHP đích không listen nên chưa theo redirect cuối. |
| Route / SEO live | `/product/` hiện 404 trên production, dù integration router có 301→`/san-pham/`; brand/policy canonical PHP lệch contract nêu trên. | Ghi là production/source drift; không sửa production hoặc để Nuxt che lỗi khi flag OFF. | GET read-only và source/manifest audit. | Khi PHP source được triển khai ở môi trường kiểm thử, chạy lại crawl redirect, canonical, sitemap. |
| Build gates | Frontend test/typecheck/build ở trạng thái mới nhất. | Không còn lỗi frontend trong các gate này. | `npm test` **278/278 PASS**; `npm run typecheck` **PASS**; `npm run build` **PASS** (20.2s); `git diff --check` **PASS**. Build giữ warning `INEFFECTIVE_DYNAMIC_IMPORT`/`PLUGIN_TIMINGS`. | Không thay cho PHP contract, backend integration hoặc browser visual review. |

### Trạng thái runtime và bàn giao — checkpoint cũ, được thay thế ngày 2026-10-29

Thông tin dưới đây được giữ làm lịch sử tại checkpoint `d17f828`; kết quả runtime
mới hơn nằm trong mục **Visual acceptance checkpoint — 2026-10-29** bên dưới.

- Browser Chromium/Playwright có trong WSL cache; giới hạn hiện tại là nguồn dữ liệu
  local: PHP/API `:8080/:8088` không listen, `:3100/` trả 404,
  `:3100/_migration/site` trả 502 và `:8090` đóng. PHP CLI không có; Docker
  Desktop daemon không kết nối với WSL. Vì vậy chưa chụp/review cặp PHP↔Nuxt cho
  26 route × 3 viewport. Không được coi source test hoặc route smoke là nghiệm thu mắt.
- Các lane UI và Batch B đã có trong ancestry nhánh integration; current HEAD là
  `d17f828`, ahead remote 29 commit. Worktree lane còn tồn tại nhưng commit đã
  tích hợp; không merge lại. Batch B có docs WIP riêng, cần giữ nguyên.
- Feature flags public tiếp tục OFF. Không rebuild/restart container, không đổi
  Caddy, DB, production, volumes, không push/deploy. Hai commit mới trong lượt rà
  là `4de8c7e` (solution media path) và `d17f828` (PHP bridge redirect); sửa UI,
  canonical PHP và docs vẫn còn WIP chưa commit.
- Việc mở để thật sự hoàn tất là khôi phục local PHP/API + CMS/DB fixture, chạy
  PHP lint/contracts và crawler SEO/negative fixtures, rồi chụp/review thủ công
  26 route ở 390/768/1440. Dealer stats còn cần đối chiếu CMS; chưa chọn số liệu.

## Visual acceptance checkpoint — 2026-10-29

| Evidence | Result |
|---|---|
| Build under review | Integration worktree `feature/pts-nuxt-integration-20261027`, build completed 2026-10-29; local Nuxt SSR at `127.0.0.1:3103`, PHP reference at `127.0.0.1:8080`, media proxy enabled. Public route flags were enabled only in the local Nuxt process; every indexability flag stayed OFF. |
| First-fold browser gate | `migration-compare.cjs` captured 26 routes × 390×844, 768×900 and 1440×900. 75/78 passed; the three `/cam-on/` cases have no order token and correctly return 404/noindex. No order was created. |
| Nuxt runtime/media | 0 broken media, 0 actionable failed requests, 0 horizontal overflow; maximum measured CLS 0.0855. The PHP reference had 983 broken-media observations and 6 failed requests in this local fixture; these are not Nuxt regressions. |
| Full-page visual review | 25 route pairs captured at 1440×900 for manual section review; success page excluded because no safe success-session token exists. News, news detail and projects were recaptured after the CTA layout fix. |
| Commerce | `/migration-checkout-preview` GET-only page is explicitly read-only, noindex, no payment call and no order creation. Empty cart/session was used. `/migration-order-success` displays the safe no-session state; public `/cam-on/` is 404/noindex without a token and did not call the order-confirmation API. |

Status codes in the route matrix: `OK` = section renders; `DIFF` = deliberate redesign or observable content/layout difference; `EMPTY` = explicit empty state from this local CMS/session; `OPEN` = requires verified source data or product decision; `N/A` = not part of that page. The shared header and footer render on all content routes. The header keeps the approved five navigation groups, logo-to-home behavior and no Home text link; the keyboard/focus shell check passed at all three viewport widths.

| Route | Hero / title | Primary sections | Media / related content | CTA / form / action | Footer | Status / review finding |
|---|---|---|---|---|---|---|
| `/` Home | `DIFF` — product-first, no hero per approved UX | `DIFF` — product/category sections lead; old long company/process sections removed | `OK` — product/application imagery; Nuxt has no broken media | `OK` — contact form and catalog links render | `OK` | Intentional redesign; keep old hero out unless owner changes the approved UX. |
| `/san-pham/` Catalog | `DIFF` — campaign banner crop/placement differs | `OK` — category rail, filters, search, sort, product grid and pagination | `OK` — missing product art has fallback; no broken Nuxt media | `OK` — add-to-cart/quote actions and support CTA render; no mutation submitted | `OK` | Product order and available records differ between local PHP and Nuxt data. |
| `/danh-muc-san-pham/den-nlmt/` Category | `DIFF` — same campaign treatment as catalog | `OK` — selected category, filters, grid and pagination | `OK` — media/fallbacks render | `OK` — CTA and product actions render | `OK` | Horizontal category rail is scrollable; no page overflow. |
| `/san-pham/{slug}` PDP | `DIFF` — updated two-column product summary and quick facts | `OK` — detail, technical specs, FAQ, warranty, reviews and related products | `DIFF` — PHP sample has generic application imagery/video placeholder absent from this Nuxt product payload | `OK` — quantity/cart/consultation surfaces render; form not submitted | `OK` | `OPEN`: decide/map application media and video only from verified CMS fields; do not invent product claims or photos. |
| `/tim-kiem/?keyword=den` Search | `DIFF` — Nuxt uses “Kết quả tìm kiếm” and query-aware title | `OK` — 204 matching records, product grid and sort | `OK` — image fallback shown where catalog data lacks art | `OK` — search and product actions render | `OK` | Nuxt title/H1 is more specific than PHP’s generic product heading. |
| `/thuong-hieu/` Brand root | `OK` — all-product listing heading | `OK` — exact PHP fallback behavior: all-product listing, not a separate brand directory | `OK` | `OK` | `OK` | Route contract intentionally reuses Catalog. |
| `/thuong-hieu/longi/` Brand detail | `DIFF` — brand-specific intro/layout | `OK` — brand products, category/price filters and sort | `EMPTY` — some products have no source image; explicit “Chưa có hình ảnh” fallback | `OK` | `OK` | No broken media; content reflects the local brand payload. |
| `/giai-phap/` Solutions | `OK` — page intro and solution choices | `OK` — category cards and long editorial sections/TOC | `OK` — solution imagery and linked products | `OK` — consultation CTA | `OK` | Layout is intentionally flatter than PHP; no section gap found in full-page review. |
| `/giai-phap/{slug}/` Solution detail | `OK` — image/text hero and title | `OK` — editorial content, navigation/TOC, FAQ and supporting sections | `OK` — related equipment/products | `OK` — consultation CTA | `OK` | PHP rich text remains the content source; structured sections render. |
| `/du-an-da-thuc-hien/` Projects | `OK` — title and featured project | `OK` — filters, featured panel and project cards | `OK` — verified project images and related cards | `OK` — CMS image CTA retains two-column layout | `OK` | Full-page review confirms the project CTA image is present. |
| `/{project-slug}/` Project detail | `OK` — title, summary and project metadata | `OK` — project content and media section | `EMPTY` — this project has no published gallery; explicit empty message, then related projects | `OK` — project consultation CTA | `OK` | No blank media frame or fabricated project evidence. |
| `/tin-tuc/` Knowledge/news | `OK` — lead story and featured stories | `OK` — categories, search, list and pagination | `OK` — article cards and covers | `OK` — CTA now spans the available width when CMS has no CTA image | `OK` | Fixed an actual two-column-with-empty-column bug in `NewsCta.vue`; after fix CTA is one column, full width and 178px high at desktop. |
| `/{news-slug}/` News detail | `OK` — CMS title/date/author and cover | `OK` — article body, sanitized rich text and share links | `OK` — related articles show source images | `OK` — related content and CTA | `OK` | Reading column 1080px inside 1200px main width; no runtime error. |
| `/gioi-thieu/` About | `OK` — company hero and photo | `OK` — timeline, people, benefits, vision/mission and certificates | `DIFF` — Nuxt shows source office/team media where PHP sample had an empty frame | `OK` — consultation CTA | `OK` | Difference improves the visible page; uses supplied company imagery. |
| `/dai-ly/` Dealer | `OK` — campaign hero and section navigation | `OK` — partner/product/support/testimonial sections and registration form | `OK` — product/partner imagery | `OPEN` — stats differ: Nuxt 5+/50+/5000+, PHP sample 0+/0+/0+ | `OK` | Business owner must confirm authoritative dealer, staff and customer totals before acceptance. Do not change numbers from visual inference. |
| `/lien-he/` Contact | `OK` — image/copy hero | `OK` — lead form and branch list | `EMPTY` — map has no configured location; Nuxt explains this instead of showing a blank gray map | `OK` — consultation form rendered; not submitted | `OK` | Company name casing differs by source; verify canonical CMS setting if copy parity is required. |
| `/thu-vien-anh/` Gallery | `OK` — page title | `EMPTY` — no published gallery records in the local CMS fixture | `EMPTY` — no gallery cards to show | `N/A` | `OK` | Both reference and Nuxt render an empty-result state; no route/media error. |
| `/video/` Video | `OK` — page title | `EMPTY` — no published video records in the local CMS fixture | `EMPTY` — no video cards to show | `N/A` | `OK` | Both sides render an empty-result state; no broken embed. |
| `/ho-tro/{policy-slug}/` Policy detail | `OK` — title, date and author | `OK` — table of contents, sanitized policy content and share links | `OK` — related policy cards | `N/A` | `OK` | Wider editorial whitespace/flat design is intentional; section anchors render. |
| `/gio-hang/` Cart | `OK` — empty-cart heading/state | `EMPTY` — current browser session has no items | `N/A` | `OK` — continue-shopping action; cart mutation not tested in this visual pass | `OK` | Route remains noindex. PHP and Nuxt empty-state copy differs by design. |
| `/migration-checkout-preview` Internal checkout preview | `OK` — “Thanh toán” | `EMPTY` — cart/session empty, read-only summary | `N/A` | `OK` — explicit read-only warning; no address collection, payment call or order creation | `OK` | Not a public cutover route. Checkout remains PHP-owned until a separate safe commerce gate passes. |
| `/cam-on/` Order success | `EMPTY` — no order token/session | `N/A` — success receipt cannot be evidenced without a valid safe fixture | `N/A` | `EMPTY` — no order was created; page shows missing-session state | `OK` | Public response is 404/noindex without token; this is expected, not a visual pass for the valid success state. |
| `/ve-chung-toi/` About alias | `OK` — final page is About | `OK` — same content as `/gioi-thieu/` | `OK` | `OK` | `OK` | GET confirms 301 to `/gioi-thieu/`; canonical content is visually the same as About. |
| `/he-thong-dai-ly/` Dealer alias | `OK` — final page is Contact | `OK` — same content as `/lien-he/` | `OK` | `OK` | `OK` | GET confirms 301 to `/lien-he/`; retain the legacy URL redirect. |
| `/product/` Product-root alias | `OK` — final page is Catalog | `OK` — same listing as `/san-pham/` | `OK` | `OK` | `OK` | GET confirms 301 to `/san-pham/`. |
| `/product/{slug}` Legacy product alias | `OK` — final page is PDP | `OK` — same product detail as canonical route | `OK` | `OK` | `OK` | GET confirms 301 to `/san-pham/{slug}`. |

| SEO / route gate | Result | Evidence and remaining work |
|---|---|---|
| Hidden-slug negative fixtures | `NEEDS VERIFICATION` | 1/5 PASS: a real hidden PDP slug returned 404 with no canonical. Other four families have no hidden matching row in the local DB; no fixture was invented. |
| PHP sitemap `<loc>` crawl | `PASS` | 457 unique URLs across one sitemap; 0 failed URLs and 0 failed sitemap files. |
| Internal href mapping | `PASS` for capture/reachability | Captured 26/26 routes. There are route-specific `onlyLegacy`/`onlyPreview` differences for manual review; GET reachability passed 367/367, 0 failed, 0 skipped. Path differences are not automatic failures. |
| Preview indexing / metadata | `NOINDEX` | All tested preview pages stayed `noindex, nofollow, noarchive`; public indexability flags remain OFF. Nuxt preview OG/JSON-LD is incomplete on some families; PHP remains metadata owner before cutover. |

| Files and validation | Result |
|---|---|
| UI fixes | `frontend/app/components/news/NewsCta.vue` and `frontend/app/components/projects/ProjectsCta.vue`: keep two-column CTA only when a CMS CTA image exists. |
| Compare harness | `tools/visual-tests/scripts/migration-compare.cjs`: `--full-page` saves full-page PNGs for human review without embedding huge screenshots or running misleading pixel diffs. |
| Browser artifacts | `frontend/.migration-artifacts/nuxt-public-routes-final-20261029-run5/`, `frontend/.migration-artifacts/nuxt-public-routes-fullpage-20261029/`, `frontend/.migration-artifacts/nuxt-cta-review-fullpage-20261029/`, `frontend/.migration-artifacts/nuxt-checkout-order-review-20261029/` |
| Frontend gates | `npm test` **288/288 PASS**; `npm run typecheck` PASS; `npm run build` PASS; `git diff --check` PASS. Build warnings remain `INEFFECTIVE_DYNAMIC_IMPORT` and `PLUGIN_TIMINGS`; no build error. |
| Production / container | No production change, Docker rebuild, deployment, DB write, form POST or order creation was performed. Local Nuxt preview is serving the rebuilt integration worktree; production flags remain OFF. |

## Rerun nghiệm thu hiện tại — manifest 33 route, 2026-10-29

| Gate | Kết quả | Evidence |
|---|---|---|
| First-fold responsive | `PASS` — 99/99 lượt (33 route × 390×844, 768×900, 1440×900); 0 ảnh Nuxt lỗi, 0 request/action lỗi, 0 overflow, 0 runtime error, 0 preview-link leak. | `frontend/.migration-artifacts/nuxt-final-acceptance-rerun2-20261029/report.json` |
| Full-page capture | `PASS` — 33/33 route ở 1440×900; sau khi sửa H1, hai project-category được chụp lại ở đủ 390/768/1440 (6/6 HTTP 200, 0 media lỗi, 0 overflow/runtime/request lỗi). | `frontend/.migration-artifacts/nuxt-final-fullpage-20261029/` và `frontend/.migration-artifacts/project-category-title-fix-20261029/` |
| Public canonical route | `PASS` — 93/93 lượt (31 route × 3 viewport), HTTP 200 hai phía; đây là gate runtime, khác biệt layout/nội dung trong ảnh không bị biến thành pixel-pass. | `frontend/.migration-artifacts/nuxt-public-canonical-final-20261029/report.json` |
| Shell bàn phím/focus | `PASS` — 3/3 viewport: mở/đóng tìm kiếm, Escape, trả focus, drawer mobile và khóa cuộn nền. | Các `shellAudits` trong report public canonical ở trên. |
| Flags public OFF | `PASS` — 32 path legacy duy nhất fallback tới PHP `:8088`; `/cam-on/` theo đúng PHP legacy hiện trả 200/noindex, không phải 404. | GET với `redirect: manual`; route flags OFF chỉ trong process tạm `:3115`. |
| `/cam-on/` không token | `DIFF / OPEN` — PHP `:8080` và `:8088` trả 200, canonical `/cam-on/`, robots `noindex,nofollow`; Nuxt khi cờ route tạm ON trả 404/noindex và không gọi order-thanks API. Báo cáo cũ ghi PHP 404 là sai; không có token/session an toàn để kiểm tra trạng thái thành công. | GET read-only ngày 2026-10-29; không tạo đơn. |
| Negative hidden routes | `NEEDS VERIFICATION` — 1/5 PASS: một PDP hidden thật trả 404, không redirect, không canonical; catalog/news/project/solution thiếu bản ghi ẩn phù hợp trong DB local nên không bịa slug. | `frontend/.migration-artifacts/seo-audit-20261029-current/report.json`; PDP `tru-den-co-dien-oriana-gl-0612-h2500`. |
| Sitemap `<loc>` | `PASS` — 457 URL trong 1 sitemap; tất cả 200 và URL cuối bằng đúng `<loc>`, 0 redirect/failure. | `frontend/.migration-artifacts/seo-audit-20261029-current/report.json` |
| Internal href mapping | `PASS` — đủ 33/33 route được trích xuất; `onlyLegacy`/`onlyPreview` liệt kê để review thủ công, không tự động fail vì khác path; target reachability 380/380 HTTP 200, 0 bỏ qua. | [SEO summary với danh sách href](/home/admin01/src/pts-nuxt-integration-20261027/frontend/.migration-artifacts/seo-audit-20261029-current/summary.md) và `report.json`; GET read-only. |
| Project category H1 parity | `PASS` — title của cả “Cá nhân” và “Doanh nghiệp” đã được prefix `Dự án`, khớp PHP template; title/H1 đúng tại cả 3 viewport. | `project-category-public-route.test.mjs`; report `project-category-title-fix-20261029/`. |
| Route `tin-tuyen-dung` | `OPEN / NEEDS VERIFICATION` — category CMS có thật, PHP trả 200/index, nhưng không có bài visible có slug; Nuxt hiện 307 fallback về PHP vì category chưa được expose. Đây không phải hidden-article fixture. | Chuyển sang `manualReviewRoutes` trong manifest; GET PHP/Nuxt read-only ngày 2026-10-29; DB evidence ở `EDITORIAL-CATEGORY-ROUTES-20261029.md`. |
| Test/typecheck/build | `PASS` — frontend 307/307, typecheck sạch, build sạch; chỉ còn warning bundler `INEFFECTIVE_DYNAMIC_IMPORT` và `PLUGIN_TIMINGS`. | Kết quả build của snapshot integration đang nghiệm thu. |
| Production/container | Không đổi production, DB, container `:8090`, Caddy hay feature flags production; không gửi form/checkout/order mutation. | Build và process preview cô lập; rollback source vẫn là nhánh integration tại HEAD `d17f828` cộng WIP chưa commit. |

Ký hiệu: `OK` = section đủ và hiển thị; `DIFF` = khác PHP nhưng có chủ ý/được xác nhận; `EMPTY` = không có bản ghi/media trong dữ liệu local và có trạng thái rỗng; `OPEN` = cần dữ liệu hoặc quyết định nghiệp vụ; `N/A` = không áp dụng. Footer/shell ở các route nội dung đều render; alias được đánh giá sau redirect canonical.

| Route | Tiêu đề / hero | Nội dung chính | Media / liên quan | CTA / thao tác | Shell / footer | Kết luận section |
|---|---|---|---|---|---|---|
| `/` Home | `DIFF` | `DIFF` — sản phẩm/danh mục đứng trước, bỏ hero theo UX đã duyệt | `OK` | `OK` | `OK` | Product-first đúng design; không thiếu section bắt buộc. |
| `/san-pham/` Catalog | `DIFF` — banner/cách trình bày khác PHP | `OK` — category, filter, search, sort, grid, pagination | `OK` — ảnh fallback rõ ràng | `OK` — giỏ/báo giá/CTA | `OK` | Nội dung/danh sách khác do tập dữ liệu; không có media hỏng. |
| `/danh-muc-san-pham/den-nlmt/` Category | `DIFF` — campaign treatment | `OK` — rail, sidebar, filter, grid, pagination | `OK` | `OK` | `OK` | Danh mục hoạt động; không có overflow ngang. |
| `/san-pham/{slug}` PDP | `DIFF` — summary/quick-facts mới | `OK` — thông số, mô tả, FAQ, review, related | `PASS WITH INTENTIONAL MEDIA DIFF` — application text and populated buying-guide fields are projected from CMS; no Admin media means no application image | `OK` — quantity/giỏ/tư vấn render; không submit | `OK` | Cập nhật theo re-audit 2026-10-29 bên dưới; không dùng ảnh profile mặc định do PHP template tự chèn. |
| `/tim-kiem/?keyword=den` Search | `DIFF` — H1/title theo query | `OK` — kết quả, sort và grid | `OK` — fallback cho sản phẩm thiếu ảnh | `OK` | `OK` | Khác câu chữ PHP nhưng route và dữ liệu hoạt động. |
| `/thuong-hieu/` Brand root | `OK` | `OK` — fallback danh sách sản phẩm theo PHP | `OK` | `OK` | `OK` | Hành vi route khớp PHP; không phải trang directory brand riêng. |
| `/thuong-hieu/longi/` Brand detail | `OK` | `OK` — filter và sản phẩm LONGi | `EMPTY` — một số sản phẩm không có ảnh nguồn, có fallback | `OK` | `OK` | Empty media khớp dữ liệu; không có ảnh request lỗi. |
| `/giai-phap/` Solutions | `OK` | `OK` — nội dung biên tập, danh mục, TOC | `OK` | `OK` — CTA tư vấn | `OK` | Không thấy section thiếu trong full-page review. |
| `/giai-phap/{slug}/` Solution detail | `OK` | `OK` — nội dung/TOC/FAQ | `OK` — equipment liên quan | `OK` | `OK` | Rich text và các section có cấu trúc render đầy đủ. |
| `/du-an-da-thuc-hien/` Projects | `OK` | `OK` — featured, filter, project cards | `OK` — ảnh dự án/CTA CMS | `OK` | `OK` | CTA và card có dữ liệu nguồn. |
| `/danh-muc-du-an/du-an-ca-nhan/` Project category cá nhân | `OK` — “Dự án Cá nhân” khớp PHP | `OK` — danh sách theo nhóm | `OK` | `OK` — lọc và tư vấn | `OK` | Route/category hoạt động. |
| `/danh-muc-du-an/du-an-doanh-nghiep/` Project category doanh nghiệp | `OK` — “Dự án Doanh nghiệp” khớp PHP | `EMPTY` — không có dự án trong nhóm ở fixture, giống PHP | `N/A` — không có card dự án | `OK` — CTA vẫn render | `OK` | Chụp lại sau fix H1; empty state khớp nguồn PHP. |
| `/{project-slug}/` Project detail | `OK` | `OK` — metadata và nội dung | `EMPTY` — chưa có gallery published; thông báo rõ | `OK` — related/CTA | `OK` | Không dựng gallery hay bằng chứng dự án giả. |
| `/tin-tuc/` Knowledge/news | `OK` | `OK` — featured/category/search/list/pagination | `OK` | `OK` — CTA co một cột khi không có ảnh CTA | `OK` | Không còn cột CTA trống. |
| `/tin-moi-nhat` News category mới nhất | `OK` | `OK` — bài theo category | `OK` | `OK` | `OK` | Khớp category CMS đã xác minh. |
| `/tin-truyen-thong` News category truyền thông | `OK` | `OK` — bài theo category | `OK` | `OK` | `OK` | Khớp category CMS đã xác minh. |
| `/kien-thuc-san-pham` News category kiến thức | `OK` | `OK` — bài theo category | `OK` | `OK` | `OK` | Khớp category CMS đã xác minh. |
| `/doanh-nghiep` Solution category doanh nghiệp | `OK` | `OK` — category và solution đại diện | `OK` | `OK` — CTA liên hệ | `OK` | Route public/DTO/category nav hoạt động. |
| `/ho-gia-dinh` Solution category hộ gia đình | `OK` | `OK` — solution đại diện | `EMPTY` — ảnh nhóm không có trong nguồn, fallback rõ | `OK` | `OK` | PHP sample cũng không có ảnh category. |
| `/{news-slug}/` News detail | `OK` | `OK` — ngày/tác giả/rich text/share | `OK` — cover và related | `OK` — CTA/related | `OK` | Không thấy lỗi runtime trong article detail. |
| `/gioi-thieu/` About | `OK` | `OK` — lịch sử, đội ngũ, tầm nhìn, chứng nhận | `DIFF` — Nuxt dùng ảnh công ty nguồn có sẵn | `OK` — CTA CMS có ảnh | `OK` | Nội dung media tốt hơn fixture PHP đang thiếu ảnh. |
| `/dai-ly/` Dealer | `OK` | `OK` — lợi ích, hỗ trợ, đối tác, đăng ký | `OK` | `OPEN` — số liệu Nuxt `5+/50+/5000+`, PHP fixture `0+/0+/0+` | `OK` | Chờ chủ nghiệp vụ xác nhận số liệu; không tự sửa. |
| `/lien-he/` Contact | `OK` | `OK` — địa chỉ/chi nhánh và form | `EMPTY` — chưa có tọa độ/map cấu hình, có lời giải thích thay chỗ trống | `OK` — form hiện diện, không submit | `OK` | Thiếu cấu hình map nguồn, không phải lỗi tải media. |
| `/thu-vien-anh/` Gallery | `OK` | `EMPTY` — CMS không có gallery published | `EMPTY` | `N/A` | `OK` | Empty state hiển thị tương ứng PHP. |
| `/video/` Video | `OK` | `EMPTY` — CMS không có video published | `EMPTY` | `N/A` | `OK` | Empty state hiển thị tương ứng PHP. |
| `/ho-tro/{policy-slug}/` Policy | `OK` | `OK` — TOC, nội dung policy, anchors, share | `OK` — related | `N/A` | `OK` | Bố cục phẳng/rộng hơn PHP, nội dung chính đầy đủ. |
| `/gio-hang/` Cart | `OK` | `EMPTY` — session hiện rỗng | `N/A` | `OK` — tiếp tục mua; chưa mutation | `OK` | Route `noindex`; empty state rõ ràng. |
| `/migration-checkout-preview` Checkout nội bộ | `OK` | `EMPTY` — cart rỗng | `N/A` | `OK` — cảnh báo read-only; không nhận địa chỉ/thu tiền/tạo đơn | `OK` | Không phải checkout public/cutover. |
| `/cam-on/` Order success | `DIFF` — PHP hiện luôn trả trang thành công chung 200; Nuxt không token trả 404 | `EMPTY` — không có phiên đơn để kiểm tra | `N/A` | `OPEN` — không có token/session an toàn | `OK` | Status PHP/flag ON khác nhau; giữ noindex, cần safe fixture để nghiệm thu state thành công. |
| `/ve-chung-toi/` About alias | `OK` sau 301 | `OK` — cùng About | `OK` | `OK` | `OK` | Redirect canonical `/gioi-thieu/`. |
| `/he-thong-dai-ly/` Dealer alias | `OK` sau 301 | `OK` — cùng Contact | `OK` | `OK` | `OK` | Redirect canonical `/lien-he/`. |
| `/product/` Product root alias | `OK` sau 301 | `OK` — cùng Catalog | `OK` | `OK` | `OK` | Redirect canonical `/san-pham/`. |
| `/product/{slug}` Product alias | `OK` sau 301 | `OK` — cùng PDP | `OK` | `OK` | `OK` | Redirect canonical `/san-pham/{slug}`. |

## Re-audit delta — PDP và Đại lý — 2026-10-29

| Gate | Kết quả mới | Bằng chứng / quyết định |
|---|---|---|
| PDP endpoint trên toàn sitemap | `PASS` — 372/372 slug PDP trả HTTP 200 qua `:8088`; không có lỗi API. | GET-only crawl `/sitemap.xml` và `/api/pts_v1_product_detail.php`; không in hoặc lưu payload cá nhân. |
| PDP section Ứng dụng | `PASS WITH INTENTIONAL MEDIA DIFF` — 352 sản phẩm có block ứng dụng từ CMS; không có heading ứng dụng nào còn trong mô tả sau khi được tách; 20 sản phẩm không có block ứng dụng CMS nên Nuxt không tự dựng copy. | `ptsPdpApplicationsDto()` lấy text/media từ block CMS, tối đa 3 mục. PHP legacy thêm ảnh và nội dung code-default theo profile, nhưng design system đã duyệt yêu cầu chỉ render ảnh ứng dụng do Admin quản lý; Nuxt chỉ hiện ảnh khi CMS có media hợp lệ. Đây là khác biệt chủ ý, không phải thiếu tải ảnh. |
| PDP “Thông tin cần biết trước khi lựa chọn” | `PASS` — 344 sản phẩm có ít nhất một hướng dẫn, tổng 935 mục suy ra từ field kỹ thuật có dữ liệu; không thêm giá trị mẫu. | `ptsPdpBuyingGuidesDto()` chỉ dùng field profile đã khai báo và giá trị thực từ CMS; sản phẩm thiếu field không có placeholder. |
| Dealer stats | `PASS` — PHP `:8080`, PHP migration `:8088` và template PHP cùng trả `5 / 50 / 5000` cho năm kinh nghiệm, nhân sự, khách hàng. | Các hàng trước ghi PHP fixture `0 / 0 / 0` đã bị dữ liệu fixture cũ làm nhiễu; GET mới xác nhận CMS local thật. Nuxt tiếp tục đọc stats từ DTO, không hardcode số. |
| Dealer rich content | `FIXED IN SOURCE` — DTO xuất `descriptionContent` đã qua `ptsPublicRichTextSanitize()` cho phần mở đầu hỗ trợ và từng quyền lợi; Nuxt render bằng `EditorialRichText`. | PHP đang có nhiều đoạn `<p>`/danh sách trong phần này; trước đây DTO làm phẳng thành một chuỗi. Contract kiểm tra metadata sanitizer và component dùng màu chữ phù hợp nền tối. |
| PHP origin cho local Nuxt | `PASS WHEN USING PREVIEW LAUNCHER`; nếu chạy thủ công phải đặt origin `:8088`. | `:8080` là PHP legacy, hiện thiếu `intro`/rich support DTO; `scripts/nuxt-preview-launch.py` đặt `NUXT_PHP_ORIGIN=http://127.0.0.1:8088`, đúng với `LOCAL-PREVIEW.md`. Không đổi default hoặc service đang chạy của agent khác. |
| Kiểm chứng code và giao diện sau delta | Frontend `311/311` tests PASS; typecheck/build PASS trong bản sao cô lập; PHP lint và GET DTO helper PASS. Hai route PDP/Dealer được chụp ở 390×844, 768×900, 1440×900; 6/6 preview health gates PASS; đã review thủ công ảnh desktop full-page và responsive. | Ảnh ở `frontend/.migration-artifacts/delta-ui-review-20261029/`; `diff=not-compared` nên không claim pixel parity tự động. PHP reference có 10 PDP/15 Dealer broken-media requests; Nuxt không có broken media hoặc runtime/overflow/request failures. Chưa rebuild/restart container Nuxt. |
| Production / public flags | Không thay đổi; các route/indexability flags giữ OFF. | Không gửi form/checkout POST, không ghi DB, không sửa hay restart container. |

## Re-audit delta — worktree `feature/ui-layout-upgrade-20261029` — 2026-10-29

Worktree hiện ở `c729ca7` với thay đổi UI/API chưa commit. Phần dưới đây là
bằng chứng của đúng build trong worktree này; nó không thay thế runtime hoặc
production sign-off của nhánh integration.

| Gate | Kết quả | Bằng chứng |
|---|---|---|
| Browser route health | `PASS` — 33 route × 3 viewport = 99/99; HTTP 200, một H1, không lỗi runtime, media, failed request, tràn ngang hoặc preview-link leak. Full-page capture đã lưu; đây là health gate, không phải kết luận pixel-perfect. | `frontend/.migration-artifacts/final-ui-parity-20261029/report.json` và `summary.md`; PHP `:8080`, Nuxt `:3110`, media proxy bật. |
| Public routes, flags OFF | `PASS` — 33/33 URL kết thúc tại PHP `:8080`, status 200; bốn alias theo đúng canonical đích. Query token của `/cam-on/` được giữ khi fallback. | GET với redirect follow trên process `:3110`; không gửi mutation. |
| Public routes, flags ON tạm thời | `PASS` — 33/33 URL render qua Nuxt; title/H1 có, canonical khớp fixture, robots đúng; search/cart/order-success giữ noindex; bốn alias kết thúc tại canonical Nuxt. Flags chỉ được set cho process cô lập `:3159`, không ghi cấu hình. | HTTP GET tới production path trên `.output/server/index.mjs`; không deploy, không bật flag mặc định. |
| `/cam-on/` không token | `PASS` — PHP và Nuxt flag ON đều trả 200/noindex với lời cảm ơn chung; Nuxt không gọi endpoint order-thanks nếu thiếu token, không hiển thị mã đơn và không fire conversion. Có breadcrumb như PHP. | [PHP 1440px](</home/admin01/src/pts-ui-layout-upgrade-20261029/frontend/.migration-artifacts/order-success-parity-20261029/order-success-1440x1000-legacy.png>) · [Nuxt 1440px](</home/admin01/src/pts-ui-layout-upgrade-20261029/frontend/.migration-artifacts/order-success-parity-20261029/order-success-1440x1000-preview.png>) · [đối chiếu 3 viewport](</home/admin01/src/pts-ui-layout-upgrade-20261029/frontend/.migration-artifacts/order-success-parity-20261029/summary.md>). |
| Sitemap `<loc>` | `PASS` — 457 URL trong một sitemap; mọi status 200 và final URL trùng `<loc>`, không redirect. | `frontend/.migration-artifacts/seo-audit-final-20261029/report.json`. |
| Href mapping/reachability | `PASS` — 33/33 route được so set; 361/361 target HTTP 200. `onlyLegacy`/`onlyPreview` vẫn là khác biệt cần review thủ công, không tự động fail. | `frontend/.migration-artifacts/seo-audit-final-20261029/summary.md`. |
| Hidden/negative fixtures | `NEEDS VERIFICATION` — PDP ẩn thật trả 404, không canonical; catalog/news/project/solution không có hidden row phù hợp trong DB local nên không bịa slug (4 fixture chưa xác minh). | Cùng SEO report; giữ nguyên bằng chứng DB và trạng thái `NEEDS VERIFICATION`. |
| Frontend verification | `PASS` — 360/360 test, typecheck, build và smoke SSR; order-success được chụp riêng 3/3 viewport sau lần sửa cuối. | `npm test`, `npm run typecheck`, `npm run build`, `npm run smoke`; artifact ở `order-success-parity-20261029/`. |
| Giới hạn còn lại | Session token thật, mã đơn và conversion không được thử vì không tạo đơn. Full-page ảnh của mọi route đã được chụp nhưng chưa ký review bằng mắt từng section. OG/JSON-LD cho từng public route chưa được xác nhận trong lượt flags-on này. | Không có PHP POST, không ghi DB, không thay container/feature flag production. Worktree còn WIP; chưa commit/gộp/deploy. |

## Visual follow-up — news detail related rail — 2026-10-29

| Hạng mục | Kết quả | Bằng chứng |
|---|---|---|
| Tin liên quan | `FIXED IN WORKTREE` — dải hiển thị 4 bài desktop, 2 tablet, 1 mobile; nút trước/tiếp cuộn được; ngày dùng đủ năm và có tên tác giả như template PHP. | `NewsDetailRelated.vue`; PHP query `pts_v1_news_detail.php` lấy tên tác giả công khai qua `#_user`, DTO kiểm tra author. |
| Visual/runtime | `PASS` — PHP và Nuxt trả 200 ở 390×844, 768×900, 1440×1000. Review bằng mắt xác nhận 4 bài hiển thị mỗi lượt desktop; tương tác mũi tên đổi scrollLeft; 0 lỗi runtime/request, 0 overflow ngang. | `frontend/.migration-artifacts/news-detail-related-fix-20261029/`; report phân loại 5 ảnh `src=""` do widget dịch GTranslate chèn (alt là mã locale), không phải ảnh bài viết. |
| Kiểm thử | `PASS` — 362 frontend test, typecheck, build; test riêng rail 9/9; PHP API GET read-only trả author trong DTO. PHP CLI không có trong WSL nên không chạy được `php -l`. | Kết quả `npm test`, `npm run typecheck`, `npm run build`; GET `:8088/api/pts_v1_news_detail.php` không làm thay đổi dữ liệu. |
| Vận hành | Không thay production, Docker/container hoặc cờ public; preview Nuxt `:3110` chạy build mới, `:3157` được giữ nguyên. | Process và port được kiểm tra sau khi restart riêng Nuxt preview `:3110`. |

## Final verification — `feature/ui-layout-upgrade-20261029` — 2026-10-29 21:17 +07:00

| Gate | Kết quả | Bằng chứng |
|---|---|---|
| Nuxt route health with public flags ON | `PASS` — 33/33 manifest routes trả HTTP 200, đúng H1/canonical/noindex; hidden PDP fixture trả 404, không canonical. | `frontend/.migration-artifacts/nuxt-route-gates-20261029-final/report.json` |
| Public flag OFF fallback | `PASS` — 33/33 route kết thúc ở PHP loopback `:8080`; redirect chain được kiểm tra thủ công với `redirect: manual`. | `frontend/.migration-artifacts/nuxt-route-gates-20261029-final/flag-off.json` |
| Slash canonical redirects | `PASS` — root/hub thiếu slash trả 301 thêm slash; PDP có slash cuối trả 301 về slug không slash. | GET smoke trên Nuxt `:3112`, chỉ route flags ON |
| PHP ↔ Nuxt visual health | `PASS` — 99/99 lượt (33 route × 390/768/1440) HTTP 200, không lỗi runtime, media, request, overflow, CLS hoặc preview-link leak; shell keyboard/focus 3/3 PASS. Full-page PNG dùng cho review section-by-section. | `frontend/.migration-artifacts/migration-comparison-20261029-final5/report.json` và `summary.md` |
| Adapter/query safety | `PASS` — product/project/document/extras/order/checkout-wards adapters chỉ nhận query PHP hỗ trợ, validate slug và bounds. | `frontend/server/routes/_migration/*.get.ts`; 364 frontend tests và typecheck PASS |
| Remaining gates | `NEEDS VERIFICATION` — bốn nhóm hidden fixture không có record phù hợp trong DB local; order-success token/conversion không thử vì không tạo đơn; sitemap/robots authority và staging/cutover vẫn do PHP/approval gate. | Không bịa fixture, không POST, không đổi production/container/feature flags mặc định. |
