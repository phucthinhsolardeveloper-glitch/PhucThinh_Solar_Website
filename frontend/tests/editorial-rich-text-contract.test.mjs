import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicSanitizedRichText } from '../shared/contracts/editorial-rich-text.ts'

const good = {
  format: 'sanitized-html',
  html: '<h2>Tiêu đề</h2><p>Nội dung <strong>an toàn</strong>.</p><figure><img src="/upload/news/photo.jpg" alt="Ảnh"><figcaption>Chú thích</figcaption></figure>',
  sanitizedBy: 'pts_public_richtext',
  sanitizerVersion: 1,
}

test('accepts only the explicit PHP-sanitized rich-text envelope', () => {
  assert.equal(isPublicSanitizedRichText(good), true)
  assert.equal(isPublicSanitizedRichText({ ...good, html: '' }), true)
})

test('rejects raw CKEditor and executable markup', () => {
  assert.equal(isPublicSanitizedRichText({ html: good.html }), false)
  for (const html of [
    '<script>alert(1)</script>',
    '<p onclick="alert(1)">x</p>',
    '<a href="javascript:alert(1)">x</a>',
    '<iframe src="https://evil.example/embed"></iframe>',
    '<svg onload="alert(1)"></svg>',
  ]) assert.equal(isPublicSanitizedRichText({ ...good, html }), false, html)
})

test('rejects malformed sanitizer metadata and oversized content', () => {
  assert.equal(isPublicSanitizedRichText({ ...good, sanitizedBy: 'ckeditor' }), false)
  assert.equal(isPublicSanitizedRichText({ ...good, sanitizerVersion: 2 }), false)
  assert.equal(isPublicSanitizedRichText({ ...good, html: 'x'.repeat(512_001) }), false)
})
