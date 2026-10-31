import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isPublicHomePayload } from '../shared/contracts/public-home.ts'
import { normalizePublicHomeHero } from '../shared/contracts/home-hero.ts'
const homePreview = readFileSync(new URL('../app/components/home/HomePageContent.vue', import.meta.url), 'utf8')
const homeHero = readFileSync(new URL('../app/components/home/HomeHero.vue', import.meta.url), 'utf8')
const homeCatalog = readFileSync(new URL('../app/components/home/HomeCatalog.vue', import.meta.url), 'utf8')
const homeCampaigns = readFileSync(new URL('../app/components/home/HomeCampaigns.vue', import.meta.url), 'utf8')
const homeEditorial = readFileSync(new URL('../app/components/home/HomeEditorial.vue', import.meta.url), 'utf8')
const homeContact = readFileSync(new URL('../app/components/home/HomeContact.vue', import.meta.url), 'utf8')
const homePhpSource = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/sources/index.php', import.meta.url), 'utf8')
const homePhpApi = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/api/pts_v1_home.php', import.meta.url), 'utf8')
const good = { schemaVersion: 1, locale: 'vi', path: '/', seo: { title: '', description: '' }, hero: [{ key: 'x', name: 'Hero', description: '', image: '/assets/images/site-visuals/a.webp', href: '/san-pham/' }], categories: [{ id: 1, name: 'Đèn', slug: 'den', url: '/danh-muc-san-pham/den/', major: true, image: '' }], campaigns: [{ name: 'Campaign', description: '', image: '/assets/images/site-visuals/a.webp', href: '/san-pham/' }], solutions: [{ label: 'Gia đình', name: 'Solar', description: 'Mô tả', image: '/assets/images/site-visuals/a.webp', href: '/giai-phap/' }], process: [{ number: '01', name: 'Tư vấn', description: 'Mô tả' }], dealer: { title: 'Đại lý', description: 'Mô tả', href: '/dai-ly/' }, about: { configured: false, visible: false, title: 'About', description: 'Mô tả', href: '/gioi-thieu/', action: 'Xem' }, contact: { hotline: '', email: '', address: '', workingHours: '' }, productGroups: [{ categoryId: 1, categoryName: 'Đèn', categoryUrl: '/danh-muc-san-pham/den/', products: [{ id: 2, name: 'Sản phẩm', slug: 'san-pham', href: '/san-pham/san-pham/', image: '/upload/product/a.webp' }] }], summary: { hero: 1, categories: 1, campaigns: 1, solutions: 1, process: 1, productGroups: 1 } }
test('accepts the homepage PHP DTO without price fields', () => assert.equal(isPublicHomePayload(good), true))
test('homepage hero slots follow the PHP semantic title mapping when CMS rows are reordered', () => {
  for (const sourceNeedle of ['array(\'đèn đường\', \'den duong\', \'street light\')', 'array(\'biến tần\', \'bien tan\', \'inverter\')', 'array(\'tấm pin\', \'tam pin\', \'solar panel\')', 'array(\'pin lưu trữ\', \'pin luu tru\', \'battery\', \'lưu trữ\')']) {
    assert.ok(homePhpSource.includes(sourceNeedle), `PHP hero semantic mapping changed: ${sourceNeedle}`)
  }
  assert.match(homePhpApi, /\$row = \$heroEnabled\[\$i\] \?\? array\(\)/)
  const rows = [
    { key: 'street-light', name: 'Pin lưu trữ lithium', description: 'storage', image: '/upload/photo/storage.jpg', href: '/pin-luu-tru/' },
    { key: 'inverter', name: 'Tấm pin năng lượng mặt trời', description: 'panel', image: '/upload/photo/panel.jpg', href: '/tam-pin/' },
    { key: 'panel', name: 'Biến tần hybrid', description: 'inverter', image: '/upload/photo/inverter.jpg', href: '/bien-tan/' },
    { key: 'storage', name: 'Đèn đường năng lượng mặt trời', description: 'street', image: '/upload/photo/street.jpg', href: '/den-duong/' },
  ]
  const normalized = normalizePublicHomeHero(rows)
  assert.deepEqual(normalized.map(({ key, name }) => [key, name]), [
    ['street-light', 'Đèn đường năng lượng mặt trời'],
    ['inverter', 'Biến tần hybrid'],
    ['panel', 'Tấm pin năng lượng mặt trời'],
    ['storage', 'Pin lưu trữ lithium'],
  ])
  assert.deepEqual(normalized.map(({ image }) => image), [
    '/upload/photo/street.jpg', '/upload/photo/inverter.jpg', '/upload/photo/panel.jpg', '/upload/photo/storage.jpg',
  ])
})
test('homepage hero keeps PHP positional fallback and repeats a semantically matched CMS row when needed', () => {
  const rows = [
    { key: 'street-light', name: 'Tấm pin mặt trời', description: '', image: '/upload/photo/panel.jpg', href: '/tam-pin/' },
  ]
  const normalized = normalizePublicHomeHero(rows)
  assert.deepEqual(normalized.map(({ key, image }) => [key, image]), [
    ['street-light', '/upload/photo/panel.jpg'],
    ['panel', '/upload/photo/panel.jpg'],
  ])
})
test('homepage hero safely uses positional fallback when a slide name is absent', () => {
  const normalized = normalizePublicHomeHero([{ key: 'legacy', image: '/upload/photo/hero.jpg', href: '/' }])
  assert.deepEqual(normalized.map(({ key, image }) => [key, image]), [
    ['street-light', '/upload/photo/hero.jpg'],
  ])
})
test('homepage carousel keeps timed motion out of keyboard, pointer, touch, and reduced-motion interactions', () => {
  assert.match(homeHero, /window\.matchMedia\('\(prefers-reduced-motion: reduce\)'\)/)
  assert.match(homeHero, /if \(props\.hero\.length > 1 && !document\.hidden && !pointerInside && !focusInside && touchStartX === null\)/)
  assert.match(homeHero, /duration-700 ease-in-out motion-reduce:transition-none/)
  assert.match(homeHero, /motion-reduce:transition-none/)
  assert.match(homeHero, /@focusin="onFocusIn"/)
  assert.match(homeHero, /@focusout="onFocusOut"/)
  assert.match(homeHero, /:inert="index !== active"/)
  assert.match(homeHero, /:tabindex="index === active \? 0 : -1"/)
  assert.match(homeHero, /aria-label="Banner trước"[\s\S]*<svg[\s\S]*aria-label="Banner tiếp theo"[\s\S]*<svg/)
  assert.match(homeHero, /max-\[640px\]:leading-snug line-clamp-2/)
})
test('accepts CMS-backed homepage about points while preserving compatibility with older DTOs', () => {
  const withPoints = { ...good, about: { ...good.about, points: [{ label: 'Sản phẩm chính hãng', description: 'Thông tin rõ ràng.', image: '/upload/news/home-point.webp' }] } }
  assert.equal(isPublicHomePayload(withPoints), true)
  assert.equal(isPublicHomePayload({ ...withPoints, about: { ...withPoints.about, points: [{ ...withPoints.about.points[0], image: 'https://outside.test/icon.svg' }] } }), false)
})
test('homepage about visibility follows the configured and visible CMS state', () => {
  assert.equal(isPublicHomePayload({ ...good, about: { ...good.about, configured: true, visible: false } }), true)
  assert.equal(isPublicHomePayload({ ...good, about: { ...good.about, configured: false, visible: true } }), false)
  assert.match(homeEditorial, /v-if="!data\.about\.configured \|\| data\.about\.visible"/)
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
  assert.match(homeCatalog, /image: slot\.image/)
  assert.doesNotMatch(homeCatalog, /category\?\.image \|\|/)
  assert.match(homeEditorial, /v-for="\(point, index\) in aboutPoints"/)
  assert.match(homeEditorial, /Sản phẩm chính hãng/)
  assert.match(homePreview, /campaigns-configured="\(home\.summary\.campaignsConfigured \?\? 0\) > 0"/)
  assert.match(homeHero, /heroImageUnavailable/)
  assert.match(homeHero, /@error="markImageFailed"/)
  assert.match(homeHero, /heroImageUnavailable \? 'bg-slate-50' : 'bg-navy-950'/)
  assert.match(homeCampaigns, /props\.campaignsConfigured \? \[\] : phpFallback/)
  assert.match(homePhpSource, /\$splistnb/)
})

