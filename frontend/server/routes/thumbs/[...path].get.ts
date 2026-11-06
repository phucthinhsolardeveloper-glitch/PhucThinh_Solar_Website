import { createHash } from 'node:crypto'
import { fetchLegacyPreviewMedia } from '../../utils/legacy-preview-media'
import { readLocalLegacyAsset, renderPreviewThumb } from '../../utils/legacy-preview-thumb'

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig(event)
  const pathname = `/thumbs/${getRouterParam(event, 'path') ?? ''}`
  const media = await fetchLegacyPreviewMedia(pathname, config) ?? await readLocalLegacyAsset(pathname, config)
  if (!media) throw createError({ statusCode: 404, statusMessage: 'Preview media not found' })
  setHeader(event, 'Cache-Control', 'private, no-cache, must-revalidate')
  const thumb = await renderPreviewThumb(pathname, media.body)
  const body = thumb?.body ?? new Uint8Array(media.body)
  // Uploads are mutable so they revalidate, but an unchanged image answers 304: the
  // browser keeps its decoded copy and it does not flash between pages.
  const etag = `"${createHash('sha1').update(body).digest('base64url')}"`
  setHeader(event, 'ETag', etag)
  if (getRequestHeader(event, 'if-none-match') === etag) {
    setResponseStatus(event, 304)
    return null
  }
  setHeader(event, 'Content-Type', thumb?.contentType ?? media.contentType)
  return body
})
