import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isPublicProductRetiredPayload } from '../shared/contracts/public-product-retired.ts'
import { isPublicCatalogProduct } from '../shared/contracts/public-catalog.ts'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('a retired-product payload carries a name and a safe category link', () => {
  const ok = { schemaVersion: 1, name: 'Đèn pha JINDIAN JD-18300 (300W)', category: { name: 'Đèn pha', url: '/danh-muc-san-pham/den-nlmt/den-pha/' } }
  assert.equal(isPublicProductRetiredPayload(ok), true)
  assert.equal(isPublicProductRetiredPayload({ ...ok, category: null }), true)
  assert.equal(isPublicProductRetiredPayload({ ...ok, name: '' }), false)
  assert.equal(isPublicProductRetiredPayload({ ...ok, name: '<script>' }), false)
  assert.equal(isPublicProductRetiredPayload({ ...ok, category: { name: 'x', url: 'https://evil.test/' } }), false)
  assert.equal(isPublicProductRetiredPayload({ ...ok, category: { name: 'x', url: '/gio-hang/' } }), false)
  assert.equal(isPublicProductRetiredPayload({ ...ok, schemaVersion: 2 }), false)
})

test('a catalog card may carry featured: true and nothing else of the kind', () => {
  const card = { id: 5, name: 'Đèn A', slug: 'den-a', url: '/san-pham/den-a', media: ['/upload/product/a.jpg'] }
  assert.equal(isPublicCatalogProduct(card), true)
  assert.equal(isPublicCatalogProduct({ ...card, featured: true }), true)
  assert.equal(isPublicCatalogProduct({ ...card, featured: false }), false)
  assert.equal(isPublicCatalogProduct({ ...card, featured: 'yes' }), false)
  assert.equal(isPublicCatalogProduct({ ...card, featured: true, promoFrame: '/x.png' }), false)
})

test('the catalog offers the Featured sort and noindexes typed search words', () => {
  const page = read('../app/components/catalog/CatalogPage.vue')
  assert.match(page, /value: 'featured', label: 'Nổi bật'/)
  assert.match(page, /catalogFilterKeys = new Set\(\[[^\]]*'q'[^\]]*'s'[^\]]*'search'[^\]]*'query'\]/)
  assert.match(read('../app/components/catalog/CatalogProductGrid.vue'), /v-if="product\.featured"/)
})

test('a hidden product answers 410 with its name; an unknown one gets the 404 page; the share page says nothing about Admin', () => {
  const page = read('../app/pages/san-pham/[slug].vue')
  assert.match(page, /setResponseStatus\(410, 'Gone'\)/)
  assert.match(page, /immediate: false/)
  assert.match(page, /<NotFoundExperience v-else-if="productErrorStatus === 404"/)
  const share = read('../app/pages/xem-truoc/san-pham/[product].vue')
  assert.doesNotMatch(share, /Mở lại sản phẩm trong Admin/)
  assert.match(share, /Liên kết xem riêng đã hết hạn/)
})
