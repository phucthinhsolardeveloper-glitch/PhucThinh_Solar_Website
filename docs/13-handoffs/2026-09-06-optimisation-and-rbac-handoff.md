# Bàn giao phiên tối ưu + phân quyền — 2026-09-06

Thay cho `2026-09-04-pre-deploy-audit-handoff.md` (đã lỗi thời). Chỉ ghi việc
**còn lại** và bối cảnh tối thiểu; chi tiết từng thay đổi nằm trong commit
message tương ứng.

Nhánh `feature/ecommerce-upgrade`, local và `origin` khớp nhau tại `d23c643`.

## 1. Bắt đầu lại

```bash
cd /home/admin01/Web_PTS_PHP
claude --continue
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/   # mong đợi 200
docker compose -f docker-compose.local.yml up -d                  # nếu chưa chạy
```

Trả về 403 nghĩa là WSL chưa mount xong ổ đĩa lúc Docker tự khởi động container:

```bash
docker compose -f docker-compose.local.yml up -d --force-recreate web
```

**Lưu ý quan trọng về thư mục:** `upload/`, `thumbs/`, `caches/`, `logs/` trong
container là **named volume**, không phải thư mục host. Thư mục `upload/` trên
host chỉ là bản cũ từ lúc build image (784 file so với 952 file thật). Muốn xem
dữ liệu thật phải vào container:

```bash
docker exec pts-php74-local ls /var/www/html/upload
```

## 2. Đã làm trong phiên này

Theo kế hoạch 5 mục chủ project duyệt. Xem trang kế hoạch:
https://claude.ai/code/artifact/8f93cbe0-983c-4aa4-833e-8b552ff6dd26

### Mục 02 — Phân quyền Admin: XONG

`$config['permission']['active']` đã bật trong `config-type.php`. Trước đó cổng
chặn trong `requick.php` thủng hai chỗ, nay đã bịt bằng
`adminLegacyScreenPermission()`:

- `com=user` từng được loại khỏi kiểm tra → ai đăng nhập được cũng tự tạo được
  tài khoản role 3. Nay chỉ role 3 quản lý được tài khoản và nhóm quyền.
- Các act ghi (`save`, `save_list`, `saveImages`, `uploadExcel`...) cũng từng
  được loại → ghi được dữ liệu không cần quyền. Nay quy về quyền `add`/`edit`
  tương ứng.

Kiểm chứng: `scripts/test-admin-permission-gate.php`, 12 assertion qua HTTP thật.

### Mục 03 — Dọn dung lượng: XONG phần lớn

