import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const component = readFileSync(new URL('../app/components/order/OrderThanksPage.vue', import.meta.url), 'utf8')
const links = readFileSync(new URL('../app/composables/useMigrationLinks.ts', import.meta.url), 'utf8')

test('SSR forwards the PHP session cookie when loading the token-bound order confirmation', () => {
  assert.match(component, /const requestFetch = useRequestFetch\(\)/)
  assert.match(component, /requestFetch\('\/_migration\/order-thanks'/)
  assert.doesNotMatch(component, /\$fetch\('\/_migration\/order-thanks'/)
  assert.match(component, /useResponseHeader\('Cache-Control'\)\.value = 'private, no-store'/)
})

test('order confirmation destinations resolve to public routes or preview routes by current route', () => {
  assert.match(component, /const links = useMigrationLinks\(\)/)
  assert.equal((component.match(/links\.generic\('\/'\)/g) ?? []).length, 0)
  assert.equal((component.match(/links\.generic\('\/san-pham\/'\)/g) ?? []).length, 2)
  assert.doesNotMatch(component, /to="\/migration-(?:home|catalog)-preview"/)
  assert.match(links, /const active = computed\(\(\) => route\.path\.startsWith\('\/migration-'\)\)/)
  assert.match(links, /'\/': '\/migration-home-preview'/)
  assert.match(links, /'\/san-pham\/': '\/migration-catalog-preview'/)
})
