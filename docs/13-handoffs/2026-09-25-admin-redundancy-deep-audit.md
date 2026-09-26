# Handoff: rà soát sâu Admin dư thừa và độ lệch Admin ↔ public

Ngày rà soát: 2026-09-25
Phạm vi: code và DB local của `phucthinhsolar.com`
Mục tiêu tài liệu: cung cấp đủ bằng chứng và checklist để lượt triển khai tiếp theo có thể thu gọn Admin mà không làm mất dữ liệu hoặc vô tình tắt consumer public/backend còn dùng.

## 1. Kết luận điều hành

Admin hiện có ba nhóm vấn đề độc lập:

1. Có **7 type cấu hình không còn consumer public hiện hành** nhưng vẫn xuất hiện để người vận hành nhập liệu.
2. Hai Hero legacy vừa được ẩn khỏi sidebar bằng điều kiện cứng, nhưng vẫn còn trong trang phân quyền, direct route và query runtime.
3. Logic “độ hoàn thiện sản phẩm” đang dựa vào `product.contentvi` cũ thay vì `content_section`, tạo số lượng false positive lớn.

Không được xóa record/upload ngay. Thứ tự an toàn là: ẩn khỏi luồng thường dùng → bỏ query dư → sửa logic Admin → migration dữ liệu phụ thuộc → theo dõi → mới cân nhắc archive/xóa.

## 2. Trạng thái worktree cần bảo toàn

Tại thời điểm audit, worktree đang có nhiều thay đổi chưa commit từ các lượt trước. Audit này không thay đổi code hoặc DB. Khi triển khai:

- Không reset, checkout hoặc ghi đè thay đổi hiện hữu.
- Đọc `git diff` của file trước khi sửa.
- Tách commit cleanup Admin khỏi thay đổi PDP/CSS/ảnh.
- Không sửa production hoặc import DB nếu chưa được chủ project cho phép rõ ràng.

Các file đang có thay đổi liên quan trực tiếp tới phạm vi này gồm:

- `domains/phucthinhsolar.com/public_html/administrator/templates/layout/menu.php`
- `domains/phucthinhsolar.com/public_html/administrator/templates/setting/man/man_add_tpl.php`
- `domains/phucthinhsolar.com/public_html/libraries/config-type.php`
- `domains/phucthinhsolar.com/public_html/libraries/type/config-type-photo.php`

## 3. Những gì thay đổi Hero hiện tại đã làm đúng và chưa đủ

Thay đổi hiện có đã:

- Đưa `hero-carousel` vào nhóm Admin `Trang chủ`.
- Thay lựa chọn `home_hero_mode` trong form Setting bằng hướng dẫn Hero V4.
- Ẩn `home-hero-bg` và `slide` khỏi menu photo chung.

Điểm chưa đủ:

- Điều kiện ẩn đang hardcode trong `administrator/templates/layout/menu.php`.
- `administrator/templates/user/permission/permission_group_tpl.php` vẫn lặp toàn bộ config photo nên hai type legacy vẫn xuất hiện trong màn phân quyền.
- Direct route Admin của type legacy vẫn truy cập được.
- `sources/allpage.php` vẫn query `slide` trên mọi request.
- `sources/index.php` vẫn query `home-hero-bg` trên trang chủ.
- `administrator/sources/setting.php` vẫn còn nhánh lưu `home_hero_mode` dù form và frontend hiện hành không dùng.

Không nên tiếp tục thêm các mảng `in_array()` rời rạc. Cần một registry tập trung, ví dụ `admin_legacy_types`, dùng chung cho sidebar, permission UI và route policy.

## 4. Type Admin xác nhận không còn consumer public

Số liệu dưới đây là snapshot DB local, không đại diện production.

