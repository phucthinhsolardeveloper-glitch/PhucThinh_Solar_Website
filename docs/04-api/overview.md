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

Response/error format chưa đồng nhất giữa JSON, HTML fragment và scalar. Admin JSON object (`login.php`, `status.php`, `upload.php`) và `database.php` đã có envelope tương thích ngược `ok/message/code/data`; lỗi CSRF/quyền JSON dùng cùng contract. JavaScript bảo trì database đọc được cả root-array cũ và envelope mới. Các endpoint fragment/scalar cần xử lý theo batch riêng. Trước khi tạo React/static frontend, cần quyết định giữ HTML-fragment AJAX hay xây JSON API có version/auth/validation thống nhất.
