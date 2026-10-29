import { fetchPhpSessionResponse } from '../../utils/migration-cart'

const ACTION_FIELDS: Record<string, ReadonlySet<string>> = {
  'dang-nhap': new Set(['form_csrf', 'username', 'password', 'remember-user', 'login-user']),
  'dang-ky': new Set(['form_csrf', 'fullname', 'username', 'password', 'repassword', 'gender', 'birthday', 'email', 'phone', 'address', 'registration-user']),
  'quen-mat-khau': new Set(['form_csrf', 'username', 'email', 'forgot-password-user']),
  'kich-hoat': new Set(['form_csrf', 'confirm_code', 'activation-user']),
  'thong-tin': new Set(['form_csrf', 'fullname', 'old-password', 'new-password', 'new-password-confirm', 'gender', 'birthday', 'email', 'phone', 'address', 'info-user']),
  'danh-sach-don-hang': new Set(['form_csrf']),
}

function safeReturnPath(value: unknown, action: string, id: string | null): string | null {
  if (typeof value !== 'string' || !value) return null
  let target: URL
  try { target = new URL(value, 'http://account.local') }
  catch { return null }
  if (value.startsWith('//') || !['http:', 'https:'].includes(target.protocol) || target.username || target.password) return null
  if (!/^\/account\/[a-z-]+\/?$/.test(target.pathname) && target.pathname !== '/') return null
  if (target.pathname === '/') {
    if (action === 'dang-nhap') target.pathname = '/account/thong-tin/'
    else if (action === 'quen-mat-khau') target.pathname = '/account/quen-mat-khau/'
    else return null
  }
  if (target.pathname === '/account/kich-hoat' && id) target.searchParams.set('id', id)
  return target.pathname + target.search
}

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'POST') {
    setHeader(event, 'Allow', 'POST')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }

  const action = getRouterParam(event, 'action') ?? ''
  const allowedFields = ACTION_FIELDS[action]
  if (!allowedFields) throw createError({ statusCode: 404, statusMessage: 'Account action not found' })

  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'id' && key !== 'action')) throw createError({ statusCode: 400, statusMessage: 'Invalid account query' })
  let id: string | null = null
  let orderAction: string | null = null
  if (action === 'kich-hoat') {
    if (typeof query.id !== 'string' || !/^[1-9][0-9]{0,9}$/.test(query.id)) throw createError({ statusCode: 400, statusMessage: 'Invalid activation link' })
    id = query.id
  } else if (action === 'danh-sach-don-hang') {
    if (query.action !== 'return' || typeof query.id !== 'string' || !/^[1-9][0-9]{0,9}$/.test(query.id)) throw createError({ statusCode: 400, statusMessage: 'Invalid return request' })
    orderAction = 'return'
    id = query.id
  } else if (Object.keys(query).length) {
    throw createError({ statusCode: 400, statusMessage: 'Unexpected account query' })
  }

  const body = await readBody<Record<string, unknown>>(event)
  if (!body || typeof body !== 'object' || Array.isArray(body)) throw createError({ statusCode: 400, statusMessage: 'Invalid account form' })
  const form = new URLSearchParams()
  for (const [key, value] of Object.entries(body)) {
    if (!allowedFields.has(key) || typeof value !== 'string' || value.length > 10000) throw createError({ statusCode: 400, statusMessage: 'Invalid account form field' })
    form.set(key, value)
  }

  const endpointQuery = new URLSearchParams()
  if (orderAction) endpointQuery.set('action', orderAction)
  if (id) endpointQuery.set('id', id)
  const suffix = endpointQuery.size ? `?${endpointQuery.toString()}` : ''
  let response: Response
  try {
    response = await fetchPhpSessionResponse(event, `/account/${action}${suffix}`, {
      method: 'POST',
      headers: { 'content-type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: form.toString(),
    }, { allowMemberAuthCookies: true })
  } catch {
    throw createError({ statusCode: 502, statusMessage: 'Account service unavailable' })
  }

  const payload: unknown = await response.json().catch(() => null)
  if (!payload || typeof payload !== 'object' || typeof (payload as { redirect?: unknown }).redirect !== 'string') {
    throw createError({ statusCode: 502, statusMessage: 'Account action returned an invalid response' })
  }
  const destination = safeReturnPath((payload as { redirect: string }).redirect, action, id)
  if (!destination) throw createError({ statusCode: 502, statusMessage: 'Account action returned an invalid redirect' })

  setHeader(event, 'Cache-Control', 'private, no-store')
  return sendRedirect(event, destination, 303)
})
