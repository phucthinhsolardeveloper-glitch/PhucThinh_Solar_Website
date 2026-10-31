import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const frontendRoot = new URL('../', import.meta.url)
const phpRoot = new URL('../../domains/phucthinhsolar.com/public_html/', import.meta.url)
const readFrontend = (path) => readFileSync(new URL(path, frontendRoot), 'utf8')
const readPhp = (path) => readFileSync(new URL(path, phpRoot), 'utf8')

test('solution detail CTA keeps the PHP campaign fallback and 1600x500 media contract', () => {
  const source = readPhp('sources/solution_hub.php')
  const template = readPhp('templates/news/service_detail_tpl.php')
  const api = readPhp('api/pts_v1_solution_detail.php')
  const dto = readPhp('libraries/pts_public_solution_detail_api.php')
  const component = readFrontend('app/components/solutions/SolutionsCta.vue')

  assert.match(source, /if\(\$solutionCtaImage === ''\) \$solutionCtaImage = 'assets\/images\/site-visuals\/catalog-street-lighting\.jpg'/)
  assert.match(template, /getThumbUrl\('1600x500x1',\s*\$solutionCtaImage\)/)
  assert.match(api, /ptsSolutionDetailPayload\([\s\S]*?\$ctaRows[\s\S]*?\$hotline/)
  assert.match(dto, /'campaignCta'\s*=>\s*ptsSolutionDetailCampaignCta\(\$ctaRows,\s*\$hotline\)/)
  assert.match(component, /thumbs\/1600x500x1\$\{src\}/)
  assert.match(component, /catalog-street-lighting\\\.jpg/)
  assert.match(component, /return \/\^\\\/assets\\\/images\\\/site-visuals\\\/catalog-street-lighting/)
  assert.match(readFrontend('app/components/PublicCtaBanner.vue'), /corporate: '\/assets\/images\/site-visuals\/cta-corporate-bg\.webp'/)
})

test('solution CTA uses the shared corporate image band and its responsive layout', () => {
  const hubCss = readPhp('assets/css/solution-hub.css')
  const tokenCss = readPhp('assets/css/design-tokens.css')
  const flatCss = readPhp('assets/css/flat-surface-system.css')
  const component = readFrontend('app/components/solutions/SolutionsCta.vue')
  const sharedCta = readFrontend('app/components/PublicCtaBanner.vue')

  assert.match(hubCss, /\.pts-sh-contact-band\{[^}]*background:var\(--sh-navy\)/)
  assert.match(tokenCss, /\.pts-sh-contact-background,[\s\S]*?\{opacity:\.5!important;\}/)
  assert.match(flatCss, /\.pts-sh-intro-image,\.pts-sh-contact-band,\.pts-ph-contact-band\{border-radius:3px!important\}/)

  assert.match(component, /variant="corporate"/)
  assert.match(component, /:image="backgroundImage"/)
  assert.match(component, /id="ptsContactModal"/)
  assert.match(sharedCta, /public-cta-banner__image" :src="imageSource" alt="" loading="lazy" decoding="async" @error="handleImageError"/)
  assert.match(sharedCta, /public-cta-banner__shade" aria-hidden="true"/)
  assert.match(sharedCta, /linear-gradient\(90deg, rgb\(6 21 55 \/ 65%\).*rgb\(10 34 81 \/ 22%\)/)
  assert.match(sharedCta, /public-cta-banner--corporate \{[^}]*min-height: 154px; padding: 1\.5rem 1\.75rem;/)
  assert.match(sharedCta, /@media \(max-width: 760px\) \{[\s\S]*\.public-cta-banner--corporate,[\s\S]*grid-template-columns: minmax\(0, 1fr\); gap: 1\.15rem; min-height: 0; padding: 1\.4rem 1\.25rem;/)
})

test('solution CTA retains CMS copy, hotline and consultation action', () => {
  const hubCss = readPhp('assets/css/solution-hub.css')
  const component = readFrontend('app/components/solutions/SolutionsCta.vue')

  assert.match(hubCss, /\.pts-sh-contact-copy h2\{[^}]*font-size:24px!important;line-height:1\.3!important;font-weight:800!important/)
  assert.match(hubCss, /\.pts-sh-contact-copy p\{[^}]*font-size:16px!important;line-height:1\.65!important/)
  assert.match(component, /text-\[24px\] font-extrabold leading-\[1\.3\][\s\S]*max-\[520px\]:text-\[23px\]/)
  assert.match(component, /text-base leading-\[1\.65\] text-blue-100\/95/)
  assert.match(component, /cta\.title[\s\S]*cta\.description[\s\S]*cta\.buttonLabel[\s\S]*cta\.hotline/)
  assert.match(component, /links\.generic\(cta\.links\.consult\.href === '#ptsContactModal'/)
  assert.match(component, /openConsultation\(\)/)
  assert.match(component, /bg-hdr-gold[\s\S]*hover:bg-hdr-gold-hover/)
  assert.match(component, /<small class="mb-\[5px\] block text-xs text-blue-100">/)
  assert.match(component, /<strong class="text-xl font-extrabold text-white">/)
  assert.doesNotMatch(component, /variant="outline"|cta\.hotline\.label.*uppercase/)
})
