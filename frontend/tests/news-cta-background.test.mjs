import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('news CTA keeps CMS content and uses the shared editorial image band', () => {
  const nuxtTheme = read('../app/assets/css/main.css')
  const nuxtCta = read('../app/components/news/NewsCta.vue')
  const sharedCta = read('../app/components/PublicCtaBanner.vue')

  assert.match(nuxtTheme, /--pts-gold-gradient:\s*linear-gradient/)
  assert.match(nuxtCta, /variant="editorial"/)
  assert.match(nuxtCta, /:image="backgroundImage"/)
  assert.match(nuxtCta, /props\.cta\.image \|\| ''/)
  // Owner decision 2026-11-04: shared CTA band redesign.
  assert.match(nuxtCta, /cta\.title[\s\S]*cta\.description[\s\S]*cta\.hotline\?\.text[\s\S]*cta\.buttonLabel/)
  assert.match(sharedCta, /cta-band__image" :src="optimizedImage\(imageSource, 1600, 900\)" alt="" loading="lazy" decoding="async" @error="handleImageError"/)
  assert.match(sharedCta, /cta-band__shade" aria-hidden="true"/)
  assert.match(sharedCta, /background:\s*#061537/)
})

test('news CTA keeps its contact actions and follows the shared responsive editorial layout', () => {
  const nuxtCta = read('../app/components/news/NewsCta.vue')
  const sharedCta = read('../app/components/PublicCtaBanner.vue')

  assert.match(nuxtCta, /:hotline="cta\.hotline\?\.text"[\s\S]*:hotline-label="phoneLabel"[\s\S]*:button-label="cta\.buttonLabel/)
  assert.match(nuxtCta, /:subject="'Kiến thức: ' \+ cta\.title"/)
  assert.match(sharedCta, /openConsultation\(subject \|\| heading\)/)
  assert.match(sharedCta, /\.cta-band__primary \{[^}]*background: var\(--pts-gold-gradient\)/)
  assert.match(sharedCta, /@media \(max-width: 900px\) \{[\s\S]*\.cta-band \{ grid-template-columns: minmax\(0, 1fr\); \}/)
})
