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

function incomingPhpSessionCookie(value: string): string | null {
  const match = /(?:^|;\s*)PHPSESSID=([A-Za-z0-9,-]{16,128})(?:;|$)/.exec(value)
  return match ? `PHPSESSID=${match[1]}` : null
}

export async function fetchPhpSessionResponse(
  event: H3Event,
  endpoint: string,
  init: RequestInit = {},
): Promise<Response> {
  const headers = new Headers(init.headers)
  headers.delete('cookie')
  const cookie = getRequestHeader(event, 'cookie')
  const sessionCookie = cookie ? incomingPhpSessionCookie(cookie) : null
  if (sessionCookie) headers.set('cookie', sessionCookie)
  headers.set('accept', 'application/json')

  const response = await fetch(migrationPhpEndpoint(event, endpoint), {
    ...init,
    headers,
    redirect: 'manual',
  })

  for (const cookieValue of upstreamSetCookies(response.headers)) {
    const safeCookie = safePhpSessionCookie(cookieValue)
    if (safeCookie) appendResponseHeader(event, 'set-cookie', safeCookie)
  }
  setHeader(event, 'Cache-Control', 'private, no-store')
  return response
}
