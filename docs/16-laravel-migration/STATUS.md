# Trạng thái chuyển đổi Laravel

Đọc file này trước khi mở lại bất kỳ module nào. Mục đã **CHỐT** không đọc/rà lại, trừ khi có lỗi mới được báo.
Làm xong phần nào: test → commit → chuyển dòng sang CHỐT kèm commit → xóa file tạm của phần đó.

## Đang làm
Không có việc dở.

## Chưa làm (theo thứ tự)
Không còn mục nào. Việc tiếp: chuẩn bị chuyển hệ thống (xem HANDOFF → Cutover).

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
| Xem trước bản nháp Giải pháp / Dự án / Chính sách trên Nuxt (link ký 15 phút, giống hệt trang thật, kể cả ảnh/sản phẩm/SEO) | 2cae7811 | Dựng bằng cách ghi nháp vào DB trong transaction rồi rollback; các builder công khai không cache |
| Task 5: Bố cục trang (Trang chủ, Giới thiệu): thứ tự, ẩn/hiện, màu nền khối Giới thiệu, nháp, xem trước ký 15 phút, xuất bản, mở lại bản cũ | 2f0a1e87 | Chữ/ảnh trong khối vẫn ở `page_items` (Nội dung các trang); chưa xuất bản bản nào thì trang dùng thứ tự gốc |
| Rà soát: gỡ vận đơn "Chưa rõ kết quả" (gắn mã từ cổng Nhất Tín hoặc đóng lại); trên production chế độ mock không tính phí checkout và không tạo vận đơn giả | 977a41ea | Lệch snapshot còn lại là dữ liệu local: 6 đánh giá mẫu (`pts:demo-data`), mục giới thiệu trang Đại lý bị xóa trong Admin lúc 11:55 09/11 |
| Đánh giá Gemini/ChatGPT: ảnh chia sẻ 1200×630 có kích thước (Zalo/Facebook không còn hiện logo méo), LocalBusiness kèm kho, mô tả SEO dự án tự sinh, bản đồ Liên hệ tải chậm; ứng viên dựng lại từ `e02a4a1b` và qua toàn bộ cổng | c3c4c372, e02a4a1b | Còn: công cụ ước tính, thông số dự án, so sánh sản phẩm, chữ 14,5px trên điện thoại, srcset vài trang; staging VPS và sandbox cần chủ website |
| Giả lập VNPay/Nhất Tín/Mailpit + kịch bản thử; công cụ ước tính điện mặt trời (Admin chỉnh số, tắt mặc định); thông số dự án (kWp, sản lượng, tiết kiệm…) | 96ee9684, c9fb8120, 9cd9f45c, 5f3e0ba8 | Chủ website cần duyệt đơn giá ước tính rồi bật; nhập thông số cho các dự án; khai `MAIL_ADMIN_TO` trên server |
| So sánh tối đa 4 sản phẩm (nút trên ảnh sản phẩm, thêm ngay trong trang so sánh, ẩn dòng giống nhau); tìm kiếm hiểu từ đồng nghĩa (Admin sửa được) | 5b301e0f, afad2bd5 | Trang so sánh noindex |
| Chuẩn hóa thông số mọi đường nhập (Admin, Excel, import cũ, dữ liệu sẵn có): giờ, m, m², ×, kg, năm, V/Ah/W, K, IP, cái/thùng; tên thương hiệu trong tên sản phẩm | d388098b, 52107468 | `pts:normalize-specs --apply` phải chạy trên production sau migrate; không đổi tên dòng thông số (đã kiểm 372 sản phẩm) |
| Tốc độ: thẻ sản phẩm/danh mục chuyển trang trong app (mở sản phẩm 0,7 s → 0,09 s PC; 4,9 s → 0,9 s điện thoại yếu); 3D tải gần mới tải trên điện thoại; ảnh minh họa qua /thumbs | f5896fa7 | |
| GTM từ Admin (đặt sẵn GTM-57TJSRVM, chỉ gắn trên tên miền thật); sự kiện GA4 view_item/add_to_cart/begin_checkout/purchase/generate_lead/click_call/click_zalo; feed `/google-shopping.xml`; giữ gclid/gbraid/wbraid/fbclid với lead | 13053b32, d6964350, d00d48d8 | Không có Consent Mode / Enhanced Conversions: cần chủ website quyết (Nghị định 13/2023) |
| Breadcrumb danh mục đủ cấp cha; trang chính sách không nhảy khi chuyển; tìm kiếm PC phủ header có logo + hotline; bỏ chữ "kỹ sư" | d388098b, 13053b32 | Candidate `f5aac548` verify đạt |
