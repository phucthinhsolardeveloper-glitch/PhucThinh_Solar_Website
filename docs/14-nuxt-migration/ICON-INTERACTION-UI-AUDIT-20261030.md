# Nuxt icon and interaction audit — 2026-10-30

| Phạm vi | Bằng chứng |
|---|---|
| Source | `frontend/app/components`, `frontend/app/pages`, `frontend/app/middleware`, Nuxt UI/config và các test Node liên quan |
| Chuẩn đối chiếu | `docs/02-development/public-ui-ux-design-system.md` §§5.1, 5.3–5.4, 5.8–5.10, 8, 11 |
| Cách audit | Đọc source + test, kiểm tra compile/build. Chưa có lượt chụp/review ảnh trực quan và Playwright tương tác trên từng route trong audit này. |
| Trạng thái hệ thống | Source đang ở `master` local, có thay đổi WIP chưa commit. Không có thao tác production, DB hay order mutation. |

## 1. Icon — inventory và kết luận

| Nguồn icon hiện có | Cách dùng | Kết quả audit |
|---|---|---|
| Nuxt UI `UIcon` + Lucide cục bộ | Icon giao diện phổ thông; được nạp qua `@nuxt/ui` và `@iconify-json/lucide` | Đã dùng cho fallback trust icon trên trang chi tiết dự án. Không cần thêm Font Awesome. |
| Inline SVG tự thiết kế | Icon menu/danh mục, sơ đồ và chi tiết có ngôn ngữ hình ảnh riêng | **Giữ lại** khi phục vụ thiết kế đặc thù. Không thay hàng loạt bằng Lucide. Có 62 dòng chứa SVG trong 23 file Vue; số lần sử dụng lớn hơn số nét/biểu tượng riêng biệt. |
| Ảnh icon từ CMS | Icon trust/đặc điểm do Admin quản lý | Giữ nguyên dữ liệu thật; component dùng `object-fit: contain`, không thay bằng hình dự đoán. |
| Ký tự Unicode | Mũi tên, dấu đóng/mở và ký hiệu văn bản ngắn | Hợp lý ở một số text link/điều khiển có nhãn; tiếp tục rà những icon-only control để bảo đảm nhãn truy cập và kích thước hit target. |
| Font Awesome class | Trước đây dùng làm fallback trust icon dự án | Đã bỏ `<i class="fas">`; thay bằng các icon Lucide `check`, `headset`, `settings`, `shield-check` qua `UIcon`. DTO PHP vẫn giữ các mã `fa-*` cũ và map an toàn sang Lucide, không làm gãy nội dung Admin. |

| Mức | Phát hiện | File/vị trí | Trạng thái |
|---|---|---|---|
| P1 | Fallback Font Awesome trước đây không có CSS/font trong Nuxt nên có thể thành glyph trống | `frontend/app/components/project-detail/ProjectDetailPage.vue` | **Đã sửa** bằng `UIcon`/Lucide; test hợp đồng xác nhận 4 mapping. |
| P2 | Icon danh mục trong mega-menu suy ra từ label CMS chính xác; label đổi hoặc danh mục mới rơi về icon bánh răng | `frontend/app/components/SiteHeader.vue`, `frontend/shared/catalog-menu-presentation.ts` | **Còn lại**; nên map theo slug/id ổn định nếu API cung cấp khóa ổn định. Không thay mapping bằng icon giả định trong đợt này. |
| P2 | Dấu kiểm và icon liên hệ/địa điểm ở Home/Footer ban đầu nhỏ khoảng 11–14px, thấp hơn thang 16px của design system | `frontend/app/components/home/HomeEditorial.vue`, `frontend/app/components/SiteFooter.vue` | **Đã sửa** lên 16px nhưng giữ nguyên nét SVG tự thiết kế. Visual review nhiều viewport vẫn nằm trong visual gate. |
| P2 | Sao đánh giá dùng `aria-label` trên phần tử văn bản tổng quát | `frontend/app/components/product-preview/ProductReviews.vue` | **Đã sửa**: role `img` công bố một nhãn sao, chuỗi ký tự sao được ẩn với screen reader; có regression test. |

## 2. Tương tác, popup và trạng thái

