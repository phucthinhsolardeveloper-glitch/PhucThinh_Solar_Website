import { getRequestProtocol, type H3Event } from 'h3'

function upstreamSetCookies(headers: Headers): string[] {
  const extended = headers as Headers & { getSetCookie?: () => string[] }
  const multiple = extended.getSetCookie?.() ?? []
  if (multiple.length) return multiple
  const single = headers.get('set-cookie')
  return single ? [single] : []
}

/** The backend's session cookie (Laravel SESSION_COOKIE); its value is encrypted by Laravel. */
export const SESSION_COOKIE = 'pts_session'
const SESSION_VALUE = /^[A-Za-z0-9%=+/._-]{16,4096}$/

/** Passes the session cookie back to the browser with fixed, safe attributes; anything else is dropped. */
export function safeSessionCookie(value: string, secure = false): string | null {
  const first = value.split(';', 1)[0]?.trim() ?? ''
  const separator = first.indexOf('=')
  if (separator < 0 || first.slice(0, separator) !== SESSION_COOKIE || !SESSION_VALUE.test(first.slice(separator + 1))) return null
  return `${first}; Path=/; HttpOnly; SameSite=Lax${secure ? '; Secure' : ''}`
}

function incomingSessionCookie(value: string): string | null {
  for (const part of value.split(';')) {
    const trimmed = part.trim()
    const separator = trimmed.indexOf('=')
    if (separator > 0 && trimmed.slice(0, separator) === SESSION_COOKIE && SESSION_VALUE.test(trimmed.slice(separator + 1))) return trimmed
  }
  return null
}

export async function fetchSessionResponse(
  event: H3Event,
  endpoint: string,
  init: RequestInit = {},
): Promise<Response> {
  const headers = new Headers(init.headers)
  headers.delete('cookie')
  const cookie = getRequestHeader(event, 'cookie')
  const sessionCookie = cookie ? incomingSessionCookie(cookie) : null
  if (sessionCookie) headers.set('cookie', sessionCookie)
  headers.set('accept', 'application/json')
  const apiUrl = new URL(apiEndpoint(event, endpoint))
  headers.set('host', apiUrl.host)

  const response = await fetch(apiUrl.toString(), {
    ...init,
    headers,
    redirect: 'manual',
  })

  for (const cookieValue of upstreamSetCookies(response.headers)) {
    // Trust the incoming TLS protocol as reported by the socket/reverse proxy,
    // not the internal HTTP connection to the API.
    const safeCookie = safeSessionCookie(cookieValue, getRequestProtocol(event) === 'https')
    if (safeCookie) appendResponseHeader(event, 'set-cookie', safeCookie)
  }
  setHeader(event, 'Cache-Control', 'private, no-store')
  return response
}
