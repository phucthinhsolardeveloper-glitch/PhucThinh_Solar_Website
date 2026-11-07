# Bản đồ sử dụng backend PHP

Tóm tắt: api Nuxt 32; api không Nuxt 5; lib cho Nuxt 37; lib chỉ Admin 15; lib chỉ index/giao diện cũ hoặc không thấy 7.

Tạo bởi `tools/migration/usage_map.py` (phân tích tĩnh). Quy tắc chuyển đổi: Laravel chỉ dựng lại phần
Nuxt hoặc Admin dùng; phần "không thấy tham chiếu" và "chỉ giao diện PHP cũ" là ứng viên bỏ, phải xác minh
bằng log truy cập production trước khi xóa.

## API `api/*.php` (37)

| File | Ai dùng |
|---|---|
| `cart.php` | Nuxt, giao diện PHP cũ |
| `comment.php` | Admin, giao diện PHP cũ |
| `config.php` | Nuxt, Admin |
| `fillter_service.php` | giao diện PHP cũ |
| `payment_vnpay.php` | Admin |
| `product3.php` | giao diện PHP cũ |
| `pts_v1_about.php` | Nuxt |
| `pts_v1_brand_detail.php` | Nuxt |
| `pts_v1_cart.php` | Nuxt |
| `pts_v1_cart_mutate.php` | Nuxt |
| `pts_v1_catalog.php` | Nuxt |
| `pts_v1_checkout.php` | Nuxt |
| `pts_v1_checkout_notice.php` | Nuxt |
| `pts_v1_checkout_wards.php` | Nuxt |
| `pts_v1_contact.php` | Nuxt |
| `pts_v1_dealer.php` | Nuxt |
| `pts_v1_gallery.php` | Nuxt |
| `pts_v1_gallery_detail.php` | Nuxt |
| `pts_v1_home.php` | Nuxt |
| `pts_v1_lead_form.php` | Nuxt |
| `pts_v1_news.php` | Nuxt |
| `pts_v1_news_detail.php` | Nuxt |
| `pts_v1_order_thanks.php` | Nuxt |
| `pts_v1_policy_detail.php` | Nuxt |
| `pts_v1_product_detail.php` | Nuxt |
| `pts_v1_product_extras.php` | Nuxt |
| `pts_v1_project_detail.php` | Nuxt |
| `pts_v1_projects.php` | Nuxt |
| `pts_v1_public_route_resolve.php` | Nuxt |
| `pts_v1_review.php` | Nuxt |
| `pts_v1_search.php` | Nuxt |
| `pts_v1_site.php` | Nuxt |
| `pts_v1_solution_detail.php` | Nuxt |
| `pts_v1_solutions.php` | Nuxt |
| `pts_v1_video.php` | Nuxt |
| `shipping_quote.php` | Nuxt |
| `ward-city.php` | giao diện PHP cũ |

## Trang PHP `sources/*.php` (16)

