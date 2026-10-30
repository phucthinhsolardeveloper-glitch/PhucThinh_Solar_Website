import { isPublicProductExtrasPayload } from '../../../shared/contracts/public-product-extras'
import { fetchPhpSessionResponse } from '../../utils/migration-cart'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  const query = getQuery(event)
  if (Object.keys(query).some((key) => !['slug', 'reviewOffset', 'replyParent', 'replyOffset'].includes(key))) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid product extras query' })
  }
  const slug = query.slug
  if (typeof slug !== 'string' || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid product slug' })
  }
  const reviewOffset = query.reviewOffset
  if (reviewOffset !== undefined && (typeof reviewOffset !== 'string' || !/^(?:0|[1-9][0-9]{0,5})$/.test(reviewOffset))) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid review offset' })
  }
  const replyParent = query.replyParent
  const replyOffset = query.replyOffset
  if ((replyParent === undefined) !== (replyOffset === undefined)
    || replyParent !== undefined && (typeof replyParent !== 'string' || !/^[1-9][0-9]{0,9}$/.test(replyParent))
    || replyOffset !== undefined && (typeof replyOffset !== 'string' || !/^(?:0|[1-9][0-9]{0,5})$/.test(replyOffset))
    || reviewOffset !== undefined && replyParent !== undefined) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid review reply page' })
  }

  const phpQuery = new URLSearchParams({ slug })
  if (typeof reviewOffset === 'string') phpQuery.set('reviewOffset', reviewOffset)
  if (typeof replyParent === 'string' && typeof replyOffset === 'string') {
    phpQuery.set('replyParent', replyParent)
    phpQuery.set('replyOffset', replyOffset)
  }

  let response: Response
  try {
    response = await fetchPhpSessionResponse(event, '/api/pts_v1_product_extras.php?' + phpQuery.toString(), { method: 'GET' })
  } catch {
    throw createError({ statusCode: 502, statusMessage: 'PHP product extras unavailable' })
  }
  if (!response.ok) {
    throw createError({ statusCode: response.status === 404 ? 404 : 502, statusMessage: 'PHP product extras unavailable' })
  }
  const payload: unknown = await response.json().catch(() => null)
  if (!isPublicProductExtrasPayload(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid PHP product extras contract' })
  return payload
})
