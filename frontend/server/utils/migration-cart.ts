import type { H3Event } from 'h3'

function upstreamSetCookies(headers: Headers): string[] {
  const extended = headers as Headers & { getSetCookie?: () => string[] }
  const multiple = extended.getSetCookie?.() ?? []
  if (multiple.length) return multiple
  const single = headers.get('set-cookie')
  return single ? [single] : []
}

function safePhpSessionCookie(value: string): string | null {
  const first = value.split(';', 1)[0]?.trim() ?? ''
  const match = /^PHPSESSID=([A-Za-z0-9,-]{16,128})$/.exec(first)
  if (!match) return null
  return `PHPSESSID=${match[1]}; Path=/; HttpOnly; SameSite=Lax`
}

function safeMemberAuthCookie(value: string): string | null {
  const [first, ...attributes] = value.split(';').map((part) => part.trim())
  if (!first) return null
  const id = /^login_member_id=(\d{1,10})$/.exec(first)
  const session = /^login_member_session=([a-f0-9]{32})$/i.exec(first)
  const clearing = /^login_member_(?:id|session)=$/.test(first)
  if (!id && !session && !clearing) return null

  const safe = [first, 'Path=/', 'HttpOnly', 'SameSite=Lax']
  for (const attribute of attributes) {
    const expires = /^Expires=(.+)$/i.exec(attribute)
    const maxAge = /^Max-Age=(-?\d{1,10})$/i.exec(attribute)
    if (expires && Number.isFinite(Date.parse(expires[1]!))) safe.push(`Expires=${new Date(expires[1]!).toUTCString()}`)
    else if (maxAge && Number(maxAge[1]) >= -1 && Number(maxAge[1]) <= 31536000) safe.push(`Max-Age=${Number(maxAge[1])}`)
  }
  return safe.join('; ')
}

function incomingPhpSessionCookie(value: string): string | null {
  const match = /(?:^|;\s*)PHPSESSID=([A-Za-z0-9,-]{16,128})(?:;|$)/.exec(value)
  return match ? `PHPSESSID=${match[1]}` : null
}

export async function fetchPhpSessionResponse(
  event: H3Event,
  endpoint: string,
  init: RequestInit = {},
  options: { allowMemberAuthCookies?: boolean } = {},
): Promise<Response> {
  const headers = new Headers(init.headers)
  headers.delete('cookie')
  const cookie = getRequestHeader(event, 'cookie')
  const sessionCookie = (cookie ? incomingPhpSessionCookie(cookie) : null)
    ?? (typeof event.context.migrationPhpSessionCookie === 'string' ? event.context.migrationPhpSessionCookie : null)
  if (sessionCookie) headers.set('cookie', sessionCookie)
  headers.set('accept', 'application/json')

  const response = await fetch(migrationPhpEndpoint(event, endpoint), {
    ...init,
    headers,
    redirect: 'manual',
  })

  for (const cookieValue of upstreamSetCookies(response.headers)) {
    const sessionCookie = safePhpSessionCookie(cookieValue)
    if (sessionCookie) event.context.migrationPhpSessionCookie = sessionCookie.split(';', 1)[0]
    const safeCookie = sessionCookie ?? (options.allowMemberAuthCookies ? safeMemberAuthCookie(cookieValue) : null)
    if (safeCookie) appendResponseHeader(event, 'set-cookie', safeCookie)
  }
  setHeader(event, 'Cache-Control', 'private, no-store')
  return response
}
