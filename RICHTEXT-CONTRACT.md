# Shared CMS rich-text contract

## Phạm vi pilot

Contract này dành cho nội dung CKEditor đi từ PHP CMS sang Nuxt 4. Task pilot
chỉ thêm sanitizer, contract, component dùng chung và test; chưa sửa detail API,
template hiện tại, routing hay tích hợp hàng loạt.

Database không bị ghi hoặc thay đổi. PHP template legacy hiện vẫn có các nhánh
`htmlspecialchars_decode()` riêng; chúng chỉ được thay thế trong một task tích
hợp sau khi contract này đã được duyệt.

## Audit local CMS

Các nguồn đã đối chiếu:

- `sources/news.php` và detail templates `news_detail_tpl.php`,
  `project_detail_tpl.php`, `service_detail_tpl.php`.
- API detail `pts_v1_news_detail.php`, `pts_v1_project_detail.php`,
  `pts_v1_solution_detail.php` và các helper DTO tương ứng.
- Bảng CMS local `table_news`, hai trường `contentvi` và `content1vi`.

Nội dung local đang được lưu dưới một lớp HTML entity encoding. Markup thực tế
đã quan sát: `h2`, `h3`, `h4`, `p`, `strong`, `em`, `a`, `ul`, `ol`, `li`, `br`,
`blockquote`, `code`, `img`. Không thấy `table`, `figure`, `figcaption`,
`oembed`, `iframe` trong các bản ghi local đã audit; các cấu trúc này vẫn được
allowlist để giữ tương thích CKEditor hợp lệ trong tương lai.

Thuộc tính thực tế gồm `style`, `dir`, `id`, `href`, `target`, `rel`, thuộc tính
ảnh kích thước/tải và một số thuộc tính legacy/data. Sanitizer giữ các thuộc
tính cần cho semantics và media, đồng thời loại `style`, event handler và
`data-*`.

## PHP sanitizer

File: `domains/phucthinhsolar.com/public_html/libraries/pts_public_richtext.php`

Entry point:

```php
$html = ptsPublicRichTextSanitize($row['contentvi'] ?? '');
```

Các thẻ được giữ: `h2`–`h6`, `p`, `br`, `strong`, `b`, `em`, `i`, `u`, `s`,
`mark`, `sub`, `sup`, `a`, `ul`, `ol`, `li`, `blockquote`, `pre`, `code`,
`table`, `thead`, `tbody`, `tfoot`, `tr`, `th`, `td`, `figure`, `figcaption`,
`img`.

Các thẻ thực thi hoặc có rủi ro embedding bị loại cùng toàn bộ nội dung của
chúng: `script`, `style`, `iframe`, `object`, `embed`, `svg`, `math`, `form`,
`input`, `button`, `textarea`, `select`, `video`, `audio` và các thẻ liên quan.
Thẻ không nằm trong allowlist nhưng không thuộc nhóm blocked được unwrap để giữ
text/con của nó.

Thuộc tính an toàn:

- Heading/đoạn: `id` token an toàn; heading có `dir` với `ltr|rtl|auto`.
- Link: `href`, `title`, `target` (`_blank|_self`), `rel`; `_blank` tự thêm
  `noopener noreferrer`.
- Trích dẫn: `cite` được xử lý theo cùng policy URL an toàn như `href`.
- Ảnh: `src`, `alt`, `title`, `width`, `height`, `loading`, `decoding`,
  `aria-describedby`.
- Bảng/danh sách: chỉ giữ số giới hạn cho `colspan`, `rowspan`, `start`,
  `value`; cùng `scope`, `headers`, `type` với giá trị được kiểm tra.

`style`, `class`, event handler (`on*`), `srcset`, CSS custom property và
`data-*` không thuộc contract. SVG không được phép, kể cả inline SVG trong
ảnh/data URL.

Ảnh chỉ nhận file từ `/upload/...` với extension `jpg`, `jpeg`, `png`, `gif`,
`webp`, `avif`. URL absolute cùng host `phucthinhsolar.com` được đổi thành
public path `/upload/...`; host ngoài, protocol-relative URL, traversal,
`data:`, `javascript:`, `vbscript:` và extension SVG/PHP bị loại.

Link nội bộ cùng host được đổi về path public; path tương đối được thêm `/`.
Link ngoài chỉ nhận `http(s)`, còn `mailto:` và `tel:` được giữ. Các scheme
khác bị loại.

## Nuxt contract và component

Payload API tương lai phải đưa rich text qua envelope rõ ràng:

```ts
{
  format: 'sanitized-html',
  html: '<h2>...</h2><p>...</p>',
  sanitizedBy: 'pts_public_richtext',
  sanitizerVersion: 1,
}
```

`isPublicSanitizedRichText()` là hàng rào contract phía Nuxt: kiểm tra metadata,
độ dài và một số marker nguy hiểm. Nó không thay thế sanitizer PHP. Component
`EditorialRichText.vue` chỉ nhận envelope này và dùng `v-html` trên `html` đã qua
PHP sanitizer; raw field `contentvi`/`content1vi` không được truyền trực tiếp.

CKEditor media embed (`oembed`, iframe YouTube/Vimeo hoặc iframe tùy ý) chưa có
policy proxy/allowlist host trong pilot nên bị loại. Nếu cần hỗ trợ, phải thiết
kế DTO media riêng với host, URL và renderer đã kiểm soát; không mở rộng
allowlist iframe trong sanitizer này.

## Kiểm thử

PHP unit/contract test:

```bash
docker cp domains/phucthinhsolar.com/public_html/libraries/pts_public_richtext.php \
  pts-php81-local:/tmp/pts_public_richtext.php
docker cp scripts/test-pts-public-richtext.php pts-php81-local:/tmp/test-pts-public-richtext.php
docker exec -e PTS_RICHTEXT_HELPER=/tmp/pts_public_richtext.php \
  pts-php81-local php /tmp/test-pts-public-richtext.php
```

Thử với database local thật:

```bash
docker exec -e PTS_RICHTEXT_HELPER=/tmp/pts_public_richtext.php \
  -e PTS_RICHTEXT_LOCAL_DB=1 pts-php81-local \
  php /tmp/test-pts-public-richtext.php
```

Nuxt contract test chạy cùng test suite frontend:

```bash
cd frontend && npm test
```

## Giới hạn còn lại

- Chưa tích hợp vào ba detail API, nên API hiện tại vẫn trả plain-text/deferred
  hoặc chưa render rich text như contract mới.
- Chưa thay các template PHP đang decode và render HTML trực tiếp.
- Chưa có renderer cho embed, upload attachment/download, `srcset`, custom
  classes hoặc CSS alignment từ CKEditor.
- Component mới chưa được gắn vào News/Projects/Solutions; việc đó cần một task
  tích hợp riêng với contract DTO và parity test cho từng detail.
