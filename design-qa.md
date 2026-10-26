# Design QA — menu danh mục sản phẩm — 03/10/2026

## Trang danh mục sản phẩm 07/10/2026 — desktop và mobile đạt

- Nguồn bố cục: ảnh mockup danh mục đèn
  `/mnt/c/Users/ADMIN/Downloads/1790391587621_9048970982745282477_g7263129555270226418_adfef13ccb7864ea559a794928b251c6.jpg`
  và ảnh đối chiếu layout 1905px do chủ project cung cấp.
- Local desktop 1905×1000: hero 250px, dải cam kết nền trắng tách khỏi hero,
  bốn mục trải đều; tab cân hàng; sidebar 340px; lưới bốn sản phẩm; ảnh vuông
  `contain`, nội dung card căn trái và hai CTA cùng chiều rộng. Không tràn ngang
  (`scrollWidth = viewportWidth = 1905`). Ảnh kiểm tra: `/tmp/pts-category-desktop.png`.
- Local mobile 390×895: hero co theo màn hình, dải cam kết thành hai cột, tab
  cuộn ngang, filter là drawer ngoài viewport khi đóng và lưới sản phẩm hai cột.
  Không tràn ngang (`scrollWidth = viewportWidth = 390`). Ảnh kiểm tra:
  `/tmp/pts-category-mobile.png`.
- Giữ nguyên dữ liệu thật, logic giá công khai/báo giá và hành vi giỏ hàng.
  Thay đổi này áp dụng từ template chung nên trang Đèn NLMT và các danh mục
  sản phẩm khác dùng cùng hệ bố cục.
- PHP 8.1 lint, HTTP local cho `/san-pham/` và
  `/danh-muc-san-pham/den-nlmt/`, cùng `git diff --check` đều đạt.
- Banner gốc `/san-pham/` dùng asset tạo mới
  `assets/images/site-visuals/catalog-products-hero-20261007-v2.png`: bố cục
  sản phẩm nằm ngang ở nửa phải, vùng trái dành cho HTML copy và focal point
  riêng `center 59%`. Đã kiểm tra ở desktop 1905×720 và mobile 390×895;
  banner 250/230px không tràn ngang, desktop giữ trọn cụm sản phẩm chính.

## Desktop 04/10/2026 — final result: passed

- Nguồn: `/mnt/c/Users/ADMIN/Pictures/Screenshots/Screenshot 2026-10-04 150434.png`, 978×468px, ảnh panel desktop.
- Bản triển khai: `tools/visual-tests/test-results/header-v3-desktop-product--70f28--layout-and-supports-search-desktop-chromium/desktop-catalog-garden-hover-1440.png`, panel 960×455px ở viewport 1440×1000 CSS px, Playwright Desktop Chrome, chụp 1× CSS. So panel với panel; ảnh nguồn có khoảng nền 14px hai bên nên không so trực tiếp các mép ảnh.
- Lần so đầu: `.playwright-mcp/menu-desktop-before-20261004.png` có panel 1180×588px, liệt kê cấp gốc cùng nhiều dòng con và thiếu ô hỗ trợ trong cột 3 — P1 về cấu trúc/mật độ. Đã đổi thành 4–4–2 lối tắt, bỏ các dòng phụ, thêm ô hỗ trợ, thu kích thước panel và chụp lại ảnh trên ở cùng trạng thái hover `Đèn sân vườn`.
- So toàn panel và vùng trọng tâm: font Be Vietnam Pro, phân cấp tiêu đề/mô tả/hàng mục, nhịp 3 cột, navy/gold, vạch chia và ô tư vấn đã gần mẫu. Không có ảnh raster cần tạo; dùng thư viện icon hiện hữu. Khác biệt còn lại chấp nhận được: icon Font Awesome không trùng nét minh họa trong mockup, panel render dữ liệu thật, chân phải là tư vấn thay ngôn ngữ theo chỉ đạo.
- Tương tác: hover/click/keyboard/Escape, lọc `Hybrid`, 10 URL category trả HTTP thành công, ô tư vấn mở form; test menu desktop/mobile/tablet 13 passed, 7 skipped. Test header không có page error; chưa đánh giá screen reader toàn diện.

