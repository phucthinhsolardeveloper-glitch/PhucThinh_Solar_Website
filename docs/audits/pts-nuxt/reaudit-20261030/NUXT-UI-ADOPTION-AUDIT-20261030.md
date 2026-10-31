# Đánh giá mức độ áp dụng Nuxt UI và đề xuất nâng cấp

- Ngày: 2026-10-30.
- Repo: `/home/admin01/src/Web_PTS_PHP`, branch `master`, working tree đang có WIP local.
- Phạm vi: inventory source Nuxt UI v4, các form/control public và nguyên tắc Apple Design dịch sang web. Không sửa code trong lượt audit này.
- Peer review: một sub-agent độc lập đã đọc source và đối chiếu đề xuất. Phiên này không có connector gọi Gemini trực tiếp; peer review là agent Codex, không được trình bày như kết quả của Gemini.

## Kết luận ngắn

Nhận xét **đúng: Nuxt UI hiện được dùng ít ở các luồng public**, dù nền tảng đã cấu hình đúng. `app.config.ts` đã ánh xạ `primary=gold`, `secondary=navy`, `neutral=slate`; `UApp` được bọc tại `app.vue`. Component public hiện tập trung vào `UButton`, `UIcon`, hai `UModal`, một `UTabs` và một `UBadge`. Các `UInput`, `USelectMenu`, `UDrawer` và mẫu component khác hiện xuất hiện trong UI lab, chưa thành control dùng chung ở luồng chính.

Trong `app/components`, source có 9 form, 14 file chứa field HTML native; phép đếm source thấy 38 `<input>`, 12 `<select>`, 6 `<textarea>` và 75 `<button>`. Đây là số node trong source, không phải số control hiển thị trên mỗi trang. Mẫu cho thấy Nuxt UI đang đảm nhiệm nhiều nút/modal, nhưng form fields và một phần navigation vẫn tự dựng bằng HTML/CSS.

Nuxt UI chưa được dùng ít vì cấu hình thiếu. Lý do chính là project cần giữ parity PHP, nhịp flat-surface và style riêng; tuy vậy, form semantics và một số overlay đang bị triển khai lặp lại, là nơi có thể tận dụng framework tốt hơn.

## Tiêu chí design áp dụng

Apple Design được dùng như nguyên tắc thiết kế web, không áp quy ước native iOS/macOS lên storefront:

- `accessibility.md › Vision/Mobility`: chữ dễ đọc, label rõ, hit area và phản hồi không phụ thuộc riêng màu.
- `layout.md › Visual hierarchy`: nội dung thích ứng theo độ rộng, thứ bậc và khoảng cách diễn tả quan hệ.
- `typography.md › Ensuring legibility`: chữ và trạng thái vẫn đọc được khi phóng to.
- `color.md › Best practices`: màu nhất quán theo vai trò, màu không gánh ý nghĩa duy nhất.
- `cross-platform.md › Conventions to check`: với web/Vue, semantic CSS tokens và component UI là cách ánh xạ; không cần mô phỏng app native.
- `buttons.md`, `search-fields.md`, `feedback.md`, `collections.md`, `motion.md`: action, tìm kiếm, phản hồi, rail/card và animation cần giải quyết đúng tác vụ, không thêm component chỉ để trang trí.

Các nguyên tắc này khớp UI contract nội bộ: form control có border/focus vì có nghĩa thao tác; card nội dung giữ phẳng; giảm motion khi được yêu cầu; rail ảnh/sản phẩm cần giữ cuộn ngang chạm được.

## Inventory và đánh giá

