import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const root = new URL('../app/', import.meta.url)
const read = (path) => readFileSync(new URL(path, root), 'utf8')

test('catalog navigation exposes a responsive disclosure and current category', () => {
  const navigation = read('components/catalog/CatalogCategoryNav.vue')
  const branch = read('components/catalog/CatalogCategoryBranch.vue')
  assert.match(navigation, /aria-controls="catalog-category-list"/)
  assert.match(navigation, /:aria-expanded="expanded"/)
  assert.match(branch, /:aria-current="selected === node\.url \? 'page' : undefined"/)
})

test('catalog keeps category navigation and business filters in one panel', () => {
  const catalog = read('components/catalog/CatalogPage.vue')
  const sidebar = read('components/catalog/CatalogFilterSidebar.vue')
  const icon = read('components/CatalogMenuIcon.vue')
  assert.doesNotMatch(catalog, /CatalogCategoryNav/)
  assert.match(sidebar, /aria-label="Bộ lọc sản phẩm"/)
  assert.match(sidebar, /:aria-controls="categoryListId"/)
  assert.match(sidebar, /idPrefix\?: string/)
  assert.match(sidebar, /<CatalogCategoryBranch/)
  assert.match(catalog, /node\) => \(\{ \.\.\.node, children: \[\] \}\)/)
  assert.match(catalog, /const sidebarCategories = computed\(\(\) => selectedCategory\.value\s*\? \[\]/)
  assert.match(catalog, /:subcategory-title="subcategoryTitle"/)
  assert.match(catalog, /dialog\.showModal\(\)/)
  assert.match(catalog, /aria-controls="catalog-filter-drawer"/)
  assert.match(catalog, /aria-modal="true"/)
  assert.match(catalog, /closeFilterDrawerAfterNavigation/)
  assert.match(catalog, /min-\[1002px\]:grid-cols-\[240px_minmax\(0,1fr\)\][\s\S]*min-\[1200px\]:grid-cols-\[280px_minmax\(0,1fr\)\]/)
  assert.match(catalog, /hidden flex-col gap-6 min-\[1002px\]:flex/)
  assert.match(sidebar, /subcategoryTitle \|\| 'Loại sản phẩm'/)
  assert.match(icon, /class="block"/)
  assert.doesNotMatch(icon, /h-full w-full/)
})

