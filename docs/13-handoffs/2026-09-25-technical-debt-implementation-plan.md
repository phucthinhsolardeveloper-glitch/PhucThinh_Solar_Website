> **Cập nhật 2026-09-25 (cùng ngày, sau khi lập kế hoạch):** Mục 2 (breadcrumb sản phẩm) và mục 3
> (logic hoàn thiện sản phẩm) đã triển khai xong ở local, xem "Kết quả triển khai" ở cuối file.

# Kế hoạch triển khai nợ kỹ thuật còn tồn đọng (sau audit Admin + SEO)

Ngày: 2026-09-25
Nguồn: tổng hợp từ `docs/13-handoffs/2026-09-25-admin-redundancy-deep-audit.md` (Phase B/C) và
`docs/10-audits/2026-09-25-seo-technical-audit-verification.md` (P2 còn lại). Đã audit sâu (đọc
code, đo DB local) từng việc trước khi lập kế hoạch, không suy đoán phạm vi.

---

## 1. `adv` → `about-team`: KHÔNG PHẢI việc code — chỉ cần bạn upload 1 ảnh

Audit ra kết quả bất ngờ: type `about-team` **đã tồn tại đầy đủ và đã lên Admin từ trước** (Admin →
Trang Giới thiệu → "About - Con người"), chỉ là **chưa từng có ai tạo record nào** (0 record).

`sources/about.php` đã tự có logic: nếu `about-team` có record với ảnh hợp lệ, dùng ngay; chỉ khi
rỗng mới fallback qua mượn ảnh từ `adv` (record "Đăng ký nhận tư vấn", id=103, ảnh `4-8075.png`).
Nghĩa là **không cần sửa dòng code nào** — chỉ cần bạn vào Admin, tạo 1 record `about-team` với ảnh
nhân sự thật, fallback sẽ tự động ngừng được dùng (short-circuit có sẵn).

**Phát hiện thêm:** `adv` type thực ra **không có tác dụng CTA banner nào khác trên frontend**
như ghi chú admin của nó tuyên bố — grep toàn bộ `sources/`+`templates/` chỉ thấy đúng 1 nơi dùng
(`about.php` fallback này). Sau khi có `about-team` thật, `adv` type trở thành mồ côi hoàn toàn,
có thể xếp vào nhóm "ẩn khỏi Admin" giống 7 type đã dọn ở đợt trước.

**Việc cần làm:**
- [ ] **Bạn**: Admin → Trang Giới thiệu → "About - Con người" → tạo record (tên, ảnh 1600×1120,
      mô tả ngắn). Ảnh cần thật, xác minh được (nhân sự thật, không dùng người giả/stock).
- [ ] **Tôi** (sau khi có record thật, xác nhận `/gioi-thieu/` render đúng): xóa đoạn fallback mượn
      `adv` trong `sources/about.php:171-182`, ẩn type `adv` khỏi Admin theo đúng cách đã làm ở đợt
      dọn trước.

Không có gì để "phân tích sâu" thêm ở mục này — đã rõ ràng, chỉ đợi nội dung thật.

---

## 2. BreadcrumbList JSON-LD + breadcrumb hiển thị dùng URL bare-slug

**Xác nhận:** đây không chỉ là vấn đề JSON-LD (P2 "cosmetic") — breadcrumb **hiển thị trên UI**
cũng dùng chung giá trị bare-slug này (`class.BreadCrumbs.php` build cả `href` lẫn JSON-LD `item`
từ cùng 1 slug truyền vào). Cũng xác nhận **không chỉ ở PDP** — `sources/news.php` có cùng lỗi hệt
cho `tin-tuc`/`du-an` (6 nhóm gọi), trong khi nhánh `giai-phap` đã tự sửa đúng từ trước (dùng làm
mẫu tham khảo).

