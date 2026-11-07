import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const frontendRoot = new URL('../', import.meta.url)
const readFrontend = (path) => readFileSync(new URL(path, frontendRoot), 'utf8')

test('solution detail CTA keeps the PHP campaign fallback and 1600x500 media contract', () => {
  const component = readFrontend('app/components/solutions/SolutionsCta.vue')

  assert.match(component, /thumbUrl\(src, 1600, 500\)/)
  assert.match(component, /catalog-street-lighting\\\.jpg/)
  assert.match(component, /return \/\^\\\/assets\\\/images\\\/site-visuals\\\/catalog-street-lighting/)
  assert.match(readFrontend('app/components/PublicCtaBanner.vue'), /corporate: '\/assets\/images\/site-visuals\/cta-corporate-bg\.webp'/)
})

test('solution CTA uses the shared corporate image band and its responsive layout', () => {
  const component = readFrontend('app/components/solutions/SolutionsCta.vue')
  const sharedCta = readFrontend('app/components/PublicCtaBanner.vue')
  // Owner decision 2026-11-04: shared CTA band redesign.
  assert.match(component, /variant="corporate"/)
  assert.match(component, /:image="backgroundImage"/)
  assert.match(component, /id="ptsContactModal"/)
  assert.match(sharedCta, /cta-band__image" :src="imageSource" alt="" loading="lazy" decoding="async" @error="handleImageError"/)
  assert.match(sharedCta, /cta-band__shade" aria-hidden="true"/)
})

test('solution CTA retains CMS copy, hotline and consultation action', () => {
  const component = readFrontend('app/components/solutions/SolutionsCta.vue')
  const sharedCta = readFrontend('app/components/PublicCtaBanner.vue')
  assert.match(component, /:title="cta\.title"[\s\S]*:description="cta\.description"[\s\S]*:button-label="cta\.buttonLabel"[\s\S]*:hotline="cta\.hotline\?\.text"/)
  assert.match(component, /:fallback-href="cta\.links\.consult\.href === '#ptsContactModal'/)
  assert.match(sharedCta, /@click\.prevent="openConsultation\(subject \|\| heading\)"/)
  assert.match(sharedCta, /const heading = computed\(\(\) => sentenceCase\(props\.title\)\)/)
})
