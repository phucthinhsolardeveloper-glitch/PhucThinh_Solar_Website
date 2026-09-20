# Sổ tay Phúc Thịnh Solar — đọc vào là làm được

Cập nhật: 2026-09-20.

Tài liệu này là **cửa vào duy nhất** khi bắt đầu một phiên làm việc mới, cho
người thật hoặc cho AI vừa mất hết context. Nó không giải thích chi tiết — nó
nói **việc bạn cần làm nằm ở tài liệu nào**, và nói trước những cái bẫy khiến
người mới mất hàng giờ.

Khác biệt với `docs/README.md`: file đó liệt kê tài liệu theo thư mục. File này
liệt kê theo **việc cần làm**.

---

## 0. Ba câu phải biết trước khi gõ dòng lệnh đầu tiên

1. **Website thật đang chạy trên VPS** `222.255.182.182` (Ubuntu 24.04,
   Docker/Caddy). DNS `phucthinhsolar.com` đã cutover; deploy production bằng
   SAFE/FAST một lệnh, không còn upload FTP vào host cũ.
2. **Host VNPT cũ không còn phục vụ website/database.** Chỉ còn tài nguyên mail
   trong giai đoạn chuyển tiếp; không xem FTP/phpMyAdmin cũ là production.
3. **Không suy đoán.** Khi runtime, code, schema và docs mâu thuẫn, ghi rõ xung
   đột và tin bằng chứng có độ tin cậy cao hơn — runtime > code > schema > docs.
   Đây là quy tắc trong `CLAUDE.md`, không phải lời khuyên.

---

## 1. Tôi cần làm X → đọc gì

| Việc cần làm | Đọc |
|---|---|
| Dựng môi trường local để chạy thử | [02-development/local-setup.md](02-development/local-setup.md) |
| Hiểu một URL chạy qua những file nào | [01-architecture/codebase-guide.md](01-architecture/codebase-guide.md) |
| Tìm code của một trang cụ thể | [01-architecture/codebase-guide.md § Tra cứu nhanh](01-architecture/codebase-guide.md) |
| Sửa giao diện / thêm CSS mà không làm vỡ trang khác | [02-development/frontend-guide.md](02-development/frontend-guide.md) |
| Làm một nội dung sửa được ở Admin | [07-features/admin-guide.md](07-features/admin-guide.md) |
| Hiểu bảng nào chứa gì, đổi schema thế nào | [03-data/database.md](03-data/database.md) |
| Sửa một con bug | [11-workflows/bug-fixing-playbook.md](11-workflows/bug-fixing-playbook.md) |
| Làm một tính năng mới từ đầu tới deploy | [11-workflows/feature-delivery-playbook.md](11-workflows/feature-delivery-playbook.md) |
| Chạy test / cổng kiểm tra trước deploy | [02-development/testing.md](02-development/testing.md) |
| Deploy production SAFE/FAST một lệnh | [06-operations/deploy-production-owner.md](06-operations/deploy-production-owner.md) |
| Dựng VPS mới từ số không | [06-operations/vps-cloudfly-runbook.md](06-operations/vps-cloudfly-runbook.md) |
| VPS đang chạy mà có vấn đề | [06-operations/vps-operations.md](06-operations/vps-operations.md) |
| Backup / restore / kiểm tra backup còn dùng được | [06-operations/backup-restore.md](06-operations/backup-restore.md) |
| Phân quyền, tài khoản Admin, đăng nhập | [05-security/auth-rbac.md](05-security/auth-rbac.md) |
| Giá sản phẩm, chế độ ẩn giá, báo giá | [03-data/product-pricing-invariant.md](03-data/product-pricing-invariant.md), [07-features/public-price-mode.md](07-features/public-price-mode.md) |
| Feed Google Shopping / structured data | [10-audits/2026-09-16-merchant-feed-and-product-schema-audit.md](10-audits/2026-09-16-merchant-feed-and-product-schema-audit.md) |
| Gửi mail / SMTP | [08-integrations/smtp-production.md](08-integrations/smtp-production.md) |
| Quyết định thiết kế đã chốt, đừng làm trái | [09-decisions/](09-decisions/) |
| Trạng thái hiện tại của project | [00-project/status.md](00-project/status.md) |

---

## 2. Bản đồ hệ thống trong 20 dòng

