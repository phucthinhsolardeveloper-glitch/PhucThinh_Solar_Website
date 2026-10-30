import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('Nuxt footer follows PHP column ownership and keeps location rows flat', () => {
  const footer = read('../app/components/SiteFooter.vue')
  const php = read('../../domains/phucthinhsolar.com/public_html/templates/layout/footer.php')

  assert.match(footer, /nav:grid-cols-\[290px_minmax\(280px,1fr\)_minmax\(460px,1\.3fr\)\]/)
  assert.match(footer, /ph:grid-cols-\[minmax\(0,5fr\)_minmax\(0,7fr\)\]/)
  assert.match(footer, /class="m-0 not-italic"/)
  assert.match(footer, /<ul v-if="footer\?\.socialLinks\.length"[^>]*tb:pr-14/)
  assert.doesNotMatch(footer, /<address[\s\S]*?rounded-btn border border-white\/10 bg-white/)
  const policyStart = footer.indexOf('<nav v-if="footer?.policies.length || footer?.trustBadge"')
  const policyEnd = footer.indexOf('</nav>', policyStart)
  const trustBadge = footer.indexOf('footer.trustBadge.href')
  assert.ok(policyStart >= 0 && trustBadge > policyStart && trustBadge < policyEnd)
  assert.match(php, /class="footer-bct-badge"/)
  assert.match(php, /class="item_cn"/)
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
  assert.doesNotMatch(floating, /footerObserver|footerVisible/)
  assert.doesNotMatch(floating, /channelsOpen|contact-channel-list|facebookHref|smsHref/)
  assert.match(floating, /props\.site\.brand\.zaloHref/)
  assert.match(floating, /:href="phoneHref"/)
  assert.match(floating, /:href="zaloHref"/)
  assert.match(floating, /motion-reduce:animate-none/)
  assert.match(floating, /motion-reduce:transition-none/)
  assert.match(floating, /#0068ff/)
  assert.match(floating, /#c58a2d/)
  assert.match(floating, /phoneHref/)
  assert.match(phpFooter, /class="pts-contact-dock-toggle"/)
  assert.match(phpFooter, /id="fanpage-vr"/)
  assert.match(phpFooter, /id="zalo-vr"/)
  assert.match(phpFooter, /class="pts-contact-dock-phone"/)
  assert.match(phpProjection, /\$zaloDigits\s*=\s*preg_replace\('\/\[\^0-9\]\/\',\s*\'\',\s*ptsPublicNavScalar\(isset\(\$options\['zalo'\]\)/)
  assert.match(phpProjection, /\$brand\['zaloHref'\]\s*=\s*'https:\/\/zalo\.me\/'\.\$zaloDigits/)
  assert.match(phpScroll, /class="progress-wrap cursor-pointer active-progress"/)
})
