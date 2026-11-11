import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

const root = resolve(new URL('..', import.meta.url).pathname)
const read = (file) => readFileSync(resolve(root, file), 'utf8')

/*
 * Các route public (thu-vien-anh, video, [slug], du-an-da-thuc-hien, tin-tuc,...)
 * là wrapper mỏng import thẳng component preview/migration đã có đủ logic -
 * không nhân bản code (xem comment trong từng page). Vì vậy test phải theo
 * đúng import đó để kiểm tra nội dung thật, không grep mù trên wrapper rỗng.
 */
test('public gallery and video keep the real empty-state copy and interactive entry points', () => {
  const galleryPreview = read('app/components/views/GalleryView.vue')
  const galleryDetailPreview = read('app/components/views/GalleryDetailView.vue')
  const video = read('app/components/views/VideoView.vue')
  assert.match(galleryPreview, /Không tìm thấy kết quả|Chưa có/)
  assert.match(video, /Không tìm thấy kết quả|Chưa có/)
  assert.match(galleryDetailPreview, /@click="open\(/)
  assert.match(video, /@click="open\(/)
})

test('public listing/detail surfaces retain the PHP-equivalent CTA and related-content hooks', () => {
  const projectsPreview = read('app/components/views/ProjectsView.vue')
  const newsPreview = read('app/components/views/NewsView.vue')
  const newsDetailPreview = read('app/components/views/NewsDetailView.vue')
  const projectDetailPreview = read('app/components/views/ProjectDetailView.vue')
  assert.match(projectsPreview, /ProjectsCta/)
  assert.match(newsPreview, /NewsCta/)
  assert.match(newsDetailPreview, /NewsDetailRelated/)
  assert.match(newsDetailPreview, /NewsDetailArticle/)
  assert.match(read('app/components/news-detail/NewsDetailArticle.vue'), /EditorialShare/)
  assert.match(projectDetailPreview, /ProjectDetailPage/)
})

test('contact form requires name and phone natively; details are optional with a readable fallback', () => {
  const form = read('app/components/contact/ContactLeadForm.vue')
  assert.match(form, /const form = reactive\(\{ fullname: '', phone: '', email: '', subject: '', content: '' \}\)/)
  assert.match(form, /submit\('contact', \{ \.\.\.form, content: form\.content \|\|/)
  assert.match(form, /@submit\.prevent/)
  assert.match(form, /<UFormField label="Họ và tên" name="fullname" required[\s\S]*<UInput v-model\.trim="form\.fullname" name="fullname" required maxlength="100" autocomplete="name"/)
  assert.match(form, /<UFormField label="Số điện thoại" name="phone" required[\s\S]*<UInput v-model\.trim="form\.phone" name="phone" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel"/)
  assert.match(form, /<UFormField label="Email" name="email"[\s\S]*<UInput v-model\.trim="form\.email" name="email" type="email" maxlength="150" autocomplete="email"/)
  assert.match(form, /(?:<UFormField label="Nhu cầu quan tâm" name="subject"[\s\S]*<USelect v-model="form\.subject" name="subject"[\s\S]*:items="subjectOptions"|v-for="option in subjectOptions"[\s\S]*@click="toggleSubject\(option\)")/)
  assert.match(form, /<UFormField label="Chi tiết nhu cầu tư vấn" name="content"[\s\S]*<UTextarea v-model\.trim="form\.content" name="content" maxlength="5000"/)
  assert.match(form, /focus-visible:ring-2 focus-visible:ring-gold-500\/30/)
  assert.match(form, /<UButton type="submit"[\s\S]*bg-gold-500[\s\S]*text-navy-950/)
})

test('catalog listing exposes a stable canonical URL for SEO', () => {
  const catalog = read('app/components/catalog/CatalogPage.vue')
  assert.match(catalog, /usePublicSocialMeta/)
  assert.match(catalog, /canonicalPath: metadataCanonicalPath/)
  assert.match(catalog, /catalogSeoDescription\(/)
})

test('home SEO keeps a non-empty description when CMS metadata is blank', () => {
  const home = read('app/components/home/HomePageContent.vue')
  assert.match(home, /description: \(\) => home\.value\?\.seo\.description \|\| fallbackDescription/)
  assert.match(home, /const fallbackDescription = '[^']+'/)
})

// Intrinsic-dimension/thumbnail-resize coverage lives in responsive-image-contract.test.mjs.
