import { isPublicAccountPayload, type PublicAccountAction } from '../../../shared/contracts/public-account'
import { fetchPhpSessionResponse } from '../../utils/migration-cart'

const ACTIONS = new Set<PublicAccountAction>(['login', 'register', 'forgot', 'activate', 'profile', 'orders', 'order-detail', 'returns', 'cancellations'])

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  const query = getQuery(event)
  if (Object.keys(query).some((key) => !['action', 'id'].includes(key))) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })
  const action = query.action
  const id = query.id
  if (typeof action !== 'string' || !ACTIONS.has(action as PublicAccountAction)) throw createError({ statusCode: 400, statusMessage: 'BAD_ACTION' })
  if ((action === 'activate' || action === 'order-detail') !== (typeof id === 'string' && /^[1-9][0-9]{0,9}$/.test(id))) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })

  let response: Response
  try {
    const params = new URLSearchParams({ action })
    if (typeof id === 'string') params.set('id', id)
    response = await fetchPhpSessionResponse(event, `/api/pts_v1_account.php?${params.toString()}`, { method: 'GET' })
  } catch {
    throw createError({ statusCode: 502, statusMessage: 'Account data source unavailable' })
  }
  const payload: unknown = await response.json().catch(() => null)
  if (!response.ok) {
    if (response.status === 400 || response.status === 404) throw createError({ statusCode: response.status, statusMessage: 'Invalid account request' })
    throw createError({ statusCode: 502, statusMessage: 'Account data source unavailable' })
  }
  if (!isPublicAccountPayload(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid account data contract' })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
