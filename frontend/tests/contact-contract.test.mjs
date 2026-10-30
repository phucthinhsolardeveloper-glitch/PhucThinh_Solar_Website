import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isPublicContactPayload } from '../shared/contracts/public-contact.ts'
const good = { schemaVersion: 1, locale: 'vi', path: '/lien-he/', seo: { title: 'Liên hệ', description: '' }, title: 'Liên hệ Phúc Thịnh Solar', intro: 'Nội dung CMS', visual: { name: 'Tư vấn', description: '', href: '#yeu-cau-tu-van', image: '/upload/photo/contact.jpg' }, contact: { company: 'Phúc Thịnh Solar', hotline: '', email: '', address: '', workingHours: '' }, locations: [{ id: 1, name: 'Trụ sở', address: 'Địa chỉ CMS', mapUrl: 'https://www.google.com/maps/embed?pb=fixture' }], labels: { locations: 'Hệ thống chi nhánh', formCta: 'Liên hệ qua form PHP', formNote: 'Biểu mẫu vẫn ở PHP.' } }
test('accepts the read-only contact DTO', () => assert.equal(isPublicContactPayload(good), true))
test('accepts optional Admin keywords and social image, and rejects unsafe SEO media', () => {
  const seo = { ...good.seo, keywords: 'contact solar', image: '/upload/seopage/contact.jpg' }
  assert.equal(isPublicContactPayload({ ...good, seo }), true)
  assert.equal(isPublicContactPayload({ ...good, seo: { ...seo, image: '/upload/seopage/../private.jpg' } }), false)
  assert.equal(isPublicContactPayload(good), true)
})
test('contact page prefers Admin metadata and SEO image while retaining visual fallback', () => {
  const source = readFileSync(new URL('../app/components/contact/ContactPageContent.vue', import.meta.url), 'utf8')
  assert.match(source, /contact\.value\?\.seo\.keywords/)
  assert.match(source, /contact\.value\?\.seo\.image\s*\|\|\s*contact\.value\?\.visual\?\.image/)
})
test('rejects unsafe media and map URLs', () => { assert.equal(isPublicContactPayload({ ...good, visual: { ...good.visual, image: '/upload/photo/../secret.jpg' } }), false); assert.equal(isPublicContactPayload({ ...good, locations: [{ ...good.locations[0], mapUrl: 'https://evil.example/maps' }] }), false) })
test('allows empty CMS collections without inventing contact data', () => assert.equal(isPublicContactPayload({ ...good, visual: null, locations: [], contact: { company: '', hotline: '', email: '', address: '', workingHours: '' } }), true))