## Cập nhật mobile 04/10/2026 — visual QA đạt với dữ liệu thực

- Ảnh đích: `/mnt/c/Users/ADMIN/Pictures/Screenshots/Screenshot 2026-10-04 142004.png` (drawer rộng khoảng 326px). Bỏ hàng ngôn ngữ theo chỉ đạo mới.
- Bản local đã đổi cây mobile sang accordion inline, chỉ mở sẵn tiêu đề Danh mục sản phẩm; nhóm con không tự bung. Có icon, thứ tự Giải pháp → Dự án → Giới thiệu → Kiến thức → Hợp tác, nút tư vấn ở đáy, chỉnh chiều rộng và khoảng cách cho màn hẹp. Vẫn lấy taxonomy/URL từ Admin nên số nhóm nhiều hơn ảnh đích.
- Ảnh local CSS 1× của drawer rộng 326px trong viewport 390×895:
  `tools/visual-tests/test-results/header-v3-mobile-drawer-ke-dd8d0--and-submits-product-search-mobile-chromium/mobile-drawer-390x895.png`.
  Ảnh viewport 320×740: cùng thư mục, `mobile-drawer-320x740.png`.
  Tablet 768×900: `.playwright-mcp/menu-mobile-768-final-20261004.png`.
- Đối chiếu trực quan cùng bề ngang 326px: logo/X, ô tìm, tiêu đề danh mục,
  icon/hàng nhóm, vạch chia khu điều hướng và CTA giữ thứ bậc/màu/khoảng cách
  sát mẫu. Khác biệt được duyệt: nhóm con đóng mặc định theo yêu cầu 04/10;
  taxonomy thật có nhiều nhóm hơn mẫu và có lối `Xem tất cả sản phẩm`, nên
  các mục phía dưới nằm thấp hơn. Bộ chọn ngôn ngữ đã bỏ.
- Kiểm thử tương tác: accordion mở/đóng, label danh mục đi tới landing,
  tìm kiếm, nút X, click vùng ngoài và mở lại đều hoạt động; ở 320px có thể
  cuộn tới Hợp tác mà không bị CTA che. Root panel không còn `aria-hidden`
  sai khi đang mở. Tablet 768px dùng cùng bố cục compact.
- `pnpm test -- tests/header-v3.spec.js`: 12 passed, 6 skipped theo project;
  PHP 7.4 lint, JS syntax, `git diff --check` đạt. Kết quả: **passed** cho
  phạm vi menu mobile/tablet; chưa làm đánh giá screen reader toàn diện.

## Nguồn tham chiếu

Ảnh đính kèm của chủ project: bố cục desktop mega-menu ba cột và drawer mobile
trên cùng một mẫu header. Nội dung ghi chú bên ngoài hai mockup trong ảnh không
phải thành phần giao diện.

## Đối chiếu triển khai

| Hạng mục | Mẫu | Local hiện tại |
|---|---|---|
| Desktop | Điều hướng sản phẩm mở panel nổi, nền trang phía dưới được làm tối | Có; panel nổi nằm ngay dưới header, header vẫn rõ và nền nội dung phía dưới được phủ scrim |
| Nội dung desktop | Tiêu đề, mô tả, ô tìm kiếm, nút đóng | Có; cùng thứ bậc, khoảng cách và màu navy/gold của website |
| Danh mục desktop | Ba nhóm có biểu tượng, mỗi nhóm chứa danh mục phù hợp | Có; dữ liệu/URL lấy từ danh mục đang hiển thị trong Admin, nhóm hiện tại suy ra theo tên |
| Đáy panel | Xem tất cả sản phẩm và nhận tư vấn | Có; đi tới landing hiện hữu và modal tư vấn hiện hữu |
| Mobile | Drawer có tìm kiếm, danh mục sản phẩm và các nhóm điều hướng | Có; giữ Mmenu/cây liên kết cũ, tìm kiếm hoạt động; danh mục mở theo panel đi sâu |
| Hành vi đóng | Nút X và vùng ngoài menu | Có; X nằm trong drawer trên cả trang gốc/submenu, reset về gốc khi đóng; desktop hỗ trợ hover/click/keyboard |

