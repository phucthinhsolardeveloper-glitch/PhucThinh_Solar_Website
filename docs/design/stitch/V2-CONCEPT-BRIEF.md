# V2 — Design exploration trên clone V1 Phúc Thịnh Solar

Tình trạng: thiết kế phương án, **không sửa V1, không sửa production, không tự merge Nuxt**.

## Đầu vào không được thay đổi
- Site source: https://phucthinhsolar.com
- Stitch Canvas: https://stitch.google.com/projects/5456517467698612459
- V1 source screenshot đúng VI và HTML final đúng font/asset (link theo SCREEN-INVENTORY.md).
- Thương hiệu: vàng kim + navy + trắng; logo, hình sản phẩm, danh mục, quy trình nghiệp vụ giữ theo nguồn.
- Bố cục hình học vuông vức, bo nhẹ, tính nhất quán với trang chủ/danh sách/chi tiết sản phẩm hiện hành.

## Hướng thiết kế chung: Precision Industrial
Một thiết kế số cho doanh nghiệp giải pháp điện NLMT: đáng tin, kỹ thuật chuẩn xác, thương mại rõ ràng, không bán hình ảnh AI. Tham chiếu tinh thần sản phẩm của Apple: điều hướng trực giác, phân cấp mạnh, tương tác mượt vừa đủ, tỉ lệ và khoảng trắng chỉn chu. Không copy kiểu dáng Apple, không dàn đều thẻ/bóng đổ, không hiệu ứng sặc sỡ.

## Hai hướng khám phá từ **cùng V1**, không tạo theme tùy tiện

### Concept A — Technical Premium (mặc định)
- UI chính xác như catalog kỹ thuật cao cấp; nhất quán grid/card/detail/specification/CTA.
- Hero thông điệp sắc nét và CTA rõ, overlay có tương phản tốt.
- Heading, subtitle, tags, form, bảng thông số có vai trò rõ; giảm diễn đạt lặp.
- Nổi bật thông số sản phẩm và độ tin cậy hỗ trợ kỹ thuật.
- Chủ động tối ưu responsive 390 px nhưng giữ đủ tính năng.

### Concept B — Calm Commerce
- Chú trọng hành trình tra cứu sản phẩm → kiểm tra thông số → tư vấn, như một storefront doanh nghiệp uy tín.
- Cấu trúc thông tin tối ưu thao tác: filter/sort/search/product card/PDP/gallery/quick inquiry.
- Tăng khoảng trắng có kỷ luật; hierarchy rõ, ít viền không cần thiết.
- Không đổi tên, ảnh, giá, mã, thông số sản phẩm; các thành phần source không kiểm chứng phải đánh dấu MISSING thay vì sáng tác.

## Quy định không thể phá vỡ
- V1 phải giữ nguyên để so sánh trước/sau.
- Dùng hình/asset thật; không dùng stock/AI tạo nhân vật và công trình giả.
- Giữ nguyên nội dung tiếng Việt, không tự dịch tiếng Anh.
- Mỗi cải tiến phải là UX hypothesis đo được: tốc độ định vị sản phẩm, độ đọc thông số, số thao tác liên hệ, tính khả dụng mobile, accessibility.
- Giữ 100% các chức năng hiện hữu; không cắt section hay hide CTA vì trông đẹp hơn.
- Cỡ bo nhẹ thống nhất (tham chiếu gần 4–8px), ít shadow, không nền tím/gradient.
- Motion: 120–240ms cho hover/focus/tab/accordion thích hợp, ưu tiên giảm giật/CLS và prefers-reduced-motion.
- Tabs, search, filter, compare, carousel, gallery, contact form, mobile nav có state riêng; không chỉ vẽ trang tĩnh.

## Phạm vi V2 ưu tiên
1. Home desktop/mobile: hero, product sections, giải pháp, process, trust, đại lý, form, footer.
2. Category/listing desktop/mobile: tìm, lọc, sắp xếp, sản phẩm, trạng thái đã chọn.
3. Product detail desktop/mobile: gallery, ưu tiên mã & thông số, giá/CTA, bảo hành, liên quan, sticky CTA.
4. Contact/Dealer: form, trạng thái, thông tin liên hệ, mobile.

## Handoff của mỗi hướng
- Màn desktop+mobile, bản so sánh cụ thể với V1.
- Design tokens font/size/line-height/grid/spacing/color/radius/motion + component states.
- Không được merge/deploy từ Stitch; Nuxt triển khai riêng sau khi chọn concept.
