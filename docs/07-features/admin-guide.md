# Admin — hiểu và làm cho nội dung sửa được từ Admin

Cập nhật: 2026-09-17. Đường dẫn tính từ
`domains/phucthinhsolar.com/public_html/`.

Đây là phần dễ hiểu sai nhất của hệ thống, và cũng là phần quan trọng nhất về
mặt vận hành: **một khối nội dung hardcode trong template là một khối chủ
project không tự sửa được.** Audit 2026-09-15 và 2026-09-16 đều xuất phát từ
chỗ này.

---

## 1. Admin không có bảng route — nó sinh ra từ khai báo

```text
administrator/index.php
  ├─ bootstrap dùng chung với storefront
  ├─ libraries/config-type.php  → nạp libraries/type/config-type-*.php
  │                               dựng $config['news'], $config['product'],
  │                               $config['static'], $config['photo'], ...
  ├─ libraries/requick.php      → auth (session + token DB + lockout theo IP) + RBAC
  ├─ administrator/templates/layout/menu.php
  │     foreach($config['news'] as $k => $v) → sinh một mục menu cho mỗi $k
  └─ administrator/sources/<com>.php  → xử lý list/add/edit/save/delete
```

Hệ quả: **thêm một mục menu Admin = thêm một khai báo trong
`libraries/type/config-type-*.php`**, không phải viết màn hình mới. Màn hình
CRUD, upload ảnh, phân trang, sắp xếp đã có sẵn và dùng chung.

---

## 2. Ba hệ lưu nội dung — chọn đúng hệ

| Hệ | Bảng | Dùng khi | Ví dụ thật |
|---|---|---|---|
| **Loại nội dung dạng danh sách** | `table_news`, phân biệt bằng cột `type` | Cần **nhiều record** cùng dạng | `du-an`, `giai-phap`, `tin-tuc`, `he-thong-cua-hang`, `project-benefit`, `home-about-point` |
| **Khối tĩnh một bản** | `table_static`, phân biệt bằng cột `type` | Cần **một** khối tiêu đề + mô tả + ảnh | `footer`, `su-menh`, `loi-ich`, `dai-ly-1..3`, `tom-tat-gioi-thieu` |
| **Khối mô tả dùng chung cho sản phẩm** | `table_content_section` (`scope` = `product`/`cat`/`brand`/`list`) | Mô tả sản phẩm có kế thừa theo danh mục/thương hiệu | mô tả sản phẩm, 247 record scope `product` ở local |

### Vận hành khối nội dung mô tả

- Admin có thể bật/ẩn trực tiếp khối nội dung của thương hiệu và danh mục bằng
  nút trạng thái trong màn **Khối nội dung mô tả**. Frontend chỉ dùng bản có cờ
  `hienthi`.
- Sản phẩm vẫn dùng bốn chế độ `inherit`, `own`, `both`, `hide`; form sản phẩm
  hiển thị thêm nguồn thực tế đang được render và số khối đang dùng.
- Trước khi xoá hoặc phục hồi ghi đè, hệ thống lưu snapshot vào
  `table_content_section_backup`. Màn danh sách có thể phục hồi bản sao gần
  nhất; migration tương ứng là `049_add_content_section_backups.sql`.
- JSON export/import giữ cả trạng thái hiển thị để không vô tình bật lại nội
  dung đã ẩn khi chuyển môi trường.

Chọn sai hệ là lỗi tốn công nhất: đưa một khối "một bản duy nhất" vào
`table_news` thì chủ project sẽ thấy màn hình danh sách có nút "Thêm mới" và
tạo ra 5 bản, trang thì chỉ hiển thị bản đầu.

Các loại đang khai báo (đọc từ `config-type-news.php` / `config-type-static.php`,
2026-09-17):

- `news`: `tin-tuc`, `chinh-sach`, `du-an`, `giai-phap`, `he-thong-cua-hang`,
  `loi-ich`, `cam-ket-dai-ly`, `phan-hoi-dai-ly`, `quy-trinh`, `tieu-chi`,
  `cam-ket`, `thong-tin-lien-he`, `about-team`, `about-principle`, `home-about`,
  `home-about-point`, `lich-su`, `su-menh`, `ly-do-chon-chung-toi`,
  `so-lieu-thong-ke`, `so-lieu-thong-ke-dai-ly`, `hinh-thuc-thanh-toan`,
  `project-benefit`, `project-trust`
- `static`: `su-menh`, `dai-ly-1`, `dai-ly-2`, `dai-ly-3`, `loi-ich`,
  `gioi-thieu`, `doi-tac-cong-nghe`, `tom-tat-gioi-thieu`,
  `gioi-thieu-san-pham`, `lienhe`, `footer`, `bct`

---

## 3. Làm một khối nội dung sửa được từ Admin — đủ 5 bước

