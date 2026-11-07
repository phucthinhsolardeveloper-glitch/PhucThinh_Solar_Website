import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { backendMediaUrl } from '../server/utils/media-proxy.ts'

test('maps uploads and thumbnails onto the Laravel origin', () => {
  assert.equal(backendMediaUrl('/upload/product/den-1.jpg', 'http://127.0.0.1:8090'), 'http://127.0.0.1:8090/upload/product/den-1.jpg')
  assert.equal(backendMediaUrl('/thumbs/320x320x1/upload/product/den-1.jpg.webp', 'http://pts-laravel13-local:8000/'), 'http://pts-laravel13-local:8000/thumbs/320x320x1/upload/product/den-1.jpg.webp')
  assert.equal(backendMediaUrl('/upload/video/review-1.mp4', 'https://api.example.test'), 'https://api.example.test/upload/video/review-1.mp4')
})

test('refuses traversal, other prefixes, encoded or odd characters and unsafe origins', () => {
  for (const path of ['/upload/../.env', '/upload//etc', '/assets/images/x.png', '/api/v1/site', '/upload/a%2e%2e/b', '/upload/a b.png', '/upload/a?x=1', 'upload/a.png']) {
    assert.equal(backendMediaUrl(path, 'http://127.0.0.1:8090'), null, path)
  }
  for (const origin of ['javascript:alert(1)', 'http://user:pass@127.0.0.1:8090', 'http://127.0.0.1:8090/base', 'not a url']) {
    assert.equal(backendMediaUrl('/upload/a.png', origin), null, origin)
  }
})

test('streams with byte ranges and never forwards visitor cookies', () => {
  const source = readFileSync(new URL('../server/utils/media-proxy.ts', import.meta.url), 'utf8')
  assert.match(source, /sendProxy\(event, target, \{ headers,/)
  assert.match(source, /'range', 'if-none-match', 'if-modified-since'/)
  assert.doesNotMatch(source, /proxyRequest|getRequestHeaders|'cookie'/)
})
