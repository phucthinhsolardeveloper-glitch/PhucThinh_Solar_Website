# F02/F04 — Nuxt catalog preview

Đã tạo preview nội bộ `/migration-catalog-preview` dùng header/footer và cây danh mục từ `/_migration/site`, còn danh sách sản phẩm lấy qua Nitro adapter `/_migration/catalog` từ PHP `pts_v1_catalog.php`. Query upstream chỉ được phép `category`, `page`, `per_page`; không forward URL tùy ý, cookie hay header phiên.

## Phạm vi parity đã làm

- Validator strict cho DTO Phase A: category path 1–4 slug, sản phẩm/media public, phân trang và các cờ parity.
- Danh mục dùng canonical full slug chain từ `site.productCategoryTree`; điều hướng preview giữ route preview.
- Sản phẩm link rõ ràng về PHP legacy path; ảnh dùng media path thật, ảnh lỗi được ẩn an toàn.
- Không hiển thị giá. Trạng thái giá hiển thị rõ là liên hệ nhận báo giá, không suy diễn dữ liệu.
- Preview có `noindex`; không claim đã migrate `/san-pham/` hoặc nested public routes.

## Blockers còn lại

- `filters`: chưa có parity contract cho nhu cầu/công suất/giá/brand/search.
- `price`: chưa delegate qua `public_pricing.php`; chưa có giá/chiết khấu/tồn kho.
- `sort`: Phase A chỉ ổn định theo API, chưa chứng minh thứ tự merchandising của PHP.
- `quote`: chưa có luồng quote/form hoặc session business state.
- `route URLs`: route public `/san-pham/`, `/danh-muc-san-pham/.../` và canonical/redirect vẫn do PHP sở hữu.
- Không thể curl local PHP từ sandbox hiện tại; coordinator cần chạy smoke/build/browser QA ngoài sandbox.
