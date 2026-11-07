import { isPublicCartPayload } from '../../../shared/contracts/public-cart'
import { fetchSessionResponse } from '../../utils/backend-session'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }

  let response: Response
  try {
    response = await fetchSessionResponse(event, '/api/v1/cart', { method: 'GET' })
  } catch {
    throw createError({ statusCode: 502, statusMessage: 'Cart data source unavailable' })
  }

  if (!response.ok) throw createError({ statusCode: 502, statusMessage: 'Cart data source unavailable' })
  const payload: unknown = await response.json().catch(() => null)
  if (!isPublicCartPayload(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid cart data contract' })
  return payload
})
