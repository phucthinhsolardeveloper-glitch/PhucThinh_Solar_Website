# Runbook đổi hosting (phòng trường hợp nhà cung cấp cũ gây khó/phá host)

Ngày viết: 2026-09-12. Áp dụng cho `phucthinhsolar.com`, hosting DirectAdmin hiện
tại (PHP 7.4.29, MariaDB 10.4.24, không SSH — chỉ FTP + phpMyAdmin).

## 0. Phát hiện quan trọng nhất — domain và hosting KHÔNG cùng một chỗ

Đã tra cứu trực tiếp (RDAP + DNS) ngày 2026-09-12:

- **Domain `phucthinhsolar.com` đăng ký tại P.A Vietnam** (P.A. Viet Nam Company
  Limited), hết hạn 2029-08-14, trạng thái `clientTransferProhibited` (khoá
  transfer mặc định, bình thường, chủ tài khoản tự mở được).
- **DNS nằm ở `ns1/ns2.dotvndns.vn` + `nsbak.dotvndns.com`** — hạ tầng DNS của
  P.A Vietnam, KHÔNG phải server hosting.
- **Server hosting nằm trên hạ tầng VNPT** (IP `103.82.21.26`, AS135905), dùng
  DirectAdmin, tách biệt hoàn toàn với domain/DNS.

**Ý nghĩa**: nếu bên đang quản lý hosting (agency/dev cũ) có phá/khoá server,
domain và DNS **không bị ảnh hưởng** — miễn anh còn giữ được quyền truy cập tài
khoản P.A Vietnam. Đây là lớp bảo vệ quan trọng nhất, cần xác nhận NGAY (mục 1),
trước khi lo tới việc backup file hay đổi host.

Rủi ro thật sự nằm ở đúng 2 chỗ: (a) ai đang login được vào tài khoản P.A
Vietnam (không phải ai đứng tên domain, mà là ai có username/password đăng
nhập được), và (b) dữ liệu trên server hosting (file + database) có được
backup ra ngoài server đó hay chưa.

## 1. Việc phải làm trong hôm nay, bất kể có đổi host hay không

1. **Tự đăng nhập thử tài khoản P.A Vietnam** (my.pavietnam.vn hoặc trang quản
   lý domain của P.A Vietnam) bằng email/số điện thoại của chính công ty, không
   qua email của agency/dev cũ. Nếu không đăng nhập được hoặc email khôi phục
   là email người khác — liên hệ P.A Vietnam đổi ngay thông tin chủ sở hữu/liên
   hệ về đúng email của anh. Đây là việc khẩn cấp nhất, làm trước mọi thứ khác.
2. **Backup toàn bộ file + database về máy cá nhân** (chi tiết ở mục 3) — làm
   trong hôm nay, không đợi quyết định có đổi host hay không.
3. Ghi lại (viết tay hoặc file riêng ngoài repo Git) toàn bộ thông tin truy cập
   đang có: tài khoản FTP, tài khoản DirectAdmin (nếu có), tài khoản
   phpMyAdmin, tài khoản P.A Vietnam, tài khoản email SMTP đang gửi mail từ
   website. Không lưu các thông tin này vào repo Git hay gửi qua kênh không an
   toàn (chat công khai, email không mã hoá).

## 2. Kiểm kê tài sản kỹ thuật hiện tại

| Hạng mục | Ở đâu | Ghi chú |
|---|---|---|
| Domain registrar | P.A Vietnam | Xác nhận lại email liên hệ/chủ sở hữu |
| DNS | `dotvndns.vn` (P.A Vietnam) | Đổi bản ghi A ở đây khi cutover, mục 8 |
| Hosting/file/DB | DirectAdmin trên hạ tầng VNPT, IP `103.82.21.26` | Chỉ FTP + phpMyAdmin, không SSH |
| Email gửi từ site | SMTP `mail.phucthinhsolar.com`, port 587 STARTTLS | Cấu hình ở file `~/.pts-mail.php` ngoài webroot, không nằm trong Git |
| License bên thứ 3 | CKFinder (`CKFINDER_LICENSE_NAME`/`CKFINDER_LICENSE_KEY`) | Đọc trực tiếp từ `config.php`/biến môi trường trên host cũ, xin cấp lại hoặc kiểm tra còn hạn nếu đổi domain/host |
| Mã nguồn | GitHub, branch `feature/ecommerce-upgrade` | Đã đồng bộ đầy đủ tới thời điểm viết tài liệu này |

## 3. Backup toàn bộ (làm trước, giữ ở máy cá nhân + ít nhất 1 nơi khác — email/cloud riêng)

