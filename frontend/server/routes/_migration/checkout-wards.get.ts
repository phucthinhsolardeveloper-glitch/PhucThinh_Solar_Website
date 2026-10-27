import { isPublicCheckoutWardsPayload } from '../../../shared/contracts/public-checkout'
import { fetchPhpSessionResponse } from '../../utils/migration-cart'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  const city = getQuery(event).city
  if (typeof city !== 'string' || !/^[1-9][0-9]*$/.test(city)) throw createError({ statusCode: 400, statusMessage: 'Invalid city' })
  let response: Response
  try { response = await fetchPhpSessionResponse(event, '/api/pts_v1_checkout_wards.php?city=' + encodeURIComponent(city), { method: 'GET' }) }
  catch { throw createError({ statusCode: 502, statusMessage: 'PHP checkout locations unavailable' }) }
  if (!response.ok) throw createError({ statusCode: response.status === 404 ? 404 : 502, statusMessage: 'Checkout locations unavailable' })
  const payload: unknown = await response.json().catch(() => null)
  if (!isPublicCheckoutWardsPayload(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid checkout locations contract' })
  return payload
})
