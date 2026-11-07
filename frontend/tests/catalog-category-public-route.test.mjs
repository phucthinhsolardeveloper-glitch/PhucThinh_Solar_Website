import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { productCategorySegmentsFromPublicPath } from '../shared/public-route-gate.ts'

const frontendRoot = new URL('../', import.meta.url)
const read = (path) => readFileSync(new URL(path, frontendRoot), 'utf8')

test('public category route is Nuxt-owned and reuses the catalog page for validated hierarchy', () => {
  const page = read('app/pages/danh-muc-san-pham/[...slug].vue')
  const middleware = read('app/middleware/catalog-category-public-route.ts')
  const catalogPage = read('app/components/catalog/CatalogPage.vue')

  assert.match(page, /middleware:\s*['"]catalog-category-public-route['"]/)
  assert.match(page, /<CatalogPage :category-path="categoryPath"/)
  assert.doesNotMatch(middleware, /PublicRouteEnabled|phpPublicRouteUrl/)
  assert.match(middleware, /to\.path !== categoryPath/)
  assert.match(catalogPage, /categoryPath\?: string/)
  assert.match(catalogPage, /props\.categoryPath/)
  assert.match(catalogPage, /catalogDataKey = computed\(\(\) => `catalog:\$\{route\.path\}:\$\{JSON\.stringify\(query\.value\)\}`\)/)
  assert.match(catalogPage, /useAsyncData<PublicCatalogPayload>\(\s*catalogDataKey/)
  assert.match(catalogPage, /catalogCategoryPublicRouteIndexable/)
  assert.match(middleware, /productCategorySegmentsFromPublicPath\(to\.path\)/)
})

test('category path parser preserves validated catch-all levels and rejects malformed route paths', () => {
  assert.deepEqual(productCategorySegmentsFromPublicPath('/danh-muc-san-pham/den-nlmt/'), ['den-nlmt'])
  assert.deepEqual(productCategorySegmentsFromPublicPath('/danh-muc-san-pham/chieu-sang/den-duong/'), ['chieu-sang', 'den-duong'])
  assert.deepEqual(productCategorySegmentsFromPublicPath('/danh-muc-san-pham/a%2Fb/'), [])
  assert.deepEqual(productCategorySegmentsFromPublicPath('/san-pham/'), [])
})

test('brand root route reuses catalog listing and canonicalizes within Nuxt', () => {
  const page = read('app/pages/thuong-hieu/index.vue')
  const middleware = read('app/middleware/brand-root-public-route.ts')
  const catalogPage = read('app/components/catalog/CatalogPage.vue')
  assert.match(page, /<CatalogPage \/>/)
  assert.doesNotMatch(middleware, /PublicRouteEnabled|phpPublicRouteUrl/)
  assert.match(middleware, /BRAND_ROOT_PUBLIC_PATH/)
  assert.match(middleware, /redirectCode: 301/)
  assert.match(catalogPage, /route\.path === .+thuong-hieu/)
})
