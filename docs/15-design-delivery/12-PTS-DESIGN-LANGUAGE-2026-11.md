# Ngôn ngữ thiết kế PTS — bản 2026-11

Trạng thái: **ĐANG ÁP DỤNG** — quyết định của chủ dự án ngày 2026-11-04; được đồng bộ qua ADR 0010.
Phạm vi: toàn bộ website công khai Phúc Thịnh Solar (Nuxt, `frontend/`).
Thay thế: các quy định thị giác cũ về flat-only surfaces, radius 0–5px, cấm glass/shadow, breadcrumb không viền và giới hạn motion trong `AGENTS.md`, `docs/02-development/public-ui-ux-design-system.md`, ADR 0001/0002/0003/0006/0007 và `02-PTS-VISUAL-LANGUAGE.md`. Các hợp đồng về dữ liệu thật, URL/SEO, bảo mật, khả năng truy cập, commerce và tỷ lệ media vẫn giữ nguyên nếu tài liệu này không sửa rõ.

## 1. Tinh thần

- **Không giới hạn sáng tạo**: không có quy tắc nào khóa layout, motion, hiệu
  ứng, chất liệu hay cách kể chuyện; mỗi trang được có bố cục và chuyển động
  riêng, được phá cách so với giao diện cũ. Các mục bên dưới là **từ vựng chung**
  để mọi trang nói cùng một giọng thương hiệu, không phải hàng rào. Thứ duy nhất
  không được hy sinh: trang phải chạy mượt, đọc được, dùng được và nội dung là thật.
- **Đồng bộ nằm ở hệ thống, không phải ở việc làm mọi thứ giống nhau**: cùng màu, cùng thang bo góc, cùng chất liệu, cùng đường cong chuyển động, cùng cách viết chữ. Nhìn trang nào cũng phải nhận ra cùng một thương hiệu.
- Cảm giác mục tiêu: hiện đại, cao cấp, chuyên nghiệp, mượt, không giật.

## 1b. Cách nhận việc và làm việc (áp dụng cho mọi phiên AI: Claude, Codex, …)

Chủ dự án giao việc bằng định hướng ngắn ("bứt phá đi", "sáng tạo lên", "làm cho mượt"). Phiên nhận việc phải:

1. **Tự chủ đến cùng**: tự khảo sát code/dữ liệu thật, tự chọn hướng thiết kế táo bạo, tự triển khai, không hỏi lại những lựa chọn thiết kế — chọn phương án tốt nhất rồi làm.
2. **Đọc ý, không đọc chữ**: "đồng bộ" là cùng một ngôn ngữ, không phải làm mọi thứ giống nhau; "bỏ quy tắc cũ" là được phá cách, không phải bám tài liệu cũ.
3. **Kiểm chứng như người dùng thật**: chạy trên trình duyệt (desktop 1440 / tablet 820 / mobile 390), đo nhảy layout trên bản build, thử đúng thao tác (bấm, lọc, thêm giỏ, chuyển trang); sửa lỗi phát hiện được kể cả lỗi có sẵn.
4. **Phản hồi nhanh theo góp ý**: chủ dự án gửi ảnh chụp + một câu → sửa ngay đúng chỗ, rồi mở rộng sang chỗ tương tự trên toàn site.
5. **Giữ an toàn**: `npm test` + `npm run typecheck` đạt trước mỗi commit; commit nhỏ, mô tả lý do; không đẩy staging khi chưa được xác nhận; không ghi đè việc dở của phiên khác (kiểm tra `git status`/`git log` trước).
6. **Báo cáo bằng tiếng Việt, ngắn gọn**: đã làm gì, thấy lỗi gì, còn gì treo.

## 2. Màu

