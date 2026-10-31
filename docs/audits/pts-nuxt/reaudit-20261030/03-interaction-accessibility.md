# Re-audit tương tác, accessibility và media — Nuxt so với PHP

> **Cập nhật remediation 2026-10-30:** các trạng thái source-only bên dưới là baseline trước remediation. IA-01 đã được sửa ở Nuxt và PHP legacy; IA-02 đã sửa và browser-verified. Phát hiện thêm gTranslate sửa SSR DOM trước hydration; widget nay chỉ nạp sau mount và browser xác nhận không còn hydration warning. Bằng chứng: [REMEDIATION-STATUS-20261030.md](REMEDIATION-STATUS-20261030.md).

- Ngày rà soát: 2026-10-30
- Phạm vi: đọc source/tài liệu/test hiện có; chỉ tạo báo cáo này.
- Không sửa product code, không chạy trình duyệt, không chạy test và không thay đổi Git.
- Kết quả dưới đây là source review, không phải browser verification. Báo cáo QA cũ được xem làm đầu mối tìm component/route; không dùng làm bằng chứng cho trạng thái hiện tại.
- PHP đối chiếu chính: `templates/order/order_tpl.php`, `assets/js/functions.js`, `assets/js/apps.js`, `templates/index/index_tpl.php`, cùng các component Nuxt được dẫn ở từng mục.

## Kết luận

Không thấy dấu hiệu source cho thấy các control shared cốt lõi hoàn toàn mất chức năng. Header có drawer/menu tìm kiếm, keyboard Escape, focus trap/restore; catalog có URL filter/sort/page và native dialog filter; PDP có stepper, add-to-cart, thumbnail và lightbox; form contact/dealer có label, trạng thái submit và error; project/solution gallery hỗ trợ đóng và điều hướng ảnh. Đây là kiểm tra implementation, không xác nhận các trạng thái chạy thực tế.

| ID | Mức | Phân loại | Khu vực | Release blocker | Trạng thái |
|---|---:|---|---|---|---|
| IA-01 | P2 | Defect legacy PHP còn được giữ trong Nuxt | Checkout: Tỉnh/TP → Phường/Xã, báo phí ship | NO | Source-confirmed; chưa browser verify |
| IA-02 | P3 | Media fallback Nuxt thiếu chính xác | PDP gallery khi mọi ảnh lỗi tải | NO | Source-confirmed; chưa browser verify |

Không ghi nhận finding P0/P1 từ lượt source review này. Không xác nhận breakpoint nào đã vượt qua browser test; rủi ro hẹp/mobile/desktop bên dưới là suy luận từ CSS/source và cần xác nhận bằng viewport thực tế.

## Findings

### IA-01 — P2 — Danh sách Phường/Xã có thể bị response cũ ghi đè khi đổi Tỉnh/TP nhanh

- **Route/module:** `/thanh-toan/`; `frontend/app/pages/thanh-toan.vue`.
- **Expected PHP vs actual Nuxt:** Cả hai giao diện đều cần Phường/Xã khớp với Tỉnh/TP hiện chọn và backend hiện có xác thực cặp địa chỉ khi lấy báo phí. Nuxt gọi `loadWards()` bất đồng bộ mỗi lần đổi Tỉnh/TP nhưng không hủy request cũ hoặc kiểm tra city của response trước khi gán `wards`; nếu đổi tỉnh lần nữa trước khi request trước trả về, response cũ có thể thay danh sách của tỉnh mới. `quoteShipping()` cũng không kiểm tra lại city/ward sau khi await, nên kết quả cước cũ có thể tạm thời hiển thị cho lựa chọn mới.
- **Evidence/repro từ source:** `thanh-toan.vue:39-51` reset rồi fetch danh sách ward và ghi kết quả trực tiếp; `:74-88` fetch phí vận chuyển và watch thay đổi city/ward độc lập. Repro cần browser/network throttling: chọn tỉnh A, lập tức chọn B, cho request A trả sau B; sau đó kiểm tra danh sách ward và quote. Không thực hiện repro trong lượt này.
- **PHP comparison/root cause:** PHP cũ gọi `loadWardByCity(id)` khi city đổi (`assets/js/apps.js:1149-1156`); AJAX tại `assets/js/functions.js:587-605` cũng ghi response vào select mà không so sánh request mới nhất. Vì vậy đây là **defect legacy PHP được giữ lại**, không phải regression parity mới của Nuxt. API shipping bridge hiện có kiểm tra ward thuộc city (`frontend/server/routes/_migration/shipping-quote.post.ts:20-45`), giảm nguy cơ backend nhận cặp sai nhưng không ngăn UI hiển thị state stale.
- **Fix đề xuất:** gắn request sequence/AbortController vào `loadWards` và `quoteShipping`; chỉ áp dụng response khi city/ward vẫn trùng snapshot lúc gửi. Trong khi tải ward, disable select và phát trạng thái `role="status"`; khi lỗi, hiện thông báo/retry thay vì chỉ để danh sách rỗng.
- **Breakpoint/rủi ro:** logic xảy ra ở mọi viewport; trên mobile đổi select nhanh bằng touch có thể dễ gặp hơn nhưng đây là inference, chưa đo tại 320/360px. Checkout CSS dùng một wrapper chính và form grid một cột trước `nav`, nhưng chưa kiểm tra 320/360/desktop trực quan.
- **Release blocker:** NO. Backend đang xác thực quan hệ ward/city ở endpoint quote; vẫn nên xử lý trước khi dựa vào quote để khách quyết định đặt đơn.
- **Status:** Source-confirmed, runtime chưa xác minh.