```text
Trình duyệt
  │
  ├─ .htaccess  (rewrite mọi URL về index.php)
  │
  └─ public_html/index.php
       ├─ libraries/config.php        ← credential, KHÔNG nằm trong git
       ├─ libraries/autoload.php      ← nạp libraries/class/*
       ├─ libraries/config-type.php   ← nạp libraries/type/config-type-*.php
       └─ libraries/router.php        ← switch($com) đặt $source và $template
            │
            ├─ sources/<source>.php       ← query DB, dựng biến cho view
            └─ templates/index.php        ← layout chung
                 └─ templates/<template>_tpl.php   ← nội dung trang
```

Ba thứ cần nhớ:

- **`$com`** = đoạn đầu của URL (`/san-pham/...` → `$com = 'san-pham'`).
- **`$source`** = file logic trong `sources/`, nó chỉ *chuẩn bị dữ liệu*.
- **`$template`** = đường dẫn view, `templates/index.php` sẽ include
  `TEMPLATE.$template."_tpl.php"`.

Admin là một ứng dụng riêng cùng DB: `administrator/index.php` →
`libraries/requick.php` (auth + RBAC) → `administrator/sources/<com>.php`.

AJAX là các file độc lập trong `api/`, mỗi file tự bootstrap qua `api/config.php`.
Không có API router chung.

---

## 3. Những cái bẫy đã làm mất thời gian thật

Đây là danh sách rút ra từ lỗi thực tế, không phải lý thuyết. Đọc trước khi debug.

| Triệu chứng | Nguyên nhân thật |
|---|---|
| Xóa `caches/` trên host mà trang vẫn cũ | Local: `caches/` là **Docker named volume**, xóa trên host không tác dụng — phải `docker exec` vào container. Xem [local-setup.md](02-development/local-setup.md) |
| Ảnh trong carousel trắng trơn, `src` trả HTTP 200 nhưng `naturalWidth=0` | Slider dùng `transform` đẩy slide ra ngoài viewport → trình duyệt hoãn `loading="lazy"` vô thời hạn. Ảnh trong carousel phải truyền `$pcInCarousel` |
| Cột trong grid phình ra ngoài container (651px trong 311px) | `1fr` mặc định `min-width:auto`. Phải `minmax(0,1fr)` + `min-width:0` |
| Rail thiếu ô thì thụt hết về một bên | `justify-content:center` trong vùng scroll sẽ cắt ô đầu. Dùng `safe center` |
| Click nút mà không có gì xảy ra, dù JS `.click()` chạy được | Có element vô hình phủ lên trên. `pointer-events` + `!important` cũ. Phải click bằng chuột thật để phát hiện |
| Anchor link nhảy sai chỗ | Header sticky 154px, phải đặt `scroll-margin-top` cho target |
| Test Playwright timeout 30s ngẫu nhiên ở chỗ click | Popup chào mừng bung lên **sau** khi apps.js chạy → check-then-dismiss bị race. Phải chặn tất định bằng `sessionStorage` override |
| `ERR_CONNECTION_REFUSED` / `MySQL server has gone away` giữa lúc chạy test | Container WSL tự restart. **Kiểm tra uptime container trước khi nghi code** |
| `git commit -m` với tiếng Việt báo `pathspec ... did not match` | Shell ăn dấu. Dùng `git commit -F -` với heredoc |
| Màu trên trang "cứ lệch lệch" | Từng hub tự định nghĩa bảng màu riêng. Giờ đã gom về `design-tokens.css` — **sửa màu ở đó, không sửa rải rác** |

---

## 4. Cổng kiểm tra trước deploy

Người trực deploy không cần gõ hai cổng dưới đây bằng tay. Chế độ `SAFE` chạy
cả hai; chế độ `FAST` chỉ dành cho frontend rủi ro thấp và tự chặn file nhạy
cảm. Xem [deploy-production-owner.md](06-operations/deploy-production-owner.md).

Hai cổng, cả hai phải sạch:

```bash
# Cổng 1: regression + smoke + lint + log + cleanup (~10 phút, build lại image)
LOCAL_ENFORCE_RBAC=1 ./scripts/predeploy-local.sh
# Mong đợi dòng cuối: PASS: Pre-deploy local hoàn tất

# Cổng 2: browser regression desktop + mobile (~10 phút)
cd tools/visual-tests
PLAYWRIGHT_BROWSERS_PATH=.browsers npx playwright test --workers=1
# Kết quả phải có 0 failed; số pass/skip thay đổi khi bộ test được mở rộng.
```

