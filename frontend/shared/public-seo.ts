import type { PublicSitePayload } from './contracts/public-site.ts'

export const PUBLIC_SEO_SITE_NAME = 'Phúc Thịnh Solar'
// The shared head falls back to the configured public logo when a route has
// no social image. Keep this aligned with the current CMS logo asset rather
// than using the small browser favicon as a social preview.
export const PUBLIC_SEO_FALLBACK_IMAGE = '/upload/photo/logo-phucthinh-solar-wordmark-2026.png'

const CANONICAL_HOST = 'phucthinhsolar.com'

/** Empty OG descriptions fall back to the site setting. */
export function publicSeoDescription(value: unknown, fallback = PUBLIC_SEO_SITE_NAME): string {
  const description = typeof value === 'string' ? value.trim() : ''
  if (description) return description
  const siteDescription = typeof fallback === 'string' ? fallback.trim() : ''
  return siteDescription || PUBLIC_SEO_SITE_NAME
}

export const PUBLIC_SEO_DESCRIPTION_MAX = 160

const HTML_ENTITIES: Record<string, string> = { amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' ' }

/** Plain text from CMS/HTML input: tags dropped, entities decoded, whitespace collapsed. */
export function plainSeoText(value: unknown): string {
  if (typeof value !== 'string') return ''
  return value
    .replace(/<(script|style)\b[^>]*>[\s\S]*?<\/\1>/gi, ' ')
    .replace(/<[^>]*>/g, ' ')
    .replace(/&(#x[0-9a-f]+|#\d+|[a-z]+);/gi, (match, entity: string) => {
      const lower = entity.toLowerCase()
      if (lower.startsWith('#x')) return String.fromCodePoint(parseInt(lower.slice(2), 16) || 32)
      if (lower.startsWith('#')) return String.fromCodePoint(parseInt(lower.slice(1), 10) || 32)
      return HTML_ENTITIES[lower] ?? match
    })
    .replace(/[<>]/g, ' ')
    .replace(/[\u0000-\u001F\u007F\s]+/g, ' ')
    .trim()
}

/** Plain text cut at a word boundary to at most `max` characters. */
export function conciseSeoDescription(value: unknown, max = PUBLIC_SEO_DESCRIPTION_MAX): string {
  const text = plainSeoText(value)
  if (text.length <= max) return text
  const cut = text.slice(0, max - 1)
  const lastSpace = cut.lastIndexOf(' ')
  return `${(lastSpace > max * 0.6 ? cut.slice(0, lastSpace) : cut).replace(/[\s,;:.\-–—]+$/, '')}…`
}

/** Explicit SEO text, else the product summary, else a sentence built only from the product name and category. */
export function productSeoDescription(input: { seo?: unknown; summary?: unknown; name?: unknown; category?: unknown }): string {
  const explicit = conciseSeoDescription(input.seo)
  if (explicit) return explicit
  const summary = conciseSeoDescription(input.summary)
  if (summary) return summary
  const name = plainSeoText(input.name)
  if (!name) return ''
  const category = plainSeoText(input.category)
  const lead = category && !name.toLocaleLowerCase('vi').includes(category.toLocaleLowerCase('vi')) ? `${name} (${category})` : name
  return conciseSeoDescription(`${lead} tại ${PUBLIC_SEO_SITE_NAME}. Xem hình ảnh, thông số và liên hệ để được tư vấn.`)
}

export const PUBLIC_CATALOG_ROOT_DESCRIPTION = `Sản phẩm năng lượng mặt trời tại ${PUBLIC_SEO_SITE_NAME}: đèn năng lượng mặt trời, tấm pin, biến tần và pin lưu trữ.`

/** Explicit SEO text, else a sentence naming the actual category; the root listing gets its own short text. */
export function catalogSeoDescription(input: { seo?: unknown; categoryName?: unknown; total?: unknown }): string {
  const categoryName = plainSeoText(input.categoryName)
  // The root listing has no category; the CMS text there is a long company intro, so use the topical text.
  if (!categoryName) return PUBLIC_CATALOG_ROOT_DESCRIPTION
  const explicit = conciseSeoDescription(input.seo)
  if (explicit) return explicit
  const total = typeof input.total === 'number' && Number.isInteger(input.total) && input.total > 0 ? `${input.total} sản phẩm ` : 'sản phẩm '
  return conciseSeoDescription(`${categoryName} tại ${PUBLIC_SEO_SITE_NAME}: xem ${total}kèm hình ảnh, thông số và nhận tư vấn.`)
}

