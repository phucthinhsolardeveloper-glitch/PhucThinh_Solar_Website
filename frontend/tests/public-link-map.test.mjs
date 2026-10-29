import assert from 'node:assert/strict'
import { existsSync, readFileSync } from 'node:fs'
import { test } from 'node:test'
import { useMigrationLinks } from '../app/composables/useMigrationLinks.ts'

const app = new URL('../app/', import.meta.url)
const repo = new URL('../../', import.meta.url)
const readApp = (path) => readFileSync(new URL(path, app), 'utf8')

function withRoute(path, callback) {
  const originalRoute = globalThis.useRoute
  const originalComputed = globalThis.computed
  globalThis.useRoute = () => ({ path })
  globalThis.computed = (getter) => Object.defineProperty({}, 'value', { get: getter })
  try {
    return callback(useMigrationLinks())
  } finally {
    if (originalRoute === undefined) delete globalThis.useRoute
    else globalThis.useRoute = originalRoute
    if (originalComputed === undefined) delete globalThis.computed
    else globalThis.computed = originalComputed
  }
}

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

test('canonical hrefs stay public outside preview and map to existing Nuxt preview pages inside preview', () => {
  withRoute('/san-pham/den-that', (links) => {
    assert.equal(links.active.value, false)
    assert.equal(links.generic('/'), '/')
    assert.equal(links.generic('/gio-hang/'), '/gio-hang/')
    assert.equal(links.generic('/lien-he/#yeu-cau-tu-van'), '/lien-he/#yeu-cau-tu-van')
    assert.equal(links.generic('/danh-muc-san-pham/den-nlmt/den-duong/'), '/danh-muc-san-pham/den-nlmt/den-duong/')
    assert.equal(links.product('den-that'), '/san-pham/den-that')
    assert.equal(links.solution('dien-mat-troi'), '/giai-phap/dien-mat-troi/')
    assert.equal(links.brand('longi'), '/thuong-hieu/longi/')
    assert.equal(links.generic('/ho-tro/chinh-sach-bao-hanh/'), '/ho-tro/chinh-sach-bao-hanh/')
  })

  withRoute('/migration-product-preview', (links) => {
    assert.equal(links.active.value, true)
    const staticLinks = [
      ['/', '/migration-home-preview'],
      ['/gioi-thieu/', '/migration-about-preview'],
      ['/giai-phap/', '/migration-solutions-preview'],
      ['/du-an-da-thuc-hien/', '/migration-projects-preview'],
      ['/tin-tuc/', '/migration-news-preview'],
      ['/dai-ly/', '/migration-dealer-preview'],
      ['/lien-he/', '/migration-contact-preview'],
      ['/san-pham/', '/migration-catalog-preview'],
      ['/gio-hang/', '/migration-cart-preview'],
      ['/tim-kiem/', '/migration-search-preview'],
      ['/thu-vien-anh/', '/migration-gallery-preview'],
      ['/video/', '/migration-video-preview'],
    ]
    for (const [href, target] of staticLinks) assert.equal(links.generic(href), target)
    assert.equal(links.generic('/lien-he/#yeu-cau-tu-van'), '/migration-contact-preview#yeu-cau-tu-van')
    assert.equal(links.generic('/danh-muc-san-pham/den-nlmt/den-duong/'), '/migration-catalog-preview?category=den-nlmt%2Fden-duong')
    assert.equal(links.product('den-that'), '/migration-product-preview?slug=den-that')
    assert.equal(links.solution('dien-mat-troi'), '/migration-solution-detail-preview?slug=dien-mat-troi')
    assert.equal(links.brand('longi'), '/migration-brand-detail-preview?slug=longi')
    assert.equal(links.generic('/ho-tro/chinh-sach-bao-hanh/'), '/migration-policy-preview?slug=chinh-sach-bao-hanh')
    assert.equal(links.news('bai-viet-that'), '/migration-news-detail-preview?slug=bai-viet-that')
    assert.equal(links.project('du-an-that'), '/migration-project-detail-preview?slug=du-an-that')
    assert.equal(links.gallery('album-that'), '/migration-gallery-detail-preview?slug=album-that')
  })
})

test('PDP cart and document links use public assets on public routes and preview proxy only in preview', () => {
  const purchase = readApp('components/product-preview/ProductPurchasePanel.vue')
  const documents = readApp('components/product-preview/ProductPreviewDocuments.vue')
  const page = readApp('components/product-preview/ProductPreviewPage.vue')

  assert.match(purchase, /useMigrationLinks\(\)/)
  assert.match(purchase, /navigateTo\(links\.generic\('\/gio-hang\/'\)\)/)
  assert.doesNotMatch(purchase, /navigateTo\('\/migration-cart-preview'\)/)
  assert.match(documents, /links\.active\.value/)
  assert.match(documents, /!links\.active\.value\) return document\.url/)
  assert.match(documents, /_migration\/product-document\?path=/)
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
  const homeEditorial = readApp('components/home/HomeEditorial.vue')
  const homeContact = readApp('components/home/HomeContact.vue')
  const supportExtras = readApp('components/product-preview/ProductSupportExtras.vue')
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
  assert.match(catalog, /links\.generic\(catalogData\.support\.href \|\| '\/lien-he\/'\)/)
  assert.match(homeCatalog, /links\.generic\(slot\.category\?\.url \|\| slot\.href\)/)
  assert.match(homeProductGroups, /links\.generic\(group\.categoryUrl\)/)
  assert.match(homeProductGroups, /links\.product\(product\.slug\)/)
  assert.match(homeCampaigns, /links\.generic\(campaign\.href\)/)
  assert.match(homeHero, /links\.generic\(current\.href\)/)
  assert.match(homeEditorial, /links\.generic\(solution\.href\)/)
  assert.match(homeEditorial, /href="#yeu-cau-tu-van" @click="requestConsultation\('Hợp tác đại lý'\)"/)
  assert.match(homeEditorial, /links\.generic\(data\.about\.href\)/)
  assert.match(homeCatalog, /id="danh-muc-thiet-bi"/)
  assert.match(homeHero, /href="#danh-muc-thiet-bi"/)
  assert.match(homeContact, /id="yeu-cau-tu-van"/)
  assert.match(homeCampaigns, /href="#yeu-cau-tu-van"/)
  assert.match(homeEditorial, /href="#yeu-cau-tu-van"/)
  assert.match(supportExtras, /links\.generic\(policy\.href\)/)

  const phpProjection = readFileSync(new URL('domains/phucthinhsolar.com/public_html/libraries/pts_public_navigation_api.php', repo), 'utf8')
  assert.match(phpProjection, /'label'=>'Giới thiệu','href'=>'\/gioi-thieu\//)
  assert.match(phpProjection, /'label'=>'Giải pháp','href'=>'\/giai-phap\//)
  assert.match(phpProjection, /'label'=>'Dự án','href'=>'\/du-an-da-thuc-hien\//)
  assert.match(phpProjection, /'label'=>'Kiến thức','href'=>'\/tin-tuc\//)
  assert.match(phpProjection, /'label'=>'Hợp tác','href'=>'\/dai-ly\//)
})
