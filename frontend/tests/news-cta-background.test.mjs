import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('news CTA uses the effective PHP navy surface and background image treatment', () => {
  const phpTokens = read('../../domains/phucthinhsolar.com/public_html/assets/css/design-tokens.css')
  const phpTemplate = read('../../domains/phucthinhsolar.com/public_html/templates/news/news_cta_tpl.php')
  const nuxtTheme = read('../app/assets/css/main.css')
  const nuxtCta = read('../app/components/news/NewsCta.vue')

  assert.match(phpTemplate, /if\(\$nhCtaImage\)[\s\S]*class="nh-cta-bg"/)
  assert.match(phpTokens, /\.nh-cta-band\{background:var\(--pts-navy\)!important;\}/)
  assert.match(phpTokens, /\.nh-cta-bg\{opacity:\.5!important;\}/)
  assert.match(phpTokens, /\.nh-cta-band:before\s*\{[\s\S]*linear-gradient\(90deg,[\s\S]*rgba\(10,34,81,\.97\)[\s\S]*rgba\(10,34,81,\.3\)/)
  assert.match(phpTokens, /@media\(max-width:900px\)[\s\S]*\.nh-cta-band:before[\s\S]*linear-gradient\(180deg,rgba\(10,34,81,\.96\)/)
  assert.match(nuxtTheme, /--color-navy-800:\s*#0a2251/)

  assert.match(nuxtCta, /bg-navy-800/)
  assert.match(nuxtCta, /pointer-events-none absolute inset-0 -z-10 bg-\[linear-gradient\(180deg,rgba\(10,34,81,\.96\)_0,rgba\(10,34,81,\.86\)_100%\)\] min-\[901px\]:bg-\[linear-gradient\(90deg,rgba\(10,34,81,\.97\)_0,rgba\(10,34,81,\.9\)_38%,rgba\(10,34,81,\.55\)_68%,rgba\(10,34,81,\.3\)_100%\)\]/)
  assert.match(nuxtCta, /aria-hidden="true"\s*\/>\s*<img v-if="cta\.image"[\s\S]*-z-10 h-full w-full object-cover opacity-50/)
})