Ví dụ thật: hàng 3 icon tin cậy ở CTA cuối trang Dự án, trước đây hardcode
trong `sources/project_hub.php`.

### Bước 1 — khai báo loại nội dung

`libraries/type/config-type-news.php`:

```php
$config['news']['project-trust'] = array(
    'title_main' => 'Dự án - Tin cậy CTA',
    'admin_note' => 'Tối đa 3 record đang Hiển thị tạo hàng icon tin cậy ở CTA cuối trang Dự án. Tên record là nhãn ngắn. Icon tuỳ chọn.',
    'check'  => array('hienthi' => 'Hiển thị'),
    'view'   => false, 'slug' => false, 'copy' => false,
    'images' => true,  'show_images' => true,
    'desc'   => false, 'content' => false, 'seo' => false,
    'width'  => 80, 'height' => 80, 'thumb' => '100x100x2',
    'img_type' => '.jpg|.gif|.png|.jpeg',
);
```

`admin_note` **không phải trang trí** — nó là dòng hướng dẫn hiện ngay trên form
cho chủ project. Viết rõ: bao nhiêu record được dùng, trường nào map vào đâu,
hiện ở URL nào. Bắt buộc có.

Các cờ hay dùng:

| Cờ | Ý nghĩa |
|---|---|
| `title_main` | Tên mục trong menu Admin |
| `admin_note` | Hướng dẫn hiện trên form |
| `check` | Các checkbox trạng thái, ghi vào cột `status` dạng chuỗi phân tách bằng dấu phẩy |
| `desc` | Bật ô mô tả ngắn |
| `content` | Bật CKEditor nội dung dài |
| `images` / `show_images` | Bật upload ảnh / hiện cột ảnh trong danh sách |
| `slug` | Có URL riêng hay không (khối nội dung thì `false`) |
| `view` | Có đếm lượt xem hay không |
| `seo` | Có tab SEO hay không |
| `thumb` | Kích thước thumbnail sinh tự động, dạng `WxHxMODE` |
| `phone`, `address`, `email`, `ggmap`, `map_iframe` | Bật các trường chi nhánh (xem `he-thong-cua-hang`) |

### Bước 2 — đọc dữ liệu trong `sources/`

```php
$projectTrustRows = $d->rawQuery(
    "select namevi, nameen, photo from #_news
     where type=? and find_in_set('hienthi',status)
     order by numb, id desc limit 0,3",
    array('project-trust')
);
```

Ba điều bắt buộc:

- Lọc trạng thái bằng `find_in_set('hienthi', status)`, **không** `status='hienthi'`.
- Sắp xếp `order by numb, id desc` để chủ project kéo-thả đổi thứ tự được.
- `limit` đúng số mà giao diện chịu được. Nếu giao diện chỉ có 3 ô thì `limit 3`,
  đừng để 10 record phá layout.

### Bước 3 — fallback khi Admin chưa nhập

Nội dung mặc định phải là **nội dung đã duyệt**, không phải chuỗi rỗng và cũng
không phải placeholder kiểu "Lorem" hay "Tiêu đề ở đây".

```php
if (empty($projectTrustRows)) {
    $projectTrustRows = $projectUi['trust_default'];   // bộ nội dung đã duyệt
}
```

Trang phải **tự đứng được** khi DB rỗng. Đây là tình trạng thật hiện nay:
`home-about`, `project-benefit`, `project-trust` đều chưa có record nào.

### Bước 4 — render trong template

Dùng đúng field thật, ẩn phần trống, **không bịa số liệu**:

```php
<?php foreach($projectTrustRows as $row){ ?>
  <div class="pts-ph-trust-item">
    <?php if(!empty($row['photo'])) echo $func->getImage(UPLOAD_NEWS, $row['photo'], 80, 80, array('alt' => $row['namevi'])); ?>
    <span><?=$row['name'.$lang]?></span>
  </div>
<?php } ?>
```

### Bước 5 — kiểm tra bằng Admin thật

Chưa vào Admin bấm thử thì chưa xong. Phải xác nhận:

- Mục menu hiện đúng tên.
- Thêm một record → trang public đổi đúng.
- Bỏ tick "Hiển thị" → khối biến mất, **không** vỡ layout.
- Xoá hết record → rơi về fallback, **không** trang trắng.
- Upload ảnh → thumbnail sinh đúng kích thước khai báo.

---

## 4. Thêm một cột mới cho loại nội dung sẵn có

Ví dụ thật: cột `photo_feed` cho ảnh Google Shopping.

1. **Migration SQL** trong `migrations/`, đánh số tăng dần, idempotent, có ghi
   chú rollback:

   ```sql
   ALTER TABLE `table_product`
       ADD COLUMN IF NOT EXISTS `photo_feed` VARCHAR(255) NULL DEFAULT NULL AFTER `photo_khuyenmai`;
   -- Rollback: ALTER TABLE `table_product` DROP COLUMN `photo_feed`;
   ```

