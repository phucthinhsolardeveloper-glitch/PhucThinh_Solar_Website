import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

const source = readFileSync(resolve(new URL('../app/components/SiteFloatingContact.vue', import.meta.url).pathname), 'utf8')

test('floating contact controls leave the footer unobstructed and disconnect their observer', () => {
  assert.match(source, /const footerInView = ref\(false\)/)
  assert.match(source, /document\.querySelector\('footer'\)/)
  assert.match(source, /new IntersectionObserver\(\(\[entry\]\) => \{\s*footerInView\.value = entry\?\.isIntersecting \?\? false/)
  assert.match(source, /\(showBackToTop \|\| hasContactChannels\) && !footerInView/)
  assert.match(source, /footerObserver\?\.disconnect\(\)/)
})
