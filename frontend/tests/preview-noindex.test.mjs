import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readdirSync, readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const testsDir = dirname(fileURLToPath(import.meta.url))
const pagesDir = join(testsDir, '../app/pages')
const config = readFileSync(join(testsDir, '../nuxt.config.ts'), 'utf8')

test('every migration preview page has an X-Robots-Tag noindex route rule', () => {
  const previewPages = readdirSync(pagesDir)
    .filter((name) => name.startsWith('migration-') && name.endsWith('.vue'))
    .map((name) => `/${name.slice(0, -4)}`)
    .sort()

  assert.ok(previewPages.length > 0)
  for (const path of previewPages) {
    const escaped = path.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
    const rule = new RegExp(`['"]${escaped}['"]\\s*:\\s*\\{[\\s\\S]*?X-Robots-Tag['"]\\s*:\\s*['"]noindex, nofollow, noarchive['"]`)
    assert.match(config, rule, `${path} must be covered by noindex routeRules`)
  }
})
