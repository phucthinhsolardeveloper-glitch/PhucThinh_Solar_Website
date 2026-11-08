# Kiến trúc đích: backend thế hệ mới cho Nuxt

Ngày 2026-11-07. Chủ website chốt: không chép backend cũ; dựng lại theo những gì frontend Nuxt mới
cần, cho tùy biến sâu hơn và thông minh hơn. Tài liệu này là hướng thiết kế. Từng phần vẫn qua
golden master trước khi thay PHP, để site không hỏng trong lúc chuyển.

Mục "Thứ tự" ở cuối là kế hoạch lúc khởi đầu, không còn là tiến độ hiện hành.
Xem [README.md](README.md) và [ma trận Admin](admin-capability-audit-2026-11-08.md)
cho trạng thái kiểm chứng sau commit Filament.

## 1. Dựng theo Nuxt, không theo file PHP

[phase-0/usage-map.md](phase-0/usage-map.md) cho thấy:

- 32 trong 37 file `api/*.php` là đường Nuxt đang gọi. Đây là phạm vi phải giữ đúng contract.
- `api/fillter_service.php`, `product3.php`, `ward-city.php` chỉ giao diện PHP cũ dùng. `comment.php`
  chỉ còn Admin và giao diện cũ. `cart.php` vừa Nuxt vừa giao diện cũ, cần xem kỹ.
- 14 trong 16 file `sources/*.php` là trang PHP cũ mà Caddy đã chuyển sang Nuxt. Chỉ còn `user.php`
  (`/account/*`) và `order.php` (POST `/gio-hang`) được gọi.
- Phần lớn `templates/` và `assets/` (Bootstrap, Owl, Slick, Fancybox, MagicZoom...) thuộc giao diện cũ.

Không chuyển những phần chỉ giao diện cũ dùng. Trước khi xóa khỏi PHP, đối chiếu log truy cập
production 30 ngày (chỉ đọc) để chắc không còn ai gọi.

## 2. API

| Lớp | Mục đích |
|---|---|
| `/api/v1/*` (Laravel) | Trả đúng contract `frontend/shared/contracts/public-*.ts` của 32 đường hiện có, để Nuxt đổi nguồn mà không đổi code. So sánh song song với PHP trước khi chuyển |
| `/api/v2/*` | Thiết kế cho Nuxt từ đầu, chỉ làm sau khi v1 chạy ổn |

Nâng cấp so với PHP:
- **Bộ nhớ đệm theo sự kiện.** Hiện Nitro giữ 60 giây rồi mới thấy thay đổi. Laravel gắn tag theo dữ
  liệu: Admin lưu sản phẩm thì xóa đúng tag của sản phẩm đó và danh mục chứa nó, Nuxt thấy ngay.
- **Gom một lần gọi.** Hiện mỗi trang gọi riêng `site` và dữ liệu trang. v2 trả kèm phần dùng chung
  có ETag, Nuxt chỉ tải lại khi đổi.
- **Một quy tắc cho một việc.** Ví dụ giá: PHP có ba chỗ tự tính, Laravel còn một (`PricingPolicy`).

## 3. Tùy biến sâu từ Admin

Hiện còn nội dung viết cứng trong Nuxt mà chủ website muốn tự sửa:
- 20 logo đối tác (`frontend/app/utils/partnerBrands.ts`)
- Lịch sử hình thành (`components/about/AboutHistory.vue`)
- Các chương câu chuyện 3D ở trang chủ
- Một số tiêu đề và câu dẫn

Mỗi lần đổi đều phải sửa code và deploy.

Đề xuất **section có kiểu** quản lý trong Filament:
- Mỗi loại khối (hero, dải logo, dòng thời gian, chương 3D, lưới sản phẩm, CTA) có schema riêng, kiểm
  tra dữ liệu khi lưu, và một component Nuxt tương ứng.
- Admin sắp xếp, bật tắt, soạn nháp, xem trước bằng chính Nuxt, rồi mới xuất bản. Có lịch sử và quay
  lại bản trước. Không cho nhập CSS hay JS tự do.
- Cài đặt chung có kiểu rõ: số điện thoại theo vai trò (bán hàng, kỹ thuật), giờ làm việc, liên kết
  mạng xã hội. Lỗi thẻ "Hỗ trợ kỹ thuật" mất trên production xảy ra vì số này nằm trong JSON không có
  ô nhập trong Admin. Kiểu này sẽ không lặp lại.
- Menu, SEO từng trang, chuyển hướng URL cũ: quản lý trong Admin, có kiểm tra trùng và vòng lặp.

## 4. Thông minh hơn

| Việc | Hiện nay | Đề xuất |
|---|---|---|
| Sức khỏe dữ liệu | Không có; lỗi chỉ lộ ra khi khách thấy | Lệnh `pts:data-health` và widget Filament: sản phẩm không ảnh (76 ở local), giá bất thường, slug trùng, danh mục mồ côi, sản phẩm thiếu nội dung. Chạy định kỳ, báo khi có lỗi mới |
| Tìm kiếm | Tìm "Blue Carbon" trên live ra 84 kết quả nhưng 20 kết quả đầu không có tên chứa cụm này | Chỉ mục FULLTEXT có bỏ dấu tiếng Việt, từ đồng nghĩa do Admin nhập, thống kê từ khóa không ra kết quả |
| Lead tư vấn | Lưu thô trong `table_contact` | Trạng thái xử lý, gán người phụ trách, gộp trùng theo số điện thoại, ghi trang nguồn (Nuxt đã gửi kèm), thông báo |
| Ảnh | phpThumb cắt khi có yêu cầu | Thư viện media có điểm lấy nét, tạo sẵn WebP/AVIF theo kích thước Nuxt dùng, bắt buộc chữ thay thế |
| Đầy đủ sản phẩm | `admin_product_completeness.php` | Điểm đầy đủ hiện ngay trong form Filament, chỉ rõ thiếu gì |
| Nhật ký | Rải rác | Ai sửa gì, lúc nào, trước và sau, cho mọi bản ghi Admin |

## 5. Thứ tự

1. Phase 2 tiếp: các phần trang sản phẩm cần (đã xong khối mô tả, giá, ảnh; còn thông số, cây danh
   mục, URL), kèm `pts:data-health` vì nó dùng ngay các quy tắc vừa chuyển.
2. Phase 3: `/api/v1/product-detail` và catalog trên Laravel, so song song với PHP.
3. Section có kiểu và cài đặt chung trong Filament, bắt đầu từ thứ đang viết cứng (logo đối tác, lịch sử).
4. Các API đọc còn lại, rồi tới phần ghi theo từng domain như [spec-review.md](spec-review.md).