test('catalog matches PHP list-search visibility and keeps the lighting need filter', () => {
  const catalog = read('components/catalog/CatalogPage.vue')
  const sidebar = read('components/catalog/CatalogFilterSidebar.vue')
  const phpCatalogCss = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/assets/css/product-category-v3.css', import.meta.url), 'utf8')
  assert.match(phpCatalogCss, /\.product-category-page \.pts-lighting-category-v2 \.lv-search,[\s\S]*?\.lv-active-filters\{display:none!important;\}/)
  assert.doesNotMatch(catalog, /role="search"|Tìm tên hoặc mã sản phẩm|onKeywordSearch/)
  assert.match(catalog, /keyword\.value \? \{ keyword: keyword\.value \} : \{\}/)
  assert.match(catalog, /filters\.needs\?\.length/)
  assert.match(sidebar, /Nhu cầu sử dụng/)
  assert.match(sidebar, /filterLink\(\{ need:/)
})

test('PDP renders allow-listed decision metrics and package contents; brand can select supported sorting', () => {
  const productPage = read('components/product-preview/ProductPreviewPage.vue')
  const publicProductRoute = read('pages/san-pham/[slug].vue')
  const productPreviewRoute = read('pages/migration-product-preview.vue')
  const metrics = read('components/product-preview/ProductPreviewDecisionMetrics.vue')
  const brand = read('components/catalog/BrandDetailPage.vue')
  assert.match(productPage, /ProductPreviewDecisionMetrics/)
  assert.match(productPage, /product\.packageContents/)
  assert.match(publicProductRoute, /payload\.value\?\.breadcrumbs/)
  assert.match(publicProductRoute, /<SiteBreadcrumb :items="breadcrumbItems"/)
  assert.match(publicProductRoute, /:breadcrumbs="payload!\.breadcrumbs \?\? \[\]"/)
  assert.match(productPreviewRoute, /payload\.value\?\.breadcrumbs/)
  assert.doesNotMatch(productPreviewRoute, /Sản phẩm preview/)
  assert.match(productPage, /productCategories\.map\(\(item\) => item\.label\)/)
  assert.match(productPage, /product\.brand/)
  assert.match(metrics, /metric\.iconImage/)
  assert.match(metrics, /availableMetrics = computed\(\(\) => props\.metrics\.filter\(\(metric\) => metric\.value\.trim\(\) !== ''\)\)/)
  assert.doesNotMatch(metrics, /\[\.\.\.\]|Đang cập nhật/)
  assert.match(metrics, /pts-pdp-decision-metrics/)
  assert.match(brand, /const sortOptions = computed/)
  assert.match(brand, /data\.selected\.sort/)
  assert.match(brand, /function onSortChange/)
})

test('PDP keeps the source section order, PHP navigation labels, and FAQ placement', () => {
  const productPage = read('components/product-preview/ProductPreviewPage.vue')
  const productContent = read('components/product-preview/ProductPreviewContent.vue')
  const source = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/templates/product/product_detail_tpl.php', import.meta.url), 'utf8')
  const pageOrder = [
    productPage.indexOf('<ProductPreviewContent'),
    productPage.indexOf('<ProductPreviewApplications'),
    productPage.indexOf('<ProductPreviewBuyingGuide'),
    productPage.indexOf('<ProductPreviewSpecs'),
    productPage.indexOf('<ProductSupportExtras'),
    productPage.indexOf('id="cau-hoi-thuong-gap"'),
    productPage.indexOf('<ProductReviews'),
    productPage.indexOf('<ProductConsultationForm'),
    productPage.indexOf('<ProductTrustRelated'),
  ]
  assert.ok(pageOrder.every((position) => position >= 0))
  assert.deepEqual(pageOrder, [...pageOrder].sort((a, b) => a - b))
  assert.match(productPage, /label: 'Thông số kỹ thuật'/)
  assert.match(productPage, /label: 'Chính sách bảo hành'/)
  assert.match(productPage, /label: 'Câu hỏi thường gặp'/)
  assert.match(productPage, /label: 'Yêu cầu tư vấn'/)
  assert.match(productContent, /Mô tả sản phẩm/)
  assert.doesNotMatch(productContent, /content\.faqs|Câu hỏi thường gặp/)
  assert.ok(source.indexOf('id="cau-hoi-thuong-gap"') < source.indexOf('pts-pdp-reviews'))
})

test('PDP support, reviews, consultation and related items follow the PHP section behavior', () => {
  const productPage = read('components/product-preview/ProductPreviewPage.vue')
  const support = read('components/product-preview/ProductSupportExtras.vue')
  const reviews = read('components/product-preview/ProductReviews.vue')
  const consultation = read('components/product-preview/ProductConsultationForm.vue')
  const related = read('components/product-preview/ProductTrustRelated.vue')
  const grid = read('components/catalog/CatalogProductGrid.vue')
  const source = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/templates/product/product_detail_tpl.php', import.meta.url), 'utf8')

  assert.match(productPage, /<ProductSupportExtras :extras="extras" :documents="documents" \/>/)
  assert.doesNotMatch(productPage, /<ProductPreviewDocuments/)
  assert.match(support, /id="video-san-pham"/)
  assert.match(support, /Video đang được cập nhật/)
  assert.match(support, /id="tai-lieu-san-pham"/)
  assert.match(support, /id="chinh-sach-bao-hanh"/)
  assert.ok(source.indexOf('pts-pdp-support-grid') < source.indexOf('id="cau-hoi-thuong-gap"'))

  assert.match(reviews, /<details id="danh-gia-san-pham"/)
  assert.doesNotMatch(reviews, /<details[^>]*\bopen\b/)
  assert.match(source, /<details class="pts-pdp-review-disclosure">/)

  assert.match(consultation, /bg-slate-50/)
  assert.doesNotMatch(consultation, /bg-navy-950|Tư vấn theo sản phẩm/)
  assert.match(consultation, /type="radio" value="Người dùng cá nhân"/)
  assert.match(consultation, /type="radio" value="Đại lý\/Nhà phân phối, bán lẻ\/Nhà thầu…"/)
  assert.match(consultation, /submit\('product'/)

  assert.ok(related.indexOf('id="san-pham-lien-quan"') < related.indexOf('id="ly-do-chon-phuc-thinh-solar"'))
  assert.match(related, /:products="extras\.related" layout="rail"/)
  assert.match(grid, /catalog-product-grid-rail/)
  assert.match(grid, /quickSpecText\(product\)/)
  assert.doesNotMatch(grid, /absolute left-2 top-2 rounded-chip bg-navy-800/)
})

test('brand sidebar uses real category and brand links and filters prices within the brand', () => {
  const brand = read('components/catalog/BrandDetailPage.vue')
  const adapter = read('../server/routes/_migration/brand-detail.get.ts')
  assert.match(brand, /site\.productCategories/)
  assert.match(brand, /data\.filters\.brands/)
  assert.match(brand, /data\.filters\.priceRanges/)
  assert.match(brand, /function priceLink/)
  assert.match(brand, /data\.selected\.priceKeys/)
  assert.match(adapter, /'price'/)
  assert.match(adapter, /query\.price/)
})

test('search and catalog root keep their PHP-backed route and category hero data', () => {
  const search = read('components/catalog/SearchResultsPage.vue')
  const catalog = read('components/catalog/CatalogPage.vue')
  const masthead = read('components/catalog/CatalogMasthead.vue')
  assert.match(search, /\$fetch\('\/_migration\/search'/)
  assert.match(search, /keyword: keyword\.value/)
  assert.match(catalog, /:hero="catalogData\?\.categoryHero \?\? null"/)
  assert.match(catalog, /'den-pha-nang-luong-mat-troi': 10/)
  assert.match(catalog, /'den-duong-nang-luong-mat-troi': 20/)
  assert.match(catalog, /function categoryNavLabel\([\s\S]*'Đèn Sân Vườn'/)
  assert.match(catalog, /max-\[769px\]:w-full max-\[769px\]:flex-col max-\[769px\]:items-start/)
  assert.match(catalog, /max-\[769px\]:gap-y-4/)
  assert.match(masthead, /max-w-\[1200px\][\s\S]*max-\[769px\]:w-\[calc\(100%_-_32px\)\]/)
  assert.match(masthead, /h-\[250px\][\s\S]*max-\[769px\]:h-\[230px\]/)
  assert.match(masthead, /w-\[min\(520px,48vw\)\][\s\S]*max-\[769px\]:w-\[min\(100%,300px\)\]/)
  assert.match(masthead, /hero\?\.image/)
  assert.match(catalog, /productCategoryTree/)
  assert.match(catalog, /Sản phẩm năng lượng mặt trời/)
})

test('commerce media failures render explicit fallbacks instead of blank frames', () => {
  const grid = read('components/catalog/CatalogProductGrid.vue')
  const gallery = read('components/product-preview/ProductPreviewGallery.vue')
  assert.match(grid, /markImageFailed\(product\.id\)/)
  assert.match(grid, /Chưa có hình ảnh/)
  assert.match(gallery, /markMediaFailed\(selectedMedia\)/)
  assert.match(gallery, /CMS chưa có ảnh sản phẩm hợp lệ/)
})

test('PDP gallery uses the PHP white image surface and purchase actions preserve PHP order', () => {
  const gallery = read('components/product-preview/ProductPreviewGallery.vue')
  const purchase = read('components/product-preview/ProductPurchasePanel.vue')
  const source = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/templates/product/product_detail_tpl.php', import.meta.url), 'utf8')
  assert.match(gallery, /place-items-center[^\n]*bg-white/)
  assert.ok(purchase.indexOf('Mua ngay') < purchase.indexOf('Thêm vào giỏ'))
  assert.ok(source.indexOf('title="Mua ngay"') < source.indexOf('title="Thêm vào giỏ hàng"'))
})

test('catalog pagination stays bounded for large CMS result sets', () => {
  const catalog = read('components/catalog/CatalogPage.vue')
  assert.match(catalog, /const paginationItems = computed/)
  assert.match(catalog, /if \(total <= 10\)/)
  assert.match(catalog, /catalog-pagination__ellipsis/)
  assert.doesNotMatch(catalog, /v-for="number in totalPages"/)
  assert.match(catalog, />\s*First\s*<\/NuxtLink>/)
  assert.match(catalog, />\s*Prev\s*<\/NuxtLink>/)
  assert.match(catalog, />\s*Next\s*<\/NuxtLink>/)
  assert.match(catalog, />\s*Last\s*<\/NuxtLink>/)
})
