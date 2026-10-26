export interface PublicProjectCategory { id: number; name: string; slug: string; url: string; image: string }
export interface PublicProject { id: number; title: string; slug: string; url: string; description: string; address: string; image: string; category: PublicProjectCategory | null; featured: boolean }
export interface PublicProjectsCta { title: string; description: string; buttonLabel: string; image: string; hotline: { label: string; text: string; tel: string } | null }
export interface PublicProjectsPayload {
  schemaVersion: 1; locale: 'vi'; path: '/du-an-da-thuc-hien/'; title: string
  category: PublicProjectCategory | null; categories: PublicProjectCategory[]; featured: PublicProject | null; projects: PublicProject[]
  pagination: { page: number; perPage: number; total: number }; cta: PublicProjectsCta | null
  labels: { all: string; filters: string; featured: string; view: string; empty: string; cta: string }
}

function isRecord(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function isString(value: unknown): value is string { return typeof value === 'string' }
function isSlug(value: unknown): value is string { return isString(value) && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(value) }
function isImage(value: unknown): value is string { return isString(value) && (value === '' || /^\/upload\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(value)) }
function isCategory(value: unknown): value is PublicProjectCategory { return isRecord(value) && Number.isInteger(value.id) && value.id > 0 && isString(value.name) && isSlug(value.slug) && value.url === `/danh-muc-du-an/${value.slug}/` && isImage(value.image) }
function isProject(value: unknown): value is PublicProject { return isRecord(value) && Number.isInteger(value.id) && value.id > 0 && isString(value.title) && isSlug(value.slug) && value.url === `/${value.slug}/` && isString(value.description) && isString(value.address) && isImage(value.image) && typeof value.featured === 'boolean' && (value.category === null || isCategory(value.category)) }

export function isPublicProjectsPayload(value: unknown): value is PublicProjectsPayload {
  if (!isRecord(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || value.path !== '/du-an-da-thuc-hien/' || !isString(value.title)) return false
  if (value.category !== null && !isCategory(value.category)) return false
  if (!Array.isArray(value.categories) || !value.categories.every(isCategory)) return false
  if (value.featured !== null && !isProject(value.featured)) return false
  if (!Array.isArray(value.projects) || !value.projects.every(isProject)) return false
  if (!isRecord(value.pagination) || !Number.isInteger(value.pagination.page) || value.pagination.page < 1 || !Number.isInteger(value.pagination.perPage) || value.pagination.perPage < 1 || !Number.isInteger(value.pagination.total) || value.pagination.total < 0) return false
  if (value.cta !== null && (!isRecord(value.cta) || !isString(value.cta.title) || !isString(value.cta.description) || !isString(value.cta.buttonLabel) || !isImage(value.cta.image) || (value.cta.hotline !== null && (!isRecord(value.cta.hotline) || !isString(value.cta.hotline.label) || !isString(value.cta.hotline.text) || !isString(value.cta.hotline.tel))))) return false
  return isRecord(value.labels) && ['all', 'filters', 'featured', 'view', 'empty', 'cta'].every((key) => isString(value.labels[key]))
}
