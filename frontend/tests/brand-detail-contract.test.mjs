import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isBrandSlug, isPublicBrandDetailPayload } from '../shared/contracts/public-brand-detail.ts'

const brandApi = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/api/pts_v1_brand_detail.php', import.meta.url), 'utf8')
const catalogDto = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/libraries/pts_public_catalog_api.php', import.meta.url), 'utf8')

const good = {
  schemaVersion: 1,
  locale: 'vi',
  path: '/thuong-hieu/longi/',
  brand: { id: 3, name: 'LONGi', slug: 'longi', url: '/thuong-hieu/longi/' },
  products: [{ id: 20, name: 'Tấm pin LONGi 650WP', slug: 'tam-pin-longi-650wp', url: '/san-pham/tam-pin-longi-650wp', media: ['/upload/product/photo.webp'], powerWatts: 650, pricing: { mode: 'price', currency: 'VND', regularPrice: 2648100, salePrice: null } }],
  pagination: { page: 1, perPage: 20, total: 1 },
  selected: { sort: 'default' },
  parity: { prices: { included: true }, seoOverrides: { included: false, reason: 'legacy_seo_override_table_not_delegated' } },
}

test('accepts the real brand detail DTO', () => assert.equal(isPublicBrandDetailPayload(good), true))
test('validates exact slug and rejects a URL that does not match the slug', () => {
  assert.equal(isBrandSlug('longi'), true)
  assert.equal(isBrandSlug('LONGi'), false)
  assert.equal(isPublicBrandDetailPayload({ ...good, brand: { ...good.brand, url: '/thuong-hieu/other/' } }), false)
})
test('accepts only the catalog sort keys already supported by the brand PHP API', () => {
  assert.equal(isPublicBrandDetailPayload({ ...good, selected: { sort: 'abc' } }), true)
  assert.equal(isPublicBrandDetailPayload({ ...good, selected: { sort: 'price-desc' } }), true)
  assert.equal(isPublicBrandDetailPayload({ ...good, selected: { sort: 'random' } }), false)
})
test('validates brand navigation and price filters from the brand DTO while keeping legacy DTOs valid', () => {
  const withFilters = {
    ...good,
    filters: {
      brands: [good.brand],
      priceRanges: [{ key: '1', label: 'Dưới 5 triệu' }, { key: '2', label: '5–20 triệu' }],
    },
    selected: { sort: 'default', priceKeys: ['2'] },
  }
  assert.equal(isPublicBrandDetailPayload(good), true)
  assert.equal(isPublicBrandDetailPayload(withFilters), true)
  assert.equal(isPublicBrandDetailPayload({ ...withFilters, selected: { ...withFilters.selected, priceKeys: ['4'] } }), false)
  assert.equal(isPublicBrandDetailPayload({ ...withFilters, filters: { ...withFilters.filters, brands: [{ ...good.brand, url: '/outside/' }] } }), false)
})
test('rejects a brand canonical URL missing the trailing slash (category-style path, unlike PDP)', () => {
  assert.equal(isPublicBrandDetailPayload({ ...good, brand: { ...good.brand, url: '/thuong-hieu/longi' }, path: '/thuong-hieu/longi' }), false)
})
test('keeps quote-only products without inventing a price', () => {
  const quote = { ...good.products[0], powerWatts: null, pricing: { mode: 'quote', currency: 'VND', regularPrice: null, salePrice: null } }
  assert.equal(isPublicBrandDetailPayload({ ...good, products: [quote] }), true)
})
test('rejects unsafe product media and invented pagination bounds', () => {
  assert.equal(isPublicBrandDetailPayload({ ...good, products: [{ ...good.products[0], media: ['/upload/product/../secret.php'] }] }), false)
  assert.equal(isPublicBrandDetailPayload({ ...good, pagination: { ...good.pagination, perPage: 49 } }), false)
})
test('rejects a price parity shape outside prices.included true/public_pricing_disabled', () => {
  assert.equal(isPublicBrandDetailPayload({ ...good, parity: { ...good.parity, prices: { included: false, reason: 'something_else' } } }), false)
  assert.equal(isPublicBrandDetailPayload({ ...good, parity: { ...good.parity, prices: { included: false, reason: 'public_pricing_disabled' } } }), true)
})

test('brand GET includes only published, existing Admin gallery media using parameterized product ids', () => {
  assert.match(brandApi, /select id_parent,photo,status from #_gallery where id_parent in \(\{\$idPlaceholders\}\) and com = \? and kind = \? and val = \? and find_in_set\('hienthi',status\)/)
  assert.match(brandApi, /array_merge\(\$productIds, array\('product', 'man', 'san-pham'\)\)/)
  assert.match(brandApi, /ptsCatalogPublicMedia\(\$filename\) === '' \|\| !is_file\(ROOT\.UPLOAD_PRODUCT_L\.\$filename\)/)
  assert.match(catalogDto, /_pts_public_gallery/)
})
