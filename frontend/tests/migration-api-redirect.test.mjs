import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const source = readFileSync(new URL('../server/utils/migration-api.ts', import.meta.url), 'utf8')

test('server-side PHP GET bridge never follows upstream redirects', () => {
  assert.match(source, /redirect:\s*['"]manual['"]/, 'migration API fetch must use manual redirect handling')
})