test('homepage hero copy and calls to action stay centered across breakpoints', () => {
  assert.match(homeHero, /relative z-\[4\][^\"]*items-start justify-start/)
  assert.match(homeHero, /max-w-\[820px\] flex-col items-center text-center/)
  assert.match(homeHero, /id="home-title"[^>]*text-center/)
  assert.match(homeHero, /flex w-full max-w-\[820px\] flex-wrap justify-center/)
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
  assert.match(homePhpApi, /'configured'=>\$aboutConfigured,'visible'=>\$aboutVisible/)
  assert.match(homePhpApi, /'needles'=>array\('gia đình','gia dinh','hộ gia đình','ho gia dinh'\)/)
  assert.doesNotMatch(homePhpApi, /\$row = \$solutionRows\[\$i\]/)
  assert.match(homePhpSource, /Hệ thống kho ba khu vực/)
})
test('homepage Admin SEO fields use the legacy setting row and logo for OG image', () => {
  const withAdminSeo = { ...good, seo: { title: 'Admin title', description: 'Admin description', keywords: 'solar', image: '/upload/photo/logo.webp' } }
  assert.equal(isPublicHomePayload(withAdminSeo), true)
  assert.equal(isPublicHomePayload({ ...withAdminSeo, seo: { ...withAdminSeo.seo, image: '/upload/photo/logo.avif' } }), true)
  assert.equal(isPublicHomePayload({ ...withAdminSeo, seo: { ...withAdminSeo.seo, image: '/upload/photo/../secret.jpg' } }), false)
  assert.match(homePhpApi, /#_seo where com=\? and act=\? and type=\? limit 1', array\('setting','update','setting'\)/)
  assert.match(homePhpApi, /#_photo where type=\? and act=\? limit 0,1', array\('logo','photo_static'\)/)
  assert.match(homePhpApi, /ptsPublicSeoDto/)
  assert.match(homePreview, /home\.value\?\.seo\.title \|\| fallbackTitle\.value/)
  assert.match(homePreview, /home\.value\?\.seo\.image \|\| undefined/)
  assert.ok(homePreview.includes("name: 'keywords', content: pageKeywords.value"))
})
