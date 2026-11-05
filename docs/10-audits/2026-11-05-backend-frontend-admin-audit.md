# Audit backend, API và khả năng tùy biến từ Admin — 2026-11-05

Phạm vi: storefront PHP (đang chạy production), DTO `api/pts_v1_*` + Nitro
`/_migration/*` (Nuxt, staging) và Admin. Branch `claude/backend-audit-20261105`
(gốc `e9400754`). Không deploy, không ghi dữ liệu staging/production.

## 1. Kết quả kiểm tra

| Hạng mục | Kết quả |
|---|---|
| PHP lint (`index.php`, `api/`, `libraries/`, `libraries/class/`) | sạch |
| 24 script hồi quy `scripts/test-pts-public-*.php`, news/project hub | PASS (tích hợp HTTP 69/69) |
| DTO công khai (site, home, catalog, PDP, extras, solutions, projects, news, about, dealer, contact, gallery, video, cart, checkout, search) | JSON hợp lệ, 2–32 ms trên DB local |
| Storefront PHP: trang chủ, danh mục, PDP, giỏ, tìm kiếm, liên hệ | 200, không lỗi PHP, không lỗi JS console |
| Nuxt production build, 15 route × 390/1440 | 1 H1/trang, không tràn ngang, không lỗi JS |
| Frontend `npm test` / typecheck / build | 494/494, sạch, thành công |

## 2. Đã gỡ — tính năng backend dư thừa

Tiêu chí: không còn DOM/caller trên storefront PHP, Admin, Nuxt, script test
hay nội dung CMS (đã quét DB local và bản clone production 2026-09-07).

| Đã gỡ | Vì sao chết |
|---|---|
| `api/product.php` + `GA_FRAMEWORK.loadTab`, khối `.paging-product` | Không template nào sinh `.paging-product`, `#load-product-tab`, `#loadmore` |
| `api/product2.php` + khối `.paging-product-category` | DOM bị gỡ khi redesign trang chủ (`b939d52e`, 2026-09-21) |
| `api/search_product.php` + `doSearchProduct` | Chỉ còn lời gọi đã comment từ baseline |
| `api/color.php` + handler `.color-pro-detail` | Không có DOM chọn màu từ baseline |
| `api/video.php`, `api/addons.php`, class `AddonsOnline` | `AddonsOnline` được khởi tạo nhưng không bao giờ gọi, nên `#video-select`/`#video-fotorama` không thể xuất hiện |
| `api/district.php`, `api/ward.php` + `loadDistrict`/`loadWard` | Không có nơi gọi; địa chỉ ba cấp đã bỏ (checkout dùng `ward-city.php`) |

Giữ lại vì còn dùng: `cart`, `comment`, `fillter_service`, `product3`,
`ward-city`, `shipping_quote`, `payment_vnpay`, toàn bộ `pts_v1_*`.
`/_migration/review` đã trả 410 từ trước (PDP gửi thẳng `pts_v1_review.php`).

Các loại nội dung Admin không còn renderer (`thong-tin-lien-he`, `dangkybaogia`,
`background`, `doitacgioithieu`, `gia-tri-cot-loi`, `hinh-anh-gioi-thieu`,
`slide-product`, `doi-tac-cong-nghe`, `gioi-thieu-san-pham`) đã được ẩn khỏi
Admin từ trước. Dữ liệu cũ của chúng vẫn còn trong DB production (6 logo đối
tác, 4 giá trị cốt lõi, 3 thông tin liên hệ...) — giữ nguyên, không xóa.

## 3. Lỗi đã sửa

- **Thumbnail ảnh WebP gốc** (`createThumb`): mọi ảnh upload `.webp` trả
  "NO IMAGE" (HTTP 200) qua `/thumbs/`. PHP né bằng cách tải ảnh gốc; Nuxt luôn
  gọi `/thumbs/` nên ảnh vỡ trên staging (2 sản phẩm, 4 ảnh thư viện ở DB local).
- **Fixture `test-project-hub.php`** thiếu `$func` mà template CTA cần từ 2026-09-16.

## 4. Giao diện Nuxt có chỉnh được từ Admin không

### Đã do Admin điều khiển

Header (logo, hotline, Zalo, menu danh mục/giải pháp/tin), footer (thông tin
doanh nghiệp, chính sách, kho, mạng xã hội, huy hiệu BCT), trang chủ (hero,
3 chiến dịch, giải pháp, quy trình, giới thiệu + 4 điểm, liên hệ, nhóm sản
phẩm), danh mục/PDP (toàn bộ dữ liệu sản phẩm, hero danh mục, CTA catalog,
tài liệu, bảo hành, đánh giá), giải pháp (intro, thẻ, nội dung CKEditor, thiết
bị, CTA), dự án, tin tức, giới thiệu, đại lý, liên hệ, chính sách, phương thức
thanh toán (`hinh-thuc-thanh-toan`), SEO từng trang.

**Mới nối trong đợt này:** popup quảng cáo trang chủ và favicon (trước đó Admin
bật nhưng Nuxt bỏ qua; popup đang bật ở bản clone production).

### Còn viết cứng trong Nuxt — đề xuất

| Vị trí | Nội dung | Đề xuất |
|---|---|---|
| Trang chủ `HomeCatalog` | 5 mắt xích năng lượng: tên, mô tả, ảnh | Lấy tên/ảnh từ danh mục sản phẩm Admin (ghép qua slug sẵn có), giữ text hiện tại làm fallback |
| Trang chủ `EnergyStageSection` | Minh họa dòng năng lượng | Giữ cứng (minh họa thiết kế, không phải nội dung marketing) |
| `CatalogMasthead` | 4 cam kết (Tư vấn, Hỗ trợ kỹ thuật, Chính sách, Giao hàng toàn quốc) | Đưa vào loại `cam-ket` có sẵn trong Admin |
| `SiteHeader` | Chip tìm nhanh | Lấy từ từ khóa Admin hoặc danh mục nổi bật |
| `SolutionFinder` | Lựa chọn "nơi dùng/mục tiêu" và ánh xạ sang thẻ giải pháp | Thêm trường phân loại cho bài giải pháp trong Admin |
| `HomeContact`, modal tư vấn | Danh sách chủ đề và gợi ý | Giữ cứng (microcopy form) hoặc gom vào cấu hình chung |
| Trang Giới thiệu | Ảnh showroom, tiêu đề "Thương hiệu đồng hành" | Chuyển ảnh showroom sang loại ảnh Admin |
| Admin `banner-top`, watermark tin tức | Nuxt chưa đọc | Chỉ làm khi chủ project muốn dùng lại (bản clone không có banner-top đang bật; watermark đang tắt) |

Microcopy giao diện (nhãn form, nút, thông báo lỗi, aria-label) không cần đưa
vào Admin.

## 5. Rollback

Mỗi hạng mục là một commit riêng trên `claude/backend-audit-20261105`; revert
commit tương ứng hoặc quay về `e9400754`. Endpoint đã gỡ có thể khôi phục bằng
`git checkout e9400754 -- <file>`.
