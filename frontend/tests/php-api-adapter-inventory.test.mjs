import assert from 'node:assert/strict'
import { readFileSync, readdirSync, existsSync } from 'node:fs'
import path from 'node:path'
import { test } from 'node:test'

const root = path.resolve(import.meta.dirname, '../..')
const adaptersDir = path.join(root, 'frontend/server/routes/_migration')
const phpRoot = path.join(root, 'domains/phucthinhsolar.com/public_html')
const adapters = readdirSync(adaptersDir).filter((name) => name.endsWith('.get.ts'))

test('every Nuxt GET data adapter targets an existing PHP API', () => {
  let mapped = 0
  for (const name of adapters) {
    const source = readFileSync(path.join(adaptersDir, name), 'utf8')
    const endpoint = source.match(/endpoint:\s*['"]([^'"]+)['"]/)
    if (!endpoint) continue // Separate binary/media adapters have their own tests.
    mapped++
    assert.match(endpoint[1], /^\/api\/pts_v1_[a-z_]+\.php$/, name)
    assert.ok(existsSync(path.join(phpRoot, endpoint[1].slice(1))), `${name}: missing PHP endpoint ${endpoint[1]}`)
    assert.match(source, /requireMigrationGet\(event\)/, `${name}: GET guard missing`)
    const php = readFileSync(path.join(phpRoot, endpoint[1].slice(1)), 'utf8')
    assert.match(php, /Cache-Control:\s*private, no-store/, `${name}: PHP cache policy changed`)
    assert.match(source, /Cache-Control', 'private, no-store'/, `${name}: cache must match PHP`)
  }
  assert.ok(mapped >= 16, `Unexpected loss of PHP-backed adapters: ${mapped}`)
})
