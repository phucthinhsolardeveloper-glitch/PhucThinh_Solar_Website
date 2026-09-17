# Cutover DNS và xử lý host cũ — hướng dẫn từng bước

Cập nhật: 2026-09-17. Mọi số liệu dưới đây **đo thật** từ DNS công khai
(`dig @8.8.8.8`) và từ FTP host cũ, không lấy từ giao diện quản trị.

Đọc kèm [vps-cloudfly-runbook.md](vps-cloudfly-runbook.md) mục 11 và
[handoff 2026-09-17](../13-handoffs/2026-09-17-vps-migration-state.md).

---

## 0. Cảnh báo "chưa xác thực Email" ở P.A — nó chặn cái gì

P.A Vietnam hiện cảnh báo trên trang **"Thay đổi DNS"**:

> Tên miền hiện tại chưa xác thực Email nên không thay đổi DNS được. Vui lòng
> kiểm tra hộp thư email chủ thể tên miền để xác thực.

**Nó KHÔNG chặn việc thêm/sửa bản ghi DNS.** Đã kiểm bằng thực tế ngày
2026-09-17: chủ project thêm `staging` A `222.255.182.182` TTL 300 và hạ TTL của
`@`/`www` về 300 — **lưu thành công**, `dig @ns1.dotvndns.vn` xác nhận đã áp
dụng. Nút "Lưu cấu hình" trông mờ chỉ là kiểu hiển thị của giao diện, không phải
bị vô hiệu.

Trang "Thay đổi DNS" trong giao diện P.A là nơi **đổi nameserver**, khác với
trang "Cấu hình bản ghi tên miền". Nên cảnh báo đó giới hạn ở việc đổi
nameserver.

Với dự án này, **không cần đổi nameserver**: DNS đang được quản lý tại P.A
(`ns1.dotvndns.vn`, `ns2.dotvndns.vn`, `nsbak.dotvndns.com`) và cutover chỉ cần
sửa bản ghi A. Nên cảnh báo này **không chặn cutover**.

### Nhưng vẫn nên xử lý, vì lý do khác

Việc cần kiểm tra là: **email chủ thể tên miền có phải địa chỉ anh đang kiểm
soát không.** Nếu nó là email do công ty web cũ đặt thì đây là việc gấp, độc lập
với cutover — ai kiểm soát email chủ thể thì có đường tác động tới tên miền, và
tên miền là tài sản duy nhất mất là không lấy lại được.

1. Gọi P.A **028 22317777** hoặc `info@pavietnam.vn`, hỏi **email chủ thể của
   `phucthinhsolar.com` đang là địa chỉ nào**.
2. Nếu là địa chỉ của anh: mở hộp thư đó, tìm thư xác thực (xem cả Spam), bấm
   link. Xong thì cảnh báo mất và cũng mở lại được quyền đổi nameserver nếu sau
   này cần.
3. Nếu **không** phải địa chỉ của anh: yêu cầu đổi email chủ thể. Việc này cần
   giấy tờ doanh nghiệp, làm sớm.

---

## 1. Trạng thái DNS hiện tại (đo 2026-09-17)

```text
phucthinhsolar.com.          3600  A    103.82.21.26     ← host cũ
www                          3600  A    103.82.21.26     ← host cũ
*                            3600  A    103.82.21.26     ← WILDCARD, host cũ
mail                         3600  A    103.82.21.26     ← host cũ
ftp                          3600  A    103.82.21.26     ← host cũ
@                            3600  MX   10 mail.phucthinhsolar.com
@                            3600  TXT  "v=spf1 a mx ~all"
NS: ns1.dotvndns.vn, ns2.dotvndns.vn, nsbak.dotvndns.com
```

### Hai điều dễ bỏ sót

**1. Có bản ghi wildcard `*`.** Nên `staging.phucthinhsolar.com` **đã phân giải
rồi** — về host cũ. Kiểm chứng: một tên bất kỳ như
`batky123xyz.phucthinhsolar.com` cũng trả `103.82.21.26`.

