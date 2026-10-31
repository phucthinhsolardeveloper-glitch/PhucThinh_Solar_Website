import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('Nuxt footer follows PHP column ownership and keeps location rows flat', () => {
  const footer = read('../app/components/SiteFooter.vue')
  const php = read('../../domains/phucthinhsolar.com/public_html/templates/layout/footer.php')

  assert.match(footer, /nav:grid-cols-\[290px_minmax\(280px,1fr\)_minmax\(460px,1\.3fr\)\]/)
  assert.match(footer, /grid-cols-1 gap-8 min-\[768px\]:grid-cols-\[minmax\(0,5fr\)_minmax\(0,7fr\)\]/)
  assert.match(footer, /class="m-0 not-italic"/)
  assert.match(footer, /<ul v-if="footer\?\.socialLinks\.length"[^>]*hidden[^>]*tb:flex[^>]*tb:pr-14/)
  assert.match(footer, /socialKind\(social\.label, social\.href\) === 'facebook'/)
  assert.match(footer, /socialKind\(social\.label, social\.href\) === 'youtube'/)
  assert.doesNotMatch(footer, /\{\{ social\.label \}\}/)
  assert.doesNotMatch(footer, /Tất cả quyền được bảo lưu/)
  assert.doesNotMatch(footer, /<address[\s\S]*?rounded-btn border border-white\/10 bg-white/)
  const logoColumn = footer.indexOf('<!-- Cột 1: Biểu trưng thương hiệu -->')
  const policyStart = footer.indexOf('<nav v-if="footer?.policies.length"')
  const policyEnd = footer.indexOf('</nav>', policyStart)
  const trustBadge = footer.indexOf('footer.trustBadge.href')
  assert.ok(logoColumn >= 0 && trustBadge > logoColumn && trustBadge < policyStart)
  assert.ok(policyStart >= 0 && policyEnd > policyStart)
  assert.match(footer, /max-w-\[240px\][\s\S]*object-contain tb:max-w-\[248px\]/)
  assert.match(php, /class="footer-bct-badge"/)
  assert.match(php, /class="item_cn"/)
})

test('catalog category rail centers the selected item after navigation', () => {
  const catalog = read('../app/components/catalog/CatalogPage.vue')
  assert.match(catalog, /ref="categoryNavRef"/)
  assert.match(catalog, /querySelector<HTMLElement>\('\[aria-current="page"\]'\)/)
  assert.match(catalog, /nav\.scrollTo\(\{ left: Math\.max\(0, left\), behavior: reducedMotion \? 'auto' : 'smooth' \}\)/)
  assert.match(catalog, /watch\(\(\) => selectedCategory\.value\?\.url/)
})

test('footer rich text overrides editorial body color on dark surfaces', () => {
  const css = read('../app/assets/css/main.css')
  assert.match(css, /\.cms-content--editorial\.cms-content-on-dark\s*\{\s*color:\s*var\(--color-blue-100\)/)
  assert.match(css, /\.cms-content--editorial\.cms-content-on-dark strong\s*\{\s*color:\s*#fff/)
})

test('floating contact exposes accessible back-to-top only after scrolling', () => {
  const floating = read('../app/components/SiteFloatingContact.vue')
  const phpFooter = read('../../domains/phucthinhsolar.com/public_html/templates/layout/footer.php')
  const phpScroll = read('../../domains/phucthinhsolar.com/public_html/templates/layout/js.php')
  const phpProjection = read('../../domains/phucthinhsolar.com/public_html/libraries/pts_public_navigation_api.php')

  assert.match(floating, /window\.scrollY > 300/)
  assert.match(floating, /prefers-reduced-motion: reduce/)
  assert.match(floating, /aria-label="Lên đầu trang"/)
  assert.doesNotMatch(floating, /channelsOpen|contact-channel-list|facebookHref|smsHref/)
  assert.match(floating, /props\.site\.brand\.zaloHref/)
  assert.match(floating, /:href="phoneHref"/)
  assert.match(floating, /:href="zaloHref"/)
  assert.match(floating, /motion-reduce:animate-none/)
  assert.match(floating, /motion-reduce:transition-none/)
  assert.match(floating, /#0068ff/)
  assert.match(floating, /bg-gold-500/)
  assert.match(floating, /phoneHref/)
  assert.match(phpFooter, /class="pts-contact-dock-toggle"/)
  assert.match(phpFooter, /id="fanpage-vr"/)
  assert.match(phpFooter, /id="zalo-vr"/)
  assert.match(phpFooter, /class="pts-contact-dock-phone"/)
  assert.match(phpProjection, /\$zaloDigits\s*=\s*preg_replace\('\/\[\^0-9\]\/\',\s*\'\',\s*ptsPublicNavScalar\(isset\(\$options\['zalo'\]\)/)
  assert.match(phpProjection, /\$brand\['zaloHref'\]\s*=\s*'https:\/\/zalo\.me\/'\.\$zaloDigits/)
  assert.match(phpScroll, /class="progress-wrap cursor-pointer active-progress"/)
})
