# Audit section cuộn ngang trên mobile — PHP ↔ Nuxt

**Ngày:** 30/10/2026  
**Phạm vi:** audit ban đầu và remediation local cho các rail/carousel trên PHP production checkout và Nuxt local; không deploy production.  
**Runtime:** PHP `http://127.0.0.1:8080`, Nuxt `http://127.0.0.1:3100`.  
**Viewport:** 320, 360, 390, 414 và 768 px; đối chiếu trực tiếp các route đại diện bên dưới.  
**Trạng thái Git:** `master` tại `e09f0bf`; working tree có WIP trước audit. Báo cáo và ảnh mới nằm riêng trong thư mục audit, không chỉnh các file WIP đó.

## Kết luận

**Baseline audit: FAIL. Sau remediation local: PASS về hướng bố cục/cuộn cho cả bốn finding MHR-01–04; chưa đạt pixel parity hoàn toàn.** Ba section có cùng dữ liệu CMS nhưng trước sửa PHP hiển thị rail ngang, còn Nuxt xếp dọc: ứng dụng phù hợp trên PDP, phản hồi đại lý và cards CMS “Chọn đèn đúng nhu cầu” của danh mục. Đã khôi phục rail mobile, đo DOM tại 320–390 px và chụp ảnh cặp PHP/Nuxt sau sửa tại 390 px. Còn khác biệt typography/bề mặt ở một số section, được ghi dưới đây.

Rail điều hướng/filter chính trên Home, Catalog, Solutions, Projects, News, About và PDP vẫn cuộn ngang. Rail danh mục `/san-pham/` trước sửa dài hơn PHP 43%; sau khi đặt cùng width 145 px cho mỗi mục, `scrollWidth` Nuxt và PHP đều 1.209 px ở 320/390/768 px. Không deploy production.

## Phát hiện

| ID | Mức độ / trạng thái | Trang và section | PHP kỳ vọng / thực tế | Nuxt thực tế | Ảnh hưởng |
|---|---|---|---|---|---|
| MHR-01 | **P2 — PASS sau sửa, release blocker: NO** | PDP `/san-pham/den-ufo-nang-luong-mat-troi-oriana-ufo-1000-1000w`, “Phù hợp với nhu cầu của bạn” | PHP rail 3 card 230 px, overflow/snap ngang đến 991 px. 390 px: track 246 px, card 230×238 px. | Trước sửa: một cột. Sau sửa: rail 230 px/card, cuộn ngang đến 991 px. 390 px track 246 px; chiều cao track Nuxt 242 px so với PHP 246 px. | Chênh lệch chiều dài dọc đã xử lý. QA độc lập xác nhận đủ 3 ảnh/card và không có tràn ngang toàn trang. |
| MHR-02 | **P2 — PASS sau sửa, release blocker: NO** | `/dai-ly/`, “Phản hồi từ khách hàng và đối tác” | PHP rail ngang ≤767 px, card `min(86vw,340px)`, snap. 390 px: track 527 px cao, card 335 px. | Trước sửa: 3 hàng dọc. Sau sửa: rail ngang, card 335 px; 768–991 px hai cột, từ 992 px ba cột. | Chiều cao track giảm từ 1.357 px xuống khoảng 527 px tại 390 px. QA độc lập kiểm tra cuộn cảm ứng/keyboard và không có page overflow. |
| MHR-03 | **P2 — PASS sau sửa, release blocker: NO** | Trang danh mục `/danh-muc-san-pham/den-nlmt/`, “Chọn đèn đúng nhu cầu” | PHP lấy 3 thẻ CMS qua `productRenderCardsBlocks`; class rail mang tên cũ `.pts-product-project-grid`; mobile card `min(82vw,320px)`, ảnh 4:3. Đây là cards ứng dụng, **không phải dự án**. | Trước sửa: grid dọc và ảnh 8:5. Sau sửa: CMS `categoryContent.applications` thành rail/snap ngang dưới 768 px, cùng chiều rộng card và ảnh 4:3; từ 768 px grid 2 cột, từ 1200 px grid 3 cột. | 390 px track width 358 px và scrollWidth 1.007 px khớp PHP; document không tràn ngang. Không thiếu API/data. |
| MHR-04 | **P3 — PASS sau sửa, release blocker: NO** | `/san-pham/`, dải danh mục đầu trang | PHP 8 mục, mỗi mục 145 px; `scrollWidth` 1.209 px tại 320/390/768 px. | Trước sửa scrollWidth 1.724 px. Sau sửa mỗi mục 145×50 px; scrollWidth 1.209 px tại 320/390/768 px, nhãn được wrap tối đa 2 dòng. | Khoảng cuộn bằng PHP; document không tràn ngang ở các viewport này. |

