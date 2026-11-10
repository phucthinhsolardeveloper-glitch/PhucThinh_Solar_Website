import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicSitePayload } from '../shared/contracts/public-site.ts'

const footerRichText = { format: 'sanitized-html', html: '<p><strong>Phúc Thịnh Solar</strong></p><p>Địa chỉ<br>Email</p>', sanitizedBy: 'pts_public_richtext', sanitizerVersion: 1 }

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

test('accepts only the PHP-projected public Zalo contact URL', () => {
  assert.equal(isPublicSitePayload({ ...good, brand: { ...good.brand, zaloHref: 'https://zalo.me/0944555957' } }), true)
  assert.equal(isPublicSitePayload({ ...good, brand: { ...good.brand, zaloHref: 'https://evil.example/0944555957' } }), false)
  assert.equal(isPublicSitePayload({ ...good, brand: { ...good.brand, zaloHref: 'https://zalo.me/not-a-number' } }), false)
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
      companyName: 'Thông tin doanh nghiệp', contactText: footerRichText,
      policies: [{ label: 'Chính sách', href: '/ho-tro/chinh-sach/' }],
      locations: [{ name: 'Trụ sở', address: 'Gia Lai', mapUrl: 'https://maps.example.test/' }],
      socialLinks: [{ label: 'Facebook', href: 'https://facebook.com/example', kind: 'facebook' }],
      trustBadge: { image: '/upload/photo/bct.webp', href: 'https://online.gov.vn/Home/WebDetails/123' },
    },
  }), true)
})

test('rejects unsafe URLs in optional CMS fields', () => {
  assert.equal(isPublicSitePayload({ ...good, brand: { ...good.brand, logo: 'javascript:alert(1)' } }), false)
  assert.equal(isPublicSitePayload({ ...good, footer: { companyName: '', contactText: '', policies: [], locations: [], socialLinks: [], trustBadge: { image: '/upload/photo/bct.webp', href: 'javascript:alert(1)' } } }), false)
  // An unknown network would render no icon at all, so the payload is rejected before it reaches the footer.
  assert.equal(isPublicSitePayload({ ...good, footer: { companyName: '', contactText: footerRichText, policies: [], locations: [], socialLinks: [{ label: 'Mạng mới', href: 'https://example.com', kind: 'myspace' }] } }), false)
  assert.equal(isPublicSitePayload({ ...good, navigation: [{ label: 'X', href: '//evil.example/' }] }), false)
  assert.equal(isPublicSitePayload({ ...good, footer: { companyName: '', contactText: '', policies: [], locations: [{ name: 'X', address: 'Y', mapUrl: 'data:text/html,x' }], socialLinks: [] } }), false)
})

test('footer requires the PHP-sanitized rich-text envelope so CMS paragraphs are preserved safely', () => {
  const footer = { companyName: 'Thông tin doanh nghiệp', contactText: footerRichText, policies: [], locations: [], socialLinks: [] }
  assert.equal(isPublicSitePayload({ ...good, footer }), true)
  assert.equal(isPublicSitePayload({ ...good, footer: { ...footer, contactText: '<p>Raw CMS</p>' } }), false)
  assert.equal(isPublicSitePayload({ ...good, footer: { ...footer, contactText: { ...footerRichText, html: '<script>x</script>' } } }), false)
})

test('rejects malformed optional structures instead of partially rendering them', () => {
  assert.equal(isPublicSitePayload({ ...good, navigationSchemaVersion: 2 }), false)
  assert.equal(isPublicSitePayload({ ...good, catalog: { lighting: { label: 'Chiếu sáng', href: '', items: [{ label: 'X', href: '/x/', children: 'bad' }] } } }), false)
})

test('Admin favicon is optional and must be a safe URL', () => {
  assert.equal(isPublicSitePayload({ ...good, brand: { ...good.brand, favicon: '/upload/photo/icon.png' } }), true)
  assert.equal(isPublicSitePayload({ ...good, brand: { ...good.brand, favicon: 'javascript:alert(1)' } }), false)
})

test('Admin catalog benefits and quick searches are bounded so they cannot break the layout', () => {
  assert.equal(isPublicSitePayload({ ...good, catalogBenefits: [{ title: 'Tư vấn', description: '' }], quickSearches: ['Đèn đường'] }), true)
  assert.equal(isPublicSitePayload({ ...good, catalogBenefits: Array(5).fill({ title: 'A', description: '' }) }), false)
  assert.equal(isPublicSitePayload({ ...good, quickSearches: Array(8).fill('Pin') }), false)
  assert.equal(isPublicSitePayload({ ...good, quickSearches: [''] }), false)
})
