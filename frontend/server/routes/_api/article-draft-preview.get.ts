import { isPublicNewsDetailPayload } from '../../../shared/contracts/public-news-detail'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => !['revision', 'expires', 'signature'].includes(key))
    || typeof query.revision !== 'string' || !/^[1-9]\d{0,18}$/.test(query.revision)
    || typeof query.expires !== 'string' || !/^\d{10}$/.test(query.expires)
    || typeof query.signature !== 'string' || !/^[a-f0-9]{64}$/.test(query.signature)) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid preview link' })
  }

  const payload = await fetchBackendPayload(event, {
    endpoint: `/api/v1/article-draft-preview/${query.revision}`,
    query: { expires: query.expires, signature: query.signature },
    validate: isPublicNewsDetailPayload,
    invalidMessage: 'Invalid article preview data',
    unavailableMessage: 'Article preview unavailable',
    passThroughStatuses: [403, 404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  setHeader(event, 'X-Robots-Tag', 'noindex, nofollow, noarchive')
  setHeader(event, 'Referrer-Policy', 'no-referrer')
  return payload
})
