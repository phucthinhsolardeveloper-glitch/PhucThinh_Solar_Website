import assert from 'node:assert/strict'
import { readFileSync, readdirSync } from 'node:fs'
import path from 'node:path'
import { test } from 'node:test'

const root = path.resolve(import.meta.dirname, '../..')
const adaptersDir = path.join(root, 'frontend/server/routes/_api')
const laravelRoutes = readFileSync(path.join(root, 'backend/routes/api.php'), 'utf8')
const adapters = readdirSync(adaptersDir).filter((name) => name.endsWith('.get.ts'))

test('every Nuxt GET data adapter targets an existing Laravel v1 route', () => {
  let mapped = 0
  for (const name of adapters) {
    const source = readFileSync(path.join(adaptersDir, name), 'utf8')
    const endpoint = source.match(/endpoint:\s*['"]([^'"]+)['"]/)
    if (!endpoint) continue // Binary/media adapters have their own tests.
    mapped++
    assert.match(endpoint[1], /^\/api\/v1\/[a-z-]+$/, name)
    const route = endpoint[1].slice('/api/v1/'.length)
    assert.match(laravelRoutes, new RegExp(`Route::get\\('${route}'`), `${name}: no Laravel route for ${route}`)
    assert.match(source, /requireGet\(event\)/, `${name}: GET guard missing`)
    assert.match(source, /Cache-Control', 'private, no-store'/, `${name}: cache policy missing`)
  }
  assert.ok(mapped >= 16, `Unexpected loss of API-backed adapters: ${mapped}`)
})
