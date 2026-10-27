import type { H3Event } from 'h3'

type MigrationEvent = H3Event

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

export function requireMigrationGet(event: MigrationEvent): void {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
}

export function migrationPhpEndpoint(event: MigrationEvent, endpoint: string): string {
  if (!endpoint.startsWith('/') || endpoint.startsWith('//') || endpoint.includes('\\')) {
    throw createError({ statusCode: 500, statusMessage: 'Invalid PHP migration endpoint' })
  }
  const config = useRuntimeConfig(event)
  let origin: URL
  try { origin = new URL(String(config.phpOrigin)) }
  catch { throw createError({ statusCode: 500, statusMessage: 'Invalid PHP migration origin' }) }

  const allowedHost = origin.hostname === '127.0.0.1' || origin.hostname === 'localhost'
  const allowedPort = origin.port === '8080' || origin.port === '8088'
  if (origin.protocol !== 'http:' || !allowedHost || !allowedPort || origin.username || origin.password || origin.pathname !== '/' || origin.search || origin.hash) {
    throw createError({ statusCode: 500, statusMessage: 'Untrusted PHP migration origin' })
  }
  return origin.origin + endpoint
}

export async function fetchMigrationPayload<T>(event: MigrationEvent, options: MigrationFetchOptions<T>): Promise<T> {
  let payload: unknown
  try {
    payload = await $fetch<unknown>(migrationPhpEndpoint(event, options.endpoint), {
      method: 'GET',
      query: options.query,
      timeout: 5000,
    })
  } catch (error) {
    const status = statusFromError(error)
    if (status !== undefined && options.passThroughStatuses?.includes(status)) throw error
    throw createError({ statusCode: 502, statusMessage: options.unavailableMessage })
  }
  if (!options.validate(payload)) {
    throw createError({ statusCode: 502, statusMessage: options.invalidMessage })
  }
  return payload
}
