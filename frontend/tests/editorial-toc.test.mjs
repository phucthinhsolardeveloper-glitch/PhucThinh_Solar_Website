import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { buildEditorialToc, buildEditorialTocForFragments } from '../shared/editorial-toc.ts'

const content = {
  format: 'sanitized-html',
  html: '<h2>Điện mặt trời doanh nghiệp</h2><h3>Giới thiệu &amp; phạm vi</h3><h3>Giới thiệu &amp; phạm vi</h3><h4 id="detail-heading">Ngoài mục lục cũ</h4><h2 id="custom-id">Tiêu đề tùy chỉnh</h2>',
  sanitizedBy: 'pts_public_richtext',
  sanitizerVersion: 1,
}

test('builds legacy TOC entries and matching Vietnamese anchors while preserving CMS IDs', () => {
  const result = buildEditorialToc(content)
  assert.deepEqual(result.toc, [
    { id: 'dien-mat-troi-doanh-nghiep', label: 'Điện mặt trời doanh nghiệp', level: 2 },
    { id: 'gioi-thieu-pham-vi', label: 'Giới thiệu & phạm vi', level: 3 },
    { id: 'gioi-thieu-pham-vi_1', label: 'Giới thiệu & phạm vi', level: 3 },
    { id: 'custom-id', label: 'Tiêu đề tùy chỉnh', level: 2 },
  ])
  assert.match(result.content.html, /<h3 id="gioi-thieu-pham-vi_1">/)
  assert.match(result.content.html, /<h4 id="detail-heading">Ngoài mục lục cũ<\/h4>/)
  assert.equal(result.content.sanitizedBy, content.sanitizedBy)
  assert.equal(result.content.sanitizerVersion, content.sanitizerVersion)
})

test('leaves empty and non-TOC rich text untouched', () => {
  const empty = { ...content, html: '<p>Chưa có heading</p><h4>Tiêu đề sâu</h4>' }
  assert.deepEqual(buildEditorialToc(empty), { content: empty, toc: [] })
})

test('builds matching SSR markup and TOC IDs across both solution content fragments', () => {
  const first = {
    ...content,
    html: '<h2></h2><h2>Giới thiệu dự án</h2><h3 id="legacy-heading">Dữ liệu CMS</h3><h1>Tiêu đề ngoài TOC</h1>',
  }
  const second = {
    ...content,
    html: '<h2>Giới thiệu dự án</h2><h3>Hạng mục thi công</h3>',
  }
  const result = buildEditorialTocForFragments([first, second], {
    levels: [2, 3],
    idPrefix: 'solution-section',
  })

  assert.deepEqual(result.toc, [
    { id: 'solution-section-gioi-thieu-du-an-2', label: 'Giới thiệu dự án', level: 2 },
    { id: 'legacy-heading', label: 'Dữ liệu CMS', level: 3 },
    { id: 'solution-section-gioi-thieu-du-an-4', label: 'Giới thiệu dự án', level: 2 },
    { id: 'solution-section-hang-muc-thi-cong-5', label: 'Hạng mục thi công', level: 3 },
  ])
  assert.match(result.contents[0].html, /<h2><\/h2><h2 id="solution-section-gioi-thieu-du-an-2">Giới thiệu dự án<\/h2>/)
  assert.match(result.contents[0].html, /<h3 id="legacy-heading">Dữ liệu CMS<\/h3>/)
  assert.match(result.contents[0].html, /<h1>Tiêu đề ngoài TOC<\/h1>/)
  assert.match(result.contents[1].html, /<h2 id="solution-section-gioi-thieu-du-an-4">Giới thiệu dự án<\/h2>/)
  assert.match(result.contents[1].html, /<h3 id="solution-section-hang-muc-thi-cong-5">Hạng mục thi công<\/h3>/)
})

test('solution detail renders the shared processed fragments and TOC without waiting for hydration', () => {
  const page = readFileSync(new URL('../app/components/solution-detail/SolutionDetailPage.vue', import.meta.url), 'utf8')
  assert.match(page, /const editorial = computed\(\(\) => buildEditorialTocForFragments\(/)
  assert.match(page, /<EditorialRichText :content="solutionContent" \/>/)
  assert.match(page, /<EditorialRichText class="mt-8" :content="solutionExtraContent" \/>/)
  assert.match(page, /:href="`#\$\{item\.id\}`"/)
  assert.doesNotMatch(page, /onMounted\(/)
})
