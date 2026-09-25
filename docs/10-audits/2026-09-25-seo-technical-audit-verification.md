# Đối chiếu audit SEO/kỹ thuật do ChatGPT thực hiện với trạng thái live thật

Ngày: 2026-09-25
Nguồn gốc: chủ project paste một báo cáo audit 18 mục do ChatGPT (web-search) thực hiện trên
`phucthinhsolar.com`. Tài liệu này ghi lại kết quả **kiểm chứng trực tiếp** (curl + Playwright
browser thật trên domain production) từng claim trước khi đưa vào backlog, theo nguyên tắc
"không suy đoán, ưu tiên bằng chứng độ tin cậy cao hơn" (CLAUDE.md).

Kết luận quan trọng nhất: báo cáo gốc dùng công cụ đọc web không thực thi/parse đúng thẻ
`<base href>` mà site đang dùng ở mọi trang, dẫn tới **2 trong 4 lỗi P0 được báo là false
positive** (đã sửa từ trước hoặc chưa từng xảy ra với người dùng/Googlebot thật). Các claim còn
lại được kiểm chứng độc lập, phần lớn đúng.

## Phương pháp kiểm chứng

- `curl -I` / `curl -L` trực tiếp vào `phucthinhsolar.com` (production, không phải local) để xem
  status code, redirect chain, header response thật.
- Playwright (Chromium thật) để test hành vi mà chỉ browser mới thực thi đúng: `<base href>`
  resolution, click thật vào product card, đọc `element.href` sau khi DOM parse.
- Không sửa code/DB trong phiên làm việc này — tài liệu này chỉ là kết quả xác minh + checklist,
  chưa triển khai.

---

## Phần A — 4 lỗi P0 trong báo cáo gốc

### A1. "www và non-www là hai website khác nhau" — ❌ SAI / đã được fix từ trước

Test trực tiếp:

```
http://phucthinhsolar.com/      → 301 → https://phucthinhsolar.com/
http://www.phucthinhsolar.com/  → 301 → https://phucthinhsolar.com/
https://www.phucthinhsolar.com/ → 301 → https://phucthinhsolar.com/
```

Header xác nhận Caddy đang 301 đúng, cả 2 bản đều ra title
"Phúc Thịnh Solar | Thiết bị & giải pháp năng lượng mặt trời" (site mới). Không có website cũ nào
còn sống ở `www`. Nhiều khả năng ChatGPT đọc bản cache cũ của Google (crawl trước khi redirect
được thiết lập), không phải trạng thái live.

**Hành động: không cần làm gì.**

### A2. "Product card sinh URL `/san-pham/san-pham/...` → 404" — ❌ SAI với người dùng/Googlebot thật

Nguồn gốc claim có thật ở mức code: `templates/product/_product_card.php` và nhiều template khác
dùng `href="san-pham/<?=$slug?>"` — thiếu dấu `/` đầu, là relative URL.

Nhưng site có `<base href="https://phucthinhsolar.com/"/>` trong `<head>` mọi trang
(`templates/layout/head.php:2`). Thẻ `<base>` là HTML tĩnh (không cần JS), mọi trình duyệt và
Googlebot (renderer Chromium evergreen) đều tuân theo — nó buộc **mọi** relative URL trên trang
resolve về root site, bất kể độ sâu URL hiện tại.

Verify bằng Playwright thật:

```js
// Tại /danh-muc-san-pham/den-nlmt/
page.$$eval('a.product-item', els => els.map(e => e.href))
// → "https://phucthinhsolar.com/san-pham/den-duong-...jd-19600l-600w"  (ĐÚNG)

// Click thật vào card đầu tiên
await page.click('a.product-item')
// → page.url() = ".../san-pham/den-duong-...jd-19600l-600w"  (vào đúng PDP, không 404)
```

Công cụ ChatGPT dùng để "click" nhiều khả năng chỉ resolve relative URL theo URL hiện tại
(`urljoin` kiểu thư viện HTTP thuần), không tôn trọng `<base>` — nên tạo ra chuỗi 404 giả.

