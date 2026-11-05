# Nhật ký triển khai production

Mỗi lần thay đổi production phải thêm một mục mới, không ghi credential, token,
PII hoặc nội dung bí mật. Nếu deploy chỉ thay docs không rebuild image thì ghi rõ
`docs-only` và lý do không cần runtime deploy.

## 2026-11-05 — cân lại giỏ hàng, checkout và tải ảnh PDP

- Release production: PHP/Nuxt `263faa84286e`. Cột tóm tắt commerce rộng
  480px ở desktop; card dùng radius 10px; checkout ba cột trường thông tin ở
  viewport rất rộng; giá sản phẩm nằm dưới tên để dễ đọc. Ảnh chính PDP dùng
  rendition 960px, preload sớm và xử lý lỗi theo URL thumbnail thực tế.
- Trước deploy: 509/509 test, typecheck, build và smoke PASS. Browser local
  với sản phẩm CMS thật tại 390/1440/2048 không tràn ngang hay page error;
  ảnh PDP mẫu 960px là 29 KB so với bản 1600px 63 KB.
- Backup DB `20261105T135137Z-db`; xóa 115 page cache và 2 asset cache tái
  sinh, không migration, không chạm upload/DB volume. Rollback image PHP/Nuxt
  `b8c86f162ed4` qua `/srv/phucthinhsolar/scripts/rollback-release.sh`.
- Deploy script dừng ở verify đầu do Caddy tạm báo 503 khi upstream Nuxt đổi
  IP. Sau khi upstream phục hồi, verify-stack và schema preflight READY; browser
  production tại 390/2048 xác nhận PDP, cart, checkout không lỗi JavaScript,
  không tràn ngang. Log 1 phút sau ổn định không có lỗi mới. Cần xử lý khoảng
  503 ngắn trong quy trình đổi image ở đợt hạ tầng riêng.

## 2026-11-05 — video CTA Giải pháp và hợp nhất release

- Release production: PHP/Nuxt `b8c86f162ed4`. Gộp commit video CTA của nhánh
  Claude vào nhánh hotfix đã chạy production; các nhánh audit và visual tương
  đương đã được dọn sau khi kiểm tra quan hệ commit/patch. Giữ nhánh film pilot
  riêng vì đó là trang thử nghiệm chưa thuộc release.
- Trước deploy: 509/509 unit test, typecheck, build và smoke PASS. Backup FAST
  database: `20261105T130611Z-db`; không migration, không đổi DNS, không xóa
  upload/DB volume. Cache tái sinh đã xóa: 91 page và 2 asset.
- Script deploy dừng tại bước verify đầu vì Caddy tạm báo `no upstreams
  available` khi container Nuxt đổi IP. Sau khi active health checker báo
  `host is up`, verify-stack và schema preflight chỉ đọc PASS. Sáu route chính
  trả 200; video MP4 trả 206 và đúng byte range. Browser production ở 390/1440
  không lỗi JavaScript hoặc tràn ngang; mobile dùng poster, desktop có video.
  PHP/Nuxt healthy và log 2 phút sau ổn định không có lỗi mới.
- Rollback code: `state/previous-image` và `state/previous-nuxt-image` trỏ về
  `5c4fc0728392`; dùng `/srv/phucthinhsolar/scripts/rollback-release.sh`.
  Backup hiện nằm trên VPS; hook off-site riêng chưa được cài.
- Ghi nhận: script cần đợi Caddy active health chuyển upstream sang `up` trước
  HTTP verify để tránh báo fail tạm thời. Bộ Playwright production cũ vẫn dùng
  selector template PHP, cần chuyển sang Nuxt ở đợt bảo trì test riêng.

## 2026-11-05 — Nuxt storefront fixes và cutout sân khấu Giải pháp

- Hai release liên tiếp: `4f241fed92f9` rồi follow-up `5c4fc0728392`; trạng
  thái production cuối cùng là PHP/Nuxt `5c4fc0728392`.
- Phạm vi: đồng bộ icon registry, ổn định phản hồi số lượng giỏ, sửa cache bài
  tin và PHP layout hook tùy chọn; bỏ ảnh bối cảnh khỏi stage thiết bị. Follow-up
  resolve URL alias Giải pháp qua `productCategoryTree`, để danh mục “Đèn sân
  vườn” lấy đúng ba sản phẩm CMS có PNG alpha; “Trụ đèn” không có sản phẩm nên
  giữ placeholder.
- Backup full trước release: `20261105T120925Z-full` và
  `20261105T124008Z-full`; không migration, không đổi DNS.
- Kiểm tra local ở follow-up `5c4fc0728392`: `npm test` 505/505, typecheck,
  build và `NITRO_SMOKE_MOCK_PHP=1 npm run smoke` đều PASS. Full browser suite
  trên release `4f241fed92f9`: 248 pass, 42 skip, 0 fail; follow-up được kiểm
  tra bằng browser production theo luồng riêng bên dưới.
