# Audit responsive architecture — 2026-09-18

## Phạm vi

- Home, Giới thiệu, Danh mục sản phẩm, Chi tiết sản phẩm, Tin tức, Chi tiết tin và Liên hệ.
- Shared header, footer, page wrapper, Bootstrap row/column, UI card và marketing band.
- Viewport kiểm tra: `320`, `360`, `375`, `390`, `414`, `768` px.
- Nguyên tắc: desktop giữ boxed hierarchy; mobile `<= 767px` dùng flattened hierarchy.

## Baseline trước thay đổi

Rule cuối của `theme-v2.css` gán padding ngang cho đồng thời `.wrap-content`,
`.wrap-main`, `.uk-container` và `.container`. Vì `templates/index.php` luôn render
`.wrap-main`, các trang hub dùng thêm container bên trong bị cộng hai gutter trước
khi tính padding của section/card.

Tại 320px, audit computed style ghi nhận các chuỗi lớn nhất:

| Trang/khu vực | Tổng padding khai báo trong chuỗi ancestor |
| --- | ---: |
| Home / logo đối tác | 100px |
| Category / filter card | 104px |
| Product detail / review | 110px |
| News / CTA | 108px |
| Contact / intro và form | 104px |

Danh sách quyết định trước implementation:

| Wrapper | Quyết định mobile |
| --- | --- |
| `.wrap-main.wrap-home` | Flatten; container con sở hữu gutter |
| `.wrap-main.wrap-content` | Giữ làm gutter owner cho trang nội dung |
| Header/footer container | Giữ; footer vẫn chừa vùng cho nút liên hệ fixed |
| Bootstrap `.row`/`.col-*` | Không reset toàn cục; chỉ flatten pass-through wrapper đã xác định |
| Home campaign/product heading | Full-bleed, copy inset một lần |
| Category hero/content/campaign | Full-bleed, radius 0 trên mobile |
| Category filter shell | Flatten; control bên trong vẫn giữ trạng thái |
| Product tabs/review shell | Flatten; gallery và form control vẫn giữ UI |
| News card | Giữ entity card; bỏ secondary shell lồng |
| News CTA | Full-bleed; news detail bỏ container lồng |
| Contact intro | Giữ navy band có ý nghĩa, chuyển full-bleed |
| Contact form shell | Flatten; field giữ border |
| About history | Timeline một cột, media full-bleed |
| About certificates | Giữ entity, chuyển thành hàng compact; bỏ chiều cao desktop |

## Implementation

- Thêm `assets/css/responsive-architecture.css`, nạp cuối toàn bộ CSS public.
- Chỉ có rule trong `@media (max-width: 767px)` và `@media (max-width: 359px)`.
- Gutter mobile là 16px; màn hình `<= 359px` là 12px.
- Không thay đổi template dữ liệu, route, schema hoặc hành vi backend.
- Ở 768px, toàn bộ computed padding/surface record của sáu trang baseline giống
  trước thay đổi; stylesheet mới không tham gia cascade.

Kết quả audit 320px sau thay đổi:

- Home logo: chuỗi giảm từ 100px xuống 60px; 36px còn lại là padding nội tại
  của logo entity, không phải wrapper.
- Category hero/content: full-bleed; inset nội dung 12px.
- Product review/tab/description shell: không còn padding ngang wrapper.
- News CTA: full-bleed; article card giữ một lớp inset nội tại.
- Contact intro: full-bleed, inset 12px; form shell không còn padding/border/background.
- About timeline dùng toàn chiều ngang; certificate thumbnail 80×112px ở 320px,
  88×124px từ 360–767px.

## Screenshot và test

- Baseline: `tools/visual-tests/artifacts/responsive-architecture-2026-09-18/before/`
  (36 ảnh, sáu trang ban đầu).
- Sau thay đổi: `tools/visual-tests/artifacts/responsive-architecture-2026-09-18/after/`
  (42 ảnh, bổ sung trang Giới thiệu).
- Mọi response đều HTTP 200; không có viewport nào horizontal overflow.
- Playwright responsive architecture: 3/3 pass, gồm ma trận 42 tổ hợp.
- Regression desktop: 38 pass, 1 skip có chủ đích (test mobile-only).
- Regression About trên mobile profile: 4/4 pass.
- PHP 7.4 lint `templates/layout/css.php`: pass.

## Deploy và rollback

- Chưa deploy production trong task này.
- Rollback code: bỏ lệnh nạp `responsive-architecture.css`, rồi xóa stylesheet và
  hai file Playwright mới. Không có migration hoặc dữ liệu cần rollback.
