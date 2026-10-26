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
  const config = useRuntimeConfig(event)
  return `${String(config.phpOrigin).replace(/\/$/, '')}${endpoint}`
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
