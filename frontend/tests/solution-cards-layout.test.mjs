import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const cards = readFileSync(new URL('../app/components/solutions/SolutionCards.vue', import.meta.url), 'utf8')
const intro = readFileSync(new URL('../app/components/solutions/SolutionsIntro.vue', import.meta.url), 'utf8')
const hub = readFileSync(new URL('../app/pages/giai-phap.vue', import.meta.url), 'utf8')

test('solutions intro matches PHP typography and text-only layout at tablet and desktop widths', () => {
  assert.match(intro, /grid grid-cols-1 items-start gap-0 min-\[801px\]:grid-cols-\[minmax\(0,1\.05fr\)_minmax\(0,\.95fr\)\] min-\[801px\]:gap-x-\[72px\] min-\[801px\]:gap-y-0/)
  assert.match(intro, /max-\[800px\]:max-w-none/)
  assert.match(intro, /text-\[clamp\(34px,3\.2vw,48px\)\][^\n]*leading-\[1\.18\][^\n]*max-\[520px\]:text-\[clamp\(32px,8\.5vw,39px\)\]/)
  assert.match(intro, /text-\[18px\] leading-\[1\.7\]/)
  assert.match(intro, /max-\[800px\]:text-\[32px\] max-\[800px\]:leading-\[40\.12px\]/)
  assert.match(intro, /max-\[520px\]:text-\[17px\] max-\[520px\]:leading-\[1\.8\]/)
  assert.match(intro, /max-\[520px\]:gap-0/)
  assert.match(hub, /min-\[561px\]:px-5 min-\[821px\]:px-6 min-\[1321px\]:px-\[34px\]/)
  assert.match(intro, /leading-\[1\.75\][^\n]*max-\[520px\]:leading-\[1\.66\]/)
})

test('solution cards use a responsive editorial mosaic with a full-width single item', () => {
  assert.match(cards, /class="mosaic"[^>]*:class="\{ 'is-single': cards\.length === 1 \}"/)
  assert.match(cards, /\.mosaic \{[\s\S]*grid-template-columns: repeat\(4, minmax\(0, 1fr\)\)/)
  assert.match(cards, /\.mosaic\.is-single \{ grid-template-columns: 1fr;/)
  assert.match(cards, /@media \(max-width: 1100px\)[\s\S]*grid-template-columns: repeat\(2, minmax\(0, 1fr\)\)/)
  assert.match(cards, /@media \(max-width: 640px\)[\s\S]*grid-template-columns: 1fr/)
  assert.match(cards, /<img\s+v-if="card\.image"[\s\S]*object-fit: cover/)
  assert.match(cards, /\.tile:focus-visible[\s\S]*outline: 3px solid/)
})