2. **Thêm vào `migrations/000_preflight_production.php`** để trước khi deploy
   biết được production còn thiếu migration nào.

3. **Form Admin** trong `administrator/templates/<com>/.../..._tpl.php`, và xử
   lý lưu/xoá file trong `administrator/sources/<com>.php`.

4. **Query của storefront phải SELECT cột mới.** Bẫy thật: `sources/news.php` có
   nhiều nhánh query khác nhau cho cùng một `type`; sửa một nhánh mà quên nhánh
   kia thì biến rỗng ở một số trang. Với `du-an` có 2 nhánh (`idl` và mặc định).

5. **Validate ở chỗ nhập, không chỉ ở chỗ xuất.** `photo_feed` bị chặn ngay
   trong form nếu ảnh dưới 500×500 px, kèm filter "Thiếu ảnh Shopping sạch"
   trong danh sách để chủ project thấy còn thiếu bao nhiêu.

Lưu ý quan trọng: **query SELECT cột chưa tồn tại sẽ làm lỗi DB, không phải trả
về rỗng.** `administrator/sources/product.php` SELECT trực tiếp `photo_feed`
(dòng 88 và 793) — nếu deploy code mà quên chạy migration thì màn hình danh
sách/sửa sản phẩm sẽ chết. Luôn deploy migration **trước** code.

### Vai trò ảnh sản phẩm

- `table_product.photo`: ảnh đại diện ưu tiên cho card, PDP, SEO và dữ liệu có
  cấu trúc.
- `table_gallery`: ảnh phụ; ảnh đầu tiên đang hiển thị và còn file thật được dùng
  làm fallback khi `photo` trống hoặc file đã mất.
- `table_product.photo_feed`: ảnh nền sạch dành cho Google Shopping. Trạng thái
  “Thiếu ảnh Shopping sạch” độc lập với “Thiếu ảnh hiển thị”.
- `table_product.photo_khuyenmai`: khung chiến dịch; không được tính là ảnh sản
  phẩm.
- `photo2`: field legacy đang tắt trong cấu hình sản phẩm, không dùng làm nguồn
  ảnh đại diện mới.

File runtime tiếp tục nằm trong `shared/upload/product` trên production và
volume `pts_web_upload` ở local. Không tách hoặc di chuyển file vật lý theo thư
mục con nếu chưa có migration tương thích cho thumbnail, watermark, backup và
rollback. Tên file ZIP mới theo quy ước `MÃ-01` (ảnh chính), `MÃ-02...` (gallery).
Logic chọn ảnh và kiểm tra file vật lý nằm tại `libraries/product_images.php`;
không tự viết fallback riêng trong template mới.

---

## 5. Xác thực và phân quyền

- Đăng nhập: session + token trong DB + lockout theo IP.
  Mật khẩu là `md5(secret + password + salt)` — hệ cũ, **secret và salt phải
  giữ nguyên khi chuyển host**, tự sinh mới sẽ làm toàn bộ mật khẩu cũ không
  đăng nhập được. Xem [vps-cloudfly-runbook § 6](../06-operations/vps-cloudfly-runbook.md).
- RBAC: có schema và code. Bật/tắt theo môi trường; local chạy
  `LOCAL_ENFORCE_RBAC=1` để test đúng như production.
- Ánh xạ quyền theo màn hình nằm trong `libraries/admin_security.php`.
  Màn hình mới mà không khai báo quyền sẽ rơi về `SUPERADMIN_ONLY`.
- Tài khoản demo chỉ tạo bằng `scripts/create-local-rbac-demo-accounts.php`,
  **không** mang lên production.

Chi tiết: [05-security/auth-rbac.md](../05-security/auth-rbac.md).

---

## 6. Danh sách kiểm tra khi audit tích hợp Admin

Dùng khi rà một trang xem còn gì hardcode. Đây là cách hai audit vừa rồi tìm ra
`home-about`, `project-benefit`, `project-trust`, và nội dung teaser trang chủ.

1. Mở template của trang, tìm mọi chuỗi tiếng Việt viết thẳng trong HTML.
2. Với mỗi chuỗi, hỏi: chủ project có bao giờ muốn đổi nó không?
   - Có → phải đưa về Admin.
   - Không (nhãn UI như "Xem thêm") → để nguyên.
3. Tìm ảnh trỏ thẳng vào `assets/images/` — ảnh nội dung phải đi qua `upload/`.
4. Tìm số liệu cụ thể (năm kinh nghiệm, số dự án, phần trăm tiết kiệm). Số liệu
   **chưa xác minh thì không được viết vào code** — đây là nội dung khách hàng
   có thể bắt bẻ.
5. Kiểm tra mọi khối mới có fallback khi DB rỗng.
