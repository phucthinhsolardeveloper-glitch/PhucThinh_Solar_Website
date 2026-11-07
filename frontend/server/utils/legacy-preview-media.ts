import { isAllowedApiOrigin } from './backend-api.ts'

const MEDIA_PREFIXES = ['/upload/', '/assets/', '/thumbs/'] as const
const MEDIA_EXTENSIONS = new Map([
  ['.jpg', 'image/jpeg'],
  ['.jpeg', 'image/jpeg'],
  ['.png', 'image/png'],
  ['.gif', 'image/gif'],
  ['.webp', 'image/webp'],
  ['.avif', 'image/avif'],
])

export const LEGACY_MEDIA_MAX_BYTES = 8 * 1024 * 1024

export interface LegacyMediaConfig {
  apiOrigin: string
  previewMediaOrigin?: string
  apiOriginAllowlist?: unknown
  previewMediaProxy: boolean | string | number
}

export interface LegacyMediaValidation {
  pathname: string
  contentType: string
}

function isEnabled(value: LegacyMediaConfig['previewMediaProxy']): boolean {
  return value === true || value === 1 || value === '1' || value === 'true'
}

export function validateLegacyMediaPath(pathname: string): LegacyMediaValidation | null {
  let decoded: string
  try {
    decoded = decodeURIComponent(pathname)
  } catch {
    return null
  }

  if (decoded !== pathname || !MEDIA_PREFIXES.some((prefix) => pathname.startsWith(prefix))) return null
  if (!pathname.startsWith('/') || pathname.includes('\\') || pathname.includes('..') || pathname.includes('://') || /[\u0000-\u001f\u007f]/.test(pathname)) return null
  if (/[?#]/.test(pathname)) return null

  const extension = pathname.slice(pathname.lastIndexOf('.')).toLowerCase()
  const contentType = MEDIA_EXTENSIONS.get(extension)
  if (!contentType || /\.(?:php|phtml|phar|cgi|pl|py|rb|js|mjs|html?|shtml|svg|xml|wasm)$/i.test(pathname)) return null
  return { pathname, contentType }
}

function legacyOrigin(value: string): URL | null {
  try {
    const origin = new URL(value)
    if (!['http:', 'https:'].includes(origin.protocol) || origin.username || origin.password || origin.pathname !== '/' || origin.search || origin.hash) return null
    return origin
  } catch {
    return null
  }
}

export async function fetchLegacyPreviewMedia(
  pathname: string,
  config: LegacyMediaConfig,
  fetcher: typeof fetch = fetch,
): Promise<{ body: ArrayBuffer; contentType: string } | null> {
  if (!isEnabled(config.previewMediaProxy)) return null
  const validated = validateLegacyMediaPath(pathname)
  const apiOrigin = legacyOrigin(config.apiOrigin)
  const origin = legacyOrigin(config.previewMediaOrigin ?? config.apiOrigin)
  if (!validated || !apiOrigin || !origin) return null
  // Only use a separate media origin when it is explicitly allowlisted.
  if (origin.origin !== apiOrigin.origin && !isAllowedApiOrigin(origin, config.apiOriginAllowlist)) return null

  let expectedContentType = validated.contentType
  const upstream = new URL(validated.pathname, origin)
  let response: Response
  try {
    response = await fetcher(upstream, { method: 'GET', redirect: 'manual' })
  } catch {
    return null
  }
  // The local API bridge intentionally exposes /upload but denies /thumbs.
  // Fall back only for dimensioned upload thumbnails; the original path is
  // revalidated and still uses the same origin, byte limit, and MIME checks.
  if ((response.status === 403 || response.status === 404) && validated.pathname.startsWith('/thumbs/')) {
    const match = /^\/thumbs\/\d{1,5}x\d{1,5}x\d+(\/upload\/.+)$/.exec(validated.pathname)
    // ".png.webp"/".jpg.webp" are PHP WebP renditions of the JPG/PNG original.
    const fallbackPath = match?.[1]?.replace(/\.(jpe?g|png)\.webp$/i, '.$1')
    const original = fallbackPath ? validateLegacyMediaPath(fallbackPath) : null
    if (original) {
      try {
        response = await fetcher(new URL(original.pathname, origin), { method: 'GET', redirect: 'manual' })
        expectedContentType = original.contentType
      } catch {
        return null
      }
    }
  }
  if (!response.ok || response.status >= 300 && response.status < 400) return null

  const declaredLength = Number(response.headers.get('content-length') ?? '')
  if (Number.isFinite(declaredLength) && declaredLength > LEGACY_MEDIA_MAX_BYTES) return null
  const reader = response.body?.getReader()
  if (!reader) return null
  const chunks: Uint8Array[] = []
  let total = 0
  while (true) {
    const chunk = await reader.read()
    if (chunk.done) break
    total += chunk.value.byteLength
    if (total > LEGACY_MEDIA_MAX_BYTES) {
      await reader.cancel()
      return null
    }
    chunks.push(chunk.value)
  }
  const bytes = new Uint8Array(total)
  let offset = 0
  for (const chunk of chunks) {
    bytes.set(chunk, offset)
    offset += chunk.byteLength
  }

  const upstreamType = response.headers.get('content-type')?.split(';', 1)[0]?.trim().toLowerCase() ?? ''
  if (upstreamType !== expectedContentType) return null
  return { body: bytes.buffer, contentType: expectedContentType }
}
