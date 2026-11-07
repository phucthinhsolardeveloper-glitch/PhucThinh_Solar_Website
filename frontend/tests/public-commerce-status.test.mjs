import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const app = new URL('../app/', import.meta.url)
const read = (path) => readFileSync(new URL(path, app), 'utf8')

test('public category returns 404 only when validated taxonomy lacks the requested category', () => {
  const page = read('components/catalog/CatalogPage.vue')
  const route = read('pages/danh-muc-san-pham/[...slug].vue')

  assert.match(page, /isPublicSitePayload\(payload\)[\s\S]*statusCode: 502/)
  assert.match(page, /if \(import\.meta\.server && props\.categoryPath && siteData\.value && !selectedCategory\.value\) \{\s*setResponseStatus\(404, 'Not Found'\)/)
  assert.match(route, /<CatalogPage :category-path="categoryPath" \/>/)
  assert.doesNotMatch(page, /catalogError\.value[\s\S]*setResponseStatus\(404/)
})

test('public brand detail preserves API 404 and reports upstream errors as 502', () => {
  const page = read('components/catalog/BrandDetailPage.vue')
  assert.match(page, /const \{ generic: publicHref \} = usePublicLinks\(\)/)
  assert.match(page, /const brandErrorStatus = \(error\.value as \{ statusCode\?: unknown \} \| null\)\?\.statusCode/)
  assert.match(page, /if \(import\.meta\.server && \(error\.value \|\| !data\.value\)\) \{\s*const status = brandErrorStatus === 404 \? 404 : 502\s*setResponseStatus\(status, status === 404 \? 'Not Found' : 'Bad Gateway'\)/)
})

test('public Home, Contact, and Dealer return 502 for failed or invalid CMS payloads', () => {
  const cases = [
    ['components/home/HomePageContent.vue', 'home.value'],
    ['components/contact/ContactPageContent.vue', 'contact.value'],
    ['components/dealer/DealerPageContent.vue', 'dealer.value'],
  ]
  for (const [path, content] of cases) {
    const component = read(path)
    assert.match(component, new RegExp(`import\\.meta\\.server && \\(siteError\\.value \\|\\| !isPublicSitePayload\\(siteData\\.value\\) \\|\\| error\\.value \\|\\| !${content.replace('.', '\\.')}\\)`))
    assert.match(component, /setResponseStatus\(502, 'Bad Gateway'\)/)
  }
})
