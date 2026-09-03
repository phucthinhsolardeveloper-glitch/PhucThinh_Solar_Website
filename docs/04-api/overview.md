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

Response/error format chưa đồng nhất giữa JSON, HTML fragment và scalar. Batch chuẩn hóa Admin đầu tiên đã thêm envelope tương thích ngược `ok/message/code/data` cho `login.php`, `status.php` và `upload.php`, đồng thời giữ các field legacy. Các endpoint fragment/scalar và `database.php` cần xử lý theo batch riêng cùng JavaScript consumer. Trước khi tạo React/static frontend, cần quyết định giữ HTML-fragment AJAX hay xây JSON API có version/auth/validation thống nhất.
