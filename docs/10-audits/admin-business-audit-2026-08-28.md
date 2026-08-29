# Checklist rà soát Admin — 2026-08-29

## Phạm vi và cách kiểm tra

- Đối chiếu menu/route Admin, source, template, API, cấu hình type, frontend và schema/dữ liệu local.
- Đăng nhập bằng tài khoản test chỉ tồn tại ở local; smoke test toàn bộ 65 route xuất hiện trong sidebar.
- Kiểm tra sâu các luồng đang vận hành: sản phẩm, đơn hàng, liên hệ, yêu cầu tư vấn, đăng ký đại lý, bài viết, media, dashboard và đánh giá.
- Không tạo/sửa dữ liệu nghiệp vụ thật, không gửi mail, không đổi schema và không triển khai feature backlog.

## Kết quả tổng quan

- 65/65 route trong menu mở được sau đăng nhập; không gặp redirect sai, 404, PHP fatal hoặc trang rỗng.
- Search/filter/pagination đã render và giữ query đúng ở sản phẩm, đơn hàng, liên hệ, tư vấn/đại lý và tin tức.
- Form sản phẩm đã có các field đang dùng ở frontend/schema: công suất, pin, tấm pin, vật liệu, thời gian sạc/chiếu sáng, chống nước, chip LED, diện tích chiếu sáng, video, chứng nhận, thông số và chính sách mua hàng.
- Phát hiện và sửa hai lỗi P0 ở dashboard: section hàng đợi nằm giữa vòng lặp option nên lặp 12 lần; chart legacy không còn UI/dữ liệu gây PHP warning và làm hỏng JavaScript dashboard.

## Checklist theo module

| Module | Checklist `Hiện trạng → Vấn đề → Đề xuất → Mức ưu tiên` |
|---|---|
| Dashboard | Có thống kê truy cập/doanh thu và hàng đợi nghiệp vụ → HTML hàng đợi bị chèn trong `<select>`; chart legacy tham chiếu `$items_product` không tồn tại và container đã bỏ → Đã sửa cấu trúc section, bỏ block chart mồ côi; verify desktop/mobile và console → **P0 đã sửa** |
| Sản phẩm | CRUD, category/brand/gallery, search, filter, pagination và review theo từng sản phẩm đều có → Đã thêm validation server cho định dạng giá/công suất và chặn giá mới vượt giá bán; nhiều sản phẩm local vẫn thiếu công suất → Hoàn thiện dữ liệu bằng Excel khi nhận file thật → **P1 dữ liệu còn lại** |
| Đánh giá | Admin truy cập review từ từng dòng sản phẩm, backend comment còn hoạt động → Không có inbox review toàn cục; dashboard chỉ dẫn về danh sách sản phẩm có filter → Thiết kế queue review toàn cục nếu vận hành cần, chưa triển khai → **P2** |
| Đơn hàng | Danh sách/detail, trạng thái, thanh toán, search/filter/pagination hoạt động → Đã khóa mass-assignment bằng allowlist và xác minh status; chưa có lịch sử ai đổi trạng thái → Thêm audit trail và ràng buộc transition sau khi chốt workflow → **P1 còn lại** |
| Liên hệ | Admin có search, trạng thái, chủ đề, thời gian; frontend đã đi đúng marker submit/backend → Chưa test SMTP/reCAPTCHA thật để tránh phát sinh mail/dữ liệu ngoài ý muốn → Test staging bằng mailbox và site-key riêng → **P1** |
| Tư vấn/đại lý/nhận tin | Ba type newsletter có danh sách, search, lọc trạng thái và pagination → Các type dùng chung bảng nhưng nhãn/workflow xử lý chưa thống nhất → Chuẩn hóa trạng thái và người phụ trách trước khi thêm automation → **P2** |
| Tin tức/giải pháp/dự án/chính sách | CRUD theo type/category, SEO, ảnh, search và pagination có sẵn → Nhiều type legacy còn dữ liệu nhưng config/menu bị tắt; khó biết loại nào còn là nguồn chính thức → Lập mapping owner/URL rồi archive hoặc phục hồi menu có kiểm soát → **P2** |
| Media | Slide, logo, breadcrumb, đối tác và các nhóm ảnh cấu hình mở được → Có record legacy như `banner`, `background`, `popup`, `video`, watermark; popup hiện bị comment ở frontend → Xác minh usage trước khi ẩn/xóa; không xóa asset hiện tại → **P2** |
| Upload | Có giới hạn theo `post_max_size`, extension và tên file server-side ở phần lớn luồng → Hàm dùng so khớp extension dạng chuỗi, chưa xác minh MIME, và chạy xử lý ảnh cả với file tài liệu → Tách image/file validator, dùng allowlist extension chính xác, MIME/size và bảo đảm thư mục không thực thi PHP → **P1** |
| SEO/cấu hình | Setting, seopage, lang và ảnh breadcrumb có Admin → Một số copy giao diện mới vẫn hard-code, không chỉnh từ Admin → Chỉ đưa copy có nhu cầu vận hành thường xuyên vào setting/CMS → **P2** |
| Tài khoản/quyền | Có source user/permission; mọi API Admin kiểm tra session đăng nhập → RBAC đang tắt; kiểm quyền action cũ chưa phủ save/AJAX mutation → Thiết kế ma trận quyền, enforce server-side cho save/delete/status/numb/copy trước khi có nhiều tài khoản → **P1** |
| API Admin | API upload/status/numb/copy/category/place dùng cho UI hiện tại và yêu cầu login → API mutation nhận tên bảng từ request, thiếu allowlist theo module/quyền và không thấy CSRF chung → Bổ sung allowlist, CSRF và authorization theo action trong một đợt hardening riêng → **P1** |
| Route/menu | 65 route sidebar đều render → Source legacy vẫn có route/API không nằm menu (`save_add_item`, import cũ, một số type bị comment), tăng chi phí bảo trì → Gắn usage/deprecation trước khi xóa → **P3** |
| Validation/output | Nhiều CRUD có required client-side và prepare dữ liệu server-side → Escape output và validation server-side chưa đồng nhất giữa module cũ → Chuẩn hóa dần khi chạm từng module, ưu tiên dữ liệu khách hàng → **P1** |

