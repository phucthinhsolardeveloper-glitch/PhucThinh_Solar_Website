export const ABOUT_PUBLIC_PATH = '/gioi-thieu/'
export const SOLUTIONS_PUBLIC_PATH = '/giai-phap/'
export const HOME_PUBLIC_PATH = '/'
export const CONTACT_PUBLIC_PATH = '/lien-he/'
export const DEALER_PUBLIC_PATH = '/dai-ly/'
export const NEWS_PUBLIC_PATH = '/tin-tuc/'
export const PROJECTS_PUBLIC_PATH = '/du-an-da-thuc-hien/'
export const GALLERY_PUBLIC_PATH = '/thu-vien-anh/'
export const VIDEO_PUBLIC_PATH = '/video/'
export const CATALOG_PUBLIC_PATH = '/san-pham/'
export const BRAND_ROOT_PUBLIC_PATH = '/thuong-hieu/'
export const LEGACY_CATALOG_PUBLIC_PATH = '/product/'
export const SEARCH_PUBLIC_PATH = '/tim-kiem/'
export const CART_PUBLIC_PATH = '/gio-hang/'
export const ORDER_SUCCESS_PUBLIC_PATH = '/cam-on/'

// Static exact-match fallback paths (no slug).
const STATIC_PUBLIC_PATHS: readonly string[] = [HOME_PUBLIC_PATH, ABOUT_PUBLIC_PATH, SOLUTIONS_PUBLIC_PATH, CONTACT_PUBLIC_PATH, DEALER_PUBLIC_PATH, NEWS_PUBLIC_PATH, PROJECTS_PUBLIC_PATH, GALLERY_PUBLIC_PATH, VIDEO_PUBLIC_PATH, CATALOG_PUBLIC_PATH, BRAND_ROOT_PUBLIC_PATH, LEGACY_CATALOG_PUBLIC_PATH, SEARCH_PUBLIC_PATH, CART_PUBLIC_PATH, ORDER_SUCCESS_PUBLIC_PATH]
const RESERVED_BARE_SLUGS = new Set([
  'he-thong-dai-ly', 've-chung-toi', 'product', 'index', 'sitemap', 'lien-he',
  'gioi-thieu', 'dai-ly', 'tin-tuc', 'giai-phap', 'du-an', 'du-an-da-thuc-hien',
  'chinh-sach', 'thuong-hieu', 'san-pham', 'tim-kiem', 'thu-vien-anh', 'video',
  'gio-hang', 'cam-on', 'account', 'ngon-ngu', 'admin', 'api', 'administrator', 'upload',
  'thumbs', 'watermark',
])
// Slug-bearing fallback paths: PDP has no trailing slash (matches
// publicProductEntityPath()'s canonical form, see ROUTE_PARITY_MATRIX.md
// F03); brand detail keeps the trailing slash (F06).
const DYNAMIC_PUBLIC_PATH_PATTERNS: readonly RegExp[] = [/^\/san-pham\/[a-z0-9]+(?:-[a-z0-9]+)*$/, /^\/thuong-hieu\/[a-z0-9]+(?:-[a-z0-9]+)*\/$/, /^\/product\/[a-z0-9]+(?:-[a-z0-9]+)*$/]

export function isRuntimeFlagEnabled(value: unknown): boolean {
  return value === true || value === '1' || value === 'true'
}

export function productDetailPublicPath(slug: string): string {
  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) throw new Error('Invalid product slug')
  return `/san-pham/${slug}`
}

export function legacyProductDetailPublicPath(slug: string): string {
  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) throw new Error('Invalid legacy product slug')
  return `/product/${slug}`
}

export function brandDetailPublicPath(slug: string): string {
  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) throw new Error('Invalid brand slug')
  return `/thuong-hieu/${slug}/`
}

export function productCategoryPublicPath(slugs: readonly string[]): string {
  if (!isProductCategoryHierarchy(slugs)) throw new Error('Invalid product category path')
  return `/danh-muc-san-pham/${slugs.join('/')}/`
}

