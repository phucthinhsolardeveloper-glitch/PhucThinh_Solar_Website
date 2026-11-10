import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

const frontendRoot = resolve(new URL('..', import.meta.url).pathname)
const read = (path) => readFileSync(resolve(frontendRoot, path), 'utf8')

test('catalog media reserves intrinsic space and chooses responsive product sizes', () => {
  const catalog = read('app/components/catalog/CatalogPage.vue')
  const masthead = read('app/components/catalog/CatalogMasthead.vue')
  const products = read('app/components/catalog/CatalogProductGrid.vue')
  const sharedCta = read('app/components/PublicCtaBanner.vue')
  assert.match(masthead, /hero\?\.image[\s\S]*width="1800"[\s\S]*height="600"[\s\S]*sizes="100vw"/)
  assert.match(masthead, /catalogHeroSrcSet\(hero\.image\)/)
  assert.match(masthead, /960w,[\s\S]*1800w/)
  assert.match(catalog, /categoryContent\.applications[\s\S]*width="760"[\s\S]*height="475"[\s\S]*sizes=/)
  assert.match(catalog, /<PublicCtaBanner\s+v-if="catalogData\?\.support"[^>]*variant="catalog"[^>]*:image="catalogData\.support\.image/)
  assert.match(sharedCta, /\.cta-band--catalog \{/)
  assert.match(sharedCta, /cta-band__image" :src="optimizedImage\(imageSource, 1600, 900\)" alt="" loading="lazy" decoding="async" @error="handleImageError"/)
  assert.match(sharedCta, /\.cta-band__image \{[^}]*object-fit: cover/)
  assert.match(products, /width="640"[\s\S]*height="640"[\s\S]*sizes=/)
  assert.match(products, /thumbUrl\(original, 640, 640\)/)
  assert.match(products, /productImageSrcSet\(product\)/)
  assert.match(products, /\[320, 480, 640\]/)
  assert.match(products, /productIndex < 4 \? 'eager' : 'lazy'/)
  assert.match(products, /productIndex < 4 \? 'high' : 'auto'/)
})

test('catalog keeps the valid JD-V500 product image and falls back only after an image error', () => {
  const products = read('app/components/catalog/CatalogProductGrid.vue')
  assert.doesNotMatch(products, /['"]\/upload\/product\/jd-v500-7208\.webp['"]\s*:/)
  assert.match(products, /:src="productImageSrc\(product\)"/)
  assert.match(products, /@error="markImageError\(product\)"/)
})

test('news cards use allowlisted PHP thumbnails and match the rendered 16:9 frame', () => {
  const news = read('app/components/news/NewsCard.vue')
  assert.match(news, /\^\\\/upload\\\/news\\\//)
  assert.match(news, /thumbUrl\(src, width, height\)/)
  assert.match(news, /cardImage\(src, 600, 338\).*600w,.*cardImage\(src, 900, 506\).*900w/s)
  assert.match(news, /variant === 'lead' \? 900 : 600/)
  assert.match(news, /variant === 'lead' \? 'eager' : 'lazy'/)
  assert.match(news, /variant === 'lead' \? 'high' : 'low'/)
  assert.doesNotMatch(news, /rounded-btn border border-slate-200\/90 bg-white/)
})

test('homepage media uses the same PHP thumbnail contracts and prioritizes the first hero', () => {
  const hero = read('app/components/home/HomeHero.vue')
  const homeCatalog = read('app/components/home/HomeCatalog.vue')
  const editorial = read('app/components/home/HomeSolutions.vue')
  const about = read('app/components/home/HomeAbout.vue')
  assert.match(hero, /thumbUrl\(src, width, height\)/)
  assert.match(hero, /heroImageCandidate\(src, 960\).*960w,.*heroImageCandidate\(src, 1600\).*1600w/s)
  assert.match(hero, /:fetchpriority="index === 0 \? 'high' : 'low'"/)
  assert.match(hero, /:loading="index === 0 \? 'eager' : 'lazy'"/)
  assert.match(homeCatalog, /:src="slot\.image"/)
  assert.match(homeCatalog, /width="960"[\s\S]*height="760"/)
  assert.match(homeCatalog, /links\.generic\(slot\.category\?\.url \|\| slot\.href\)/)
  assert.match(editorial, /thumbUrl\(src, width, height\)/)
  assert.match(editorial, /editorialImageCandidate\(src, 760, 475\).*760w,.*editorialImageCandidate\(src, 1200, 750\).*1200w/s)
  assert.match(about, /thumbUrl\(src, 48, 48\)/)
  assert.match(about, /:src="pointImage\(point\.image\)"/)
})

test('CMS logos and trust badge use the PHP fit thumbnail contract without cropping', () => {
  const header = read('app/components/SiteHeader.vue')
  const footer = read('app/components/SiteFooter.vue')
  assert.match(header, /thumbUrl\(src, 520, 112, 3\)/)
  assert.match(header, /width="520"[\s\S]*height="73"/)
  assert.match(footer, /thumbUrl\(src, 400, 152, 3\)/)
  assert.match(footer, /thumbUrl\(src, 400, 152, 3\)/)
  assert.match(footer, /width="400"[\s\S]*height="152"[\s\S]*object-contain/)
  assert.match(footer, /width="400"[\s\S]*height="152"[\s\S]*object-contain/)
})
