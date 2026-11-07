import type { H3Event } from 'h3'

type MigrationEvent = H3Event

type OriginAllowlistEntry = {
  hostname: string
  port: string | null
}

type MigrationFetchOptions<T> = {
  endpoint: string
  query?: Record<string, string | undefined>
  validate: (payload: unknown) => payload is T
  invalidMessage: string
  unavailableMessage: string
  passThroughStatuses?: readonly number[]
}
function statusFromError(error: unknown): number | undefined {
  if (!error || typeof error !== 'object') return undefined
  const candidate = error as { statusCode?: unknown; response?: { status?: unknown } }
  if (typeof candidate.statusCode === 'number') return candidate.statusCode
  return typeof candidate.response?.status === 'number' ? candidate.response.status : undefined
}

function defaultPort(protocol: string): string {
  return protocol === 'https:' ? '443' : '80'
}

function parseOriginAllowlist(raw: unknown): OriginAllowlistEntry[] {
  const values = Array.isArray(raw) ? raw : typeof raw === 'string' ? raw.split(',') : []
  return values.flatMap((value) => {
    if (typeof value !== 'string' || !value.trim()) return []
    const candidate = value.trim()
    if (candidate.includes('://') || candidate.includes('/') || candidate.includes('?') || candidate.includes('#') || candidate.includes('*')) return []
    try {
      const parsed = new URL(`http://${candidate}`)
      if (parsed.username || parsed.password || parsed.pathname !== '/' || parsed.search || parsed.hash) return []
      return [{ hostname: parsed.hostname.toLowerCase(), port: parsed.port || null }]
    } catch {
      return []
    }
  })
}

export function isAllowedApiOrigin(origin: URL, rawAllowlist: unknown): boolean {
  if (!['http:', 'https:'].includes(origin.protocol) || origin.username || origin.password || origin.pathname !== '/' || origin.search || origin.hash) return false
  const originPort = origin.port || defaultPort(origin.protocol)
  return parseOriginAllowlist(rawAllowlist).some((entry) => entry.hostname === origin.hostname.toLowerCase()
    && (entry.port === null ? originPort === defaultPort(origin.protocol) : entry.port === originPort))
}

export function requireMigrationGet(event: MigrationEvent): void {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
}

export function apiEndpoint(event: MigrationEvent, endpoint: string): string {
  if (!endpoint.startsWith('/') || endpoint.startsWith('//') || endpoint.includes('\\')) {
    throw createError({ statusCode: 500, statusMessage: 'Invalid API endpoint' })
  }
  const config = useRuntimeConfig(event)
  let origin: URL
  try { origin = new URL(String(config.apiOrigin)) }
  catch { throw createError({ statusCode: 500, statusMessage: 'Invalid API origin' }) }

  if (!isAllowedApiOrigin(origin, config.apiOriginAllowlist)) {
    throw createError({ statusCode: 500, statusMessage: 'Untrusted API origin' })
  }
  return origin.origin + endpoint
}

export async function fetchMigrationPayload<T>(event: MigrationEvent, options: MigrationFetchOptions<T>): Promise<T> {
  let payload: unknown
  try {
    const apiUrl = new URL(apiEndpoint(event, options.endpoint))
    payload = await $fetch<unknown>(apiUrl.toString(), {
      method: 'GET',
      query: options.query,
      headers: { host: apiUrl.host },
      timeout: 5000,
      // Never let an API response redirect this server-side bridge to an
      // origin outside the exact allowlist checked above.
      redirect: 'manual',
    })
  } catch (error) {
    const status = statusFromError(error)
    if (status !== undefined && options.passThroughStatuses?.includes(status)) {
      // Re-create instead of re-throwing the raw ofetch FetchError: an
      // expected pass-through status (eg. a resolver 404 for an unknown
      // slug) is a normal outcome, not a crash, but Nitro's request logger
      // treats any error object that wasn't built via createError() as
      // "[unhandled]" and prints the internal PHP fetch URL/stack with it -
      // noisy for every mistyped URL and a minor internal-endpoint leak.
      throw createError({ statusCode: status, statusMessage: options.unavailableMessage, fatal: false })
    }
    throw createError({ statusCode: 502, statusMessage: options.unavailableMessage })
  }
  if (!options.validate(payload)) {
    throw createError({ statusCode: 502, statusMessage: options.invalidMessage })
  }
  return payload
}
