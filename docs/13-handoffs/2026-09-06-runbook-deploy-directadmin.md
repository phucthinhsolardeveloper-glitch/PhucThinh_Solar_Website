# Runbook deploy lên hosting DirectAdmin (không có SSH)

Hosting dùng **DirectAdmin**, không cấp SSH nhưng **có Cron Jobs** — đó là đường
chạy migration PHP. Toàn bộ việc dưới đây làm qua giao diện DirectAdmin.

Làm đúng thứ tự. Mỗi bước có cách kiểm chứng ngay sau khi làm.

---

## Bước 0 — Backup trước, luôn luôn

DirectAdmin → **Create/Restore Backups** → tạo backup đầy đủ (file + database).
Tải bản backup đó về máy trước khi đi tiếp.

Riêng database, tải thêm một bản qua **phpMyAdmin → Export → Go** (định dạng SQL).
Backup của DirectAdmin đôi khi khó bóc riêng một bảng khi cần khôi phục gấp.

**Không làm bước nào tiếp theo khi chưa có backup nằm trên máy bạn.**

---

## Bước 1 — Sửa 2 dòng cấu hình

DirectAdmin → **File Manager** → mở
`domains/phucthinhsolar.com/public_html/libraries/config.php`

Sửa hai dòng:

```php
'error-reporting' => false,      // đang là true
'debug-developer' => false,      // đang là true
```

Vì sao:

- `error-reporting => true` bật `error_reporting(E_ALL)`. Gặp lỗi PHP là in thẳng
  ra trang khách kèm đường dẫn thật trên server. Đây cũng chính là cơ chế biến
  một cảnh báo nhỏ thành trang vỡ — hai lỗi đã sửa trong phiên này đều thuộc loại đó.
- `debug-developer => true` **bỏ qua toàn bộ kiểm tra phân quyền**. Để nguyên thì
  phân quyền vừa làm hoàn toàn vô nghĩa, nhân viên nào cũng vào được mọi thứ.

Hai dòng `debug-css` và `debug-js` **để nguyên** ở bước này. Chúng tắt nén CSS/JS;
đổi sang `false` giúp trang nhẹ hơn nhưng đổi cách nạp asset, nên làm sau khi
mọi thứ chạy ổn.

Kiểm chứng: mở `https://phucthinhsolar.com/` — trang vẫn bình thường, không có
dòng chữ lạ màu đỏ ở đầu trang.

---

## Bước 2 — Đưa code lên

Cách an toàn nhất là **thay theo thư mục**, không xoá cả `public_html`.

Từ máy bạn, lấy code mới nhất của nhánh `feature/ecommerce-upgrade`, rồi upload
đè lên hosting các thư mục sau:

```
domains/phucthinhsolar.com/public_html/libraries/
domains/phucthinhsolar.com/public_html/templates/
domains/phucthinhsolar.com/public_html/administrator/
domains/phucthinhsolar.com/public_html/sources/
domains/phucthinhsolar.com/public_html/api/
domains/phucthinhsolar.com/public_html/assets/css/
```

Và upload thư mục `migrations/` lên **ngang hàng với `domains/`**, tức
`/home/<tài-khoản>/migrations/` — KHÔNG để trong `public_html`.

**Tuyệt đối không đè lên:**

```
libraries/config.php     <- cấu hình riêng của hosting, đè là mất kết nối DB
upload/                  <- toàn bộ ảnh sản phẩm, tin tức
thumbs/  caches/  logs/  <- thư mục runtime
```

Kiểm chứng: mở trang chủ, xem mã nguồn trang (Ctrl+U), tìm chữ
`rll-youtube-player`. **Không còn** nghĩa là code mới đã lên.

---

## Bước 3 — Chạy 5 migration SQL bằng phpMyAdmin

DirectAdmin → **phpMyAdmin** → chọn database → tab **SQL** → dán nội dung từng
file rồi bấm Go. Chạy đúng thứ tự:

```
013_add_content_sections.sql
016_content_section_mode.sql
017_content_section_add_cat_scope.sql
021_add_product_promo_photo.sql
022_add_product_campaigns.sql
026_index_visit_tracking_tables.sql
```

Các file này dùng `CREATE TABLE IF NOT EXISTS` và `ADD COLUMN IF NOT EXISTS`, nên
chạy lại lần hai không hỏng gì. Riêng `026` khoá bảng trong lúc dựng index —
chạy lúc ít khách (khuya).

