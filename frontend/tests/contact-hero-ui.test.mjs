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
  assert.match(hero, /v-if="emailHref"[\s\S]*?v-else>Email:/)
  assert.match(hero, /v-if="phoneHref"[\s\S]*?v-else>/)
})

test('contact hero aligns its CMS image and text band at mobile and desktop breakpoints', () => {
  assert.match(hero, /-mx-4 grid grid-cols-1 items-start gap-0 py-0 nav:mx-0/)
  assert.match(hero, /aspect-\[8\/5\] w-full object-cover nav:rounded-chip/)
  assert.match(hero, /p-5 text-white nav:rounded-chip nav:p-10/)
})

test('contact hero desktop heading avoids an orphan last word without changing the PHP column split', () => {
  assert.match(hero, /<h1 id="contact-preview-title" class="[^\"]*text-\[clamp\(32px,3vw,44px\)\][^\"]*">/)
  assert.match(hero, /nav:grid-cols-\[1\.3fr_0\.7fr\]/)
  assert.match(hero, /nav:p-10/)
})

test('contact hero uses the CMS CTA URL and matches PHP label and visibility', async () => {
  assert.match(hero, /v-if="contact\.visual\?\.href"/)
  assert.match(hero, /links\.generic\(contact\.visual\.href\)/)
  assert.match(hero, /Gửi yêu cầu tư vấn/)
  const php = await readFile(new URL('../../domains/phucthinhsolar.com/public_html/templates/contact/contact_tpl.php', import.meta.url), 'utf8')
  assert.match(php, /if\(\$contactVisual && !empty\(\$contactVisual\['link'\]\)\)/)
  assert.match(php, />Gửi yêu cầu tư vấn/)
})
