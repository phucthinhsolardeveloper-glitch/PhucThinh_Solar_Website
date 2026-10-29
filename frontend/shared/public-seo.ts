export const PUBLIC_SEO_SITE_NAME = 'Phúc Thịnh Solar'
export const PUBLIC_SEO_FALLBACK_IMAGE = '/assets/images/favicon-pts-mobile.png'

const CANONICAL_HOST = 'phucthinhsolar.com'

function isSafePublicPath(path: unknown): path is string {
  if (typeof path !== 'string' || !/^\/[A-Za-z0-9/_.-]*$/.test(path) || path.startsWith('//')) return false
  if (path.includes('//') || path.split('/').some((segment) => segment === '.' || segment === '..')) return false
  return true
}

export function canonicalPublicOrigin(originValue: unknown): string | null {
  if (typeof originValue !== 'string' || !originValue.trim()) return null
  try {
    const origin = new URL(originValue)
    if (origin.protocol !== 'https:' || origin.hostname.toLowerCase() !== CANONICAL_HOST
      || origin.username || origin.password || origin.port || origin.pathname !== '/' || origin.search || origin.hash) return null
    return `https://${CANONICAL_HOST}`
  } catch {
    return null
  }
}

export function canonicalPublicUrl(originValue: unknown, path: unknown): string | null {
  const origin = canonicalPublicOrigin(originValue)
  if (!origin || !isSafePublicPath(path)) return null
  try {
    const url = new URL(path, origin)
    return url.origin === origin ? url.toString() : null
  } catch {
    return null
  }
}

export function publicSocialImageUrl(originValue: unknown, imageValue: unknown): string | null {
  const origin = canonicalPublicOrigin(originValue)
  if (!origin) return null
  if (typeof imageValue !== 'string' || imageValue === '') return `${origin}${PUBLIC_SEO_FALLBACK_IMAGE}`
  if (isSafePublicPath(imageValue)) return `${origin}${imageValue}`
  const url = canonicalPublicUrl(origin, imageValue)
  return url
}

export function isPublicSeoIndexable(indexable: unknown, hasContent: unknown, hasFilters: unknown = false): boolean {
  return indexable === true && hasContent === true && hasFilters !== true
}

export function publicSeoRobots(indexable: unknown, hasContent: unknown, hasFilters: unknown = false): string {
  return isPublicSeoIndexable(indexable, hasContent, hasFilters) ? 'index, follow' : 'noindex, nofollow, noarchive'
}