## Quan sát trực quan / khác biệt có chủ đích

- Giữ nguyên logo, header và năm nhóm điều hướng đã được project duyệt; không
  sao chép header giả lập trong ảnh mẫu.
- Không hardcode danh sách 4/4/3 mục trong ảnh: local có 10 nhóm sản phẩm cấp
  cao, vì vậy menu gom dữ liệu thực thành ba nhóm và giữ toàn bộ danh mục con
  hiện có để không làm mất lối vào hàng hóa.
- Dùng màu, font, kích thước control và radius theo design system của Phúc Thịnh
  Solar; không thêm hình minh họa/ảnh giả.
- Mobile dùng drawer Mmenu hiện hữu thay cho dựng một cơ chế drawer song song.
- Mobile không giống hoàn toàn mockup: mẫu cho thấy danh mục đang mở dạng
  accordion cùng các điều hướng khác; local dùng panel trượt theo cây taxonomy.
  Cách này giữ cây danh mục nhiều cấp gọn và URL hiện hữu, nhưng cần thêm một
  lần chạm để vào danh mục rồi mới chọn nhóm con. Đây là khác biệt chức năng có
  chủ đích, không phải lỗi hiển thị.
- Drawer mobile rộng khoảng 84% viewport, có lớp tối ở vùng trang phía sau; nút
  đóng đặt trong header drawer thay vì để hamburger biến hình nằm ngoài panel.
- Tên nhóm dài trên mobile xuống dòng thay vì bị rút gọn. Search ở panel gốc;
  người dùng quay lại panel gốc để tìm kiếm. Đóng từ bất kỳ panel nào reset
  drawer về gốc cho lần mở tiếp theo.

## Kiểm chứng local

- Desktop 1440×1000: rê chuột mở menu; rê chậm từ nút vào panel không làm menu
  tắt, rê khỏi panel đóng sau độ trễ; ba nhóm, search, Escape, click và Enter
  hoạt động; không tràn ngang.
- Mobile 390×844: drawer, search, nhánh danh mục, quay lại và nút đóng hoạt
  động; đóng từ submenu rồi mở lại trở về panel đầu; tên nhóm dài không bị cắt.
  Search submit đến `/tim-kiem?keyword=...`.
- `pnpm test -- tests/header-v3.spec.js`: 10 passed, 4 skipped (đúng các test
  chỉ dành cho desktop/mobile); `node --check` và `git diff --check` pass.
- Screenshot bằng chứng hiện tại: `.playwright-mcp/menu-audit-20261003-02-desktop-hover-final.png`,
  `.playwright-mcp/menu-audit-20261003-03-mobile-root-final.png`,
  `.playwright-mcp/menu-audit-20261003-05-mobile-products.png` và
  `.playwright-mcp/menu-audit-20261003-06-mobile-subcategories.png`.
- Browser có Google Translate tự dịch nội dung screenshot sang English; cấu trúc
  và tương tác được kiểm thử bằng DOM/test trên local. Chưa chạy screen reader,
  nên đây không phải chứng nhận accessibility đầy đủ.

## Kết luận hiện hành

Desktop đạt hướng bố cục và hành vi chính của mẫu. Mobile đã chuyển từ cơ chế
drill-down mô tả ở đợt kiểm thử ban đầu sang accordion inline như cập nhật
04/10 ở đầu tài liệu; các nhóm con đóng mặc định. Rule gom nhóm desktop dựa
trên từ khóa tên danh mục; nếu taxonomy đổi đáng kể, cần rà lại rule hoặc đưa
group key thành trường cấu hình Admin.

## Cập nhật rail danh mục đèn 07/10/2026

- Trang `/danh-muc-san-pham/den-nlmt/` hiển thị danh mục tổng theo thứ tự đã
  chốt: `Tất cả → Đèn Pha → Đèn Đường → Đèn Sân Vườn → Đèn UFO → Đèn Ốp Trần`.
- `Đèn Sân Vườn` hiện là `product_cat` trực tiếp dưới `den-nlmt`; 68 sản phẩm
  đã chuyển sang `/danh-muc-san-pham/den-nlmt/den-san-vuon/`. URL list cũ
  `/danh-muc-san-pham/den-san-vuon/` và các URL phân loại cũ đều chuyển 301
  về URL danh mục mới.
