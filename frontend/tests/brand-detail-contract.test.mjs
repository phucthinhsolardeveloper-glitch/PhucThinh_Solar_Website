import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isBrandSlug, isPublicBrandDetailPayload } from '../shared/contracts/public-brand-detail.ts'

const brandApi = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/api/pts_v1_brand_detail.php', import.meta.url), 'utf8')
const catalogDto = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/libraries/pts_public_catalog_api.php', import.meta.url), 'utf8')
const brandPage = readFileSync(new URL('../app/components/catalog/BrandDetailPage.vue', import.meta.url), 'utf8')

const good = {
  schemaVersion: 1,
  locale: 'vi',
  path: '/thuong-hieu/longi/',
  brand: { id: 3, name: 'LONGi', slug: 'longi', url: '/thuong-hieu/longi/' },
  seo: { title: 'LONGi chính hãng | Phúc Thịnh Solar', description: 'Tấm pin LONGi & giải pháp thật.', keywords: 'LONGi, năng lượng mặt trời', image: '/upload/product/brand.webp' },
  products: [{ id: 20, name: 'Tấm pin LONGi 650WP', slug: 'tam-pin-longi-650wp', url: '/san-pham/tam-pin-longi-650wp', media: ['/upload/product/photo.webp'], powerWatts: 650, pricing: { mode: 'price', currency: 'VND', regularPrice: 2648100, salePrice: null } }],
  pagination: { page: 1, perPage: 20, total: 1 },
  selected: { sort: 'default' },
  pricesVisible: true,
}

test('accepts the real brand detail DTO', () => assert.equal(isPublicBrandDetailPayload(good), true))
test('validates exact slug and rejects a URL that does not match the slug', () => {
  assert.equal(isBrandSlug('longi'), true)
  assert.equal(isBrandSlug('LONGi'), false)
  assert.equal(isPublicBrandDetailPayload({ ...good, brand: { ...good.brand, url: '/thuong-hieu/other/' } }), false)
})
test('validates brand SEO fields and restricts Admin brand photos to safe product media', () => {
  assert.equal(isPublicBrandDetailPayload(good), true)
  assert.equal(isPublicBrandDetailPayload({ ...good, seo: { ...good.seo, image: '/upload/news/brand.webp' } }), false)
  assert.equal(isPublicBrandDetailPayload({ ...good, seo: { ...good.seo, image: '/upload/product/../private.php' } }), false)
  assert.equal(isPublicBrandDetailPayload({ ...good, seo: { ...good.seo, description: '<script>bad</script>' } }), false)
  const withoutSeo = { ...good }
  delete withoutSeo.seo
  assert.equal(isPublicBrandDetailPayload(withoutSeo), true)
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
test('requires a boolean pricesVisible flag', () => {
  assert.equal(isPublicBrandDetailPayload({ ...good, pricesVisible: 'no' }), false)
  assert.equal(isPublicBrandDetailPayload({ ...good, pricesVisible: false }), true)
})

test('brand GET includes only published, existing Admin gallery media using parameterized product ids', () => {
  assert.match(brandApi, /select id_parent,photo,status from #_gallery where id_parent in \(\{\$idPlaceholders\}\) and com = \? and kind = \? and val = \? and find_in_set\('hienthi',status\)/)
  assert.match(brandApi, /array_merge\(\$productIds, array\('product', 'man', 'san-pham'\)\)/)
  assert.match(brandApi, /ptsCatalogPublicMedia\(\$filename\) === '' \|\| !is_file\(ROOT\.UPLOAD_PRODUCT_L\.\$filename\)/)
  assert.match(catalogDto, /_pts_public_gallery/)
})

test('brand GET maps Admin SEO and brand photo through parameterized queries; page uses metadata with PHP-compatible fallbacks', () => {
  assert.match(brandApi, /select id,namevi,slugvi,status,photo from #_product_brand where slugvi = \? and type = \? and find_in_set\('hienthi',status\)/)
  assert.match(brandApi, /select titlevi,descriptionvi,keywordsvi from #_seo where id_parent = \? and com = \? and act = \? and type = \? limit 1/)
  assert.match(brandApi, /array\(\(int\)\$row\['id'\], 'product', 'man_brand', 'san-pham'\)/)
  assert.match(brandApi, /ptsPublicSeoImage\(\$row\['photo'\] \?\? '', ROOT, UPLOAD_PRODUCT_L\)/)
  assert.match(brandPage, /data\.value\?\.seo\?\.title \|\| \(data\.value \? \`\$\{data\.value\.brand\.name\} \| Phúc Thịnh Solar\`/)
  assert.match(brandPage, /brandSocialDescription = computed\(\(\) => brandDescription\.value \|\| site\.value\.brand\.name/)
  assert.match(brandPage, /description: brandSocialDescription/)
  assert.match(brandPage, /name: 'keywords', content: brandKeywords/)
  assert.match(brandPage, /data\.value\?\.seo\?\.image \|\| site\.value\.brand\.logo/)
  assert.match(brandPage, /publicSeoRobots\(brandIndexable\.value/)
})
