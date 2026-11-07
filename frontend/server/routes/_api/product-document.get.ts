import { fetchDocument } from '../../utils/media-document'
import { requireMigrationGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'path')) throw createError({ statusCode: 400, statusMessage: 'Invalid document query' })
  const path = query.path
  if (typeof path !== 'string') throw createError({ statusCode: 400, statusMessage: 'Invalid document path' })

  const config = useRuntimeConfig(event)
  const document = await fetchDocument(path, config)
  if (!document) throw createError({ statusCode: 404, statusMessage: 'Document not found' })

  setHeader(event, 'Content-Type', document.contentType)
  setHeader(event, 'Content-Disposition', `attachment; filename="${document.filename.replace(/["\\]/g, '_')}"`)
  setHeader(event, 'Cache-Control', 'private, no-store')
  return new Uint8Array(document.body)
})
