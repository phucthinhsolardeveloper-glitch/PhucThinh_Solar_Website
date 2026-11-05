import assert from 'node:assert/strict'
import { readFileSync, statSync } from 'node:fs'
import { test } from 'node:test'

const banner = readFileSync(new URL('../app/components/PublicCtaBanner.vue', import.meta.url), 'utf8')
const solutionsCta = readFileSync(new URL('../app/components/solutions/SolutionsCta.vue', import.meta.url), 'utf8')
const asset = (name) => new URL(name.endsWith('.mp4') ? `../server/assets/video/${name}` : `../public/assets/video/${name}`, import.meta.url)

test('ambient CTA video is silent, lazy and never reachable by keyboard or assistive tech', () => {
  const video = banner.match(/<video[\s\S]*?\/>/)?.[0] || ''
  for (const attr of ['muted', 'playsinline', 'preload="none"', 'aria-hidden="true"', 'tabindex="-1"']) assert.ok(video.includes(attr), `video needs ${attr}`)
  assert.doesNotMatch(video, /\bcontrols\b|\bautoplay\b|\bloop\b|:poster=/, 'the lazy <picture> underneath is the poster; a video poster attribute fetches eagerly')
})

test('ambient video only plays on wide screens with motion allowed; poster covers every other case', () => {
  assert.match(banner, /matchMedia\('\(min-width: 901px\)'\)/)
  assert.match(banner, /matchMedia\('\(prefers-reduced-motion: reduce\)'\)/)
  assert.match(banner, /saveData/)
  assert.match(banner, /IntersectionObserver/)
  assert.match(banner, /media="\(max-width: 900px\)"/)
  assert.match(banner, /prefers-reduced-motion: reduce\)[\s\S]*\.cta-band__video \{ display: none; \}/)
})

test('solutions CTA keeps an Admin-uploaded image ahead of the illustrative footage', () => {
  assert.match(solutionsCta, /hasUploadedImage\.value \? null :/)
  assert.match(solutionsCta, /:video="ambientVideo"/)
})

test('web footage stays a light derivative, never the 4K master', () => {
  const mp4 = readFileSync(asset('solar-rooftop-aerial-1600.mp4'))
  assert.equal(mp4.toString('ascii', 4, 8), 'ftyp')
  assert.ok(!mp4.includes(Buffer.from('soun')), 'web footage must carry no audio track')
  assert.ok(statSync(asset('solar-rooftop-aerial-1600.mp4')).size < 4 * 1024 * 1024)
  for (const poster of ['solar-rooftop-aerial-poster-1600.webp', 'solar-rooftop-aerial-poster-960.webp']) {
    const bytes = readFileSync(asset(poster))
    assert.equal(bytes.toString('ascii', 8, 12), 'WEBP')
    assert.ok(bytes.length < 320 * 1024)
  }
})
