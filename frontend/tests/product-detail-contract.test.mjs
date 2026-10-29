import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isProductSlug, isPublicProductDetailPayload } from '../shared/contracts/public-product-detail.ts'

const good = {
  schemaVersion: 1,
  locale: 'vi',
  breadcrumbs: [
    { label: 'Sản phẩm', url: '/san-pham/' },
    { label: 'Tấm pin năng lượng mặt trời', url: '/danh-muc-san-pham/tam-pin/' },
    { label: 'Tấm pin dân dụng', url: '/danh-muc-san-pham/tam-pin/tam-pin-dan-dung/' },
  ],
  product: {
    id: 7,
    name: 'Đèn thật',
    slug: 'den-that',
    url: '/san-pham/den-that',
    code: 'PT-7',
    summary: '',
    profile: 'lighting',
    media: ['/upload/product/real.webp'],
    specifications: [{ key: 'cong_suat', label: 'Công suất', value: '30W' }],
    packageContents: 'Đèn liền thể · Tấm pin · Remote',
    decisionMetrics: [
      { key: 'quang_thong', label: 'Quang thông', value: '3000', unit: 'lm', icon: 'sun', iconImage: 'quanthong.png', iconDir: 'pdp-lighting' },
      { key: 'battery_capacity', label: 'Pin lưu trữ', value: '120', unit: 'Wh', icon: 'battery', iconImage: 'pin.png', iconDir: 'pdp-lighting' },
      { key: 'installation_height', label: 'Chiều cao lắp', value: '', unit: 'm', icon: 'height', iconImage: 'chieucaoLapdat.png', iconDir: 'pdp-lighting' },
      { key: 'warranty', label: 'Bảo hành', value: '24', unit: 'tháng', icon: 'shield', iconImage: 'baohanh.png', iconDir: 'pdp-lighting' },
    ],
  },
  pricing: { mode: 'price', currency: 'VND', regularPrice: 2648100, salePrice: null },
  content: {
    rendered: true,
    source: 'product',
    highlights: ['Công suất 30W', 'Nguồn sáng LED 3000lm'],
    blocks: [
      { type: 'heading', text: 'Mô tả sản phẩm' },
      { type: 'text', text: 'Nội dung\nđược giữ theo block.' },
      { type: 'list', ordered: false, items: ['Mục 1', 'Mục 2'] },
      { type: 'image', image: '/upload/product/block.webp', caption: 'Ảnh thật' },
      { type: 'media', image: '', title: 'Ứng dụng', text: 'Mô tả ứng dụng', side: 'left' },
    ],
    faqs: [{ question: 'Dùng ở đâu?', answer: 'Theo nhu cầu thực tế.' }],
  },
  applications: {
    title: 'Phù hợp với nhu cầu của bạn',
    items: [{ title: 'Sân vườn', text: 'Sử dụng tại sân vườn và lối đi.', image: '' }],
  },
  buyingGuides: [{ key: 'light_source', label: 'Nguồn sáng', value: 'LED' }],
  documents: [
    { type: 'datasheet', title: 'Thông số kỹ thuật', url: '/upload/product-documents/den-that.pdf' },
    { type: 'manual', title: 'Hướng dẫn lắp đặt', url: 'https://docs.example.com/den-that.docx' },
  ],
  canonical: { url: '/san-pham/den-that', sources: { product: 'publicProductEntityPath(product)' } },
  seo: { title: 'Đèn thật | Phúc Thịnh Solar', description: 'Mô tả SEO', keywords: 'đèn solar' },
  parity: { quote: { included: false, reason: 'legacy_quote_flow_stays_php' } },
}

test('validates product detail DTO', () => assert.equal(isPublicProductDetailPayload(good), true))
test('requires sanitized Admin SEO fields and a product-matching canonical path', () => {
  assert.equal(isPublicProductDetailPayload({ ...good, seo: { ...good.seo, description: '<script>x</script>' } }), false)
  assert.equal(isPublicProductDetailPayload({ ...good, seo: undefined }), false)
  assert.equal(isPublicProductDetailPayload({ ...good, canonical: { ...good.canonical, url: '/other/' } }), false)
})
test('PDP SSR emits PHP SEO metadata and canonical while retaining its indexability gate', () => {
  const page = readFileSync(new URL('../app/pages/san-pham/[slug].vue', import.meta.url), 'utf8')
  assert.ok(page.includes('payload.value?.seo.title'))
  assert.match(page, /description: productDescription/)
  assert.ok(page.includes("name: 'keywords', content: productKeywords.value"))
  assert.ok(page.includes('payload.value?.canonical.url || route.path'))
  assert.ok(page.includes('robots: computed(() => indexable.value && !!product.value'))
})
test('validates source-backed PDP hero metrics and package contents', () => {
  assert.equal(isPublicProductDetailPayload(good), true)
  assert.equal(isPublicProductDetailPayload({ ...good, product: { ...good.product, packageContents: '' } }), true)
  assert.equal(isPublicProductDetailPayload({ ...good, product: { ...good.product, decisionMetrics: [{ ...good.product.decisionMetrics[0], iconDir: '../private' }] } }), false)
  assert.equal(isPublicProductDetailPayload({ ...good, product: { ...good.product, decisionMetrics: [{ ...good.product.decisionMetrics[0], iconImage: '../private.png' }] } }), false)
})
test('accepts only internal breadcrumb paths backed by the product category hierarchy', () => {
  assert.equal(isPublicProductDetailPayload(good), true)
  assert.equal(isPublicProductDetailPayload({ ...good, breadcrumbs: [{ label: 'Tấm pin', url: 'https://example.com/' }] }), false)
  assert.equal(isPublicProductDetailPayload({ ...good, breadcrumbs: [{ label: 'Tấm pin', url: '/danh-muc-san-pham/../private/' }] }), false)
  assert.equal(isPublicProductDetailPayload({ ...good, breadcrumbs: Array(6).fill(good.breadcrumbs[0]) }), false)
})
test('validates exact slug and rejects traversal/media leakage', () => {
  assert.equal(isProductSlug('den-that'), true)
  assert.equal(isProductSlug('Den That'), false)
  assert.equal(isPublicProductDetailPayload({ ...good, product: { ...good.product, media: ['/upload/product/../secret.jpg'] } }), false)
})
test('rejects a product canonical URL that carries a trailing slash (PHP router has none for /san-pham/{slug})', () => {
  assert.equal(isPublicProductDetailPayload({ ...good, product: { ...good.product, url: '/san-pham/den-that/' } }), false)
  assert.equal(isPublicProductDetailPayload({ ...good, product: { ...good.product, url: '/san-pham/other' } }), false)
})
test('accepts quote-only pricing without inventing a price, rejects a price on a quote', () => {
  const quote = { mode: 'quote', currency: 'VND', regularPrice: null, salePrice: null }
  assert.equal(isPublicProductDetailPayload({ ...good, pricing: quote }), true)
  assert.equal(isPublicProductDetailPayload({ ...good, pricing: { ...quote, regularPrice: 1 } }), false)
})
test('rejects malformed or invented pricing shapes', () => {
  assert.equal(isPublicProductDetailPayload({ ...good, pricing: { included: true, reason: 'x', price: 1 } }), false)
  assert.equal(isPublicProductDetailPayload({ ...good, pricing: { ...good.pricing, currency: 'USD' } }), false)
})

