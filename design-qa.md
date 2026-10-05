# Design QA — menu danh mục sản phẩm — 03/10/2026

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