/** Append the brand once to page titles. */
/** Search results show about 60 characters; the brand suffix is added only when the whole title still fits. */
export const PUBLIC_SEO_TITLE_MAX = 65

export function publicSeoTitle(value: unknown, fallback = PUBLIC_SEO_SITE_NAME): string {
  const title = typeof value === 'string' ? value.trim() : ''
  const resolved = title || fallback
  if (resolved.toLocaleLowerCase('vi').includes('phúc thịnh s')) return resolved
  const branded = `${resolved} | ${PUBLIC_SEO_SITE_NAME}`
  // A long product or article name is worth more in the result than the shop name Google already shows beside it.
  return branded.length <= PUBLIC_SEO_TITLE_MAX ? branded : resolved
}

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

/** Build breadcrumb JSON-LD from the same CMS-backed labels shown in the UI. */
export function publicBreadcrumbStructuredData(
  originValue: unknown,
  pathname: unknown,
  items: Array<{ label: string; path?: string }>,
): string | null {
  const origin = canonicalPublicOrigin(originValue)
  const currentUrl = canonicalPublicUrl(origin, pathname)
  if (!origin || !currentUrl || !Array.isArray(items) || items.length === 0 || items.length > 10) return null

  const entries = [{ label: 'Trang chủ', path: '/' }, ...items.map((item, index) => ({
    label: typeof item?.label === 'string' ? item.label.trim() : '',
    path: item?.path ?? (index === items.length - 1 ? pathname as string : ''),
  })).filter((item, index) => index === items.length - 1 || item.path)]
  if (entries.some((item) => !item.label || item.label.length > 200 || /[\u0000-\u001F\u007F]/.test(item.label))) return null

  const listItems = entries.map((entry, index) => {
    const itemUrl = canonicalPublicUrl(origin, entry.path)
    return itemUrl ? { '@type': 'ListItem', position: index + 1, name: entry.label, item: itemUrl } : null
  })
  if (listItems.some((item) => item === null)) return null

  return JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: listItems,
  }).replace(/</g, '\\u003c')
}

/** Share pictures are 1200×630 (1.91:1), the size Zalo and Facebook show without cropping. */
export const PUBLIC_SOCIAL_IMAGE_SIZE = { width: 1200, height: 630 } as const
// Same sources the /thumbs route accepts. Product shots and logos are fitted on white; photos are cropped to fill.
const SOCIAL_THUMB_SOURCE = /^\/(upload\/(?:news|photo|product|seopage)\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp)|assets\/images\/site-visuals\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png))$/i

function socialThumb(path: string): string {
  if (!SOCIAL_THUMB_SOURCE.test(path)) return path
  const fit = path.startsWith('/upload/product/') || /\/logo[^/]*$/i.test(path)
  return `/thumbs/${PUBLIC_SOCIAL_IMAGE_SIZE.width}x${PUBLIC_SOCIAL_IMAGE_SIZE.height}x${fit ? 2 : 1}${path}`
}

/** True when the share picture is served at exactly PUBLIC_SOCIAL_IMAGE_SIZE. */
export function isSizedSocialImage(url: string | null): boolean {
  return !!url && url.includes(`/thumbs/${PUBLIC_SOCIAL_IMAGE_SIZE.width}x${PUBLIC_SOCIAL_IMAGE_SIZE.height}x`)
}

export function publicSocialImageUrl(originValue: unknown, imageValue: unknown): string | null {
  const origin = canonicalPublicOrigin(originValue)
  if (!origin) return null
  if (typeof imageValue !== 'string' || imageValue === '') return `${origin}${socialThumb(PUBLIC_SEO_FALLBACK_IMAGE)}`
  if (isSafePublicPath(imageValue)) return `${origin}${socialThumb(imageValue)}`
  const url = canonicalPublicUrl(origin, imageValue)
  if (!url) return null
  return `${origin}${socialThumb(new URL(url).pathname)}`
}

function publicAssetUrl(origin: string, value: string | undefined): string | null {
  if (!value) return null
  if (value.startsWith('/')) return canonicalPublicUrl(origin, value)
  try {
    const url = new URL(value)
    if (url.origin !== origin || url.username || url.password || url.search || url.hash) return null
    return canonicalPublicUrl(origin, url.pathname)
  } catch {
    return null
  }
}

