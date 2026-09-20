# Sửa giao diện mà không làm vỡ trang khác

Cập nhật: 2026-09-20. Đường dẫn tính từ
`domains/phucthinhsolar.com/public_html/`.

Website này có **13 năm CSS chồng lớp**: theme WordPress cũ, theme "local",
`theme-v2`, rồi các hub redesign từng trang. Hệ quả là `!important` nằm khắp nơi
và specificity rất cao. Tài liệu này nói cách chèn thay đổi vào đúng chỗ để
không phải đánh nhau với cascade.

---

## 1. Thứ tự nạp CSS — quyết định mọi thứ

Đọc từ `templates/layout/css.php`. Nạp sau thì thắng (khi specificity bằng nhau):

```text
 1. bootstrap, style.min, vendors-style, styles          ← legacy WordPress
 2. themes/local/css/*                                   ← theme cũ (~20 file)
 3. plugin: slick, owlcarousel, fancybox, magiczoomplus, mmenu, photobox...
 4. css/style.css
 5. css/theme-v2.css                                     ← theme hiện tại
 6. CSS riêng của từng trang (chỉ nạp khi đúng trang):
      $source == 'index'                → home-retail-v3.css
      $source == 'about'                → about-v2.css
      $source == 'daily'                → dealer-v2.css
      $template news/service*           → solution-hub.css
      $template news/project*           → project-hub.css
      $type == 'tin-tuc' && news        → news-hub.css
 7. css/header-v3.css
 8. css/flat-surface-system.css
 9. css/media-system.css
10. css/design-tokens.css
11. css/responsive-architecture.css
12. css/commerce-experience.css                          ← lưới PDP/cart/checkout
13. css/typography-system.css                            ← NẠP CUỐI, thắng tất cả
```

**Quy tắc: thay đổi màu/khoảng cách dùng `design-tokens.css`; thay đổi cỡ chữ
public dùng `typography-system.css`; kiến trúc mobile dùng
`responsive-architecture.css`; luồng mua hàng dùng `commerce-experience.css`.**
Các lớp này nạp cuối theo đúng trách nhiệm để
không phải vá rải rác CSS legacy. Rollback bằng cách bỏ rule tương ứng hoặc dòng
`$css->set()` của lớp đó.

Thêm CSS cho một trang mới: thêm một dòng `if(...) $css->set("css/<ten>.css");`
vào nhóm 6, **trước** `header-v3.css`.

`typography-system.css` có thêm breakpoint `min-width:1600px` cho màn Full HD/
2K: body 20px, menu 17–17.5px, tên sản phẩm 19px và footer link 17px. Không dùng
`zoom` vì nó làm sai breakpoint và vùng bấm. Tên card sản phẩm không còn clamp
hai dòng; card tự tăng chiều cao và `overflow-wrap:anywhere` chỉ ngắt khẩn cấp
các model/cụm từ không có điểm ngắt.

`commerce-experience.css` là chủ sở hữu duy nhất của lưới giỏ hàng. Không khôi
phục tổng cột Bootstrap `3+6+3+3` (15/12) ở mobile. Dưới 992px, tóm tắt đơn nằm
trước form nhận hàng; dưới 768px mỗi dòng dùng grid `72px minmax(0,1fr)`.

---

## 2. `design-tokens.css` — nguồn sự thật cho màu và khoảng cách

### Vấn đề nó giải quyết

Trước 2026-09-15, mỗi hub tự định nghĩa bảng màu riêng. Đo thực tế: **4 biến thể
navy** (`#0a2251`, `#071b46`, `#091f4c`, `#061b46`), **4 biến thể vàng**
(`#c9962e`, `#c58a24`, `#b67d2e`, `#d29a28`), và muted/line cũng 4 biến thể.
Chênh nhỏ nên không ai chỉ ra được, nhưng mắt đọc ra sự không đều — chủ project
mô tả là "cứ lệch lệch, thiếu thiếu cái gì đó".

### Cách nó hoạt động

Không sửa hàng trăm chỗ dùng. Thay vào đó `design-tokens.css` **trỏ lại biến
cục bộ của từng hub về token dùng chung**:

