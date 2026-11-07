import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('TOC scrolling honors reduced motion, transfers keyboard focus, and resumes on keyboard scroll', () => {
  const source = read('../app/composables/useTocScrollspy.ts')
  assert.match(source, /matchMedia\('\(prefers-reduced-motion: reduce\)'\)/)
  assert.match(source, /target\.focus\(\{ preventScroll: true \}\)/)
  assert.match(source, /scrollContainer\.scrollTop/)
  assert.doesNotMatch(source, /activeEl\.scrollIntoView/)
  assert.match(source, /window\.addEventListener\('keydown', onKeyboardScroll\)/)
  assert.match(source, /window\.removeEventListener\('keydown', onKeyboardScroll\)/)
  assert.doesNotMatch(source, /behavior:\s*'smooth'/)
})

test('News and policy TOCs expose active state and use the shared scrollspy', () => {
  const news = read('../app/components/news-detail/NewsDetailArticle.vue')
  const policy = read('../app/components/views/PolicyView.vue')
  for (const source of [news, policy]) {
    assert.match(source, /useTocScrollspy\(/)
    assert.match(source, /data-toc-id="item\.id"/)
    assert.match(source, /aria-current="activeHeadingId === item\.id \? 'true' : undefined"/)
    assert.match(source, /@click\.prevent="scrollToHeading\(item\.id\)"/)
  }
})

test('page wheel input stays native instead of being intercepted for scripted scrolling', () => {
  const app = read('../app/app.vue')
  assert.doesNotMatch(app, /usePageScrollExperience\(/)
  assert.doesNotMatch(app, /addEventListener\(['"]wheel['"],/)
})

test('active horizontal rails keep the selected public filter visible', () => {
  const source = read('../app/composables/useActiveHorizontalRail.ts')
  const about = read('../app/components/about/AboutPage.vue')
  const product = read('../app/components/product-detail/ProductDetailPage.vue')
  const project = read('../app/components/projects/ProjectCategoryPage.vue')
  assert.match(source, /querySelector<HTMLElement>\('\[aria-current="page"\], \[aria-current="location"\]'\)/)
  assert.match(source, /activeRect\.left \+ activeRect\.width \/ 2/)
  assert.match(source, /Math\.abs\(targetLeft - rail\.scrollLeft\) < 1/)
  assert.match(source, /rail\.scrollTo\(\{ left: targetLeft/)
  assert.match(source, /MutationObserver/)
  assert.match(source, /watch\(railRef,[\s\S]*observer\.observe\(rail,[\s\S]*scheduleAlignment\(\)/)
  assert.match(source, /window\.removeEventListener\('resize', scheduleAlignment\)/)
  assert.match(about, /useActiveHorizontalRail\(aboutNavRef\)/)
  assert.match(product, /useActiveHorizontalRail\(productNavRef\)/)
  assert.match(product, /aria-current="activeSection === item\.id \? 'location' : undefined"/)
  assert.match(project, /<ProjectFilters/)
  assert.match(read('../app/components/projects/ProjectFilters.vue'), /useActiveHorizontalRail\(railRef\)/)
})
