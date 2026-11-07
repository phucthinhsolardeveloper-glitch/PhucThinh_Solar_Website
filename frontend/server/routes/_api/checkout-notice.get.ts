import { isPublicCheckoutNoticePayload } from '../../../shared/contracts/public-checkout'
import { fetchSessionResponse } from '../../utils/backend-session'

/** A separate, one-time flash read so the site header's cart refresh cannot consume it. */
export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  let response: Response
  try { response = await fetchSessionResponse(event, '/api/v1/checkout-notice', { method: 'GET' }) }
  catch { throw createError({ statusCode: 502, statusMessage: 'Checkout notice unavailable' }) }
  if (!response.ok) throw createError({ statusCode: 502, statusMessage: 'Checkout notice unavailable' })
  const payload: unknown = await response.json().catch(() => null)
  if (!isPublicCheckoutNoticePayload(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid checkout notice contract' })
  return payload
})
