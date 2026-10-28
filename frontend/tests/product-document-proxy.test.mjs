import { test } from 'node:test'
import assert from 'node:assert/strict'
import { fetchLegacyPreviewDocument, LEGACY_DOCUMENT_MAX_BYTES, validateLegacyDocumentPath } from '../server/utils/legacy-preview-document.ts'

const config = { phpOrigin: 'http://127.0.0.1:8088', previewMediaProxy: true }
const response = (body, headers = { 'content-type': 'application/pdf' }, status = 200) => new Response(body, { status, headers })

test('accepts allowlisted local product documents', async () => {
  assert.deepEqual(validateLegacyDocumentPath('/upload/product-documents/model.pdf'), {
    pathname: '/upload/product-documents/model.pdf',
    contentType: 'application/pdf',
    filename: 'model.pdf',
  })
  const result = await fetchLegacyPreviewDocument('/upload/product-documents/model.pdf', config, async (url, options) => {
    assert.equal(url.toString(), 'http://127.0.0.1:8088/upload/product-documents/model.pdf')
    assert.deepEqual(options, { method: 'GET', redirect: 'manual' })
    return response(new Uint8Array([1, 2, 3]))
  })
  assert.equal(result?.contentType, 'application/pdf')
  assert.equal(result?.filename, 'model.pdf')
  assert.equal(result?.body.byteLength, 3)
})

test('rejects traversal, executable, encoded and remote paths', () => {
  for (const path of [
    '/upload/../secret.pdf',
    '/upload/%2e%2e/secret.pdf',
    '/upload/photo.jpg',
    '/upload/run.php',
    '/upload/http://evil.test/file.pdf',
    'https://docs.example.com/file.pdf',
    '/thumbs/file.pdf',
  ]) assert.equal(validateLegacyDocumentPath(path), null, path)
})

test('accepts octet-stream upstream but rejects mismatched MIME, redirects and oversized files', async () => {
  const octet = await fetchLegacyPreviewDocument('/assets/docs/file.xlsx', config, async () =>
    response(new Uint8Array([1]), { 'content-type': 'application/octet-stream' }))
  assert.equal(octet?.contentType, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')

  const mismatch = await fetchLegacyPreviewDocument('/upload/file.pdf', config, async () =>
    response(new Uint8Array([1]), { 'content-type': 'text/html' }))
  assert.equal(mismatch, null)

  const redirect = await fetchLegacyPreviewDocument('/upload/file.pdf', config, async () =>
    response('', { location: 'https://evil.test', 'content-type': 'application/pdf' }, 302))
  assert.equal(redirect, null)

  const oversized = await fetchLegacyPreviewDocument('/upload/file.pdf', config, async () =>
    response(new Uint8Array(LEGACY_DOCUMENT_MAX_BYTES + 1)))
  assert.equal(oversized, null)
})

test('document proxy requires explicit local preview flag', async () => {
  const result = await fetchLegacyPreviewDocument('/upload/file.pdf', { ...config, previewMediaProxy: false }, async () => response(new Uint8Array([1])))
  assert.equal(result, null)
})
