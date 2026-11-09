# Trạng thái chuyển đổi Laravel

Đọc file này trước khi mở lại bất kỳ module nào. Mục đã **CHỐT** không đọc/rà lại, trừ khi có lỗi mới được báo.
Làm xong phần nào: test → commit → chuyển dòng sang CHỐT kèm commit → xóa file tạm của phần đó.

## Đang làm
Không có việc dở.

## Chưa làm (theo thứ tự)
- [ ] Thử thật: upload banner/video/ảnh trong editor, quyền theo từng thao tác (GAP trong `admin-capability-audit-2026-11-08.md`)
- [ ] Xem trước trên Nuxt cho Giải pháp / Dự án / Chính sách
- [ ] Task 5: chỉnh trang theo khối (Home + About POC)

## Không làm (đã kiểm chứng)
| Mục | Lý do |
|---|---|
| Màu/cỡ, tags, đa ngôn ngữ | Bản cũ đã tắt trong cấu hình hoặc 0 dữ liệu |
| Thành viên, OneSignal | 0 dữ liệu |
| Chứng nhận sản phẩm | 0 dữ liệu, trang công khai không đọc |

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
| Khối mô tả: danh sách, độ phủ, lịch sử, khôi phục, JSON, HTML→khối | e7b7ecea | Đã đối chiếu `content_section.php`, `productLegacyHtmlToBlocks` |
| Mã tỉnh (BNV) và mã phường/xã (TMS) từ `Danh-muc-Phuong-xa_moi.xlsx` | commit kế tiếp | 3.321/3.321 phường, 34/34 tỉnh; mã phường là mã thuế 8 số, khác mã Nhất Tín trong ví dụ, phải đối chiếu khi có sandbox |
