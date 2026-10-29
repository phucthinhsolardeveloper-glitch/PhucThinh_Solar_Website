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

test('a single solution card gets a full-width desktop row while the mobile rail stays stacked', () => {
  assert.match(cards, /min-\[1101px\]:grid/)
  assert.match(cards, /cards\.length === 1 \? 'min-\[1101px\]:grid-cols-1' : 'min-\[1101px\]:grid-cols-5'/)
  assert.match(cards, /cards\.length === 1 \? 'min-\[1101px\]:w-full min-\[1101px\]:flex-row min-\[1101px\]:gap-6' : ''/)
  assert.match(cards, /cards\.length === 1 \? 'min-\[1101px\]:w-1\/2 min-\[1101px\]:max-w-\[560px\] min-\[1101px\]:flex-none' : ''/)
  assert.match(cards, /basis-\[280px\][^\"]*max-\[520px\]:basis-\[min\(78vw,286px\)\]/)
  assert.match(cards, /cards\.length === 1 \? 'min-\[1101px\]:justify-center min-\[1101px\]:pt-0' : ''/)
  assert.match(cards, /<div v-if="card\.image"/)
  assert.match(cards, /max-\[520px\]:-mr-4 max-\[520px\]:pr-4 max-\[359px\]:-mr-3 max-\[359px\]:pr-3/)
  assert.match(cards, /max-\[520px\]:min-h-0/)
  assert.match(cards, /max-\[520px\]:h-\[30px\] max-\[520px\]:w-\[30px\]/)
  assert.match(cards, /max-\[520px\]:leading-\[1\.8\]/)
  assert.doesNotMatch(cards, /<span v-else aria-hidden="true">—<\/span>/)
})
