# Hiểu codebase — tìm đúng file trong 2 phút

Cập nhật: 2026-09-17. Tất cả đường dẫn tính từ
`domains/phucthinhsolar.com/public_html/`.

Tài liệu này trả lời đúng một câu hỏi: **"trang này do file nào tạo ra?"** —
câu hỏi tốn thời gian nhất với người mới, vì framework là tự viết, không phải
Laravel/WordPress nên không có convention quen thuộc nào áp dụng được.

---

## 1. Một request đi qua đâu

```text
GET /san-pham/den-pha-jindian-jd-t300
  │
  ├─ .htaccess              rewrite tất cả về index.php (trừ file tĩnh có thật)
  │
  └─ index.php
       ├─ libraries/constant.php     định nghĩa SOURCES, TEMPLATE, LIBRARIES, UPLOAD_*
       ├─ libraries/config.php       host/user/pass DB, app secret + salt   ← KHÔNG có trong git
       ├─ libraries/autoload.php     nạp toàn bộ libraries/class/*.php
       ├─ libraries/config-type.php  nạp libraries/type/config-type-*.php
       │                             → dựng $config['news'], $config['product'], $config['static']...
       └─ libraries/router.php
            ├─ $com = đoạn URL đầu tiên           ('san-pham')
            ├─ switch($com) → đặt $source, $template, $type, $titleMain, seo type
            ├─ require sources/allpage.php        dữ liệu dùng chung mọi trang (menu, setting, footer)
            ├─ include sources/$source.php        logic riêng của trang
            └─ include templates/index.php
                 ├─ templates/layout/head.php, css.php, header.php, breadcrumb.php
                 ├─ include TEMPLATE.$template."_tpl.php"    ← NỘI DUNG TRANG
                 └─ templates/layout/footer.php, modal.php, js.php
```

Điểm quan trọng: **`$source` và `$template` được đặt trong một `switch` duy
nhất** ở `libraries/router.php` (khoảng dòng 239 trở đi). Muốn biết trang nào
dùng file nào, mở đúng cái switch đó và đọc. Không có bảng route riêng.

---

## 2. Tra cứu nhanh: URL → source + template

Đọc từ `libraries/router.php`. Bảng này là ảnh chụp tại 2026-09-17; khi router
đổi thì bảng này sai — luôn đối chiếu lại file.

| URL | `$source` | `$template` |
|---|---|---|
| `/` | `index` | `home/home` (theo layout đang chọn) |
| `/gioi-thieu/` | `about` | `about/about` |
| `/lien-he/` | `contact` | `contact/contact` |
| `/dai-ly` | `daily` | `daily/daily` |
| `/san-pham/...` (danh mục) | `product` | `product/product` |
| `/san-pham/<slug>` (chi tiết) | `product` | `product/product_detail` |
| `/tin-tuc/` | `news` | `news/news` |
| `/tin-tuc/<slug>` | `news` | `news/news_detail` |
| `/giai-phap/` | `solution_hub` | `news/solution` |
| `/giai-phap/<slug>` | `news` | `news/solution_detail` |
| `/du-an-da-thuc-hien/` | `project_hub` | `news/project` |
| `/du-an-da-thuc-hien/<slug>` | `news` | `news/project_detail` |
| `/tim-kiem` | `search` | `product/product` |
| `/gio-hang` | `order` | `order/...` |
| `/cam-on` | `thankyou` | — |

Hai điều gây nhầm:

1. **Trang "hub" có source riêng, trang chi tiết thì không.** `solution_hub.php`
   và `project_hub.php` chỉ chạy cho trang danh sách. Trang chi tiết dùng
   `sources/news.php` với `$type` khác nhau. Nên khi sửa trang chi tiết dự án,
   biến bạn cần thường đã được `news.php` dựng sẵn.
2. **`sources/news.php` phục vụ 4 loại nội dung** (`tin-tuc`, `giai-phap`,
   `du-an`, `chinh-sach`) qua biến `$type`. Sửa một query trong file này có thể
   ảnh hưởng cả 4 trang — luôn kiểm tra nhánh nào đang chạy.

---

## 3. Vai trò từng thư mục

| Thư mục | Vai trò | Được phép chứa |
|---|---|---|
| `sources/` | Logic storefront: query DB, dựng biến cho view | SQL, tính toán. **Không** HTML |
| `templates/` | View. `layout/` là khung chung, còn lại theo trang | HTML + echo. **Không** query DB mới |
| `libraries/` | Helper theo nghiệp vụ (giá, feed, filter, watermark, sitemap...) | Hàm thuần, ít trạng thái |
| `libraries/class/` | Service dùng chung: `PDODb`, `Cache`, `Functions`, `Cart`, `Seo`, `Email` | Class |
| `libraries/type/` | **Khai báo loại nội dung** → sinh ra màn hình Admin. Xem [admin-guide](../07-features/admin-guide.md) | Mảng `$config` |
| `api/` | Endpoint AJAX, mỗi file tự bootstrap qua `api/config.php` | Trả JSON/HTML fragment |
| `administrator/` | CMS riêng, cùng DB | Ứng dụng độc lập |
| `assets/` | CSS/JS/ảnh tĩnh của theme | — |
| `upload/` | **Dữ liệu thật, không tái tạo được.** Ảnh do Admin upload | Phải backup |
| `thumbs/`, `watermark/`, `caches/`, `assets/caches/` | Dẫn xuất, xóa được và hệ thống tự sinh lại | Không cần backup |
| `wp-content/` | Asset WordPress cũ còn sót, **không** nằm trong bootstrap PHP | Legacy |

