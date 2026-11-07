import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const config = readFileSync(new URL('../nuxt.config.ts', import.meta.url), 'utf8')
const block = config.match(/\.\.\.Object\.fromEntries\(\[([\s\S]*?)\]\.map/)?.[1] || ''
const cached = [...block.matchAll(/'([a-z-]+)'/g)].map((match) => match[1])

test('only public, cookie-free DTO bridges with bounded keys are cached', () => {
  assert.ok(cached.includes('site') && cached.includes('home'))
  for (const name of ['cart', 'checkout', 'checkout-wards', 'checkout-notice', 'lead-form', 'order-thanks', 'shipping-quote', 'review', 'search', 'catalog', 'news', 'public-route-resolve', 'product-extras']) {
    assert.ok(!cached.includes(name), `${name} must never be cached`)
  }
  for (const name of cached) {
    const route = readFileSync(new URL(`../server/routes/_api/${name}.get.ts`, import.meta.url), 'utf8')
    assert.match(route, /fetchMigrationPayload/, `${name} must use the cookie-free bridge`)
    assert.doesNotMatch(route, /fetchSessionResponse|getHeader\(event, 'cookie'\)/, `${name} must not forward the session`)
  }
  assert.match(config, /cache: \{ maxAge: 60, swr: true \}/)
})
