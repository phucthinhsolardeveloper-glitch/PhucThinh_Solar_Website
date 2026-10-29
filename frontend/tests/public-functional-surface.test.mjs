import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

const root = resolve(new URL('..', import.meta.url).pathname)
const read = (file) => readFileSync(resolve(root, file), 'utf8')

test('public gallery and video keep the real empty-state copy and interactive entry points', () => {
  const gallery = read('app/pages/thu-vien-anh/index.vue')
  const galleryDetail = read('app/pages/[slug].vue')
  const video = read('app/pages/video/index.vue')
  const videoPreview = read('app/pages/migration-video-preview.vue')
  assert.match(gallery, /Không tìm thấy kết quả/)
  assert.match(video, /Không tìm thấy kết quả/)
  assert.match(galleryDetail, /openGalleryImage/)
  assert.match(video, /@click="open\(/)
  assert.match(videoPreview, /@click="open\(/)
})

test('public listing/detail surfaces retain the PHP-equivalent CTA and related-content hooks', () => {
  const projects = read('app/pages/du-an-da-thuc-hien/index.vue')
  const news = read('app/pages/tin-tuc/index.vue')
  const slug = read('app/pages/[slug].vue')
  assert.match(projects, /ProjectsCta/)
  assert.match(news, /NewsCta/)
  assert.match(slug, /NewsDetailRelated/)
  assert.match(slug, /NewsDetailArticle/)
  assert.match(read('app/components/news-detail/NewsDetailArticle.vue'), /EditorialShare/)
  assert.match(slug, /ProjectsCta/)
  assert.match(slug, /ProjectDetailPage/)
})

test('contact form keeps native required validation for the public lead flow', () => {
  const form = read('app/components/contact/ContactLeadForm.vue')
  assert.match(form, /required/)
  assert.match(form, /@submit\.prevent/)
})

test('catalog listing exposes a stable canonical URL for SEO', () => {
  const catalog = read('app/pages/san-pham/index.vue')
  assert.match(catalog, /new URL\('\/san-pham\/', requestUrl\.origin\)/)
  assert.match(catalog, /rel: 'canonical'/)
  assert.match(catalog, /description: 'Khám phá thiết bị chiếu sáng/)
})

test('home SEO keeps a non-empty description when CMS metadata is blank', () => {
  const home = read('app/pages/index.vue')
  assert.match(home, /description: \(\) => home\.value\?\.seo\.description \|\| '[^']+'/)
})

test('catalog and news image surfaces declare intrinsic dimensions for stable layout', () => {
  assert.match(read('app/components/catalog/CatalogProductGrid.vue'), /width="640"[\s\S]*height="640"[\s\S]*sizes=/)
  assert.match(read('app/components/catalog/CatalogPage.vue'), /categoryHero\.image[\s\S]*width="1600"[\s\S]*height="500"/)
  const newsCard = read('app/components/news/NewsCard.vue')
  assert.match(newsCard, /width="760"[\s\S]*height="428"[\s\S]*sizes=/)
  assert.match(newsCard, /thumbs\/760x428x1\$\{src\}/)
})