| Kind | Type | Dữ liệu local | Bằng chứng | Hành động đề xuất |
|---|---|---:|---|---|
| photo | `home-proof-background` | 0 | `sources/index.php` tạo `$homeProofBackground`; `templates/index/index_tpl.php` không đọc | Ẩn Admin, bỏ query và fallback dead |
| photo | `doitac` | 10 record, 10 bật | Chỉ query thành `$partner` ở homepage; template không đọc; query dealer đã comment | Ẩn Admin trước, giữ record/upload để xác minh production |
| news | `tieu-chi` | 5/5 bật | `sources/product.php` query `$tc`; không template product nào đọc `$tc` | Ẩn Admin, bỏ query PDP |
| news | `loi-ich` | 7/7 bật | Homepage query `$loiich`; template hiện tại không đọc | Ẩn Admin, bỏ query |
| static | `loi-ich` | 1/1 bật | Homepage query `$loiich_title`; template không đọc | Ẩn Admin, bỏ query |
| news | `so-lieu-thong-ke` | 4/4 bật | Homepage query `$solieu`; template không đọc; Admin note cũng nói sửa chưa lên GUI | Ẩn Admin, bỏ query |
| static | `su-menh` | 1/1 bật | About dùng news `su-menh`, source ghi rõ không đọc static cũ | Ẩn Admin; chỉ archive sau backup |

Các type legacy Hero cần giữ riêng để rollback nhưng không đưa vào luồng vận hành thường:

| Type | Dữ liệu local | Trạng thái |
|---|---:|---|
| `home-hero-bg` | 4 record, 3 bật | Template legacy không được include |
| `slide` | 4 record, 1 bật | Template legacy không được include; query vẫn chạy toàn site |

## 5. Type không được đánh dấu dư chỉ vì homepage không dùng

Khi dọn query homepage, không ẩn/xóa các type sau vì vẫn có consumer ở trang chuyên biệt:

- `tin-tuc`: hub Tin tức.
- `du-an`: hub và chi tiết Dự án.
- `chungnhan`: trang Giới thiệu.
- `phan-hoi-dai-ly`: trang Đại lý.
- `cam-ket`: trang chi tiết sản phẩm.
- `quy-trinh`: trang chủ hiện tại.
- `hero-carousel`, `home-campaign`: trang chủ hiện tại.
- `catalog-campaign`, `product-category-hero`: catalog.
- `contact-visual`, `dealer-hero`, `hinh-anh-dai-ly`, `about-cta`: các trang tương ứng.
- `social`: structured data và email; footer lại dùng setting riêng, đây là nguồn trùng cần chuẩn hóa sau chứ chưa phải dữ liệu mồ côi.
- `watermark`, `watermark-news`: vận hành xử lý ảnh, dù record/công tắc có thể đang tắt.

## 6. `adv` là dependency chuyển tiếp, chưa được xóa

Local có 2 record `adv` đang bật:

- Một record link `#ptsContactModal`.
- Một record link `#ptsDealerModal`.

Homepage query `$adv` nhưng không render. Trang Giới thiệu lại dùng record `#ptsContactModal` làm ảnh fallback cho phần nhân sự nếu `about-team` chưa có ảnh thật. Local hiện chưa có record `about-team`, vì vậy xóa `adv` ngay có thể làm mất ảnh phần nhân sự.

Thứ tự migration:

1. Tạo/nhập `about-team` bằng dữ liệu và ảnh thật.
2. Xác nhận `/gioi-thieu/` render đúng.
3. Bỏ fallback `adv` trong `sources/about.php`.
4. Chuyển `adv` sang legacy.
5. Record `#ptsDealerModal` hiện chưa tìm thấy consumer; vẫn giữ cho tới khi đối chiếu production.

## 7. Query frontend dư cần loại bỏ

### 7.1 Trang chủ

`sources/index.php` đang có 13 cache/DB read cố định không có output tương ứng, cộng tối đa 3 query nhóm sản phẩm:

- `$adv`
- `$homeProofBackgroundRows`
- `$homeProductGroups` và tối đa 3 query sản phẩm theo nhóm
- `$newsnb`
- `$solieu`
- `$loiich`
- `$loiich_title`
- `$testimonial`
- `$partner`
- `$chungnhan`
- `$heroBgList`
- `$tieuchi`
- `$solutionGalleryRows`
- `$featuredProjectRows`

