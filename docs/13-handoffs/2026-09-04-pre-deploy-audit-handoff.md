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

## 2. Trạng thái chốt phiên 2026-09-04

Nhánh `feature/ecommerce-upgrade`, **chưa push**. Gói release đã đóng tại
`backup/releases/20260904T154413Z-9d627a9b91f1`.

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
013 → 015 --apply → 016 → 017 → 018 → 019 → 020
```

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

### 3.3 Quyết định còn treo

- **Slug `den-nlmt`** cho danh mục "Đèn năng lượng mặt trời": viết tắt cố ý,
  vẫn đúng chủ đề nên `migrations/020` chủ động bỏ qua. Đây là danh mục lớn
  nhất (165 sản phẩm) nên rủi ro SEO khi đổi cao hơn lợi ích. Nếu chủ project
  duyệt đổi, chỉ cần thêm một dòng vào `$approvedRenames` trong
  `migrations/020_fix_mismatched_category_slugs.php` rồi chạy lại; script tự ghi
  301 từ slug cũ.
- **"Tin tuyển dụng"**: hiện ẩn khỏi menu vì chưa có bài nào. Đăng bài thuộc
  danh mục này là nó tự hiện lại, không cần sửa code.

### 3.4 Push

30 commit chưa đẩy lên `origin/feature/ecommerce-upgrade`.

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
- **Guard trong script deploy dùng `grep -E`, không dùng `rg`.** Trước đây
  `if rg ...; then FAIL; fi` fail-open khi máy thiếu ripgrep, làm 6 kiểm tra an
  toàn âm thầm PASS.

### 4.3 Migration mới viết theo mẫu nào

Tất cả migration PHP trong phiên này đều: dry-run mặc định, `--apply` mới ghi,
in rõ giá trị cũ → mới từng dòng, liệt kê riêng các ca không suy ra được để chủ
project tự quyết thay vì đoán. Giữ đúng khuôn này cho migration sau.