- Kiểm tra production: PHP/Nuxt healthy; schema preflight read-only READY; các
  route `/`, `/san-pham/`, PDP, `/gio-hang/`, `/tin-tuc/` và `/giai-phap/` HTTP
  200; log web/Nuxt/proxy không có lỗi mới. Browser QA riêng `/giai-phap/` tại
  320/390/768/1440: không tràn ngang, không page error, không ảnh nền phong cảnh;
  chọn “Đèn sân vườn” đổi sang V600 ORIANA, T600 ORIANA và WL-0412, ảnh tải đủ và
  kiểm tra mẫu alpha cho thấy nền trong suốt.
- Production subset `mobile-commerce-experience.spec.js` và
  `typography-system.spec.js` còn selector của template PHP cũ nên không dùng
  làm gate Nuxt: các section/catalog/heading hiện có nhưng class legacy không
  tồn tại. Cần cập nhật selector trong lượt test-maintenance riêng; kết quả này
  không thay thế browser QA production nêu trên.
- Cache tái sinh đã xóa theo deploy script: release đầu 93 page + 11 asset;
  follow-up 104 page + 2 asset. Không chạm upload/DB volume.
- Rollback code hiện qua `/srv/phucthinhsolar/scripts/rollback-release.sh`;
  image trước đó được ghi trong `state/previous-image` và
  `state/previous-nuxt-image`. Backup hiện chỉ lưu trên VPS; script cảnh báo
  chưa có hook off-site riêng.

## 2026-10-09 — khôi phục nội dung tư vấn toàn bộ catalog

- Commit/image: `42db406ce85f` / `phucthinhsolar-web:42db406ce85f`; deploy `SAFE`.
- Phạm vi: giữ khối thẻ nhu cầu và FAQ trước CTA khi dữ liệu Admin thiếu; đồng
  bộ nội dung cho toàn bộ danh mục cha/con bằng migrations 063, 065, 067–071 và
  073; bổ sung 26 ảnh thẻ vào shared upload.
- Backup trước thay đổi: `20261008T155253Z-full` và backup SAFE
  `20261008T161208Z-full`.
- Kiểm tra: 248 Playwright local đạt, 42 skipped; 9 Playwright production đạt,
  5 skipped; audit sitemap đạt 31/31 URL danh mục, 0 lỗi; 26/26 ảnh HTTP 200.
- Production: web/database healthy; log web/proxy không có Fatal, Warning hoặc
  HTTP 5xx mới sau deploy.
- Rollback code: `/srv/phucthinhsolar/scripts/rollback-release.sh`; rollback dữ
  liệu dùng full backup trước migration sau khi xác nhận phạm vi cần phục hồi.
- Hai danh mục con không có row nội dung riêng (`Đèn cắm cỏ`, `Kẹp tấm pin`)
  kế thừa block từ danh mục cha và đã được kiểm tra trực tiếp.

## 2026-09-30 — rà soát lớp backup

- Trạng thái production không thay đổi; đây là cập nhật tài liệu (`docs-only`).
- Backup ứng dụng trên VPS vẫn có timer DB/full backup và các bản backup local có
  checksum.
- CloudFly automatic backup/snapshot là lớp khôi phục toàn VPS đang có theo gói
  hạ tầng hiện hành; cần kiểm tra lịch, retention và restore trên CloudFly Console
  theo định kỳ.
- `/usr/local/sbin/pts-backup-offsite` chưa phải là thành phần bắt buộc của
  baseline hiện tại. Nếu chưa cài hook này, cảnh báo của script chỉ có nghĩa là
  chưa có thêm một bản copy sang nhà cung cấp/object storage độc lập ngoài CloudFly.
- Không cleanup, không thay đổi backup và không thay đổi production trong lần rà
  soát này.

## 2026-09-29 — migration và vận hành sau deploy

- Commit/image đang chạy: `14fdb2672665` / `phucthinhsolar-web:14fdb2672665`.
- Phạm vi: migration 058–060, kiểm tra route, backup retention và dọn Docker
  build cache.
- Migration đã xác minh: payment/shipping tables; payment/carrier columns trên
  `table_order`; project location/date columns trên `table_news`; `weight` trên
  `table_product`.
- Backup ứng dụng giữ lại: `20260929T013137Z-full` và
  `20260929T012822Z-full`.
- Docker build cache: đã kiểm tra và prune phần không dùng; không xóa container,
  volume hoặc image đang chạy.
- Kiểm tra sau thay đổi: stack healthy, các route public chính HTTP 200, không có
  log web/proxy lỗi mới tại thời điểm kiểm tra.
- Rollback point: image trước đó được lưu qua `state/previous-image`; không tự
  rollback DB.

## Mẫu ghi cho lần tiếp theo

```text
Ngày/giờ:
Commit/image:
Mode: AUTO | SAFE | FAST | docs-only
Phạm vi thay đổi:
Migration:
Backup trước deploy:
Smoke/HTTP/Playwright:
Log scan:
Rollback point:
Rủi ro còn lại:
```
