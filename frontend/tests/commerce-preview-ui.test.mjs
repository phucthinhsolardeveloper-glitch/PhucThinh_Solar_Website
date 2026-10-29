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
  assert.doesNotMatch(catalog, /CatalogCategoryNav/)
  assert.match(sidebar, /aria-label="Bộ lọc sản phẩm"/)
  assert.match(sidebar, /aria-controls="catalog-filter-category-list"/)
  assert.match(sidebar, /<CatalogCategoryBranch/)
})

test('catalog exposes category-scoped name/code search and the PHP lighting need filter', () => {
  const catalog = read('components/catalog/CatalogPage.vue')
  const sidebar = read('components/catalog/CatalogFilterSidebar.vue')
  assert.match(catalog, /role="search"/)
  assert.match(catalog, /Tìm tên hoặc mã sản phẩm/)
  assert.match(catalog, /filterLink\(\{ keyword: value \}\)/)
  assert.match(catalog, /filters\.needs\?\.length/)
  assert.match(sidebar, /Nhu cầu sử dụng/)
  assert.match(sidebar, /filterLink\(\{ need:/)
})

test('PDP renders allow-listed decision metrics and package contents; brand can select supported sorting', () => {
  const productPage = read('components/product-preview/ProductPreviewPage.vue')
  const publicProductRoute = read('pages/san-pham/[slug].vue')
  const metrics = read('components/product-preview/ProductPreviewDecisionMetrics.vue')
  const brand = read('components/catalog/BrandDetailPage.vue')
  assert.match(productPage, /ProductPreviewDecisionMetrics/)
  assert.match(productPage, /product\.packageContents/)
  assert.match(publicProductRoute, /payload\.value\?\.breadcrumbs/)
  assert.match(publicProductRoute, /<SiteBreadcrumb :items="breadcrumbItems"/)
  assert.match(metrics, /metric\.iconImage/)
  assert.match(metrics, /availableMetrics/)
  assert.match(metrics, /grid-cols-1[^\n]*tb:grid-cols-2/)
  assert.doesNotMatch(metrics, /\[\.\.\.\]|Đang cập nhật/)
  assert.match(brand, /const sortOptions = computed/)
  assert.match(brand, /data\.selected\.sort/)
  assert.match(brand, /function onSortChange/)
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
  assert.match(search, /\$fetch\('\/_migration\/search'/)
  assert.match(search, /keyword: keyword\.value/)
  assert.match(catalog, /catalogData\?\.categoryHero\?\.image/)
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

test('catalog pagination stays bounded for large CMS result sets', () => {
  const catalog = read('components/catalog/CatalogPage.vue')
  assert.match(catalog, /const paginationItems = computed/)
  assert.match(catalog, /if \(total <= 7\)/)
  assert.match(catalog, /catalog-pagination__ellipsis/)
  assert.doesNotMatch(catalog, /v-for="number in totalPages"/)
})
