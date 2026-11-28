# API inventory hiện hành

Đối chiếu route declarations 2026-11-26 (5c35860f). Base Laravel /api/v1; public Nuxt proxy /_api. Source [api.php](../../backend/routes/api.php), [web.php](../../backend/routes/web.php), [stateless.php](../../backend/routes/stateless.php), [Nitro routes](../../frontend/server/routes), [contracts](../../frontend/shared/contracts).

| Method | /api/v1/... | Owner controller |
|---|---|---|
| GET | home, site, catalog, product-detail, product-retired | HomeController, SiteController, CatalogController, ProductDetailController |
| GET | about, brand-detail, contact, dealer, gallery, gallery-detail, news, news-detail, policy-detail, projects, project-detail, solutions, solution-detail, video, product-extras | AboutController, PagesController |
| GET | search, compare, public-route-resolve, redirect, checkout-wards | PagesController |
| GET / POST | lead-form | PagesController |
| POST | review, track | PagesController, PageViewController |
| GET / POST | solar-estimator (GET), solar-estimate (POST) | SolarEstimatorController |
| GET / POST | cart, checkout | ShopController |
| POST | shipping-quote, order-thanks/transferred | ShopController |
| GET | checkout-notice, order-thanks, payment/vnpay | ShopController |
| GET | article-draft-preview/{revision}, post-draft-preview/{revision}, page-layout-preview/{layout}, product-preview/{product} | Preview controllers; signed:relative, throttle |

Ngoài v1: POST /api/integrations/ntl/webhook; GET /health, /upload/{path}, /thumbs/{width}x{height}x{mode}/{path}, /sitemap.xml, /google-shopping.xml; admin web route in đơn/phiếu PDF.

Commerce group có middleware cookie/session. Không bỏ validation, signature, throttle hay session khi thêm route. Đọc controller và test trước khi thay contract; kiểm cả Nitro validation và UI. Bảng chỉ là inventory route source, không khẳng định mọi endpoint đã bật ở production.

Lịch sử API PHP: git show 5c35860f:docs/04-api/overview.md.
