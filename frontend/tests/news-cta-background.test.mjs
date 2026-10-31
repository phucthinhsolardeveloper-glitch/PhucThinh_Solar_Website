import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('news CTA keeps CMS content and uses the shared editorial image band', () => {
  const phpTemplate = read('../../domains/phucthinhsolar.com/public_html/templates/news/news_cta_tpl.php')
  const nuxtTheme = read('../app/assets/css/main.css')
  const nuxtCta = read('../app/components/news/NewsCta.vue')
  const sharedCta = read('../app/components/PublicCtaBanner.vue')

  assert.match(phpTemplate, /if\(\$nhCtaImage\)[\s\S]*class="nh-cta-bg"/)
  assert.match(nuxtTheme, /--pts-gold-gradient:\s*linear-gradient/)
  assert.match(nuxtCta, /variant="editorial"/)
  assert.match(nuxtCta, /:image="backgroundImage"/)
  assert.match(nuxtCta, /props\.cta\.image \|\| ''/)
  assert.match(nuxtCta, /cta\.title[\s\S]*cta\.description[\s\S]*cta\.hotline\.text[\s\S]*cta\.buttonLabel/)
  assert.match(sharedCta, /public-cta-banner__image" :src="imageSource" alt="" loading="lazy" decoding="async" @error="handleImageError"/)
  assert.match(sharedCta, /public-cta-banner__shade" aria-hidden="true"/)
  assert.match(sharedCta, /background:\s*#0a2251/)
  assert.match(sharedCta, /linear-gradient\(90deg, rgb\(6 21 55 \/ 72%\).*rgb\(10 34 81 \/ 26%\)/)
})

test('news CTA keeps its contact actions and follows the shared responsive editorial layout', () => {
  const nuxtCta = read('../app/components/news/NewsCta.vue')
  const sharedCta = read('../app/components/PublicCtaBanner.vue')

  assert.match(nuxtCta, /phoneLabel[\s\S]*cta\.hotline\.text[\s\S]*cta\.buttonLabel/)
  assert.match(nuxtCta, /openConsultation\('Kiến thức: ' \+ cta\.title\)/)
  assert.match(nuxtCta, /min-\[541px\]:flex-row min-\[761px\]:flex-col min-\[1101px\]:flex-row/)
  assert.match(nuxtCta, /text-\[clamp\(21px,2\.2vw,28px\)\][\s\S]*text-white/)
  assert.match(nuxtCta, /bg-hdr-gold[\s\S]*hover:bg-hdr-gold-hover/)
  assert.match(sharedCta, /public-cta-banner--editorial \{[^}]*min-height: 214px; padding: 2rem 2\.5rem;/)
  assert.match(sharedCta, /@media \(max-width: 760px\) \{[\s\S]*\.public-cta-banner--editorial,[\s\S]*grid-template-columns: minmax\(0, 1fr\); gap: 1\.15rem; min-height: 0; padding: 1\.4rem 1\.25rem;/)
})
