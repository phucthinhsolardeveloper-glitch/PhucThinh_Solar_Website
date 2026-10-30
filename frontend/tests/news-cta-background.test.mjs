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

test('news CTA responsive layout follows PHP icon, action, and heading breakpoints', () => {
  const phpCss = read('../../domains/phucthinhsolar.com/public_html/assets/css/news-hub.css')
  const nuxtCta = read('../app/components/news/NewsCta.vue')

  assert.match(phpCss, /@media\(max-width:1100px\)\{\.nh-cta-icon\{display:none\}\.nh-cta-actions\{flex-direction:column;gap:10px\}/)
  assert.match(phpCss, /@media\(max-width:800px\)[\s\S]*?\.nh-cta-copy h2\{font-size:20px!important\}/)
  assert.match(phpCss, /@media\(max-width:540px\)[\s\S]*?\.nh-cta-band\{flex-direction:column;padding:25px 22px;gap:20px\}\.nh-cta-actions\{border-left:0;border-top:1px solid #dae5f2;padding:17px 0 0;width:100%;align-items:stretch\}/)

  assert.match(nuxtCta, /class="hidden h-\[68px\] w-\[68px\][\s\S]*min-\[1101px\]:flex"/)
  assert.match(nuxtCta, /grid-cols-1[\s\S]*min-\[541px\]:grid-cols-\[minmax\(0,1fr\)_auto\][\s\S]*max-\[800px\]:items-start[\s\S]*min-\[1101px\]:grid-cols-\[68px_minmax\(0,1fr\)_auto\]/)
  assert.match(nuxtCta, /min-\[541px\]:border-l[\s\S]*min-\[1101px\]:flex-row/)
  assert.match(nuxtCta, /text-\[22px\][\s\S]*max-\[800px\]:text-\[20px\]/)
  assert.match(nuxtCta, /font-\[850\][\s\S]*tracking-\[-\.025em\]/)
  assert.match(nuxtCta, /max-\[767px\]:-mx-4 max-\[767px\]:px-4 max-\[359px\]:-mx-3 max-\[359px\]:px-3/)
  assert.match(nuxtCta, /leading-\[1\.66\][\s\S]*text-\[23px\] font-bold/)
})