### 3.1. File qua FTP

Dùng FileZilla/WinSCP, tải toàn bộ thư mục `domains/phucthinhsolar.com/public_html/`
về máy. Riêng các thư mục sau **dung lượng lớn nhưng có thể bỏ qua** nếu cần
tiết kiệm thời gian (tự sinh lại được, không phải dữ liệu gốc):

- `caches/` — cache CSS/JS/query, tự sinh lại.
- `thumbs/` — cache ảnh đã resize, tự sinh lại khi có request.
- `watermark/` — cache ảnh đã đóng logo, tự sinh lại khi có request.
- `logs/` — log lỗi, không cần mang sang host mới.

**Bắt buộc phải tải, không được bỏ qua:**

- `upload/` — toàn bộ ảnh/file sản phẩm, tin tức, banner... đã upload qua
  admin. Đây là dữ liệu gốc, mất là mất thật, không tự sinh lại.
- `libraries/config.php` — chứa thông tin kết nối database và cấu hình quan
  trọng khác. File này **không nằm trong Git** (bị `.gitignore`), chỉ có trên
  host, phải tải riêng.
- Toàn bộ mã nguồn còn lại trong `public_html/` (nếu không chắc host mới sẽ
  lấy mã nguồn từ GitHub, cứ tải hết cho chắc).

Ngoài `public_html/`, ở thư mục gốc tài khoản FTP còn có:

- `migrations/` — script vận hành, không phải webroot công khai, nên tải luôn
  để có đầy đủ công cụ khi cần chạy lại trên host mới.
- `~/.pts-mail.php` — file bí mật cấu hình SMTP, quyền `0600`, nằm ngoài
  `public_html/`. Phải tải riêng, và tuyệt đối không đưa vào Git hay chia sẻ
  qua kênh không mã hoá — nó chứa mật khẩu email thật.

### 3.2. Database qua phpMyAdmin

1. Vào phpMyAdmin → chọn đúng database production (tên lấy từ
   `libraries/config.php`, mục `database.dbname`).
2. Export → chọn "Custom" → format SQL → tick đủ "Add DROP TABLE", "Add CREATE
   DATABASE" → tải file `.sql` về máy.
3. Đặt tên file có ngày tháng rõ ràng (vd `phucthinhs-production-2026-09-12.sql`)
   và giữ file này cùng chỗ với backup file ở mục 3.1.

### 3.3. DirectAdmin backup (nếu còn quyền truy cập panel)

Nếu tài khoản DirectAdmin (không phải chỉ FTP) vẫn đăng nhập được: vào
**Create/Restore Backups**, tạo backup toàn tài khoản (file + database +
email), tải về. Đây là bản sao đầy đủ và nhanh nhất nếu cần restore lại đúng
y nguyên trạng thái. Nếu KHÔNG còn quyền vào panel (chỉ có FTP), bỏ qua bước
này — bản backup ở mục 3.1 + 3.2 vẫn đủ để dựng lại trên host mới, chỉ mất
công hơn.

## 4. Yêu cầu kỹ thuật tối thiểu cho host mới

| Thành phần | Yêu cầu | Vì sao |
|---|---|---|
| PHP | 7.4.x (khớp bản đang chạy: 7.4.29) | Code chưa test trên PHP 8.x, có thể lỗi cú pháp/hàm đã bỏ |
| PHP extension | `calendar`, `exif`, `gd` (kèm `freetype`, `jpeg`), `mbstring`, `mysqli`, `pdo_mysql`, `zip` | Thiếu bất kỳ cái nào là site lỗi khi chạy |
| Database | MariaDB 10.4.x hoặc MySQL tương thích | Khớp bản đang chạy |
| Web server | Apache với `mod_rewrite`, `mod_headers`, `mod_expires` bật sẵn; `AllowOverride All` | Toàn bộ định tuyến site đi qua `.htaccess` → `index.php`, thiếu `mod_rewrite`/`AllowOverride All` là site chỉ hiện trang trắng hoặc lỗi 404 mọi trang |
| SSL | Let's Encrypt hoặc tương đương, ép HTTPS | Site hiện đã chạy HTTPS |
| Dung lượng ổ đĩa | Tối thiểu 2-3 GB cho code + `upload/`, cộng thêm khoảng trống cho `thumbs/`/`watermark/`/`caches/` tự sinh (thường vài trăm MB) | Ước tính dựa trên dung lượng `upload/` thực tế cần kiểm tra lại khi tải xuống |
| Panel quản trị | DirectAdmin không bắt buộc — cPanel/Plesk hay VPS thuần cũng chạy được, miễn đáp ứng các mục trên | Code không phụ thuộc DirectAdmin, chỉ phụ thuộc PHP/Apache/MySQL |

