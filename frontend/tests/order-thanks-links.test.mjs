import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const component = readFileSync(new URL('../app/components/order/OrderThanksPage.vue', import.meta.url), 'utf8')
const links = readFileSync(new URL('../app/composables/useMigrationLinks.ts', import.meta.url), 'utf8')

test('order confirmation destinations resolve to public routes or preview routes by current route', () => {
  assert.match(component, /const links = useMigrationLinks\(\)/)
  assert.equal((component.match(/links\.generic\('\/'\)/g) ?? []).length, 0)
  assert.equal((component.match(/links\.generic\('\/san-pham\/'\)/g) ?? []).length, 2)
  assert.doesNotMatch(component, /to="\/migration-(?:home|catalog)-preview"/)
  assert.match(links, /const active = computed\(\(\) => route\.path\.startsWith\('\/migration-'\)\)/)
  assert.match(links, /'\/': '\/migration-home-preview'/)
  assert.match(links, /'\/san-pham\/': '\/migration-catalog-preview'/)
})
