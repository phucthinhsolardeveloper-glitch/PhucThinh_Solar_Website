import { isPublicProductDetailPayload } from '../../../shared/contracts/public-product-detail'
import { productPreviewQuery } from '../../../shared/product-preview'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

// Product page as saved in Admin, hidden or not, from a signed link. Never cached or indexed.
export default defineEventHandler(async (event) => {
  requireGet(event)
  const query = getQuery(event)
  const preview = Object.keys(query).every((key) => ['product', 'expires', 'signature'].includes(key)) ? productPreviewQuery(query.product, query) : null
  if (!preview) throw createError({ statusCode: 400, statusMessage: 'Invalid preview link' })

  const payload = await fetchBackendPayload(event, {
    endpoint: `/api/v1/product-preview/${preview.product}`,
    query: { expires: preview.expires, signature: preview.signature },
    validate: isPublicProductDetailPayload,
    invalidMessage: 'Invalid product preview data',
    unavailableMessage: 'Product preview unavailable',
    passThroughStatuses: [403, 404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  setHeader(event, 'X-Robots-Tag', 'noindex, nofollow, noarchive')
  setHeader(event, 'Referrer-Policy', 'no-referrer')
  return payload
})
