import assert from 'node:assert/strict'
import { existsSync, readFileSync } from 'node:fs'
import { test } from 'node:test'

const root = new URL('../', import.meta.url)
const read = (path) => readFileSync(new URL(path, root))
const text = (path) => read(path).toString('utf8')

const mirroredAssets = [
  'assets/images/site-visuals/catalog-street-lighting.jpg',
  'assets/images/site-visuals/contact-consultation.jpg',
  'assets/images/site-visuals/home-solution-construction-v1.png',
  'assets/images/site-visuals/solution-solar-pump-v2.jpg',
  'assets/images/site-visuals/pdp-usecase-v2/lighting-ufo-warehouse.webp',
  'assets/images/site-visuals/pdp-usecase-v2/lighting-ufo-worksite.webp',
  'assets/images/site-visuals/pdp-usecase-v2/lighting-ufo-farm.webp',
]

const optimizedMedia = [
  {
    component: 'app/components/home/HomeProductGroups.vue',
    source: '/upload/product/x3-pro-30-2-9905.png',
    asset: 'assets/images/site-visuals/media-fallbacks/product-x3-pro-30.webp',
  },
  {
    component: 'app/components/home/HomeProductGroups.vue',
    source: '/upload/product/x3-pro-20-2-2061.png',
    asset: 'assets/images/site-visuals/media-fallbacks/product-x3-pro-20.webp',
  },
  {
    component: 'app/components/dealer/DealerSections.vue',
    source: '/upload/news/svgviewer-png-output-3-5470.png',
    asset: 'assets/images/site-visuals/media-fallbacks/dealer-support-media.webp',
  },
  {
    component: 'app/components/dealer/DealerSections.vue',
    source: '/upload/news/svgviewer-png-output-2-4536.png',
    asset: 'assets/images/site-visuals/media-fallbacks/dealer-support-training.webp',
  },
  {
    component: 'app/components/dealer/DealerSections.vue',
    source: '/upload/news/svgviewer-png-output-1-2932.png',
    asset: 'assets/images/site-visuals/media-fallbacks/dealer-support-technical.webp',
  },
  {
    component: 'app/components/catalog/CatalogProductGrid.vue',
    source: '/upload/product/t-bat-tsys-d53-2-4295.png',
    asset: 'assets/images/site-visuals/media-fallbacks/brand-tbat-tsys-d53.webp',
  },
]

test('PHP-owned static images exist in Nuxt public at the API paths', () => {
  for (const asset of mirroredAssets) {
    assert.equal(existsSync(new URL(`public/${asset}`, root)), true, `${asset} must resolve in Nuxt public`)
    assert.ok(read(`public/${asset}`).equals(read(`../domains/phucthinhsolar.com/public_html/${asset}`)), `${asset} must match the existing PHP source asset`)
  }
})

test('all PHP PDP use-case WebP assets are mirrored into Nuxt public', () => {
  const profiles = text('../domains/phucthinhsolar.com/public_html/libraries/product_pdp_profiles.php')
  const filenames = [...new Set([...profiles.matchAll(/\$asset\.'([A-Za-z0-9._-]+\.webp)'/g)].map(([, filename]) => filename))]

  assert.ok(filenames.length > 0, 'PHP product profiles must define use-case images')
  for (const filename of filenames) {
    const asset = `assets/images/site-visuals/pdp-usecase-v2/${filename}`
    assert.equal(existsSync(new URL(`public/${asset}`, root)), true, `${asset} must resolve in Nuxt public`)
  }
})

test('oversized CMS images use optimized WebP derivatives', () => {
  for (const item of optimizedMedia) {
    const component = text(item.component)
    assert.ok(component.includes(item.source), `${item.source} must map from its API-owned path`)
    assert.ok(component.includes(`/${item.asset}`), `${item.asset} must be used by the matching page component`)

    const bytes = read(`public/${item.asset}`)
    assert.equal(bytes.toString('ascii', 0, 4), 'RIFF')
    assert.equal(bytes.toString('ascii', 8, 12), 'WEBP')
  }

})
