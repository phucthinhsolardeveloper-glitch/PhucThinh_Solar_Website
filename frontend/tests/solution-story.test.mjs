import { test } from 'node:test'
import assert from 'node:assert/strict'
import { parseSolutionStory } from '../app/utils/solutionStory.ts'

const html = `<h2 id="a">XÁC ĐỊNH NHU CẦU</h2>
<h3 id="a1">Phương án bắt đầu từ nhu cầu thực tế</h3><p>Mở đầu.</p>
<h3 id="a2">Giảm chi phí</h3><p>Mô tả.</p><p><strong>Giải pháp phù hợp:</strong> Điện mặt trời mái nhà.</p>
<h2 id="b">Khảo sát</h2><p>Cần xem xét:</p><ul><li>Hướng mái.</li><li>Che bóng.</li></ul><blockquote><strong>Câu trích.</strong></blockquote>
<h2 id="c">Quy trình</h2><h3 id="c1">01. Tiếp nhận</h3><p>x</p><h3 id="c2">02. Khảo sát &amp; đo</h3><p>y</p><h3 id="c3">03. Thiết kế</h3><p>z</p><h3 id="c4">04. Thi công</h3><p>w</p>
<h2 id="d">Chọn cách hệ thống hoạt động</h2><h3 id="d1">1. Hòa lưới</h3><p>Mô tả.</p><p><strong>Phù hợp khi:</strong></p><ul><li>Dùng ban ngày.</li><li>Chưa cần lưu trữ.</li></ul><h3 id="d2">2. Có pin</h3><p>Mô tả.</p>
<h2 id="e">Quy trình dự án</h2><ol><li>Khảo sát.</li><li>Thi công.</li></ol>
<h2 id="f">FAQ</h2><h3 id="f1">Có dùng khi mất điện?</h3><p>Tùy cấu hình.</p>`

test('solution story keeps every heading id, the first h3 of a chapter and classifies layouts', () => {
  const chapters = parseSolutionStory(html)
  assert.deepEqual(chapters.map((c) => [c.id, c.kind]), [['a', 'match'], ['b', 'checklist'], ['c', 'process'], ['d', 'options'], ['e', 'process'], ['f', 'faq']])
  assert.equal(chapters[0].title, 'Xác định nhu cầu')
  assert.deepEqual(chapters[0].items.map((i) => i.id), ['a1', 'a2'])
  assert.deepEqual(chapters[0].items[1].fit, { label: 'Giải pháp phù hợp', text: 'Điện mặt trời mái nhà.', list: [] })
  assert.deepEqual(chapters[1].list, ['Hướng mái', 'Che bóng'])
  assert.equal(chapters[1].quote, 'Câu trích.')
  assert.equal(chapters[2].items.length, 4)
  assert.deepEqual(chapters[3].items[0].fit, { label: 'Phù hợp khi', text: '', list: ['Dùng ban ngày', 'Chưa cần lưu trữ'] })
  assert.deepEqual(chapters[4].list, ['Khảo sát', 'Thi công'])
  assert.equal(chapters[4].ordered, true)
  assert.equal(chapters[2].items[1].title, '02. Khảo sát & đo')
})

test('solution story returns no chapters for content without h2 so the page falls back to rich text', () => {
  assert.deepEqual(parseSolutionStory('<p>Chỉ có đoạn văn.</p>'), [])
})