function publicSocialUrls(site: PublicSitePayload): string[] {
  const ignored = new Set([
    'http://google.com', 'http://www.google.com',
    'https://google.com', 'https://www.google.com',
  ])
  const urls = new Set<string>()
  for (const item of site.footer?.socialLinks ?? []) {
    try {
      const url = new URL(item.href)
      if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password || ignored.has(url.href.replace(/\/$/, ''))) continue
      urls.add(url.href)
    } catch {
      // Relative links are navigation, not Organization.sameAs identities.
    }
  }
  return [...urls]
}

/** Build the Organization graph without inventing missing CMS fields. */
export function publicSiteStructuredData(originValue: unknown, pathname: unknown, site: PublicSitePayload): string | null {
  const origin = canonicalPublicOrigin(originValue)
  if (!origin) return null

  const organizationId = `${origin}/#organization`
  const organization: Record<string, unknown> = {
    '@type': 'Organization',
    '@id': organizationId,
    name: PUBLIC_SEO_SITE_NAME,
    legalName: site.brand.name.trim() || PUBLIC_SEO_SITE_NAME,
    alternateName: 'PHUCTHINH SOLAR',
    url: origin,
  }
  const logo = publicAssetUrl(origin, site.brand.logo)
  if (logo) organization.logo = { '@type': 'ImageObject', url: logo }
  if (site.brand.hotline.trim()) organization.telephone = site.brand.hotline.trim()

  const headOffice = site.footer?.locations.find((location) => /^trụ sở/i.test(location.name.trim()) && location.address.trim())
  const mapLink = (href: string) => /^https:\/\/(maps\.app\.goo\.gl|(www\.)?google\.[a-z.]+\/maps)\//.test(href.trim()) ? href.trim() : undefined
  if (headOffice) {
    // With a real head office the company is also a LocalBusiness; the other footer places are its branches.
    organization['@type'] = ['Organization', 'LocalBusiness']
    if (logo) organization.image = logo
    organization.address = {
      '@type': 'PostalAddress',
      streetAddress: headOffice.address.trim(),
      addressRegion: 'Gia Lai',
      addressCountry: 'VN',
    }
    if (mapLink(headOffice.mapUrl ?? '')) organization.hasMap = mapLink(headOffice.mapUrl ?? '')
    const branches = (site.footer?.locations ?? []).filter((location) => location !== headOffice && location.name.trim() && location.address.trim())
      .map((location) => ({
        '@type': 'LocalBusiness',
        name: `${PUBLIC_SEO_SITE_NAME} – ${location.name.trim()}`,
        address: { '@type': 'PostalAddress', streetAddress: location.address.trim(), addressCountry: 'VN' },
        ...(mapLink(location.mapUrl ?? '') ? { hasMap: mapLink(location.mapUrl ?? '') } : {}),
        ...(site.brand.hotline.trim() ? { telephone: site.brand.hotline.trim() } : {}),
      }))
    if (branches.length) organization.department = branches
  }

  const sameAs = publicSocialUrls(site)
  if (sameAs.length) organization.sameAs = sameAs

  const schema: Record<string, unknown> = { '@context': 'https://schema.org' }
  if (pathname === '/') {
    schema['@graph'] = [organization, {
      '@type': 'WebSite',
      '@id': `${origin}/#website`,
      url: origin,
      name: PUBLIC_SEO_SITE_NAME,
      alternateName: 'PHUCTHINH SOLAR',
      publisher: { '@id': organizationId },
      inLanguage: 'vi-VN',
    }]
  } else {
    Object.assign(schema, organization)
  }

  return JSON.stringify(schema).replace(/</g, '\\u003c')
}

export function isPublicSeoIndexable(indexable: unknown, hasContent: unknown, hasFilters: unknown = false): boolean {
  const preference = indexable === true || indexable === '1' || indexable === 'true'
  return preference && hasContent === true && hasFilters !== true
}

export function publicSeoRobots(indexable: unknown, hasContent: unknown, hasFilters: unknown = false): string {
  return isPublicSeoIndexable(indexable, hasContent, hasFilters) ? 'index, follow' : 'noindex, nofollow, noarchive'
}
