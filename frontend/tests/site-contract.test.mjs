import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicSitePayload } from '../shared/contracts/public-site.ts'

const good = {
  schemaVersion: 1,
  brand: { name: 'Phúc Thịnh Solar', hotline: '' },
  productCategories: [{ id: 4, name: 'Đèn', slug: 'den', url: '/danh-muc-san-pham/den/' }],
}

test('accepts published public site payload', () => {
  assert.equal(isPublicSitePayload(good), true)
})

test('rejects absent category identifiers', () => {
  assert.equal(isPublicSitePayload({ ...good, productCategories: [{ ...good.productCategories[0], id: 0 }] }), false)
})

test('rejects malformed API payload', () => {
  assert.equal(isPublicSitePayload({ schemaVersion: 1, brand: {}, productCategories: [] }), false)
})

test('keeps the old v1 payload valid when CMS extensions are absent', () => {
  assert.equal(isPublicSitePayload(good), true)
})

test('accepts the published navigation and footer projection', () => {
  assert.equal(isPublicSitePayload({
    ...good,
    navigationSchemaVersion: 1,
    brand: { ...good.brand, technicalHotline: '0945 536 899', logo: '/upload/photo/logo.webp', logoFooter: '/upload/photo/logo-footer.webp' },
    navigation: [{ label: 'Giới thiệu', href: '/gioi-thieu/', children: [{ label: 'Liên hệ', href: '/lien-he/#yeu-cau-tu-van' }] }],
    catalog: { lighting: { label: 'Chiếu sáng', href: '/danh-muc-san-pham/den-nlmt/', items: [{ label: 'Đèn đường', href: '/danh-muc-san-pham/den-duong/', children: [] }] } },
    productCategoryTree: [{ id: 4, name: 'Đèn', slug: 'den', url: '/danh-muc-san-pham/den/', children: [] }],
    footer: {
      companyName: 'Thông tin doanh nghiệp', contactText: 'Trụ sở: Gia Lai',
      legalName: 'CÔNG TY TNHH CÔNG NGHỆ - THƯƠNG MẠI VÀ DỊCH VỤ PHÚC THỊNH SOLAR',
      contactLines: ['Trụ sở: Gia Lai'],
      policies: [{ label: 'Chính sách', href: '/ho-tro/chinh-sach/' }],
      locations: [{ name: 'Trụ sở', address: 'Gia Lai', mapUrl: 'https://maps.example.test/' }],
      socialLinks: [{ label: 'Facebook', href: 'https://facebook.com/example' }],
      trustBadge: { image: '/upload/photo/bct.webp', href: 'https://online.gov.vn/Home/WebDetails/123' },
    },
  }), true)
})

test('rejects unsafe URLs in optional CMS fields', () => {
  assert.equal(isPublicSitePayload({ ...good, brand: { ...good.brand, logo: 'javascript:alert(1)' } }), false)
  assert.equal(isPublicSitePayload({ ...good, footer: { companyName: '', contactText: '', policies: [], locations: [], socialLinks: [], trustBadge: { image: '/upload/photo/bct.webp', href: 'javascript:alert(1)' } } }), false)
  assert.equal(isPublicSitePayload({ ...good, navigation: [{ label: 'X', href: '//evil.example/' }] }), false)
  assert.equal(isPublicSitePayload({ ...good, footer: { companyName: '', contactText: '', policies: [], locations: [{ name: 'X', address: 'Y', mapUrl: 'data:text/html,x' }], socialLinks: [] } }), false)
})

test('rejects malformed optional structures instead of partially rendering them', () => {
  assert.equal(isPublicSitePayload({ ...good, navigationSchemaVersion: 2 }), false)
  assert.equal(isPublicSitePayload({ ...good, catalog: { lighting: { label: 'Chiếu sáng', href: '', items: [{ label: 'X', href: '/x/', children: 'bad' }] } } }), false)
  assert.equal(isPublicSitePayload({ ...good, footer: { companyName: '', contactText: '', contactLines: 'Trụ sở', policies: [], locations: [], socialLinks: [] } }), false)
})