**Hành động: không khẩn cấp.** Dù vậy đây vẫn là code fragile/không theo best practice (toàn bộ
site phụ thuộc 1 thẻ `<base>` duy nhất để link nội bộ không vỡ) — có thể dọn dần sang absolute path
(`/san-pham/...`) như một việc dọn code chất lượng, **không phải P0 khẩn**, xếp P2.

### A3. "URL bài viết cũ và `/tin-tuc/...` trùng nhau, duplicate content" — ⚠️ ĐÃ SỬA LẠI KẾT LUẬN sau khi đọc code — không phải bug

Lượt trước tôi kết luận đây là P1 cần sửa. Sau khi đọc trực tiếp
`libraries/product_category_urls.php` (hàm `publicCanonicalEntityPath()`) và đối chiếu với
`sitemap.xml` + link nội bộ thật, kết luận đó **sai** — cần đính chính:

```
/8-ly-do-ban-nen-su-dung.../          → HTTP 200, canonical → bare slug (không /tin-tuc/)
/tin-tuc/8-ly-do-ban-nen-su-dung.../  → HTTP 200, canonical → bare slug (không /tin-tuc/)
```

Nhìn riêng thì giống duplicate content. Nhưng đối chiếu thêm 2 nguồn:

- `sitemap.xml` live: `<loc>https://phucthinhsolar.com/8-ly-do-ban-nen-...</loc>` — publish đúng
  **bare slug**, không có `/tin-tuc/`.
- Link thật trong trang hub `/tin-tuc/`: `href="8-ly-do-ban-nen-...-ngay-bay-gio/"` — cũng dùng
  **bare slug**.

Tức là canonical tag + sitemap + internal link **hoàn toàn nhất quán** với nhau — cả 3 đều đồng ý
bare slug là "URL thật". `/tin-tuc/slug/` chỉ là 1 alias route (do router có fallback chung, xem
`libraries/router.php:46-49`) vẫn crawl được nhưng tự khai canonical trỏ về bare slug — đây chính
là cách làm đúng theo hướng dẫn của Google khi có nhiều URL cho cùng nội dung (dùng
`rel=canonical` nhất quán), không phải lỗi.

Code còn có 1 dòng comment xác nhận đây là chủ đích (`product_category_urls.php:93-96`):
"Chuẩn hoá về bare slug — đúng dạng sitemap đang công bố."

**Hành động: không cần sửa gì cho case bài viết.** Bỏ khỏi backlog P1.

**Trường hợp category (`/den-nlmt` vs `/danh-muc-san-pham/den-nlmt/`) thì ngược lại — đã đúng từ
đầu:** sitemap + menu chính đều dùng bản dài `danh-muc-san-pham/den-nlmt/`, và canonical của bản
ngắn đã trỏ đúng về bản dài. Không có gì cần sửa ở đây nữa.

### A3-bis. Phát hiện thật khi đối chiếu kỹ hơn: BreadcrumbList JSON-LD dùng sai dạng URL

Khi so sánh canonical/sitemap/menu (đều đúng, nhất quán) với **structured data** trên trang chi
tiết sản phẩm, phát hiện lệch thật:

```
JSON-LD BreadcrumbList trên PDP JD-19600L:
  "item":"https://phucthinhsolar.com/san-pham"                                  (thiếu / cuối)
  "item":"https://phucthinhsolar.com/den-nlmt"                                  (bare, không phải danh-muc-san-pham/den-nlmt/)
  "item":"https://phucthinhsolar.com/den-duong"                                 (bare, 301 redirect chứ không phải URL thật)
  "item":"https://phucthinhsolar.com/den-duong-nang-luong-mat-troi-...-600w"    (bare, thiếu san-pham/ — khác canonical thật của chính trang này)
```

Cả 4 URL này đều "sống" (200 hoặc 301, không 404) nhưng **không khớp** với canonical/sitemap/menu
của cùng entity. Google đọc riêng BreadcrumbList structured data để tạo breadcrumb rich result
trên SERP — lệch URL ở đây không gây mất index (không phải lỗi nặng) nhưng là tín hiệu không nhất
quán, nên sửa cho khớp.

