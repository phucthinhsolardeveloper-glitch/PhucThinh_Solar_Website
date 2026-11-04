# Ngôn ngữ thiết kế PTS — bản 2026-11

Trạng thái: **ĐANG ÁP DỤNG** — quyết định của chủ dự án ngày 2026-11-04.
Phạm vi: toàn bộ website công khai Phúc Thịnh Solar (Nuxt, `frontend/`).
Thay thế: các quy định về **bề mặt** (bo góc 0–5px, cấm kính mờ, cấm đổ bóng) trong `02-PTS-VISUAL-LANGUAGE.md`. Các quy định về dữ liệu thật, SEO, khả năng truy cập và nội dung kỹ thuật trong file đó vẫn giữ nguyên.

## 1. Tinh thần

- Sáng tạo tự do, không giới hạn layout, motion, hiệu ứng; mỗi trang được có bố cục và chuyển động riêng.
- **Đồng bộ nằm ở hệ thống, không phải ở việc làm mọi thứ giống nhau**: cùng màu, cùng thang bo góc, cùng chất liệu, cùng đường cong chuyển động, cùng cách viết chữ. Nhìn trang nào cũng phải nhận ra cùng một thương hiệu.
- Cảm giác mục tiêu: hiện đại, cao cấp, chuyên nghiệp, mượt, không giật.

## 2. Màu

