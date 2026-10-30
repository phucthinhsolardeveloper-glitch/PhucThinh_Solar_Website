import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const css = readFileSync(new URL('../app/assets/css/main.css', import.meta.url), 'utf8')

test('wide public containers follow the approved 1600px to 2200px formula', () => {
  assert.match(css, /@media\s*\(min-width:\s*1600px\)[\s\S]*--container-page:\s*min\(calc\(100vw\s*-\s*clamp\(96px,\s*8vw,\s*220px\)\),\s*2200px\)/)
  assert.match(css, /max-width:\s*var\(--container-page\)/)
})
