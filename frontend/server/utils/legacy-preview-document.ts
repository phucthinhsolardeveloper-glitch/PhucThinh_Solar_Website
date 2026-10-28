const DOCUMENT_PREFIXES = ['/upload/', '/assets/'] as const
const DOCUMENT_EXTENSIONS = new Map([
  ['.pdf', 'application/pdf'],
  ['.doc', 'application/msword'],
  ['.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
  ['.xls', 'application/vnd.ms-excel'],
  ['.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
])

export const LEGACY_DOCUMENT_MAX_BYTES = 25 * 1024 * 1024

export interface LegacyDocumentConfig {
  phpOrigin: string
  previewMediaProxy: boolean | string | number
}

export interface LegacyDocumentValidation {
  pathname: string
  contentType: string
  filename: string
}

function isEnabled(value: LegacyDocumentConfig['previewMediaProxy']): boolean {
  return value === true || value === 1 || value === '1'
}

export function validateLegacyDocumentPath(pathname: string): LegacyDocumentValidation | null {
  let decoded: string
  try {
    decoded = decodeURIComponent(pathname)
  } catch {
    return null
  }

  if (decoded !== pathname || !DOCUMENT_PREFIXES.some((prefix) => pathname.startsWith(prefix))) return null
  if (!pathname.startsWith('/') || pathname.includes('\\') || pathname.includes('..') || pathname.includes('://') || /[\u0000-\u001f\u007f]/.test(pathname)) return null
  if (/[?#]/.test(pathname) || !/^\/(?:upload|assets)\/[A-Za-z0-9._/-]+$/.test(pathname)) return null

  const extension = pathname.slice(pathname.lastIndexOf('.')).toLowerCase()
  const contentType = DOCUMENT_EXTENSIONS.get(extension)
  if (!contentType) return null
  const filename = pathname.slice(pathname.lastIndexOf('/') + 1)
  if (!filename || filename.startsWith('.')) return null
  return { pathname, contentType, filename }
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

export async function fetchLegacyPreviewDocument(
  pathname: string,
  config: LegacyDocumentConfig,
  fetcher: typeof fetch = fetch,
): Promise<{ body: ArrayBuffer; contentType: string; filename: string } | null> {
  if (!isEnabled(config.previewMediaProxy)) return null
  const validated = validateLegacyDocumentPath(pathname)
  const origin = legacyOrigin(config.phpOrigin)
  if (!validated || !origin) return null

  const upstream = new URL(validated.pathname, origin)
  let response: Response
  try {
    response = await fetcher(upstream, { method: 'GET', redirect: 'manual' })
  } catch {
    return null
  }
  if (!response.ok || response.status >= 300 && response.status < 400) return null

  const declaredLength = Number(response.headers.get('content-length') ?? '')
  if (Number.isFinite(declaredLength) && declaredLength > LEGACY_DOCUMENT_MAX_BYTES) return null
  const upstreamType = response.headers.get('content-type')?.split(';', 1)[0]?.trim().toLowerCase() ?? ''
  if (upstreamType && upstreamType !== validated.contentType && upstreamType !== 'application/octet-stream') return null

  const reader = response.body?.getReader()
  if (!reader) return null
  const chunks: Uint8Array[] = []
  let total = 0
  while (true) {
    const chunk = await reader.read()
    if (chunk.done) break
    total += chunk.value.byteLength
    if (total > LEGACY_DOCUMENT_MAX_BYTES) {
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
  return { body: bytes.buffer, contentType: validated.contentType, filename: validated.filename }
}
