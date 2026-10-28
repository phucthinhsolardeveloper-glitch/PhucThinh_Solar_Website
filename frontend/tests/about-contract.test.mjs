import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { isPublicAboutPayload } from '../shared/contracts/public-about.ts'

const text = { title: 'CMS title', description: 'CMS description', image: '' }
const good = {
  schemaVersion: 1, locale: 'vi', path: '/gioi-thieu/', title: 'Giới thiệu Phúc Thịnh Solar',
  story: { eyebrow: 'Hành trình phát triển', title: 'Giới thiệu Phúc Thịnh Solar', intro: 'Nội dung CMS', body: '', image: '/upload/news/story.jpg' },
  history: { title: 'Lịch sử', image: '', items: [{ year: '2020', title: 'Mốc thật', description: 'Mô tả CMS' }] },
  people: { eyebrow: 'Con người', title: 'Nhân sự', intro: 'Giới thiệu', body: '', image: '', principles: [text] },
  trust: { title: 'Vì sao', items: [text] }, compass: { title: 'Tầm nhìn', items: [] },
  certificates: { title: 'Chứng nhận', intro: 'Đang công bố', items: [{ id: 1, name: 'Chứng nhận CMS', description: '', image: '/upload/photo/cert.jpg', href: '/upload/photo/cert.jpg' }] },
  cta: { title: 'Tư vấn', description: 'Mô tả', image: '', href: null, buttonLabel: 'Nhận tư vấn ngay', hotline: null },
  labels: { story: 'Về Phúc Thịnh Solar', history: 'Lịch sử phát triển', people: 'Con người', compass: 'Tầm nhìn và sứ mệnh', certificates: 'Năng lực và chứng nhận', timeline: 'Các mốc', certLabel: 'Chứng nhận', certAll: 'Xem tất cả', certDetail: 'Chi tiết' },
}

test('accepts the read-only About DTO with nullable CMS sections', () => {
  assert.equal(isPublicAboutPayload(good), true)
  assert.equal(isPublicAboutPayload({ ...good, story: { ...good.story, image: '' }, certificates: { ...good.certificates, items: [] } }), true)
})

test('rejects invented routes, unsafe media and non-year timeline values', () => {
  assert.equal(isPublicAboutPayload({ ...good, path: '/gioi-thieu/demo/' }), false)
  assert.equal(isPublicAboutPayload({ ...good, story: { ...good.story, image: '/upload/news/../../secret.jpg' } }), false)
  assert.equal(isPublicAboutPayload({ ...good, history: { ...good.history, items: [{ ...good.history.items[0], year: '2026-01-01' }] } }), false)
})

test('accepts escaped plain text because the adapter never renders CMS HTML', () => {
  assert.equal(isPublicAboutPayload({ ...good, story: { ...good.story, intro: '<b>escaped by PHP adapter</b>' } }), true)
})

test('does not render an empty certificate image frame when CMS media is absent', async () => {
  const source = await readFile(new URL('../app/components/about/AboutPreviewPage.vue', import.meta.url), 'utf8')
  const certificates = source.split('id="about-certificates"')[1]?.split('</section>')[0] ?? ''
  assert.match(certificates, /<a v-if="item\.image"/)
  assert.match(certificates, /:src="item\.image"/)
})