- Các nhóm đèn trực tiếp phát sinh thêm trong taxonomy sẽ nằm sau nhóm ưu tiên
  theo thứ tự dữ liệu hiện có. Sidebar của nhóm Đèn dùng cùng thứ tự; mục Sân
  Vườn là liên kết chuyển nhóm thay vì checkbox lọc chéo.
- Landing Đèn Sân Vườn hiển thị một nhóm sản phẩm tổng; bốn `product_cat` cũ
  được giữ ở trạng thái legacy để rollback/SEO nhưng không còn sản phẩm và
  không xuất hiện thành tab phân loại trên giao diện.
- Ở danh sách sản phẩm mặc định, thứ tự nhóm cũng dùng cùng rank trên: sản
  phẩm Đèn Pha xuất hiện trước Đèn Đường, Sân Vườn, UFO, Ốp Trần rồi mới đến
  nhóm khác; thứ tự `numb/id` vẫn được giữ bên trong từng nhóm. Khi người dùng
  chọn Sắp xếp theo tên, giá hoặc mới nhất, lựa chọn đó được ưu tiên.

## Mega-menu màn hình lớn 07/10/2026

- Mega-menu desktop dùng panel rộng tối đa 1200px, cách mép viewport tối thiểu
  32px, bo góc 12px và padding trong 32px theo bản chỉ dẫn 07/10/2026. Màn
  hình lớn hơn giữ panel ở ngưỡng này và tăng khoảng trắng ngoài thay vì kéo
  giãn chữ hoặc phóng menu quá khổ.
- Giữ bố cục ba cột; tiêu đề nhóm 18px, tên danh mục 16px/24px, hàng tối thiểu
  48px và ô tìm kiếm 400×48px. Mobile tiếp tục dùng menu trượt.
- Đã kiểm tra layout ở 1360, 1440, 1920 và 2560px; panel không tạo overflow ngang.

## Banner danh mục sản phẩm 08/10/2026

- Các route danh mục có trang riêng dùng fallback banner theo canonical path;
  banner Admin đã cấu hình vẫn được ưu tiên. Các nhánh Đèn Đường, Đèn Pha,
  Đèn Sân Vườn, Đèn UFO và Đèn Ốp Trần không còn rơi về ảnh sản phẩm đại diện.
- Banner giữ bố cục ảnh toàn chiều ngang, nền tối bên trái cho tiêu đề và ảnh
  đúng ngữ cảnh bên phải. Hero desktop co giãn theo màn hình trong khoảng
  250–322px; mobile giữ chiều cao gọn và không tạo tràn ngang.
- Ảnh mới cho Đèn Pha, Đèn UFO và Đèn Ốp Trần là ảnh minh họa giao diện, không
  dùng làm bằng chứng dự án/chứng nhận/năng lực thực tế.
- Taxonomy cấp 2 hiện có 28 bản ghi: 20 nhóm có sản phẩm, 4 nhánh Trụ đèn còn
  trang public nhưng chưa có sản phẩm, và 4 nhánh Sân Vườn legacy chuyển 301 về
  danh mục chuẩn nên không dựng banner riêng. Fallback banner được khai báo
  cho các route public đang dùng; các nhóm
  Hòa lưới, ESS, BMS/Rack, bộ sạc, dây cáp, giám sát và bảo vệ điện dùng ảnh
  chuyên biệt theo ngữ cảnh.
- Đã kiểm tra route banner trên desktop và mobile: 1 test pass mỗi cấu hình;
  PHP lint `product.php` và `product_tpl.php` pass.

## Bộ lọc nhu cầu sử dụng 08/10/2026

- Sidebar nhóm Đèn dùng đúng ba nhãn: `Dự án & công trình`, `Chiếu sáng gia
  đình`, `Sân vườn & cảnh quan`.
