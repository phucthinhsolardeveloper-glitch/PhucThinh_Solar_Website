import assert from 'node:assert/strict'
import { existsSync, readFileSync } from 'node:fs'
import { test } from 'node:test'
import { usePublicLinks } from '../app/composables/usePublicLinks.ts'

const app = new URL('../app/', import.meta.url)
const repo = new URL('../../', import.meta.url)
const readApp = (path) => readFileSync(new URL(path, app), 'utf8')

test('public routes used by Home, shell, catalog, PDP, and policy links exist in the Nuxt route tree', () => {
  const routeFiles = [
    'frontend/app/pages/index.vue',
    'frontend/app/pages/gioi-thieu.vue',
    'frontend/app/pages/giai-phap.vue',
    'frontend/app/pages/giai-phap/[slug].vue',
    'frontend/app/pages/du-an-da-thuc-hien/index.vue',
    'frontend/app/pages/tin-tuc/index.vue',
    'frontend/app/pages/dai-ly.vue',
    'frontend/app/pages/lien-he.vue',
    'frontend/app/pages/san-pham/index.vue',
    'frontend/app/pages/san-pham/[slug].vue',
    'frontend/app/pages/danh-muc-san-pham/[...slug].vue',
    'frontend/app/pages/thuong-hieu/index.vue',
    'frontend/app/pages/thuong-hieu/[slug].vue',
    'frontend/app/pages/product/index.vue',
    'frontend/app/pages/product/[slug].vue',
    'frontend/app/pages/tim-kiem/index.vue',
    'frontend/app/pages/gio-hang/index.vue',
    'frontend/app/pages/ho-tro/[slug].vue',
    'frontend/app/pages/thu-vien-anh/index.vue',
    'frontend/app/pages/video/index.vue',
  ]
  for (const path of routeFiles) {
    assert.equal(existsSync(new URL(path, repo)), true, `missing public route file ${path}`)
  }
})

test('link builders return the canonical public paths', () => {
  const links = usePublicLinks()
  assert.equal(links.generic('/gio-hang/'), '/gio-hang/')
  assert.equal(links.generic('/lien-he/#yeu-cau-tu-van'), '/lien-he/#yeu-cau-tu-van')
  assert.equal(links.product('den-that'), '/san-pham/den-that')
  assert.equal(links.solution('dien-mat-troi'), '/giai-phap/dien-mat-troi/')
  assert.equal(links.brand('longi'), '/thuong-hieu/longi/')
  assert.equal(links.news('bai-viet-that'), '/bai-viet-that/')
  assert.equal(links.project('du-an-that'), '/du-an-that/')
  assert.equal(links.gallery('album-that'), '/album-that/')
  assert.equal(links.external('https://zalo.me/1'), true)
  assert.equal(links.external('/lien-he/'), false)
})

test('PDP cart and document links use public paths', () => {
  const purchase = readApp('components/product-detail/ProductPurchasePanel.vue')
  const documents = readApp('components/product-detail/ProductDetailDocuments.vue')
  const page = readApp('components/product-detail/ProductDetailPage.vue')

  assert.match(purchase, /usePublicLinks\(\)/)
  assert.match(purchase, /navigateTo\(links\.generic\('\/gio-hang\/'\)\)/)
  assert.match(documents, /return document\.url/)
  assert.match(page, /:consultation-available="Boolean\(extras\)"/)
  assert.match(purchase, /v-if="pricing\.mode === 'quote' && consultationAvailable"[\s\S]*href="#pts-pdp-consultation"/)
})

test('shared catalog and shell links resolve CMS internal URLs and keep exactly the approved five top-level groups', () => {
  const header = readApp('components/SiteHeader.vue')
  const footer = readApp('components/SiteFooter.vue')
  const branch = readApp('components/catalog/CatalogCategoryBranch.vue')
  const grid = readApp('components/catalog/CatalogProductGrid.vue')
  const catalog = readApp('components/catalog/CatalogPage.vue')
  const homeCatalog = readApp('components/home/HomeCatalog.vue')
  const homeProductGroups = readApp('components/home/HomeProductGroups.vue')
  const homeCampaigns = readApp('components/home/HomeCampaigns.vue')
  const homeHero = readApp('components/home/HomeHero.vue')
  const homeEditorial = readApp('components/home/HomeSolutions.vue')
  const homeAbout = readApp('components/home/HomeAbout.vue')
  const homeDealer = readApp('components/home/HomeDealer.vue')
  const directorStage = readApp('components/home/DirectorStage.vue')
  const homeContact = readApp('components/home/HomeContact.vue')
  const supportExtras = readApp('components/product-detail/ProductSupportExtras.vue')
  const navBlock = header.match(/const fallbackNav: PublicSiteLink\[\] = \[([\s\S]*?)\n\]/)?.[1]
  assert.ok(navBlock)
  const navItems = [...navBlock.matchAll(/\{ label: '([^']+)', href: '([^']+)' \}/g)].map((match) => [match[1], match[2]])
  assert.deepEqual(navItems, [
    ['Giới thiệu', '/gioi-thieu/'],
    ['Giải pháp', '/giai-phap/'],
    ['Dự án', '/du-an-da-thuc-hien/'],
    ['Kiến thức', '/tin-tuc/'],
    ['Hợp tác', '/dai-ly/'],
  ])
  assert.match(header, /const navItems = computed\(\(\) => fallbackNav\.map\(/)
  assert.match(header, /props\.site\.navigation\?\.find\(\(item\) => item\.href === fallback\.href\)/)
  assert.match(header, /shellHref\(item\.href\)/)
  assert.doesNotMatch(header, /navItems = computed\(\(\) => \(props\.site\.navigation\?\.length \? props\.site\.navigation/)
  assert.match(footer, /footerHref\(policy\.href\)/)
  assert.match(branch, /links\.generic\(url\)/)
  assert.doesNotMatch(branch, /migration-catalog-preview/)
  assert.match(grid, /links\.product\(product\.slug\)/)
  assert.match(catalog, /links\.generic\(item\.url\)/)
  assert.match(catalog, /:fallback-href="catalogData\.support\.href \|\| '\/lien-he\/'"/)
  assert.match(homeCatalog, /links\.generic\(slot\.category\?\.url \|\| slot\.href\)/)
  assert.match(homeProductGroups, /links\.generic\(group\.categoryUrl\)/)
  assert.match(homeProductGroups, /links\.product\(product\.slug\)/)
  assert.match(homeCampaigns, /links\.generic\(campaign\.href\)/)
  assert.match(homeHero, /links\.generic\(current\.href\)/)
  assert.match(homeEditorial, /href: solution\.href/)
  assert.match(directorStage, /links\.generic\(current\.href\)/)
  assert.match(homeDealer, /href="#yeu-cau-tu-van" @click="leadIntent = 'Hợp tác đại lý'"/)
  assert.match(homeAbout, /links\.generic\(about\.href\)/)
  assert.match(homeCatalog, /id="danh-muc-thiet-bi"/)
  assert.match(homeHero, /href="#danh-muc-thiet-bi"/)
  assert.match(homeContact, /id="yeu-cau-tu-van"/)
  assert.match(homeCampaigns, /href="#yeu-cau-tu-van"/)
  assert.match(homeDealer, /href="#yeu-cau-tu-van"/)
  assert.match(supportExtras, /links\.generic\(policy\.href\)/)

})