### Bằng chứng source cho các lỗi

- MHR-01: PHP markup tại `domains/phucthinhsolar.com/public_html/templates/product/product_detail_tpl.php:692`; PHP breakpoint/rail tại `assets/css/product-detail-v2.css:374-396`. Nuxt grid dọc trước remediation; rail hiện tại ở `frontend/app/components/product-preview/ProductPreviewApplications.vue:8-23`.
- MHR-02: PHP markup tại `templates/daily/daily_tpl.php:133-155`; rail CSS tại `assets/css/dealer-v2.css:127-147`. Nuxt grid một cột trước remediation; rail hiện tại tại `frontend/app/components/dealer/DealerSections.vue:48-68`.
- MHR-03: PHP generic CMS renderer tại `libraries/product_content_blocks.php:565-600`; rail CSS tại `assets/css/product-proof.css:206-228`. Nuxt adapter trả cùng nội dung trong `categoryContent.applications`; rail hiện tại tại `frontend/app/components/catalog/CatalogPage.vue:436-445`, runtime đã kiểm tra ở 320/390/768/992 px.
- MHR-04: PHP `.pts-category-tabs` ở `assets/css/product-category-v3.css:28-80`; Nuxt nav ở `frontend/app/components/catalog/CatalogPage.vue:290-309`.

### Root cause và bước tái hiện

- **MHR-01:** API Nuxt trả đủ 3 applications. Trên cả PHP/Nuxt mở PDP ở 320/390/768/992 px: 3 thẻ, document width bằng viewport. Mobile rail Nuxt 230 px/card; scrollWidth tại 390 px là 750 px như PHP. Tại 320 px Nuxt 746 px, PHP 750 px (lệch 4 px); section track Nuxt 242 px, PHP 246 px.
- **MHR-02:** API Nuxt trả đủ 3 testimonials. QA độc lập kiểm tra preview route ở 320/390/768/992/1321 px; 320/390 là rail, 768 là 2 cột, ≥992 là 3 cột; document không tràn ngang, ArrowRight cuộn rail.
- **MHR-03:** Mở category ở 320/390/768/992 px. 320/390: Nuxt và PHP đều rail 3 card, image ratio 4:3, scrollWidth lần lượt 835/1.007 px Nuxt và 831/1.007 px PHP; tại 390 px track rộng 358 px ở cả hai bên, document width bằng viewport.
- **MHR-04:** `/san-pham/` ở 320/390/768 px: 8 nav items, mỗi mục 145 px, scrollWidth 1.209 px trên cả PHP và Nuxt; document width bằng viewport.

## Các rail đã kiểm tra — không thấy bị xếp dọc

| Nhóm trang | PHP source/runtime | Nuxt source/runtime | Kết quả |
|---|---|---|---|
| Home — nhóm danh mục | `.pts-home-category-rail`; PHP và Nuxt đều có `scrollWidth` lớn hơn chiều rộng nhìn thấy ở 390 px | `HomeCatalog.vue`, flex rail dưới 860 px | **PASS** |
| Home — tab giải pháp | `.pts-home-tabs` là rail; Nuxt UI tabs có track ngang | `HomeEditorial.vue`; DOM runtime overflow ngang | **PASS** |
| Danh mục sản phẩm — tab danh mục | PHP `.pts-category-tabs`; Nuxt nav `overflow-x-auto` | Cả hai vẫn là rail | **PASS về hướng bố cục**, chiều dài có chênh lệch MHR-04 |
| Hub Giải pháp — thẻ chọn giải pháp | PHP `.pts-sh-choice-grid` cuộn ngang ở ≤1100 px; Nuxt `SolutionCards.vue` cuộn ngang đến 1100 px | DOM 390 px: 5 thẻ, scroll width ~1.510 px PHP / ~1.510 px Nuxt | **PASS** |
| Hub Dự án — bộ lọc | PHP `.pts-ph-filters>div`; Nuxt `ProjectFilters.vue` | Hai bên đều overflow ngang | **PASS** |
| Hub Tin tức — bộ lọc | PHP `.nh-filters`; Nuxt `NewsFilters.vue` | Hai bên đều overflow ngang | **PASS** |
| Giới thiệu — quick navigation | PHP `.pts-about-section-nav`; Nuxt `AboutPreviewPage.vue` | Hai bên đều overflow ngang | **PASS** |
| PDP — navigation tới section | PHP `.pts-pdp-section-nav`; Nuxt `ProductPreviewPage.vue` | Hai bên đều overflow ngang | **PASS** |
| Đại lý — quick navigation | PHP `.pts-dl-quick-nav`; Nuxt `DealerPageContent.vue` | Hai bên đều overflow ngang | **PASS** |
| Tin chi tiết — tin liên quan | PHP dùng related-card rail ở mobile; Nuxt `NewsDetailRelated.vue` có snap/overflow ngang | Source Nuxt giữ rail; chưa phát hiện chuyển thành grid dọc | **PASS về cấu trúc source** |
| PDP — thumbnail ảnh | PHP slider/thumbnail và Nuxt `ProductPreviewGallery.vue` đều có thể cuộn ngang | Nuxt có `overflow-x-auto` | **PASS** |

