import { isPublicOrderThanksPayload } from '../../../shared/contracts/public-checkout'
import { fetchSessionResponse } from '../../utils/backend-session'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'token')) throw createError({ statusCode: 400, statusMessage: 'Invalid order query' })
  const token = query.token
  if (typeof token !== 'string' || !/^[a-f0-9]{48}$/.test(token)) throw createError({ statusCode: 400, statusMessage: 'Invalid order token' })
  let response: Response
  try { response = await fetchSessionResponse(event, '/api/v1/order-thanks?token=' + encodeURIComponent(token), { method: 'GET' }) }
  catch { throw createError({ statusCode: 502, statusMessage: 'Order result unavailable' }) }
  if (!response.ok) throw createError({ statusCode: response.status === 404 ? 404 : 502, statusMessage: 'Order result unavailable' })
  const payload: unknown = await response.json().catch(() => null)
  if (!isPublicOrderThanksPayload(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid order result contract' })
  return payload
})
