import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isPublicHomePayload } from '../shared/contracts/public-home.ts'
import { normalizePublicHomeHero } from '../shared/contracts/home-hero.ts'
const homePreview = readFileSync(new URL('../app/components/home/HomePageContent.vue', import.meta.url), 'utf8')
const homeHero = readFileSync(new URL('../app/components/home/HomeHero.vue', import.meta.url), 'utf8')
const homeCatalog = readFileSync(new URL('../app/components/home/HomeCatalog.vue', import.meta.url), 'utf8')
const homeCampaigns = readFileSync(new URL('../app/components/home/HomeCampaigns.vue', import.meta.url), 'utf8')
const homeEditorial = readFileSync(new URL('../app/components/home/HomeSolutions.vue', import.meta.url), 'utf8')
const homeAbout = readFileSync(new URL('../app/components/home/HomeAbout.vue', import.meta.url), 'utf8')
const homeContact = readFileSync(new URL('../app/components/home/HomeContact.vue', import.meta.url), 'utf8')
const good = { schemaVersion: 1, locale: 'vi', path: '/', seo: { title: '', description: '' }, hero: [{ key: 'x', name: 'Hero', description: '', image: '/assets/images/site-visuals/a.webp', href: '/san-pham/' }], categories: [{ id: 1, name: 'Đèn', slug: 'den', url: '/danh-muc-san-pham/den/', major: true, image: '' }], campaigns: [{ name: 'Campaign', description: '', image: '/assets/images/site-visuals/a.webp', href: '/san-pham/' }], solutions: [{ label: 'Gia đình', name: 'Solar', description: 'Mô tả', image: '/assets/images/site-visuals/a.webp', href: '/giai-phap/' }], process: [{ number: '01', name: 'Tư vấn', description: 'Mô tả' }], dealer: { title: 'Đại lý', description: 'Mô tả', href: '/dai-ly/' }, about: { configured: false, visible: false, title: 'About', description: 'Mô tả', href: '/gioi-thieu/', action: 'Xem' }, contact: { hotline: '', email: '', address: '', workingHours: '' }, productGroups: [{ categoryId: 1, categoryName: 'Đèn', categoryUrl: '/danh-muc-san-pham/den/', products: [{ id: 2, name: 'Sản phẩm', slug: 'san-pham', href: '/san-pham/san-pham/', image: '/upload/product/a.webp' }] }], summary: { hero: 1, categories: 1, campaigns: 1, solutions: 1, process: 1, productGroups: 1 } }
test('accepts the homepage PHP DTO without price fields', () => assert.equal(isPublicHomePayload(good), true))
test('homepage hero slots follow the PHP semantic title mapping when CMS rows are reordered', () => {
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
  assert.match(homeHero, /if \(props\.hero\.length > 1 && !reducedMotion\.value && !document\.hidden && !pointerInside && !focusInside && touchStartX === null\)/)
  assert.match(homeHero, /duration-\[900ms\][^\n]*motion-reduce:transition-none/)
  assert.match(homeHero, /motion-reduce:transition-none/)
  assert.match(homeHero, /@focusin="onFocusIn"/)
  assert.match(homeHero, /@focusout="onFocusOut"/)
  assert.match(homeHero, /:inert="index !== active"/)
  assert.match(homeHero, /:tabindex="index === active \? 0 : -1"/)
  assert.match(homeHero, /aria-label="Banner trước"[\s\S]*<svg[\s\S]*aria-label="Banner tiếp theo"[\s\S]*<svg/)
  assert.match(homeHero, /max-\[640px\]:text-\[15\.5px\]/)
})
test('accepts CMS-backed homepage about points while preserving compatibility with older DTOs', () => {
  const withPoints = { ...good, about: { ...good.about, points: [{ label: 'Sản phẩm chính hãng', description: 'Thông tin rõ ràng.', image: '/upload/news/home-point.webp' }] } }
  assert.equal(isPublicHomePayload(withPoints), true)
  assert.equal(isPublicHomePayload({ ...withPoints, about: { ...withPoints.about, points: [{ ...withPoints.about.points[0], image: 'https://outside.test/icon.svg' }] } }), false)
})
test('homepage about visibility follows the configured and visible CMS state', () => {
  assert.equal(isPublicHomePayload({ ...good, about: { ...good.about, configured: true, visible: false } }), true)
  assert.equal(isPublicHomePayload({ ...good, about: { ...good.about, configured: false, visible: true } }), false)
  assert.match(homeAbout, /v-if="!about\.configured \|\| about\.visible"/)
})
test('homepage renders its sections in the published layout order, defaulting to the PHP order', () => {
  assert.match(homePreview, /v-for="block in layout"/)
  assert.match(homePreview, /pageLayout\('home'/)
  assert.doesNotMatch(homePreview, /HomeProductGroups/)
  const order = ['<HomeHero', '<HomeCatalog', '<EnergyStageSection', '<HomeCampaigns', '<HomeSolutions', '<HomeProcess', '<HomeDealer', '<HomeAbout', '<HomeContact']
  for (let i = 1; i < order.length; i++) assert.ok(homePreview.indexOf(order[i - 1]) < homePreview.indexOf(order[i]), order[i])
  assert.match(homeCatalog, /id="home-catalog-title"/)
  assert.match(homeCatalog, /v-for="slot in chain"/)
  assert.match(homeCatalog, /const slots = \[/)
  assert.match(homeCatalog, /props\.categories\.find/)
  assert.match(homeCatalog, /category-panels-user-2026\.jpg/)
  assert.match(homeCatalog, /slot\.category\?\.url \|\| slot\.href/)
  assert.doesNotMatch(homeCatalog, /category\?\.image \|\|/)
  assert.match(homeAbout, /v-for="point in points"/)
  assert.match(homeAbout, /Sản phẩm chính hãng/)
  assert.match(homePreview, /campaigns-configured="\(home\.summary\.campaignsConfigured \?\? 0\) > 0"/)
  assert.match(homeHero, /heroImageUnavailable/)
  assert.match(homeHero, /@error="markImageFailed"/)
  assert.match(homeHero, /heroImageUnavailable \? 'bg-slate-50' : 'bg-navy-950'/)
  assert.match(homeCampaigns, /props\.campaignsConfigured \? \[\] : phpFallback/)
})

test('homepage hero copy and calls to action stay centered across breakpoints', () => {
  assert.match(homeHero, /relative z-\[4\][^\"]*items-start justify-start/)
  assert.match(homeHero, /max-w-\[820px\] flex-col items-center text-center/)
  assert.match(homeHero, /id="home-title"[^>]*text-center/)
  assert.match(homeHero, /flex w-full flex-wrap items-center justify-center/)
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
  ]) assert.ok(homeCampaigns.includes(copy), `Campaign fallback missing: ${copy}`)
  assert.match(homeCampaigns, /props\.campaignsConfigured \? \[\] : phpFallback/)
})
test('homepage Admin SEO fields use the legacy setting row and logo for OG image', () => {
  const withAdminSeo = { ...good, seo: { title: 'Admin title', description: 'Admin description', keywords: 'solar', image: '/upload/photo/logo.webp' } }
  assert.equal(isPublicHomePayload(withAdminSeo), true)
  assert.equal(isPublicHomePayload({ ...withAdminSeo, seo: { ...withAdminSeo.seo, image: '/upload/photo/logo.avif' } }), true)
  assert.equal(isPublicHomePayload({ ...withAdminSeo, seo: { ...withAdminSeo.seo, image: '/upload/photo/../secret.jpg' } }), false)
  assert.match(homePreview, /home\.value\?\.seo\.title \|\| fallbackTitle\.value/)
  assert.match(homePreview, /home\.value\?\.seo\.image \|\| undefined/)
  assert.ok(homePreview.includes("name: 'keywords', content: pageKeywords.value"))
})

test('Admin popup is optional, validated and shown on the homepage only once per campaign', () => {
  const popup = { key: '796f6a1d3caa064c9ddb', image: '/upload/photo/campaign.png', title: 'Khuyến mãi', href: '/san-pham/' }
  assert.equal(isPublicHomePayload({ ...good, popup }), true)
  assert.equal(isPublicHomePayload({ ...good, popup: null }), true)
  assert.equal(isPublicHomePayload({ ...good, popup: { ...popup, href: 'javascript:alert(1)' } }), false)
  assert.equal(isPublicHomePayload({ ...good, popup: { ...popup, image: 'https://outside.test/a.png' } }), false)
  assert.equal(isPublicHomePayload({ ...good, popup: { ...popup, key: '<x>' } }), false)
  const component = readFileSync(new URL('../app/components/home/HomePromoPopup.vue', import.meta.url), 'utf8')
  assert.match(homePreview, /<HomePromoPopup v-if="home\.popup"/)
  assert.match(component, /const STORAGE_KEY = 'pts-popup-campaign'/)
  assert.match(component, /role="dialog" aria-modal="true"/)
  assert.match(component, /event\.key === 'Escape'/)
  assert.match(component, /@click\.self="close"/)
  assert.match(component, /prefers-reduced-motion: reduce/)
})

test('Admin energy-chain cards override per field and keep the default layout', () => {
  const card = { name: 'Tấm pin', stage: 'Đón nắng', description: '', action: '', image: '' }
  assert.equal(isPublicHomePayload({ ...good, energyChain: [card] }), true)
  assert.equal(isPublicHomePayload({ ...good, energyChain: Array(6).fill(card) }), false)
  assert.equal(isPublicHomePayload({ ...good, energyChain: [{ ...card, image: 'https://outside.test/a.jpg' }] }), false)
  assert.match(homeCatalog, /title: admin\?\.name \|\| category\?\.name \|\| slot\.name/)
  assert.match(homeCatalog, /description: admin\?\.description \|\| slot\.description/)
  assert.match(homeCatalog, /\.chain-title \{[^}]*-webkit-line-clamp: 2/)
  assert.match(homeCatalog, /\.chain-desc \{[^}]*-webkit-line-clamp: 3/)
  assert.match(homePreview, /:energy-chain="home\.energyChain"/)
})