`$solutionRows` vẫn được template dùng; chỉ `$solutionTabs` là derived structure dư. Không xóa query `giai-phap`.

### 7.2 Toàn website

`sources/allpage.php` query `$slider` trên mọi request, trong khi `templates/index.php` không include `templates/layout/slide.php`. Bỏ query này khỏi allpage; nếu cần rollback Hero legacy thì phục hồi đồng thời source + include bằng commit rõ ràng.

## 8. Lỗi độ hoàn thiện sản phẩm

### 8.1 “Thiếu nội dung chi tiết” đang sai nguồn

`libraries/admin_product_completeness.php` hiện dùng:

```php
coalesce(trim(p.contentvi), '') = ''
```

PDP hiện dùng `table_content_section`, có kế thừa theo product → brand/category/list. Snapshot local:

- Tổng sản phẩm `san-pham`: 417.
- `contentvi` không rỗng: 53.
- Bộ lọc cũ báo thiếu: 364.
- `content_section` scope product đang bật, có blocks: 247.
- Có 194 sản phẩm `contentvi` rỗng nhưng có content block riêng hợp lệ đang bật.

Như vậy có ít nhất 194 false positive chắc chắn; có thể còn thêm sản phẩm dùng nội dung kế thừa.

Cần sửa đồng bộ:

- `adminProductDataStatusDefinitions()['missing_content']`.
- `adminProductMissingLabels()`.
- Bộ lọc danh sách sản phẩm.
- Export/bulk export theo `data_status`.
- API status nếu dùng cùng definition.
- Mọi dashboard/count liên quan.

Tiêu chí đúng: sản phẩm chỉ “thiếu nội dung chi tiết” nếu không có nội dung hiệu lực sau khi áp dụng đúng mode `own|inherit|both|hide`, status và nguồn kế thừa. Mode `hide` là chủ động ẩn, không nên bị báo là lỗi thiếu nội dung.

### 8.2 “Thiếu công suất” không phù hợp mọi profile

Dashboard và filter hiện bắt mọi sản phẩm có `cong_suat > 0`. Local báo 197/417 thiếu. Nhưng profile PDP khác nhau dùng metric khác:

- inverter: `ac_power`
- storage: `usable_capacity`, `battery_voltage`
- pump: `motor_power`, `max_flow`, `max_head`
- accessory: `rated_voltage`, `rated_current`, `compatibility`

Snapshot theo nhóm cho thấy cả 25 sản phẩm `tru-den` bị tính thiếu công suất, dù đây không phải field bắt buộc hợp lý.

Đề xuất đổi nhãn và logic thành “Thiếu thông số quyết định”, dùng `productPdpProfiles()[profile]['metrics']`. Nếu chưa thể làm SQL động, tạm bỏ card dashboard “Thiếu công suất” thay vì hiển thị con số gây hiểu nhầm.

## 9. Công tắc nội dung PDP: hiện chỉ tắt một phần

Form sản phẩm có `section_mode`:

- `inherit`
- `own`
- `both`
- `hide`

Mode `hide` chỉ chặn `content_section` và đoạn mô tả tự sinh. Nó không phải master switch cho toàn bộ phần dưới PDP. Các phần sau vẫn có thể render:

- `$productSpecHighlights`
- Use-case card mặc định theo profile
- Buying guide sinh từ specs
- Bảng thông số
- Video/chứng nhận/chính sách/tài liệu
- FAQ tự sinh

Code còn có độ lệch: `$hasProductDescription` vẫn tính `$productSpecHighlights` kể cả source là `hide`, nên nhãn “Ẩn phần mô tả dài” chưa hoàn toàn khớp hành vi.

Yêu cầu triển khai tối thiểu:

1. Chốt tên control là “Ẩn nội dung biên tập dài” nếu chỉ muốn điều khiển content block; hoặc
2. Nếu chủ project muốn tắt toàn bộ section dưới PDP, bổ sung master visibility có fallback rõ ràng, không tận dụng sai `section_mode`.

