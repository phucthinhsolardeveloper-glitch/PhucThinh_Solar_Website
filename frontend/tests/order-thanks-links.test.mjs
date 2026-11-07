import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const component = readFileSync(new URL('../app/components/order/OrderThanksPage.vue', import.meta.url), 'utf8')
const links = readFileSync(new URL('../app/composables/usePublicLinks.ts', import.meta.url), 'utf8')

test('SSR forwards the session cookie when loading the token-bound order confirmation', () => {
  assert.match(component, /const requestFetch = useRequestFetch\(\)/)
  assert.match(component, /requestFetch\('\/_api\/order-thanks'/)
  assert.doesNotMatch(component, /\$fetch\('\/_api\/order-thanks'/)
  assert.match(component, /useResponseHeader\('Cache-Control'\)\.value = 'private, no-store'/)
})

test('order confirmation destinations are public routes', () => {
  assert.match(component, /const links = usePublicLinks\(\)/)
  assert.equal((component.match(/links\.generic\('\/'\)/g) ?? []).length, 0)
  assert.equal((component.match(/links\.generic\('\/san-pham\/'\)/g) ?? []).length, 2)
  assert.match(links, /const generic = \(href: string\): string => href/)
})
