# Bàn giao sau audit dữ liệu/logic trước triển khai — 2026-09-04

Điểm vào khi tiếp tục công việc ở phiên/tab/tài khoản mới. Chỉ ghi việc **còn
lại** và bối cảnh tối thiểu để làm tiếp; chi tiết từng lỗi đã nằm trong commit
message tương ứng.

## 1. Bắt đầu lại như thế nào

```bash
cd /home/admin01/Web_PTS_PHP
claude --continue        # mở lại hội thoại gần nhất của đúng thư mục này
```

Container local có `restart: unless-stopped` nên tự chạy lại sau khi khởi động
máy. Kiểm tra nhanh:

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8080/   # mong đợi 200
docker compose -f docker-compose.local.yml up -d                  # nếu chưa chạy
git log --oneline -8
```

**Nếu trả về 403 (thư mục `/var/www/html` rỗng trong container):** đây là do
WSL chưa mount xong ổ đĩa lúc Docker tự khởi động container theo
`restart: unless-stopped`, nên bind mount `./domains/...:/var/www/html` bám vào
một thư mục rỗng tại thời điểm đó. Cách sửa — tạo lại container `web` (không
đụng tới `db`, dữ liệu không mất vì nằm ở named volume riêng):

```bash
docker compose -f docker-compose.local.yml up -d --force-recreate web
```

## 2. Trạng thái chốt phiên 2026-09-04

Nhánh `feature/ecommerce-upgrade`. Gói release đã đóng tại
`backup/releases/20260904T154413Z-9d627a9b91f1`.

**Cập nhật 2026-09-05:** đã push — local và `origin/feature/ecommerce-upgrade`
khớp nhau ở `bf79e07`, xem mục 3.4.

Kết quả kiểm thử tại thời điểm chốt:

- 12 bộ test PHP / 320 assertion — pass toàn bộ.
- Playwright `layout-interactions`: 7/7 desktop, 8/8 mobile.
- Menu công khai: 27/27 đích trả 200.
- Sitemap: 511 URL, lấy mẫu 26, không URL nào lỗi.
- Toàn vẹn DB: 0 sản phẩm mồ côi, 0 tham chiếu chết, 0 sai cấp cha-con.
- Log PHP: sạch.

Không còn lỗi P0/P1 nào đang mở.

## 3. Việc còn lại

### 3.1 Deploy — bắt buộc, ưu tiên cao nhất

Backup database production trước, sau đó chạy migration **đúng thứ tự**:

```
013 → 015 --apply → 016 → 017 → 018 → 019 → 020 → 021
```

`021` (2026-09-05, thêm cột `photo_khuyenmai` cho ảnh khuyến mãi/sự kiện theo
sản phẩm) chỉ thêm cột nullable, không cần dry-run, an toàn chạy trực tiếp.

`018`, `019`, `020` **mặc định dry-run**. Phải chạy xem trước trên DB thật, đọc
kết quả, rồi mới thêm `--apply`. Production nhiều khả năng có mức thiệt hại
**khác local** vì cùng dính bug form Admin ghi đè danh mục (xem 4.1), nên số
dòng cần sửa sẽ khác.

`014` là báo cáo chỉ đọc, chạy lúc nào cũng được.

### 3.2 Cập nhật ảnh baseline visual

`tools/visual-tests/visual-baseline.spec.js` đang 2 fail / 1 pass
(`home` desktop, `products` mobile). Ảnh lệch **từ trước phiên 2026-09-04** do
giao diện đã thay đổi hợp lệ, không phải regression của phiên này.

Chỉ chạy lệnh dưới **sau khi chủ project nhìn và xác nhận giao diện hiện tại là
đúng** — nó chốt lại ảnh chuẩn:

```bash
cd tools/visual-tests && npx playwright test visual-baseline.spec.js --update-snapshots
```

### 3.2b Tính năng mới thêm 2026-09-05 (đã test, chưa dùng thật)

Chủ project tham khảo giao diện Kitawa Solar Light, yêu cầu 3 việc — cả 3 đã
code + test xong nhưng **chưa có ảnh/nội dung thật** vì cần chủ project tự
chuẩn bị theo từng đợt sự kiện:

- **Banner đầu trang**: Admin → Cài đặt hình ảnh → "Banner đầu trang (trên
  header)". Bật "Hiển thị" + upload ảnh là hiện full-width trên mọi trang;
  tắt là ẩn, ảnh vẫn lưu lại dùng cho đợt sau.
- **Popup khi vào trang chủ**: Admin → Cài đặt hình ảnh → "Popup quảng cáo".
  Data cũ đã có sẵn trong DB (ai đó cấu hình trước đây), giờ có nút bật/tắt.
- **Ảnh khuyến mãi/sự kiện theo từng sản phẩm**: sửa 1 sản phẩm → card "Ảnh
  khuyến mãi/sự kiện" để upload + tick cờ "Khuyến mãi/Sự kiện" ở khối trạng
  thái. Card sản phẩm sẽ ưu tiên hiện ảnh này thay ảnh thường. Áp dụng hàng
  loạt: cột "Khuyến mãi/Sự kiện" tự xuất hiện ở trang danh sách sản phẩm
  (checkbox từng dòng + nút "Cập nhật hàng loạt" theo trang/toàn bộ lọc) và
  trong file Excel xuất/nhập hàng loạt (`administrator?com=product&act=export`).

Nhân tiện rà soát phát hiện 2 chỗ Admin thiếu quyền chỉnh dù frontend đang
hiển thị thật (ảnh + video trang "Về chúng tôi") — đã đăng ký lại, xem
migrations/021 và commit tương ứng.

### 3.3 Quyết định còn treo

- **Slug `den-nlmt`** cho danh mục "Đèn năng lượng mặt trời": **đã chốt giữ
  nguyên** (chủ project duyệt 2026-09-05, không đổi). Viết tắt cố ý, vẫn đúng
  chủ đề, `migrations/020` chủ động bỏ qua — không cần sửa gì thêm.
- **"Tin tuyển dụng"**: hiện ẩn khỏi menu vì chưa có bài nào. Đăng bài thuộc
  danh mục này là nó tự hiện lại, không cần sửa code.

### 3.4 Push — đã xong

Đã push 2026-09-04. Local và `origin/feature/ecommerce-upgrade` khớp nhau ở
`bf79e07`, 0 commit còn lại. Việc còn lại là mở PR / merge khi chủ project
sẵn sàng, không phải push thêm.

## 4. Bối cảnh cần biết trước khi sửa tiếp

### 4.1 Bug gốc đã vá — đọc trước khi đụng vào Admin

`getAjaxCategory()` trong `libraries/class/class.Functions.php` trước đây chỉ
lấy giá trị chọn sẵn từ `$_REQUEST`. Route sửa không mang tham số danh mục nên
form không chọn sẵn gì, và **mỗi lần bấm Lưu ghi đè `id_list`/`id_cat`/
`id_brand` về 0**. Đây là nguyên nhân gốc của toàn bộ dữ liệu hỏng mà
`018`/`019` đang dọn.

Đã vá bằng `categoryFieldValue()` (ưu tiên `$_REQUEST`, fallback về bản ghi đang
sửa) và khoá bằng assertion trong `scripts/test-admin-readiness.php` — assertion
này fail nếu ai đó revert bản vá.

### 4.2 Quy ước đang áp dụng

- **Canonical suy ra từ entity**, không lấy từ REQUEST_URI. Xem
  `publicCanonicalEntityPath()` trong `libraries/product_category_urls.php`.
  Router chấp nhận nhiều tiền tố trang trí cho cùng một entity; đừng quay lại
  canonical theo URL đang truy cập.
- **Đổi slug công khai phải ghi 301** vào `#_url_redirect` trước khi update, và
  **xoá cache công khai** sau khi ghi (menu đọc qua cache file 2 giờ mà chỉ
  luồng Admin mới tự dọn). `migrations/020` làm mẫu đầy đủ cả hai bước.