## Frontend có nội dung/dữ liệu Admin chưa quản lý thuận tiện

| Hiện trạng → Vấn đề → Đề xuất → Mức ưu tiên |
|---|
| Homepage có headline/CTA/trust copy của các section mới → Một phần nằm trực tiếp trong template → Chuyển các copy thường xuyên thay đổi vào cấu hình sau khi owner xác nhận → **P2** |
| Trang danh mục sản phẩm có hero copy, trust badges và lời nhắc sidebar → Nội dung hard-code, Admin chỉ quản lý sản phẩm/category → Tạo nhóm setting nội dung, không tạo CMS mới nếu ít thay đổi → **P2** |
| Detail bài viết/dịch vụ có nhãn tác giả kiểu `Admin Dev` → Không có field tác giả nghiệp vụ rõ ràng → Dùng tên thương hiệu mặc định hoặc bổ sung field author khi có quy trình biên tập → **P2** |
| Checkout đọc type `hinh-thuc-thanh-toan` từ DB → Type vẫn có 3 record nhưng config quản trị đang bị comment → Khôi phục menu CRUD sau khi xác nhận checkout còn dùng nguồn này → **P1** |
| Frontend có popup/watermark hooks → Popup query bị comment và watermark-news có config legacy bị tắt → Xác nhận còn sử dụng; archive có rollback nếu không còn → **P3** |

## Backlog ưu tiên

### P0 lỗi cần sửa ngay

- **Đã sửa:** Dashboard DOM sai làm section “Việc cần xử lý” lặp 12 lần và nằm trong bộ lọc tháng.
- **Đã sửa:** Chart sản phẩm legacy tham chiếu biến/container không còn tồn tại, phát warning và làm hỏng toàn bộ JavaScript dashboard.
- Không còn lỗi route/render P0 trong 65 route đã smoke test.

### P1 thiếu chức năng quan trọng

- CSRF và authorization theo action cho toàn bộ CRUD/AJAX mutation.
- Allowlist bảng/field cho API `status`, `numb`, `copy`, `category`, `place`.
- Hardening upload: extension chính xác, MIME, size theo loại, filename và non-executable storage.
- **Đã sửa:** validation server-side cho giá và công suất sản phẩm; còn cần chốt transition trạng thái đơn.
- **Đã sửa:** cập nhật đơn chỉ nhận requirements/notes/order status/payment status; từ chối status không tồn tại và placeholder thanh toán.
- Khôi phục khả năng quản lý hình thức thanh toán nếu xác nhận checkout đang sử dụng.
- Audit log cho thay đổi trạng thái đơn hàng; test SMTP/reCAPTCHA trên staging.

### P2 nên nâng cấp

- Queue review toàn cục; chuẩn hóa workflow liên hệ/tư vấn/đại lý.
- Quản lý các copy frontend thay đổi thường xuyên; field tác giả bài viết.
- Chuẩn hóa empty/error/success, escape output và UX filter/pagination giữa các module.
- Mapping và quyết định giữ/archive các content/media type legacy.

### P3 nice-to-have

- Dọn route/API legacy sau khi có log usage và rollback.
- Export/bulk/quick-edit chỉ triển khai khi có yêu cầu vận hành được xác nhận.

## Verify

- Browser authenticated: 65/65 route menu render thành công.
- Browser focused: product/order/contact/newsletter/news filter và query state render đúng.
- Dashboard mobile 390 px và desktop: 1 section hàng đợi, 13 option tháng (placeholder + 12 tháng), section không nằm trong form/select, không horizontal overflow.
- PHP lint file sửa và `git diff --check` phải đạt trước commit.

## Rủi ro và triển khai

- Chưa chạy mutation CRUD thật để tránh làm đổi dữ liệu nghiệp vụ local; kiểm tra hiện tại là read-only/authenticated render và đối chiếu code/schema.
- Không bật RBAC, không thay API contract, database hay production trong audit này.
- Deploy file dashboard và tài liệu cùng commit. Rollback bằng revert commit; không cần rollback database.
