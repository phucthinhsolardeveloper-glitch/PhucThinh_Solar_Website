# Feature inventory

- IMPLEMENTED: catalogue/hierarchy/brand/price/sale/gallery/spec/SEO; news/project/solution/static; contact; CMS CRUD/media/SEO; sitemap; session cart/order code.
- PARTIAL: product filter; commerce activation/confirmation; member; review; RBAC; OneSignal.
- MISSING: wattage/type structured filter, dedicated thank-you/conversion page, automated test/deploy.
- UNKNOWN: actual production usage of member/order/review and external consumers of AJAX endpoints.

Main areas: product `sources/product.php`, `templates/product/`, `administrator/sources/product.php`; commerce `api/cart.php`, `sources/order.php`; content `sources/news.php` and templates; leads `sources/contact.php`, `sources/daily.php`.