Kiểm chứng, dán vào tab SQL:

```sql
SELECT
  (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE table_schema = DATABASE() AND table_name = 'table_content_section') AS mig_013,
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE table_schema = DATABASE() AND table_name = 'table_product'
      AND column_name = 'photo_khuyenmai') AS mig_021,
  (SELECT COUNT(*) FROM information_schema.TABLES
    WHERE table_schema = DATABASE() AND table_name = 'table_product_campaign') AS mig_022,
  (SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE table_schema = DATABASE() AND index_name = 'idx_counter_ip_tm') AS mig_026;
```

Cả bốn cột phải khác 0.

---

## Bước 4 — Chạy migration PHP bằng Cron Jobs

DirectAdmin → **Advanced Features → Cron Jobs → CREATE CRON JOB**.

Mẹo: đặt lịch vào **một phút sắp tới** để nó chạy ngay, tick "Send all Cron output
to e-mail" để nhận kết quả, chạy xong thì **xoá cron job đó đi**.

Đường dẫn PHP trên DirectAdmin thường là `/usr/local/bin/php`. Nếu sai, xem
**System Info & Files → System Information** hoặc thử `/usr/bin/php`.

Thay `<tài-khoản>` bằng tên tài khoản hosting của bạn.

### 4.1 Luôn chạy XEM TRƯỚC (không có `--apply`) rồi đọc email kết quả

```
/usr/local/bin/php /home/<tài-khoản>/migrations/018_repair_orphan_product_taxonomy.php
```

Chạy lần lượt và đọc kỹ từng cái: `014`, `015`, `018`, `019`, `020`, `023`, `024`,
`027`, `028`.

Đây là bước quan trọng nhất. Dữ liệu production **khác local**, nhất là `018`,
`019`, `020` — chúng sửa dữ liệu hỏng do lỗi form Admin cũ, mà mức hỏng ở
production nhiều khả năng khác hẳn. Đọc xong thấy hợp lý mới sang bước sau.

### 4.2 Chạy thật, thêm `--apply`, đúng thứ tự

```
/usr/local/bin/php /home/<tài-khoản>/migrations/015_consolidate_description_into_sections.php --apply
/usr/local/bin/php /home/<tài-khoản>/migrations/018_repair_orphan_product_taxonomy.php --apply
/usr/local/bin/php /home/<tài-khoản>/migrations/019_assign_news_categories.php --apply
/usr/local/bin/php /home/<tài-khoản>/migrations/020_fix_mismatched_category_slugs.php --apply
/usr/local/bin/php /home/<tài-khoản>/migrations/023_delete_retired_products.php --apply
/usr/local/bin/php /home/<tài-khoản>/migrations/024_move_legacy_wp_images.php --apply
/usr/local/bin/php /home/<tài-khoản>/migrations/027_fix_inverter_typo.php --apply
/usr/local/bin/php /home/<tài-khoản>/migrations/028_clean_orphan_gallery_seo.php --apply
```

`014` chỉ là báo cáo, không ghi gì, chạy lúc nào cũng được.

**Trước khi chạy `028`:** theo quy ước của dự án, phải trích các dòng sắp xoá từ
bản backup ra file `.sql` cất riêng — xem phần đầu file `028`. Trên hosting cách
đơn giản là giữ nguyên bản backup ở Bước 0 và ghi lại ngày giờ chạy.

**Sau `024`:** thư mục `public_html/wp-content/` không còn cần thiết (khoảng
1,2 GB). Dùng File Manager nén lại rồi **tải về máy**, sau đó mới xoá trên
hosting. Đừng xoá thẳng.

Kiểm chứng sau khi chạy hết:

```sql
SELECT
  (SELECT COUNT(*) FROM table_product WHERE contentvi LIKE '%wp-content%') AS con_sot_wp,
  (SELECT COUNT(*) FROM table_news    WHERE namevi LIKE '%Invester%')      AS con_sot_typo,
  (SELECT COUNT(*) FROM table_gallery g
     LEFT JOIN table_product p ON p.id = g.id_parent
     WHERE g.com='product' AND g.kind='man' AND p.id IS NULL)              AS gallery_mo_coi;
```

Cả ba phải bằng **0**.

---

## Bước 5 — Bật phân quyền và tạo tài khoản nhân viên

Chỉ làm khi Bước 1 đã đổi `debug-developer => false`.