## Quy trình và giới hạn

1. Đọc CSS/template PHP gốc, các Vue component/contract liên quan; không dựa riêng vào checklist cũ.
2. Dùng Playwright headless trên PHP và Nuxt local; đo `clientWidth`, `scrollWidth`, chiều cao, số thẻ, kích thước thẻ và `overflow-x` ở 320/360/390/414/768 px cho các section lỗi. Dùng 390 px để rà các route-family còn lại.
3. Đối chiếu source/runtime cho 11 route-family: Home, landing Catalog, category Catalog, PDP, hub Solutions, hub Projects, hub News, Dealer, About, Gallery, Video. Với rail dùng component chung, so sánh implementation phía PHP và Nuxt để áp dụng cho route siblings.
4. Ảnh trong `mobile-horizontal-evidence/` ghi lại baseline trước remediation cho Dealer và PDP. Kiểm chứng sau sửa dùng runtime DOM, kích thước section/card, số item, scrollWidth và test thao tác độc lập; ảnh baseline không được dùng để tuyên bố UI Nuxt sau sửa.
5. Audit ban đầu không chạy test suite; remediation chạy test Catalog/contract liên quan và typecheck. Chỉ dùng local runtime/preview; không gọi dữ liệu production trực tiếp và không deploy. Gallery/Video rỗng tại runtime nên không có rail card để so sánh ở hai trang đó.

## Bằng chứng ảnh

- **Baseline trước sửa:** Dealer PHP/Nuxt: `mobile-horizontal-evidence/dealer-php-390.png`, `dealer-nuxt-390.png`; PDP PHP/Nuxt: `pdp-php-390.png`, `pdp-nuxt-390.png`.
- **Sau sửa, cùng viewport 390 px:** Dealer [PHP](mobile-horizontal-evidence/remediation-20261030/dealer-testimonials-php-390.png) / [Nuxt](mobile-horizontal-evidence/remediation-20261030/dealer-testimonials-nuxt-390.png); PDP [PHP](mobile-horizontal-evidence/remediation-20261030/pdp-applications-php-390.png) / [Nuxt](mobile-horizontal-evidence/remediation-20261030/pdp-applications-nuxt-390.png); Catalog [PHP](mobile-horizontal-evidence/remediation-20261030/catalog-applications-php-390.png) / [Nuxt](mobile-horizontal-evidence/remediation-20261030/catalog-applications-nuxt-390.png).

Ảnh hậu remediation cho thấy card kế tiếp lộ ở cạnh phải và rail giữ một hàng ngang ở cả ba section. PDP/Catalog có chênh lệch copy hoặc tỷ lệ khung rất nhỏ; Dealer còn khác kiểu chữ tiêu đề (Nuxt dùng sentence case, PHP viết hoa) và bề mặt card. Các điểm này không làm rail quay về dạng dọc nhưng chưa phải visual parity.

## Remediation complete

Các finding MHR-01 đến MHR-04 đã được sửa và kiểm tra local. 22 test liên quan Catalog/filter/category route PASS; QA độc lập PDP và Dealer PASS. Root đo trực tiếp PHP/Nuxt Catalog rail và nav tại viewport 320/390/768/992; document width không vượt viewport. `git diff --check` cho ba Vue component sạch.

Ghi chú lịch sử: lượt kiểm tra ban đầu báo `npm run typecheck` fail do thiếu type `process` (`TS2591`) trong WIP `frontend/nuxt.config.ts`. Trạng thái sau remediation là **PASS** sau khi bổ sung `@types/node` và chuẩn hóa biến `NUXT_PREVIEW_MEDIA_PROXY` thành boolean. Kết quả cập nhật và các lần chạy liên quan ở [REMEDIATION-STATUS-20261030.md](REMEDIATION-STATUS-20261030.md). Không deploy, commit hay stage; production không bị tác động.
