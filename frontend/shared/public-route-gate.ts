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
export const SEARCH_PUBLIC_PATH = '/tim-kiem/'
export const CART_PUBLIC_PATH = '/gio-hang/'
/** The checkout form posts straight to the API, which answers with a redirect. */
export const CART_CHECKOUT_POST_PATH = '/api/v1/checkout'
export const ORDER_SUCCESS_PUBLIC_PATH = '/cam-on/'

const RESERVED_BARE_SLUGS = new Set([
  'he-thong-dai-ly', 've-chung-toi', 'product', 'index', 'sitemap', 'lien-he',
  'gioi-thieu', 'dai-ly', 'tin-tuc', 'giai-phap', 'du-an', 'du-an-da-thuc-hien',
  'chinh-sach', 'thuong-hieu', 'san-pham', 'tim-kiem', 'thu-vien-anh', 'video',
  'gio-hang', 'cam-on', 'account', 'ngon-ngu', 'admin', 'api', 'administrator', 'upload',
  'thumbs', 'watermark',
])
// Product pages have no trailing slash; brand pages keep it.

export function productDetailPublicPath(slug: string): string {
  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) throw new Error('Invalid product slug')
  return `/san-pham/${slug}`
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
