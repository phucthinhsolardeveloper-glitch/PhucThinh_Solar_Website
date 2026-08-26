# Request flows

## Storefront

`GET /slug/` -> `.htaccess` -> `index.php` -> object bootstrap -> `router.php` generic match/slug lookup -> `sources/allpage.php` + selected source -> `templates/index.php` -> layout + selected `*_tpl.php`.

## Product

`sources/product.php` tải product/hierarchy/brand/gallery/related/comment/SEO, tăng view; `templates/product/product_detail_tpl.php` render.

## Commerce

`api/cart.php` sửa `$_SESSION['cart']`. `gio-hang` chạy `sources/order.php`; POST hợp lệ insert `table_order`/`table_order_detail`, gửi email, xóa cart và redirect về base URL.

## CMS

`administrator/index.php` -> shared bootstrap -> `config-type.php` -> `libraries/requick.php` auth/optional RBAC -> `administrator/sources/<com>.php` -> admin template.

## AJAX

Mỗi `api/*.php` tự bootstrap qua `api/config.php`; không có centralized API router/spec.
