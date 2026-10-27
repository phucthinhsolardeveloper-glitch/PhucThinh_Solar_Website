import { isPublicCartPayload } from '../../../shared/contracts/public-cart'
import { fetchPhpSessionResponse } from '../../utils/migration-cart'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }

  let response: Response
  try {
    response = await fetchPhpSessionResponse(event, '/api/pts_v1_cart.php', { method: 'GET' })
  } catch {
    throw createError({ statusCode: 502, statusMessage: 'PHP cart data source unavailable' })
  }

  if (!response.ok) throw createError({ statusCode: 502, statusMessage: 'PHP cart data source unavailable' })
  const payload: unknown = await response.json().catch(() => null)
  if (!isPublicCartPayload(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid PHP cart data contract' })
  return payload
})
