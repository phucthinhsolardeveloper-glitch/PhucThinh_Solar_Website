export const ABOUT_PUBLIC_PATH = '/gioi-thieu/'
export const SOLUTIONS_PUBLIC_PATH = '/giai-phap/'
export const CATALOG_PUBLIC_PATH = '/san-pham/'
export const SEARCH_PUBLIC_PATH = '/tim-kiem/'

// Static exact-match fallback paths (no slug).
const STATIC_PUBLIC_PATHS: readonly string[] = [ABOUT_PUBLIC_PATH, SOLUTIONS_PUBLIC_PATH, CATALOG_PUBLIC_PATH, SEARCH_PUBLIC_PATH]
// Slug-bearing fallback paths: PDP has no trailing slash (matches
// publicProductEntityPath()'s canonical form, see ROUTE_PARITY_MATRIX.md
// F03); brand detail keeps the trailing slash (F06).
const DYNAMIC_PUBLIC_PATH_PATTERNS: readonly RegExp[] = [/^\/san-pham\/[a-z0-9-]+$/, /^\/thuong-hieu\/[a-z0-9-]+\/$/]

export function isRuntimeFlagEnabled(value: unknown): boolean {
  return value === true || value === '1' || value === 'true'
}

export function productDetailPublicPath(slug: string): string {
  if (!/^[a-z0-9-]+$/.test(slug)) throw new Error('Invalid product slug')
  return `/san-pham/${slug}`
}

export function brandDetailPublicPath(slug: string): string {
  if (!/^[a-z0-9-]+$/.test(slug)) throw new Error('Invalid brand slug')
  return `/thuong-hieu/${slug}/`
}

export function phpPublicRouteUrl(originValue: unknown, pathname: string): string {
  const isKnown = STATIC_PUBLIC_PATHS.includes(pathname) || DYNAMIC_PUBLIC_PATH_PATTERNS.some((pattern) => pattern.test(pathname))
  if (!isKnown) throw new Error('Unsupported public route fallback')
  if (typeof originValue !== 'string' || !originValue.trim()) throw new Error('PHP public origin is required')

  const origin = new URL(originValue)
  if (!['http:', 'https:'].includes(origin.protocol) || origin.username || origin.password || origin.pathname !== '/' || origin.search || origin.hash) {
    throw new Error('Untrusted PHP public origin')
  }
  return new URL(pathname, origin).toString()
}
