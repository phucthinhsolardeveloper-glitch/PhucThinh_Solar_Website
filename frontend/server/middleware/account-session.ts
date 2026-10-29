import { fetchPhpSessionResponse } from '../utils/migration-cart'

const ACCOUNT_ACTIONS: Record<string, string> = {
  'dang-nhap': 'login',
  'dang-ky': 'register',
  'quen-mat-khau': 'forgot',
  'kich-hoat': 'activate',
  'thong-tin': 'profile',
  'danh-sach-don-hang': 'orders',
  'don-hang-doi-tra': 'returns',
  'don-hang-huy': 'cancellations',
}

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') return
  const match = /^\/account\/([a-z-]+)\/?$/.exec(getRequestURL(event).pathname)
  const action = match ? ACCOUNT_ACTIONS[match[1]!] : undefined
  if (!action) return

  const query = new URLSearchParams({ action })
  const incoming = getQuery(event)
  if (action === 'activate' || action === 'orders' && incoming.action === 'edit') {
    const id = typeof incoming.id === 'string' ? incoming.id : ''
    if (/^[1-9][0-9]{0,9}$/.test(id)) {
      if (action === 'orders') query.set('action', 'order-detail')
      query.set('id', id)
    }
  }
  try {
    await fetchPhpSessionResponse(event, `/api/pts_v1_account.php?${query.toString()}`, { method: 'GET' })
    const sessionCookie = event.context.migrationPhpSessionCookie
    if (typeof sessionCookie === 'string' && !getRequestHeader(event, 'cookie')?.includes('PHPSESSID=')) {
      event.node.req.headers.cookie = [event.node.req.headers.cookie, sessionCookie].filter(Boolean).join('; ')
    }
  } catch {
    // The page's own account query renders the source error state.
  }
})