| Component/route | Mặc định và mở | Khi rê/thoát/đóng | Bàn phím/chạm | Trạng thái và xử lý |
|---|---|---|---|---|
| Mega-menu Danh mục — mọi route | Desktop hover mở; click/keyboard cũng mở có chủ ý | Rời toàn bộ header đóng nếu menu chỉ mở do hover. Menu con dùng `group-hover` và `focus-within`; khi con trỏ đi từ danh mục cha sang flyout liền kề, submenu giữ mở. Ra khỏi mục cha thì submenu đóng ngay, không có delay. | Mega-menu có Escape, backdrop, focus trap và trả focus. Mobile dùng nút accordion/click, không tự mở theo hover. | **P1 đã sửa:** click lần đầu khi menu đang mở do hover giờ ghim menu ở trạng thái mở và focus ô tìm; click lần sau mới đóng. Test source đã thêm. |
| Submenu 5 nhóm điều hướng | Mở khi hover hoặc focus | Đóng ngay khi rời phần tử `<li>`; không có hover delay | Ở màn hình `nav` từ 1321px trở lên, cha là link và không có disclosure button riêng | **P1 còn lại:** tablet/laptop cảm ứng rộng có thể đi thẳng vào trang cha nhưng không mở được submenu bằng chạm. Nằm trong header; chưa tái cấu trúc theo chỉ đạo giữ nguyên header. |
| Popup tìm kiếm header | Mở từ icon tìm kiếm; focus vào input; khóa cuộn trang | Có nút đóng và Escape; không có backdrop/click-outside | Focus trap hiện có; trang phía sau chưa `inert` | **P2 còn lại:** popup gắn `aria-modal="true"` nhưng hình thức là panel thả xuống, không có lớp nền đóng. Cần thống nhất đây là dialog thật hay disclosure/panel không modal. |
| Drawer điều hướng mobile | Mở bằng hamburger; khóa cuộn và focus vào drawer | Nút đóng, chạm nền và Escape đóng; trả focus về hamburger | Nút điều hướng con mở accordion bằng click | **P2 còn lại:** `inert` hiện đặt trên `<header>`; nội dung chính/footer phía sau vẫn có thể được công nghệ hỗ trợ đọc tiếp cận. Nên khóa toàn app nền khi drawer mở. |
| PDP gallery/lightbox | Mở từ ảnh sản phẩm, chỉ khi media hợp lệ | Chạm backdrop/nút đóng/Escape đóng | **Đã bổ sung** focus vào nút đầu, giữ Tab trong lightbox, lưu/trả focus về trigger và khôi phục overflow body; mũi tên trái/phải đổi ảnh. |
| Gallery giải pháp/dự án | Mở từ ảnh hoặc media | Có backdrop, nút đóng và Escape | Đã có focus trap, focus restore và điều khiển mũi tên | Đã có pattern tốt; nút kế thừa focus ring toàn cục. |
| Bộ lọc danh mục/sản phẩm | Mở drawer bằng nút; query link đổi bộ lọc | Nút đóng và Escape; filter dùng query URL | Subcategory/brand/price dùng checkbox trình bày trong link | **Đã bổ sung** `aria-current` cho trạng thái đang chọn; test xác nhận cả subcategory, nhu cầu, brand, price và “Tất cả”. |
| Tin liên quan | Điều hướng ngang theo từng viewport | Không có trạng thái popup | Nút trước/sau tăng từ 36 lên 44px; cuộn dùng `auto` nếu `prefers-reduced-motion` | **Đã sửa**, có test kích thước và giảm chuyển động. |
| Liên hệ nổi | Hotline và Zalo hiển thị trực tiếp; lên đầu trang xuất hiện sau 300px cuộn | Không biến mất ở Footer; hotline pulse giảm theo `prefers-reduced-motion` | Link gọi/chat một chạm; hit targets 44–48px | **Chưa đo tại viewport 320px** cạnh CTA/form cuối trang. Cụm nút chiếm xấp xỉ 164px chiều cao khi đủ ba nút; cần visual/Playwright xác nhận không che submit hoặc link. |
| Dark mode | Nuxt color mode đặt preference/fallback `light`; chưa có nút chuyển theme | Không có toggle để bật dark | Phù hợp chỉ thị trước đó chưa bật toggle trước khi hoàn tất theme | Giữ trạng thái này. Không nhầm với cờ route public. |

## 3. Feature toggles và quyền hiển thị

