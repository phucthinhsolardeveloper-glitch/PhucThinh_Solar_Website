import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isPublicNewsPayload } from '../shared/contracts/public-news.ts'

const category = { id: 4, name: 'Kiến thức điện mặt trời', description: 'Mô tả nhóm', slug: 'kien-thuc-dien-mat-troi', url: '/kien-thuc-dien-mat-troi/', canonicalPath: '/kien-thuc-dien-mat-troi', image: '' }
const article = { id: 8, title: 'Bài viết CMS', slug: 'bai-viet-cms', url: '/bai-viet-cms/', description: 'Mô tả đã làm sạch', date: '2026-10-26', image: '/upload/news/article.webp', readingMinutes: 2, category, featured: false }
const good = { schemaVersion: 1, locale: 'vi', path: '/tin-tuc/', title: 'Kiến thức', description: 'Giới thiệu CMS', category: null, categories: [category], featured: [{ ...article, featured: true }], articles: [article], pagination: { page: 1, perPage: 6, total: 7 }, intro: { title: 'Kiến thức', description: 'Giới thiệu CMS' }, cta: null, labels: { all: 'Tất cả', search: 'Tìm kiếm bài viết', featured: 'Nổi bật', results: 'Bài viết', empty: 'Trống', reset: 'Xem tất cả', read: 'phút đọc', pages: 'Trang', readMore: 'Đọc tiếp', ctaPhone: 'Gọi' } }

test('accepts the PHP news DTO and nullable CMS CTA', () => assert.equal(isPublicNewsPayload(good), true))
test('rejects unsafe canonical paths and raw HTML', () => {
  assert.equal(isPublicNewsPayload({ ...good, articles: [{ ...article, url: 'https://example.test/news' }] }), false)
  assert.equal(isPublicNewsPayload({ ...good, articles: [{ ...article, title: '<script>x</script>' }] }), false)
  assert.equal(isPublicNewsPayload({ ...good, articles: [{ ...article, image: '/upload/news/../../secret.jpg' }] }), false)
})
test('rejects wrong legacy page size and malformed visibility DTO', () => {
  assert.equal(isPublicNewsPayload({ ...good, pagination: { ...good.pagination, perPage: 12 } }), false)
  assert.equal(isPublicNewsPayload({ ...good, featured: [{ ...article, featured: false }] }), true)
  assert.equal(isPublicNewsPayload({ ...good, articles: [{ ...article, readingMinutes: -1 }] }), false)
})

test('secondary news media stays within its grid column at intermediate widths', () => {
  const card = readFileSync(new URL('../app/components/news/NewsCard.vue', import.meta.url), 'utf8')
  assert.match(card, /grid grid-cols-\[minmax\(0,38%\)_minmax\(0,1fr\)\] gap-4/)
  assert.match(card, /aspect-video h-auto min-h-0 w-full min-w-0 self-start rounded-chip/)
  assert.doesNotMatch(card, /aspect-video h-full min-h-\[150px\]/)
})

test('accepts resolved multi-level category metadata only when its leaf slug matches', () => {
  const selected = { ...category, slug: 'ung-dung', url: '/ung-dung/', canonicalPath: '/ung-dung', type: 'tin-tuc', level: 'item', path: 'kien-thuc/dien-mat-troi/ung-dung' }
  assert.equal(isPublicNewsPayload({ ...good, category: selected }), true)
  assert.equal(isPublicNewsPayload({ ...good, category: { ...selected, path: 'kien-thuc/dien-mat-troi/khac' } }), false)
  assert.equal(isPublicNewsPayload({ ...good, category: { ...selected, type: 'du-an' } }), false)
})

test('news Admin SEO uses #_seopage at the hub and depth-specific #_seo at taxonomy routes', () => {
  const api = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/api/pts_v1_news.php', import.meta.url), 'utf8')
  const page = readFileSync(new URL('../app/pages/migration-news-preview.vue', import.meta.url), 'utf8')
  const categoryPage = readFileSync(new URL('../app/components/news/NewsCategoryPage.vue', import.meta.url), 'utf8')
  assert.match(api, /#_seo where id_parent = \? and com = \? and act = \? and type = \? limit 1', array\(\(int\)\$selectedCategory\['id'\], 'news', \$seoActions\[\$categoryType\], 'tin-tuc'\)/)
  assert.match(api, /'list' => 'man_list'.*'cat' => 'man_cat'.*'item' => 'man_item'.*'sub' => 'man_sub'/s)
  assert.match(api, /#_seopage where type=\? limit 0,1', array\('tin-tuc'\)/)
  assert.match(api, /select titlevi,descriptionvi,keywordsvi,photo from #_seopage/)
  assert.match(page, /news\.value\?\.seo\?\.title \|\| news\.value\?\.title/)
  assert.match(page, /news\.value\?\.seo\?\.image \|\| undefined/)
  assert.match(categoryPage, /category\.value\?\.seo\?\.title \|\| category\.value\?\.name/)
  assert.match(categoryPage, /category\.value\?\.seo\?\.image \|\| category\.value\?\.image/)
  assert.equal(isPublicNewsPayload({ ...good, seo: { title: 'Admin title', description: 'Admin description', keywords: 'solar', image: '/upload/seopage/news.webp' } }), true)
  assert.equal(isPublicNewsPayload({ ...good, seo: { title: '', description: '', image: '/upload/news/category.webp' } }), false)
  assert.equal(isPublicNewsPayload({ ...good, category: { ...category, seo: { title: 'Category title', description: '', image: '/upload/news/category.webp' } } }), true)
})
