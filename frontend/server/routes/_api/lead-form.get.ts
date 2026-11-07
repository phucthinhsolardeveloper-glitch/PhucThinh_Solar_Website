import { isPublicLeadFormSecurity } from '../../../shared/contracts/public-lead-form'
import { fetchSessionResponse } from '../../utils/backend-session'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  let response: Response
  try { response = await fetchSessionResponse(event, '/api/v1/lead-form', { method: 'GET' }) }
  catch { throw createError({ statusCode: 502, statusMessage: 'Lead form metadata unavailable' }) }
  if (!response.ok) throw createError({ statusCode: 502, statusMessage: 'Lead form metadata unavailable' })
  const payload: unknown = await response.json().catch(() => null)
  if (!isPublicLeadFormSecurity(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid lead form security contract' })
  return payload
})
