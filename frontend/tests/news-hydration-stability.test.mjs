import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('featured side excerpt size never competes with the phone compact row (no jump after hydration)', () => {
  const featured = read('../app/components/news/NewsFeatured.vue')
  const card = read('../app/components/news/NewsCard.vue')
  // NewsCard owns the excerpt at <=820px; the featured override must be limited to wider screens,
  // otherwise SSR inline CSS and client chunks resolve the equal-specificity rules differently.
  assert.match(card, /@media \(max-width: 820px\)[\s\S]*\.news-row \.news-excerpt \{[^}]*font-size: 13\.5px/)
  assert.match(featured, /@media \(min-width: 821px\) \{\s*\.featured-side :deep\(\.line-clamp-3\)/)
  assert.doesNotMatch(featured.replace(/@media \(min-width: 821px\) \{[^}]*\}/, ''), /\.featured-side :deep\(\.line-clamp-3\)\s*\{/)
})

test('knowledge pages reuse the shell site payload instead of fetching /_api/site again', () => {
  for (const file of ['../app/components/views/NewsView.vue', '../app/components/news/NewsCategoryPage.vue']) {
    assert.doesNotMatch(read(file), /useFetch<unknown>\('\/_api\/site'/, file)
  }
  assert.match(read('../app/components/news/NewsCategoryPage.vue'), /await usePublicSite\(\)/)
})

test('knowledge page content avoids post-hydration text-node rewrites', () => {
  const plugin = read('../app/plugins/vi-line-breaks.client.ts')
  assert.match(plugin, /\[data-no-vi-text-reflow\]/)
  for (const file of ['../app/components/views/NewsView.vue', '../app/components/news/NewsCategoryPage.vue']) {
    assert.match(read(file), /<main data-no-vi-text-reflow /, file)
  }
})
