import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

const source = readFileSync(resolve(new URL('../app/components/SiteFloatingContact.vue', import.meta.url).pathname), 'utf8')

test('floating contact actions stay mounted near forms and footer', () => {
  assert.match(source, /v-if="showBackToTop \|\| hasContactChannels"/)
  assert.match(source, /v-if="showBackToTop"/)
  assert.doesNotMatch(source, /footerInView|formInView|IntersectionObserver/)
})
