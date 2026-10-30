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
const app = read('app.vue')
const nuxtConfig = readFileSync(new URL('../nuxt.config.ts', import.meta.url), 'utf8')

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
  assert.match(header, /const cartCountAvailable = computed\(\(\) => true\)/)
  assert.doesNotMatch(header, /PublicRouteEnabled/)
  assert.match(header, /if \(cartCountAvailable\.value\) void refreshMigrationCart\(\)/)
  assert.match(header, /v-if="cartCountAvailable && migrationCart\?\.count"/)
  assert.doesNotMatch(header, /https?:\/\/[^'"`]+logo/i)
})

test('shared header keeps PHP production single-row composition without a duplicate utility strip', () => {
  const phpHeader = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/templates/layout/header.php', import.meta.url), 'utf8')
  assert.match(phpHeader, /<div class="header-bottom pts-header-primary">/)
  assert.match(header, /class="pts-site-header sticky top-0 z-\[1000\]"/)
  assert.doesNotMatch(header, /Tầng 1: Thanh tiện ích/)
  assert.doesNotMatch(header, /Yêu cầu khảo sát/)
  assert.match(header, /Nhận tư vấn/)
  assert.match(phpHeader, /class="hdr-call"/)
  assert.match(header, /v-if="hotline"[\s\S]*telHrefFor\(hotline\)/)
  assert.match(header, /min-\[821px\]:flex min-\[821px\]:flex-col/)
  assert.match(header, /shellHref\('\/gio-hang\/'\)/)
  assert.match(header, /class="gtranslate_wrapper[^\"]*min-\[1321px\]:flex"/)
  assert.match(header, /<nav class="hidden min-w-0 nav:flex-1 nav:block"/)
  assert.match(header, /nav:w-\[270px\][\s\S]*min-\[1501px\]:w-\[clamp\(360px,22vw,460px\)\]/)
  assert.match(header, /min-\[1321px\]:max-\[1346px\]:w-\[244px\]/)
  assert.match(header, /<ul class="flex items-center justify-center gap-1 nav:gap-0"/)
  assert.match(header, /whitespace-nowrap border-b-2 px-2\.5[^\"]*nav:px-2 wd:px-3/)
  assert.doesNotMatch(header, /ptsInlineSearchInput|quickCategories|wd:hidden/)
  assert.match(app, /cdn\.gtranslate\.net\/widgets\/latest\/popup\.js/)
  assert.match(app, /'zh-CN', 'ko', 'ja'/)
  assert.doesNotMatch(mainCss, /\.gtranslate_wrapper\s*\{[^}]*display\s*:/)
})

test('storefront stays in light mode while the unfinished theme toggle is withheld', () => {
  assert.match(nuxtConfig, /colorMode:\s*\{[\s\S]*preference:\s*'light'[\s\S]*fallback:\s*'light'[\s\S]*storage:\s*'localStorage'/)
  assert.match(nuxtConfig, /storageKey:\s*'pts-color-mode-light-only'/)
  assert.doesNotMatch(header, /useColorMode|Bật giao diện tối|Bật giao diện sáng|toggleColorMode/)
  assert.match(tokensCss, /html\.dark \.text-navy-950[\s\S]*color: #e2e8f0/)
  assert.match(tokensCss, /html\.dark \.bg-white \.text-navy-800[\s\S]*color: #0a2251/)
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

test('header dialogs isolate the page background and restore prior inert states on close', () => {
  assert.match(header, /const inertBeforeModal = new Map<HTMLElement, boolean>\(\)/)
  assert.match(header, /function syncModalBackground\(\)[\s\S]*document\.body\.contains\(overlay\)[\s\S]*sibling\.inert = true/)
  assert.match(header, /function restoreModalBackground\(\)[\s\S]*element\.inert = wasInert[\s\S]*inertBeforeModal\.clear\(\)/)
  assert.match(header, /ref="searchOverlay"/)
  assert.match(header, /ref="catalogOverlay"/)
  assert.match(header, /ref="mobileOverlay"/)
  assert.doesNotMatch(header, /:inert="mobileOpen/)
})

test('desktop nav parent links stay navigable and submenu disclosure is an independent touch target', () => {
  assert.match(header, /<NuxtLink[\s\S]*:to="shellHref\(item\.href\)"[\s\S]*\{\{ item\.label \}\}[\s\S]*<\/NuxtLink>\s*<button[\s\S]*v-if="item\.children\?\.length"/)
  assert.match(header, /:aria-expanded="navDropdownOpen === item\.href"/)
  assert.match(header, /:aria-controls="navDropdownOpen === item\.href \? `ptsNavMenu-\$\{index\}` : undefined"/)
  assert.match(header, /:aria-label="`\$\{navDropdownOpen === item\.href \? 'Đóng' : 'Mở'\} menu con \$\{item\.label\}`"/)
  assert.match(header, /@click="toggleNavDropdown\(item, \$event\)"/)
  assert.match(header, /function handleNavMouseleave\(\)[\s\S]*navDropdownMode\.value === 'hover'/)
  assert.match(header, /function toggleNavDropdown\(item: PublicSiteLink, event: MouseEvent\)[\s\S]*navDropdownMode\.value === 'hover' \|\| navDropdownTrigger\.value !== trigger/)
})

test('desktop catalog mega-menu mirrors PHP production structure', () => {
  assert.match(header, /Khám phá sản phẩm/)
  assert.match(header, /Chọn thiết bị phù hợp với nhu cầu của bạn/)
  assert.match(header, /id="ptsCatalogKeyword"/)
  assert.match(header, /Tìm sản phẩm, mã sản phẩm\.\.\./)
  assert.match(header, /Cần hỗ trợ lựa chọn\?/)
  assert.match(header, /Tư vấn chọn sản phẩm/)
  assert.match(header, /Xem tất cả sản phẩm/)
  assert.match(header, /catalog-menu-presentation/)
  assert.match(header, /CatalogMenuIcon/)
  assert.doesNotMatch(header, /Dữ liệu danh mục đang được cung cấp từ API public/)
})

test('clicking a hover-open catalog menu pins it instead of immediately closing it', () => {
  assert.match(header, /function toggleCatalog\(\)[\s\S]*if \(!catalogOpen\.value\)[\s\S]*openCatalog\('intent'\)[\s\S]*if \(catalogMode\.value === 'hover'\)[\s\S]*catalogMode\.value = 'intent'[\s\S]*catalogSearchInput\.value\?\.focus\(\)/)
})

test('catalog benefit rail uses consistent stroke icons instead of platform-dependent glyphs', () => {
  const masthead = read('components/catalog/CatalogMasthead.vue')
  assert.equal((masthead.match(/<CatalogMenuIcon/g) || []).length, 4)
  assert.doesNotMatch(masthead, /aria-hidden="true">[✦⚙✓↗]</)
})

test('news and project CTA bands retain the PHP wide-banner hierarchy on desktop and mobile', () => {
  const newsCta = read('components/news/NewsCta.vue')
  const projectsCta = read('components/projects/ProjectsCta.vue')
  assert.match(newsCta, /grid-cols-\[44px_minmax\(0,1fr\)\][\s\S]*nav:grid-cols-\[52px_minmax\(0,1fr\)_auto\]/)
  assert.match(newsCta, /absolute inset-0 -z-20 h-full w-full object-cover/)
  assert.match(newsCta, /phoneLabel[\s\S]*cta\.hotline\.text[\s\S]*cta\.buttonLabel/)
  assert.match(projectsCta, /absolute inset-0 -z-20 h-full w-full object-cover/)
  assert.match(projectsCta, /cta\.eyebrow[\s\S]*cta\.title[\s\S]*cta\.description[\s\S]*trustItems/)
  assert.match(projectsCta, /cta\.hotline\.label[\s\S]*cta\.buttonLabel/)
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