**Độ phức tạp:** class `BreadCrumbs.php` xử lý full-path sạch, không double-slash — an toàn để
truyền full path vào. Nhưng hầu hết các query hiện tại **không** fetch sẵn slug của các cấp cha, nên
mỗi điểm gọi cần đổi sang `publicProductEntityPath($d, '<table>', $id, $lang)` (tự query riêng, +1
query nhỏ mỗi cấp breadcrumb — chấp nhận được, PDP tối đa 4-5 cấp).

**Phạm vi chính xác — 11 nhóm gọi, 2 file:**

| File | Nhóm (dòng) | Cấp | Cách sửa |
|---|---|---|---|
| `sources/product.php` | 290-295 | Product detail | `publicProductEntityPath()` cho list/cat/item/sub/product |
| `sources/product.php` | 368-369 | List | Dùng thẳng `publicProductCategoryPath()` (row đã đủ field) |
| `sources/product.php` | 441-443 | Cat | `publicProductEntityPath()` |
| `sources/product.php` | 499-502 | Item | `publicProductEntityPath()` |
| `sources/product.php` | 561-565 | Sub | `publicProductEntityPath()` |
| `sources/product.php` | 614 | Brand | **Chưa có helper** — brand không nằm trong `publicProductCategoryPath()`/`publicProductEntityPath()`, cần viết thêm case mới |
| `sources/product.php` | 677 | Fallback | `$com` thô — kiểm tra riêng, khả năng không cần sửa |
| `sources/news.php` | 104-109 | Detail (tin-tuc/du-an) | `publicProductEntityPath()`-tương-đương cho news, cần tạo case `news_list/news_cat/news_item/news_sub` (hiện chưa có trong `publicProductEntityPath()`, chỉ có `news_chinh_sach`/`news_giai_phap`) |
| `sources/news.php` | 161-163 | List | Tương tự |
| `sources/news.php` | 214-217 | Cat | Tương tự |
| `sources/news.php` | 271-275 | Item | Tương tự |
| `sources/news.php` | 332-337 | Sub | Tương tự |
| `sources/news.php` | 386-387 | Fallback | Kiểm tra riêng |

**Việc cần làm thêm trước khi sửa breadcrumb:** `publicProductEntityPath()` trong
`libraries/product_category_urls.php` hiện chỉ có case cho `product*`, `news_chinh_sach`,
`news_giai_phap` — cần bổ sung case `news_list`/`news_cat`/`news_item`/`news_sub`/`news` (tương tự
cấu trúc `publicProductCategoryPath()` nhưng cho `tin-tuc`/`du-an`, có prefix khác nhau theo
`type`) và case `product_brand` (không có tiền tố phân cấp, chỉ `san-pham/thuong-hieu/<slug>/` hoặc
tương tự — cần xác nhận URL thật của trang brand trước khi viết).

**Đánh giá rủi ro:** trung bình — máy móc, lặp lại đúng 1 pattern qua 11 điểm, nhưng cần thêm code
mới (không chỉ gọi hàm có sẵn) cho case brand và case news hierarchy. Nên làm sau khi xác nhận URL
brand/news hierarchy thật trên site (tôi sẽ verify bằng browser trước khi sửa, không đoán).

---

## 3. Logic "độ hoàn thiện sản phẩm" sai nguồn — phạm vi RỘNG hơn ước tính ban đầu

Đây là việc nặng nhất, có **rủi ro chức năng thật** (không chỉ hiển thị sai).

### 3.1 Toàn bộ điểm cần sửa (10 điểm, 2 file định nghĩa lõi)