Hệ quả: khi thêm bản ghi `staging` cụ thể, nó thắng wildcard (bản ghi cụ thể ưu
tiên hơn), nên vẫn hoạt động. Nhưng **khi cutover phải đổi cả `*`**, nếu không
mọi tên miền con vẫn đi về host cũ.

**2. TTL đang là 3600 (1 giờ).** Nghĩa là sau khi đổi bản ghi, các DNS resolver
trên thế giới còn giữ giá trị cũ tới 1 giờ. Nếu phải quay lại host cũ gấp thì
cũng chậm đúng 1 giờ. Phải hạ xuống 300 **trước** ít nhất một chu kỳ TTL cũ.

---

## 2. Host cũ đang giữ những gì

Không chỉ website. Đo từ FTP:

| Thứ | Trạng thái | Mất nếu huỷ host cũ |
|---|---|---|
| File website | đã có bản sao ở Git + VPS | không mất |
| Database | đã restore lên VPS (54 bảng) | không mất |
| `upload/` | đã copy lên VPS (1.125 file) | không mất |
| Hộp thư `website@phucthinhsolar.com` | gần rỗng, tạo 07/09/2026 | gần như không có gì để mất |
| **Máy chủ SMTP mà website dùng để GỬI mail** | `mail.phucthinhsolar.com:587` → chính host cũ | **MẤT — form liên hệ sẽ ngừng gửi** |

### Đây là rủi ro thật, không phải giả định

`docs/08-integrations/smtp-production.md` ghi rõ website gửi mail qua:

```text
SMTP_HOST=mail.phucthinhsolar.com   ← A record trỏ về 103.82.21.26 = host cũ
SMTP_PORT=587  (STARTTLS)
SMTP_USERNAME=website@phucthinhsolar.com
MAIL_ADMIN_TO=phucthinhsolar.hr@gmail.com
```

Các luồng phụ thuộc vào nó: form liên hệ (gửi admin + xác nhận khách), yêu cầu
tư vấn, đăng ký đại lý, đơn hàng, kích hoạt/reset mật khẩu thành viên, newsletter.

Nếu huỷ host cũ mà chưa chuyển SMTP: **mọi form trên website sẽ im lặng không
gửi được mail** — khách điền form, anh không nhận được gì. Dữ liệu vẫn vào DB
nhưng không ai biết để xử lý.

Điểm may: địa chỉ nhận thông báo là `phucthinhsolar.hr@gmail.com` (Gmail), không
nằm trên host cũ. Nên **thư đã nhận không mất**, chỉ mất khả năng gửi.

### VPS mới không có DirectAdmin — và đó là chủ ý

Hộp thư `website@phucthinhsolar.com` được tạo trong **DirectAdmin của host cũ**.
VPS mới là stack Docker thuần: **không có DirectAdmin, không có mail server**.
Đo thật ngày 2026-09-17: không có port 25/465/587/993/143 nào mở, và
`secrets/pts_mail_config.php` chỉ là stub **22 byte, không có khoá SMTP nào**.

Nên trên VPS hiện tại, website **chưa gửi được mail**.

### SỬA LẠI 2026-09-17: cách "bắc cầu" qua SMTP host cũ KHÔNG dùng được

Trước đó mục này đề xuất: cứ điền SMTP hiện có của host cũ vào VPS, coi như
xong email cho tới lúc huỷ host cũ. **Đề xuất đó sai.** Đã thử thật và thất bại,
lý do:

Máy chủ mail của host cũ dùng certificate **tự ký `CN=localhost`** (snakeoil mặc
định của Exim), trong khi app bắt buộc `verify_peer`/`verify_peer_name` và
không cho gửi không mã hoá. Đo từ VPS: `connect()` OK, `startTLS()` **thất bại**.

Đồng thời phát hiện: **mail trên production hiện cũng KHÔNG gửi được** — file
`~/.pts-mail.php` chưa bao giờ được tạo, nên mọi lần gửi bị chặn ngay ở bước
kiểm cấu hình. Chi tiết đầy đủ và bằng chứng:
[smtp-production.md](../08-integrations/smtp-production.md) mục 2026-09-17.