- Với danh mục `den-nlmt`, bộ lọc lấy taxonomy danh mục làm nguồn chính: Đèn
  Pha/Đèn Đường/Đèn UFO thuộc nhóm Dự án & công trình, Đèn Ốp Trần thuộc
  Chiếu sáng gia đình, Đèn Sân Vườn thuộc Sân vườn & cảnh quan. Không dùng từ
  khoá để kéo sản phẩm của nhóm Đèn sang nhóm khác.
- Audit dữ liệu local cho 203 sản phẩm đang hiển thị: 132 / 3 / 68 sản phẩm
  theo đúng ba nhóm trên. Query `is_need` dùng placeholder và được áp dụng cả
  lần tải trang lẫn API lọc AJAX; các key cũ vẫn được alias để không hỏng URL
  đã lưu.

## Icon rail danh mục 08/10/2026

- Rail danh mục dùng SVG chung với mega-menu nhưng đã bổ sung icon theo từng
  slug: bộ điều khiển MPPT/PWM, cáp & dây điện, giám sát/đo lường, phụ kiện,
  tiếp địa và vật tư phụ trợ không còn dùng icon bánh răng mặc định.
- Đã tách các nhóm từng bị trùng: Pin/Battery, ESS và BMS/Rack; biến tần bơm
  và máy bơm; trụ đường, trụ trang trí, cần/giá đỡ, móng/đế; đèn trụ cổng,
  đèn treo tường, đèn nấm và đèn cắm cỏ. Mapping theo slug giúp tên danh mục
  thay đổi vẫn không làm mất icon; chỉ cần rà mapping khi thêm slug taxonomy mới.

## Tiêu đề nhóm mega-menu 08/10/2026

- Ba tiêu đề `Chiếu sáng`, `Điện mặt trời` và `Thiết bị ứng dụng` là liên kết
  thật, có focus bằng bàn phím. Chúng lần lượt đi tới landing Đèn, Tấm pin và
  Bơm nước theo route đã chốt.

## Banner quy mô công nghiệp 08/10/2026

- Tấm pin, Biến tần, Pin lưu trữ, ESS, Hệ khung và Bơm dùng ảnh toàn cảnh
  công nghiệp: trang trại điện mặt trời, trạm điện, container lưu trữ, cánh
  đồng và hệ thống tưới quy mô lớn.
- Vị trí crop được đặt riêng theo chủ thể để giữ đường chân trời, toàn dãy
  thiết bị hoặc hệ thống công trình thay vì phóng vào một thiết bị nhỏ.
- Bốn route lưu trữ không dùng chung ảnh: trang Pin lưu trữ dùng ảnh BESS tổng
  quan; Hybrid dùng trạm inverter kết hợp lưu trữ; Pin/Battery dùng module pin
  lithium; ESS dùng khu container ESS. Mỗi ảnh vẫn giữ vùng an toàn bên trái
  cho tiêu đề banner.
- Nhóm Chiếu sáng cũng tách ảnh tổng quan và Đèn đường: ảnh tổng quan thể hiện
  khuôn viên có nhiều ngữ cảnh chiếu sáng; ảnh Đèn đường thể hiện đại lộ và dãy
  đèn kéo sâu về bên phải, không dùng chung asset.
- Nhóm Bơm tách ảnh theo chức năng: Biến tần bơm dùng trạm điều khiển điện bên
  cạnh dãy pin; Máy bơm dùng trạm bơm, đường ống và dòng nước trong cánh đồng.
- Nhóm bộ điều khiển sạc tách MPPT và PWM: MPPT dùng tủ điều khiển nhiều module
  trong hệ thống lớn; PWM dùng bộ điều khiển nhỏ trong mô hình điện mặt trời
  độc lập quy mô dân dụng.
- Nhóm Biến tần tách ảnh tổng quan hệ thống và Hòa lưới: trang tổng quan dùng
  nhiều inverter trong một hệ thống PV; Hòa lưới dùng nhà máy điện mặt trời,
  trạm biến áp và đường truyền tải.
- Nhóm Thiết bị điện & phụ kiện tách ảnh hệ thống dây/cáp/bảo vệ khỏi ảnh phụ
  kiện đèn đường, để nội dung banner khớp đúng nhóm sản phẩm thay vì dùng ảnh
  phụ kiện generic cho cả hai route.
