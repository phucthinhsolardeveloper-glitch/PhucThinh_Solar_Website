# Triển khai đợt đầu: Nuxt UI cho form và bộ lọc

- Ngày: 2026-10-30.
- Repo: `/home/admin01/src/Web_PTS_PHP`, branch `master`.
- Phạm vi: áp dụng đề xuất từ `NUXT-UI-ADOPTION-AUDIT-20261030.md` vào form tư vấn liên hệ và drawer lọc catalog.
- Production: không deploy; không thay đổi API, database, CMS hoặc cấu hình production.

## Thay đổi

1. `frontend/app/components/contact/ContactLeadForm.vue`
   - Chuyển label, input, select và textarea sang `UFormField`, `UInput`, `USelect`, `UTextarea`.
   - Giữ nguyên tên field/payload (`fullname`, `phone`, `email`, `subject`, `content`), dữ liệu lựa chọn, `required`, giới hạn độ dài, autocomplete, inputmode, kiểm tra hợp lệ native, trạng thái bảo mật và hàm submit hiện tại.
   - Khai báo label cho combobox nhu cầu; giữ kích thước điều khiển phù hợp form hiện có.
2. `frontend/app/components/catalog/CatalogPage.vue`
   - Đổi dialog lọc tự dựng thành `UDrawer` Nuxt UI, mở từ cạnh trái, giới hạn rộng 340px trên viewport mobile.
   - Giữ nguyên filter sidebar, URL/query semantics và thao tác đóng khi điều hướng từ một link trong drawer.
   - Dùng `UIcon` Lucide cho biểu tượng lọc và đóng drawer. Giữ bộ lọc desktop hiện tại.
3. `frontend/app/components/SiteHeader.vue`
   - Chuẩn hóa các biểu tượng tìm kiếm ở header, nút submit tìm kiếm và nút đóng hộp tìm kiếm thành Lucide `UIcon`, không đổi kích thước hit-area hoặc hành vi overlay.
4. `frontend/tests/commerce-preview-ui.test.mjs`
   - Cập nhật contract source test để kiểm tra `UDrawer` và semantics của luồng mới.

Không mở rộng đổi card, FAQ `<details>`, rail cuộn ngang, mega-menu header, checkout hoặc các form khác trong pilot này.

## Kiểm chứng

- Typecheck: `npm run typecheck` — PASS sau khi sửa kiểu `rows` thành số và giữ drawer ID trên phần nội dung, do `UDrawer` không nhận ID trong prop `content` đã khai báo kiểu.
- Test liên quan: `node --test tests/commerce-preview-ui.test.mjs tests/public-functional-surface.test.mjs tests/contact-contract.test.mjs tests/contact-hero-ui.test.mjs` — 28/28 PASS.
- Kiểm tra bổ sung `tests/site-shell-ui.test.mjs`: có 4 FAIL về composition/header breakpoint/theme; các assertion báo khác markup/class navigation hiện tại. Chưa kiểm chứng được các lỗi này có tồn tại ở baseline trước các WIP khác trong `SiteHeader.vue` hay không; thay đổi icon của đợt này không đổi markup navigation, nhưng không đánh dấu suite này PASS.
- `git diff --check` — PASS.
- Browser mobile tại viewport 390×844:
  - Form liên hệ render input 48px, textarea 156px; giữ `name`, `type`, `required` và label cho nhu cầu.
  - Combobox hiển thị 5 lựa chọn; chọn giá trị cập nhật lại nội dung đã chọn.
  - Drawer lọc mở đúng, rộng 340px; khóa cuộn body; Escape đóng và focus trở lại nút mở.
  - Icon tìm kiếm header render ở 19×19px; hộp tìm kiếm mở được và hiển thị nút đóng dùng UIcon.
- Console có CORS error từ widget dịch GTranslate (`translate-pa.googleapis.com`) trong browser context; không phải request của form/drawer Nuxt UI. Nuxt page contact báo 0 browser console errors khi tải riêng.

## Trạng thái backlog

| Hạng mục | Trạng thái | Ghi chú |
|---|---|---|
| Form tư vấn pilot với Nuxt UI | DONE | Giữ API/payload và native validation; typecheck/test/browser đạt. |
| Một overlay pilot với UDrawer | DONE | Catalog filter; Escape, focus restore, body scroll lock đã kiểm tra. |
| Icon action phổ thông | PARTIAL | Filter/close và search/search-close dùng `UIcon`; các icon tiện ích khác trong header chưa đổi. |
| Mở rộng field kit sang các form khác | NOT STARTED | Chỉ thực hiện sau khi chủ dự án duyệt rollout tiếp theo và xác định owner/field contract của từng form. |

Đây là kết quả của pilot, không phải tuyên bố toàn bộ public UI đã chuyển sang Nuxt UI hoặc đã đạt visual parity toàn website. Thay đổi vẫn là working-tree WIP; chưa commit, push hay deploy.
