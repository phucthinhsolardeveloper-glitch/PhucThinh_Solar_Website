import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const filters = readFileSync(new URL('../app/components/news/NewsFilters.vue', import.meta.url), 'utf8')

test('news filter rail stays within the mobile viewport before scrolling horizontally', () => {
  assert.match(filters, /<nav class="[^"]*w-full[^"]*max-w-full[^"]*overflow-x-auto/)
})
