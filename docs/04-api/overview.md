# API/AJAX inventory

Các endpoint là PHP file, không phải REST API versioned và không có OpenAPI.

- `api/cart.php`: POST command add/update/delete/ship/popup; session cart; JSON hoặc HTML fragment.
- `api/comment.php`: `GET limitLists`, `GET limitReplies`, `POST add` qua `Comments`.
- `api/fillter_service.php`: GET filter/sort/pagination, trả HTML fragment; dynamic brand/origin list cần hardening.
- `api/product.php`, `product2.php`, `product3.php`, `search_product.php`: product fragments/search.
- `api/color.php`, `video.php`: product media fragments.
- `api/district.php`, `ward.php`: dependent location options.
- `api/addons.php`: lazy-loaded blocks.
- `administrator/api/*`: login, upload, status/order/category/component/sort/delete helpers; dựa vào CMS session/bootstrap theo từng file.

Response thành công vẫn có chủ ý gồm JSON hoặc HTML fragment theo consumer legacy. Toàn bộ lỗi Admin API đã chuẩn hóa dần về envelope tương thích ngược `ok/message/code/data`; lỗi hết phiên, CSRF/quyền, validation, endpoint legacy bị khóa và exception ngoài dự kiến đều có HTTP status + mã lỗi rõ ràng. Integration test dùng một assertion schema chung trên các nhóm lỗi đại diện. Trước khi tạo React/static frontend, cần quyết định giữ HTML-fragment AJAX hay xây JSON API có version/auth/validation thống nhất.

`administrator/api/numb.php`, `copy.php` và `slug.php` đã chuyển sang envelope JSON. Client phục hồi thứ tự cũ khi lỗi, chỉ reload sau khi copy thành công và đọc `data.available` khi kiểm tra slug. Copy dùng vòng lặp slug có giới hạn, lấy đúng `lastInsertId`, chuẩn hóa field số và bắt exception DB. Kiểm tra slug xác thực định dạng, ép kiểu ID và parameterize điều kiện loại trừ bản ghi. Các endpoint fragment vẫn giữ contract legacy.

Ba endpoint `update_sttMenu.php`, `update_sttIndex.php`, `update_sttDetail.php` trả envelope JSON và cập nhật thứ tự trong transaction. Danh sách bị giới hạn 500 ID dương, không trùng, phải tồn tại đủ trong bảng allowlist; JavaScript hiển thị lỗi nếu server không xác nhận thành công.

`delete_add_item.php` nhận nhóm `menu/index/detail` qua allowlist để chọn đúng bảng sortable, trả JSON và xác minh bản ghi đã được xóa. Client giữ nguyên dòng nếu request thất bại; dòng mới chưa lưu (`id=0`) chỉ được gỡ khỏi DOM và không gọi API.

Mutation trong `comment.php` giữ `success/errors` cho plugin cũ và thêm envelope chuẩn. Quyền edit/delete được suy ra từ `type` của bình luận trong DB; trạng thái có allowlist, dữ liệu phản hồi Admin có allowlist cột và luôn kế thừa `id_variant/type` từ bình luận gốc.

Form full-page `com=sortable&act=save` vẫn dùng response chuyển trang legacy nhưng đã kiểm tra quyền, allowlist field và validate các mảng item trước khi mở transaction. Ba nhóm item, cấu hình chung và SEO cùng commit hoặc rollback; payload POST không còn bị dump ra response.