| File | Còn được gọi từ |
|---|---|
| `about.php` | chỉ giao diện PHP cũ; Caddy đã chuyển trang này sang Nuxt |
| `allpage.php` | chỉ giao diện PHP cũ; Caddy đã chuyển trang này sang Nuxt |
| `contact.php` | chỉ giao diện PHP cũ; Caddy đã chuyển trang này sang Nuxt |
| `daily.php` | chỉ giao diện PHP cũ; Caddy đã chuyển trang này sang Nuxt |
| `index.php` | chỉ giao diện PHP cũ; Caddy đã chuyển trang này sang Nuxt |
| `news.php` | chỉ giao diện PHP cũ; Caddy đã chuyển trang này sang Nuxt |
| `news_hub.php` | chỉ giao diện PHP cũ; Caddy đã chuyển trang này sang Nuxt |
| `order.php` | POST /gio-hang (giỏ hàng, đặt hàng) |
| `product.php` | chỉ giao diện PHP cũ; Caddy đã chuyển trang này sang Nuxt |
| `project_hub.php` | chỉ giao diện PHP cũ; Caddy đã chuyển trang này sang Nuxt |
| `search.php` | chỉ giao diện PHP cũ; Caddy đã chuyển trang này sang Nuxt |
| `solution_hub.php` | chỉ giao diện PHP cũ; Caddy đã chuyển trang này sang Nuxt |
| `tags.php` | chỉ giao diện PHP cũ; Caddy đã chuyển trang này sang Nuxt |
| `thankyou.php` | chỉ giao diện PHP cũ; Caddy đã chuyển trang này sang Nuxt |
| `user.php` | /account/* (đăng nhập thành viên) |
| `video.php` | chỉ giao diện PHP cũ; Caddy đã chuyển trang này sang Nuxt |

## Thư viện `libraries/*.php` (59)

| File | Được nạp bởi |
|---|---|
| `PHPExcel.php` | Admin |
| `PHPWord.php` | Admin |
| `admin_product_completeness.php` | Admin |
| `admin_product_pricing.php` | Admin |
| `admin_security.php` | Admin |
| `autoload.php` | API cho Nuxt, Admin |
| `checkSSLv2.php` | không thấy require (có thể autoload class hoặc thừa) |
| `commerce_integrations.php` | API cho Nuxt, Admin |
| `config-type.php` | Admin |
| `constant.php` | không thấy require (có thể autoload class hoặc thừa) |
| `form_security.php` | API cho Nuxt |
| `home_hero_carousel.php` | chỉ index.php (sitemap, account, giỏ POST, ảnh, giao diện cũ) |
| `login_security.php` | Admin |
| `mail_config.php` | Admin |
| `mail_templates.php` | Admin |
| `news_detail_media.php` | API cho Nuxt |
| `order_security.php` | Admin |
| `product_category_urls.php` | API cho Nuxt |
| `product_content_blocks.php` | API cho Nuxt, Admin |
| `product_images.php` | Admin |
| `product_merchant_feed.php` | Admin |
| `product_need_filter.php` | API cho Nuxt |
| `product_pdp_profiles.php` | API cho Nuxt, Admin |
| `product_power_filter.php` | API cho Nuxt |
| `product_price_filter.php` | API cho Nuxt |
| `product_projects.php` | Admin |
| `product_promo.php` | Admin |
| `product_sort.php` | API cho Nuxt |
| `product_specs.php` | API cho Nuxt |
| `product_structured_data.php` | không thấy require (có thể autoload class hoặc thừa) |
| `pts_public_about_api.php` | API cho Nuxt |
| `pts_public_brand_detail_api.php` | API cho Nuxt |
| `pts_public_cart_api.php` | API cho Nuxt |
| `pts_public_catalog_api.php` | API cho Nuxt |
| `pts_public_checkout_notice.php` | API cho Nuxt |
| `pts_public_contact_api.php` | API cho Nuxt |
| `pts_public_dealer_api.php` | API cho Nuxt |
| `pts_public_home_api.php` | API cho Nuxt |
| `pts_public_media_library_api.php` | API cho Nuxt |
| `pts_public_navigation_api.php` | API cho Nuxt |
| `pts_public_news_api.php` | API cho Nuxt |
| `pts_public_news_detail_api.php` | API cho Nuxt |
| `pts_public_product_detail_api.php` | API cho Nuxt |
| `pts_public_project_detail_api.php` | API cho Nuxt |
| `pts_public_projects_api.php` | API cho Nuxt |
| `pts_public_richtext.php` | API cho Nuxt |
| `pts_public_route_resolve_api.php` | không thấy require (có thể autoload class hoặc thừa) |
| `pts_public_seo_api.php` | API cho Nuxt |
| `pts_public_site_api.php` | API cho Nuxt |
| `pts_public_solution_detail_api.php` | API cho Nuxt |
| `pts_public_solutions_api.php` | API cho Nuxt |
| `public_pricing.php` | API cho Nuxt, Admin |
| `public_route_resolver.php` | API cho Nuxt |
| `public_watermark.php` | API cho Nuxt |
| `requick.php` | Admin |
| `router.php` | chỉ index.php (sitemap, account, giỏ POST, ảnh, giao diện cũ) |
| `session_security.php` | API cho Nuxt, Admin |
| `sitemap.php` | chỉ index.php (sitemap, account, giỏ POST, ảnh, giao diện cũ) |
| `solution_hub.php` | API cho Nuxt |