- Nhóm Trụ đèn dùng bốn banner riêng: trụ đường phố, trụ trang trí, cần/giá đỡ
  và móng/đế trụ. Các banner đều giữ bối cảnh công trình và vùng an toàn bên
  trái cho nội dung HTML.
- Route cha Bơm dùng ảnh tổng quan toàn cảnh hồ chứa, kênh tưới, cánh đồng và
  dàn pin; route Máy bơm vẫn dùng ảnh trạm bơm/đường ống để không lặp banner.
- Bộ banner Đèn đã được thay đồng bộ theo ngữ cảnh: Đèn pha ở sân bãi thể thao,
  Sân vườn ở villa/cảnh quan, UFO trong nhà kho cao tầng và Ốp trần ở hiên nhà;
  mỗi ảnh giữ chủ thể chính phía phải để không bị tiêu đề che.

## Dọn ảnh danh mục cũ 08/10/2026

- Rail danh mục đã dùng SVG theo slug; 9 file `featured-category-*-v2.png`
  không còn là nguồn hiển thị nên đã gỡ khỏi upload local.
- Tham chiếu `photo` tương ứng ở `product_list` và `product_cat` đã được đặt
  rỗng. Banner danh mục dùng fallback `assets/images/site-visuals/` hoặc ảnh
  Admin riêng, không phụ thuộc bộ ảnh icon cũ.
- Bản sao phục hồi nằm tại `/tmp/pts-category-icon-cleanup-20261008/` trong
  phiên làm việc local.

## Ảnh preview khi chia sẻ danh mục 08/10/2026

- `og:image` và `twitter:image` của trang danh mục lấy cùng banner đang render,
  không lấy favicon hoặc ảnh icon danh mục đã dọn.
- Khi chạy qua Quick Tunnel, URL ảnh dùng hostname tunnel hiện tại để crawler
  bên ngoài tải được ảnh; môi trường local vẫn dùng `configBase`.

## Favicon và lưu website trên mobile 08/10/2026

- Favicon vẫn dùng đúng logo Phúc Thịnh Solar hiện có (`favicon-pts.png`), không
  thay bằng icon minh họa khác.
- Head bổ sung kích thước 32/192/512px, `apple-touch-icon` 180px và
  `site.webmanifest` để Android/iOS nhận cùng logo khi thêm website ra màn hình.
- Bản vuông `favicon-pts-mobile.png` được tạo từ chính logo hiện tại với nền
  trong suốt để tránh crop/méo mà không đổi nhận diện favicon.

## Nuxt Contact + Dealer Tailwind — 26/10/2026

- Nguồn Contact: `/mnt/c/Users/ADMIN/Downloads/screencapture-phucthinhsolar-lien-he-2026-10-26-11_11_53.png` (1920×3239).
- Nguồn Dealer: `/mnt/c/Users/ADMIN/Downloads/screencapture-phucthinhsolar-dai-ly-2026-10-26-11_13_13.png` (1920×4411).
- Bằng chứng local: `.migration-artifacts/contact-tailwind-1440.png` và `.migration-artifacts/dealer-tailwind-1440.png`.
- Chromium tại 390×844, 768×1024 và 1440×1000: cả sáu tổ hợp route/viewport trả HTTP 200, không tràn ngang, không ảnh lỗi và không console error.
- Mỗi route chỉ có một header, một footer, một h1 và một form; noindex là `noindex, nofollow, noarchive`.
- Form nhận input bằng label và chuyển việc submit về PHP để giữ CSRF/session hiện hữu; Nuxt không tự nhận POST.
- Header/Footer/Navigation/noindex/loading/error là phần dùng chung. Hero, quick-nav, form, product grid, support, testimonial, stats, spacing và responsive ordering vẫn thuộc từng trang.
- Contact hiển thị empty state bản đồ vì API local hiện trả `mapUrl` rỗng cho bốn chi nhánh; không bịa URL embed.
- Dealer giữ số liệu CMS hiện tại `5+`, `50+`, `5000+`; ba ảnh support dùng thumbnail PHP `480x300x1` để tuân thủ trần 8 MiB của media proxy.
- Kết quả: **PASS**; không còn lỗi P1/P2 trong phạm vi Contact và Dealer.
