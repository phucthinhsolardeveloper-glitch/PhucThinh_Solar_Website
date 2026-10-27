import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const root = new URL('../app/', import.meta.url)
const read = (path) => readFileSync(new URL(path, root), 'utf8')
const header = read('components/SiteHeader.vue')
const footer = read('components/SiteFooter.vue')
const breadcrumb = read('components/SiteBreadcrumb.vue')
const links = read('composables/useMigrationLinks.ts')

test('shared shell renders CMS assets and explicit navigation/taxonomy only', () => {
  assert.match(header, /props\.site\.brand\.logo/)
  assert.match(header, /props\.site\.catalog/)
  assert.match(header, /props\.site\.productCategoryTree/)
  assert.match(header, /flattenLinks\(item\.children\)/)
  assert.doesNotMatch(header, /groupDefinitions|slug.*(?:lighting|solar|devices)/i)
  assert.match(header, /useMigrationCart\(\)/)
  assert.match(header, /refreshMigrationCart\(\)/)
  assert.doesNotMatch(header, /https?:\/\/[^'"`]+logo/i)
})

test('header overlays expose keyboard state, focus trap and scroll restoration hooks', () => {
  for (const pattern of [
    /role="dialog"[\s\S]*aria-modal="true"[\s\S]*aria-labelledby="ptsHeaderSearchTitle"/,
    /role="dialog"[\s\S]*aria-modal="true"[\s\S]*aria-labelledby="ptsCatalogTitle"/,
    /role="dialog"[\s\S]*aria-modal="true"[\s\S]*aria-labelledby="ptsMobileTitle"/,
    /function trapFocus\(/,
    /event\.key === 'Escape'/,
    /bodyOverflowBeforeLock/,
    /ref="searchTrigger"/,
    /ref="mobileTrigger"/,
    /aria-controls="ptsMobileCatalog"/,
  ]) assert.match(header, pattern)
  assert.match(header, /searchOpen\.value\) closeSearch\(\)/)
  assert.match(header, /navDropdownOpen\.value\) closeNavDropdown\(true\)/)
})

test('footer and breadcrumb route CMS links through the migration resolver', () => {
  assert.match(footer, /footer\.policies/)
  assert.match(footer, /footer\.locations/)
  assert.match(footer, /footer\.socialLinks/)
  assert.match(footer, /footer\.trustBadge/)
  assert.match(footer, /footerHref\(policy\.href\)/)
  assert.match(breadcrumb, /migrationHref\(item\.to\)/)
  assert.match(links, /function external\(href: string\)/)
})
