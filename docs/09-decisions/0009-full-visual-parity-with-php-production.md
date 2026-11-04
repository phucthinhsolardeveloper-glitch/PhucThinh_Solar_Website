# ADR 0009: Khôi phục Full Visual Parity giao diện Nuxt theo chuẩn PHP Production

- Ngày: 2026-10-29
- Trạng thái: Đã duyệt theo chỉ đạo trực tiếp của Chủ project
- Owner: Chủ project Phúc Thịnh Solar
- Thay thế: ADR 0008 (hủy bỏ quy định tắt Hero trang chủ và cắt gọt section)

> **Phạm vi hiện hành (2026-11-04):** ADR này giữ nguyên yêu cầu về các section
> nghiệp vụ của trang chủ và đặt PHP làm baseline cho task migration/parity.
> “Full Visual Parity” không khóa các task creative redesign được giao rõ; ở đó
> có thể thay đổi presentation theo design language hiện hành trong khi giữ nội
> dung, dữ liệu, URL/SEO, luồng nghiệp vụ và accessibility. Xem ADR 0010.

## Bối cảnh

Theo chỉ đạo trực tiếp ngày 29-10-2026 từ chủ project: việc tự ý loại bỏ Hero banner slider và các section (giải pháp, quy trình, đại lý, giới thiệu) trên trang chủ là không đúng yêu cầu.
Yêu cầu cốt lõi của quá trình chuyển đổi Nuxt là:
- **Cấu trúc công nghệ**: Chuyển đổi sang kiến trúc hiện đại Nuxt 4 (Vue 3 + Tailwind CSS + Nuxt UI).
- **Giao diện người dùng (UI/UX) tại thời điểm ghi ADR**: task migration phải
  đối chiếu giao diện PHP production theo bộ ảnh tham khảo (13 ảnh tại
  `/mnt/c/Users/ADMIN/Downloads/screencapture-phucthinhsolar-*.png`). Đây là
  baseline migration/parity, không phải khóa thị giác cho redesign được giao
  sau này.

## Quyết định

1. **Trang chủ**:
   - Khôi phục hiển thị `HomeHero` (slider banner nhóm thiết bị nổi bật, đầy đủ ảnh, controls điều hướng, chỉ số slide, tiêu đề và nút CTA).
   - Khôi phục `HomeEditorial` bao gồm 4 section lớn:
     - "Giải pháp cho từng nhu cầu" (hệ thống tabbed solutions, chips thiết bị liên quan, ảnh visual và CTA).
     - "Đồng hành từ tư vấn đến vận hành" (5 bước quy trình từ 01 đến 05).
     - "Đăng ký đại lý" (khối thông tin, 3 ưu điểm hợp tác và 2 nút đăng ký/tư vấn).
     - "Về chúng tôi" (thông tin giới thiệu, 4 giá trị nổi bật).
   - Giữ đầy đủ `HomeCatalog`, `HomeCampaigns`, `HomeProductGroups` và `HomeContact`.
2. **Các trang con**:
   - Đối chiếu và tinh chỉnh layout, tabs, banner, bộ lọc và khối CTA theo đúng 13 ảnh chụp thực tế của từng trang (Catalog, Category, PDP, Solutions, Projects, News, About, Dealer, Contact).
