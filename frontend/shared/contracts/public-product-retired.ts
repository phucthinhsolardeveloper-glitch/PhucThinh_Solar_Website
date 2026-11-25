/** What an old link to a hidden product shows: the product's name and the nearest visible category to look in. */
export interface PublicProductRetiredPayload {
  schemaVersion: 1
  name: string
  category: { name: string, url: string } | null
}

function isRecord(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function isText(value: unknown): value is string { return typeof value === 'string' && value.trim() !== '' && value.length <= 300 && !/[<>\x00-\x1f\x7f]/.test(value) }

export function isPublicProductRetiredPayload(value: unknown): value is PublicProductRetiredPayload {
  if (!isRecord(value) || value.schemaVersion !== 1 || !isText(value.name)) return false
  if (value.category === null) return true
  return isRecord(value.category) && isText(value.category.name)
    && typeof value.category.url === 'string' && /^\/danh-muc-san-pham(?:\/[a-z0-9]+(?:-[a-z0-9]+)*)+\/$/.test(value.category.url)
}
