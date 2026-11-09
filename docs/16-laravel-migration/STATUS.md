# Trạng thái chuyển đổi Laravel

Đọc file này trước khi mở lại bất kỳ module nào. Mục đã **CHỐT** không đọc/rà lại, trừ khi có lỗi mới được báo.
Làm xong phần nào: test → commit → chuyển dòng sang CHỐT kèm commit → xóa file tạm của phần đó.

## Đang làm
Không có việc dở.

## Chưa làm (theo thứ tự)
- [ ] Task 5: chỉnh trang theo khối (Home + About POC)

## Không làm (đã kiểm chứng)
| Mục | Lý do |
|---|---|
| Màu/cỡ, tags, đa ngôn ngữ | Bản cũ đã tắt trong cấu hình hoặc 0 dữ liệu |
| Thành viên, OneSignal | 0 dữ liệu |
| Chứng nhận sản phẩm | 0 dữ liệu, trang công khai không đọc |
| Xuất/nhập JSON khối mô tả, chuyển HTML thành khối (bản cũ có) | Bỏ ngày 09/11 theo yêu cầu rà dư thừa: 49 khối thương hiệu/danh mục sửa thẳng trong form, khối sản phẩm đã có Excel; `body` sản phẩm nay là ghi chú tìm kiếm, không phải nội dung trang |

## CHỐT
| Phần | Commit | Ghi chú |
|---|---|---|
| Outbox email, hàng đợi | bff35f79 | |
| Vòng đời đơn hàng, nhật ký thay đổi | 8718c11d | |
| Dọn tên trường và chú thích PHP cũ | dbea5695, f8d64fdd, 7768dab0 | |
| Kho media | a3d52638 | |
| Bản nháp & lịch sử chung | 255ba3a3 | |
| Bảng Admin vừa khung, audit sâu | c8e5f8e3 | |
| Slug + chuyển hướng 301, tỉnh/phường dự án, dọn tỉnh cũ, settings, resolver, published_at | cdfccdda | |
| Ảnh Shopping (`feed_image`) | 8d4fef78 | |
| Thao tác hàng loạt sản phẩm, bộ lọc dữ liệu | 2aee5a9f | |
| Nút phễu cạnh ô tìm kiếm, logo ngang, thanh cuộn sidebar | bec398ee, 78338e37 | |
| Nhập/xuất Excel hợp nhất, ảnh ZIP, feed Google | b52f3366 | Đã đối chiếu xong `product_bulk.php`; không đọc lại |
| Xuất đơn hàng Excel, phiếu in, lọc ngày/tỉnh | c373035a | Phiếu in thay file Word; có thể lưu PDF |
| Kéo-thả thứ tự sản phẩm, bài viết | d3a24116 | |
| Khung khuyến mãi theo chiến dịch | 225b2b21 | Đã đối chiếu `product_promo.php`, `product_campaign.php` |
| Logo thương hiệu quản lý trong Admin | 553ebb8f | `pts:brand-logos` tự chạy trong `pts:import-legacy`; DB dev chưa chạy vì kho upload local chỉ đọc |
| Khối mô tả: danh sách, độ phủ, lịch sử, khôi phục | e7b7ecea | Đã đối chiếu `content_section.php`, `productLegacyHtmlToBlocks` |
| Mã tỉnh (BNV) và mã phường/xã (TMS) từ `Danh-muc-Phuong-xa_moi.xlsx` | e09127f8 | 3.321/3.321 phường, 34/34 tỉnh; mã phường là mã thuế 8 số, khác mã Nhất Tín trong ví dụ, phải đối chiếu khi có sandbox |
| Chuyển khoản VietQR theo đơn, xác nhận thủ công đối chiếu số tiền | 5b35487a | Tài khoản nhận khai ở Thông tin website (chỉ chủ website). **Chưa khai thì checkout ẩn mục Chuyển khoản**: phải khai trước khi chuyển hệ thống |
| Nhất Tín: báo giá, tạo vận đơn nháp (một đơn một vận đơn), hành trình, hủy, webhook có token; bỏ GHN/GHTK/Viettel Post | 7559dd58 | MOCK. Sandbox cần tài khoản thử + đối chiếu mã tỉnh/phường (`NTL_LOCATION_CODES_VERIFIED`). Production khóa |
| Bảng điều khiển: biểu đồ truy cập, đơn–doanh số, liên hệ, nguồn khách, trang xem nhiều, sản phẩm bán chạy; khối Cần xử lý thay các ô số | 94f8e267 | Lượt truy cập tự đếm (không cookie, không lưu IP, bỏ bot), giữ 400 ngày |
| Checkout tính phí Nhất Tín (Tiết kiệm) từ cân nặng sản phẩm; mô phỏng ghi rõ; >50 kg hoặc thiếu cân nặng (sandbox/production) → nhân viên báo phí; ghi chú QR ở mục Chuyển khoản | 1abecf60 | Cần điền cân nặng sản phẩm (Admin hoặc Excel cột "Cân nặng đóng gói (kg)"). Tắt bằng `NTL_CHECKOUT_QUOTES=0` |
| Thử thật upload banner, ảnh chèn trong editor (chặn file lạ, không lưu ảnh hỏng), ẩn/hiện hàng loạt theo quyền | bf664e4c | Video là link YouTube, không có upload |
| Xem trước bản nháp Giải pháp / Dự án / Chính sách trên Nuxt (link ký 15 phút, giống hệt trang thật, kể cả ảnh/sản phẩm/SEO) | commit kế tiếp | Dựng bằng cách ghi nháp vào DB trong transaction rồi rollback; các builder công khai không cache |
