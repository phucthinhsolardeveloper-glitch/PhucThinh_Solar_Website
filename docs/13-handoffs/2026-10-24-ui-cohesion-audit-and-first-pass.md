# Audit cohesion UI và first pass — 2026-10-24

## Phạm vi và trạng thái

- Worktree: `/home/admin01/src/pts-ui-cohesion-20261024`
- Branch: `feature/pts-ui-cohesion-20261024`
- Base đã kiểm tra: `1ea8ab5`
- Ghi chú trước nghiệm thu: phiên Codex không thể tự tạo commit do Git worktree dùng metadata tại `/home/admin01/src/Web_PTS_PHP/.git/worktrees/pts-ui-cohesion-20261024/`, ngoài writable root và trả `Read-only file system` khi tạo `index.lock`. Điều phối viên sẽ commit từ môi trường Git có quyền sau review.
- Phạm vi: CSS public shared layer và regression test UI. Không đổi template cấu trúc homepage, nội dung/Admin, form/backend, schema, SEO/URL hay production.
- `/du-an/` được đối chiếu với route canonical/template hiện hữu `/du-an-da-thuc-hien/`; router vẫn giữ cả hai case `du-an` và `du-an-da-thuc-hien`.

## Kết luận audit

### Đã xác minh từ code và rendered artifact local

1. Focus keyboard không có owner shared ổn định. `assets/css/style.css:175` đặt `outline:none` cho `a,input,textarea`; một số form còn lặp lại `outline:none` ở `dealer-v2.css:42` và `contact-v3.css:47`. Catalog overlay có focus riêng, nhưng không bao phủ các link/button/form control trên các hub.
2. Primary CTA cùng vai trò còn lệch surface language: Giải pháp dùng gradient + radius 7px ở `solution-hub.css:51`; Giới thiệu dùng radius 9px và hover chung đổi sang nền trắng ở `about-v2.css:103,107`; Đại lý kế thừa radius 7px từ `.pts-btn` trên route không phải homepage. Dự án còn rule gốc gradient/pill/shadow ở `project-hub.css:41,86`, nhưng đã có override cuối file nên computed style hiện tại đã phẳng — đây là drift ownership cần gom, không phải lỗi visual mới. Các khác biệt trên được xác minh từ code.
3. Các pass trước đã xử lý một phần token màu, container, spacing và surface phẳng. Vì vậy batch này không mở rộng sang đại tu vertical rhythm/card/background; các khác biệt còn lại cần rendered measurement thật trước khi sửa tiếp.

### Giả thuyết chưa đủ bằng chứng

- Vertical rhythm vẫn có thể lệch ở các inner section của Giải pháp/Giới thiệu/Đại lý; CSS có nhiều giá trị lịch sử 42/54/56/58/64px nhưng tài liệu đã ghi một số ngoại lệ có chủ ý. Không sửa trong batch này khi chưa đo `getComputedStyle` trên server local.
- Icon stroke và media crop có drift ở legacy surface; baseline PNG cho thấy hướng chung đã ổn, nhưng chưa có phiên browser mới trong môi trường này để kết luận theo viewport 390/768/1440/1920.

## Ma trận cross-page

| Nhóm route | Selector/bằng chứng | Trước first pass | Xử lý |
|---|---|---|---|
| `/`, `/san-pham/`, `/danh-muc-san-pham/den-nlmt/` | Header, product CTA, `a,input,textarea` | Focus ring phụ thuộc legacy/riêng từng component | Shared `:focus-visible` |
| Product detail thật `/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-t300-300w` | `.btn-product-detail`, `#pts-pdp-consultation` | CTA đã có token riêng; focus shared chưa nhất quán | Chỉ hưởng shared focus/CTA token, không đổi PDP layout |
| `/giai-phap/` và detail | `.pts-sh-consult` | Gradient, radius `7px` | Gold phẳng, radius `4px`, không shadow |
| `/du-an/` → canonical `/du-an-da-thuc-hien/` | `.pts-ph-featured-cta`, `.pts-ph-consult` | Rule gốc gradient/pill/shadow còn nằm trong hub; computed final đã được override phẳng nhưng chưa có owner shared | Gom invariant vào token shared, không đổi visual đã đạt |
| `/gioi-thieu/` | `.pts-about-contact-button.is-primary` | Radius `9px`; hover chung chuyển trắng | Radius `4px`; primary hover giữ gold-light |
| `/tin-tuc/` | `.nh-consult` | Đã được token hóa ở first pass trước | Không đổi; dùng làm control |
| `/dai-ly/` | `.pts-dl-submit`, `.pts-dl-input:focus` | Submit kế thừa radius 3px; input tắt outline | CTA token hóa; focus shared bổ sung ring |
| `/lien-he/` | `.pts-contact-v3`, form focus | Form đã có box-shadow focus riêng; link/button vẫn chịu legacy reset | Không đổi cấu trúc/form; hưởng shared focus nơi cascade cho phép |