```css
:root{
  --pts-navy:#0a2251;
  --pts-gold:#c9962e;
  --pts-muted:#52627a;
  --pts-line:#e1e7ef;
  --pts-chip-bg:#eef2f7;
  --pts-chip-bg-hover:#dfe6ef;
  --pts-chip-text:#0a2251;
}

/* Hub cũ vẫn dùng tên biến của nó, chỉ đổi giá trị nó trỏ tới */
.pts-project-hub{ --ph-navy:var(--pts-navy); --ph-gold:var(--pts-gold); ... }
.pts-solution-hub{ --sh-navy:var(--pts-navy); --sh-gold:var(--pts-gold); ... }
.pts-news-hub{ --nh-navy:var(--pts-navy); ... }
.pts-about-page{ --about-navy:var(--pts-navy); ... }
```

Nên: **đổi màu toàn site = sửa một dòng trong `:root`.** Rollback = bỏ dòng
`$css->set("css/design-tokens.css")` trong `css.php`.

File này cũng chứa các thứ đã được gom về một kiểu duy nhất:

- Chip/filter: `.pts-ph-filter`, `.pts-catalog-jump a`, `.nh-filter`, `.pts-sd-switcher a`
- Text-link có mũi tên: `.nh-arrow`, `.pts-ph-arrow`
- Tiêu đề section của các hub
- Badge
- `scroll-margin-top` cho ~20 anchor id (170px desktop / 96px mobile)
- `justify-content: safe center` cho các rail
- Khối giá và thanh tab của trang chi tiết sản phẩm

---

## 3. Bốn lỗi layout đã gặp thật và cách sửa

### 3.1 Grid phình ra ngoài container

Triệu chứng: cột rộng 651px nằm trong container 311px; mobile tràn ngang.

Nguyên nhân: `grid-template-columns: 1fr` — `1fr` mặc định là
`minmax(auto, 1fr)`, và `min-width: auto` cho phép cột **nở ra theo nội dung**
thay vì co lại. Một bảng, khối `<code>` hay ảnh không co được là đủ để phá.

```css
/* Sai */
.grid{ grid-template-columns: 1fr 320px; }
/* Đúng */
.grid{ grid-template-columns: minmax(0,1fr) 320px; }
.grid > *{ min-width: 0; }
```

### 3.2 Rail thiếu ô thì thụt hết về một bên

Vùng cuộn ngang dùng `justify-content: center`: khi nội dung **rộng hơn** khung,
`center` đẩy phần đầu ra ngoài vùng cuộn và **cắt mất ô đầu tiên**.

```css
.rail{
  display:flex; overflow-x:auto;
  justify-content:center;      /* fallback cho trình duyệt không hiểu safe */
  justify-content:safe center; /* đủ chỗ thì căn giữa, tràn thì về flex-start */
}
@supports not (justify-content: safe center){
  .rail{ justify-content:flex-start; }
}
```

### 3.3 Anchor link nhảy sai chỗ

Header sticky cao **154px**, nên `#id` cuộn tới đúng vị trí rồi bị header che.

```css
#ve-phuc-thinh, #lich-su-phat-trien, /* ... */ { scroll-margin-top:170px; }
@media (max-width:800px){ /* ... */ { scroll-margin-top:96px; } }
```

### 3.4 Nút bấm không phản hồi dù JS chạy được

Nút "back to top" không hoạt động. `element.click()` bằng JS thì chạy — nên
tưởng là đúng. Click bằng **chuột thật** mới lộ: ba vòng tròn vô hình của dock
liên hệ đã thu gọn vẫn nhận click vì rule `pointer-events` cũ có `!important`,
và chúng nằm đúng trên nút.

Bài học: kiểm tra tương tác phải **click bằng chuột thật**, không phải `.click()`.

---

## 4. Hiệu năng: lazy-load

Tình trạng trước 2026-09-16: 77/91 ảnh trang chủ tải ngay, **2.190 KB**. Sau khi
bật native lazy: **350 KB**, 67 ảnh lazy.

Cách làm: `libraries/class/class.Functions.php::getImage()` tự thêm
`loading="lazy" decoding="async"`. Không cần JS, không bị bug "thẻ trắng" của
thư viện lazy cũ.