Hệ quả cho kế hoạch: **không có bước bắc cầu.** Phải chuyển sang một SMTP relay
có certificate hợp lệ — và việc đó sửa luôn cả mail production đang hỏng, nên
làm một lần được cả hai. Không cần làm trong ngày cutover, nhưng nên làm sớm vì
production đang không gửi được mail.

Dữ liệu không mất trong lúc chưa sửa: form ghi vào `table_contact` **trước** khi
gửi mail, nên lead vẫn nằm trong DB, chỉ là không ai được thông báo — phải vào
Admin mới thấy.

### Chuyển SMTP đi đâu (cho lúc huỷ host cũ)

**Không** dựng mail server trên VPS mới. IP mới không có lịch sử uy tín, thư sẽ
vào spam, và vận hành mail server là gánh nặng riêng.

| Cách | Chi phí | Nhận xét |
|---|---|---|
| **Brevo** (Sendinblue) | miễn phí 300 thư/ngày | **Đề xuất.** Quá đủ cho form liên hệ, giữ được From `website@phucthinhsolar.com`, có DKIM |
| Google Workspace | ~$6/người/tháng | Hợp nếu anh muốn hộp thư `@phucthinhsolar.com` thật để dùng hằng ngày |
| Zoho Mail | có bậc miễn phí | Có cả hộp thư và SMTP |
| Email hosting P.A | trả phí | Cùng nhà với tên miền, tiện quản lý một chỗ |

Sau khi chọn, **phải cập nhật SPF**. Hiện là `v=spf1 a mx ~all` — chỉ cho phép
gửi từ A record và MX host của chính domain. Dùng relay ngoài thì phải thêm
`include`, ví dụ với Brevo: `v=spf1 a mx include:spf.brevo.com ~all`. Thiếu bước
này thì thư gửi được nhưng vào spam.

---

## 3. Thứ tự làm — không đảo

### Giai đoạn A: mở đường — ĐÃ XONG 2026-09-17

1. ~~Hạ TTL cho `@`, `www`~~ → **xong**, cả hai đã về 300.
2. ~~Thêm bản ghi `staging` A `222.255.182.182` TTL 300~~ → **xong**, đã phân
   giải đúng và certificate Let's Encrypt đã cấp.
3. **Còn lại: hạ TTL của `*` từ 3600 về 300.** Bắt buộc trước cutover vì khi
   cutover phải đổi cả bản ghi wildcard; để 3600 thì rollback chậm 1 giờ.
   Không đổi nameserver, không sửa giá trị của `@`, `www`, `*` ở bước này.

### Giai đoạn B: kiểm thử trên staging (chưa ảnh hưởng khách)

4. Xác nhận Caddy lấy được certificate Let's Encrypt thật cho
   `staging.phucthinhsolar.com`.
5. **Anh tự đăng nhập Admin trên staging bằng mật khẩu hiện tại.** Đây là bằng
   chứng **duy nhất** rằng `app_secret`/`app_salt` đã lấy đúng từ host cũ — mật
   khẩu được hash bằng `md5(secret + password + salt)`. Nếu sai thì **toàn bộ
   mật khẩu hiện có không đăng nhập được**, và phải biết trước cutover.
6. Smoke test đầy đủ theo [runbook mục 10](vps-cloudfly-runbook.md).
7. **Điền SMTP hiện có vào VPS** (đường bắc cầu ở mục 2) rồi gửi thử thật từ
   staging: form liên hệ, đăng ký đại lý. Kiểm tra thư về đúng
   `phucthinhsolar.hr@gmail.com` và **không vào spam**.
   Chưa cần nhà cung cấp SMTP mới ở bước này.

### Giai đoạn C: backup phải xanh trước khi cutover

8. Tạo off-site backup ở nhà cung cấp khác CloudFly, rồi **tải một bản về và
   chạy `restore-check.sh`**. Backup chưa từng restore thử thì chưa tính là backup.

### Giai đoạn D: cutover (chọn giờ ít khách, không phải chiều thứ Sáu)