### IA-02 — P3 — Gallery PDP mô tả ảnh lỗi tải như thể CMS chưa có ảnh

**Trạng thái hiện tại: FIXED / browser-verified 2026-10-30.** Nội dung dưới đây mô tả baseline trước fix.

- **Route/module:** mọi PDP `/san-pham/:slug`; `ProductPreviewGallery.vue`.
- **Expected PHP vs actual Nuxt:** Khi có URL ảnh nhưng toàn bộ ảnh không tải được, UI nên báo lỗi media/fallback phù hợp. Nuxt loại URL lỗi khỏi `availableIndexes`; khi không còn ảnh được chọn, render câu `CMS chưa có ảnh sản phẩm hợp lệ.` Điều kiện này cũng xảy ra khi CMS có URL nhưng HTTP/media origin hỏng, nên thông báo quy lỗi cho dữ liệu CMS không đúng nguyên nhân và không đưa ra hành động tiếp theo. PHP template dùng media data trực tiếp; chưa xác minh runtime fallback PHP cho HTTP 404, do đó chưa kết luận đây là khác biệt hành vi so với production.
- **Evidence/repro từ source:** `ProductPreviewGallery.vue:29-34` đánh dấu ảnh lỗi và tìm ảnh kế tiếp; `:22-24` xác định ảnh hiện tại không khả dụng; `:134-135` chỉ render khung ảnh khi ảnh còn khả dụng; `:208-210` luôn hiện thông báo CMS thiếu ảnh khi điều kiện đó sai. Repro source-level: truyền một hoặc nhiều URL hợp lệ về mặt DTO nhưng trả lỗi ảnh; sau `@error` cuối, fallback hiện thông báo CMS.
- **Root cause:** fallback gộp trạng thái “không có URL trong CMS” và “mọi URL bị lỗi khi tải” thành cùng một nhánh. Ảnh lỗi trong gallery không được giữ trạng thái lỗi riêng để giải thích hoặc thử lại.
- **Fix đề xuất:** phân biệt `media.length === 0` với `availableIndexes.length === 0`; trường hợp thứ hai nên báo ảnh tạm thời không tải được và có thể cung cấp nút thử lại. Duy trì placeholder theo loại ảnh và không hiển thị khung ảnh vỡ.
- **Breakpoint/rủi ro:** không phụ thuộc breakpoint. Thumbnail nằm trong rail cuộn ngang (`:211-227`); chưa xác minh focus/scroll visibility ở 320/360px hoặc số ảnh dài trên desktop.
- **Release blocker:** NO; chỉ tác động khi tất cả media request hỏng.
- **Status baseline:** Source-confirmed, browser chưa xác minh; parity với PHP fallback còn chưa rõ. **Status hiện tại:** lỗi ảnh bị chặn trước hydration đã được runtime kiểm tra sau fix; xem remediation status.

## Phạm vi component đã đọc và trạng thái source

