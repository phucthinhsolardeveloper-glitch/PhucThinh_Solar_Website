export interface PublicSolutionDetail { id: number; title: string; slug: string; url: string; summary: string; image: string; paragraphs: string[]; extraParagraphs: string[]; gallery: string[] }
export interface PublicSolutionDetailNav { title: string; slug: string; url: string; image: string; active: boolean }
export interface PublicSolutionDetailPayload {
  schemaVersion: 1; locale: 'vi'; path: string; solution: PublicSolutionDetail
  navigation: { allUrl: '/giai-phap/'; items: PublicSolutionDetailNav[] }
  equipment: { name: string; description: string; url: string; image: string }[]
  cta: { label: string; href: '/lien-he/#yeu-cau-tu-van' }
  editorial: { rendered: false; reason: string }
  parity: Record<string, { included: false; reason: string }>
}

const record = (value: unknown): value is Record<string, unknown> => value !== null && typeof value === 'object' && !Array.isArray(value)
const text = (value: unknown, allowEmpty = false): value is string => typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F<>]/.test(value)
const slug = (value: unknown): value is string => typeof value === 'string' && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(value)
const image = (value: unknown): value is string => text(value, true) && (value === '' || /^\/upload\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(value))
const solutionUrl = (value: unknown, expected?: string): value is string => typeof value === 'string' && (!expected || value === `/giai-phap/${expected}/`) && /^\/giai-phap\/[a-z0-9]+(?:-[a-z0-9]+)*\/$/.test(value)
const publicUrl = (value: unknown): value is string => typeof value === 'string' && /^\/(?:danh-muc-san-pham\/[^/?#]+|san-pham)\/?$/.test(value)

export function isSolutionSlug(value: unknown): value is string { return slug(value) }
export function isPublicSolutionDetailPayload(value: unknown): value is PublicSolutionDetailPayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || !text(value.path) || !solutionUrl(value.path, value.solution && record(value.solution) ? value.solution.slug as string : undefined) || !record(value.solution)) return false
  const solution = value.solution
  if (typeof solution.id !== 'number' || !Number.isSafeInteger(solution.id) || solution.id < 1 || !text(solution.title) || !slug(solution.slug) || !solutionUrl(solution.url, solution.slug) || !text(solution.summary, true) || !image(solution.image) || !Array.isArray(solution.paragraphs) || !solution.paragraphs.every((item) => text(item)) || !Array.isArray(solution.extraParagraphs) || !solution.extraParagraphs.every((item) => text(item)) || !Array.isArray(solution.gallery) || !solution.gallery.every(image)) return false
  if (!record(value.navigation) || value.navigation.allUrl !== '/giai-phap/' || !Array.isArray(value.navigation.items) || !value.navigation.items.every((item) => record(item) && text(item.title) && slug(item.slug) && solutionUrl(item.url, item.slug) && image(item.image) && typeof item.active === 'boolean')) return false
  if (!Array.isArray(value.equipment) || value.equipment.length > 4 || !value.equipment.every((item) => record(item) && text(item.name) && text(item.description, true) && publicUrl(item.url) && image(item.image))) return false
  if (!record(value.cta) || value.cta.label !== 'Nhận tư vấn giải pháp' || value.cta.href !== '/lien-he/#yeu-cau-tu-van') return false
  if (!record(value.editorial) || value.editorial.rendered !== false || !text(value.editorial.reason)) return false
  return record(value.parity) && Object.values(value.parity).every((item) => record(item) && item.included === false && text(item.reason))
}
