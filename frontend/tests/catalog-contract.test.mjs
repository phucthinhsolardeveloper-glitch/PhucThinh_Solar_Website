import { test } from 'node:test'
import assert from 'node:assert/strict'
import { existsSync, readFileSync } from 'node:fs'
import { isCatalogSlugChain, isPublicCatalogPayload } from '../shared/contracts/public-catalog.ts'

const good = {
  schemaVersion: 1,
  locale: 'vi',
  seo: { title: 'Sản phẩm — Phúc Thịnh Solar', description: '', keywords: '' },
  canonical: { url: '/san-pham/' },
  category: [],
  categories: [{ id: 10, name: 'Điện mặt trời', slug: 'dien-mat-troi', url: '/danh-muc-san-pham/dien-mat-troi/' }],
  products: [{ id: 20, name: 'Sản phẩm thật từ CMS', slug: 'san-pham-that', url: '/san-pham/san-pham-that', media: ['/upload/product/photo.webp'], powerWatts: 650, quickSpecs: ['650W', 'IP65', '10-15 giờ'], pricing: { mode: 'price', currency: 'VND', regularPrice: 2648100, salePrice: null } }],
  pagination: { page: 1, perPage: 20, total: 1 },
  pricesVisible: true,
}
const catalogApi = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/api/pts_v1_catalog.php', import.meta.url), 'utf8')