| Vai trò | Giá trị | Ghi chú |
|---|---|---|
| Navy chủ đạo | `--color-navy-950` (#061537) → `navy-800` | Nền hero, băng CTA, khối tóm tắt, footer |
| Vàng kim | `--pts-gold-gradient` | **Mọi** nền vàng (nút, huy hiệu, icon nổi) dùng gradient, không dùng vàng phẳng |
| Vàng chữ / icon | `gold-300` trên nền navy, `gold-600/700` trên nền sáng | |
| Nền trang sáng | `#fff` và `#f6f8fb` | Section xen kẽ trắng / xám rất nhạt |
| Chữ phụ | `#52627a` (sáng), `#c3cfdf` (trên navy) | |

Tránh màu accent ngoài nhận diện như tím/neon. Màu chức năng (thành công, cảnh
báo, lỗi), media và trạng thái dữ liệu có thể dùng màu semantic phù hợp, giữ
đủ tương phản và không cạnh tranh với navy/gold.

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
- **Breadcrumb**: dùng component và treatment phù hợp với composition hiện hành;
  đặt đúng thứ bậc điều hướng, không bắt buộc một dải nền/viền toàn cục.
- Nhịp section: `padding: clamp(64px, 8vw, 120px) 0`.
- Hero có thể tràn khung hoặc nằm trong lưới theo art direction; chọn chiều cao
  theo media, nội dung và viewport. Khai báo tỷ lệ/kích thước để tránh layout
  shift, không khóa một chiều cao toàn cục chỉ để làm các route giống nhau.
- Danh sách nhiều trang (dự án, tin, sản phẩm): lưới đều, chạy ổn định khi có nhiều trang.
- Mỗi trang được tự do chọn layout (bảng ghép đôi, timeline, sân khấu trích dẫn, tường ảnh…) miễn dùng đúng màu/chữ/bo góc/chất liệu ở trên.

## 9. Chuyển động

- Đường cong duy nhất: `--pts-ease` = `cubic-bezier(0.22, 1, 0.36, 1)`; thời lượng `--pts-dur-fast` 0.2s, `--pts-dur` 0.35s, `--pts-dur-slow` 0.6s.
- Chuyển trang: native View Transitions hoặc giải pháp tương đương khi có lợi
  cho định hướng; giữ trạng thái focus/scroll phù hợp và không làm header hoặc
  nội dung nhảy ngoài ý muốn. Route có hiệu ứng riêng cần tránh chạy chồng hai
  transition.
- Hiệu ứng “lỏng”: pill kéo giãn khi đổi lựa chọn, ảnh bay vào giỏ, số tiền chạy, thẻ nảy nhẹ — luôn ngắn, có mục đích.
- Bắt buộc tôn trọng `prefers-reduced-motion` (tắt hoặc chỉ giữ fade).
- Ưu tiên `transform` và `opacity`; hiệu ứng khác chỉ dùng khi đã kiểm tra chi
  phí render và không gây layout shift.

## 10. Ổn định & hiệu năng (bắt buộc)

- CLS khi tải ≈ 0 (đo bằng script layout-shift trên bản build, không đo trên dev).
- Ảnh hero có kích thước cố định; nội dung động có chỗ chừa sẵn.
- Danh sách dài cuộn trong khung riêng khi đặt cạnh khối tóm tắt dính.
- Phân trang cuộn mượt về đầu danh sách (`data-results`); đổi bộ lọc giữ nguyên vị trí.
- Menu và nội dung tương tác phải có trạng thái mở/đóng ổn định, hoạt động bằng
  bàn phím/chạm; tránh nhấp nháy hoặc phụ thuộc riêng vào hover.

## 11. Nội dung

- Không bịa số liệu, chứng chỉ, dự án, đánh giá.
- Không thêm số đếm trang trí hoặc số liệu không có nguồn. Count có thật (ví dụ
  kết quả tìm kiếm) được hiển thị khi giúp người dùng hiểu trạng thái/tác vụ.
- Trạng thái trống phải nói đúng lý do (chưa có nội dung / không khớp tìm kiếm / đang tuyển…) và luôn có lối đi tiếp.

## 12. Định hướng sáng tạo

### 12.1 Mô-típ thương hiệu (dùng lại để tạo “chữ ký” riêng)

- **Mặt trời & đường chân trời**: vòng cung vàng mọc/lặn, tia sáng xoay chậm, quầng vàng ở góc khối navy (đã dùng: 404, băng CTA).
- **Năng lượng chảy**: đường nối gradient vàng → navy giữa các bước, dòng sáng chạy dọc tuyến (timeline quy trình, thanh bước mua hàng).
- **Lưới kỹ thuật**: giấy trắng kẻ 32px như bản vẽ kỹ sư — nền cho nội dung kỹ thuật, so sánh, thông số.
- **Ngày ↔ đêm**: navy đêm cho hành động/cam kết, trắng ngày cho thông tin/đọc.
- **Tấm pin**: nhịp lưới ô vuông, phản chiếu ánh sáng chéo khi hover ảnh sản phẩm/dự án.

### 12.2 Thư viện bố cục (chọn theo nội dung, không lặp một kiểu cho mọi trang)

| Bố cục | Khi nào dùng | Đã có |
|---|---|---|
| Hero điện ảnh tràn khung + parallax | Trang chi tiết có ảnh thật | Giải pháp, dự án, đại lý |
| Bảng ghép đôi “nhu cầu → giải pháp” | Nội dung so khớp | Chương giải pháp |
| Timeline nối liền | Quy trình nhiều bước | Quy trình triển khai |
| Thẻ phương án so sánh | 2–4 lựa chọn | Hòa lưới / lưu trữ |
| Sân khấu trích dẫn + chân dung chọn | Phản hồi khách hàng | Hợp tác |
| Panorama / accordion mở rộng | Bộ sưu tập ít mục, ảnh đẹp | (gợi ý) |
| Tường ảnh / lưới đều | Danh sách nhiều trang | Dự án |
| Tạp chí (1 lớn + 3 cột phụ) | Tin tức | Kiến thức |
| Khối navy + khối trắng xen kẽ | Trang dài nhiều chương | Giải pháp |
| Sticky cột trái + nội dung cuộn | Tiêu đề chương + chi tiết | Quyền lợi đại lý, nhu cầu |

### 12.3 Thư viện hiệu ứng

- **Lỏng**: pill kéo giãn khi đổi tab/lựa chọn; thẻ bật nhẹ (`cubic-bezier(0.34,1.56,0.64,1)`) khi xác nhận.
- **Bay**: ảnh sản phẩm bay theo đường cong vào giỏ; phản hồi tại đích (giỏ nảy, huy hiệu bật số).
- **Chạy số**: số tiền/chỉ số đếm tới giá trị thật (không bịa số).
- **Hé lộ khi cuộn**: phần tử trượt 16–24px + mờ dần khi vào khung nhìn (một lần, không lặp).
- **Kính lỏng**: thẻ kính trên ảnh hero, quầng sáng đi theo con trỏ trên thẻ lớn (desktop).
- **Ánh sáng**: tia/viền gradient vàng chạy chậm quanh nút chính khi trang rảnh (tối đa 1 phần tử/trang).
- **Chuyển trang**: view transition; với chuỗi trang cùng loại (giải pháp) dùng chuyển cảnh trượt theo hướng.

Quy tắc: tối đa **một hiệu ứng “chủ đạo”** mỗi màn hình; còn lại là vi chuyển động phục vụ thao tác.

### 12.4 Định hướng theo loại trang

- **Trang chủ**: sân khấu năng lượng — hero điện ảnh, câu chuyện ngày/đêm, lối vào nhanh tới thiết bị/giải pháp.
- **Danh mục/sản phẩm**: rõ ràng, nhanh — ảnh nền sáng, thông số dạng bảng lưới kỹ thuật, thêm giỏ có phản hồi bay.
- **Giải pháp**: hành trình — chọn nhu cầu → hiểu cấu hình → thiết bị → tư vấn.
- **Dự án**: bằng chứng thật — ảnh lớn, địa điểm, tư liệu thi công, xem ảnh nền đen trung tính.
- **Kiến thức**: tạp chí — đọc thoải mái, mục lục, sản phẩm liên quan theo chủ đề.
- **Hợp tác/Giới thiệu**: niềm tin — chỉ số thật, quyền lợi, phản hồi đối tác, quy trình.
- **Giỏ hàng/Thanh toán**: yên tâm — bước rõ, tổng tiền nổi bật, kiểm tra hợp lệ tại chỗ, không xô layout.
- **Trang lỗi/trống**: có cá tính thương hiệu nhưng luôn dẫn lối đi tiếp.

### 12.5 Ý tưởng nên thử tiếp

- Mô phỏng dòng điện ngày/đêm cho trang giải pháp (tấm pin → inverter → pin → tải) bằng SVG động, đúng nguyên lý kỹ thuật.
- So sánh trước/sau trên ảnh dự án (thanh kéo).
- Bộ cấu hình nhanh: chọn nhu cầu → gợi ý nhóm thiết bị thật trong danh mục.
- Thẻ sản phẩm có ánh sáng chéo “tấm pin” khi hover; xem nhanh thông số không rời trang.
- Bản đồ điểm giao dịch dạng danh sách tương tác (không vẽ bản đồ Việt Nam).

## 13. Thành phần dùng chung (ưu tiên tái sử dụng)

`PublicCtaBanner` (băng CTA), `NotFoundExperience` (404/502), `CommerceSteps`, `AnimatedMoney`, `CartFeedback` + `useCartFeedback`, `SolutionsStory` (chia chương nội dung CMS), `ProjectWall`/`ProjectCard`, `MediaEmptyState`, `CatalogMenuIcon`, `.pts-note`, `.pts-glass`, `.pts-grid-paper`.

## 14. Checklist trước khi giao

- [ ] Màu, chữ, bo góc, bóng, chất liệu đều lấy từ token ở trên (không số lẻ).
- [ ] Không chữ hoa giãn chữ, không đánh số trang trí, không con số đếm.
- [ ] Breadcrumb đúng thứ bậc và treatment của composition; header/footer không bị đè.
- [ ] Desktop 1440 + tablet 820 + mobile 390: không tràn ngang, không lòi lề.
- [ ] CLS ≈ 0 trên bản build; thao tác không làm nhấp nháy phần khác.
- [ ] `prefers-reduced-motion` đã xử lý.
- [ ] `npm test` + `npm run typecheck` đạt.
