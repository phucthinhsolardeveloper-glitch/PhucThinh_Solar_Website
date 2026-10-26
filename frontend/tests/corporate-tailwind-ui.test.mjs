import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const root = new URL('../app/', import.meta.url)
const read = (path) => readFileSync(new URL(path, root), 'utf8')

test('contact route uses Tailwind composition and preserves safe PHP form ownership', () => {
  const page = read('pages/migration-contact-preview.vue')
  const form = read('components/contact/ContactFormHandoff.vue')
  const locations = read('components/contact/ContactLocations.vue')
  assert.doesNotMatch(page, /contact-preview\.css/)
  assert.match(page, /<ContactHero/)
  assert.match(page, /<ContactFormHandoff/)
  assert.match(form, /@submit\.prevent="continueOnPhp"/)
  assert.match(form, /window\.location\.assign\('\/lien-he\/#yeu-cau-tu-van'\)/)
  assert.match(locations, /selectedLocation/)
  assert.match(locations, /<iframe/)
})

test('dealer route keeps every page-specific section and hands mutations to PHP', () => {
  const page = read('pages/migration-dealer-preview.vue')
  const form = read('components/dealer/DealerRegistrationHandoff.vue')
  const sections = read('components/dealer/DealerSections.vue')
  assert.doesNotMatch(page, /dealer-preview\.css/)
  assert.match(page, /id="nang-luc-phan-phoi"/)
  assert.match(page, /<DealerRegistrationHandoff/)
  assert.match(sections, /id="ho-tro-dai-ly"/)
  assert.match(sections, /dealer\.testimonials/)
  assert.match(sections, /dealer\.stats/)
  assert.match(form, /window\.location\.assign\('\/dai-ly\/#dang-ky-dai-ly'\)/)
})
