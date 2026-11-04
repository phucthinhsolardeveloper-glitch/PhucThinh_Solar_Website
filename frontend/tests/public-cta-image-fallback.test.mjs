import assert from 'node:assert/strict'
import { existsSync, readFileSync } from 'node:fs'
import { test } from 'node:test'

const component = readFileSync(new URL('../app/components/PublicCtaBanner.vue', import.meta.url), 'utf8')
const variants = ['corporate', 'editorial', 'catalog']

test('every shared CTA variant resolves to a generated background image', () => {
  for (const variant of variants) {
    const filename = `cta-${variant}-bg.webp`
    const url = `/assets/images/site-visuals/${filename}`
    const assetPath = new URL(`../public${url}`, import.meta.url)

    assert.match(component, new RegExp(`${variant}:\\s*['"]${url.replaceAll('/', '\\/')}['"]`))
    assert.equal(existsSync(assetPath), true, `${filename} must be bundled as a public asset`)

    const bytes = readFileSync(assetPath)
    assert.equal(bytes.toString('ascii', 0, 4), 'RIFF', `${filename} must be a valid WebP container`)
    assert.equal(bytes.toString('ascii', 8, 12), 'WEBP', `${filename} must be encoded as WebP`)
  }
})

test('CTA image is present without a CMS image and broken CMS images fall back once', () => {
  assert.match(component, /const fallbackImages\s*=/)
  assert.match(component, /fallbackImages\[props\.variant\]/)
  assert.match(component, /@error="handleImageError"/)
  assert.match(component, /function handleImageError/)
  assert.match(component, /imageSource\.value\s*=\s*props\.image\s*\|\|\s*fallbackImage\.value/)
  assert.match(component, /imageSource\.value\s*!==\s*fallbackImage\.value/)
  assert.match(component, /imageSource\.value\s*=\s*fallbackImage\.value/)
  assert.match(component, /imageFailed\.value\s*=\s*true/)
  assert.match(component, /:src="imageSource"/)
  assert.match(component, /class="cta-band__image"[^>]*alt=""[^>]*loading="lazy"/)
})