| Khu vực | Hiện trạng | Cơ hội |
|---|---|---|
| Nền tảng/theme | Nuxt UI v4 đã cài; `UApp`; semantic colors đã map trong `app.config.ts`. | Giữ cấu hình. Tránh import thêm framework component chỉ để tăng số lượng dùng. |
| Buttons/icons | `UButton` và `UIcon` đã có ở CTA và một số action. Nhiều nút header/modal vẫn là native button; một phần icon là SVG viết tay. | Chuẩn hóa icon action phổ thông bằng Lucide/UIcon và button style có hợp đồng; giữ SVG cho biểu tượng thương hiệu/solar đặc thù. |
| Modal/dialog/drawer | Hai public forms dùng `UModal`; header menu/search có focus/inert/scroll-lock logic tự quản. Catalog filter dùng native `<dialog>` với `showModal()`. `UDrawer` hiện là demo trong UI lab. | Thử `UDrawer` ở mobile menu hoặc catalog filter trên một luồng trước; đo focus restore, Escape/backdrop, body scroll, safe area và hành vi link. Không chuyển đồng thời cả hai overlay. |
| Forms/fields | Public forms chủ yếu dùng `label` + `input/select/textarea` HTML, mỗi nơi khai class và trạng thái riêng. Contact CTA đã dùng `UButton`. | Dùng `UFormField` cùng `UInput`, `USelect`, `UTextarea` ở một form pilot để thống nhất label/help/error/focus; giữ nguyên validation HTML, name/value, autocomplete, inputmode, `required` và API submit. Mở rộng sau khi style parity đạt. |
| Checkout địa chỉ | Hai select Tỉnh/TP → Phường/Xã; thay đổi selection ảnh hưởng quote. | Có thể thử searchable `USelectMenu` nếu danh sách native gây khó tìm. Đây là pilot có rủi ro cao hơn; phải giữ thứ tự địa chỉ hai cấp, cancel stale requests và xác thực selected value trước khi áp dụng. Không ưu tiên trước form tư vấn. |
| Tabs/content disclosure | `UTabs` dùng trong giải pháp trên trang chủ. FAQ/product content đang dùng `<details>`. | Giữ native `<details>`: behavior nhẹ, rõ và không cần custom state. Không thay FAQ bằng accordion chỉ để dùng thêm Nuxt UI. |
| Carousels/rails | Home hero có slider riêng; các rails mobile dùng CSS overflow/snap. | Giữ CSS rail ở danh sách ứng dụng/sản phẩm: thao tác touch tự nhiên và đã parity audit. Chỉ cân nhắc `UCarousel` cho hero nếu cần giải quyết cụ thể đồng bộ autoplay, pause/focus, pagination và reduced-motion; chưa thấy lợi ích đủ để thay ngay. |
| Feedback | Form dùng inline success/error và `role` thích hợp; cart có trạng thái action cục bộ. | Giữ lỗi/validation gắn gần field; không thay bằng toast tự biến mất. Có thể dùng `UAlert` nếu cần chuẩn hóa banner lỗi/success mà vẫn giữ live-region behavior. |
| Surfaces | Nhiều card/section được render bằng component riêng và Tailwind. | Không dùng `UCard` đại trà: contract yêu cầu bề mặt phẳng, không thêm shell/border/shadow chỉ vì component có sẵn. |

## Backlog đề xuất

### 1 — Ưu tiên cao: form field kit trên một form tư vấn

Thử `UFormField` + `UInput`/`USelect`/`UTextarea` trong `ContactLeadForm.vue`. Gắn nhãn, dấu bắt buộc, trợ giúp và lỗi theo cùng pattern; cấu hình appearance bằng class của design system, không dùng style mặc định không kiểm soát. Giữ API, CSRF/security state và HTML native validation hiện tại. Sau khi đo ổn ở mobile/desktop mới tái dùng ở `DealerLeadForm.vue` và modal form.

### 2 — Ưu tiên vừa: giảm duplicated overlay code

Chọn **một** trong `SiteHeader.vue` mobile drawer hoặc `CatalogPage.vue` filter dialog làm thử nghiệm `UDrawer`. Acceptance: trigger trả focus sau close, Escape/backdrop hoạt động, scroll nền được khóa và phục hồi, tab order đúng, không đổi route/filter semantics, mobile safe-area ổn. Header mega-menu desktop vẫn custom.

### 3 — Ưu tiên vừa/thấp: icon controls

Chuẩn hóa search/close/chevron/arrow/status phổ thông theo Lucide thông qua `UIcon` hoặc một wrapper nhỏ có kích cỡ/aria contract. Không thay logo, social brand marks hoặc icon minh họa kỹ thuật riêng.

## Những việc không nên làm

- Không thay toàn bộ native inputs/buttons chỉ để tăng tỉ lệ dùng Nuxt UI.
- Không dùng `UCard` bọc mỗi section; sẽ trái flat-surface contract.
- Không đổi FAQ `<details>` sang accordion nếu không có vấn đề vận hành.
- Không thay mọi rail CSS bằng carousel JS; làm tăng bundle và phức tạp focus/touch khi rail hiện tại đã hoạt động.
- Không đưa checkout vào đợt đầu của field migration; đây là luồng có state địa chỉ, quote và session.

## Mức độ tin cậy và việc tiếp theo

- Inventory: source-confirmed tại `frontend/app.config.ts`, `frontend/app/app.vue`, `frontend/app/components/**` và `@nuxt/ui` cài trong `frontend/`.
- Nguyên tắc bề mặt/spacing/accessibility: đối chiếu `docs/02-development/public-ui-ux-design-system.md`.
- Đây là đề xuất, chưa phải bug report: kiểm tra ảnh/browser chưa được thực hiện vì câu hỏi tập trung vào mức áp dụng framework và cơ hội nâng cấp, không vào parity của một trang cụ thể.
- Đợt UI tiếp theo nên bắt đầu bằng form tư vấn pilot và một overlay pilot; đánh giá ảnh/runtime trước khi mở rộng ra các trang khác.
