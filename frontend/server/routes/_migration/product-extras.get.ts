import { isPublicProductExtrasPayload } from '../../../shared/contracts/public-product-extras'
import { fetchPhpSessionResponse } from '../../utils/migration-cart'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug')) throw createError({ statusCode: 400, statusMessage: 'Invalid product extras query' })
  const slug = query.slug
  if (typeof slug !== 'string' || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid product slug' })
  }

  let response: Response
  try {
    response = await fetchPhpSessionResponse(event, '/api/pts_v1_product_extras.php?slug=' + encodeURIComponent(slug), { method: 'GET' })
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
