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
