const SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/

export type PublicResolvedEntity = 'product' | 'product-category' | 'brand' | 'gallery' | 'news' | 'news-category' | 'project' | 'project-category' | 'solution' | 'solution-category' | 'policy' | 'not-found'
export interface PublicResolvedCategory {
  id: number
  slug: string
  type: 'san-pham' | 'tin-tuc' | 'giai-phap' | 'du-an'
  level: 'list' | 'cat' | 'item' | 'sub'
  path: string
  name: string
  description: string
  image: string
}
export interface PublicRouteResolution {
  schemaVersion: 1
  entity: PublicResolvedEntity
  slug: string
  canonical: string
  category?: PublicResolvedCategory
}

/**
 * Older QA PHP images exposed `kind`/`canonicalPath`.  Keep that DTO
 * compatibility at the migration boundary only; the rest of Nuxt consumes the
 * canonical `entity`/`canonical` contract above.
 */
const LEGACY_ENTITY: Record<string, Exclude<PublicResolvedEntity, 'not-found' | 'product-category' | 'news-category' | 'project-category' | 'solution-category'>> = {
  pdp: 'product',
  product: 'product',
  brand: 'brand',
  gallery: 'gallery',
  news: 'news',
  project: 'project',
  solution: 'solution',
  policy: 'policy',
}

export function normalizePublicRouteResolution(value: unknown): PublicRouteResolution | null {
  if (isPublicRouteResolution(value)) return value
  if (!value || typeof value !== 'object' || Array.isArray(value)) return null
  const dto = value as Record<string, unknown>
  if (dto.schemaVersion !== 1 || typeof dto.slug !== 'string' || !SLUG.test(dto.slug)) return null
  if (typeof dto.kind !== 'string' || typeof dto.canonicalPath !== 'string') return null
  const entity = LEGACY_ENTITY[dto.kind]
  if (!entity || !dto.canonicalPath.startsWith('/') || dto.canonicalPath.includes('://')) return null
  const normalized: PublicRouteResolution = {
    schemaVersion: 1,
    entity,
    slug: dto.slug,
    canonical: dto.canonicalPath,
  }
  return isPublicRouteResolution(normalized) ? normalized : null
}

const ENTITY_CANONICAL: Record<Exclude<PublicResolvedEntity, 'not-found'>, (slug: string, canonical: string) => boolean> = {
  product: (slug, path) => path === `/san-pham/${slug}`,
  'product-category': (_slug, path) => /^\/danh-muc-san-pham\/[a-z0-9-]+(?:\/[a-z0-9-]+){0,3}\/$/.test(path),
  brand: (slug, path) => path === `/thuong-hieu/${slug}/`,
  gallery: (slug, path) => path === `/${slug}/`,
  news: (slug, path) => path === `/${slug}/`,
  'news-category': (slug, path) => path === `/${slug}/`,
  project: (slug, path) => path === `/${slug}/`,
  'project-category': (slug, path) => path === `/${slug}/`,
  solution: (slug, path) => path === `/giai-phap/${slug}/`,
  'solution-category': (slug, path) => path === `/${slug}/`,
  policy: (slug, path) => path === `/ho-tro/${slug}/`,
}

export function isPublicRouteResolution(value: unknown): value is PublicRouteResolution {
  if (!value || typeof value !== 'object' || Array.isArray(value)) return false
  const dto = value as Record<string, unknown>
  if (dto.schemaVersion !== 1 || typeof dto.entity !== 'string' || typeof dto.slug !== 'string' || typeof dto.canonical !== 'string') return false
  if (dto.entity === 'not-found') return dto.slug === '' && dto.canonical === '' && dto.category === undefined
  if (!(dto.entity in ENTITY_CANONICAL) || !SLUG.test(dto.slug)) return false
  if (!ENTITY_CANONICAL[dto.entity as Exclude<PublicResolvedEntity, 'not-found'>](dto.slug, dto.canonical)) return false
  if (dto.category === undefined) return !['product-category', 'news-category', 'project-category', 'solution-category'].includes(dto.entity)
  const category = dto.category as Record<string, unknown>
  return (dto.entity === 'product-category' || dto.entity === 'news-category' || dto.entity === 'project-category' || dto.entity === 'solution-category')
    && Number.isSafeInteger(category.id) && Number(category.id) > 0
    && typeof category.slug === 'string' && SLUG.test(category.slug)
    && (dto.entity === 'product-category' ? category.type === 'san-pham' : dto.entity === 'news-category' ? category.type === 'tin-tuc' : dto.entity === 'solution-category' ? category.type === 'giai-phap' : category.type === 'du-an')
    && ['list', 'cat', 'item', 'sub'].includes(String(category.level))
    && typeof category.path === 'string' && category.path.split('/').length >= 1 && category.path.split('/').length <= 4
    && category.path.split('/').every((part) => SLUG.test(part))
    && category.path.split('/').at(-1) === category.slug
    && typeof category.name === 'string' && typeof category.description === 'string' && typeof category.image === 'string'
}
