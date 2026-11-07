import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { readFile } from 'node:fs/promises'

const hero = readFileSync(new URL('../app/components/contact/ContactHero.vue', import.meta.url), 'utf8')

test('contact hero exposes the CMS hotline, email, and business hours', () => {
  assert.match(hero, /contact\.contact\.hotline/)
  assert.match(hero, /contact\.contact\.email/)
  assert.match(hero, /contact\.contact\.workingHours/)
  assert.match(hero, /publicEmailHref\(props\.contact\.contact\.email\)/)
  assert.match(hero, /publicPhoneHref\(props\.contact\.contact\.hotline\)/)
})

test('contact hero does not construct a mailto URL from an invalid CMS email', () => {
  assert.match(hero, /v-if="emailHref"[\s\S]*?v-else class="min-w-0 break-words">Email:/)
  assert.match(hero, /v-if="phoneHref"[\s\S]*?v-else>Hotline:/)
})

test('contact hero aligns its CMS image and text band at mobile and desktop breakpoints', () => {
  assert.match(hero, /lg:grid-cols-\[minmax\(0,1fr\)_minmax\(380px,\.86fr\)\]/)
  assert.match(hero, /aspect-\[8\/5\] w-full object-cover/)
})

test('contact page passes the CMS title to the single hero H1', async () => {
  const page = await readFile(new URL('../app/components/contact/ContactPageContent.vue', import.meta.url), 'utf8')
  assert.match(page, /<ContactHero :contact="contact" :page-title="contact\.title"/)
  assert.match(hero, /<h1 id="contact-hero-title"[^>]*>\s*\{\{ pageTitle \}\}/)
  assert.match(page, /v-if="error \|\| !contact"[\s\S]*?<h1/)
})

test('contact hero uses the CMS CTA URL and matches PHP label and visibility', async () => {
  assert.match(hero, /v-if="contact\.visual\?\.href"/)
  assert.match(hero, /links\.generic\(contact\.visual\.href\)/)
  assert.match(hero, /Gửi yêu cầu tư vấn/)
})
