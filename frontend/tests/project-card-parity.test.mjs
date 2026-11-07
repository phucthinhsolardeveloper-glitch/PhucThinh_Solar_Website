import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('project cards use a flat surface and retain PHP project metadata', () => {
  const card = read('../app/components/projects/ProjectCard.vue')

  assert.match(card, /aspect-\[4\/3\]/)
  assert.doesNotMatch(card, /border border-slate|bg-white/)
  assert.doesNotMatch(card, /hover:-translate-y|hover:shadow/)
  assert.match(card, /project\.address/)
  assert.match(card, /project\.category\.name/)
})
