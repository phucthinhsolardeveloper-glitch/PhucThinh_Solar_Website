# Giai đoạn 3 — verify lightbox Gallery/Video thật bằng Playwright (2026-10-28)

Hai lượt QA trước (`STAGE3-BROWSER-QA-20261028.md` và review tay của Lane B/E)
không thể test hành vi lightbox thật vì DB local (`pts-mariadb-local`) không có
dữ liệu Gallery/Video (0 album, 0 video). Lượt này dựng DB hoàn toàn cách ly có
fixture tối thiểu để test bằng Playwright thật, xác nhận fix
`7238717`/`511704e` (focus trap không bị re-trap khi chuyển ảnh bằng phím mũi
tên) hoạt động đúng trên trình duyệt thật, không chỉ review code.

## Cách ly môi trường

- MariaDB 10.11 riêng (`pts-mariadb-lightbox-qa`, volume
  `pts-lightbox-qa-db-data`) — schema lấy qua `mysqldump --no-data
  --single-transaction` (chỉ đọc cấu trúc) từ `pts-mariadb-local`, KHÔNG copy
  dữ liệu thật.
- PHP 8.1 riêng (`pts-php81-lightbox-qa`, port `8095`) trỏ DB trên, mount
  `./domains/.../public_html` read-only + override riêng `/var/www/html/upload`
  (bind mount thường, không tmpfs) để vừa ghi `.htaccess`/thumbnail cache vừa
  giữ 4 ảnh fixture persist qua restart.
- Nuxt build riêng, chạy `node .output/server/index.mjs` với
  `NUXT_PHP_ORIGIN=http://127.0.0.1:8095`,
  `NUXT_PHP_ORIGIN_ALLOWLIST=127.0.0.1:8095`, `NUXT_PREVIEW_MEDIA_PROXY=1`,
  `PORT=3115`.
- Tất cả container/volume/network có tên riêng (`*-lightbox-qa*`), không đụng
  `pts-mariadb-local`, `pts-php81-local` hay volume upload chung.

## Fixture (dữ liệu tối thiểu, tự tạo)

- 1 ảnh bìa + 3 ảnh gallery: copy từ 4 file ảnh THẬT đã có sẵn trong
  `upload/product/` của site (không fabricate ảnh), đổi tên
  `lightbox-fixture-{cover,1,2,3}.png`. Không phải dữ liệu khách hàng, chỉ là
  asset ảnh tái dùng cho fixture.
- SQL fixture (an toàn, chỉ chạy trên DB cách ly, id range `900xx` để không va
  ID thật):

  ```sql
  INSERT INTO table_product (id, namevi, slugvi, photo, descvi, status, type, numb)
  VALUES (90001, 'Lightbox QA Album', 'lightbox-qa-album', 'lightbox-fixture-cover.png', 'Fixture album for lightbox QA', 'hienthi', 'thu-vien-anh', 1);

  INSERT INTO table_gallery (id, id_parent, photo, namevi, numb, com, type, kind, val, status)
  VALUES
   (90001, 90001, 'lightbox-fixture-1.png', 'Fixture ảnh 1', 1, 'product', 'thu-vien-anh', 'man', 'thu-vien-anh', 'hienthi'),
   (90002, 90001, 'lightbox-fixture-2.png', 'Fixture ảnh 2', 2, 'product', 'thu-vien-anh', 'man', 'thu-vien-anh', 'hienthi'),
   (90003, 90001, 'lightbox-fixture-3.png', 'Fixture ảnh 3', 3, 'product', 'thu-vien-anh', 'man', 'thu-vien-anh', 'hienthi');

  INSERT INTO table_photo (id, namevi, link_video, type, act, numb, status)
  VALUES (90001, 'Fixture Video QA', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'video', 'video_item', 1, 'hienthi');
  ```

  (Data model xác nhận bằng cách đọc `api/pts_v1_gallery.php`,
  `pts_v1_gallery_detail.php`, `pts_v1_video.php` và
  `libraries/pts_public_media_library_api.php` — Gallery album = `table_product`
  `type='thu-vien-anh'`; ảnh trong album = `table_gallery` với
  `id_parent/com/type/kind/val` đúng tổ hợp; Video = `table_photo`
  `type='video'`.)

## Phát hiện môi trường (không phải bug code, ghi lại để lần sau khỏi dò lại)

- Container PHP cần `/var/www/html/upload` **ghi được** (không chỉ
  `caches`/`logs`/`thumbs`) — `libraries/constant.php` tự `mkdir()`/
  `file_put_contents()` `.htaccess` trong `upload/` lúc bootstrap; nếu thư mục
  này read-only, PHP in ra hàng loạt `<b>Warning</b>` HTML **trước** JSON, phá
  luôn response — làm Nuxt nhận JSON không hợp lệ và trả 502 "Invalid PHP ...
  contract" (ban đầu tưởng là bug contract, hóa ra chỉ do container test thiếu
  quyền ghi `upload/`).
- Phải set `NUXT_PREVIEW_MEDIA_PROXY=1` mới proxy được ảnh preview (đã có
  trong memory từ trước, xác nhận lại).

## Kết quả Playwright thật (`lightbox-qa.mjs`, chromium headless)

12/12 PASS:

| Hành vi | Kết quả |
|---|---|
| Fixture ảnh/video render đúng | PASS |
| Lightbox gallery mở khi click | PASS |
| Focus chuyển vào overlay lúc mở | PASS |
| Tab 15 lần vẫn giữ focus trong overlay (không thoát ra ngoài) | PASS |
| ArrowRight chuyển ảnh KHÔNG còn re-trap focus (xác nhận fix `511704e`) | PASS |
| Escape đóng overlay gallery | PASS |
| Focus trả đúng về nút trigger sau Escape (gallery) | PASS |
| Fixture video render đúng | PASS |
| Overlay video mở khi click | PASS |
| Escape đóng overlay video | PASS |
| Focus trả đúng về nút trigger sau Escape (video) | PASS |
| Không có console/page error nào trên cả 2 trang | PASS |

Kết luận: fix focus trap ở Lane B/E hoạt động đúng trên trình duyệt thật, không
chỉ đúng khi review code. **Giai đoạn 3 (browser QA) đóng hoàn toàn**, không
còn mục NEEDS VERIFICATION nào treo.

## Dọn dẹp

`docker compose -f docker-compose.lightbox-qa.yml down -v`, xóa network
`pts-lightbox-qa-net`, kill process `node .output/server/index.mjs`, xóa
`_upload-fixture/` (không commit — chỉ là ảnh thật copy tạm cho test, không
phải fixture cần giữ lại; SQL insert ở trên đủ để tái tạo). Không còn
container/volume nào tên `*-lightbox-qa*` sau khi dọn.