| Khu vực | Quan sát từ source | PHP/parity và giới hạn |
|---|---|---|
| Header, navigation, search | `SiteHeader.vue:388-414` quản lý Tab/Escape; `:275-327` mở/đóng search/mobile drawer và focus; `:502-514` hamburger 44px; `:928-1059` drawer/mobile category accordion; desktop nav bắt đầu từ 1681px (`:538-540`). | Hợp đồng trong design system yêu cầu desktop nav 5 nhóm từ 1681px, drawer tại dải hẹp hơn. Source có lối mở/đóng, aria-expanded/controls, modal semantics và khôi phục focus. Chưa kiểm tra va chạm logo/control tại 1321–1680, 320/360, hay tương tác keyboard thực tế; không kết luận browser-pass.
| Breadcrumb | `SiteBreadcrumb.vue:16-38`: landmark `aria-label="Breadcrumb"`, home link, current item `aria-current="page"`; item cuối không có `to` được render thành text. | Đúng ngữ nghĩa chính theo chuẩn; wrappers của nhiều route còn bọc breadcrumb bằng border/background, là drift presentation so với contract nhưng không phải lỗi tương tác. Không ghi thành issue riêng trong audit này.
| Catalog filters/sort/pagination | `CatalogPage.vue:55-79` reset page khi filter và dùng native `dialog.showModal()`; `:347-430` select sort và pagination link có current state/hit area; drawer có nút đóng/autofocus (`:466-504`). | Controls có semantics/URL state; native dialog có Escape/focus behavior trình duyệt. Chưa thao tác thực tế để xác nhận query preservation, focus restore, touch scrolling hoặc empty/error runtime.
| Category/product cards | `CatalogProductGrid.vue:21-39` có loading per item, success/error; `:88-104` alt/fallback ảnh; `:130-159` add cart và empty state. | Source có fallback ảnh và thông báo mutation. Không xác minh API/session trên runtime.
| PDP/cart | `ProductPurchasePanel.vue:14-60` chặn duplicate add khi global loading, báo thành công/lỗi; `CartPage.vue:91-173` có loading/error/empty, retry, quantity disable, remove, summary và checkout gate. `QuantityStepper.vue:41-68` label nhóm và nút tăng/giảm có aria-label. | Luồng thương mại được nối tới PHP cart adapter; chưa chạy smoke test category → product → cart → checkout → confirmation theo chỉ dẫn repo. Không xác nhận đơn hàng hoặc state session thực tế.
| Checkout/địa chỉ/payment | `thanh-toan.vue:114-185`: label gắn field, city/ward, radio payment thật trong label, CSRF/checkout token, summary và POST PHP. | Hai cấp địa chỉ đúng policy. Finding race/error state chi tiết ở IA-01. Nút “Đặt hàng” hiện không có state loading/disable trong source (`:182-184`), nhưng PHP form cũ cũng submit theo form truyền thống; chưa có bằng chứng submission chậm gây duplicate ở endpoint nên chưa ghi defect chắc chắn.
| Forms dealer/contact/modal | Dealer field labels/required và submit guard (`DealerLeadForm.vue:24-40,44-73`); contact form label, required, success/error/security loading (`ContactLeadForm.vue:46-85`); consultation modal có label, loading, error, submit disable (`StorefrontConsultationModal.vue:92-230`). | Source có form states và feedback. Chưa chạy browser validation, test screen reader hoặc xác nhận mọi CTA truyền đúng nhu cầu đã preselect.
| Gallery/video, project/news/solution/policy | PDP gallery có keyboard arrows/Escape, focus trap/restore (`ProductPreviewGallery.vue:41-90`), lỗi ảnh có fallback (IA-02); project/solution lightbox có dialog/close controls; news share status, TOC; project media tabs và iframe lazy-load; contact map iframe lazy-load/title. | Source review cho thấy controls/landmarks nhưng không xác minh dữ liệu CMS, link thật, video playback, focus trap runtime hay route resolver. Báo cáo functional cũ chỉ dùng như chỉ dẫn khu vực.
| Footer/floating actions | `SiteFooter.vue` có nav/footer links và `SiteFloatingContact.vue:22-49` hỗ trợ reduced motion cho back-to-top, observer ẩn khi footer vào viewport; Zalo/hotline có label/focus ring. | Kích thước action 44–48px theo source. Chưa xác minh fixed actions có che form/CTA ở 320px, safe area thật hoặc bàn phím mobile.

## Test, browser và breakpoint

- Không chạy automated tests hoặc browser session trong task read-only này. `scripts/test-pts-public-cart-api.php`, `scripts/test-pts-public-catalog-api.php`, form API scripts và các QA artifacts hiện có là inventory/đầu mối; không được tính là test của source hiện tại.
- Không có tuyên bố browser verified. Responsive contract yêu cầu ma trận 320, 360, 390, 414, 768, 1024, 1366, 1440, 1600, 1920, 2560px; lượt này chỉ đọc breakpoint rules trong code và contract, chưa đo DOM/overflow/touch target ở bất kỳ viewport nào.
- 320/360px: header logo có rule thu hẹp và gutter responsive; drawer rộng tối đa viewport trừ gutter; checkout một cột theo breakpoint `nav`; gallery thumbnail rail cuộn ngang. Đây là bằng chứng khai báo CSS, không chứng minh không overflow.
- Desktop: desktop nav chỉ bật từ 1681px theo component; cần kiểm tra thực tế dải 1321–1680 và màn 1920/2560 để xác nhận header/content alignment.

## Tệp thay đổi

- Chỉ tạo `docs/audits/pts-nuxt/reaudit-20261030/03-interaction-accessibility.md`.
- Không sửa product code, test, dữ liệu, production state, backup hay rollback point.
