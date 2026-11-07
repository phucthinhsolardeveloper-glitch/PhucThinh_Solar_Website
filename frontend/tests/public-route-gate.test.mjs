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
  brandDetailPublicPath,
  bareSlugPublicPath,
  isProductCategoryHierarchy,
  productCategoryPublicPath,
  productDetailPublicPath,
  VIDEO_PUBLIC_PATH,
} from '../shared/public-route-gate.ts'

test('all migrated routes are permanently Nuxt-owned without runtime enable flags', async () => {
  const { readFileSync } = await import('node:fs')
  const config = readFileSync(new URL('../nuxt.config.ts', import.meta.url), 'utf8')
  const middleware = ['home', 'about', 'catalog', 'product-detail', 'solutions', 'contact', 'dealer', 'news', 'projects', 'gallery', 'video', 'cart', 'checkout', 'order-success']
    .map((name) => readFileSync(new URL(`../app/middleware/${name}-public-route.ts`, import.meta.url), 'utf8'))
    .join('\n')
  assert.doesNotMatch(config, /PublicRouteEnabled/)
  assert.doesNotMatch(middleware, /PublicRouteEnabled|isRuntimeFlagEnabled|phpPublicRouteUrl/)
})

test('static public paths are exact', () => {
  const paths = [
    [BRAND_ROOT_PUBLIC_PATH, '/thuong-hieu/'],
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
  }
})

test('product category path validates one to four hierarchical slugs and keeps the canonical trailing slash', () => {
  assert.equal(isProductCategoryHierarchy(['den-nlmt']), true)
  assert.equal(isProductCategoryHierarchy(['den-nlmt', 'den-duong', 'den-ufo', 'den-ufo-1000w']), true)
  assert.equal(isProductCategoryHierarchy([]), false)
  assert.equal(isProductCategoryHierarchy(['one', 'two', 'three', 'four', 'five']), false)
  assert.equal(isProductCategoryHierarchy(['den-nlmt', '../private']), false)
  assert.equal(productCategoryPublicPath(['den-nlmt', 'den-duong']), '/danh-muc-san-pham/den-nlmt/den-duong/')
  assert.throws(() => productCategoryPublicPath([]), /Invalid product category path/)
  assert.throws(() => productCategoryPublicPath(['one', 'two', 'three', 'four', 'five']), /Invalid product category path/)
})

test('product detail slug builds the canonical no-trailing-slash path and rejects unsafe slugs', () => {
  assert.equal(productDetailPublicPath('den-ufo-1000w'), '/san-pham/den-ufo-1000w')
  assert.throws(() => productDetailPublicPath('../../etc/passwd'), /Invalid product slug/)
  assert.throws(() => productDetailPublicPath(''), /Invalid product slug/)
  assert.throws(() => productDetailPublicPath('<script>'), /Invalid product slug/)
  assert.throws(() => productDetailPublicPath('invalid--slug'), /Invalid product slug/)
  assert.throws(() => productDetailPublicPath('trailing-'), /Invalid product slug/)
})

test('brand detail slug builds the trailing-slash path and rejects unsafe slugs', () => {
  assert.equal(brandDetailPublicPath('longi'), '/thuong-hieu/longi/')
  assert.throws(() => brandDetailPublicPath('../etc'), /Invalid brand slug/)
  assert.throws(() => brandDetailPublicPath('-longi'), /Invalid brand slug/)
})

test('bare published slug accepts safe slugs and rejects reserved paths', () => {
  assert.equal(bareSlugPublicPath('tin-tuc-thuc-te'), '/tin-tuc-thuc-te/')
  assert.throws(() => bareSlugPublicPath('../private'), /Invalid public slug path/)
  assert.throws(() => bareSlugPublicPath('tin-tuc--moi'), /Invalid public slug path/)
  assert.throws(() => bareSlugPublicPath('giai-phap'), /Invalid public slug path/)
})

test('checkout posts to the API, which redirects with site-root paths', () => {
  assert.equal(CART_CHECKOUT_POST_PATH, '/api/v1/checkout')
})