## Thay đổi đã thực hiện

### 1. Shared focus ring

File `domains/phucthinhsolar.com/public_html/assets/css/design-tokens.css`:

- Thêm token `--pts-focus-ring: var(--pts-navy)`.
- Thêm `:where(a,button,input,select,textarea,summary):focus-visible` ở shared layer cuối, `3px` outline và `3px` offset.
- Chỉ kích hoạt với keyboard focus, không đổi trạng thái hover/touch.

Mục tiêu là khôi phục trạng thái keyboard bị legacy `outline:none` làm mất, không tạo component hay theme mới.

### 2. Shared primary CTA surface

Trong cùng shared layer, các class CTA đã tồn tại được gom về một rule nhỏ:

`.pts-btn-gold`, `.pts-sh-consult`, `.pts-ph-featured-cta`, `.pts-ph-consult`, `.pts-about-contact-button.is-primary`

đều dùng token gold hiện hành, `background-image:none`, `border-radius:4px`, `box-shadow:none`; hover dùng `--pts-gold-light`.

`about-v2.css` chỉ đổi selector hover chung thành `:not(.is-primary)` để primary CTA không bị rule legacy đổi sang trắng; hotline/Zalo không bị thay đổi semantics.

## Test và QA

| Kiểm tra | Kết quả | Ghi chú |
|---|---|---|
| `git diff --check` | PASS | Không có whitespace error |
| `node --check tools/visual-tests/tests/ui-cohesion.spec.js` | PASS | Spec mới hợp lệ cú pháp |
| PHP lint `templates/layout/css.php` | SKIPPED | PHP CLI không có trong execution environment |
| `ui-cohesion.spec.js` — CTA computed style/hover | SKIPPED | `tools/visual-tests/node_modules` không tồn tại; Playwright local không khả dụng |
| `ui-cohesion.spec.js` — keyboard focus ring | SKIPPED | Cùng blocker Playwright |
| Local HTTP/rendered smoke | SKIPPED | `curl localhost:8080` bị sandbox từ chối socket; không có local server đang chạy |
| Before/after screenshot 390/768/1440/1920 | SKIPPED | Không tạo screenshot giả và không dùng production |

### Screenshot artifact

Các ảnh dưới đây là baseline local đã có từ trước trong repository, dùng để đối chiếu ngữ cảnh; không phải ảnh before được chụp lại riêng cho patch này:

- Before/reference desktop homepage: `tools/visual-tests/tests/visual-baseline.spec.js-snapshots/home-desktop-chromium-linux.png`
- Before/reference desktop catalog: `tools/visual-tests/tests/visual-baseline.spec.js-snapshots/products-desktop-chromium-linux.png`
- Before/reference desktop PDP: `tools/visual-tests/tests/visual-baseline.spec.js-snapshots/product-detail-desktop-chromium-linux.png`
- Before/reference mobile homepage: `tools/visual-tests/tests/visual-baseline.spec.js-snapshots/home-mobile-chromium-linux.png`
- Before/reference mobile catalog: `tools/visual-tests/tests/visual-baseline.spec.js-snapshots/products-mobile-chromium-linux.png`
- After build local: chưa tạo, cần chạy trên môi trường PHP/Docker chuẩn. Preview overlay read-only trên website công khai đã được tạo ở `artifacts/ui-review-20261024/` (không phải screenshot local đã rebuild). Không cập nhật baseline snapshot trong commit này.