test('accepts the exact Phase A catalog DTO', () => assert.equal(isPublicCatalogPayload(good), true))
test('accepts products without prices when public pricing is off', () => {
  const { powerWatts, quickSpecs, pricing, ...basicProduct } = good.products[0]
  assert.equal(isPublicCatalogPayload({ ...good, products: [basicProduct], pricesVisible: false }), true)
  assert.equal(isPublicCatalogPayload({ ...good, pricesVisible: 'yes' }), false)
})
test('rejects private fields, unsafe media and invalid pagination', () => {
  assert.equal(isPublicCatalogPayload({ ...good, products: [{ ...good.products[0], price: 100 }] }), false)
  assert.equal(isPublicCatalogPayload({ ...good, products: [{ ...good.products[0], media: ['/upload/product/../secret.php'] }] }), false)
  assert.equal(isPublicCatalogPayload({ ...good, pagination: { ...good.pagination, perPage: 49 } }), false)
  assert.equal(isPublicCatalogPayload({ ...good, pagination: { ...good.pagination, page: 10001 } }), false)
  assert.equal(isPublicCatalogPayload({ ...good, pagination: { ...good.pagination, page: 10000 } }), true)
})
test('accepts quote-only products without inventing a price', () => {
  const quote = { ...good.products[0], powerWatts: null, pricing: { mode: 'quote', currency: 'VND', regularPrice: null, salePrice: null } }
  assert.equal(isPublicCatalogPayload({ ...good, products: [quote] }), true)
  assert.equal(isPublicCatalogPayload({ ...good, products: [{ ...quote, pricing: { ...quote.pricing, regularPrice: 1 } }] }), false)
})
test('catalog card specs are safe, bounded and projected from PHP product fields', () => {
  assert.equal(isPublicCatalogPayload(good), true)
  assert.equal(isPublicCatalogPayload({ ...good, products: [{ ...good.products[0], quickSpecs: ['650W', '<script>'] }] }), false)
  assert.equal(isPublicCatalogPayload({ ...good, products: [{ ...good.products[0], quickSpecs: ['200W', 'IP65', '> 12 tiếng, chiếu sáng 3 ngày mưa'] }] }), true)
  assert.equal(isPublicCatalogPayload({ ...good, products: [{ ...good.products[0], quickSpecs: ['x'.repeat(101)] }] }), false)
  assert.match(readFileSync(new URL('../app/components/catalog/CatalogProductGrid.vue', import.meta.url), 'utf8'), /\{\{ quickSpecText\(product\) \}\}/)
  assert.match(catalogApi, /p\.cong_suat,p\.waterproof_rating,p\.lighting_time/)
  assert.match(readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/libraries/pts_public_catalog_api.php', import.meta.url), 'utf8'), /'quickSpecs' => ptsCatalogQuickSpecsDto\(\$row\)/)
})
test('accepts category chain only at the supported 0..4 slug boundaries', () => {
  assert.equal(isCatalogSlugChain(''), true)
  assert.equal(isCatalogSlugChain('dien-mat-troi'), true)
  assert.equal(isCatalogSlugChain('a/b/c/d'), true)
  assert.equal(isCatalogSlugChain('a/b/c/d/e'), false)
  assert.equal(isCatalogSlugChain('A/b'), false)
  assert.equal(isCatalogSlugChain('a//b'), false)
})
test('accepts a resolved category object with its canonical full path', () => {
  const category = { id: 11, name: 'Đèn', slug: 'den', url: '/danh-muc-san-pham/dien-mat-troi/den/' }
  assert.equal(isPublicCatalogPayload({ ...good, category, canonical: { url: category.url } }), true)
})

test('requires safe SEO metadata and canonical route from the PHP catalog DTO', () => {
  assert.equal(isPublicCatalogPayload({ ...good, seo: { ...good.seo, title: '<script>bad</script>' } }), false)
  assert.equal(isPublicCatalogPayload({ ...good, canonical: { url: 'https://evil.example/' } }), false)
  assert.equal(isPublicCatalogPayload({ ...good, seo: undefined }), false)
})

test('catalog SSR metadata comes from the PHP SEO DTO while preserving the robots gate', () => {
  const page = readFileSync(new URL('../app/components/catalog/CatalogPage.vue', import.meta.url), 'utf8')
  assert.match(page, /title: metadataTitle/)
  assert.match(page, /description: metadataDescription/)
  assert.ok(page.includes("name: 'keywords', content: metadataKeywords.value"))
  assert.match(page, /canonicalPath: metadataCanonicalPath/)
  assert.ok(page.includes('robots: computed(() => publicSeoRobots(catalogIndexable.value'))
  assert.match(catalogApi, /#_seo where id_parent = \? and com = \? and act = \? and type = \? limit 1/)
  assert.match(catalogApi, /'product', \$seoAction, 'san-pham'/)
  assert.match(catalogApi, /#_seopage where type = \? limit 1', array\('san-pham'\)/)
  assert.match(catalogApi, /select titlevi,descriptionvi,keywordsvi,photo from #_seopage/)
  assert.match(page, /catalogData\.value\?\.seo\.image \|\| \(route\.path === '\/thuong-hieu\/' \? undefined : catalogData\.value\?\.categoryHero\?\.image\)/)
})

test('category hero fallback matches PHP source for den-nlmt and respects a configured Admin hide toggle', () => {
  const catalogLibrary = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/libraries/pts_public_catalog_api.php', import.meta.url), 'utf8')
  const phpSource = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/sources/product.php', import.meta.url), 'utf8')
  assert.match(catalogApi, /\$heroConfigured = false;/)
  assert.match(catalogApi, /\$heroConfigured = true;/)
  assert.match(catalogApi, /!\$heroConfigured\)\s*\{\s*\$extras\['hero'\] = ptsCatalogCategoryHeroFallback\(\$currentPath, ROOT\);/)
  for (const path of ['danh-muc-san-pham/den-nlmt', 'danh-muc-san-pham/den-nlmt/den-ufo']) {
    assert.ok(catalogLibrary.includes(`'${path}' => array(`), `Missing shared fallback for ${path}`)
    assert.ok(phpSource.includes(`'${path}' => array(`), `Missing PHP template fallback for ${path}`)
  }
})

test('Nuxt bundles every curated catalog hero fallback referenced by the PHP API', () => {
  const catalogLibrary = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/libraries/pts_public_catalog_api.php', import.meta.url), 'utf8')
  const start = catalogLibrary.indexOf('$fallbacks = array(', catalogLibrary.indexOf('function ptsCatalogCategoryHeroFallback'))
  const end = catalogLibrary.indexOf('\n    );', start)
  assert.ok(start >= 0 && end > start)
  const names = [...new Set([...catalogLibrary.slice(start, end).matchAll(/array\('([^']+\.(?:png|jpe?g|webp|avif))'/gi)].map((match) => match[1]))]
  assert.ok(names.length > 0)
  for (const name of names) {
    assert.ok(existsSync(new URL(`../public/assets/images/site-visuals/${name}`, import.meta.url)), `Nuxt catalog hero asset missing: ${name}`)
  }
})

test('catalog DTO default product order uses the same highlighted category ranking as PHP catalog', () => {
  const phpSource = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/sources/product.php', import.meta.url), 'utf8')
  assert.match(phpSource, /publicProductDefaultOrder\('',\s*publicProductBossCategoryOrder\('',\s*\$type\)\)/)
  assert.match(catalogApi, /publicProductDefaultOrder\('p',\s*publicProductBossCategoryOrder\('p'\)\)/)
})

const extended = {
  ...good,
  categoryHero: null,
  categoryContent: { title: '', applications: [], faq: [] },
  support: null,
  filters: {
    brands: [{ id: 5, name: 'LONGI' }, { id: 6, name: 'ASTRONERGY' }],
    priceRanges: [{ key: '1', label: 'Dưới 1.000.000đ' }, { key: '2', label: '1.000.000đ - 3.000.000đ' }, { key: '3', label: 'Trên 3.000.000đ' }],
    subcategories: [{ id: 7, name: 'Kẹp tấm pin', slug: 'kep-tam-pin' }],
  },
  selected: { brandIds: [5], priceKeys: ['2'], subcategoryIds: [], sort: 'default' },
}

test('accepts the extended shape with real brand/price/subcategory filters and sort', () => {
  assert.equal(isPublicCatalogPayload(extended), true)
})
test('accepts the new allow-listed lighting need options while preserving the previous v1 shape', () => {
  const withNeeds = {
    ...extended,
    filters: {
      ...extended.filters,
      needs: [
        { key: 'du-an-cong-trinh', label: 'Dự án & công trình' },
        { key: 'chieu-sang-gia-dinh', label: 'Chiếu sáng gia đình' },
        { key: 'san-vuon-canh-quan', label: 'Sân vườn & cảnh quan' },
      ],
    },
    selected: { ...extended.selected, needKeys: ['du-an-cong-trinh'] },
  }
  assert.equal(isPublicCatalogPayload(withNeeds), true)
  assert.equal(isPublicCatalogPayload(extended), true)
  assert.equal(isPublicCatalogPayload({ ...withNeeds, selected: { ...withNeeds.selected, needKeys: ['unverified'] } }), false)
  assert.equal(isPublicCatalogPayload({ ...withNeeds, filters: { ...withNeeds.filters, needs: [{ key: 'unverified', label: 'Tự nghĩ ra' }] } }), false)
})
test('rejects a sort key or price key outside the real backend whitelist', () => {
  assert.equal(isPublicCatalogPayload({ ...extended, selected: { ...extended.selected, sort: 'relevance' } }), false)
  assert.equal(isPublicCatalogPayload({ ...extended, selected: { ...extended.selected, priceKeys: ['4'] } }), false)
  assert.equal(isPublicCatalogPayload({ ...extended, filters: { ...extended.filters, priceRanges: [{ key: '9', label: 'x' }] } }), false)
})
test('rejects an extended payload missing filters or selected (not a valid staggered shape)', () => {
  const { selected: _selected, ...withoutSelected } = extended
  assert.equal(isPublicCatalogPayload(withoutSelected), false)
})
test('rejects invented IDs that are not safe positive integers in selected filters', () => {
  assert.equal(isPublicCatalogPayload({ ...extended, selected: { ...extended.selected, brandIds: [-1] } }), false)
  assert.equal(isPublicCatalogPayload({ ...extended, selected: { ...extended.selected, brandIds: [1.5] } }), false)
})