Không nên thêm nhiều cột boolean rời rạc trước khi chốt nhu cầu. Có thể dùng một cấu hình PDP visibility có schema/version rõ ràng và kế thừa theo profile/danh mục.

## 10. Setting Admin có field không còn consumer

### 10.1 Field hiện trong form nhưng không được public/backend đọc

- `phone`: trùng `hotline`, không có `optsetting['phone']` consumer; hiện vẫn bị validation bắt buộc.
- `oaidzalo`: chỉ còn sample helper, không có luồng public hiện hành truyền giá trị.
- `coords`: không được template hiện hành dùng.
- `twitter`: không consumer.
- `linkedin`: không consumer.
- `header_promise`: form nói hiện ở top header nhưng header hiện hành không đọc.

Khi bỏ field `phone`, phải bỏ đồng thời validation bắt buộc trong `administrator/sources/setting.php`, nếu không form Setting sẽ không lưu được.

### 10.2 Legacy không còn field công khai

- `home_hero_mode`: form đã bỏ nhưng save handler và DB key còn.
- Các key SMTP legacy: form đã bỏ và save handler chủ động bỏ qua, runtime mail lấy cấu hình ngoài Git.

DB local vẫn chứa các key/giá trị SMTP legacy. Không ghi credential vào tài liệu hoặc log. Trước khi xóa key phải:

1. Xác nhận mail runtime dùng `libraries/mail_config.php`/external config.
2. Test email local/staging bằng cấu hình an toàn.
3. Rotate credential cũ nếu có khả năng từng được sử dụng.
4. Backup options rồi mới purge key legacy bằng migration được duyệt.

### 10.3 Field cần giữ

- `address`, `email`, `hotline`, `zalo`, `fanpage`
- `youtube`
- `coords_iframe`
- `thoigianlamviec`
- `website` vì email template dùng
- `public_product_prices`
- `review_require_order_code`
- `watermark_active`
- `namevi`, SEO setting
- `analytics`, `mastertool`, `headjs`, `bodyjs`

Các trường script là consumer thật, không dư, nhưng quyền sửa rất nhạy cảm. `setting_update` hiện có thể cho phép người dùng chèn script toàn public site. Nên giới hạn field script cho superadmin hoặc tách quyền “Thiết lập kỹ thuật”.

## 11. Hero V4 còn sai semantics bật/tắt

`sources/index.php` đang fallback bốn banner mặc định khi:

```php
if (!$homeHeroRows || !$homeHeroEnabledRows)
```

Hậu quả: Admin tắt toàn bộ record thì Hero mặc định tự xuất hiện lại. Hợp đồng nên là:

- Chưa từng có record: dùng fallback an toàn.
- Đã có record nhưng tắt hết: ẩn Hero.
- Có một phần record bật: chỉ render record bật.

Phải thêm test cho cả ba trạng thái; không chỉ test trạng thái có đủ bốn banner.

## 12. Mục Admin cần giữ dù nhìn có vẻ nhiều

- `content_section`: frontend PDP đang phụ thuộc mạnh; không xóa. Có thể chuyển menu vào nhóm Sản phẩm.
- Structured specs và `additional_specs`.
- `thongsokythuatvi`: fallback legacy còn được PDP đọc.
- Giá, giỏ hàng, đơn hàng: có toggle toàn site và có thể bật lại.
- `photo_feed`: Merchant Center export phụ thuộc.
- Product campaign và khung riêng.
- Product document, certificate, purchase policy, video.
- Mail template, liên hệ, yêu cầu tư vấn, đăng ký đại lý.
- City/Ward backend: menu places đang ẩn nhưng form public vẫn dùng dữ liệu hai cấp. Không thêm Quận/Huyện trở lại; đơn mới tiếp tục ghi `district = 0`.

## 13. Kế hoạch triển khai khuyến nghị

### Phase A — cleanup không đổi schema