| # | Vị trí | Vai trò |
|---|---|---|
| 1 | `libraries/admin_product_completeness.php:20` | SQL `missing_content` (chỉ check `contentvi`) |
| 2 | `libraries/admin_product_completeness.php:22` | SQL `missing_wattage` (chỉ check `cong_suat`, không theo profile) |
| 3 | `libraries/admin_product_completeness.php:6-21` | SQL `missing_specs` (danh sách cột phẳng, không theo profile) |
| 4 | `libraries/admin_product_completeness.php:40-54` | **Bản PHP thứ 2, độc lập** — check lại `contentvi`/specs riêng cho badge từng dòng, không dùng chung SQL ở trên |
| 5 | `administrator/sources/index.php:75` + `administrator/templates/index_tpl.php:77,79` | Dashboard card "Thiếu công suất" — **SQL viết tay riêng, không qua `adminProductDataStatus()`** |
| 6 | `administrator/templates/product/man/mans_tpl.php:56-57,86` | Dropdown filter — tự sửa theo #1-3 |
| 7 | `administrator/templates/product/man/bulk_tpl.php:4-6,59` | Dropdown export — tự sửa theo #1-3 |
| 8 | `administrator/sources/product.php:587-590,604,606` | Query danh sách dùng `data_status` |
| 9 | `administrator/sources/product_bulk.php:1364-1371` | Query bulk export dùng `data_status` |
| 10 | `administrator/api/status.php:34-35` | **Bulk toggle trạng thái theo filter** — nếu điều kiện sai, có thể bật/tắt nhầm sản phẩm hàng loạt |

### 3.2 Rào cản kiến trúc thật sự — không đơn giản là sửa SQL

`productResolveSections()` (`libraries/product_content_blocks.php:118`) — hàm PDP dùng để tính
"nội dung hiệu lực" thật (tôn trọng kế thừa product→brand→category→list + mode
own/inherit/both/hide) — là hàm **PHP chạy 1 query riêng cho từng sản phẩm**, không thể nhét
thẳng vào 1 điều kiện `WHERE` SQL như cách làm hiện tại.

→ Có 2 hướng, cần bạn chọn trước khi tôi code:

**Hướng A — Lọc phía PHP (khuyến nghị):** Query lấy danh sách như cũ (không lọc `missing_content`
trong SQL), sau đó lọc lại bằng PHP gọi `productResolveSections()` cho từng dòng. Đúng 100% với
PDP thật (dùng chung 1 nguồn logic), nhưng **phá vỡ phân trang hiện tại** — không thể
`count(*)`/`LIMIT` trực tiếp trong SQL khi filter là `missing_content` nữa, phải đếm sau khi lọc
PHP (chấp nhận được với quy mô ~417 sản phẩm, nhưng cần viết lại cách phân trang cho riêng
trường hợp `data_status=missing_content`).

**Hướng B — Viết lại thành SQL join phức tạp:** Mô phỏng lại đúng logic kế thừa bằng SQL (JOIN tới
`content_section` theo 4 cấp, `CASE` theo `mode`). Giữ được phân trang SQL thuần, nhưng **trùng lặp
logic** với `productResolveSections()` ở 2 nơi — rủi ro lệch dần theo thời gian nếu sau này sửa 1
bên quên sửa bên kia.

**`missing_wattage`/`missing_specs` theo profile** cũng cần chọn tương tự: `CASE` SQL theo
`productPdpProfiles()` (giữ phân trang, nhưng phải đồng bộ tay khi thêm profile mới) hay lọc PHP
qua `productPdpResolveProfile()` + `productPdpSpecValue()` (đúng logic PDP thật, cùng vấn đề phân
trang).

### 3.3 Việc cần làm ngay, không cần chờ quyết định kiến trúc

