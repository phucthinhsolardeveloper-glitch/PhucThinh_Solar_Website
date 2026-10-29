import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const root = new URL('../app/', import.meta.url)
const read = (path) => readFileSync(new URL(path, root), 'utf8')
const header = read('components/SiteHeader.vue')
const footer = read('components/SiteFooter.vue')
const breadcrumb = read('components/SiteBreadcrumb.vue')
const links = read('composables/useMigrationLinks.ts')
const mainCss = read('assets/css/main.css')
const tokensCss = read('assets/css/tokens.css')

test('shared shell renders CMS assets and explicit navigation/taxonomy only', () => {
  const navigation = header.match(/const fallbackNav:[\s\S]*?= \[([\s\S]*?)\]/)?.[1] || ''
  assert.deepEqual([...navigation.matchAll(/label: '([^']+)'/g)].map((match) => match[1]), [
    'Giới thiệu', 'Giải pháp', 'Dự án', 'Kiến thức', 'Hợp tác',
  ])
  assert.match(header, /props\.site\.brand\.logo/)
  assert.match(header, /props\.site\.catalog/)
  assert.match(header, /props\.site\.productCategoryTree/)
  assert.match(header, /flattenLinks\(item\.children\)/)
  assert.doesNotMatch(header, /groupDefinitions|slug.*(?:lighting|solar|devices)/i)
  assert.match(header, /useMigrationCart\(\)/)
  assert.match(header, /refreshMigrationCart\(\)/)
  assert.match(header, /cartCountAvailable[\s\S]*config\.public\.homePublicRouteEnabled[\s\S]*config\.public\.catalogPublicRouteEnabled[\s\S]*config\.public\.productDetailPublicRouteEnabled/)
  assert.match(header, /if \(cartCountAvailable\.value\) void refreshMigrationCart\(\)/)
  assert.match(header, /v-if="cartCountAvailable && migrationCart\?\.count"/)
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

test('shared shell keeps controls reachable at 44px and preserves the 320px layout', () => {
  assert.match(header, /h-11 w-11/)
  assert.doesNotMatch(header, /max-ph:h-\[38px\]|max-ph:w-\[38px\]|class="[^"]*\bp-2\.5/)
  assert.match(header, /max-\[359px\]:px-3/)
  assert.match(header, /max-w-\[min\(198px,100%\)\]/)
  assert.match(header, /mobileOpen \? 'rotate-45'/)
  assert.match(header, /mobileOpen \? 'opacity-0'/)
  assert.match(header, /min-h-11 min-w-11/)
  assert.doesNotMatch(header, /role="menuitem"|role="menu"/)
  assert.match(header, /function catalogGroupLinks\(group: RenderedCatalogGroup\): FlatSiteLink\[\]/)
  assert.match(header, /const items: PublicSiteLink\[\] = group\.items\.map\(\(item\) => \(\{ \.\.\.item \}\)\)/)
  assert.match(header, /return flattenLinks\(items, 0, `catalog-\$\{group\.id\}-`\)/)
  assert.match(header, /v-for="entry in catalogGroupLinks\(group\)"/)

  assert.match(footer, /px-4 max-\[359px\]:px-3 tb:px-\(--spacing-gutter\)/)
  assert.match(footer, /flex min-h-11 items-center py-1 text-\[15px\]/)
  assert.match(footer, /inline-flex min-h-11 items-center text-\[15px\]/)
})

test('global keyboard focus has at least 3:1 contrast on the shell light and dark surfaces', () => {
  assert.match(mainCss, /:focus-visible\s*\{\s*outline:\s*3px solid var\(--color-gold-700\)/)
  assert.match(tokensCss, /:focus-visible\s*\{\s*outline:\s*3px solid var\(--color-gold-700\)/)
  const gold = mainCss.match(/--color-gold-700:\s*(#[0-9a-f]{6})/i)?.[1]
  assert.ok(gold)

  const luminance = (hex) => {
    const channels = hex.slice(1).match(/../g).map((channel) => Number.parseInt(channel, 16) / 255)
    const linear = channels.map((channel) => channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4)
    return linear[0] * 0.2126 + linear[1] * 0.7152 + linear[2] * 0.0722
  }
  const contrast = (left, right) => {
    const values = [luminance(left), luminance(right)].sort((a, b) => b - a)
    return (values[0] + 0.05) / (values[1] + 0.05)
  }
  assert.ok(contrast(gold, '#ffffff') >= 3)
  assert.ok(contrast(gold, '#061537') >= 3)
})

test('footer renders its CMS heading once and uses the validated rich-text component for contact content', () => {
  assert.match(footer, /<h2[^>]*>\s*\{\{ footer\?\.companyName \|\| 'Thông tin doanh nghiệp' \}\}\s*<\/h2>/)
  assert.match(footer, /<EditorialRichText v-if="footer\?\.contactText\?\.html" :content="footer\.contactText" class="cms-content-on-dark/)
  assert.doesNotMatch(footer, /<p v-if="footer\?\.companyName/)
  assert.doesNotMatch(footer, /contactLines/)
})