## 5. Thiết lập host mới

1. Đăng ký hosting/VPS mới, xác nhận đủ yêu cầu ở mục 4 (hỏi thẳng nhà cung
   cấp mới bằng đúng bảng trên trước khi trả tiền).
2. Nếu là hosting có control panel (DirectAdmin/cPanel): tạo tài khoản, ghi
   nhận IP mới, tài khoản FTP mới, tài khoản phpMyAdmin mới.
3. Nếu là VPS thuần: cần tự cài Apache + PHP 7.4 + MariaDB + các extension ở
   mục 4, hoặc thuê dịch vụ cài đặt hộ.
4. **Chưa trỏ domain sang host mới ở bước này** — cứ để domain vẫn chạy ở host
   cũ, test xong xuôi (mục 7) mới cutover (mục 8).

## 6. Khôi phục dữ liệu lên host mới

1. Tải mã nguồn mới nhất từ GitHub (branch `feature/ecommerce-upgrade`, hoặc
   nhánh chủ project chỉ định) lên host mới qua FTP/Git, vào đúng vị trí
   webroot mới (vd `public_html/` hoặc `domains/phucthinhsolar.com/public_html/`
   tuỳ cấu trúc host mới).
2. Copy `libraries/config.php` đã backup ở mục 3.1 vào đúng vị trí — **rồi sửa
   lại thông tin database** (`host`, `username`, `password`, `dbname`) khớp
   với database mới sẽ tạo ở bước 3.
3. Tạo database mới trên host mới (qua phpMyAdmin/panel), import file `.sql`
   đã export ở mục 3.2.
4. Upload lại thư mục `upload/` đã backup ở mục 3.1. Không cần copy
   `caches/`/`thumbs/`/`watermark/`/`logs/` — tạo thư mục rỗng, phân quyền ghi
   được cho web server, để hệ thống tự sinh lại khi có request.
5. Copy `~/.pts-mail.php` vào đúng vị trí tương ứng ngoài webroot trên host
   mới (theo cấu trúc thư mục home mới), giữ quyền `0600`.
6. Kiểm tra `libraries/config.php` có 2 dòng bắt buộc cho production:
   ```php
   'error-reporting' => false,
   'debug-developer' => false,
   ```
   Thiếu 2 dòng này site sẽ lộ lỗi PHP chi tiết ra ngoài cho khách xem.
7. Set quyền ghi (`chmod`) cho các thư mục: `upload/`, `thumbs/`, `watermark/`,
   `caches/`, `logs/` — web server (Apache/PHP-FPM) phải ghi được vào các thư
   mục này, nếu không sẽ lỗi upload ảnh/tạo thumbnail.

## 7. Kiểm thử trước khi đổi DNS (bắt buộc, đừng bỏ qua)

Trước khi trỏ domain thật sang, test bằng 1 trong 2 cách:

- **Cách 1 — sửa file hosts trên máy cá nhân** (Windows:
  `C:\Windows\System32\drivers\etc\hosts`): thêm dòng
  `<IP host mới> phucthinhsolar.com` rồi truy cập `https://phucthinhsolar.com/`
  như bình thường — máy cá nhân sẽ vào thẳng host mới mà domain thật vẫn chưa
  đổi, khách khác không bị ảnh hưởng.
- **Cách 2 — dùng IP hoặc domain phụ tạm** nhà cung cấp mới cấp sẵn (vd
  `xyz.hostingprovider.com`), nếu SSL/HTTPS cho phép test qua đó.

Checklist cần xác nhận PASS trước khi cutover:

- [ ] Trang chủ, trang sản phẩm, trang tin tức load được, không lỗi PHP
      (Fatal/Warning/Notice hiện ra màn hình).
- [ ] Đăng nhập admin (`/administrator/`) được, xem được danh sách sản phẩm.
- [ ] Ảnh sản phẩm/tin tức hiển thị đúng (kiểm tra `upload/` đã copy đủ).
- [ ] Thử đặt 1 đơn hàng test / gửi 1 form liên hệ test, xác nhận nhận được
      email (khớp cấu hình SMTP ở `~/.pts-mail.php`).
- [ ] Giỏ hàng, tìm kiếm, lọc sản phẩm hoạt động bình thường.

## 8. Đổi DNS — cutover

