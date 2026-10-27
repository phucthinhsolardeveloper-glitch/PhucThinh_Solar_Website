# Stitch — PROMPT 02: V2 nâng cấp từ bản clone

**Điều kiện chạy:** chỉ bắt đầu sau khi V1 clone đã được QA và khóa; duplicate V1 sang Section `02 / V2 Concepts`.

## Mục tiêu
Nâng cấp Phúc Thịnh Solar thành website doanh nghiệp phân phối thiết bị NLMT chuyên nghiệp, dễ mua hàng/dễ tư vấn, mượt và tinh tế. Giữ cấu trúc nghiệp vụ đã có. Cải thiện chất lượng thiết kế, KHÔNG chỉ đổi theme hoặc đảo bố cục tùy hứng.

## Ngôn ngữ thiết kế thống nhất
- Đặc trưng: **industrial premium / precise / trustworthy** (kỹ thuật chuẩn xác, thương hiệu tin cậy).
- Gold + Navy + warm whites, màu trạng thái có ý nghĩa; không tím, không gradient AI sặc sỡ.
- Hình khối vuông vức, bo nhẹ; nhất quán trên button/input/card/tab/table/modal.
- Typography hierarchy rõ, khoảng trắng có chủ đích, grid và canh hàng chuẩn.
- Hiệu ứng tinh tế: hover/focus có phản hồi tức thì; transition khoảng 120–240ms khi phù hợp; hạn chế parallax/blur/animation lớn; hỗ trợ reduced motion.
- Giao diện hướng enterprise storefront, tính thẩm mỹ kiểu Apple ở độ chỉn chu/chuyển động, không sao chép kiểu dáng Apple.

## Nâng cấp có kiểm soát
- Header: logo/menu/tìm kiếm/CTA rõ ưu tiên, mega menu đa cấp dễ quét.
- Homepage: kể câu chuyện rõ, hero CTA, nhóm sản phẩm thực, giải pháp, năng lực, dự án, đại lý.
- Listing: lọc có thứ tự (danh mục/thương hiệu/thuộc tính), trạng thái active, sort, sản phẩm, so sánh.
- PDP: ảnh, mã và thông số rõ, giá/CTA/contact, bảo hành/vận chuyển, liên quan, hành vi mobile sticky CTA.
- Content: kiến thức, giải pháp, dự án, giới thiệu đồng hệ thống component và spacing.
- Form đăng ký đại lý/liên hệ: validation, trạng thái gửi, mobile keyboard, lỗi rõ.
- Mobile: navigation/filters/CTA thân thiện thao tác, không ẩn mất chức năng.
- UX/performance/accessibility: contrast, keyboard navigation, loading/empty/error, ảnh thật tối ưu.

## Đầu ra yêu cầu
1. Tối thiểu 2–3 directions trên cùng V1, so sánh tiêu chí rõ ràng.
2. Phương án chọn gồm desktop/mobile cho 4 luồng chính (home, listing, PDP, contact/advisor).
3. Design tokens / typography / grid / spacing / component patterns / motion / DESIGN.md.
4. Danh sách thay đổi so với V1 và tác động UX, không xóa tính năng.
5. Prototype click-through và handoff cho Nuxt. Không tự merge/deploy.

**V2 phải nâng cấp 'chất lượng hệ thống thiết kế', không phá bản sắc hoặc dựng website chung chung.**
