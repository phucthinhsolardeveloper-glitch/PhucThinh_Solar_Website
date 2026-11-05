import { isPublicSanitizedRichText, type PublicSanitizedRichText } from './editorial-rich-text.ts'

export interface PublicProductCategory { id: number; name: string; slug: string; url: string }
export interface PublicSiteLink { label: string; href: string; children?: PublicSiteLink[] }
export interface PublicProductCategoryNode { id: number; name: string; slug: string; url: string; children: PublicProductCategoryNode[] }
export interface PublicCatalogItem { label: string; href: string; children: PublicSiteLink[] }
export interface PublicCatalogGroup { label: string; href: string; items: PublicCatalogItem[] }
export interface PublicFooterPolicy { label: string; href: string }
export interface PublicFooterLocation { name: string; address: string; mapUrl: string }
export interface PublicSocialLink { label: string; href: string }
export interface PublicTrustBadge { image: string; href: string }

export interface PublicSitePayload {
  schemaVersion: 1
  navigationSchemaVersion?: 1
  locale?: string
  brand: { name: string; hotline: string; technicalHotline?: string; logo?: string; logoFooter?: string; favicon?: string; zaloHref?: string }
  navigation?: PublicSiteLink[]
  catalog?: Record<string, PublicCatalogGroup>
  productCategories: PublicProductCategory[]
  productCategoryTree?: PublicProductCategoryNode[]
  footer?: { companyName: string; contactText: PublicSanitizedRichText; policies: PublicFooterPolicy[]; locations: PublicFooterLocation[]; socialLinks: PublicSocialLink[]; trustBadge?: PublicTrustBadge }
}

function isRecord(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function isText(value: unknown, allowEmpty = false): value is string { return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value) }
function isSafeUrl(value: unknown, allowEmpty = false): value is string {
  if (typeof value !== 'string' || (value === '' && allowEmpty) === false && value === '') return false
  if (value === '' && allowEmpty) return true
  if (/[\u0000-\u0020<>"'`]/.test(value) || value.includes('..') || /^(?:javascript|data|vbscript):/i.test(value) || /^\/\//.test(value)) return false
  return value.startsWith('/') || /^https?:\/\//i.test(value)
}
function isId(value: unknown): value is number { return typeof value === 'number' && Number.isSafeInteger(value) && value > 0 }
function isCategoryUrl(value: unknown): value is string { return isSafeUrl(value) && value.startsWith('/danh-muc-san-pham/') }
function isLink(value: unknown, depth = 0): value is PublicSiteLink {
  if (depth > 8 || !isRecord(value) || !isText(value.label) || !isSafeUrl(value.href)) return false
  return value.children === undefined || Array.isArray(value.children) && value.children.every((child) => isLink(child, depth + 1))
}
function isCategoryNode(value: unknown, depth = 0): value is PublicProductCategoryNode {
  return depth <= 8 && isRecord(value) && isId(value.id) && isText(value.name) && isText(value.slug) && isCategoryUrl(value.url) && Array.isArray(value.children) && value.children.every((child) => isCategoryNode(child, depth + 1))
}
function isCatalogGroup(value: unknown): value is PublicCatalogGroup {
  if (!isRecord(value) || !isText(value.label) || !isSafeUrl(value.href, true) || !Array.isArray(value.items)) return false
  return value.items.every((item: unknown) => isRecord(item) && isText(item.label) && isSafeUrl(item.href) && Array.isArray(item.children) && item.children.every((child: unknown) => isLink(child)))
}
function isFooter(value: unknown): value is NonNullable<PublicSitePayload['footer']> {
  if (!isRecord(value) || !isText(value.companyName, true) || !isPublicSanitizedRichText(value.contactText) || !Array.isArray(value.policies) || !Array.isArray(value.locations) || !Array.isArray(value.socialLinks)) return false
  if (!value.policies.every((row: unknown) => isRecord(row) && isText(row.label) && isSafeUrl(row.href)) || !value.locations.every((row: unknown) => isRecord(row) && isText(row.name) && isText(row.address) && isSafeUrl(row.mapUrl, true)) || !value.socialLinks.every((row: unknown) => isRecord(row) && isText(row.label) && isSafeUrl(row.href))) return false
  return value.trustBadge === undefined || isRecord(value.trustBadge) && isSafeUrl(value.trustBadge.image) && isSafeUrl(value.trustBadge.href)
}

export function isPublicSitePayload(value: unknown): value is PublicSitePayload {
  if (!isRecord(value) || value.schemaVersion !== 1 || !isRecord(value.brand) || !isText(value.brand.name, true) || !isText(value.brand.hotline, true)) return false
  if (value.brand.logo !== undefined && !isSafeUrl(value.brand.logo)) return false
  if (value.brand.logoFooter !== undefined && !isSafeUrl(value.brand.logoFooter)) return false
  if (value.brand.favicon !== undefined && !isSafeUrl(value.brand.favicon)) return false
  if (value.brand.technicalHotline !== undefined && !isText(value.brand.technicalHotline)) return false
  if (value.brand.zaloHref !== undefined && (typeof value.brand.zaloHref !== 'string' || !/^https:\/\/zalo\.me\/\d{8,15}$/.test(value.brand.zaloHref))) return false
  if (value.navigationSchemaVersion !== undefined && value.navigationSchemaVersion !== 1) return false
  if (value.locale !== undefined && !isText(value.locale)) return false
  if (!Array.isArray(value.productCategories) || !value.productCategories.every((row: unknown) => isRecord(row) && isId(row.id) && isText(row.name) && isText(row.slug) && isCategoryUrl(row.url))) return false
  if (value.navigation !== undefined && (!Array.isArray(value.navigation) || !value.navigation.every((row: unknown) => isLink(row)))) return false
  if (value.catalog !== undefined && (!isRecord(value.catalog) || !Object.values(value.catalog).every((group) => isCatalogGroup(group)))) return false
  if (value.productCategoryTree !== undefined && (!Array.isArray(value.productCategoryTree) || !value.productCategoryTree.every((row: unknown) => isCategoryNode(row)))) return false
  return value.footer === undefined || isFooter(value.footer)
}
