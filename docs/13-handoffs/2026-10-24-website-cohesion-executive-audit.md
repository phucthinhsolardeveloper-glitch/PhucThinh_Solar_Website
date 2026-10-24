# Executive audit: website cohesion và storyline

- Ngày audit: 2026-10-24
- Worktree: `/home/admin01/src/pts-ui-cohesion-20261024`
- Branch/commit đã kiểm: `feature/pts-ui-cohesion-20261024` / `c1a3c04`
- Phạm vi: audit read-only, không thay đổi runtime, DB, Admin, production hay homepage đã duyệt.
- Kết luận ngắn: navy/gold, homepage product-first, catalog và PDP đã có nền tốt; vấn đề còn lại là website chưa dẫn người dùng qua một câu chuyện thống nhất và chưa chứng minh đều tay bằng dữ liệu/ảnh thật.

## 1. Phương pháp và ranh giới bằng chứng

### Đã kiểm từ repository

- [AGENTS.md](../../AGENTS.md), [public UI/UX design system](../02-development/public-ui-ux-design-system.md), [frontend guide](../02-development/frontend-guide.md), [project status](../00-project/status.md), và các handoff PDP/solution/technical debt gần nhất.
- Shared shell: `domains/phucthinhsolar.com/public_html/templates/index.php:4-47`, `templates/layout/header.php:12-75`, `templates/layout/footer.php:32-228`, `templates/layout/menu.php`.
- Homepage: `templates/index/index_tpl.php:132-376`.
- Catalog/listing: `templates/product/product_tpl.php:69-104`, `templates/product/product_list_tpl.php:12-70`.
- PDP: `templates/product/product_detail_tpl.php:30-72, 95-145, 740-890` và các helper structured specs/content blocks.
- Solution: `templates/news/service_tpl.php:6-74`, `templates/news/service_detail_tpl.php:18-120`.
- Project: `templates/news/project_tpl.php:2-118`, `templates/news/project_detail_tpl.php:1-163`.
- About: `templates/about/about_tpl.php:11-222`.
- News: `templates/news/news_tpl.php:1-44`, `templates/news/news_detail_tpl.php:5-134`.
- Contact: `templates/contact/contact_tpl.php:99-218`.
- CSS ownership: `assets/css/design-tokens.css`, `flat-surface-system.css`, `header-v3.css`, `home-story-v4.css`, `product-category-v3.css`, `product-detail-v2.css`, `solution-hub.css`, `project-hub.css`, `about-v2.css`, `dealer-v2.css`, `news-hub.css`, `contact-v3.css`, cùng legacy `style.css`/`theme-v2.css`.
- Existing local artifacts: `artifacts/ui-review-20261024/` gồm cặp before/after đã có và `qa-results.json`. Đây là artifact có sẵn; audit này không tạo screenshot browser mới.

### Đã đối chiếu public read-only

Ngày 2026-10-24, HTML công khai được mở read-only tại:

- [Trang chủ](https://phucthinhsolar.com/)
- [Sản phẩm](https://phucthinhsolar.com/san-pham/)
- [Giải pháp](https://phucthinhsolar.com/giai-phap/)
- [Dự án đã thực hiện](https://phucthinhsolar.com/du-an-da-thuc-hien/)
- [Giới thiệu](https://phucthinhsolar.com/gioi-thieu/)
- [Đại lý](https://phucthinhsolar.com/dai-ly/)
- [Tin tức](https://phucthinhsolar.com/tin-tuc/)
- [Liên hệ](https://phucthinhsolar.com/lien-he/)

Public crawler ghi nhận thời điểm khác nhau giữa các URL (trang chủ/liên hệ trong ngày; giải pháp khoảng một tháng; đại lý khoảng một năm). Vì vậy HTML public chỉ được dùng làm tín hiệu tham chiếu, không dùng để kết luận local branch hay production đã đồng nhất. Không có claim/certificate/dự án mới nào được suy ra từ HTML public.

### Giả thuyết cần verify sau khi có staging/browser runtime

- Tính nhất quán computed-style, overflow và crop theo 390/768/1440/1920px trên toàn route.
- Route nào đang phục vụ cache/DB cũ so với commit hiện tại; crawler đã cho thấy khả năng drift theo thời điểm.
- Chỉ số chuyển đổi, lead quality, search-to-product, add-to-cart và checkout completion hiện chưa có measurement baseline trong hồ sơ audit này.
- Các claim định lượng, chứng nhận, kho/chi nhánh, thời gian phản hồi và năng lực thi công phải được owner xác nhận lại trước khi dùng làm proof.

## 2. North star

> **Từ nhu cầu năng lượng cụ thể đến một phương án có thể tin, có thiết bị thật để chọn và có người đồng hành sau bàn giao.**

Website cần trả lời tuần tự bảy câu hỏi, không cần biến mọi trang thành landing page:

1. **Chúng tôi là ai?** Phúc Thịnh Solar là đơn vị phân phối, tư vấn và triển khai thiết bị/giải pháp năng lượng mặt trời — chỉ dùng wording đã được sếp duyệt.
2. **Tôi đang thuộc nhu cầu nào?** Gia đình, doanh nghiệp/nhà xưởng, chiếu sáng công trình, nông nghiệp, lưu trữ hoặc nhu cầu mua thiết bị.
3. **Giải pháp phù hợp ra sao?** Một landing solution giải thích bối cảnh, đầu vào cần khảo sát, cách chọn và CTA.
4. **Thiết bị nào tạo nên phương án?** Liên kết contextual từ solution sang category/product; PDP phải có specs thật, tình trạng thiếu dữ liệu rõ ràng và CTA phù hợp.
5. **Ai đã làm được việc tương tự?** Project/proof chỉ hiển thị record, ảnh, khách hàng, địa điểm, thời gian và kết quả đã xác minh; thiếu thì ẩn slot, không dựng placeholder như bằng chứng.
6. **Sau mua có gì?** Quy trình khảo sát → thiết kế → lắp đặt/vận hành → bảo hành/bảo trì, tách rõ phần đã cam kết và phần cần hỏi sales/kỹ thuật.
7. **Bước tiếp theo là gì?** Mua sản phẩm, yêu cầu báo giá, nhận tư vấn, đăng ký đại lý hoặc liên hệ hỗ trợ; CTA phải giữ đúng intent của visitor.

### Hai journey chính

| Visitor | Nhu cầu và đường đi đề xuất | Bằng chứng cần có | CTA chính |
|---|---|---|---|
| B2C: gia đình/chủ nhà | Home → nhu cầu gia đình/chiếu sáng → solution → category/PDP → form có context | ảnh/description thật, thông số, bảo hành, hướng dẫn lựa chọn, FAQ từ dữ liệu thật | Xem sản phẩm / Nhận tư vấn |
| B2B: doanh nghiệp/nhà xưởng/đại lý | Home hoặc menu → solution doanh nghiệp / hợp tác → năng lực/project → quy trình → lead form có quy mô/địa điểm | case thật, phạm vi cung cấp, năng lực phân phối, chính sách đại lý, owner xác nhận | Đặt lịch khảo sát / Liên hệ kinh doanh |

## 3. Ba tầng “thiếu câu chuyện”

### Tầng 1 — Định vị và journey

Homepage đã có trật tự sản phẩm → nhu cầu → giải pháp → quy trình → CTA (`index_tpl.php:174-374`), nhưng các trang đích chưa luôn trả lời “tại sao bước kế tiếp liên quan đến tôi”. Solution đã có CTA và equipment bridge (`service_detail_tpl.php:25-27, 83-115`), còn project/detail và news cần được nối lại bằng related solution/product/context phù hợp. About có story/timeline/people/trust/certificate (`about_tpl.php:12-198`) nhưng mỗi section vẫn là destination rời; cần đặt vai trò rõ trong journey thay vì thêm section mới.

### Tầng 2 — Thông tin thật, ảnh thật, uy tín, CTA

Code có fallback và điều kiện ẩn khi thiếu dữ liệu — đây là nền đúng (`about_tpl.php:25-36, 51-76, 114-198`; `service_detail_tpl.php:63-78`; `product_detail_tpl.php:63-72`). Tài liệu status cũ ghi một số trang chính sách còn rỗng, nhưng kiểm tra public trực tiếp ngày 24/10/2026 cho thấy các URL chính sách mới như `/ho-tro/chinh-sach-giao-hang-va-kiem-hang/` và `/ho-tro/chinh-sach-bao-hanh-bao-tri-va-ho-tro-ky-thuat/` trả HTTP 200 và có nội dung. Cần QA lại nội dung/owner và redirect URL cũ, **không được kết luận rằng 5 trang chính sách hiện tại rỗng**. Dữ liệu sản phẩm/chứng nhận/project vẫn phải kiểm theo bản DB dùng để release. Visual polish không thay thế proof thật. Cần content operations: một nguồn định danh, owner, due date, trạng thái xác minh, và fallback không biến nội dung thiếu thành claim.

### Tầng 3 — Nhịp thị giác, component, shared shell

Shared token/focus/CTA đã được first pass xử lý, nhưng CSS vẫn nhiều thế hệ. Ví dụ solution category còn card `border-radius:12px`/shadow (`solution-hub.css:67-73`), project còn pill/gradient/shadow ở rule gốc (`project-hub.css:41,47-63,70-87`), About CTA còn radius 9px (`about-v2.css:103-107`), form dealer/contact tắt `outline` (`dealer-v2.css:42`, `contact-v3.css:47`) và legacy reset tắt outline global (`style.css:175`). Đây là drift ownership cần lát dọc shared component, không phải lý do để rewrite toàn site.

## 4. Evidence matrix — 10 điểm quan trọng nhất

| # / ưu tiên | Evidence đã kiểm | UX/visual/narrative impact | Owner | Fix đề xuất tối thiểu | Phụ thuộc dữ liệu |
|---|---|---|---|---|---|
| **1 — P0** Governance proof | Source/status cũ đánh dấu policy PARTIAL và SKU thiếu ảnh/mô tả/spec; kiểm tra public 24/10 xác nhận URL chính sách mới có nội dung, nhưng hai alias cũ `/ho-tro/chinh-sach-bao-hanh/`, `/ho-tro/chinh-sach-mua-ban/` trả 404 (xem xét 301/410). Chứng nhận/video/project phải xác minh trước khi làm proof. | Nếu release khi proof chưa xác minh, site có vẻ polished nhưng không đáng tin; rủi ro claim sai cao hơn rủi ro thẩm mỹ. | Sếp + marketing + kho/kỹ thuật | Lập proof register: claim, nguồn, người duyệt, ngày hết hạn, asset, route; slot thiếu thì ẩn/fallback trung tính. Chặn GO nếu claim P0 chưa owner sign-off. | Bắt buộc nguồn thật, không dùng AI/stock như bằng chứng. |
| **2 — P1** Shared shell drift | Layout dùng chung là đúng (`templates/index.php:4-47`) nhưng public HTML của solution/dealer có crawl date cũ khác trang chủ; local có nhiều lớp header/menu/footer và legacy CSS. | Người dùng đổi route có thể cảm nhận như đổi website; mất nhịp tin cậy và tăng chi phí QA. | Dev + QA | Snapshot cùng commit trên 8 route; gom invariant header/nav/footer/CTA về owner shared; phân loại stale public cache trước khi sửa. | Cần staging cùng DB/cache và browser runtime. |
| **3 — P1** Định vị chưa có một “reason to believe” xuyên site | Home lead nói phân phối/tư vấn/thi công (`index_tpl.php:162-167`); About, Dealer, Solution lặp các vai trò khác nhau; chưa có content contract cho một câu định vị duy nhất. | B2C khó biết nên mua thiết bị hay đặt hệ thống; B2B khó phân biệt năng lực phân phối/thi công/đại lý. | Sếp + marketing + sales | Chốt 1 positioning sentence + 3 proof pillars; map mỗi page H1/lead/CTA vào một pillar, không chép câu ở nhiều section. | Cần sếp duyệt ưu tiên B2C/B2B và claim được chứng minh. |
| **4 — P1** Cầu nối solution → product còn generic | Solution detail có “Thiết bị cho một hệ thống hoàn chỉnh” và link `/san-pham/` (`service_detail_tpl.php:83-105`), nhưng mapping theo nhu cầu/category chưa được quy định là bắt buộc. | Visitor đọc giải pháp xong phải tự tìm lại sản phẩm; mất intent và giảm khả năng lead chất lượng. | Sales + kho/kỹ thuật + dev | Mỗi solution khai báo 3–6 category/product relation; card nêu vai trò thiết bị; fallback `/san-pham/` chỉ khi relation chưa được duyệt. | Cần taxonomy/spec/product mapping thật. |
| **5 — P1** PDP mạnh về khung nhưng có risk “empty polished” | PDP đã có structured specs, content blocks, policy, video và fallback (`product_detail_tpl.php:30-72, 98-145`); status vẫn ghi thiếu ảnh/mô tả/spec ở nhiều sản phẩm. | Khung PDP đẹp nhưng thiếu thông số quyết định/bảo hành/ứng dụng sẽ làm người mua không dám chọn, đặc biệt B2B. | Kho/kỹ thuật + marketing sản phẩm + dev | Ưu tiên 20 SKU/nhóm có traffic; completeness gate theo profile; hiển thị “cần xác nhận” chỉ ở Admin, frontend ẩn block thiếu và CTA sang tư vấn. | Excel/master, ảnh thật, policy/certificate thật. |
| **6 — P1** Project chưa đủ case-study để chứng thực | Project detail vẫn chủ yếu là title/client/address/date/content + gallery/video (`project_detail_tpl.php:6-99`), related products có nhưng chưa có outcome/solution/process schema. | “Dự án đã thực hiện” chưa trả lời quy mô, bài toán, giải pháp, thiết bị và kết quả; không nên thêm số liệu khi chưa xác minh. | Sales + kỹ thuật + marketing | Tạo case-study content checklist bằng field hiện có trước; chỉ render các mảnh có dữ liệu; sau 2–3 case đủ chuẩn mới cân nhắc field chuyên trách. | Owner xác minh client, location, scope, media, outcome. |
| **7 — P1** About và trust chưa nối sang hành động | About có story/history/people/trust/compass/cert/CTA (`about_tpl.php:12-47, 51-198, 200-221`) nhưng CTA cuối chủ yếu là hotline/Zalo/primary chung. | Người dùng hiểu “chúng tôi” nhưng chưa được dẫn sang solution/product/project tương ứng với lý do tin. | Marketing + sales + dev | CTA contextual theo intent: “Xem giải pháp”, “Xem dự án đã xác minh”, “Liên hệ kinh doanh”; giữ một primary CTA mỗi band. | Cần quyết định proof pillar và link target. |
| **8 — P1** CTA/form không giữ context xuyên route | Home form có `data-home-quote` (`index_tpl.php:260-263, 353-375`); solution/PDP/contact dùng modal/form riêng (`service_detail_tpl.php:25-27`; `contact_tpl.php:135-188`). | Sales nhận lead thiếu ngữ cảnh hoặc visitor phải nhập lại nhu cầu; B2C/B2B CTA đang cùng một “Nhận tư vấn”. | Sales + dev | Chuẩn hóa lead context: source route, intent, solution/product, location/scale; label CTA theo intent; không đổi backend contract nếu chưa được duyệt. | Cần sales FAQ và field mapping; CSRF/reCAPTCHA/PII safeguards giữ nguyên. |
| **9 — P1** Media rhythm và crop chưa chứng minh cross-page | Design system yêu cầu product 1:1 contain, project 4:3, solution/contact 8:5; project CSS hiện dùng 16:10 (`project-hub.css:49-50`), solution card 44%/copy và `object-fit:cover` (`solution-hub.css:67-73`). | Cùng một hệ nhận diện nhưng ảnh bị crop/tỷ lệ khác nhau; page dài mất nhịp và bằng chứng thật có thể bị cắt. | Dev + marketing asset + QA | Inventory ratio theo field; project 4:3 theo contract; kiểm vùng an toàn 70–75%; không phóng ảnh nhỏ/không thay ảnh thật. | Cần asset master và xác minh ảnh đủ chất lượng. |
| **10 — P2** Legacy CSS/accessibility ownership chưa khép | `style.css:175` reset outline; dealer/contact lặp `outline:none`; solution/project/about còn radius/shadow/gradient legacy. | Regression khó đoán, keyboard focus và surface language lệch giữa route; mỗi lần polish phải vá thêm. | Dev + QA | Lát dọc 4 shared primitives: focus, primary/secondary CTA, breadcrumb, media/card; thêm test owner/cascade rồi mới giảm legacy. | Cần browser runtime, không chỉ grep; không cleanup mù. |

## 5. Visual priorities — giữ homepage đã duyệt

1. **Giữ** product-first homepage, navy/gold, catalog/product/PDP direction và URL public. Không bật lại/đổi cấu trúc homepage chỉ vì audit cohesion.
2. **Ưu tiên shared shell:** header, mega catalog, breadcrumb, footer, contact dock và CTA có cùng nhịp, focus, radius 2–5px, surface phẳng theo design system.
3. **Tạo nhịp bằng ảnh thật có mục đích:** product 1:1 contain; solution/contact 8:5; project 4:3; news 16:9; campaign 16:5. Slot thiếu dữ liệu phải collapse/fallback sạch.
4. **Mỗi trang một vai trò:** About xây trust; Solution giải thích phương án; Catalog giúp chọn; PDP giúp quyết định; Project chứng thực; News giáo dục; Contact chuyển đổi.
5. **Giảm lặp:** “tư vấn/thi công/hỗ trợ” chỉ xuất hiện ở đúng khối có bằng chứng/CTA, không lặp thành các band giống nhau.

## 6. Content slots thiếu và trách nhiệm

| Slot | Cần có | Người cung cấp | Người xác minh | Fallback khi quá hạn |
|---|---|---|---|---|
| Positioning | 1 câu định vị, 3 proof pillars, ưu tiên B2C/B2B | Sếp + marketing | Sếp | Giữ wording đã có, không thêm claim mới |
| Solution | bối cảnh, đầu vào khảo sát, lựa chọn, category/product relation, CTA | Marketing + sales + kỹ thuật | Sales/kỹ thuật | Hiện nội dung đã duyệt; ẩn relation chưa xác minh |
| Product/PDP | ảnh chính/gallery, 4 specs quyết định, warranty/policy, application | Kho/kỹ thuật + marketing sản phẩm | Kho/kỹ thuật | Ẩn block thiếu, CTA tư vấn; không điền placeholder |
| Project proof | client/scope/location/date/media/outcome nếu được phép | Sales + kỹ thuật | Sếp/owner dự án | Chỉ hiển thị field đã xác minh; không tạo case mới |
| About/trust | lịch sử, people, certificate, branch/warehouse | Marketing + sếp | Sếp | Ẩn certificate/slot rỗng; giữ story đã có |
| Lead/CTA | FAQ, intent, SLA phản hồi, route/source context | Sales | Sếp + sales | Dùng form hiện tại với context tối thiểu, không hứa SLA mới |
| Policy/support | Rà policy URL mới hiện có nội dung và mapping URL cũ 404; verify tính chính xác, hiệu lực, canonical/redirect | Ops/legal/owner + SEO | Sếp | Giữ chính sách đang duyệt; không tái sử dụng URL 404 trong journey; cần sign-off nếu đổi điều khoản |

## 7. Kết luận audit

Đợt nâng cấp nên được xem là **cohesion/content-proof program**, không phải redesign toàn site. Có thể đạt cảm giác modern/polished bằng cách làm rõ journey, lấp đúng proof slot, chuẩn hóa CTA/context và migrate component shared theo lát dọc. Chưa có bằng chứng cần đổi framework; quyết định công nghệ nằm ở [ADR options](../09-decisions/2026-10-24-website-frontend-architecture-options.md), còn lịch và cổng release ở [delivery plan](2026-10-24-website-upgrade-delivery-plan.md).


## 8. Browser QA bổ sung do điều phối viên thực hiện (24/10/2026)

- Chụp mới trực tiếp 10 public routes bằng Chromium ở **1440x900** và **390x844** (20 lượt HTTP 200). Lưu ở `artifacts/website-baseline-20261024/`, gồm 20 PNG và `audit.json`.
- Toàn bộ 20 viewport ban đầu không có `documentElement.scrollWidth > clientWidth`. Đây **không phải** full responsive pass (chưa có 768/1920, chưa đo tương tác hoặc scroll toàn trang).
- Ảnh homepage desktop/mobile: identity, header và hero có hướng thiết kế nhất quán; trên mobile hero chiếm gần toàn bộ viewport đầu và slide overlay phụ ở đáy làm giảm khoảng nhìn về sản phẩm bên dưới. Đây là **đánh giá UX** cần người duyệt thử, không phải lỗi kỹ thuật.
- Ảnh Giải pháp desktop/mobile: mobile trình bày một đoạn lead khá dài trước khi người xem tới các card nhu cầu; desktop card là lưới 5 cột với ảnh/độ dài copy khác nhau, cảm nhận mật độ lớn. Đề xuất prototype 2 hướng: copy compact + nhu cầu xuất hiện sớm; hoặc phân lớp intro + visible cards. Không sửa nội dung Admin trước approval.
- Ảnh Đại lý desktop/mobile: copy đoạn đầu dùng chương trình 2021–2025 và số liệu 2021; trên mobile hero -> tab -> form chiếm gần hết first fold. Phải hỏi sales/marketing update hoặc bỏ số liệu cũ, quyết định ưu tiên lợi ích hợp tác vs form. Không tự sáng tác số liệu thay thế.
- Trong JSON, trường `brokenImages` đếm `img` chưa load xong ở thời điểm DOM snapshot; **có thể là ảnh lazy-loaded chưa cuộn đến, không kết luận ảnh hỏng**. Cần kiểm network/scroll trước khi mở bug asset.
- Trang policy `/ho-tro/chinh-sach-bao-hanh/` và `/ho-tro/chinh-sach-mua-ban/` cũ trả HTTP 404; route mới bảo hành/giao hàng trả 200. Trạng thái tài liệu cũ về "5 policy rỗng" không còn dùng làm nhận định hiện tại.
- Ảnh chưa chứng minh sau-release code local. Chúng chỉ là bằng chứng thị giác hiện tại của public site, read-only. Không có prod mutation.