| Loại cờ/trạng thái | Quyết định hiện tại | Bằng chứng |
|---|---|---|
| Cờ sở hữu route Nuxt `*PublicRouteEnabled` | Đã bỏ khỏi Nuxt config, Compose, middleware và wrapper. Các route/loại trang đã triển khai được Nuxt nhận trực tiếp; slug không có template hợp lệ vẫn theo resolver/fallback rõ ràng. | `frontend/nuxt.config.ts`, `docker-compose.nuxt-service.yml`, `frontend/app/middleware/*`, test `public-route-gate.test.mjs`. |
| SEO `*PublicRouteIndexable` | Tách riêng khỏi sở hữu route; page có nội dung marketing để index, còn Search, Cart, Checkout và order-success giữ `noindex`. | `frontend/nuxt.config.ts`, `frontend/shared/public-seo.ts`; các route commerce giữ metadata `noindex`. |
| Trạng thái hiển thị do Admin/CMS quản lý | **Không xóa**: `visible`, `showCover`, campaign visibility và trường tương tự vẫn quyết định section/media có hiển thị hay không. | DTO/contract Home, Project, News và component tương ứng. Đây là dữ liệu nghiệp vụ, không phải cờ cutover route. |
| Popup/accordion/gallery | Mở theo thao tác người dùng, không có cờ global tắt tính năng. | `SiteHeader.vue`, các gallery, catalog drawer. |

## 4. Các fix đã đưa vào source

| Mục | Thay đổi | Test |
|---|---|---|
| Mega-menu hover rồi click | Click ghim menu thay vì đóng ngay menu vừa mở bằng hover | `site-shell-ui.test.mjs` |
| PDP lightbox | Focus trap/restore, body overflow restore, Escape/Tab và focus-visible controls | `commerce-preview-ui.test.mjs` |
| Project CTA trust icon | Font Awesome fallback đổi sang Nuxt UI `UIcon` với local Lucide; giữ dữ liệu `fa-*` tương thích DTO | `project-detail-contract.test.mjs` |
| Home/Footer custom SVG | Giữ nguyên hình dạng thiết kế riêng, tăng icon nội dung nhỏ lên 16px | `product-review-accessibility.test.mjs` và source review |
| Review ratings | Thêm role/accessible name và ẩn glyph sao trang trí | `product-review-accessibility.test.mjs` |
| Catalog filters | Thêm trạng thái đang chọn có thể đọc bằng công nghệ hỗ trợ | `catalog-filter-responsive.test.mjs` |
| Related-news carousel | Nút 44px và tôn trọng reduced motion | `news-detail-contract.test.mjs` |

## 5. Còn lại trước khi ký giao diện

| Ưu tiên | Việc cần làm | Ghi chú |
|---|---|---|
| P1 | Thêm cách mở submenu desktop bằng chạm, giữ được link cha | Header đang được giữ nguyên; cần thực hiện ở đợt header riêng. |
| P1 | Rà header hotline/chiều cao và khả năng tìm kiếm/ngôn ngữ theo thiết bị | Audit agent xác nhận số hotline chỉ hiện từ 2300px, ngôn ngữ bị ẩn dưới 1321px, header cao 132–174px. Chưa thay đổi vì chỉ đạo giữ header. |
| P2 | Chốt semantic/modal behavior của search panel và inert toàn app nền khi mobile drawer mở | Không đổi IA/header trong đợt này. |
| P2 | Test floating actions tại 320px cạnh form/footer; kiểm tra crop/overlap | Audit hiện tại chưa chụp screenshot runtime. |
| P2 | Dùng key ổn định thay label cho icon catalog; rà icon nhỏ và nhãn sao review | Giữ SVG thiết kế riêng và icon CMS hợp lệ. |
| Visual gate | Chụp/review bằng mắt Home, Catalog, PDP, Solution, Project, News, Dealer, Contact tại viewport mobile/desktop | Source audit không thay thế screenshot review. |

## 6. Kiểm chứng hiện tại

| Kiểm tra | Kết quả |
|---|---|
| Frontend tests | 397/397 PASS trên working tree hiện tại. |
| Typecheck | PASS; Nuxt Icon xác nhận collection `lucide` được nạp local. |
| Nuxt build | PASS; client bundle chứa 43 icon Lucide, 10.40 KB chưa nén. Còn cảnh báo `INEFFECTIVE_DYNAMIC_IMPORT`/`PLUGIN_TIMINGS`, exit code 0. **Build/container sẽ được làm lại sau các fix Home/Footer/review rating ở trên.** |
| Container local | Lần build trước healthy tại `127.0.0.1:3101`; image cũ được tag `rollback-before-icon-interaction-audit-20261030`. Cần rebuild để đưa fix mới nhất vào container. |
| GET smoke | 33/33 manifest entry không có lỗi: 29 trả HTTP 200; 4 alias trả 301 tới canonical Nuxt URL. Không gửi POST. |
| Production/Caddy/DB/volume | Không thay đổi. |
