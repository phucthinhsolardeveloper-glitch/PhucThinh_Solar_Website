import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('Nuxt footer follows PHP column ownership and keeps location rows flat', () => {
  const footer = read('../app/components/SiteFooter.vue')

  assert.match(footer, /nav:grid-cols-\[290px_minmax\(280px,1fr\)_minmax\(460px,1\.3fr\)\]/)
  assert.match(footer, /grid-cols-1 gap-8 min-\[768px\]:grid-cols-\[minmax\(350px,1fr\)_minmax\(0,1fr\)\] min-\[768px\]:gap-12/)
  assert.match(footer, /class="m-0 not-italic"/)
  assert.match(footer, /<ul v-if="footer\?\.socialLinks\.length" class="m-0 flex list-none flex-wrap items-center gap-x-5 gap-y-1 p-0 tb:pr-14"/)
  // The icon comes from the link's own kind, so a renamed label cannot change which badge is drawn.
  assert.match(footer, /:name="socialIcon\(social\.kind\)"/)
  assert.match(footer, /facebook: 'i-simple-icons-facebook'/)
  assert.match(footer, /zalo: 'i-simple-icons-zalo'/)
  assert.doesNotMatch(footer, /\{\{ social\.label \}\}/)
  assert.doesNotMatch(footer, /Tất cả quyền được bảo lưu/)
  assert.doesNotMatch(footer, /<address[\s\S]*?rounded-btn border border-white\/10 bg-white/)
  const logoColumn = footer.indexOf('<!-- Cột 1: Biểu trưng thương hiệu -->')
  const policyStart = footer.indexOf('<nav v-if="footer?.policies.length"')
  const policyEnd = footer.indexOf('</nav>', policyStart)
  const trustBadge = footer.indexOf('footer.trustBadge.href')
  assert.ok(logoColumn >= 0 && trustBadge > logoColumn && trustBadge < policyStart)
  assert.ok(policyStart >= 0 && policyEnd > policyStart)
  assert.match(footer, /max-w-\[300px\][\s\S]*object-contain tb:max-w-\[280px\]/)
})

test('catalog category rail centers the selected item after navigation', () => {
  const catalog = readFileSync(new URL('../app/components/catalog/CatalogPage.vue', import.meta.url), 'utf8')
  const pill = readFileSync(new URL('../app/composables/useSlidingPill.ts', import.meta.url), 'utf8')
  assert.match(catalog, /useSlidingPill\(categoryNavRef, 'catalog'/)
  assert.match(pill, /chip\.offsetLeft \+ chip\.offsetWidth \/ 2 - rail\.clientWidth \/ 2/)
})

test('footer rich text overrides editorial body color on dark surfaces', () => {
  const css = read('../app/assets/css/main.css')
  assert.match(css, /\.cms-content--editorial\.cms-content-on-dark\s*\{\s*color:\s*var\(--color-blue-100\)/)
  assert.match(css, /\.cms-content--editorial\.cms-content-on-dark strong\s*\{\s*color:\s*#fff/)
})

test('floating contact exposes accessible back-to-top only after scrolling', () => {
  const floating = read('../app/components/SiteFloatingContact.vue')

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
})
