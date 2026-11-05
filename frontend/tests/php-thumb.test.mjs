import assert from 'node:assert/strict'
import { test } from 'node:test'
import { phpThumb } from '../app/utils/phpThumb.ts'

test('phpThumb asks PHP for WebP renditions of JPG/PNG and keeps WebP/GIF originals', () => {
  assert.equal(phpThumb('/upload/product/a.png', 320, 320), '/thumbs/320x320x1/upload/product/a.png.webp')
  assert.equal(phpThumb('/upload/news/a.JPG', 760, 475), '/thumbs/760x475x1/upload/news/a.JPG.webp')
  assert.equal(phpThumb('/upload/photo/a.webp', 960, 432), '/thumbs/960x432x1/upload/photo/a.webp')
  assert.equal(phpThumb('/upload/photo/a.gif', 520, 112, 3), '/thumbs/520x112x3/upload/photo/a.gif')
})

test('optimizedImage only routes PHP-resizable sources and keeps aspect with fit mode', async () => {
  const { optimizedImage } = await import('../app/utils/phpThumb.ts')
  assert.equal(optimizedImage('/upload/news/a.png', 800, 600), '/thumbs/800x600x3/upload/news/a.png.webp')
  assert.equal(optimizedImage('/assets/images/site-visuals/a.jpg', 1200, 900), '/thumbs/1200x900x3/assets/images/site-visuals/a.jpg.webp')
  assert.equal(optimizedImage('/assets/images/site-visuals/nuxt-only.webp', 1200, 900), '/assets/images/site-visuals/nuxt-only.webp')
  assert.equal(optimizedImage('https://example.test/a.jpg', 800, 600), 'https://example.test/a.jpg')
  assert.equal(optimizedImage(null, 800, 600), '')
})

test('every raster site visual served through PHP thumbs also exists in the PHP tree', async () => {
  const { readdirSync, existsSync } = await import('node:fs')
  const nuxt = new URL('../public/assets/images/site-visuals/', import.meta.url)
  const php = new URL('../../domains/phucthinhsolar.com/public_html/assets/images/site-visuals/', import.meta.url)
  const missing = readdirSync(nuxt).filter((name) => /\.(?:jpe?g|png)$/i.test(name) && !existsSync(new URL(name, php)))
  assert.deepEqual(missing, [], 'JPG/PNG site visuals must exist in both trees or be converted to WebP')
})
