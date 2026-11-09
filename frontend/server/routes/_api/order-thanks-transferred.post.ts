import { fetchSessionResponse } from '../../utils/backend-session'

// The buyer reports a finished bank transfer. Staff still check the bank statement before the order counts as paid.
export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'POST') {
    setHeader(event, 'Allow', 'POST')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  const body = await readBody<{ token?: unknown }>(event)
  const token = body?.token
  if (typeof token !== 'string' || !/^[a-f0-9]{48}$/.test(token)) throw createError({ statusCode: 400, statusMessage: 'Invalid order token' })
  let response: Response
  try {
    response = await fetchSessionResponse(event, '/api/v1/order-thanks/transferred', {
      method: 'POST',
      headers: { 'content-type': 'application/json;charset=UTF-8' },
      body: JSON.stringify({ token }),
    })
  } catch {
    throw createError({ statusCode: 502, statusMessage: 'Transfer report unavailable' })
  }
  if (!response.ok) throw createError({ statusCode: response.status === 404 ? 404 : 502, statusMessage: 'Transfer report unavailable' })
  return { ok: true }
})
