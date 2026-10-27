import { fetchLegacyPreviewDocument } from '../../utils/legacy-preview-document'
import { requireMigrationGet } from '../../utils/migration-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const path = getQuery(event).path
  if (typeof path !== 'string') throw createError({ statusCode: 400, statusMessage: 'Invalid document path' })

  const config = useRuntimeConfig(event)
  const document = await fetchLegacyPreviewDocument(path, config)
  if (!document) throw createError({ statusCode: 404, statusMessage: 'Preview document not found' })

  setHeader(event, 'Content-Type', document.contentType)
  setHeader(event, 'Content-Disposition', `attachment; filename="${document.filename.replace(/["\\]/g, '_')}"`)
  setHeader(event, 'Cache-Control', 'private, max-age=300')
  return new Uint8Array(document.body)
})
