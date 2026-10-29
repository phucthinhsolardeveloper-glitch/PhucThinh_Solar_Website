import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isPublicHomePayload } from '../shared/contracts/public-home.ts'
const homePreview = readFileSync(new URL('../app/components/home/HomePageContent.vue', import.meta.url), 'utf8')
const homeHero = readFileSync(new URL('../app/components/home/HomeHero.vue', import.meta.url), 'utf8')
const homeCatalog = readFileSync(new URL('../app/components/home/HomeCatalog.vue', import.meta.url), 'utf8')
const homeCampaigns = readFileSync(new URL('../app/components/home/HomeCampaigns.vue', import.meta.url), 'utf8')
const homeEditorial = readFileSync(new URL('../app/components/home/HomeEditorial.vue', import.meta.url), 'utf8')
const homeContact = readFileSync(new URL('../app/components/home/HomeContact.vue', import.meta.url), 'utf8')
const homePhpSource = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/sources/index.php', import.meta.url), 'utf8')
const homePhpApi = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/api/pts_v1_home.php', import.meta.url), 'utf8')
const good = { schemaVersion: 1, locale: 'vi', path: '/', seo: { title: '', description: '' }, hero: [{ key: 'x', name: 'Hero', description: '', image: '/assets/images/site-visuals/a.webp', href: '/san-pham/' }], categories: [{ id: 1, name: 'Đèn', slug: 'den', url: '/danh-muc-san-pham/den/', major: true, image: '' }], campaigns: [{ name: 'Campaign', description: '', image: '/assets/images/site-visuals/a.webp', href: '/san-pham/' }], solutions: [{ label: 'Gia đình', name: 'Solar', description: 'Mô tả', image: '/assets/images/site-visuals/a.webp', href: '/giai-phap/' }], process: [{ number: '01', name: 'Tư vấn', description: 'Mô tả' }], dealer: { title: 'Đại lý', description: 'Mô tả', href: '/dai-ly/' }, about: { title: 'About', description: 'Mô tả', href: '/gioi-thieu/', action: 'Xem' }, contact: { hotline: '', email: '', address: '', workingHours: '' }, productGroups: [{ categoryId: 1, categoryName: 'Đèn', categoryUrl: '/danh-muc-san-pham/den/', products: [{ id: 2, name: 'Sản phẩm', slug: 'san-pham', href: '/san-pham/san-pham/', image: '/upload/product/a.webp' }] }], summary: { hero: 1, categories: 1, campaigns: 1, solutions: 1, process: 1, productGroups: 1 } }
test('accepts the homepage PHP DTO without price fields', () => assert.equal(isPublicHomePayload(good), true))
test('accepts CMS-backed homepage about points while preserving compatibility with older DTOs', () => {
  const withPoints = { ...good, about: { ...good.about, points: [{ label: 'Sản phẩm chính hãng', description: 'Thông tin rõ ràng.', image: '/upload/news/home-point.webp' }] } }
  assert.equal(isPublicHomePayload(withPoints), true)
  assert.equal(isPublicHomePayload({ ...withPoints, about: { ...withPoints.about, points: [{ ...withPoints.about.points[0], image: 'https://outside.test/icon.svg' }] } }), false)
})
test('homepage preview follows the PHP section order without inserting product grids', () => {
  assert.match(homePreview, /HomeHero|<HomeHero\b/)
  assert.match(homePreview, /<HomeCatalog\b/)
  assert.doesNotMatch(homePreview, /HomeProductGroups/)
  assert.match(homePreview, /<HomeCampaigns\b/)
  assert.match(homePreview, /<HomeEditorial\b/)
  assert.match(homePreview, /<HomeContact\b/)
  assert.ok(homePreview.indexOf('<HomeHero') < homePreview.indexOf('<HomeCatalog'))
  assert.ok(homePreview.indexOf('<HomeCatalog') < homePreview.indexOf('<HomeCampaigns'))
  assert.ok(homePreview.indexOf('<HomeCampaigns') < homePreview.indexOf('<HomeEditorial'))
  assert.ok(homePreview.indexOf('<HomeEditorial') < homePreview.indexOf('<HomeContact'))
  assert.match(homeCatalog, /id="home-catalog-title"/)
  assert.match(homeCatalog, /v-for="slot in categoryRail"/)
  assert.match(homeCatalog, /featured/)
  assert.match(homeCatalog, /categoryRail/)
  assert.match(homeCatalog, /category-panels-user-2026\.jpg/)
  assert.match(homeCatalog, /props\.campaigns\[0\]/)
  assert.match(homeEditorial, /v-for="\(point, index\) in aboutPoints"/)
  assert.match(homeEditorial, /Sản phẩm chính hãng/)
  assert.match(homePreview, /campaigns-configured="\(home\.summary\.campaignsConfigured \?\? 0\) > 0"/)
  assert.match(homeHero, /heroImageUnavailable/)
  assert.match(homeHero, /@error="markImageFailed"/)
  assert.match(homeHero, /heroImageUnavailable \? 'bg-slate-50' : 'bg-navy-950'/)
  assert.match(homeCampaigns, /props\.campaignsConfigured \? \[\] : phpFallback/)
  assert.match(homePhpSource, /\$splistnb/)
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
test('homepage read-only API follows the PHP major-category order and exposes CMS about points', () => {
  assert.match(homePhpApi, /majorCategories/)
  assert.match(homePhpApi, /home-about-point/)
  assert.match(homePhpApi, /'points'=>\$aboutPoints/)
  assert.match(homePhpSource, /Hệ thống kho ba khu vực/)
})
