import { isPagination, isPositiveId, isPublicImage, isRecord, isSlug, isText } from './core.ts'

export interface PublicProjectCategory { id: number; name: string; slug: string; description?: string; url: string; image: string; type?: 'du-an'; level?: 'list' | 'cat' | 'item' | 'sub'; path?: string }
export interface PublicProject { id: number; title: string; slug: string; url: string; description: string; address: string; image: string; category: PublicProjectCategory | null; featured: boolean }
export interface PublicProjectsCta { title: string; description: string; buttonLabel: string; image: string; hotline: { label: string; text: string; tel: string } | null }
export interface PublicProjectsIntro { visible: boolean; eyebrow: string; title: string; description: string }
export interface PublicProjectsPayload {
  schemaVersion: 1; locale: 'vi'; path: '/du-an-da-thuc-hien/'; title: string
  intro?: PublicProjectsIntro
  category: PublicProjectCategory | null; categories: PublicProjectCategory[]; featured: PublicProject | null; projects: PublicProject[]
  pagination: { page: number; perPage: number; total: number }; cta: PublicProjectsCta | null
  labels: { all: string; filters: string; featured: string; view: string; empty: string; cta: string }
}

function isImage(value: unknown): value is string { return isPublicImage(value, ['/upload/news/']) }
function isCategory(value: unknown): value is PublicProjectCategory {
  if (!isRecord(value) || !isPositiveId(value.id) || !isText(value.name, true) || !isSlug(value.slug) || (value.description !== undefined && !isText(value.description, true)) || !isImage(value.image)) return false
  const hasHierarchy = value.type !== undefined || value.level !== undefined || value.path !== undefined
  if (hasHierarchy) {
    if (value.type !== 'du-an' || !['list', 'cat', 'item', 'sub'].includes(String(value.level)) || typeof value.path !== 'string') return false
    const segments = value.path.split('/')
    return segments.length === ({ list: 1, cat: 2, item: 3, sub: 4 } as Record<string, number>)[String(value.level)] && segments.every(isSlug) && segments.at(-1) === value.slug && value.url === `/${value.slug}/`
  }
  return value.url === `/danh-muc-du-an/${value.slug}/`
}
function isProject(value: unknown): value is PublicProject { return isRecord(value) && isPositiveId(value.id) && isText(value.title, true) && isSlug(value.slug) && value.url === `/${value.slug}/` && isText(value.description, true) && isText(value.address, true) && isImage(value.image) && typeof value.featured === 'boolean' && (value.category === null || isCategory(value.category)) }

export function isPublicProjectsPayload(value: unknown): value is PublicProjectsPayload {
  if (!isRecord(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || value.path !== '/du-an-da-thuc-hien/' || !isText(value.title, true)) return false
  if (value.intro !== undefined && (!isRecord(value.intro) || typeof value.intro.visible !== 'boolean' || !isText(value.intro.eyebrow, true) || !isText(value.intro.title, true) || !isText(value.intro.description, true))) return false
  if (value.category !== null && !isCategory(value.category)) return false
  if (!Array.isArray(value.categories) || !value.categories.every(isCategory)) return false
  if (value.featured !== null && !isProject(value.featured)) return false
  if (!Array.isArray(value.projects) || !value.projects.every(isProject)) return false
  if (!isPagination(value.pagination)) return false
  if (value.cta !== null && (!isRecord(value.cta) || !isText(value.cta.title, true) || !isText(value.cta.description, true) || !isText(value.cta.buttonLabel, true) || !isImage(value.cta.image) || (value.cta.hotline !== null && (!isRecord(value.cta.hotline) || !isText(value.cta.hotline.label, true) || !isText(value.cta.hotline.text, true) || !isText(value.cta.hotline.tel, true))))) return false
  if (!isRecord(value.labels)) return false
  const labels = value.labels
  return ['all', 'filters', 'featured', 'view', 'empty', 'cta'].every((key) => isText(labels[key], true))
}