Nếu con số pass tụt so với baseline, **đừng nới lỏng assertion**. Tìm nguyên
nhân. Ví dụ khi gom design token làm 3 assertion màu đỏ, cách xử lý đúng là đổi
assertion sang *đo tỷ lệ tương phản WCAG* và *đọc `--pts-navy` từ `:root`* —
không phải hardcode màu mới.

---

## 5. Deploy

### Production VPS — Docker, có SSH

```bash
# Thay đổi PHP/API/commerce/Admin hoặc khi không chắc:
./scripts/deploy-production.sh --safe --confirm-production

# Chỉ CSS/chữ/ảnh/responsive/template frontend rủi ro thấp:
./scripts/deploy-production.sh --fast --confirm-production
```

Quyết định mode, success criteria và rollback:
[deploy-production-owner.md](06-operations/deploy-production-owner.md).

### Host cũ — tài liệu lịch sử, không còn là production

Các lệnh FTP dưới đây chỉ để điều tra bản lưu cũ khi có phê duyệt rõ ràng;
không dùng cho deploy thường ngày.

```bash
# 1. Xem file nào đổi so với bản đang chạy trên production
git diff --name-status <commit-production>..HEAD -- domains/phucthinhsolar.com/public_html/

# 2. Upload từng file
curl --ftp-pasv --ftp-create-dirs -u 'phucthinhs:<pass>' \
     -T <file-local> "ftp://103.82.21.26/domains/phucthinhsolar.com/public_html/<duong-dan>"

# 3. Xóa cache production (bắt buộc, nếu không sẽ thấy bản cũ)
#    Xóa nhiều file trong một lần gọi bằng nhiều cờ -Q
curl --ftp-pasv -u 'phucthinhs:<pass>' \
     -Q "DELE /domains/.../caches/cache_xxx" ... "ftp://103.82.21.26/domains/.../caches/"

# 4. Xác minh bằng URL thật, KHÔNG tin "upload thành công"
```

Trên host cũ, SQL từng đi bằng phpMyAdmin. Không áp dụng quy trình đó cho VPS
production hiện tại. Bối cảnh lịch sử:
[06-operations/deployment.md](06-operations/deployment.md).

---

## 6. Xác minh: quy tắc không thương lượng

Sau mỗi thay đổi, xác minh bằng **bằng chứng runtime**, không bằng cách đọc lại
code mình vừa viết:

- Đổi CSS → mở trình duyệt thật, đọc `getComputedStyle`, đo `getBoundingClientRect`.
- Đổi query → chạy query đó trên DB thật và đếm dòng.
- Deploy → `curl` URL thật, so nội dung file trên server với file local.
- Sửa bug giao diện → chụp lại và click bằng chuột thật.

Lý do có quy tắc này: trong phiên 2026-09-15 tôi đo được `x=-138, w=651` trên
trang giải pháp và **kết luận sai** là "CSS chưa nạp". Chủ project phản hồi
"responsive bị lỗi", đo lại thì có **189 element tràn ngang** — lỗi thật.

---

## 7. VPS

Trạng thái tại 2026-09-20: Ubuntu 24.04, Docker 29.8.1, Compose 5.5.1,
`/srv/phucthinhsolar` đang chạy production và DNS domain chính đã cutover.
Image active sau đợt commerce/mobile là `phucthinhsolar-web:86811592c357`;
image rollback là `phucthinhsolar-web:62f31b6a6284`.

- Dựng mới / chuyển nhà: [vps-cloudfly-runbook.md](06-operations/vps-cloudfly-runbook.md)
- Deploy SAFE/FAST: [deploy-production-owner.md](06-operations/deploy-production-owner.md)
- Vận hành, đọc log, xử lý sự cố: [vps-operations.md](06-operations/vps-operations.md)

---

## 8. Việc đang treo về phía chủ project

Cập nhật khi xong, đừng để tài liệu nói sai trạng thái.

- 221/415 sản phẩm chưa có ảnh chính.
- `photo_feed = 0/415` → feed Google Shopping hiện xuất 0 dòng (đúng chủ ý, xem audit).
- 21 sản phẩm thiếu mã, 27 sản phẩm thiếu giá.
- Nội dung Admin còn trống: `home-about`, `project-benefit`, `project-trust`.
- Ảnh "QUẠT BLUE FAN" nằm lẫn trong thư viện ảnh đèn ở trang đại lý.
- Mật khẩu FTP `phucthinhs` cần đổi **sau khi** chuyển host xong.
