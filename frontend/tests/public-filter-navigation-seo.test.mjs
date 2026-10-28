import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (file) => readFileSync(new URL(file, import.meta.url), 'utf8')

test('news category links use preview paths in migration and canonical paths otherwise', () => {
  const source = read('../app/components/news/NewsFilters.vue')
  assert.match(source, /useMigrationLinks\(\)/)
  assert.match(source, /isMigrationPreview\.value \? '\/migration-news-preview\/' : '\/tin-tuc\/'/)
  assert.match(source, /:href="`\$\{filtersPath\}\$\{queryString\}`"/)
  assert.match(source, /category=\$\{encodeURIComponent\(category\.slug\)\}/)
})

test('project category links remain inside preview and are public-ready', () => {
  const source = read('../app/components/projects/ProjectFilters.vue')
  assert.match(source, /useMigrationLinks\(\)/)
  assert.match(source, /isMigrationPreview\.value \? '\/migration-projects-preview\/' : '\/du-an-da-thuc-hien\/'/)
  assert.match(source, /:href="filtersPath"/)
  assert.match(source, /category=\$\{encodeURIComponent\(category\.slug\)\}/)
})

test('catalog links stay in Nuxt preview while category pages are being migrated', () => {
  const source = read('../app/components/catalog/CatalogPage.vue')
  assert.match(source, /const links = useMigrationLinks\(\)/)
  assert.match(source, /links\.generic\(activeCategoryRoot\.value\?\.url \?\? '\/san-pham\/'\)/)
  assert.match(source, /:to="links\.generic\(item\.url\)"/)
  assert.match(source, /item\.href \? links\.generic\(item\.href\) : undefined/)
  assert.match(source, /:to="links\.generic\(catalogData\.support\.href \|\| '\/lien-he\/'\)"/)
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
