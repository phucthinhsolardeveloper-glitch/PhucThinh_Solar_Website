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
})

test('solution CTA reproduces the effective PHP navy background, photo opacity, and desktop/mobile overlays', () => {
  const hubCss = readPhp('assets/css/solution-hub.css')
  const tokenCss = readPhp('assets/css/design-tokens.css')
  const flatCss = readPhp('assets/css/flat-surface-system.css')
  const component = readFrontend('app/components/solutions/SolutionsCta.vue')

  assert.match(hubCss, /\.pts-sh-contact-band\{[^}]*background:var\(--sh-navy\)/)
  assert.match(tokenCss, /\.pts-sh-contact-background,[\s\S]*?\{opacity:\.5!important;\}/)
  assert.match(tokenCss, /\.pts-sh-contact-band:before[\s\S]*?rgba\(10,34,81,\.97\)[\s\S]*?rgba\(10,34,81,\.3\)/)
  assert.match(tokenCss, /@media\(max-width:900px\)[\s\S]*?\.pts-sh-contact-band:before[\s\S]*?linear-gradient\(180deg,rgba\(10,34,81,\.96\)/)
  assert.match(flatCss, /\.pts-sh-intro-image,\.pts-sh-contact-band,\.pts-ph-contact-band\{border-radius:3px!important\}/)

  assert.match(component, /bg-navy-800/)
  assert.match(component, /object-cover opacity-50/)
  assert.match(component, /bg-\[linear-gradient\(180deg,rgba\(10,34,81,\.96\)_0,rgba\(10,34,81,\.86\)_100%\)\]/)
  assert.match(component, /min-\[901px\]:bg-\[linear-gradient\(90deg,rgba\(10,34,81,\.97\)_0,rgba\(10,34,81,\.9\)_38%,rgba\(10,34,81,\.55\)_68%,rgba\(10,34,81,\.3\)_100%\)\]/)
  assert.match(component, /rounded-\[3px\]/)
})

test('solution CTA copy and hotline follow PHP desktop/mobile typography and plain-link layout', () => {
  const hubCss = readPhp('assets/css/solution-hub.css')
  const component = readFrontend('app/components/solutions/SolutionsCta.vue')

  assert.match(hubCss, /\.pts-sh-contact-copy\{max-width:610px;\}/)
  assert.match(hubCss, /\.pts-sh-contact-copy h2\{[^}]*font-size:24px!important;line-height:1\.3!important;font-weight:800!important/)
  assert.match(hubCss, /\.pts-sh-contact-copy p\{[^}]*font-size:16px!important;line-height:1\.65!important/)
  assert.match(hubCss, /@media\(max-width:520px\)[\s\S]*?\.pts-sh-contact-copy h2\{font-size:23px!important;\}/)
  assert.match(hubCss, /\.pts-sh-contact-hotline|\.pts-sh-hotline small\{display:block;color:#cbd6e7;font-size:12px;margin-bottom:5px;\}/)

  assert.match(component, /max-w-\[630px\]/)
  assert.match(component, /text-\[24px\] font-extrabold leading-\[1\.3\][\s\S]*max-\[520px\]:text-\[23px\]/)
  assert.match(component, /text-base leading-\[1\.65\]/)
  assert.match(component, /<a\s+v-if="cta\.hotline"[\s\S]*text-center[\s\S]*min-\[901px\]:text-left/)
  assert.match(component, /<small class="mb-\[5px\] block text-xs text-blue-100">/)
  assert.match(component, /<strong class="text-xl font-extrabold text-white">/)
  assert.doesNotMatch(component, /variant="outline"|cta\.hotline\.label.*uppercase/)
})