test('accepts structured PDP blocks and rejects unsafe or category-only block shapes', () => {
  assert.equal(isPublicProductDetailPayload(good), true)
  assert.equal(isPublicProductDetailPayload({
    ...good,
    content: { ...good.content, blocks: [{ type: 'image', image: '/upload/product/../private.jpg', caption: '' }] },
  }), false)
  assert.equal(isPublicProductDetailPayload({
    ...good,
    content: { ...good.content, blocks: [{ type: 'cards', title: 'Không thuộc PDP', items: [] }] },
  }), false)
  assert.equal(isPublicProductDetailPayload({
    ...good,
    content: { ...good.content, blocks: [{ type: 'media', image: '', title: '', text: '', side: 'left' }] },
  }), false)
})

test('PDP mirrors PHP highlights and collapsed long description', () => {
  const api = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/api/pts_v1_product_detail.php', import.meta.url), 'utf8')
  const phpTemplate = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/templates/product/product_detail_tpl.php', import.meta.url), 'utf8')
  const content = readFileSync(new URL('../app/components/product-preview/ProductPreviewContent.vue', import.meta.url), 'utf8')
  assert.match(api, /\$content\['highlights'\]\s*=\s*productAutoHighlights\(/)
  assert.match(phpTemplate, /pts-pdp-highlights/)
  assert.match(phpTemplate, /<details class="pts-pdp-more-description"><summary>Xem mô tả chi tiết/)
  assert.match(content, /content\.highlights\?\.length[\s\S]*Điểm nổi bật/)
  assert.match(content, /<details v-if="content\.blocks\.length"[\s\S]*Xem mô tả chi tiết/)
})

test('validates inherited content source and FAQ limits', () => {
  assert.equal(isPublicProductDetailPayload({ ...good, content: { ...good.content, source: 'brand' } }), true)
  assert.equal(isPublicProductDetailPayload({ ...good, content: { ...good.content, source: 'private' } }), false)
  assert.equal(isPublicProductDetailPayload({ ...good, content: { ...good.content, faqs: Array(31).fill(good.content.faqs[0]) } }), false)
  assert.equal(isPublicProductDetailPayload({ ...good, content: { ...good.content, highlights: Array(8).fill('Thông số thật') } }), false)
})

test('validates CMS-backed application cards and buying guides', () => {
  assert.equal(isPublicProductDetailPayload(good), true)
  assert.equal(isPublicProductDetailPayload({ ...good, applications: null }), true)
  assert.equal(isPublicProductDetailPayload({
    ...good,
    applications: { ...good.applications, items: [{ ...good.applications.items[0], image: '/upload/product/../private.jpg' }] },
  }), false)
  assert.equal(isPublicProductDetailPayload({ ...good, buyingGuides: [{ key: 'private', label: 'Thông tin', value: '' }] }), false)
  assert.equal(isPublicProductDetailPayload({ ...good, buyingGuides: Array(4).fill(good.buyingGuides[0]) }), false)
})

test('accepts public product documents and rejects unsafe document paths', () => {
  assert.equal(isPublicProductDetailPayload(good), true)
  for (const url of ['/upload/../secret.pdf', '/upload/file.exe', 'http://docs.example.com/file.pdf', 'https://user:pass@docs.example.com/file.pdf']) {
    assert.equal(isPublicProductDetailPayload({ ...good, documents: [{ type: 'manual', title: 'Tài liệu', url }] }), false, url)
  }
  assert.equal(isPublicProductDetailPayload({ ...good, documents: [{ type: 'private', title: 'Tài liệu', url: '/upload/file.pdf' }] }), false)
  assert.equal(isPublicProductDetailPayload({ ...good, documents: Array(31).fill(good.documents[0]) }), false)
})
