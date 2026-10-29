import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isPublicHomePayload } from '../shared/contracts/public-home.ts'
const homePreview = readFileSync(new URL('../app/components/home/HomePageContent.vue', import.meta.url), 'utf8')
const homeCatalog = readFileSync(new URL('../app/components/home/HomeCatalog.vue', import.meta.url), 'utf8')
const homeCampaigns = readFileSync(new URL('../app/components/home/HomeCampaigns.vue', import.meta.url), 'utf8')
const homeProductGroups = readFileSync(new URL('../app/components/home/HomeProductGroups.vue', import.meta.url), 'utf8')
const homeContact = readFileSync(new URL('../app/components/home/HomeContact.vue', import.meta.url), 'utf8')
const homePhpSource = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/sources/index.php', import.meta.url), 'utf8')
const good = { schemaVersion: 1, locale: 'vi', path: '/', seo: { title: '', description: '' }, hero: [{ key: 'x', name: 'Hero', description: '', image: '/assets/images/site-visuals/a.webp', href: '/san-pham/' }], categories: [{ id: 1, name: 'Đèn', slug: 'den', url: '/danh-muc-san-pham/den/', major: true, image: '' }], campaigns: [{ name: 'Campaign', description: '', image: '/assets/images/site-visuals/a.webp', href: '/san-pham/' }], solutions: [{ label: 'Gia đình', name: 'Solar', description: 'Mô tả', image: '/assets/images/site-visuals/a.webp', href: '/giai-phap/' }], process: [{ number: '01', name: 'Tư vấn', description: 'Mô tả' }], dealer: { title: 'Đại lý', description: 'Mô tả', href: '/dai-ly/' }, about: { title: 'About', description: 'Mô tả', href: '/gioi-thieu/', action: 'Xem' }, contact: { hotline: '', email: '', address: '', workingHours: '' }, productGroups: [{ categoryId: 1, categoryName: 'Đèn', categoryUrl: '/danh-muc-san-pham/den/', products: [{ id: 2, name: 'Sản phẩm', slug: 'san-pham', href: '/san-pham/san-pham/', image: '/upload/product/a.webp' }] }], summary: { hero: 1, categories: 1, campaigns: 1, solutions: 1, process: 1, productGroups: 1 } }
test('accepts the homepage PHP DTO without price fields', () => assert.equal(isPublicHomePayload(good), true))
test('homepage preview is product-first with one catalog H1 and no long editorial blocks', () => {
  assert.doesNotMatch(homePreview, /HomeHero|<HomeHero\b/)
  assert.match(homePreview, /<HomeCatalog\b/)
  assert.match(homePreview, /<HomeProductGroups :groups="home\.productGroups"\s*\/>/)
  assert.match(homePreview, /<HomeCampaigns\b/)
  assert.ok(homePreview.indexOf('<HomeCatalog') < homePreview.indexOf('<HomeProductGroups'))
  assert.ok(homePreview.indexOf('<HomeProductGroups') < homePreview.indexOf('<HomeCampaigns'))
  assert.match(homePreview, /<h1 v-if="!home\.categories\.length" id="home-catalog-title"/)
  assert.doesNotMatch(homePreview, /HomeEditorial|<HomeEditorial\b/)
  assert.match(homeCatalog, /<h1[\s\S]*?id="home-catalog-title"/)
  assert.match(homeCatalog, /v-for="category in props\.categories"/)
  assert.match(homeCatalog, /overflow-x-auto/)
  assert.match(homeCatalog, /@pointerdown="startDrag"/)
  assert.match(homeCatalog, /rail\.value\.scrollLeft = dragStartScroll - delta/)
  assert.doesNotMatch(homeCatalog, /featured|function summary|categoryRail|\.slice\(/)
  assert.match(homePreview, /campaigns-configured="\(home\.summary\.campaignsConfigured \?\? 0\) > 0"/)
  assert.match(homeCampaigns, /props\.campaignsConfigured \? \[\] : phpFallback/)
  assert.match(homeProductGroups, /links\.product\(product\.slug\)/)
  assert.match(homeProductGroups, /links\.generic\(group\.categoryUrl\)/)
  assert.match(homeProductGroups, /aspect-square[^\n]*object-contain/)
  assert.doesNotMatch(homeProductGroups, /price|regularPrice|salePrice/i)
})
test('homepage lead form captures province and interest and passes both to the POST adapter', () => {
  assert.match(homeContact, /address: form\.address/)
  assert.match(homeContact, /subject: form\.subject/)
  assert.match(homeContact, /Tỉnh\/Thành phố/)
  assert.match(homeContact, /Anh\/chị quan tâm đến/)
  assert.match(homeContact, /v-model="form\.subject"/)
})
test('rejects unsafe media and external category claims', () => { assert.equal(isPublicHomePayload({ ...good, hero: [{ ...good.hero[0], image: 'javascript:alert(1)' }] }), false); assert.equal(isPublicHomePayload({ ...good, categories: [{ ...good.categories[0], url: 'https://example.test/' }] }), false) })
test('does not require or expose pricing in the product projection', () => assert.equal(Object.hasOwn(good.productGroups[0].products[0], 'price'), false))
test('product groups may be empty or contain products without requiring price data', () => {
  assert.equal(isPublicHomePayload({ ...good, productGroups: [] }), true)
  assert.equal(isPublicHomePayload({ ...good, productGroups: [{ ...good.productGroups[0], products: [] }] }), true)
  assert.equal(isPublicHomePayload(good), true)
})
test('HomeCampaigns fallback copy is the PHP fallback used only when no campaign records exist', () => {
  for (const copy of [
    'Chiếu sáng năng lượng mặt trời cho công trình',
    'Thiết bị đồng bộ cho đường nội bộ, khuôn viên và hạ tầng thương mại.',
    'Hệ điện mặt trời và lưu trữ',
    'Chiếu sáng sân vườn và lối đi',
  ]) assert.ok(homePhpSource.includes(copy), `PHP campaign fallback missing: ${copy}`)
  assert.match(homePhpSource, /if\(!\$homeCampaignRows\)/)
  assert.match(homeCampaigns, /props\.campaignsConfigured \? \[\] : phpFallback/)
})