## Ưu tiên còn lại

- **P1:** chạy lại `cd tools/visual-tests && pnpm test -- tests/ui-cohesion.spec.js` ở local runtime thật; chụp after ở 390, 768, 1440 và 1920; kiểm tra focus ring trên nền sáng/nền navy và CTA hover bằng chuột thật.
- **P2:** audit tiếp các non-primary CTA/link-card legacy trong `theme-v2.css`, đặc biệt catalog filter/pagination và các component còn radius/shadow lịch sử; chỉ sửa sau khi có computed-style evidence.
- **P3:** đo cross-page vertical rhythm/container/header-footer bằng browser thật trên toàn bộ route yêu cầu; không suy ra từ CSS tĩnh.

## Rủi ro, production và rollback

- Rủi ro còn lại: chưa có browser QA sau patch; focus ring navy có thể cần kiểm tra thêm trên một số nền navy/ảnh. CTA visual change có chủ đích và không đổi link, label, màu brand token hay semantics.
- Production state: không deploy, không SSH, không gọi DB thật, không ghi dữ liệu thật; repo `/home/admin01/src/Web_PTS_PHP` không bị tác động.
- Cleanup/backup: không xóa file, không cleanup branch/cache/asset, không tạo node_modules.
- Rollback point: commit trước patch `1ea8ab5`; hoặc revert riêng các thay đổi trong `design-tokens.css`, `about-v2.css` và xóa spec `tools/visual-tests/tests/ui-cohesion.spec.js`.

## Review độc lập của điều phối viên — 24/10/2026

- Phát hiện selector `.pts-btn` trong bản patch đầu tiên quá rộng: có thể tô gold cả nút phụ `.pts-btn-ghost`. Đã loại selector generic ra khỏi nhóm primary; chỉ chọn các class mang nghĩa primary cụ thể.
- Phát hiện `theme-v2.css` còn nhóm rule gradient `!important` áp cho `.pts-btn-gold`. Đã tách đúng `.pts-btn-gold` khỏi hai nhóm gradient thường/hover trong theme legacy (không tăng thêm `!important`), giữ lại rule gradient của các component khác chưa thuộc batch này. File này vì vậy là phần cần thiết của patch.
- Giữ nguyên footer/header/homepage/PDP layout, theme navy/vàng, chức năng Admin, route và giá.
- Test browser `ui-cohesion.spec.js`: cập nhật keyboard-modality bằng Tab trước khi focus và dùng polling cho hover có transition để giảm flake.
- Nghiệm thu browser độc lập bằng Playwright ngoài sandbox: đọc-only public website thực tế ở desktop 1440px và mobile 390px; tổng 5 route x 2 viewport x before/after = 20 lượt HTTP 200. Các lượt đo đều không xuất hiện tràn ngang; CTA của Giải pháp, Dự án, Giới thiệu và Đại lý sau bản preview đạt gold phẳng/radius 4px/hover gold-light ở cả desktop và mobile.
- Preview được mô phỏng bằng cách sửa tạm CSS cache trong browser và overlay các stylesheet từ worktree: KHÔNG ghi production. Đây là kiểm tra *thị giác mô phỏng trên dữ liệu live*, **không phải** nghiệm thu từ bản PHP build local, không dùng thay cho full Playwright regression/local rebuild. Có GTranslate tự dịch screenshot sang tiếng Anh; không dùng ảnh này làm bản duyệt nội dung.
- Artifact tạm để chủ dự án xem trên máy: `artifacts/ui-review-20261024/` (cặp `*-before.png` / `*-after.png`, cùng `qa-results.json`). Ảnh không được đưa vào commit.
- Cần chạy đầy đủ test trong local Docker đúng source và kiểm tra CSS cache được generate theo asset order, sau đó mới xem xét merge/deploy. Chưa có bằng chứng đảm bảo các tương tác theo toàn site đạt 100%.

