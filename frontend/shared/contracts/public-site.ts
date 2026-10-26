export interface PublicProductCategory {
  id: number
  name: string
  slug: string
  url: string
}

export interface PublicSitePayload {
  schemaVersion: 1
  brand: { name: string; hotline: string }
  productCategories: PublicProductCategory[]
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === 'object' && !Array.isArray(value)
}

export function isPublicSitePayload(value: unknown): value is PublicSitePayload {
  if (!isRecord(value) || value.schemaVersion !== 1 || !isRecord(value.brand)) return false
  if (typeof value.brand.name !== 'string' || typeof value.brand.hotline !== 'string') return false
  if (!Array.isArray(value.productCategories)) return false
  return value.productCategories.every((row: unknown) => {
    if (!isRecord(row)) return false
    const id = row.id
    return typeof id === 'number' && Number.isSafeInteger(id) && id > 0 &&
      typeof row.name === 'string' && typeof row.slug === 'string' &&
      typeof row.url === 'string' && row.url.startsWith('/danh-muc-san-pham/')
  })
}
