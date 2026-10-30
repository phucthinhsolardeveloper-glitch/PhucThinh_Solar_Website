import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'

const source = readFileSync(new URL('../app/components/product-preview/ProductReviews.vue', import.meta.url), 'utf8')

test('review star ratings expose one accessible label and hide decorative star glyphs', () => {
  assert.match(source, /<span role="img"[^>]+:aria-label="currentReviews\.average \+ ' trên 5 sao'"><span aria-hidden="true">\{\{ stars\(Math\.round\(currentReviews\.average\)\) \}\}<\/span><\/span>/)
  assert.match(source, /<div role="img"[^>]+:aria-label="review\.star \+ ' trên 5 sao'"><span aria-hidden="true">\{\{ stars\(review\.star\) \}\}<\/span><\/div>/)
})