1. Đăng nhập Admin bằng tài khoản `admin`.
2. Menu **Quản lý user → Nhóm quyền**: xem lại ba nhóm dựng sẵn (cấp cao 150
   quyền, cơ bản 37, content 17), tick thêm/bớt quyền cho đúng nhu cầu.
3. **Quản lý user → Tài khoản admin → Thêm mới**: tạo cho từng nhân viên, chọn
   nhóm quyền tương ứng.
4. Đổi mật khẩu tài khoản `admin` sang mật khẩu mạnh, chỉ bạn giữ.

Lưu ý khi tạo tài khoản: tên đăng nhập **chỉ được chữ thường và số**, không dấu
gạch ngang. Bắt buộc điền đủ họ tên, email, giới tính, ngày sinh, địa chỉ.

Kiểm chứng: đăng nhập bằng một tài khoản nhân viên vừa tạo, xác nhận vào được
đúng phần được cấp và **không** vào được mục "Quản lý user".

---

## Bước 6 — Cấu hình email

Hiện `ip_host` và `email_host` đều trống, nghĩa là **website không gửi được email
nào**. Khách gửi liên hệ hay đặt hàng, bạn không nhận được thông báo.

Admin → **Cài đặt** → phần email. Hai lựa chọn:

- **Email của hosting**: DirectAdmin → E-mail Manager → E-mail Accounts, tạo hộp
  thư (ví dụ `no-reply@phucthinhsolar.com`), rồi điền host/port/mật khẩu vào Admin.
- **Gmail**: bật xác minh 2 bước cho tài khoản Google, tạo *App Password*, dùng
  `smtp.gmail.com`. Trong Admin đổi kiểu gửi sang Gmail.

Kiểm chứng: mở `https://phucthinhsolar.com/lien-he/`, gửi thử một liên hệ, kiểm
tra hộp thư nhận được không. Nếu không, xem lại host/port/mật khẩu — hiện lỗi gửi
mail chỉ ghi vào log chứ không làm vỡ trang nữa.

---

## Bước 7 — Dọn cache và kiểm tra lần cuối

File Manager → xoá sạch nội dung `public_html/caches/` (giữ lại thư mục).

Danh sách kiểm tra trên trình duyệt:

- [ ] Trang chủ mở bình thường, ảnh không mờ
- [ ] Trang sản phẩm, lọc theo công suất chạy đúng
- [ ] Trang chi tiết một sản phẩm, nút "Tư vấn & báo giá" mở được form
- [ ] Gửi thử form liên hệ, nhận được email
- [ ] Admin: thêm thử một sản phẩm rồi xoá đi
- [ ] Admin: xuất Excel và Word danh sách đơn hàng
- [ ] `https://phucthinhsolar.com/account/` trả 404 (không phải trang trắng)

---

## Nếu có sự cố

| Hiện tượng | Xử lý |
|---|---|
| Trang trắng sau khi upload | Bật lại `error-reporting => true` tạm thời để xem lỗi, sửa xong tắt lại ngay |
| Mất kết nối database | `config.php` bị đè mất — khôi phục file này từ backup |
| Ảnh mất hết | `upload/` bị đè — khôi phục từ backup |
| Migration chạy sai | Khôi phục database từ backup Bước 0, không cố sửa tay |
| Không vào được Admin | Đặt lại `debug-developer => true` để tạm bỏ qua phân quyền, vào sửa nhóm quyền rồi tắt |

---

## Việc nên làm sau khi deploy xong và ổn định

1. **Nâng PHP 7.4 lên 8.x** — 7.4 đã hết hỗ trợ bảo mật từ 11/2022. DirectAdmin
   có **Account Manager → PHP Settings** để đổi. Phải kiểm thử kỹ, code cũ có thể
   vỡ. Chủ project đã thống nhất để sau.
2. **Đổi cách băm mật khẩu** — hiện dùng MD5, thuật toán đã bị phá và rất nhanh
   để dò. Nên chuyển sang `password_hash()`, nâng cấp dần khi từng người đăng
   nhập để không ai bị mất mật khẩu. Riêng tài khoản khách còn dùng MD5 **không
   salt** (`administrator/sources/user.php` dòng 1058), yếu hơn nữa — hiện chưa
   có tài khoản khách nào nên sửa sớm là gọn nhất.
3. **Đổi `debug-css`/`debug-js` sang `false`** để nén CSS/JS, trang nhẹ thêm.
4. **Nén ảnh trong `upload/`** — `migrations/025` làm việc này nhưng còn lỗi ở
   một file PNG, chưa chạy được.