- [ ] Tạo registry type `active|legacy|hidden` dùng chung.
- [ ] Áp registry vào sidebar.
- [ ] Áp registry vào màn phân quyền.
- [ ] Chặn hoặc chuyển direct route legacy sang read-only nếu cần.
- [ ] Ẩn 7 type mồ côi ở mục 4.
- [ ] Bỏ query homepage/allpage dư.
- [ ] Sửa admin note sai của `doitac`, `adv`, Hero legacy.
- [ ] Bỏ các field Setting không có consumer và validation liên quan.
- [ ] Xóa nhánh save `home_hero_mode` sau khi xác nhận không còn form/consumer.
- [ ] Sửa fallback Hero tôn trọng trạng thái tắt hết.

### Phase B — sửa dữ liệu hoàn thiện Admin

- [ ] Viết helper xác định effective content theo cùng hợp đồng với `productResolveSections()`.
- [ ] Sửa `missing_content` và badge danh sách.
- [ ] Test product có own/inherit/both/hide.
- [ ] Thay `missing_wattage` bằng profile-aware metric completeness.
- [ ] Sửa dashboard và bulk export.
- [ ] Thêm test ngăn regression 194 false positive kiểu hiện tại.

### Phase C — migration dependency

- [ ] Tạo nội dung `about-team` thật trước khi bỏ `adv` fallback.
- [ ] Chuẩn hóa nguồn social giữa setting và photo `social`.
- [ ] Xác minh/purge SMTP legacy theo quy trình backup + rotate.
- [ ] Chốt hợp đồng visibility cho các section tự sinh PDP.

### Phase D — archive sau thời gian theo dõi

- [ ] Export record legacy và manifest upload liên quan.
- [ ] Đối chiếu production trước khi xóa config/data.
- [ ] Giữ redirect/slug public; không xóa route SEO đang có consumer.
- [ ] Chỉ xóa upload sau khi quét reference DB/code và có backup phục hồi.

## 14. Test bắt buộc khi triển khai

### Static/runtime

- PHP 7.4 lint mọi file PHP thay đổi.
- `git diff --check`.
- Kiểm tra config load không Warning/Notice.
- Kiểm tra menu và permission page không còn type hidden.
- Truy cập direct route type legacy phải theo policy đã chọn.

### Admin

- Login bằng superadmin và role giới hạn.
- Save Setting sau khi bỏ `phone` và field legacy.
- Product filters: missing photo/desc/content/specs/profile metrics.
- Product content mode: own/inherit/both/hide.
- Bulk export giữ đúng tập record như danh sách.
- Hero: chưa cấu hình / bật một phần / tắt hết.

### Public

- Trang chủ, catalog, PDP, Giới thiệu, Giải pháp, Dự án, Tin tức, Đại lý, Liên hệ.
- Không Warning/Notice/Fatal và không query biến đã bỏ.
- PDP có/không content block, có kế thừa, mode hide.
- Commerce smoke nếu thay logic giá/menu: category → product → cart → checkout → confirmation.
- Form tư vấn xác thực đúng `Tỉnh/TP → Phường/Xã`; không đưa Quận/Huyện trở lại.

## 15. Kết quả audit read-only đã chạy

- Mapping config → menu → permission → source → template.
- SELECT tổng hợp DB local, không ghi dữ liệu.
- HTTP smoke local:
  - `/`: 200
  - một PDP thật: 200
  - `/gioi-thieu/`: 200
  - `/dai-ly/`: 200
- Không phát hiện chuỗi Warning/Notice/Fatal trong bốn response.
- `git diff --check`: PASS.

## 16. Deploy và rollback

Chưa deploy, chưa sửa DB và chưa thay code trong lượt audit này.

Khi triển khai:

- Deploy Phase A/B bằng commit riêng.
- Không gộp migration/purge DB vào commit giao diện Admin.
- Rollback Phase A/B bằng revert commit; record/upload vẫn còn nguyên.
- Migration DB phải có file backup options/type data và câu lệnh phục hồi tương ứng.
- Sau deploy cần smoke Admin + public bằng đúng PHP production và xóa cache theo quy trình dự án.
