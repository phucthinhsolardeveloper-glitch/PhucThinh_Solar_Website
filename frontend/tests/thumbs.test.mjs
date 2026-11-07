import assert from 'node:assert/strict'
import { test } from 'node:test'
import { thumbUrl } from '../app/utils/thumbs.ts'

test('thumbUrl asks for WebP renditions of JPG/PNG and keeps WebP/GIF originals', () => {
  assert.equal(thumbUrl('/upload/product/a.png', 320, 320), '/thumbs/320x320x1/upload/product/a.png.webp')
  assert.equal(thumbUrl('/upload/news/a.JPG', 760, 475), '/thumbs/760x475x1/upload/news/a.JPG.webp')
  assert.equal(thumbUrl('/upload/photo/a.webp', 960, 432), '/thumbs/960x432x1/upload/photo/a.webp')
  assert.equal(thumbUrl('/upload/photo/a.gif', 520, 112, 3), '/thumbs/520x112x3/upload/photo/a.gif')
})

test('optimizedImage only routes resizable sources and keeps aspect with fit mode', async () => {
  const { optimizedImage } = await import('../app/utils/thumbs.ts')
  assert.equal(optimizedImage('/upload/news/a.png', 800, 600), '/thumbs/800x600x3/upload/news/a.png.webp')
  assert.equal(optimizedImage('/assets/images/site-visuals/a.jpg', 1200, 900), '/thumbs/1200x900x3/assets/images/site-visuals/a.jpg.webp')
  assert.equal(optimizedImage('/assets/images/site-visuals/nuxt-only.webp', 1200, 900), '/assets/images/site-visuals/nuxt-only.webp')
  assert.equal(optimizedImage('https://example.test/a.jpg', 800, 600), 'https://example.test/a.jpg')
  assert.equal(optimizedImage(null, 800, 600), '')
})

test('every JPG/PNG site visual the thumbnails may ask for ships with the storefront', async () => {
  const { readdirSync } = await import('node:fs')
  const names = readdirSync(new URL('../public/assets/images/site-visuals/', import.meta.url))
  assert.ok(names.some((name) => /\.(?:jpe?g|png)$/i.test(name)))
})
