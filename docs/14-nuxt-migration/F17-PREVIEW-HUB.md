# F17 — Preview hub

Ngày 2026-10-26, preview-only.

## Phạm vi

`/migration-hub-preview` là landing SSR nội bộ, có `noindex`, dùng registry
thuần dữ liệu tại `frontend/shared/contracts/migration-preview-routes.ts` để
liệt kê 10 route preview đã tồn tại. Registry không thay đổi public routing.

Hub chỉ gọi GET `/_migration/catalog`. Liên kết PDP chỉ được tạo khi catalog
local trả về sản phẩm hợp lệ theo contract và dùng slug CMS thật; không có
fallback slug giả. News, project và solution detail chưa có preview tương ứng
nên không tạo sample link cho các loại đó.

`preview-links.mjs` chỉ cho phép path preview đã đăng ký và PHP fallback ở
localhost. Hub không nhận URL upstream từ request, không render CKEditor HTML,
không thêm POST hoặc form proxy.

## Kiểm chứng

Chạy `cd frontend && npm test`; test F17 kiểm tra registry xác định, không
takeover public route, query slug an toàn và PHP fallback localhost. Không chạy
`npm ci`, build, browser hoặc Docker trong task này.

