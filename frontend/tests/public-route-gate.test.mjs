import assert from 'node:assert/strict'
import { test } from 'node:test'
import {
  CONTACT_PUBLIC_PATH,
  DEALER_PUBLIC_PATH,
  GALLERY_PUBLIC_PATH,
  HOME_PUBLIC_PATH,
  NEWS_PUBLIC_PATH,
  PROJECTS_PUBLIC_PATH,
  ABOUT_PUBLIC_PATH,
  BRAND_ROOT_PUBLIC_PATH,
  SOLUTIONS_PUBLIC_PATH,
  CATALOG_PUBLIC_PATH,
  SEARCH_PUBLIC_PATH,
  CART_PUBLIC_PATH,
  CART_CHECKOUT_POST_PATH,
  ORDER_SUCCESS_PUBLIC_PATH,
  LEGACY_CATALOG_PUBLIC_PATH,
  brandDetailPublicPath,
  bareSlugPublicPath,
  isProductCategoryHierarchy,
  isRuntimeFlagEnabled,
  legacyCatalogFilterQuery,
  phpPublicRouteUrl,
  productCategoryPublicPath,
  productDetailPublicPath,
  legacyProductDetailPublicPath,
  VIDEO_PUBLIC_PATH,
} from '../shared/public-route-gate.ts'

test('public About route stays disabled unless the explicit runtime flag is enabled', () => {
  assert.equal(isRuntimeFlagEnabled(false), false)
  assert.equal(isRuntimeFlagEnabled(undefined), false)
  assert.equal(isRuntimeFlagEnabled('0'), false)
  assert.equal(isRuntimeFlagEnabled('true'), true)
  assert.equal(isRuntimeFlagEnabled(1), false)
})

test('flag-off fallback targets the configured PHP route exactly', () => {
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', ABOUT_PUBLIC_PATH), 'http://127.0.0.1:8080/gioi-thieu/')
  assert.equal(phpPublicRouteUrl('https://php.internal', ABOUT_PUBLIC_PATH), 'https://php.internal/gioi-thieu/')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', SOLUTIONS_PUBLIC_PATH), 'http://127.0.0.1:8080/giai-phap/')
})

test('public route fallback rejects unsafe origins and paths', () => {
  assert.throws(() => phpPublicRouteUrl('javascript:alert(1)', ABOUT_PUBLIC_PATH), /Untrusted PHP public origin|Invalid URL/)
  assert.throws(() => phpPublicRouteUrl('https://user:pass@php.internal', ABOUT_PUBLIC_PATH), /Untrusted PHP public origin/)
  assert.throws(() => phpPublicRouteUrl('https://php.internal/path', ABOUT_PUBLIC_PATH), /Untrusted PHP public origin/)
  assert.throws(() => phpPublicRouteUrl('https://php.internal', '/admin/'), /Unsupported public route fallback/)
})

test('catalog and search static fallbacks resolve exactly', () => {
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', CATALOG_PUBLIC_PATH), 'http://127.0.0.1:8080/san-pham/')
  assert.equal(BRAND_ROOT_PUBLIC_PATH, '/thuong-hieu/')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', BRAND_ROOT_PUBLIC_PATH), 'http://127.0.0.1:8080/thuong-hieu/')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', SEARCH_PUBLIC_PATH), 'http://127.0.0.1:8080/tim-kiem/')
})

test('legacy product root alias is an explicit flag-off PHP fallback path', () => {
  assert.equal(LEGACY_CATALOG_PUBLIC_PATH, '/product/')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', LEGACY_CATALOG_PUBLIC_PATH), 'http://127.0.0.1:8080/product/')
  assert.equal(legacyProductDetailPublicPath('published-product'), '/product/published-product')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', legacyProductDetailPublicPath('published-product')), 'http://127.0.0.1:8080/product/published-product')
  assert.throws(() => legacyProductDetailPublicPath('../private'), /Invalid legacy product slug/)
})

test('all additional static PHP public paths are explicit and accepted', () => {
  const paths = [
    [HOME_PUBLIC_PATH, '/'],
    [CONTACT_PUBLIC_PATH, '/lien-he/'],
    [DEALER_PUBLIC_PATH, '/dai-ly/'],
    [NEWS_PUBLIC_PATH, '/tin-tuc/'],
    [PROJECTS_PUBLIC_PATH, '/du-an-da-thuc-hien/'],
    [GALLERY_PUBLIC_PATH, '/thu-vien-anh/'],
    [VIDEO_PUBLIC_PATH, '/video/'],
  ]
  for (const [path, expected] of paths) {
    assert.equal(path, expected)
    assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', path), `http://127.0.0.1:8080${expected}`)
  }
})

test('product category path validates one to four hierarchical slugs and keeps the canonical trailing slash', () => {
  assert.equal(isProductCategoryHierarchy(['den-nlmt']), true)
  assert.equal(isProductCategoryHierarchy(['den-nlmt', 'den-duong', 'den-ufo', 'den-ufo-1000w']), true)
  assert.equal(isProductCategoryHierarchy([]), false)
  assert.equal(isProductCategoryHierarchy(['one', 'two', 'three', 'four', 'five']), false)
  assert.equal(isProductCategoryHierarchy(['den-nlmt', '../private']), false)
  assert.equal(productCategoryPublicPath(['den-nlmt', 'den-duong']), '/danh-muc-san-pham/den-nlmt/den-duong/')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', productCategoryPublicPath(['den-nlmt'])), 'http://127.0.0.1:8080/danh-muc-san-pham/den-nlmt/')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', productCategoryPublicPath(['den-nlmt', 'den-duong', 'den-ufo', 'den-ufo-1000w'])), 'http://127.0.0.1:8080/danh-muc-san-pham/den-nlmt/den-duong/den-ufo/den-ufo-1000w/')
  assert.throws(() => productCategoryPublicPath([]), /Invalid product category path/)
  assert.throws(() => productCategoryPublicPath(['one', 'two', 'three', 'four', 'five']), /Invalid product category path/)
  assert.throws(() => phpPublicRouteUrl('http://127.0.0.1:8080', '/danh-muc-san-pham/a//b/'), /Unsupported public route fallback/)
  assert.throws(() => phpPublicRouteUrl('http://127.0.0.1:8080', '/danh-muc-san-pham/a'), /Unsupported public route fallback/)
})

