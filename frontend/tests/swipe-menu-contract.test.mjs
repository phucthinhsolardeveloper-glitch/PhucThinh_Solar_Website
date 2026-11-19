import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const root = new URL('../app/', import.meta.url)
const composable = readFileSync(new URL('composables/useSwipeMenu.ts', root), 'utf8')
const header = readFileSync(new URL('components/SiteHeader.vue', root), 'utf8')
const floatingContact = readFileSync(new URL('components/SiteFloatingContact.vue', root), 'utf8')

test('swipe menu is touch-only, bounded to the mobile drawer range, and leaves scrolling passive', () => {
  assert.match(composable, /matchMedia\('\(pointer: coarse\)'\)/)
  assert.match(composable, /matchMedia\('\(max-width: 1320px\)'\)/)
  assert.match(composable, /document\.addEventListener\('touchstart',[\s\S]*?\{ passive: true \}\)/)
  assert.match(composable, /document\.addEventListener\('touchmove',[\s\S]*?\{ passive: true \}\)/)
  assert.match(composable, /document\.addEventListener\('touchend',[\s\S]*?\{ passive: true \}\)/)
  assert.doesNotMatch(composable, /preventDefault\(/)
  assert.match(composable, /Math\.abs\(dx\) < 64/)
  assert.match(composable, /Math\.abs\(dx\) <= 1\.6 \* Math\.abs\(dy\)/)
  assert.match(composable, /startedAt > 700/)
  assert.match(composable, /input', 'select', 'textarea'/)
  assert.match(composable, /overflowX/)
  assert.match(composable, /iframe'.*\[data-no-swipe\].*\.m-rail/s)
  assert.match(composable, /aria-roledescription="carousel".*canvas.*lightbox-strip.*pdp-studio-stage/)
  assert.match(composable, /document\.querySelector\(otherModal\)/)
  assert.doesNotMatch(composable, /\.pts-drawer/)
  assert.match(composable, /touchstart', onTouchStart[\s\S]*touchmove', onTouchMove[\s\S]*touchend', onTouchEnd/)
})

test('swipes open and close the existing header drawer without competing overlays', () => {
  assert.match(header, /useSwipeMenu\(\{[\s\S]*isOpen: \(\) => mobileOpen\.value/)
  assert.match(header, /activeOverlay\.value === null \|\| \(mobileOpen\.value && activeOverlay\.value === 'header'\)/)
  assert.match(header, /open: openMobileMenu/)
  assert.match(header, /close: \(\) => closeMobileMenu\(\)/)
  assert.match(header, /else if \(activeOverlay\.value === 'header'\) activeOverlay\.value = null/)
  assert.match(header, /@media \(prefers-reduced-motion: reduce\)[\s\S]*pts-drawer-enter-active \.pts-drawer__panel/)
  assert.match(floatingContact, /v-if="showBackToTop" class="group relative flex items-center"/)
})
