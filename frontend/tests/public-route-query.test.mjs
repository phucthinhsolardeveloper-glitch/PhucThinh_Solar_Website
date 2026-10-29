import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { legacyMediaPageQuery, legacyNewsFilterQuery, legacyProjectsFilterQuery } from '../shared/public-route-query.ts'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('legacy hub query mappers preserve only the supported scalar public state', () => {
  assert.deepEqual(legacyNewsFilterQuery({ p: '3', q: '  den nang luong  ', category: 'kien-thuc', extra: 'ignored' }), {
    p: '3', q: 'den nang luong', category: 'kien-thuc',
  })
  assert.deepEqual(legacyProjectsFilterQuery({ p: '2', category: 'du-an-ho-gia-dinh', extra: 'ignored' }), {
    p: '2', category: 'du-an-ho-gia-dinh',
  })
  assert.deepEqual(legacyMediaPageQuery({ p: '4', category: 'ignored', extra: 'ignored' }), { p: '4' })
})

test('legacy hub query mappers drop arrays, malformed pages/categories, and unsafe news text', () => {
  const repeated = { p: ['2'], q: ['den'], category: ['tin-moi'] }
  assert.deepEqual(legacyNewsFilterQuery(repeated), {})
  assert.deepEqual(legacyProjectsFilterQuery(repeated), {})
  assert.deepEqual(legacyMediaPageQuery(repeated), {})

  assert.deepEqual(legacyNewsFilterQuery({ p: '0', q: 'x'.repeat(121), category: '../news' }), {})
  assert.deepEqual(legacyNewsFilterQuery({ q: 'den\n<script>' }), {})
  assert.deepEqual(legacyProjectsFilterQuery({ p: '1000001', category: 'Bad-Slug' }), {})
  assert.deepEqual(legacyMediaPageQuery({ p: '1.2' }), {})
})

test('flag-off editorial middleware passes only its route-specific sanitized query', () => {
  const expectations = [
    ['news-public-route.ts', 'legacyNewsFilterQuery'],
    ['projects-public-route.ts', 'legacyProjectsFilterQuery'],
    ['gallery-public-route.ts', 'legacyMediaPageQuery'],
    ['video-public-route.ts', 'legacyMediaPageQuery'],
  ]
  for (const [file, helper] of expectations) {
    const source = read(`../app/middleware/${file}`)
    assert.match(source, new RegExp(`phpPublicRouteUrl\\([^\\n]+${helper}\\(to\\.query\\)`))
  }
})
