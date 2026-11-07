import type { H3Event } from 'h3'

// Uploads and thumbnails live on the Laravel side. In production Caddy can route these paths to
// Laravel directly; this keeps one origin working when the browser reaches Nuxt first.
const MEDIA_PATH = /^\/(?:upload|thumbs)\/[A-Za-z0-9._/-]+$/

/** The Laravel URL for a media path, or null when the path or the configured origin is not safe. */
export function backendMediaUrl(pathname: string, apiOrigin: string): string | null {
  if (!MEDIA_PATH.test(pathname) || pathname.includes('..') || pathname.includes('//')) return null
  let origin: URL
  try {
    origin = new URL(apiOrigin)
  } catch {
    return null
  }
  if (!['http:', 'https:'].includes(origin.protocol) || origin.username || origin.password || origin.pathname !== '/' || origin.search || origin.hash) return null
  return new URL(pathname, origin).toString()
}

/** Streams the file (byte ranges included, so video seeking works); the visitor's cookies are not sent. */
export function proxyBackendMedia(event: H3Event, pathname: string) {
  assertMethod(event, ['GET', 'HEAD'])
  const target = backendMediaUrl(pathname, useRuntimeConfig(event).apiOrigin)
  if (!target) throw createError({ statusCode: 404, statusMessage: 'Media not found' })
  const headers: Record<string, string> = {}
  for (const name of ['range', 'if-none-match', 'if-modified-since']) {
    const value = getRequestHeader(event, name)
    if (value) headers[name] = value
  }
  return sendProxy(event, target, { headers, fetchOptions: { method: event.method, redirect: 'manual' } })
}