**Hành động: P2 thật** — sửa nơi sinh JSON-LD BreadcrumbList để dùng cùng hàm
`publicCanonicalEntityPath()`/`publicProductEntityPath()` đang dùng cho thẻ canonical, thay vì tự
build URL riêng.

### A4. "Crawl toàn site tìm thêm 404" — ⏳ Chưa làm (cần thời gian riêng)

Không nằm trong 3 claim cụ thể trên, cần 1 lượt crawl có công cụ (không thể làm bằng tay từng
trang). Xếp vào backlog kỹ thuật, dùng script/sitemap thay vì audit thủ công.

### A-NAP. Địa chỉ không đồng nhất (claim #14) — ✅ đúng, đã tìm ra root cause chính xác

Diff nội dung `/lien-he/` production tìm được đúng 2 vị trí dùng `TP.Pleiku` (khác với mọi nơi
khác dùng `Phường Hội Phú`):

1. Nhãn "Địa chỉ" đầu trang: `<p>424 Trường Chinh, TP.Pleiku, Gia Lai</p>`.
2. Organization JSON-LD: `"address":{"streetAddress":"424 Trường Chinh, TP.Pleiku, Gia Lai",...}`.

Cả 2 cùng đọc từ **1 field duy nhất**: `Setting.address` (Admin > Thiết lập thông tin > "Địa
chỉ"). Các vị trí còn lại ("Trụ sở chính"/"Kho Gia Lai" card, footer, legal notice) đọc từ nguồn
khác (`table_news` type `he-thong-cua-hang`) — nguồn này đã cập nhật đúng theo đợt đổi địa giới
hành chính 2 cấp (phường/tỉnh) nhưng `Setting.address` trên production thì chưa.

Kiểm tra local: `Setting.address` local **đã đúng** (`Phường Hội Phú`) — nghĩa là local từng được
cập nhật nhưng production chưa đồng bộ theo.

**Hành động: chỉ cần sửa 1 field trên Admin production**, không cần code, không cần deploy. Local
không cần làm gì (đã đúng sẵn).

---

## Phần B — Đối chiếu các claim còn lại (không phải P0)

| # | Claim gốc | Kết quả verify | Đánh giá |
|---|---|---|---|
| 4 | Taxonomy lẫn khung/kẹp vào "Tấm pin năng lượng mặt trời" | **ĐÍNH CHÍNH SAU KHI ĐỌC MIGRATION LOG:** đây là quyết định **có chủ đích**, thực hiện 2026-09-22 (`migrations/047_normalize_product_top_categories.php`, xem `docs/13-handoffs/2026-09-22-product-category-normalization.md`) — cố ý gộp "Hệ khung & phụ kiện tấm pin" (10→9 category cấp 1) vào làm con của Tấm pin, có backup/rollback/301 redirect URL cũ đầy đủ | ❌ **Không phải bug** — ChatGPT đang chê đúng 1 quyết định kinh doanh gần đây. **Không di chuyển lại**, trừ khi chủ project chủ động muốn đảo ngược quyết định 22/9 |
| 14 | NAP (địa chỉ) không đồng nhất | **Xác nhận đúng**, thậm chí ngay trong cùng 1 trang `/lien-he/` xuất hiện cả "Phường Hội Phú" và "TP.Pleiku" | ✅ Thật |
| 15 | Language selector (VI/EN/中文...) không hoạt động thật | Xác nhận: các link dùng `href="#" data-gtlang="..."` — đây là widget Google Translate client-side, không phải route `/en/` thật | ⚠️ Đúng một phần: nó *có* chạy được (Google Translate dịch tại chỗ), không phải link chết hoàn toàn như báo cáo mô tả, nhưng cũng không phải i18n/hreflang thật cho SEO đa ngôn ngữ |
| 7 | Structured Data Product | Đã có sẵn `Product`, `Brand`, `BreadcrumbList` (`@type` xác nhận qua JSON-LD live) | ⚠️ Đã có nền, nhưng **thiếu `Offer`/giá và `AggregateRating`/`Review`** — đúng 1 phần gợi ý của báo cáo (nên bổ sung, không phải làm từ đầu) |
| 5, 6, 8, 9, 11, 12, 13, 16, 17 | Content/UX/editorial (SEO category text generic, form quá dài, dự án thiếu case study, content cũ có claim quá đà, title pattern...) | Không kiểm chứng kỹ thuật — đây là nhận định biên tập/nội dung, đúng sai tuỳ góc nhìn, cần chủ project quyết định trực tiếp chứ không phải việc "đúng/sai" như bug | 📝 Ghi nhận, đưa vào backlog nội dung riêng |
| 10 | Thêm khối "Thương hiệu & chứng nhận" lên trang chủ | — | ⛔ **Bỏ qua theo yêu cầu chủ project** — trang chủ giữ nguyên, không thêm trang/khối chứng nhận |

---

## Phần C — Checklist theo mức ưu tiên đã hiệu chỉnh lại

Khác với bảng ưu tiên gốc của ChatGPT (vốn đặt sai P0 cho 2 việc đã fix/không xảy ra), đây là
checklist đã hiệu chỉnh theo bằng chứng thật:

### P0 — Không có mục nào (2 claim P0 gốc đã bị bác bỏ qua kiểm chứng)

### P1 — Thật, ảnh hưởng SEO/dữ liệu/người dùng thật, cần chốt hướng rồi làm

- [x] **[FIX MỚI, quan trọng nhất phiên]** Anchor link `href="#muc"` bị `<base href>` đá về trang
      chủ trên 4 trang (Giới thiệu, Đại lý, Giải pháp list/detail) — đã sửa bằng
      `$func->getCurrentPagePath()`, verify bằng click thật. Xem D1.
- [x] ~~Canonical bài viết `/tin-tuc/slug/` vs `/slug/`~~ — **đã đính chính, không phải bug** (xem
      A3), không cần sửa.
- [x] ~~Route danh mục ngắn `/den-nlmt`~~ — **đã đúng từ đầu** (xem A3), không cần sửa.
- [x] ~~Chuyển sản phẩm khung/kẹp ra khỏi "Tấm pin"~~ — **KHÔNG LÀM**, đây là quyết định có chủ
      đích ngày 2026-09-22 (migration 047), production đã áp dụng đúng (xem D3), không phải bug.
- [x] Chuẩn hoá NAP — root cause đã xác định: field `Setting.address` (Admin > Thiết lập thông
      tin > Địa chỉ) trên **production** vẫn còn `TP.Pleiku` (giá trị cũ trước đợt đổi địa giới
      hành chính), trong khi mọi nơi khác đã đổi sang `Phường Hội Phú`. Local đã đúng sẵn — chỉ
      cần chủ project tự sửa 1 field này trên Admin production (không cần code, ~1 phút). Phạm vi
      ảnh hưởng chỉ các trang "chung", không phải PDP/bài viết (xem D4). Nhân tiện đã bổ sung
      `telephone`/`email` còn thiếu hẳn trong Organization schema (code, đã sửa ở local).

### P2 — Thật nhưng không khẩn, dọn dần

- [ ] Sửa BreadcrumbList JSON-LD trên PDP (và category) đang tự build URL bare-slug lệch khỏi
      canonical/sitemap/menu (`sources/product.php` gọi `$breadcr->set($slug,...)` với slug rời,
      không có tiền tố cấp cha) — cần route qua `publicProductEntityPath()`/
      `publicProductCategoryPath()` thay vì string rời (xem A3-bis). **Chưa làm** — refactor 8+
      điểm gọi, rủi ro cao hơn lợi ích trước mắt, để dành lượt sau.
- [x] Thay relative href tĩnh (`san-pham/`, `danh-muc-san-pham/`, `tin-tuc/`, `giai-phap/`,
      `du-an-da-thuc-hien/`) bằng absolute path trong 13 file template (`_product_card.php`,
      `menu.php`, `mmenu.php`, `footer.php`, `header.php`, `product_list_tpl.php`, các template
      order/news liệt kê ở A2) — đã sửa, lint sạch, test browser local xác nhận không lỗi.
      **Không đụng** `publicProductCategoryPath()`/`publicProductEntityPath()` (dùng chung với
      logic build canonical qua `$configBase`, thêm `/` vào đó sẽ tạo double-slash) — các href do 2
      hàm này sinh ra (menu dropdown nhiều cấp, `$solutionMenuHref`...) vẫn còn tương đối, vẫn chạy
      đúng nhờ `<base>`, để nguyên.
- [x] Sitemap: `giai-phap` không còn công bố URL pre-redirect (đã sửa `createSitemap()`, xem D7).
- [ ] Sitemap: `tru-den`/`bien-tan-bom` thiếu trên production — không tái hiện ở local (code đúng),
      cần đối chiếu trực tiếp trên production, không phải việc sửa code (xem D7).
- [ ] Bare-slug alias cho 205 sản phẩm (không chỉ 1 sản phẩm) — không phải risk thật (canonical
      luôn đúng), nhưng là route thừa cùng nhóm với mục absolute-href ở trên (xem D6).
- [ ] Bổ sung `Offer` (giá/trạng thái) và `AggregateRating`/`Review` vào Product schema đã có sẵn.
- [ ] Quyết định rõ: giữ Google Translate widget (miễn phí, chạy được nhưng không phải i18n SEO
      thật) hay bỏ, hay làm i18n thật (`/en/`, `hreflang`) — hiện đang lấp lửng.
- [ ] Crawl toàn site tìm 404 thật bằng công cụ (không thủ công) trước khi kết luận còn gì vỡ.

### P3 — Nội dung/biên tập, chủ project quyết định riêng, không phải lỗi kỹ thuật

- [ ] Category intro text đang generic (dùng chung 1 câu cho nhiều category).
- [ ] Form báo giá sản phẩm có thể rút gọn field bắt buộc.
- [ ] Trang Dự án nên có case study chi tiết hơn (thông số hệ thống, ảnh thực tế, sản lượng).
- [ ] Rà nội dung tin tức cũ: claim quá đà ("an toàn tuyệt đối 100%"), lỗi chính tả, đổi hướng
      sang bài dạng câu hỏi kỹ thuật thay vì "N lý do nên mua".
- [ ] Trang Đại lý: câu số liệu/chương trình đã lỗi thời (nhắc 2021-2025 trong khi đã 2026) cần
      review lại có còn đúng không.
- [ ] Title pattern chuẩn hoá theo loại trang (Home/Category/Product/Solution/Project/Article).

### Bỏ qua theo yêu cầu chủ project

- Không thêm khối "Thương hiệu & chứng nhận" hay trang mới vào trang chủ — giữ nguyên bố cục hiện
  tại.

---

## Phần D — Rà soát sâu lần 2 (đa góc độ, theo yêu cầu "kiểm tra kỹ, không lướt qua")

Lượt đầu verify 1 mẫu cho mỗi claim rồi kết luận. Lượt này chạy 3 nhánh song song, mỗi nhánh test
nhiều mẫu, nhiều loại trang, cả mobile viewport, cả local lẫn production, để không bỏ sót góc nào.

### D1. PHÁT HIỆN MỚI QUAN TRỌNG NHẤT PHIÊN NÀY: `<base href>` gây lỗi điều hướng thật — đã sửa

`<base href>` (thứ đã "cứu" link sản phẩm relative ở Phần A) lại gây ra tác dụng phụ thật: mọi
`href="#muc-luc"` thuần (không tiền tố path) trên trang KHÔNG PHẢI trang chủ bị trình duyệt resolve
về **trang chủ + fragment**, không phải cuộn trong trang hiện tại. Tái hiện bằng click thật
(Playwright) trên production: bấm "Về Phúc Thịnh Solar" ở `/gioi-thieu/` → điều hướng hẳn sang
`https://phucthinhsolar.com/#ve-phuc-thinh`, rời khỏi trang About.

**Phạm vi xác nhận:** `templates/about/about_tpl.php` (5 anchor), `templates/daily/daily_tpl.php`
(3 anchor), `templates/news/service_list_tpl.php` (1 anchor), `templates/news/service_detail_tpl.php`
(1 anchor) — tổng 10 vị trí ở 4 file. **Không bị ảnh hưởng:** mọi `href="#..."` có kèm
`data-toggle="modal"`/`data-toggle="tab"` (Bootstrap tự `preventDefault()` trước khi trình duyệt
kịp điều hướng — đã verify không cần sửa), `href="#menu"` trong header (cùng lý do), và anchor trên
chính trang chủ (base = root = trang chủ nên tự đúng).

**Phát hiện phụ thú vị:** `templates/product/product_detail_tpl.php` **đã có sẵn fix đúng cho
chính lỗi này** từ trước (biến `$pdpCurrentPath`, kèm comment giải thích rõ nguyên nhân) — nhưng
chưa được lan sang 4 template còn lại bị cùng lỗi.

**Đã sửa (local):** thêm hàm dùng chung `$func->getCurrentPagePath()` (trong
`libraries/class/class.Functions.php`, cùng logic PDP đã dùng) rồi áp vào đúng 10 vị trí. Không
đụng PDP (đã đúng sẵn, đã test kỹ ở phiên trước, giữ nguyên để giảm rủi ro). Verify: lint PHP 7.4
sạch, Playwright click thật trên cả 4 trang (`/gioi-thieu/`, `/dai-ly/`, `/giai-phap/`,
`/giai-phap/dien-mat-troi-ho-gia-dinh/`) — anchor giữ đúng trang hiện tại, 0 console error.

### D2. www/non-www + absolute-href fix — kiểm tra sâu hơn, đứng vững

- Test thêm: path con qua `www`+`http`, query string qua redirect (giữ nguyên), port tường minh
  `:443`, `http://` không `www` — toàn bộ 301/308 sạch, không có URL nào lọt lưới, không redirect
  loop, path luôn được giữ nguyên qua các hop.
- Absolute-href fix (13 file) kiểm tra thêm trên mobile viewport (390×844) qua `mmenu.php` thật —
  hamburger mở đúng, mọi link đích đúng. `git diff --check` + `php -l` lại toàn bộ: sạch.
- Form `action=""` không bị `<base>` kéo lệch (HTML spec có exception riêng cho action rỗng —
  resolve về trang hiện tại, không phải root) — đã verify, không phải bug.

### D3. Taxonomy — đính chính lại câu chữ (không đổi kết luận hành động)

Xác nhận: production **đã** ở đúng trạng thái "9 category, đã gộp" — có redirect 301
`danh-muc-san-pham/he-thong-khung-nhom/` → `danh-muc-san-pham/tam-pin-nang-luong-mat-troi/`, khớp
y hệt cặp `old_path`/đích mà migration 047 tạo ra khi `--apply`. `docs/00-project/status.md` và
`docs/13-handoffs/2026-09-22-product-category-normalization.md` ghi "production chưa deploy" —
dòng này **hiện không còn đúng với thực tế**, nhưng không tìm thấy commit/doc nào ghi lại việc
deploy migration 047 lên production giữa 22–25/9. Đây là **lỗ hổng tài liệu** (doc chưa cập nhật
theo kịp), không phải nghi vấn kỹ thuật cần điều tra thêm. **Kết luận hành động không đổi:** không
di chuyển sản phẩm khung/kẹp ra khỏi Tấm pin — vì production hiện tại (bằng chứng trực tiếp, không
qua suy luận từ doc) đã và đang chạy đúng cấu trúc gộp.

### D4. NAP — thu hẹp đúng phạm vi, thêm 1 lỗ hổng khác đã sửa

Rà theo từng loại trang: `TP.Pleiku` (JSON-LD Organization) xuất hiện ở mọi trang "chung" (trang
chủ, `/gioi-thieu/`, `/dai-ly/`, `/tin-tuc/`, `/gio-hang/`) nhưng **không xuất hiện ở PDP hay bài
viết chi tiết** — vì `templates/layout/strucdata.php` chỉ render `Organization` đầy đủ cho các loại
trang không phải `product/product_detail` hay `news/news_detail`/`static`. Gốc rễ vẫn là 1 field
`Setting.address` trên production (đã nêu ở mục A-NAP), không đổi.

**Phát hiện thêm, đã sửa (local):** Organization JSON-LD hoàn toàn thiếu `telephone` và `email` —
NAP (Name-Address-Phone) thiếu hẳn phần Phone trong structured data. Đã thêm 2 field này vào
`templates/layout/strucdata.php`, lấy từ `$optsetting['hotline']`/`$optsetting['email']` (field
đang dùng thật cho hotline công khai, không phải field `phone` đã xoá ở đợt dọn Admin trước). Verify
trên trang chủ + `/gioi-thieu/`: `"telephone":"0944 555 957","email":"phucthinhsolar@gmail.com"`
xuất hiện đúng trong JSON-LD, 0 console error.

### D5. Canonical bài viết/danh mục — test rộng hơn (5 bài + toàn bộ 9 category), kết luận cũ đứng vững

Không phát hiện lệch nào mới. Củng cố thêm bằng chứng, không đổi kết luận A3 (không phải bug).

### D6. PHÁT HIỆN MỚI: bare-slug alias cho sản phẩm là kiến trúc toàn site (205 sản phẩm), không phải cá biệt

Test 7 sản phẩm ngẫu nhiên (không chỉ 1 như lượt đầu): **tất cả** đều có URL "trần" (không tiền tố
`san-pham/`) trả 200, trùng 100% nội dung với bản `san-pham/slug`, và canonical của bản trần luôn
trỏ đúng về bản `san-pham/slug`. Đây là pattern áp dụng cho toàn bộ sitemap (205 sản phẩm), giống
hệt cách category hoạt động (Phần A3) — **không phải duplicate-content risk thật với Google** vì
canonical luôn nhất quán đúng, chỉ là bề mặt route thừa. Không cần hành động khẩn; nằm chung nhóm
"dọn route thừa" P2 đã ghi (mục A2).

### D7. Sitemap — 2 vấn đề thật, 1 đã sửa, 1 không tái hiện được ở local

- **Đã sửa (local):** `giai-phap` route 301-redirect bare slug → `giai-phap/<slug>/`, nhưng sitemap
  generator (`class.Functions.php::createSitemap()`) trước đó công bố bản trần (pre-redirect) thay
  vì URL đích — tốn crawl budget của Google qua 1 hop thừa. Sửa hẹp đúng phạm vi: chỉ khi
  `$com==='giai-phap' && $field==='id'`, dùng lại `publicCanonicalEntityPath()` (đã có sẵn, dùng
  chung với thẻ `<link rel="canonical">`) để lấy đúng URL đích. Không đổi hành vi của bất kỳ `$com`
  nào khác (tin-tuc, du-an, category, sản phẩm...) — giữ nguyên để tránh phá vỡ những gì đã đúng.
  Verify: sitemap local sau fix có `giai-phap/dien-mat-troi-ho-gia-dinh/` (đúng đích) thay vì bản
  trần; test redirect + canonical lại vẫn khớp.
- **Không tái hiện ở local:** báo cáo production thiếu 2 category `tru-den`/`bien-tan-bom` trong
  sitemap — kiểm tra lại sitemap local thì **đã có đủ cả 9 category** kể cả 2 cái này (code hoạt
  động đúng). Vậy đây nhiều khả năng là dữ liệu/trạng thái riêng của production (hoặc cache tầng
  ngoài code), không phải bug trong code. Không có gì để sửa ở local cho mục này — cần đối chiếu
  trực tiếp trên production khi có dịp, không tự suy đoán thêm.

### D8. robots.txt — sạch, không có gì cần sửa

Chỉ chặn `/cgi-bin/`, `/administrator/`; khai báo đúng sitemap. Không phát hiện vấn đề.

---

## Ghi chú cho lượt làm tiếp theo

- Trước khi sửa bất kỳ mục P1/P2 nào liên quan URL/canonical, cần tạo nhánh backup riêng (theo
  quy trình đã dùng ở đợt dọn Admin `feature/kitawa-inspired-header` — xem
  `docs/13-handoffs/2026-09-25-admin-redundancy-deep-audit.md` để tham khảo cách làm).
- Việc gán lại category cho sản phẩm khung/kẹp là thay đổi DB, không phải code — cần audit riêng
  xem có bao nhiêu sản phẩm bị ảnh hưởng và tên category đích chính xác trước khi động vào, tương
  tự cách đã làm ở đợt "category tree restructure" trước (xem memory
  `project_category_tree_restructure_20260908`).