Quy tắc đặt chỗ khi viết code mới: có query DB → `sources/` hoặc `libraries/`;
có HTML → `templates/`; dùng lại ở nhiều nơi → `libraries/`.

---

## 4. Truy cập database

Luôn qua `$d` (instance `libraries/class/class.PDODb.php`). `#_` là placeholder
prefix, PDODb đổi thành `table_`.

```php
// Đọc nhiều dòng
$rows = $d->rawQuery("select id, namevi from #_news where type=? and find_in_set('hienthi',status) order by numb, id desc limit 0,3", array('project-benefit'));

// Đọc một dòng
$row = $d->rawQueryOne("select id, photo_feed from #_product where id=? and type=? limit 0,1", array($id, $type));

// Ghi
$d->insert('content_section', array('scope' => 'product', 'scope_id' => $productId));
$d->where('id', $id);
$d->update('product', array('photo_feed' => ''));
```

Bắt buộc:

- **Luôn dùng placeholder `?`**, không nối chuỗi vào SQL.
- `status` là **chuỗi nhiều cờ phân tách bằng dấu phẩy** (`"noibat,hienthi"`),
  nên lọc bằng `find_in_set('hienthi', status)`, không phải `status='hienthi'`.
- Trường đa ngữ có hậu tố ngôn ngữ: `namevi`/`nameen`, `descvi`/`descen`.
  Trong code dùng `"name".$lang`. Khi cần fallback:
  `coalesce(nullif(namevi,''), nameen)`.

---

## 5. Ảnh: luôn qua `getImage()`

`libraries/class/class.Functions.php`. Đừng tự viết thẻ `<img>` — hàm này lo
lazy-load, ảnh lỗi, `decoding`, thumbnail.

```php
echo $func->getImage($path, $file, $w, $h, array(
    'alt'          => $alt,
    'isLazy'       => false,        // true = lazy bằng JS (data-src), cần thư viện lazy
    'noNativeLazy' => true,         // CHỈ dùng cho ảnh trong carousel
));
```

Hợp đồng ba trạng thái — nhớ đúng, vì đã từng sai và làm trắng 10/15 ảnh:

| Tình huống | `isLazy` | `noNativeLazy` | Kết quả |
|---|---|---|---|
| Lưới tĩnh, ảnh dưới màn hình | `false` | (bỏ) | `loading="lazy"` — đúng, tiết kiệm băng thông |
| Ảnh trong **carousel/slider** | `false` | `true` | tải ngay — **bắt buộc**, xem dưới |
| Lazy bằng JS (theme cũ) | `true` | — | `data-src`, cần thư viện lazy chạy |

Vì sao carousel phải tắt native lazy: slider dùng `transform` đẩy slide ra ngoài
vùng nhìn, trình duyệt coi ảnh đó là "chưa cần" và **hoãn vô thời hạn** — ảnh chỉ
tải khi bấm mũi tên. Biểu hiện: `src` trả HTTP 200 nhưng `naturalWidth = 0`.

Trong `templates/product/_product_card.php` hai khái niệm này là hai biến khác
nhau, đừng suy ra từ nhau:

- `$pcNoLazy` — tắt lazy **JS**, vẫn giữ native lazy. Dùng cho lưới tĩnh.
- `$pcInCarousel` — tắt **cả** native lazy. Chỉ dùng cho carousel.

---

## 6. Cache trang

`libraries/class/class.Cache.php` ghi HTML đã render vào `caches/cache_<hash>`.
Hệ quả thực tế: **deploy file PHP/template mà không xóa cache thì không thấy gì
đổi.** Bước xóa cache là bắt buộc trong mọi quy trình deploy.

- Local: `docker compose -f docker-compose.local.yml exec web rm -f /var/www/html/caches/cache_*`
  (xóa `caches/` trên host **không** tác dụng — nó là named volume).
- Host cũ: xóa qua FTP bằng nhiều cờ `-Q "DELE ..."` trong một lần gọi curl.
- VPS: `caches/` là bind mount trong `shared/caches`, xóa trực tiếp được.

---

## 7. Đa ngữ

`libraries/lang/<lang>.php` định nghĩa constant (ví dụ `lienhe`). Router nạp
theo `$lang`. Dữ liệu đa ngữ nằm trong cột có hậu tố. Tiếng Anh **chưa dịch đủ**
— khi thêm text mới, thêm cả hai cột nhưng đừng coi bản `en` là đã hoàn chỉnh.

---

## 8. Cái gì KHÔNG có trong hệ thống này

Ghi ra để khỏi đi tìm:

- Không có service/repository layer, không DI container.
- Không có ORM. Không có migration framework tự động — migration là file SQL
  đánh số trong `migrations/`, chạy tay.
- Không có queue, Redis, object storage.
- Không có CI. Cổng kiểm tra chạy tay ở local, xem [testing.md](../02-development/testing.md).
- Không có foreign key trong DB. Quan hệ chỉ là logic trong code → xóa bản ghi
  cha có thể để lại dữ liệu mồ côi. Xem [database.md](../03-data/database.md).