Web root **2,2 GB → 585 MB**. Không xoá file nào, tất cả chuyển sang
`D:\FIleLinhTinh\phucthinhsolar-donkho-20260905\`:

| Đã chuyển | Dung lượng |
|---|---|
| `wp-content-cu-khong-dung/` | 1,2 GB / 18.786 file |
| `backup-web-cong-ty-khac/codientumanhdat.com.zip` | 482 MB |
| `dump-database/phucthinhsolar_com.sql` | 2,7 MB |

56 ảnh còn được nội dung dùng đã chép sang `upload/filemanager/wp-legacy/` và
`migrations/024` đã đổi đường dẫn trong 12 bản ghi.

**Còn lại:** nén ảnh (`migrations/025`) — chủ project yêu cầu để sau.

### Mục 04 — Bảo mật, backup, database: XONG phần làm được

- **Database:** `migrations/026` thêm index cho `table_counter` và
  `table_user_online`. Truy vấn chạy trên mọi lượt xem trang từ quét 87.000
  dòng xuống tra index 38 dòng, nhanh 3,5 lần.
- **Log:** `createLogs()` từng đọc cả file log của ngày (613 KB) vào RAM mỗi
  lần ghi rồi vứt đi. Đã bỏ, và xoay vòng log nay xoá mọi file cũ hơn 90 ngày.
- **Backup:** `backup-local.sh` từng đóng gói thư mục host (784 file) thay vì
  volume thật (952 file) — tức backup thiếu dữ liệu. Đã sửa. Diễn tập phục hồi
  đã nối vào `predeploy-local.sh`, chạy mỗi lần, mất ~4 giây.
- **Đã có sẵn, không cần làm:** chặn thực thi PHP trong `upload/`, giới hạn
  đăng nhập theo IP và theo tài khoản, CSRF, kiểm tra phiên.

### Mục 01 — Kiểm thử: bộ quét đã có, còn checklist thủ công

`scripts/test-admin-screen-sweep.php` gọi mọi màn hình Admin chỉ-đọc (111 màn
hình) và bắt fatal, notice PHP lọt HTML, JSON hỏng. Hiện **111/111 sạch**.

`scripts/test-admin-crud-lifecycle.php` bổ sung phần bộ quét cố tình không chạm:
luồng ghi. Nó lái thêm → sửa → xoá cho sản phẩm và tin tức qua đúng form Admin
trên HTTP thật, 19 assertion, hiện pass hết.

Bốn lỗi thật đã tìm ra và sửa xong trong phiên:

1. **Thêm sản phẩm bỏ trống "Công suất" luôn chết** bằng fatal error. `fillTable()`
   trong PDODb điền mặc định theo kiểu cột nhưng danh sách kiểu số thiếu
   `decimal`, nên cột `cong_suat` thành chuỗi rỗng và MySQL strict mode từ chối.
   Đây là lý do dữ liệu ORIANA phải nhập bằng script thay vì nhập tay.
2. **Xuất Excel đơn hàng** chết vì file vendor
   `PHPExcel/Writer/Excel2007/Comments.php` rỗng 0 byte từ commit đầu tiên.
3. **Xuất Word đơn hàng** chết vì `PHPWord_Template` ghi file tạm vào thư mục
   source không có quyền ghi.
4. **Notice PHP rải rác**: `$template` chưa đặt trong `administrator/index.php`,
   dữ liệu dashboard không nạp khi vào `?com=index`, và template gallery đọc
   chuỗi `$config` sâu 4 tầng không có fallback.

Log web hiện **sạch hoàn toàn**, không còn notice nào.

**Phát hiện quan trọng nhất phiên này — lỗi thứ 5:** viết
`scripts/test-public-form-submissions.php` để test luồng ghi công khai (form
Liên hệ, Đăng ký đại lý — chỉ có test kiểm tra form tồn tại, chưa từng submit
thật) thì lộ ra **`Email::send()` ném exception không ai bắt**, làm vỡ fatal
error ngay sau khi lưu DB thành công. Ảnh hưởng **7 điểm gọi**: liên hệ, đặt
hàng (khách chốt đơn xong vẫn có thể văng ra trang lỗi), đăng ký tài khoản,
quên mật khẩu, đăng ký đại lý. Đã bọc try/catch quanh toàn bộ
`Host/SetFrom/AddAddress/Send()` trong `class.Email.php`, trả `false` thay vì
vỡ — mọi nơi gọi đã sẵn nhánh xử lý "gửi thất bại", chỉ chưa từng chạy tới
được. Kiểm chứng: submit form Liên hệ giờ trả 302 sạch, không fatal, bản ghi
vẫn lưu đúng.

## 3. Việc còn lại

### 3.1 Cần chủ project trả lời trước

1. **Cờ `debug-developer` trên production đang bật hay tắt?** Cờ này bỏ qua
   toàn bộ kiểm tra quyền. Bật thì phân quyền vô nghĩa. `config.php` không nằm
   trong git nên không kiểm tra từ đây được.
2. **Danh sách nhân sự cần tài khoản Admin, mỗi người thuộc nhóm quyền nào?**
   Ba nhóm đã dựng sẵn: cấp cao (150 quyền), cơ bản (37), content (17).
3. **Host có cho nâng PHP 7.4 lên 8.x không?** Đã thống nhất bàn sau khi xong
   plan.

### 3.2 Deploy — ưu tiên cao nhất

Backup database production, rồi chạy migration **đúng thứ tự**:

```
013 → 015 --apply → 016 → 017 → 018 → 019 → 020 → 021 → 022 → 023 → 024 → 026
```

- `018`, `019`, `020` **mặc định dry-run**. Phải chạy xem trước trên DB thật,
  đọc kết quả, rồi mới thêm `--apply`.
- `023` (xoá 36 mã ngừng kinh doanh) và `024` (gom ảnh WordPress cũ) đều
  dry-run mặc định.
- `025` (nén ảnh) **chưa chạy, đang tạm dừng** theo yêu cầu chủ project. Script
  hiện còn lỗi: chết khi xử lý `upload/photo/a-9088.png`, chưa tìm nguyên nhân.
- `026` chỉ thêm index, chạy lúc vắng khách vì bảng là MyISAM nên ALTER khoá bảng.

**Trước khi deploy phân quyền:** chạy
`scripts/check-admin-permission-readiness.php` trên production để biết tài khoản
nào sẽ mất quyền truy cập (không nhóm quyền, nhóm tắt, nhóm rỗng).

### 3.3 Việc nhỏ ghi nhận, chưa làm

- 259 bản ghi `gallery` và 242 bản ghi `seo` mồ côi, đều trỏ tới sản phẩm id
  < 311 đã xoá từ lâu. Không ảnh hưởng hiển thị, dọn được bằng một migration.
- Chữ **"Invester"** trong mục "Những con số ấn tượng" sai chính tả, đúng là
  "Inverter". Nội dung CMS, sửa trong Admin.
- `templates/layout/css.php` còn một background trỏ tới
  `wp-content/plugins/rocket-lazy-load/assets/img/youtube.png` — file chưa từng
  tồn tại, code chết.
- `assets/wp-content/uploads` (138 MB) là cây riêng phục vụ rewrite URL cũ trong
  `.htaccess` cho SEO. **Không xoá** khi chưa xác nhận không còn backlink.
- Trang "Về chúng tôi" tải ảnh chứng nhận cỡ gốc (5 tấm, ~2,5 MB) vì dùng
  `size-error` thay vì `sizes`. Hero trang chủ đã dùng thumbnail.

## 4. Quy ước đang áp dụng

- **Migration mới**: dry-run mặc định, `--apply` mới ghi, in rõ từng dòng bị ảnh
  hưởng, liệt kê riêng ca không suy ra được để chủ project tự quyết.
- **Dọn file**: chuyển sang `D:\FIleLinhTinh`, không xoá, để chủ project kiểm tra.
- **Guard trong script deploy dùng `grep -qF`/`grep -qE`, không dùng `rg`** —
  `rg` chỉ là shell function của phiên tương tác, script non-interactive không thấy.
- **Canonical suy ra từ entity**, không lấy từ REQUEST_URI. Xem
  `publicCanonicalEntityPath()` trong `libraries/product_category_urls.php`.
- **Đổi slug công khai phải ghi 301** vào `#_url_redirect` trước khi update, và
  xoá cache công khai sau khi ghi. `migrations/020` làm mẫu đầy đủ.