| Vai trò | Giá trị | Ghi chú |
|---|---|---|
| Navy chủ đạo | `--color-navy-950` (#061537) → `navy-800` | Nền hero, băng CTA, khối tóm tắt, footer |
| Vàng kim | `--pts-gold-gradient` | **Mọi** nền vàng (nút, huy hiệu, icon nổi) dùng gradient, không dùng vàng phẳng |
| Vàng chữ / icon | `gold-300` trên nền navy, `gold-600/700` trên nền sáng | |
| Nền trang sáng | `#fff` và `#f6f8fb` | Section xen kẽ trắng / xám rất nhạt |
| Chữ phụ | `#52627a` (sáng), `#c3cfdf` (trên navy) | |

Không dùng tím, neon, màu ngoài bảng.

## 3. Chữ

- Font: Be Vietnam Pro (đã preload 400/700/800 + fallback khớp số đo — không đổi font kiểu khác).
- Tiêu đề lớn: 800–850, `letter-spacing` âm nhẹ (−0.02 đến −0.045em), `text-wrap: balance`, cỡ dùng `clamp()`.
- **Viết dạng câu** (chỉ viết hoa chữ đầu). Không dùng nhãn eyebrow viết hoa giãn chữ, không đánh số 01/02 trang trí.
- Tiêu đề CMS gõ toàn chữ hoa → hiển thị qua `sentenceCase()` (`app/utils/sentenceCase.ts`, có danh sách tên riêng: Phúc Thịnh Solar, Blue Carbon, LED, CREE…).
- Thân bài: 16–19px, `line-height` 1.6–1.75.

## 4. Bo góc — chọn theo vai trò

| Token | Giá trị | Dùng cho |
|---|---|---|
| `--radius-btn` | 4px | Nút bấm chữ nhật |
| `--radius-sm` (`rounded-sm`) | 6px | Ô nhập, chip nhỏ, ảnh thu nhỏ |
| `--radius-md` / `--radius-card` (`rounded-md`) | 10px | Thẻ, khung ảnh, hộp ghi chú |
| `--radius-lg` (`rounded-lg`) | 16px | Khối nổi bật, khối tóm tắt, popover |
| `--radius-xl` (`rounded-xl`) | 24px | Thẻ hero, sân khấu trưng bày |
| `999px` | viên tròn | Thanh tìm kiếm, chip lọc, avatar, nút tròn |

Không tự đặt số lẻ (7px, 13px…). Cần mềm hơn → lên bậc, cứng hơn → xuống bậc.

## 5. Đổ bóng

| Token | Dùng cho |
|---|---|
| `--pts-shadow-soft` | Thẻ nằm trên nền xám cần tách nhẹ |
| `--pts-shadow-lift` | Trạng thái hover của thẻ/đường dẫn (đi kèm `translateY(-4…-6px)`) |
| `--pts-shadow-float` | Lớp nổi: popover, thẻ thông báo giỏ hàng, thẻ đè lên hero |
| `--pts-shadow-glow` | Hover nút vàng chính |

Bóng luôn ánh navy (không đen thuần), mềm, trải rộng.

## 6. Chất liệu

1. **Giấy trắng có lưới** — `.pts-grid-paper` (hoặc `GridSurface`): lưới 32px rất mờ, viền tan dần. Dùng cho section nền sáng mang tính trưng bày (chương giải pháp, phản hồi đối tác…). Không đặt dưới form dày.
2. **Navy sâu** — nền `navy-950` kèm quầng vàng `radial-gradient(… rgb(228 168 73 / 16–20%) …)` ở một góc. Dùng cho băng CTA, khối tổng tiền, FAQ, quyền lợi.
3. **Kính lỏng** — `.pts-glass` (navy trên ảnh) và `.pts-glass-light` (sáng trên lưới): blur 14–16px + saturate, viền sáng bên trong. Dùng cho thẻ/khối đặt trên ảnh hoặc nền chuyển động. Không xếp nhiều lớp kính chồng nhau.
4. **Ảnh điện ảnh** — hero tràn khung, lớp phủ gradient navy để chữ luôn đọc được, parallax nhẹ khi cuộn.
5. **Ghi chú** — `.pts-note` (sáng) / `.pts-note-dark` (navy) thay cho hộp viền trái vàng kiểu cũ.

## 7. Nút & hành động

- Nút chính: nền `--pts-gold-gradient`, chữ navy 800–850, cao 52–60px, thường kèm ô mũi tên navy bên phải (`→` trượt 4px khi hover).
- Nút phụ: viền mảnh navy 14–18% trên nền trắng, hoặc chữ + mũi tên.
- Hotline: icon tròn + nhãn nhỏ + số lớn tabular.
- Mọi phần tử bấm được: vùng chạm tối thiểu 44px, `:focus-visible` viền vàng 3px.
- Thao tác bất đồng bộ: chỉ khóa đúng phần tử đang xử lý, chừa sẵn chỗ cho thông báo (không đẩy layout), nút tự xác nhận tại chỗ (dấu tích) rồi trở lại.

## 8. Bố cục chung

- Khung nội dung: `.pts-container` (gutter 12/16/24px… theo breakpoint).
- **Breadcrumb**: luôn là dải `border-b border-slate-200/70 bg-slate-50/80` + `.pts-container`, đặt ngoài `<main>`, ngay dưới header.
- Nhịp section: `padding: clamp(64px, 8vw, 120px) 0`.
- Hero chi tiết (giải pháp, dự án, đại lý): tràn khung, **chiều cao cố định theo loại trang** để chuyển trang không co giãn.
- Danh sách nhiều trang (dự án, tin, sản phẩm): lưới đều, chạy ổn định khi có nhiều trang.
- Mỗi trang được tự do chọn layout (bảng ghép đôi, timeline, sân khấu trích dẫn, tường ảnh…) miễn dùng đúng màu/chữ/bo góc/chất liệu ở trên.

## 9. Chuyển động

- Đường cong duy nhất: `--pts-ease` = `cubic-bezier(0.22, 1, 0.36, 1)`; thời lượng `--pts-dur-fast` 0.2s, `--pts-dur` 0.35s, `--pts-dur-slow` 0.6s.
- Chuyển trang: View Transitions (header đứng yên, nội dung mờ chuyển). Trang có hiệu ứng riêng (chuyển giải pháp) tắt bằng `viewTransition: false`.
- Hiệu ứng “lỏng”: pill kéo giãn khi đổi lựa chọn, ảnh bay vào giỏ, số tiền chạy, thẻ nảy nhẹ — luôn ngắn, có mục đích.
- Bắt buộc tôn trọng `prefers-reduced-motion` (tắt hoặc chỉ giữ fade).
- Chỉ animate `transform` và `opacity`. Không animate kích thước gây xô layout.

## 10. Ổn định & hiệu năng (bắt buộc)

- CLS khi tải ≈ 0 (đo bằng script layout-shift trên bản build, không đo trên dev).
- Ảnh hero có kích thước cố định; nội dung động có chỗ chừa sẵn.
- Danh sách dài cuộn trong khung riêng khi đặt cạnh khối tóm tắt dính.
- Phân trang cuộn mượt về đầu danh sách (`data-results`); đổi bộ lọc giữ nguyên vị trí.
- Không flash: không bật/tắt `display` cho menu — dùng opacity/transform + độ trễ ý định hover.

## 11. Nội dung

- Không bịa số liệu, chứng chỉ, dự án, đánh giá.
- Không hiển thị con số đếm (số sản phẩm, số ảnh, số kết quả…).
- Trạng thái trống phải nói đúng lý do (chưa có nội dung / không khớp tìm kiếm / đang tuyển…) và luôn có lối đi tiếp.

## 12. Thành phần dùng chung (ưu tiên tái sử dụng)

`PublicCtaBanner` (băng CTA), `NotFoundExperience` (404/502), `CommerceSteps`, `AnimatedMoney`, `CartFeedback` + `useCartFeedback`, `SolutionsStory` (chia chương nội dung CMS), `ProjectWall`/`ProjectCard`, `MediaEmptyState`, `CatalogMenuIcon`, `.pts-note`, `.pts-glass`, `.pts-grid-paper`.

## 13. Checklist trước khi giao

- [ ] Màu, chữ, bo góc, bóng, chất liệu đều lấy từ token ở trên (không số lẻ).
- [ ] Không chữ hoa giãn chữ, không đánh số trang trí, không con số đếm.
- [ ] Breadcrumb đúng dải chuẩn; header/footer không bị đè.
- [ ] Desktop 1440 + tablet 820 + mobile 390: không tràn ngang, không lòi lề.
- [ ] CLS ≈ 0 trên bản build; thao tác không làm nhấp nháy phần khác.
- [ ] `prefers-reduced-motion` đã xử lý.
- [ ] `npm test` + `npm run typecheck` đạt.