1. Đăng nhập tài khoản P.A Vietnam (đã xác nhận truy cập được ở mục 1).
2. Vào quản lý DNS của domain `phucthinhsolar.com`, tìm bản ghi **A** đang trỏ
   tới `103.82.21.26` (host cũ), sửa thành **IP của host mới**.
3. Nếu có bản ghi **CNAME** cho `www` hoặc subdomain khác, kiểm tra và cập
   nhật tương ứng.
4. Giảm TTL xuống thấp (vd 300 giây) TRƯỚC khi đổi nếu muốn thời gian chuyển
   đổi nhanh hơn — nếu chưa kịp giảm trước, việc đổi vẫn chạy được, chỉ là
   thời gian lan truyền lâu hơn (có thể vài giờ thay vì vài phút).
5. Theo dõi bằng cách gõ trực tiếp domain trên nhiều mạng khác nhau (wifi nhà,
   4G điện thoại) trong 1-2 giờ sau khi đổi, xác nhận đã trỏ đúng host mới.

## 9. Sau khi cutover

1. Theo dõi site 24-48 giờ đầu: kiểm tra đơn hàng mới, form liên hệ mới có
   vào đúng không, email gửi đi có nhận được không.
2. Sau khi chắc chắn ổn định (3-7 ngày), mới cân nhắc huỷ/ngừng gia hạn hosting
   cũ — đừng huỷ ngay lập tức, giữ lại một thời gian đề phòng có dữ liệu quên
   backup.
3. Đổi lại toàn bộ mật khẩu liên quan tới host cũ (FTP, DirectAdmin, database
   password cũ) — dù không dùng nữa, đổi mật khẩu để bên cũ (nếu từng biết
   thông tin đó) không còn dùng được vào việc gì.

## 10. Nếu bị khoá quyền truy cập ĐỘT NGỘT trước khi kịp backup

Nếu phát hiện FTP/DirectAdmin đã bị đổi mật khẩu hoặc khoá trước khi kịp làm
mục 3:

1. **Domain và DNS không bị ảnh hưởng** (đã xác nhận ở mục 0) — bình tĩnh,
   website sẽ ngừng hoạt động (site cũ không truy cập được) nhưng domain vẫn
   là của anh, không mất domain.
2. Liên hệ ngay nhà cung cấp hosting (không phải agency/dev, mà thẳng công ty
   hosting nếu biết tên) yêu cầu khôi phục quyền truy cập với tư cách chủ tài
   khoản — cần giấy tờ/hợp đồng đứng tên công ty nếu có.
3. Nếu hoàn toàn không lấy lại được quyền truy cập hosting cũ và không có bản
   backup nào: nội dung website (giao diện, code) có thể dựng lại từ GitHub
   (branch `feature/ecommerce-upgrade` đã đồng bộ đầy đủ code tới ngày viết
   tài liệu này), nhưng **dữ liệu database** (sản phẩm, đơn hàng, khách hàng
   nhập tay qua admin) và **ảnh đã upload qua admin** sẽ mất phần chưa kịp
   backup — đây là lý do mục 1-3 phải làm ngay hôm nay, không đợi.
4. Trỏ DNS (mục 8) sang một host tạm bất kỳ đã dựng sẵn code từ GitHub, để
   site không "chết" hoàn toàn trong lúc xử lý tranh chấp với bên host cũ.

## 11. Danh sách đầy đủ những gì cần thiết lập/kiểm tra lại sau khi khôi phục lên host mới

Bổ sung ngoài mục 6, gộp mọi thứ đặc thù của site này cần soát lại — tránh sót
việc tưởng "chỉ cần copy code + DB là xong".

### 11.1. Email — phát hiện quan trọng, khác với domain/DNS

Kiểm tra MX record ngày 2026-09-12: `phucthinhsolar.com` dùng
`mail.phucthinhsolar.com` để **nhận** email, và bản ghi này trỏ **thẳng vào
server hosting hiện tại** (không tách riêng như domain/DNS ở mục 0). Trên
server có ít nhất 1 mailbox thật: `website@phucthinhsolar.com` (dùng để hệ
thống tự gửi mail thông báo — form liên hệ, đơn hàng — tới
`phucthinhsolar.hr@gmail.com`).

**Nghĩa là**: nếu chỉ chuyển web hosting mà không xử lý email, sau khi đổi DNS
sang host mới thì `website@phucthinhsolar.com` sẽ **ngừng gửi được mail** (vì
SMTP xác thực qua chính mailbox này), trừ khi:

