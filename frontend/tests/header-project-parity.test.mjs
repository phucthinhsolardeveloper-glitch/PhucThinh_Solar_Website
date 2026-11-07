import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'

const root = new URL('../app/', import.meta.url)
const read = (path) => readFileSync(new URL(path, root), 'utf8')

test('desktop catalog keeps the gold trigger and closes hover after leaving trigger and panel', () => {
  const header = read('components/SiteHeader.vue')
  assert.match(header, /border-hdr-gold bg-hdr-gold/)
  assert.match(header, /function scheduleCatalogClose\(\)[\s\S]*catalogMode\.value !== 'hover'[\s\S]*180\)/)
  assert.match(header, /@mouseleave="scheduleCatalogClose"/)
  assert.match(header, /@mouseenter="cancelCatalogClose"/)
  assert.match(header, /function handleShellLeave\(\)[\s\S]*scheduleCatalogClose\(\)/)
  assert.match(header, /class="absolute inset-0 h-full w-full cursor-default border-0 bg-transparent"/)
  assert.match(header, /<Transition name="pts-catalog-menu">/)
  assert.match(header, /min-\[1200px\]:grid-cols-\[minmax\(0,1fr\)_minmax\(280px,400px\)_36px\]/)
  assert.match(header, /min-\[1200px\]:w-\[min\(1220px,calc\(100vw-64px\)\)\]/)
})

test('mobile sidebar follows the PHP product-first order and narrower drawer size', () => {
  const header = read('components/SiteHeader.vue')
  const sidebarStart = header.indexOf('aria-label="Điều hướng mobile"')
  const sidebar = header.slice(header.lastIndexOf('<nav', sidebarStart))
  assert.match(header, /mobileNavOrder = \['\/giai-phap\/', '\/du-an-da-thuc-hien\/', '\/gioi-thieu\/', '\/tin-tuc\/', '\/dai-ly\/'\]/)
  assert.match(header, /mobileCatalogOpen\.value = true/)
  assert.match(header, /w-\[min\(326px,calc\(100vw-24px\)\)\][\s\S]*min-\[761px\]:w-\[360px\]/)
  assert.match(header, /<h2 id="ptsMobileTitle" class="sr-only">\{\{ brandName \}\}<\/h2>[\s\S]*pts-drawer__logo/)
  assert.match(header, /props\.site\.productCategoryTree\?\.length[\s\S]*flattenProductCategories\(tree\)/)
  assert.match(header, /<Teleport to="body">[\s\S]*<div v-if="mobileOpen" ref="mobileOverlay"/)
  assert.match(sidebar, /v-show="mobileCategoryVisible\(entry\)"/)
  assert.match(sidebar, /:aria-expanded="Boolean\(mobileCategoryExpanded\[entry\.key\]\)"/)
  assert.match(header, /mt-3 flex min-h-\[50px\] w-full flex-none/)
  assert.ok(sidebar.indexOf('aria-controls="ptsMobileCatalog"') < sidebar.indexOf('v-for="(item, index) in mobileNavItems"'))
  assert.match(sidebar, /Xem tất cả sản phẩm/)
})

test('PHP news-category aliases redirect only confirmed categories to their Nuxt canonical path', () => {
  const alias = read('pages/category/[slug].vue')
  assert.match(alias, /_migration\/public-route-resolve/)
  assert.match(alias, /resolution\.kind === 'news-category'/)
  assert.match(alias, /navigateTo\(\{ path: resolution\.canonicalPath, query: route\.query \}, \{ redirectCode: 301 \}\)/)
  assert.match(alias, /setResponseStatus\(validSlug \? statusCode : 404/)
})

test('empty project category offers the same contact action as PHP without inventing enterprise projects', () => {
  const category = read('components/projects/ProjectCategoryPage.vue')
  assert.match(category, /v-if="!projects\.projects\.length"[\s\S]*role="status"/)
  assert.match(category, /projects\.labels\.empty/)
  assert.match(category, /openConsultation\(`Dự án \$\{categoryTitle\}`\)/)
  assert.match(category, /projects\.labels\.cta/)
})