export function projectCategoryPublicPath(slug: string): string {
  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) throw new Error('Invalid project category slug')
  return `/danh-muc-du-an/${slug}/`
}

export function productCategorySegmentsFromPublicPath(path: unknown): string[] {
  const prefix = '/danh-muc-san-pham/'
  if (typeof path !== 'string' || !path.startsWith(prefix)) return []
  const rawSegments = path.slice(prefix.length).replace(/\/+$/, '')
  const segments = rawSegments ? rawSegments.split('/') : []
  return isProductCategoryHierarchy(segments) ? segments : []
}

export function bareSlugPublicPath(slug: string): string {
  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug) || RESERVED_BARE_SLUGS.has(slug)) {
    throw new Error('Invalid public slug path')
  }
  return `/${slug}/`
}

export function isProductCategoryHierarchy(value: unknown): value is string[] {
  return Array.isArray(value)
    && value.length >= 1
    && value.length <= 4
    && value.every((slug) => typeof slug === 'string' && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug))
}

export function legacyCatalogFilterQuery(query: Record<string, unknown>): Record<string, string> {
  const output: Record<string, string> = {}
  const get = (key: string) => typeof query[key] === 'string' ? (query[key] as string).trim() : ''
  const page = get('page')
  if (/^[1-9][0-9]*$/.test(page) && Number(page) <= 10000) output.p = page

  for (const [source, target] of [['brand', 'is_brand'], ['subcategory', 'is_cat']] as const) {
    const value = get(source)
    if (/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/.test(value)) output[target] = value
  }
  const price = get('price')
  if (/^[123](?:,[123])*$/.test(price) && new Set(price.split(',')).size === price.split(',').length) output.is_price = price

  const sort = get('sort')
  if (['default', 'abc', 'abc-desc', 'price', 'price-desc', 'new', 'new-desc'].includes(sort)) output.is_orderby = sort

  const keyword = get('keyword')
  if (keyword !== '' && [...keyword].length <= 100 && !/[\u0000-\u001F\u007F]/.test(keyword)) output.keyword = keyword

  const need = get('need')
  const needKeys = ['du-an-cong-trinh', 'chieu-sang-gia-dinh', 'san-vuon-canh-quan']
  if (need !== '' && need.split(',').every((key) => needKeys.includes(key)) && new Set(need.split(',')).size === need.split(',').length) output.is_need = need
  return output
}

export function phpPublicRouteUrl(originValue: unknown, pathname: string, query?: Record<string, string>): string {
  const categoryPath = (() => {
    const match = pathname.match(/^\/danh-muc-san-pham\/(.+)\/$/)
    return match ? isProductCategoryHierarchy(match[1]!.split('/')) : false
  })()
  const projectCategoryPath = /^\/danh-muc-du-an\/[a-z0-9]+(?:-[a-z0-9]+)*\/$/.test(pathname)
  const bareSlugMatch = pathname.match(/^\/([a-z0-9]+(?:-[a-z0-9]+)*)\/$/)
  const bareSlugPath = bareSlugMatch !== null && !RESERVED_BARE_SLUGS.has(bareSlugMatch[1]!)
  const isKnown = STATIC_PUBLIC_PATHS.includes(pathname) || DYNAMIC_PUBLIC_PATH_PATTERNS.some((pattern) => pattern.test(pathname)) || categoryPath || projectCategoryPath || bareSlugPath
  if (!isKnown) throw new Error('Unsupported public route fallback')
  if (typeof originValue !== 'string' || !originValue.trim()) throw new Error('PHP public origin is required')

  const origin = new URL(originValue)
  if (!['http:', 'https:'].includes(origin.protocol) || origin.username || origin.password || origin.pathname !== '/' || origin.search || origin.hash) {
    throw new Error('Untrusted PHP public origin')
  }
  const target = new URL(pathname, origin)
  for (const [key, value] of Object.entries(query ?? {})) {
    if (value) target.searchParams.set(key, value)
  }
  return target.toString()
}