**Ngoại lệ bắt buộc: ảnh trong carousel.** Slider dùng `transform` đẩy slide ra
ngoài viewport → trình duyệt hoãn tải vô thời hạn → `src` trả 200 nhưng
`naturalWidth = 0`. Đã làm trắng 10/15 ảnh "Sản phẩm liên quan".

Hợp đồng đầy đủ xem [codebase-guide § 5](../01-architecture/codebase-guide.md).
Điểm cần nhớ: `$pcNoLazy` ≠ `$pcInCarousel`, **đừng suy ra cái này từ cái kia** —
lần sửa đầu tiên suy từ `isLazy => false` và đã tắt sạch lazy của trang chủ
(67 → 0).

---

## 5. Nhịp dọc (khoảng trắng trên/dưới mỗi section)

Hai kiểu cấu trúc khác nhau, đừng lẫn:

- **Trang dạng section**: mỗi section tự đặt padding.
  `theme-v2.css` → `.home-2,.home-3,...{padding:64px 0}`;
  `dealer-v2.css` → `.pts-dealer-hub section{padding:56px 0}`;
  `about-v2.css` → mỗi `.pts-about-*` một giá trị.
- **Trang dạng hub**: wrapper đặt padding, section bên trong dùng `margin`.
  `.pts-project-hub{padding:34px 0 58px}`, `.pts-news-hub{padding:28px 0 50px}`,
  `.pts-solution-hub{padding:32px 0 56px}`.

Trong trang dạng hub, số nhỏ ở trên (28–34px) là **đúng chủ ý**: khối đầu nằm
ngay dưới breadcrumb, để 64px sẽ hở một lỗ trống.

Cạm bẫy khi đo: `about-v2.css` khai báo `.pts-about-story{padding:82px 0 90px}`
ở dòng 22, nhưng **dòng 111 trong cùng file ghi đè xuống `56px 0 64px`** —
và đó không phải media query. **Đọc CSS tĩnh sẽ cho số sai; phải đo
`getComputedStyle` trên trình duyệt thật.**

---

## 6. Quy trình xác minh một thay đổi giao diện

1. Sửa CSS/template ở local (bind mount, không cần build lại).
2. Xoá cache: `docker compose -f docker-compose.local.yml exec web rm -f /var/www/html/caches/cache_*`
3. Mở trình duyệt thật, **đo** chứ không đọc code:
   - `getComputedStyle(el).padding` cho khoảng cách
   - `el.getBoundingClientRect()` cho vị trí/kích thước
   - `document.documentElement.scrollWidth <= clientWidth + 1` cho tràn ngang
   - `img.naturalWidth > 0` cho ảnh đã tải thật
4. Chạy Playwright: `cd tools/visual-tests && PLAYWRIGHT_BROWSERS_PATH=.browsers npx playwright test --workers=1`
5. Kiểm tra cả desktop và mobile. Suite đã cấu hình cả hai.

Khi baseline ảnh đỏ mà giao diện thật đúng: cập nhật baseline có chủ ý
(`--update-snapshots`) và nói rõ trong commit *vì sao* baseline đổi. Đừng nới
`maxDiffPixelRatio`.

---

## 7. Quyết định thiết kế đã chốt — đừng làm trái

Đọc [docs/09-decisions/](../09-decisions/) trước khi đổi hướng thiết kế. Các ADR
hiện có:

- `0001` trang chủ ưu tiên sản phẩm, mật độ nội dung
- `0002` bề mặt nội dung phẳng toàn site (bỏ gradient, bỏ shadow nặng, bo góc nhỏ)
- `0003` hệ thống ảnh public và slot ảnh trong Admin
- `0004` gộp điều hướng chính
- `0005` trang giải pháp dạng editorial dùng CKEditor

`0002` là cái bị vi phạm nhiều nhất: gradient vàng cứ quay lại qua các rule gộp
chung trong `theme-v2.css` (ví dụ `.nav-link.active`). Khi thấy gradient vàng
xuất hiện ở thành phần mới, nguyên nhân gần như luôn là kế thừa từ đó.