- [ ] Gộp `adminProductMissingLabels()` (#4) về dùng chung định nghĩa với `adminProductDataStatus()`
      (#1-3) — xóa bản PHP trùng lặp, giảm 1 nguồn sai còn 1 nguồn, độc lập với việc chọn Hướng A/B.
- [ ] Sửa `administrator/sources/index.php:75` (#5) dùng lại `adminProductDataStatus('missing_wattage')`
      thay vì SQL viết tay riêng — dashboard card và filter list sẽ luôn khớp nhau, độc lập với A/B.
- [ ] **Ưu tiên cao nhất về rủi ro:** rà lại `administrator/api/status.php` (#10) — xác nhận có ai
      từng dùng "bật/tắt hàng loạt theo filter thiếu nội dung/công suất" chưa (log/audit trail nếu
      có), để biết mức độ đã bị ảnh hưởng bởi bug này trong quá khứ trước khi sửa.

---

## 2 & 3 — Thứ tự đề xuất triển khai

1. **`adv`→`about-team`**: chờ bạn cung cấp ảnh thật (không phải việc code).
2. **Breadcrumb**: làm trước vì rủi ro thấp, phạm vi rõ, không có quyết định kiến trúc nào cần
   chờ — chỉ cần verify URL brand/news hierarchy thật trước khi viết case mới.
3. **Completeness logic**: cần bạn chọn Hướng A hay B (mục 3.2) trước khi tôi code phần lọc chính;
   3 việc nhỏ ở mục 3.3 có thể làm ngay không cần chờ.

---

## Nhóm nội dung/biên tập (P3) — đã triển khai phần an toàn, để lại phần cần số liệu thật

P3 chia làm 2 loại rõ rệt: việc có thể làm ngay bằng dữ liệu đã có/code thuần, và việc cần số liệu
kinh doanh thật từ bạn (không tự bịa). Đã làm xong loại đầu:

- [x] **Category intro text** — viết mô tả riêng cho 9 danh mục cấp 1 (trước đó tất cả rỗng, tự
      rơi về 1 câu fallback chung). Nội dung dựa trên thương hiệu thật đang bán trong từng danh
      mục (query trực tiếp DB), không thêm số liệu. Áp qua `migrations/052_add_product_category_intro_text.php`
      (đã chạy `--apply` ở local).
- [x] **Rút gọn form báo giá sản phẩm** — bỏ `required` ở Tỉnh/Thành phố và Phường/Xã sau khi xác
      nhận server-side (`sources/allpage.php:108-114`) **chưa từng validate 2 field này** — client
      yêu cầu nhưng backend không cần, thuần túy ma sát thừa. Không đụng đến
      fullname/phone/email/loaihinh/need_code (vẫn bắt buộc, khớp đúng validate server) vì rút gọn
      tiếp là quyết định kinh doanh (ảnh hưởng chất lượng lead cho CRM), không tự quyết thay bạn.
- [x] **Rà content tin tức cũ** — sửa bài "8 lý do..." qua `migrations/053_...php`: bỏ "An toàn
      tuyệt đối 100%" (claim marketing không bằng chứng), sửa 4 lỗi chính tả ("Tiết kiện", "cho
      phí", "có để đạt", "tốt đa"). Chỉ sửa đúng 1 bài này (mẫu điển hình audit ChatGPT chỉ ra);
      rà toàn bộ tin tức cũ khác cần thêm thời gian, chưa làm.
- [x] **Trang Đại lý: bỏ số liệu lỗi thời/không kiểm chứng được** — qua
      `migrations/054_remove_dealer_page_stale_claims.php`: bỏ nhắc "Chương trình mục tiêu Quốc
      gia (2021-2025)" (đã hết giai đoạn), bỏ "doanh thu ngành đèn NLMT... hàng nghìn tỉ đồng/năm"
      (không có nguồn), bỏ "chưa nhiều Đại lý... tại Việt Nam" (claim thị trường không có căn cứ),
      thay ảnh check-mark nhúng từ `static.xx.fbcdn.net` (phụ thuộc domain Facebook ngoài, có thể
      hỏng bất cứ lúc nào) bằng ký tự ✔ thuần text. **Không đụng** khối `dai-ly-2` (thông số kỹ
      thuật Blue Carbon như "tuổi thọ >25 năm") vì đọc như spec nhà sản xuất thật, không phải
      marketing claim rõ ràng sai — không đủ căn cứ để tự sửa/bỏ.
- [x] **Chuẩn hoá title pattern** — sửa 1 điểm trung tâm (`class.Seo.php::set()`) tự thêm hậu tố
      "| Phúc Thịnh Solar" cho MỌI trang đang thiếu (trước đó chỉ trang có SEO title tự nhập tay
      trong Admin mới có hậu tố, 21 điểm fallback rải rác trong `sources/*.php` đều thiếu). Có
      check tránh double-suffix, kể cả cho các SEO title cũ bị cắt ngắn giữa chừng trong DB (phát
      hiện phụ: một số title admin nhập bị cắt còn "...Phúc Thịnh So" thiếu "lar" — dữ liệu cũ, để
      nguyên không sửa vì ngoài phạm vi task này).
  - **Phát hiện phụ đã sửa luôn (không phải P3 nhưng tiện phát hiện khi test):** title trang chủ
    local đang hoàn toàn rỗng (`<title></title>`) — 1 hàng `#_seo` bị rỗng ở local, không phải do
    thay đổi trong phiên này. Cập nhật lại giá trị đúng (khớp production đã audit trước đó) trực
    tiếp qua SQL, không cần migration riêng vì chỉ là đồng bộ local theo giá trị đã biết đúng.

**Chưa làm, cần bạn cho số liệu/quyết định thật trước:**
- [ ] Case study dự án chi tiết hơn (thông số hệ thống, ảnh thực tế, sản lượng) — cần dữ liệu dự án
      thật, không thể bịa số.
- [ ] Rà toàn bộ content tin tức cũ khác (mới sửa 1 bài mẫu) — cần thời gian đọc hết, hoặc bạn chỉ
      định bài ưu tiên.
- [ ] Rút gọn tiếp form (bỏ email/loaihinh/need_code) nếu bạn chấp nhận đánh đổi chất lượng lead.

**Verify:** lint PHP 7.4 toàn bộ file sửa + 3 migration, `git diff --check` pass, xóa cache local
(`caches/` là Docker volume, xóa qua `docker exec`, không xóa được từ host) sau mỗi lần cập nhật
nội dung DB để tránh đọc nhầm bản cache 7200s cũ, browser test qua nhiều loại trang (trang chủ,
category, PDP có/không SEO title tự nhập, bài viết, Đại lý, giỏ hàng) - 0 console error, không còn
double-suffix title.

---

## Kết quả triển khai (2026-09-25, cùng ngày)

### Mục 2 — Breadcrumb: phạm vi thu hẹp sau khi verify trực tiếp, không như audit ban đầu ước tính

Trước khi sửa, verify lại bằng browser thật phát hiện: **`sources/news.php` KHÔNG hề có bug** —
canonical của toàn bộ hierarchy tin-tuc/du-an (list/cat/item/sub) là **bare slug** (không phải dạng
phân cấp như sản phẩm), và breadcrumb hiện tại của news.php vốn đã dùng đúng bare slug khớp
canonical 100%. Audit trước suy diễn nhầm từ pattern của `product.php` sang `news.php` mà chưa tự
kiểm chứng convention canonical khác nhau giữa 2 loại nội dung. Đã sửa lại kết luận, loại
`sources/news.php` khỏi phạm vi.

Case brand (`sources/product.php:614-615`) cũng loại khỏi phạm vi: grep toàn site xác nhận không
có link nào dẫn tới trang này (route chỉ vào được bằng gõ tay `?idb=X` hoặc slug brand trần) — sửa
breadcrumb cho 1 trang không ai truy cập không đáng công.

**Đã sửa:** 5 nhóm gọi breadcrumb trong `sources/product.php` (detail/list/cat/item/sub), dùng
`publicProductEntityPath()`/`publicProductCategoryPath()` đã có sẵn thay vì slug rời.

**Verify:** lint sạch, Playwright test cả breadcrumb hiển thị lẫn JSON-LD trên 3 cấp (detail/
category/item) — khớp 100% với canonical tag, click thật điều hướng đúng, 0 console error. Cấp Sub
không test được trực tiếp (local DB không có dữ liệu cấp này) nhưng dùng chung pattern đã verify ở
cấp Item.

### Mục 3 — Logic hoàn thiện sản phẩm: đi theo Hướng A (lọc PHP), đã triển khai đủ 10 điểm

Thêm vào `libraries/admin_product_completeness.php`:
- `adminProductIsMissingContent($d, $product)` — dùng `productResolveSections()` thật (kế thừa
  product→brand→cat→list, tôn trọng mode `hide`), thay `coalesce(trim(contentvi),'')=''`.
- `adminProductIsMissingWattage($product, $list)` / `adminProductIsMissingSpecs($product, $list)` —
  theo đúng profile PDP (`productPdpResolveProfile()` + `productPdpProfiles()['metrics']`), thay vì
  luôn luôn `cong_suat`.
- `adminProductFilterIdsByStatus($d, $status, $whereSql, $params)` — helper dùng chung: chạy 1 query
  lấy candidate rows theo `$whereSql` (đã có sẵn mọi filter khác), lọc bằng PHP, trả về mảng id. Nơi
  gọi tự thu hẹp `$where` thành `and p.id in (...)` rồi **tái dùng nguyên** cách phân trang/đếm/SQL
  hiện có — không viết lại pagination ở bất kỳ đâu.

**Đã cập nhật 10 điểm tiêu thụ:** SQL definitions bỏ key `sql` cho 3 status không biểu diễn được
bằng SQL tĩnh (`administrator/sources/product.php` viewMans, `product_bulk.php` export,
`administrator/api/status.php` bulk-toggle theo filter, `administrator/sources/index.php` dashboard
card, `admin_product_completeness.php::adminProductMissingLabels()` cho badge từng dòng — gộp về
dùng chung 1 nguồn, xóa bản PHP trùng lặp cũ).

**Đổi nhãn:** "Thiếu công suất" → "**Thiếu thông số quyết định**" (khớp đề xuất audit gốc, vì field
quyết định khác nhau theo profile: biến tần dùng `ac_power`, pin lưu trữ dùng `usable_capacity`...).
Giữ nguyên key `missing_wattage` trong code/URL để không phá link cũ.

**Verify bằng số liệu thật, không chỉ chạy thử không lỗi:**
- `missing_content`: quét toàn bộ 417 sản phẩm, tổng theo nguồn nội dung
  `product=247 + list=2 + brand=146 + cat=22 = 417` — khớp CHÍNH XÁC tổng số sản phẩm. Kết luận:
  **hiện tại 0 sản phẩm thực sự thiếu nội dung** (364 số cũ hoàn toàn là báo giả, chỉ nhìn
  `contentvi`, bỏ qua kế thừa).
- `missing_wattage`: 197 (cũ) → 251 (mới). Số TĂNG vì logic mới nghiêm hơn - verify bằng
  cách đối chiếu 1 sản phẩm inverter thật (SolaX X1-HYB-5.0-LV) với chính code PDP thật
  (`templates/product/product_detail_tpl.php:99-117`, khối "Điểm nổi bật"): sản phẩm này có
  `cong_suat=NULL` và thông số công suất chỉ nằm ở dạng text tự do trong `additional_specs`, không
  gắn `key` cấu trúc - nên khối "Điểm nổi bật" trên PDP thật của chính sản phẩm này CŨNG không hiện
  thông số công suất. Badge Admin giờ khớp chính xác với trải nghiệm khách hàng thấy trên site
  thật, không phải false positive.
- Lint PHP 7.4 toàn bộ 8 file sửa, `git diff --check` pass, browser test dashboard/filter/export
  qua Admin thật (đăng nhập, click, không chỉ đọc code) - 0 console error liên quan.

**Chưa test trực tiếp:** hành vi ghi (UPDATE) thật của `api/status.php` scope=filtered - vì đây là
thao tác ghi dữ liệu hàng loạt, không nên tự ý trigger trên local để "test cho biết". Đã xác nhận
đúng qua: cùng pattern/helper y hệt 2 nơi đã test trực tiếp (list filter, export), lint sạch, đọc
lại code cẩn thận từng dòng.
