# Cutover DNS và xử lý host cũ — hướng dẫn từng bước

Cập nhật: 2026-09-17. Mọi số liệu dưới đây **đo thật** từ DNS công khai
(`dig @8.8.8.8`) và từ FTP host cũ, không lấy từ giao diện quản trị.

Đọc kèm [vps-cloudfly-runbook.md](vps-cloudfly-runbook.md) mục 11 và
[handoff 2026-09-17](../13-handoffs/2026-09-17-vps-migration-state.md).

---

## 0. Việc đang CHẶN mọi thứ — phải làm trước

P.A Vietnam đang hiện cảnh báo:

> Tên miền hiện tại chưa xác thực Email nên không thay đổi DNS được. Vui lòng
> kiểm tra hộp thư email chủ thể tên miền để xác thực.

Đây là lý do nút **"Lưu cấu hình" bị mờ trên cả 4 trang** (bản ghi tên miền,
thay đổi DNS, DNS con phụ, email chuyển tiếp). **Chưa xác thực thì không thêm
được bản ghi nào, kể cả `staging`.**

Đây là yêu cầu bắt buộc của ICANN với tên miền `.com`: email của chủ thể đăng ký
phải được xác nhận. Việc cần làm:

1. Mở hộp thư **email chủ thể tên miền** đã khai với P.A (không phải email đăng
   nhập vào `access.pavietnam.vn` nếu hai cái khác nhau).
2. Tìm thư xác thực từ P.A Vietnam / registrar, bấm link trong đó. Kiểm tra cả
   thư mục Spam/Quảng cáo.
3. Nếu không tìm thấy thư: gọi P.A **028 22317777** hoặc `info@pavietnam.vn`,
   yêu cầu **gửi lại thư xác thực chủ thể** cho `phucthinhsolar.com`. Đồng thời
   hỏi luôn email chủ thể đang là địa chỉ nào — nếu nó là email cũ mà anh không
   còn truy cập được (ví dụ email do công ty web cũ đặt), phải yêu cầu **đổi
   email chủ thể** trước, việc này cần giấy tờ doanh nghiệp.
4. Tải lại trang DNS, xác nhận cảnh báo vàng đã mất và nút "Lưu cấu hình" sáng lên.

**Kiểm tra trước điều này:** email chủ thể có phải địa chỉ anh đang kiểm soát
không. Nếu nó thuộc công ty web cũ thì đây là việc gấp nhất trong toàn bộ dự án —
quan trọng hơn cả website, vì ai kiểm soát email chủ thể thì có đường tác động
tới tên miền.

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

### Tin tốt: hai việc này TÁCH ĐƯỢC ra hai thời điểm

Đây là điểm quan trọng, nó gỡ email ra khỏi đường găng của cutover.

`mail.phucthinhsolar.com:587` là **SMTP có xác thực, truy cập được qua
Internet** — không phải chỉ dùng được từ bên trong host cũ. Nên VPS mới hoàn
toàn có thể dùng nó làm relay, miễn host cũ còn sống.

| Thời điểm | Cần gì |
|---|---|
| **Cutover website** | Chỉ cần điền SMTP hiện có (`mail.phucthinhsolar.com:587`, `website@phucthinhsolar.com`) vào `secrets/pts_mail_config.php` trên VPS. Host cũ vẫn sống nên mail vẫn gửi bình thường. **Không cần giải quyết email trong ngày cutover.** |
| **Huỷ host cũ** | Lúc này mới bắt buộc phải chuyển sang nhà cung cấp SMTP mới. |

SPF vẫn đạt trong giai đoạn bắc cầu: thư được **relay qua**
`mail.phucthinhsolar.com`, nên IP mà Gmail nhìn thấy là IP host cũ, khớp đúng
`mx` trong `v=spf1 a mx ~all`. Không cần sửa SPF ở bước này.

Cách điền secret **mà không để mật khẩu đi qua chat** — anh tự chạy dòng này,
thay `MAT_KHAU_THAT`:

```bash
ssh -i ~/.ssh/id_ed25519 root@222.255.182.182 \
  "cat > /srv/phucthinhsolar/secrets/pts_mail_config.php <<'PHP'
<?php
return array(
    'host'       => 'mail.phucthinhsolar.com',
    'port'       => 587,
    'encryption' => 'tls',
    'username'   => 'website@phucthinhsolar.com',
    'password'   => 'MAT_KHAU_THAT',
    'from'       => 'website@phucthinhsolar.com',
    'admin_to'   => 'phucthinhsolar.hr@gmail.com',
);
PHP
chown 0:33 /srv/phucthinhsolar/secrets/pts_mail_config.php
chmod 0640 /srv/phucthinhsolar/secrets/pts_mail_config.php
cd /srv/phucthinhsolar && docker compose restart web"
```

Hai dòng `chown`/`chmod` là bắt buộc — xem [vps-operations § 6](vps-operations.md):
để `0600 root` thì `www-data` không đọc được và **toàn bộ trang PHP trả 500**.

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

### Giai đoạn A: mở đường (làm ngay)

1. **Xác thực email chủ thể tên miền** (mục 0). Chưa xong thì không làm được gì tiếp.
2. **Hạ TTL xuống 300** cho `@`, `www`, `*`, `mail`. Chưa đổi giá trị, chỉ đổi TTL.
   Chờ ít nhất 1 giờ (một chu kỳ TTL cũ) để thay đổi lan hết.
3. **Thêm bản ghi staging:**
   ```
   Host: staging     Loại: A     Giá trị: 222.255.182.182     TTL: 300
   ```
   Không đổi nameserver. Không sửa bản ghi `@`, `www`, `*`.

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
| Không còn fatal/warning trên trang public | **xanh** — kiểm 9 trang |
| `upload/` không thực thi được script | **xanh** — trả 403 |
| Preflight migration READY | **xanh** — 17/17 migration |
| Email chủ thể tên miền đã xác thực | **đỏ** — đang chặn mọi thay đổi DNS |
| Đăng nhập Admin được trên staging | chưa kiểm — cần DNS |
| SMTP gửi thật được từ VPS, không vào spam | **đỏ** — `pts_mail_config.php` còn là stub 22 byte. Bắc cầu qua SMTP host cũ là đủ cho cutover (mục 2) |
| Backup off-site đã restore thử thành công | **đỏ** — chưa có off-site |
| Có đường rollback | **xanh** — đổi A record, giữ host cũ 7 ngày |
