# P0-A / P2-A route và SEO crawl gates

Checkpoint của lane: base `07a50ea`; preview implementation gần nhất `ae811bb`.
Đây là evidence tooling read-only, không phải route cutover. PHP vẫn là public
resolver/SEO/status owner; Nuxt chỉ được crawl ở preview local và phải giữ
`noindex`.

## Route inventory theo code

`libraries/router.php` và `public_route_resolver.php` là nguồn sự thật cho các
family sau:

- Home: `/`, `index.php`, cùng `sitemap.xml` route riêng.
- Catalog: `/san-pham/`; product detail `/san-pham/{slug}`; taxonomy
  `/danh-muc-san-pham/{list}[/{cat}[/{item}[/{sub}]]]` từ 1 đến 4 cấp.
  `product/` và `product/{slug}` là alias 301; category legacy được resolve
  hoặc chuyển về path phân cấp bằng `product_category_urls.php`.
- Search: `/tim-kiem/` với query `keyword`; PHP đánh `noindex,nofollow`.
- Brand: `/thuong-hieu/{slug}/` resolve qua `product_brand` và render catalog
  lọc `id_brand`; `/thuong-hieu/` không có nhánh `idb`, nên code rơi vào listing
  tất cả sản phẩm, không phải brand-list. Slug evidence trong manifest là
  `longi`, đã có contract/API test.
- Editorial: `/giai-phap/` và `/giai-phap/{slug}/`; `/du-an-da-thuc-hien/`
  và bare project slug; `/tin-tuc/` và bare news slug. Resolver giữ các alias
  prefix nhưng canonical do `publicCanonicalEntityPath()` quyết định.
- Corporate: `/gioi-thieu/`, alias `/ve-chung-toi/`; `/dai-ly/`, alias
  `/he-thong-dai-ly/` chuyển về `/lien-he/`; `/lien-he/`.
- Support/policy: record `news.type = chinh-sach` được public link tại
  `/ho-tro/{slug}/`; fixture local đã xác minh từ footer DTO là
  `chinh-sach-bao-hanh-bao-tri-va-ho-tro-ky-thuat`. Không dùng slug rút gọn hoặc
  tự đoán.
- Gallery/video: listing thật là `/thu-vien-anh/` và `/video/`; gallery detail
  dùng bare published slug `/{slug}/` được PHP resolver nhận diện theo
  `type=thu-vien-anh`. Không dùng `/album/*` làm tên route PHP. Đã có Nuxt
  internal preview tại
  `migration-gallery-preview`, `migration-gallery-detail-preview` và
  `migration-video-preview`; các route đều noindex, PHP vẫn là public owner.
  Full aspect-ratio/embed/privacy/copyright parity và canonical/cutover vẫn
  phải kiểm chứng trước khi promote.
- Commerce: `/gio-hang/` là GET page và POST checkout mutation; checkout không
  có public `/dat-hang/` riêng trong router. Thành công redirect về
  `/cam-on/?token=...`; crawler chỉ GET, không tạo order.
- Protected/technical: `/account/*`, `/administrator/*`, `/api/*`,
  `/upload/*`, `/thumbs/*`, `/watermark/*` giữ PHP owner/excluded. Không login,
  gửi form, forward cookie hoặc crawl mutation trong lane này.

## Exact local crawl command

Khi hai local origin đã sẵn sàng, chạy đúng lệnh sau từ repository root:

```bash
node tools/visual-tests/scripts/migration-seo-audit.cjs \
  http://127.0.0.1:8080 http://127.0.0.1:3100 \
  --manifest tools/visual-tests/fixtures/migration-routes.json \
  --output frontend/.migration-artifacts/seo-audit
```

Tool chỉ dùng GET, không cookie/credential, không mutation, mặc định chặn mọi
origin và redirect ngoài loopback. Tool không chụp screenshot và không gọi URL
remote. Output:

- `report.json`: từng route có status, final URL, redirect chain, title, H1,
  canonical, robots, OG basic, JSON-LD types, href mapping, ownership và checks;
- `summary.md`: bảng review nhanh cùng các gate chưa đạt;
- `resources.php/nuxt`: visibility/status của `/robots.txt` và `/sitemap.xml`,
  gồm `Disallow`, `Allow`, `Sitemap` và danh sách `<loc>`.

Nếu fixture policy thay đổi, giữ kết quả 404/needs-verification và lấy lại slug
từ `site.footer.policies` hoặc một record `chinh-sach` đang `hienthi` trước lần
crawl tích hợp. Không đổi manifest để che 404; phải ghi evidence CMS và không
dùng HTTP 200 của preview để tuyên bố parity.

## Gate decision

Chỉ ký pre-cutover khi tất cả route/alias/negative fixture đã có evidence tương
ứng trong `report.json`:

1. Canonical public route về đúng path/entity; alias đúng 301/302/307/308 và không
   loop. Missing/hidden/retired route đúng 404 hoặc 410, không soft-200.
2. PHP có title/H1/canonical/robots/OG theo route; preview có `noindex` rõ ràng.
   `ownership.preview.status = preview-noindex` không đồng nghĩa đã được public.
3. `robots.txt` không chứa staging leakage và trỏ sitemap đúng; sitemap chỉ công
   bố URL canonical, không công bố URL pre-redirect. Lấy mẫu `<loc>` và crawl GET
   để xác nhận status/final URL.
4. JSON-LD có type/entity URL hợp với canonical và dữ liệu thật; không pass vì
   route trả 200. Kiểm tra đặc biệt Product/Breadcrumb/FAQ/Article/Organization.
5. Href mapping không có internal path 404; chênh path giữa PHP và Nuxt phải được
   phân loại là public path versus preview path, không coi khác chuỗi là tự động
   fail hay tự động pass.

Account/Admin/media/API và mọi POST checkout vẫn là gate PHP riêng, không được
đánh dấu đã migrate từ kết quả crawler này.