test('category fallback maps valid Nuxt filter/sort/page state to legacy PHP query names', () => {
  assert.deepEqual(legacyCatalogFilterQuery({
    page: '3', brand: '4,8', price: '1,3', subcategory: '10,12', sort: 'abc-desc',
    keyword: 'đèn đường', need: 'du-an-cong-trinh,san-vuon-canh-quan', category: 'ignored',
  }), {
    p: '3', is_brand: '4,8', is_price: '1,3', is_cat: '10,12', is_orderby: 'abc-desc',
    keyword: 'đèn đường', is_need: 'du-an-cong-trinh,san-vuon-canh-quan',
  })
  assert.deepEqual(legacyCatalogFilterQuery({ page: '0', brand: '1,', price: '4', sort: 'random', keyword: 'x'.repeat(101), need: 'made-up' }), {})
  assert.deepEqual(legacyCatalogFilterQuery({ page: ['2'], brand: '5' }), { is_brand: '5' })
})

test('product detail slug builds the canonical no-trailing-slash path and rejects unsafe slugs', () => {
  assert.equal(productDetailPublicPath('den-ufo-1000w'), '/san-pham/den-ufo-1000w')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', productDetailPublicPath('den-ufo-1000w')), 'http://127.0.0.1:8080/san-pham/den-ufo-1000w')
  assert.throws(() => productDetailPublicPath('../../etc/passwd'), /Invalid product slug/)
  assert.throws(() => productDetailPublicPath(''), /Invalid product slug/)
  assert.throws(() => productDetailPublicPath('<script>'), /Invalid product slug/)
  assert.throws(() => productDetailPublicPath('invalid--slug'), /Invalid product slug/)
  assert.throws(() => productDetailPublicPath('trailing-'), /Invalid product slug/)
})

test('brand detail slug builds the trailing-slash path and rejects unsafe slugs', () => {
  assert.equal(brandDetailPublicPath('longi'), '/thuong-hieu/longi/')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', brandDetailPublicPath('longi')), 'http://127.0.0.1:8080/thuong-hieu/longi/')
  assert.throws(() => brandDetailPublicPath('../etc'), /Invalid brand slug/)
  assert.throws(() => brandDetailPublicPath('-longi'), /Invalid brand slug/)
})

test('bare published slug fallback accepts safe slugs but rejects PHP reserved paths', () => {
  assert.equal(bareSlugPublicPath('tin-tuc-thuc-te'), '/tin-tuc-thuc-te/')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', bareSlugPublicPath('tin-tuc-thuc-te')), 'http://127.0.0.1:8080/tin-tuc-thuc-te/')
  assert.throws(() => bareSlugPublicPath('../private'), /Invalid public slug path/)
  assert.throws(() => bareSlugPublicPath('tin-tuc--moi'), /Invalid public slug path/)
  assert.throws(() => bareSlugPublicPath('giai-phap'), /Invalid public slug path/)
  assert.throws(() => phpPublicRouteUrl('http://127.0.0.1:8080', '/administrator/'), /Unsupported public route fallback/)
})

test('an unknown dynamic path still fails closed', () => {
  assert.throws(() => phpPublicRouteUrl('https://php.internal', '/san-pham/ok/extra'), /Unsupported public route fallback/)
  assert.throws(() => phpPublicRouteUrl('https://php.internal', '/thuong-hieu/ok'), /Unsupported public route fallback/)
})

test('cart static fallback resolves exactly', () => {
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', CART_PUBLIC_PATH), 'http://127.0.0.1:8080/gio-hang/')
})

test('checkout mutation URL omits the slash so PHP relative redirects resolve from site root', () => {
  const action = phpPublicRouteUrl('http://localhost:8080', CART_CHECKOUT_POST_PATH)
  assert.equal(CART_CHECKOUT_POST_PATH, '/gio-hang')
  assert.equal(action, 'http://localhost:8080/gio-hang')
  assert.equal(new URL('gio-hang/', action).pathname, '/gio-hang/')
  assert.equal(new URL('cam-on/?token=abc123', action).pathname, '/cam-on/')
  assert.equal(new URL('cam-on/?token=abc123', action).searchParams.get('token'), 'abc123')
  assert.equal(new URL('cam-on/?token=abc123', `${action}/`).pathname, '/gio-hang/cam-on/')
})

test('order-success fallback forwards a non-empty token query and omits an empty one', () => {
  assert.equal(
    phpPublicRouteUrl('http://127.0.0.1:8080', ORDER_SUCCESS_PUBLIC_PATH, { token: 'abc123' }),
    'http://127.0.0.1:8080/cam-on/?token=abc123',
  )
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', ORDER_SUCCESS_PUBLIC_PATH, { token: '' }), 'http://127.0.0.1:8080/cam-on/')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', ORDER_SUCCESS_PUBLIC_PATH), 'http://127.0.0.1:8080/cam-on/')
})