- **Danh mục rỗng bị ẩn khỏi điều hướng** bằng `exists (...)` trong
  `sources/allpage.php`, áp cho cả sản phẩm lẫn tin tức/giải pháp/dự án.
- **Guard trong script deploy dùng `grep -qF`/`grep -qE`, không dùng `rg`.**
  `rg` không tồn tại thật trên máy này — nó chỉ là shell function do Claude
  Code chèn vào phiên tương tác (`type -a rg` → "rg is a function"), một shell
  non-interactive (script `bash scripts/...sh`) không thấy nó. Phiên
  2026-09-04 đã sửa 6/51 chỗ; phiên 2026-09-05 rà lại thì thấy còn **45 chỗ**
  `rg -q` khác ở dòng 115–222, khiến `set -euo pipefail` làm script **chết
  giữa chừng ở lần chạy đầu mỗi phiên mới** (không phải fail-open lần này —
  script thoát lỗi luôn, nhưng 45 assertion phía sau chưa từng thực sự chạy
  qua bất kỳ phiên nào trước đó). Đã chuyển hết sang `grep -qF`/`grep -qE`
  (tuỳ dòng có cần regex thật hay không) và thêm một guard đầu file tự chặn
  nếu ai đó lỡ thêm `rg -q` trở lại:
  ```bash
  if grep -n '^\s*rg -q' scripts/*.sh; then
      echo "FAIL: Script deploy còn dùng ripgrep; chuyển sang grep -qF/-qE" >&2
      exit 1
  fi
  ```
  Chạy lại toàn bộ `scripts/predeploy-local.sh` sau khi sửa: tất cả 45
  assertion mới kích hoạt đều **PASS thật** trên local, không phải giả định.

### 4.3 Migration mới viết theo mẫu nào

Tất cả migration PHP trong phiên này đều: dry-run mặc định, `--apply` mới ghi,
in rõ giá trị cũ → mới từng dòng, liệt kê riêng các ca không suy ra được để chủ
project tự quyết thay vì đoán. Giữ đúng khuôn này cho migration sau.
