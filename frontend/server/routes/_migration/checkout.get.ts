import { isPublicCheckoutPayload } from '../../../shared/contracts/public-checkout'
import { fetchPhpSessionResponse } from '../../utils/migration-cart'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  let response: Response
  try { response = await fetchPhpSessionResponse(event, '/api/pts_v1_checkout.php', { method: 'GET' }) }
  catch { throw createError({ statusCode: 502, statusMessage: 'PHP checkout metadata unavailable' }) }
  if (!response.ok) throw createError({ statusCode: 502, statusMessage: 'PHP checkout metadata unavailable' })
  const payload: unknown = await response.json().catch(() => null)
  if (!isPublicCheckoutPayload(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid PHP checkout contract' })
  return payload
})
