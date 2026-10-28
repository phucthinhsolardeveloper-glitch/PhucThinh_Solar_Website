import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isPublicHomePayload } from '../shared/contracts/public-home.ts'
const homePreview = readFileSync(new URL('../app/pages/migration-home-preview.vue', import.meta.url), 'utf8')
const homeCatalog = readFileSync(new URL('../app/components/home/HomeCatalog.vue', import.meta.url), 'utf8')
const homeCampaigns = readFileSync(new URL('../app/components/home/HomeCampaigns.vue', import.meta.url), 'utf8')
const good = { schemaVersion: 1, locale: 'vi', path: '/', seo: { title: '', description: '' }, hero: [{ key: 'x', name: 'Hero', description: '', image: '/assets/images/site-visuals/a.webp', href: '/san-pham/' }], categories: [{ id: 1, name: 'Đèn', slug: 'den', url: '/danh-muc-san-pham/den/', major: true, image: '' }], campaigns: [{ name: 'Campaign', description: '', image: '/assets/images/site-visuals/a.webp', href: '/san-pham/' }], solutions: [{ label: 'Gia đình', name: 'Solar', description: 'Mô tả', image: '/assets/images/site-visuals/a.webp', href: '/giai-phap/' }], process: [{ number: '01', name: 'Tư vấn', description: 'Mô tả' }], dealer: { title: 'Đại lý', description: 'Mô tả', href: '/dai-ly/' }, about: { title: 'About', description: 'Mô tả', href: '/gioi-thieu/', action: 'Xem' }, contact: { hotline: '', email: '', address: '', workingHours: '' }, productGroups: [{ categoryId: 1, categoryName: 'Đèn', categoryUrl: '/danh-muc-san-pham/den/', products: [{ id: 2, name: 'Sản phẩm', slug: 'san-pham', href: '/san-pham/san-pham/', image: '/upload/product/a.webp' }] }], summary: { hero: 1, categories: 1, campaigns: 1, solutions: 1, process: 1, productGroups: 1 } }
test('accepts the homepage PHP DTO without price fields', () => assert.equal(isPublicHomePayload(good), true))
test('homepage preview is product-first with one catalog H1 and no long editorial blocks', () => {
  assert.doesNotMatch(homePreview, /HomeHero|<HomeHero\b/)
  assert.match(homePreview, /<HomeCatalog\b/)
  assert.match(homePreview, /<HomeCampaigns\b/)
  assert.doesNotMatch(homePreview, /HomeEditorial|<HomeEditorial\b/)
  assert.match(homeCatalog, /<h1 id="home-catalog-title"/)
  assert.match(homeCatalog, /max-md:flex-col/)
  assert.match(homeCatalog, /w-full max-w-\[850px\]/)
  assert.match(homeCatalog, /grid-flow-col auto-cols-\[80vw\].*md:grid-flow-row md:grid-cols-4/)
  assert.match(homePreview, /campaigns-configured="\(home\.summary\.campaignsConfigured \?\? 0\) > 0"/)
  assert.match(homeCampaigns, /props\.campaignsConfigured \? \[\] : phpFallback/)
})
test('rejects unsafe media and external category claims', () => { assert.equal(isPublicHomePayload({ ...good, hero: [{ ...good.hero[0], image: 'javascript:alert(1)' }] }), false); assert.equal(isPublicHomePayload({ ...good, categories: [{ ...good.categories[0], url: 'https://example.test/' }] }), false) })
test('does not require or expose pricing in the product projection', () => assert.equal(Object.hasOwn(good.productGroups[0].products[0], 'price'), false))
