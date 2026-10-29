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
  assert.match(masthead, /hero\?\.image[\s\S]*width="1800"[\s\S]*height="600"[\s\S]*sizes="100vw"/)
  assert.match(catalog, /categoryContent\.applications[\s\S]*width="760"[\s\S]*height="475"[\s\S]*sizes=/)
  assert.match(catalog, /support\.image[\s\S]*width="1600"[\s\S]*height="500"[\s\S]*sizes="100vw"/)
  assert.match(products, /width="640"[\s\S]*height="640"[\s\S]*sizes=/)
})

test('news cards use allowlisted PHP thumbnails and match the rendered 16:9 frame', () => {
  const news = read('app/components/news/NewsCard.vue')
  assert.match(news, /\^\\\/upload\\\/news\\\//)
  assert.match(news, /thumbs\/640x360x1\$\{src\}/)
  assert.match(news, /width="640"[\s\S]*height="360"[\s\S]*sizes=/)
})

test('CMS logos and trust badge declare rendered dimensions', () => {
  const header = read('app/components/SiteHeader.vue')
  const footer = read('app/components/SiteFooter.vue')
  assert.match(header, /thumbs\/248x35x1\$\{src\}/)
  assert.match(header, /width="248"[\s\S]*height="35"/)
  assert.match(footer, /thumbs\/280x229x1\$\{src\}/)
  assert.match(footer, /thumbs\/200x76x1\$\{src\}/)
  assert.match(footer, /width="280"[\s\S]*height="229"/)
  assert.match(footer, /width="200"[\s\S]*height="76"/)
})
