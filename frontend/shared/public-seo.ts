import type { PublicSitePayload } from './contracts/public-site.ts'

export const PUBLIC_SEO_SITE_NAME = 'Phúc Thịnh Solar'
// PHP's shared head falls back to the configured public logo when a route has
// no social image. Keep this aligned with the current CMS logo asset rather
// than using the small browser favicon as a social preview.
export const PUBLIC_SEO_FALLBACK_IMAGE = '/upload/photo/logo-phucthinh-solar-wordmark-2026.png'

const CANONICAL_HOST = 'phucthinhsolar.com'

/** Mirror the PHP head's empty-OG-description fallback from the site setting. */
export function publicSeoDescription(value: unknown, fallback = PUBLIC_SEO_SITE_NAME): string {
  const description = typeof value === 'string' ? value.trim() : ''
  if (description) return description
  const siteDescription = typeof fallback === 'string' ? fallback.trim() : ''
  return siteDescription || PUBLIC_SEO_SITE_NAME
}

/** Mirror class.Seo::set(): append the brand once to PHP-owned page titles. */
export function publicSeoTitle(value: unknown, fallback = PUBLIC_SEO_SITE_NAME): string {
  const title = typeof value === 'string' ? value.trim() : ''
  const resolved = title || fallback
  return resolved.toLocaleLowerCase('vi').includes('phúc thịnh s')
    ? resolved
    : `${resolved} | ${PUBLIC_SEO_SITE_NAME}`
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
  }))]
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

export function publicSocialImageUrl(originValue: unknown, imageValue: unknown): string | null {
  const origin = canonicalPublicOrigin(originValue)
  if (!origin) return null
  if (typeof imageValue !== 'string' || imageValue === '') return `${origin}${PUBLIC_SEO_FALLBACK_IMAGE}`
  if (isSafePublicPath(imageValue)) return `${origin}${imageValue}`
  const url = canonicalPublicUrl(origin, imageValue)
  return url
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

/** Build the PHP-backed Organization graph without inventing missing CMS fields. */
export function publicSiteStructuredData(originValue: unknown, pathname: unknown, site: PublicSitePayload): string | null {
  const origin = canonicalPublicOrigin(originValue)
  if (!origin) return null

  const organizationId = `${origin}/#organization`
  const organization: Record<string, unknown> = {
    '@type': 'Organization',
    '@id': organizationId,
    name: PUBLIC_SEO_SITE_NAME,
    alternateName: 'PHUCTHINH SOLAR',
    url: origin,
  }
  const logo = publicAssetUrl(origin, site.brand.logo)
  if (logo) organization.logo = { '@type': 'ImageObject', url: logo }
  if (site.brand.hotline.trim()) organization.telephone = site.brand.hotline.trim()

  const headOffice = site.footer?.locations.find((location) => /^trụ sở/i.test(location.name.trim()) && location.address.trim())
  if (headOffice) {
    organization.address = {
      '@type': 'PostalAddress',
      streetAddress: headOffice.address.trim(),
      addressCountry: 'VN',
    }
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
