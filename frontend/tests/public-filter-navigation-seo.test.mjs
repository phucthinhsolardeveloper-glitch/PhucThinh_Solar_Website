import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (file) => readFileSync(new URL(file, import.meta.url), 'utf8')

test('news category links use canonical paths and keep the search text', () => {
  const source = read('../app/components/news/NewsFilters.vue')
  assert.match(source, /const filtersPath = '\/tin-tuc\/'/)
  assert.match(source, /:to="`\$\{filtersPath\}\$\{queryString\}`"/)
  assert.match(source, /<NuxtLink[\s\S]*:to="categoryHref\(category\)"/)
  assert.match(source, /return `\$\{category\.canonicalPath\}\$\{queryString\.value\}`/)
})

test('project category links use the canonical category URL', () => {
  const source = read('../app/components/projects/ProjectFilters.vue')
  assert.match(source, /const filtersPath = '\/du-an-da-thuc-hien\/'/)
  assert.match(source, /:to="filtersPath"/)
  assert.match(source, /<NuxtLink[\s\S]*:to="categoryHref\(category\)"/)
})

test('interactive filters keep the page mounted and avoid global route-scroll animation', () => {
  const styles = read('../app/assets/css/main.css')
  const catalog = read('../app/components/catalog/CatalogPage.vue')
  const search = read('../app/components/catalog/SearchResultsPage.vue')
  const header = read('../app/components/SiteHeader.vue')
  const scrollLock = read('../app/composables/useDocumentScrollLock.ts')

  assert.match(styles, /html\s*\{\s*scroll-behavior:\s*auto;/)
  // Reactive key: Nuxt carries the previous key's data into the new entry, so
  // the grid stays mounted while route/query changes refetch.
  assert.match(catalog, /const catalogDataKey = computed\(\(\) => `catalog:\$\{route\.path\}:\$\{JSON\.stringify\(query\.value\)\}`\)/)
  assert.match(catalog, /useAsyncData<PublicCatalogPayload>\(\s*catalogDataKey,/)
  assert.match(catalog, /v-if="catalogPending && !catalogData"/)
  assert.match(catalog, /:aria-busy="catalogPending"/)
  assert.match(search, /useAsyncData<PublicSearchPayload>\(\s*'search'/)
  assert.match(search, /pending && !data/)
  assert.match(header, /setDocumentScrollLock\(overlayOpen\)/)
  assert.match(scrollLock, /window\.innerWidth - document\.documentElement\.clientWidth/)
  assert.match(scrollLock, /document\.body\.style\.paddingRight = `\$\{currentPadding \+ scrollbarWidth\}px`/)
  assert.match(scrollLock, /owners\.value\.length === 0/)
})

test('header parent navigation remains active on bare category slug routes', () => {
  const header = read('../app/components/SiteHeader.vue')

  assert.match(header, /const bareSlugAlias = currentSegments\.length === 1 && currentLeaf === normalizedLinkPath\.split\('\/'\)\.filter\(Boolean\)\.at\(-1\)/)
  assert.match(header, /return matchesBranch\(item\.children \?\? \[\]\)/)
  assert.match(header, /:aria-current="isActiveNavItem\(item\) \? 'page' : undefined"/)
})

test('catalog links stay in Nuxt preview while category pages are being migrated', () => {
  const source = read('../app/components/catalog/CatalogPage.vue')
  assert.match(source, /const links = usePublicLinks\(\)/)
  // "Tất cả" points at the selected category (or its parent when it has no children) through links.generic.
  assert.match(source, /const categoryAllHref = computed\(\(\) => links\.generic\([^\n]*\?\? '\/san-pham\/'\)\)/)
  assert.match(source, /:to="links\.generic\(item\.url\)"/)
  assert.match(source, /item\.href \? links\.generic\(item\.href\) : undefined/)
  assert.match(source, /:fallback-href="catalogData\.support\.href \|\| '\/lien-he\/'"/)
  assert.match(read('../app/components/PublicCtaBanner.vue'), /:href="links\.generic\(fallbackHref\)"/)
})

test('related project links use the Nuxt detail preview route', () => {
  const source = read('../app/components/project-detail/ProjectDetailPage.vue')
  assert.match(source, /:href="links\.generic\('\/du-an-da-thuc-hien\/'\)"/)
  assert.match(source, /:href="links\.project\(item\.slug\)"/)
})

test('cart and order confirmation cannot be indexed even when public routes are enabled', () => {
  for (const file of ['../app/pages/gio-hang/index.vue', '../app/pages/cam-on/index.vue']) {
    const source = read(file)
    assert.match(source, /robots: 'noindex, nofollow, noarchive'/)
    assert.doesNotMatch(source, /Indexable|index, follow/)
  }
})
