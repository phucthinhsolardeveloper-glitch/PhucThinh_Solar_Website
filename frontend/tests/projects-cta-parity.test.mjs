import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const root = new URL('../', import.meta.url)
const readFrontend = (path) => readFileSync(new URL(path, root), 'utf8')

test('project listing CTA stays wired to the PHP visibility and image contract', () => {
  const page = readFrontend('app/components/views/ProjectsView.vue')
  const cta = readFrontend('app/components/projects/ProjectsCta.vue')

  assert.match(page, /<ProjectsCta v-if="projects\.cta"/)
  assert.match(cta, /thumbUrl\(src, 1600, 500\)/)
})

test('project listing CTA uses the shared editorial banner and keeps PHP image/trust data', () => {
  const cta = readFrontend('app/components/projects/ProjectsCta.vue')
  const sharedCta = readFrontend('app/components/PublicCtaBanner.vue')

  // Owner decision 2026-11-04: the shared CTA band renders copy, trust and actions from props.
  assert.match(cta, /<PublicCtaBanner\s+variant="editorial"\s+:image="backgroundImage"/)
  assert.match(cta, /const backgroundImage = computed\(\(\) =>[\s\S]*thumbUrl\(src, 1600, 500\)/)
  assert.match(cta, /:title="cta\.title"[\s\S]*:description="cta\.description"[\s\S]*:trust="trustItems"/)
  assert.match(cta, /:hotline="cta\.hotline\?\.text"[\s\S]*:button-label="cta\.buttonLabel \|\| label"/)
  assert.match(sharedCta, /\.cta-band__image \{[^}]*object-fit: cover/)
  assert.match(sharedCta, /\.cta-band__trust li \{/)
})
