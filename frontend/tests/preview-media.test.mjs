import { test } from 'node:test'
import assert from 'node:assert/strict'
import { fetchLegacyPreviewMedia, LEGACY_MEDIA_MAX_BYTES, validateLegacyMediaPath } from '../server/utils/legacy-preview-media.ts'

const config = { phpOrigin: 'http://127.0.0.1:8088', previewMediaProxy: true }
const response = (body, headers = { 'content-type': 'image/png' }, status = 200) => new Response(body, { status, headers })

test('accepts an allowlisted image path and preserves safe MIME', async () => {
  assert.deepEqual(validateLegacyMediaPath('/upload/photo/logo.png'), { pathname: '/upload/photo/logo.png', contentType: 'image/png' })
  const result = await fetchLegacyPreviewMedia('/upload/photo/logo.png', config, async (url, options) => {
    assert.equal(url.toString(), 'http://127.0.0.1:8088/upload/photo/logo.png')
    assert.deepEqual(options, { method: 'GET', redirect: 'manual' })
    return response(new Uint8Array([1, 2, 3]))
  })
  assert.equal(result?.contentType, 'image/png')
  assert.equal(result?.body.byteLength, 3)
})

test('rejects traversal and executable paths', () => {
  for (const path of ['/upload/../secret.png', '/upload/%2e%2e/secret.png', '/assets/run.php', '/thumbs/icon.svg', '/upload/http://evil.test/x.png']) {
    assert.equal(validateLegacyMediaPath(path), null, path)
  }
})

test('rejects redirects and oversized responses', async () => {
  const redirect = await fetchLegacyPreviewMedia('/upload/photo/logo.png', config, async () => response('', { location: 'http://evil.test', 'content-type': 'image/png' }, 302))
  assert.equal(redirect, null)
  const oversized = await fetchLegacyPreviewMedia('/upload/photo/logo.png', config, async () => response(new Uint8Array(LEGACY_MEDIA_MAX_BYTES + 1)))
  assert.equal(oversized, null)
})

test('is disabled unless the explicit local preview flag is enabled', async () => {
  const result = await fetchLegacyPreviewMedia('/upload/photo/logo.png', { ...config, previewMediaProxy: false }, async () => response(new Uint8Array([1])))
  assert.equal(result, null)
})
