export type PublicRouteResolveSupportedKind = 'pdp' | 'category' | 'brand' | 'gallery' | 'news' | 'news-category' | 'solution' | 'solution-category' | 'project' | 'project-category' | 'policy'
export type PublicRouteResolveResult =
  | { schemaVersion: 1; slug: string; kind: PublicRouteResolveSupportedKind; canonicalPath: string }
  | { schemaVersion: 1; slug: string; kind: 'legacy-only'; canonicalPath: null; reason: 'unsupported-taxonomy' | 'unsupported-route' }

const SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/

function record(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === 'object' && !Array.isArray(value)
}

function hasExactKeys(value: Record<string, unknown>, keys: string[]): boolean {
  const expected = [...keys].sort()
  const actual = Object.keys(value).sort()
  return expected.length === actual.length && expected.every((key, index) => key === actual[index])
}

function supportedCanonicalPath(kind: PublicRouteResolveSupportedKind, slug: string, path: unknown): path is string {
  if (typeof path !== 'string') return false
  switch (kind) {
    case 'pdp': return path === `/san-pham/${slug}`
    case 'category': return /^\/danh-muc-san-pham\/[a-z0-9]+(?:-[a-z0-9]+)*(?:\/[a-z0-9]+(?:-[a-z0-9]+)*){0,3}\/$/.test(path)
    case 'brand': return path === `/thuong-hieu/${slug}/`
    case 'gallery':
    case 'news':
    case 'project': return path === `/${slug}/`
    case 'project-category': return path === `/${slug}`
    case 'news-category':
    case 'solution-category': return path === `/${slug}`
    case 'solution': return path === `/giai-phap/${slug}/`
    case 'policy': return path === `/ho-tro/${slug}/`
  }
}

export function isPublicRouteResolveResult(value: unknown): value is PublicRouteResolveResult {
  if (!record(value) || value.schemaVersion !== 1 || typeof value.slug !== 'string' || !SLUG.test(value.slug)) return false
  if (value.kind === 'legacy-only') {
    return hasExactKeys(value, ['schemaVersion', 'slug', 'kind', 'canonicalPath', 'reason'])
      && value.canonicalPath === null
      && (value.reason === 'unsupported-taxonomy' || value.reason === 'unsupported-route')
  }
  const kinds: PublicRouteResolveSupportedKind[] = ['pdp', 'category', 'brand', 'gallery', 'news', 'news-category', 'solution', 'solution-category', 'project', 'project-category', 'policy']
  if (typeof value.kind !== 'string' || !kinds.includes(value.kind as PublicRouteResolveSupportedKind)) return false
  return hasExactKeys(value, ['schemaVersion', 'slug', 'kind', 'canonicalPath'])
    && supportedCanonicalPath(value.kind as PublicRouteResolveSupportedKind, value.slug, value.canonicalPath)
}

export function isPublicRouteResolveSlug(value: unknown): value is string {
  return typeof value === 'string' && SLUG.test(value)
}
