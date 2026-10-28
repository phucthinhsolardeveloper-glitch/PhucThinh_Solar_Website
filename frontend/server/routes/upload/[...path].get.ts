import { fetchLegacyPreviewMedia } from '../../utils/legacy-preview-media'

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig(event)
  const media = await fetchLegacyPreviewMedia(`/upload/${getRouterParam(event, 'path') ?? ''}`, config)
  if (!media) throw createError({ statusCode: 404, statusMessage: 'Preview media not found' })
  setHeader(event, 'Content-Type', media.contentType)
  setHeader(event, 'Cache-Control', 'private, max-age=300')
  return new Uint8Array(media.body)
})
