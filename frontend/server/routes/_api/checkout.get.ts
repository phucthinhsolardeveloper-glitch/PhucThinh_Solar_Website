import { isPublicCheckoutPayload } from '../../../shared/contracts/public-checkout'
import { fetchSessionResponse } from '../../utils/backend-session'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  let response: Response
  try { response = await fetchSessionResponse(event, '/api/v1/checkout', { method: 'GET' }) }
  catch { throw createError({ statusCode: 502, statusMessage: 'Checkout metadata unavailable' }) }
  if (!response.ok) throw createError({ statusCode: 502, statusMessage: 'Checkout metadata unavailable' })
  const payload: unknown = await response.json().catch(() => null)
  if (!isPublicCheckoutPayload(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid checkout contract' })
  return payload
})