9. Chụp **backup cuối** trên host cũ: database + `upload/`. Ngừng nhập liệu trên
   Admin của host cũ từ lúc này.
10. **Đồng bộ delta cuối**: dữ liệu phát sinh giữa lúc restore (hôm nay) và lúc
    cutover. Nếu chủ project có nhập sản phẩm/bài viết trong khoảng đó thì phải
    lấy lại, nếu không sẽ mất.
11. Đổi bản ghi, **theo đúng thứ tự này**:
    ```
    @      A  →  222.255.182.182
    www    A  →  222.255.182.182
    *      A  →  222.255.182.182
    ```
    **Giữ nguyên `mail` A và MX trỏ về host cũ** cho tới khi SMTP mới chạy ổn.
    **Không** đổi nameserver cùng lúc với đổi bản ghi A — nếu có sự cố thì không
    biết nguyên nhân nằm ở đâu.
12. Đổi `SITE_DOMAIN` trong `/srv/phucthinhsolar/.env` thành `phucthinhsolar.com`,
    đặt `SITE_ALIASES=", www.phucthinhsolar.com"`, rồi `docker compose restart proxy`.
    Nhắc lại: **`up -d` không nạp lại config bind-mount, phải `restart`.**
13. Theo dõi 24–48 giờ: log web, đơn/form có về không, certificate, và kiểm tra
    từ **nhiều mạng khác nhau** (4G, wifi công ty, máy khác).

### Giai đoạn E: dọn host cũ — sớm nhất là sau 7 ngày

14. **Giữ host cũ tối thiểu 7 ngày** sau cutover, nguyên vẹn dữ liệu. Đây là
    đường quay lại duy nhất: đổi bản ghi A về `103.82.21.26` là xong.
15. Chuyển SMTP sang nhà cung cấp mới (lúc này mới bắt buộc, xem mục 2), cập
    nhật SPF thêm `include:` của nhà cung cấp, gửi thử. Chỉ khi thư về đúng và
    không vào spam mới đổi `mail` A và MX.
    Nếu anh muốn giữ hộp thư `@phucthinhsolar.com` để dùng hằng ngày thì đây là
    lúc chọn Google Workspace / Zoho / email hosting P.A — VPS sẽ không bao giờ
    có DirectAdmin để tạo hộp thư.
16. Tải toàn bộ backup host cũ về giữ **ngoài** cả hai nhà cung cấp.
17. Bây giờ mới xoá source/DB trên host cũ và huỷ dịch vụ. Xoá bản ghi `ftp`
    (không còn dùng). **Đổi mật khẩu FTP `phucthinhs`** — hiện vẫn là mật khẩu cũ
    vì còn cần để deploy.

Lưu ý về `creator=admin` trên host cũ: tài khoản cấp trên vẫn reset được mật khẩu
của `phucthinhs` bất kể anh đổi gì. Nên đổi mật khẩu **không** phải biện pháp
bảo vệ thật; chuyển hẳn sang host mới mới là.

---

## 4. Nếu cần quay lại host cũ

Trong 7 ngày đầu, chỉ cần đổi 3 bản ghi về `103.82.21.26`. Với TTL 300 thì lan
trong khoảng 5 phút.

**Tuyệt đối không để hai host cùng nhận đơn/form.** Nếu quay lại, phải chắc
website trên VPS không còn nhận traffic — dừng stack hoặc đổi `SITE_DOMAIN` về
staging — nếu không dữ liệu sẽ chia đôi giữa hai database và không hợp nhất được.

---

## 5. Bảng kiểm trước khi bấm cutover

