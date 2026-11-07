# API và contract — kiểm kê Phase 0

Tạo lúc 2026-11-07T18:52:55+07:00. Tham số đọc từ `$_GET/$_POST/$_REQUEST`; cột "Ghi" là dấu hiệu có câu lệnh ghi DB, cần xác minh tay.

| File PHP API | Phương thức đoán | Tham số | Ghi |
|---|---|---|---|
| `api/cart.php` | POST | cmd, code, color, id, quantity, size, ward |  |
| `api/comment.php` | POST | form_csrf, get |  |
| `api/config.php` | GET |  |  |
| `api/fillter_service.php` | GET | eShow, id_cat, id_list, is_brand, is_cat, is_congsuat, is_need, is_orderby, is_origin, is_price … |  |
| `api/payment_vnpay.php` | GET | mode | có |
| `api/product3.php` | GET | eShow, idList, p, perpage |  |
| `api/pts_v1_about.php` | GET |  |  |
| `api/pts_v1_brand_detail.php` | GET | page, per_page, price, slug, sort |  |
| `api/pts_v1_cart.php` | GET |  |  |
| `api/pts_v1_cart_mutate.php` | GET |  |  |
| `api/pts_v1_catalog.php` | GET | brand, category, is_need, keyword, page, per_page, price, sort, subcategory |  |
| `api/pts_v1_checkout.php` | GET |  |  |
| `api/pts_v1_checkout_notice.php` | GET |  |  |
| `api/pts_v1_checkout_wards.php` | GET | city |  |
| `api/pts_v1_contact.php` | GET |  |  |
| `api/pts_v1_dealer.php` | GET |  |  |
| `api/pts_v1_gallery.php` | GET | p |  |
| `api/pts_v1_gallery_detail.php` | GET | slug |  |
| `api/pts_v1_home.php` | GET |  |  |
| `api/pts_v1_lead_form.php` | GET |  | có |
| `api/pts_v1_news.php` | GET | category, category_path, category_type, p, q |  |
| `api/pts_v1_news_detail.php` | GET | slug |  |
| `api/pts_v1_order_thanks.php` | GET | token |  |
| `api/pts_v1_policy_detail.php` | GET | slug |  |
| `api/pts_v1_product_detail.php` | GET | slug |  |
| `api/pts_v1_product_extras.php` | GET | replyOffset, replyParent, reviewOffset, slug |  |
| `api/pts_v1_project_detail.php` | GET | slug |  |
| `api/pts_v1_projects.php` | GET | category, page, per_page |  |
| `api/pts_v1_public_route_resolve.php` | GET | slug |  |
| `api/pts_v1_review.php` | POST | dataReview, form_csrf |  |
| `api/pts_v1_search.php` | GET | brand, keyword, page, per_page, price, sort |  |
| `api/pts_v1_site.php` | GET |  |  |
| `api/pts_v1_solution_detail.php` | GET | slug |  |
| `api/pts_v1_solutions.php` | GET | category |  |
| `api/pts_v1_video.php` | GET | p |  |
| `api/shipping_quote.php` | POST | city, subtotal, ward |  |
| `api/ward-city.php` | POST | id_city |  |

## Nitro bridge `/_migration/*` (33)

`about.get`, `brand-detail.get`, `cart.get`, `cart.post`, `catalog.get`, `checkout-notice.get`, `checkout-wards.get`, `checkout.get`, `checkout.post`, `contact.get`, `dealer.get`, `gallery-detail.get`, `gallery.get`, `home.get`, `lead-form.get`, `lead-form.post`, `news-detail.get`, `news.get`, `order-thanks.get`, `policy-detail.get`, `product-detail.get`, `product-document.get`, `product-extras.get`, `project-detail.get`, `projects.get`, `public-route-resolve.get`, `review.post`, `search.get`, `shipping-quote.post`, `site.get`, `solution-detail.get`, `solutions.get`, `video.get`

## Contract TypeScript (27)

`core.ts`, `editorial-rich-text.ts`, `home-hero.ts`, `migration-preview-routes.ts`, `public-about.ts`, `public-brand-detail.ts`, `public-cart.ts`, `public-catalog.ts`, `public-checkout.ts`, `public-contact.ts`, `public-dealer.ts`, `public-gallery.ts`, `public-home.ts`, `public-lead-form.ts`, `public-news-detail.ts`, `public-news.ts`, `public-policy-detail.ts`, `public-product-detail.ts`, `public-product-extras.ts`, `public-project-detail.ts`, `public-projects.ts`, `public-route-resolve.ts`, `public-search.ts`, `public-site.ts`, `public-solution-detail.ts`, `public-solutions.ts`, `public-video.ts`