- [ ] Kiểm tra lại xem ngoài `website@` có mailbox nào khác đang được nhân sự
      thật sự dùng để nhận email hàng ngày không (hỏi trực tiếp nhân sự, đừng
      suy đoán từ mỗi lần kiểm tra FTP).
- [ ] Nếu có host mới hỗ trợ email (hầu hết hosting cPanel/DirectAdmin đều có)
      → tạo lại đúng mailbox `website@phucthinhsolar.com` (và mailbox khác nếu
      có) trên host mới, cập nhật MX record trỏ sang `mail` của host mới.
- [ ] Nếu muốn tách hẳn email khỏi hosting (khuyến nghị để giảm rủi ro tương
      tự về sau) → chuyển sang Google Workspace/Zoho Mail rồi trỏ MX sang đó,
      độc lập hoàn toàn với web hosting.
- [ ] Sau khi có mailbox mới, cập nhật lại nội dung file `~/.pts-mail.php`
      trên host mới (host SMTP, username, password) khớp với nơi mailbox thật
      sự đang chạy.

### 11.2. Những thứ KHÔNG cần đổi khi chỉ đổi hosting (domain giữ nguyên)

- **reCAPTCHA** (`libraries/config.php` → `googleAPI.recaptcha`): site
  key/secret key gắn với *domain* trên Google Console, không gắn với server —
  giữ nguyên domain thì không cần tạo lại. (Hiện đang `active => false`, chưa
  bật, nên tạm thời không phải lo.)
- **Sitemap/robots.txt**: `robots.txt` có domain hardcode
  (`https://phucthinhsolar.com/sitemap.xml`) nhưng đó là domain, không phải
  IP/host — không cần sửa khi đổi hosting.
- **Cron job định kỳ**: đã kiểm tra, crontab hiện tại **rỗng** — không có tác
  vụ định kỳ nào cần dựng lại trên host mới.

### 11.3. SSL

Không "chuyển" chứng chỉ SSL cũ sang — host mới tự cấp chứng chỉ Let's Encrypt
mới cho domain (miễn phí, tự động ở hầu hết hosting panel hiện đại). Chỉ cần
đảm bảo bật HTTPS-only sau khi domain đã trỏ đúng sang host mới.

### 11.4. Bảng tổng hợp nhanh

| Việc | Có cần làm khi đổi host? |
|---|---|
| DNS A record | **Có** — trỏ sang IP mới (mục 8) |
| MX record (email) | **Có, nếu email đang nhận trên host cũ** (mục 11.1) |
| reCAPTCHA site/secret key | Không — gắn domain, không gắn server |
| SSL certificate | Không cần chuyển — host mới tự cấp mới |
| Cron job | Không — hiện không có cron nào |
| Sitemap/robots.txt | Không — domain không đổi |
| `libraries/config.php` (DB creds) | **Có** — sửa lại theo DB mới (mục 6) |
| `~/.pts-mail.php` (SMTP) | **Có, nếu email cũng chuyển** (mục 11.1) |
| CKFinder license | Kiểm tra lại còn hạn dùng cho domain hiện tại không |

## Checklist tổng hợp (in ra hoặc copy sang ghi chú riêng)

- [ ] Xác nhận tự đăng nhập được tài khoản P.A Vietnam bằng email của mình
- [ ] Backup file `public_html/` (trừ caches/thumbs/watermark/logs)
- [ ] Backup riêng `upload/`
- [ ] Backup riêng `libraries/config.php`
- [ ] Backup riêng `~/.pts-mail.php`
- [ ] Export database qua phpMyAdmin
- [ ] Backup toàn tài khoản qua DirectAdmin (nếu còn quyền)
- [ ] Ghi lại toàn bộ thông tin đăng nhập hiện có, lưu ngoài Git
- [ ] Host mới đáp ứng đủ bảng yêu cầu kỹ thuật (mục 4)
- [ ] Khôi phục xong code + config + database + upload lên host mới
- [ ] Test đầy đủ qua file hosts trước khi đổi DNS thật
- [ ] Đổi bản ghi A trên P.A Vietnam
- [ ] Xác nhận có mailbox nào đang thật sự dùng nhận email hàng ngày ngoài `website@` không (mục 11.1)
- [ ] Tạo lại mailbox trên host mới hoặc chuyển email sang Google Workspace/Zoho Mail
- [ ] Đổi bản ghi MX sang nơi email thật sự chạy
- [ ] Cập nhật lại `~/.pts-mail.php` khớp mailbox mới
- [ ] Theo dõi 24-48h sau cutover
- [ ] Đổi mật khẩu host cũ sau khi ổn định