| Điều kiện | Trạng thái 2026-09-17 |
|---|---|
| Không còn fatal/warning trên trang public | **xanh** — kiểm 11 trang qua HTTPS thật |
| `upload/` không thực thi được script | **xanh** — trả 403 |
| Preflight migration READY | **xanh** — 17/17 migration |
| Bản ghi DNS sửa được | **xanh** — đã lưu và `dig` xác nhận |
| DNS staging trỏ về VPS, TTL 300 | **xanh** — `dig` xác nhận |
| Certificate HTTPS thật | **xanh** — Let's Encrypt, hạn 16/12/2026 |
| Không có mixed content | **xanh** — 0 request `http://` |
| Chặn index bản staging | **xanh** — `X-Robots-Tag` + `robots.txt Disallow: /` |
| Đăng nhập Admin được trên staging | **chờ chủ project tự thử** — xem mục 6 |
| SMTP gửi thật được từ VPS, không vào spam | **đỏ** — `pts_mail_config.php` còn là stub 22 byte. Bắc cầu qua SMTP host cũ là đủ cho cutover (mục 2) |
| Backup off-site đã restore thử thành công | **đỏ** — chưa có off-site |
| Có đường rollback | **xanh** — đổi A record, giữ host cũ 7 ngày |


---

## 6. Việc chỉ chủ project làm được: thử đăng nhập Admin

Địa chỉ: **https://staging.phucthinhsolar.com/administrator/**

Đăng nhập bằng **đúng tài khoản và mật khẩu đang dùng trên host cũ**.

Vì sao chỉ mục này mới kiểm được điều đó: mật khẩu Admin được hash bằng
`md5(secret + password + salt)`, và `secret`/`salt` nằm trong
`/srv/phucthinhsolar/secrets/`. Không có cách nào xác minh hai giá trị đó đúng
ngoài việc **đăng nhập thật bằng một mật khẩu có sẵn**. Nếu chúng sai thì toàn
bộ mật khẩu hiện có sẽ không dùng được sau cutover.

Cũng vì lý do đó, người triển khai không nên biết mật khẩu của chủ project —
nên đây là việc chủ project tự làm.

Đã kiểm sẵn phần hạ tầng cho bước này:

- Trang đăng nhập trả HTTP 200, có form `username`/`password`.
- Cookie phiên đã có `secure; HttpOnly; SameSite=Lax` — nên **chỉ đăng nhập
  được qua HTTPS**, đây là lý do phải có staging chứ không thể test qua HTTP.

### Kết quả cần báo lại

| Kết quả | Ý nghĩa | Làm gì tiếp |
|---|---|---|
| Vào được | `app_secret`/`app_salt` đúng | Tiếp tục smoke test phần Admin |
| Sai mật khẩu | Hai giá trị đó **lấy sai** | Dừng. Phải lấy lại đúng từ `libraries/config.php` của host cũ trước khi tính cutover |
| Lỗi 500 | Vấn đề khác | Đọc `docker compose logs web`, xem [vps-operations § 7](vps-operations.md) |

Sau khi vào được, kiểm thêm trong Admin: sửa một nội dung rồi xem trang public
đổi đúng, upload một ảnh xem thumbnail sinh ra, và kiểm quyền của một tài khoản
nghiệp vụ (không phải superadmin).

**Lưu ý:** staging dùng **bản database đã restore sáng 2026-09-17**. Mọi thay
đổi anh nhập trên staging **không** tự về host cũ, và sẽ bị ghi đè nếu tôi
restore lại. Nên chỉ nhập dữ liệu thử, đừng nhập nội dung thật vào staging.

---

## 7. Ghi nhận thêm — chưa chặn cutover

- **Form đăng nhập Admin không có CSRF token.** `libraries/form_security.php`
  có sẵn helper CSRF nhưng form login không dùng, và không có kiểm
  `Origin`/`Referer`. Mức độ thấp (kẻ tấn công chỉ ép được nạn nhân đăng nhập
  vào tài khoản của kẻ tấn công, không leo thang quyền), nhưng nên xử lý thành
  một đợt riêng. Cơ chế lockout theo IP vẫn đang hoạt động.
- **TTL của `*`, `mail`, `ftp` vẫn là 3600.** Phải hạ `*` xuống 300 trước
  cutover, vì khi cutover phải đổi cả bản ghi wildcard. `mail` và `ftp` giữ
  nguyên cũng được vì chúng vẫn trỏ host cũ sau cutover.
