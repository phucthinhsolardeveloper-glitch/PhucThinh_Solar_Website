import { fetchLegacyPreviewMedia } from '../../utils/legacy-preview-media'
import { readLocalLegacyAsset } from '../../utils/legacy-preview-thumb'

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig(event)
  const pathname = `/assets/${getRouterParam(event, 'path') ?? ''}`
  const media = await fetchLegacyPreviewMedia(pathname, config) ?? await readLocalLegacyAsset(pathname, config)
  if (!media) throw createError({ statusCode: 404, statusMessage: 'Preview media not found' })
  setHeader(event, 'Content-Type', media.contentType)
  setHeader(event, 'Cache-Control', 'private, max-age=300')
  return new Uint8Array(media.body)
})
