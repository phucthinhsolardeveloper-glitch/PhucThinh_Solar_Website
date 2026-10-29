import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const cards = readFileSync(new URL('../app/components/solutions/SolutionCards.vue', import.meta.url), 'utf8')

test('a single solution card gets a full-width desktop row while the mobile rail stays stacked', () => {
  assert.match(cards, /cards\.length === 1 \? 'nav:grid-cols-1' : 'nav:grid-cols-5'/)
  assert.match(cards, /cards\.length === 1 \? 'nav:w-full nav:flex-row nav:gap-6' : ''/)
  assert.match(cards, /cards\.length === 1 \? 'nav:w-1\/2 nav:max-w-\[560px\] nav:flex-none' : ''/)
  assert.match(cards, /class="flex min-w-0 flex-none basis-\[min\(78vw,286px\)\] flex-col[^"]*"/)
  assert.match(cards, /cards\.length === 1 \? 'nav:justify-center nav:pt-0' : ''/)
  assert.match(cards, /<div v-if="card\.image"/)
  assert.doesNotMatch(cards, /<span v-else aria-hidden="true">—<\/span>/)
})
