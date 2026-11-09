import assert from 'node:assert/strict'
import test from 'node:test'
import { layoutDraftQuery, pageLayout, PAGE_SECTIONS } from '../shared/page-layout.ts'

test('a missing or broken layout renders the page as built', () => {
  assert.deepEqual(pageLayout('home', undefined).map((b) => b.type), [...PAGE_SECTIONS.home])
  assert.deepEqual(pageLayout('about', 'x').map((b) => b.type), [...PAGE_SECTIONS.about])
})

test('a published layout keeps its order, drops unknown or repeated sections and odd variants', () => {
  const layout = pageLayout('about', [{ type: 'story' }, { type: 'compass', variant: 'muted' }, { type: 'nope' }, { type: 'compass' }, { type: 'people', variant: '<b>' }])
  assert.deepEqual(layout, [{ type: 'story', variant: 'default' }, { type: 'compass', variant: 'muted' }, { type: 'people', variant: 'default' }])
})

test('only a well-formed signed link becomes a preview query', () => {
  assert.equal(layoutDraftQuery('12', { expires: '1794221369', signature: 'a'.repeat(64) })?.layout, '12')
  assert.equal(layoutDraftQuery('0', { expires: '1794221369', signature: 'a'.repeat(64) }), null)
  assert.equal(layoutDraftQuery('12', { expires: '1794221369', signature: 'zz' }), null)
})
