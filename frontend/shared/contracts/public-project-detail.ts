export interface PublicProjectGalleryItem { type: 'image' | 'video'; src: string; href?: string; caption: string }
export interface PublicProjectCategory { id: number; name: string; slug: string; url: string }
export interface PublicProjectEquipment { id: number; name: string; slug: string; url: string; code: string; image: string }
export interface PublicProjectDetail {
  id: number; title: string; slug: string; url: string; summary: string; coverImage: string
  category: PublicProjectCategory | null; client: string; address: string; startDate: string; endDate: string
  gallery: PublicProjectGalleryItem[]; relatedEquipment: PublicProjectEquipment[]
}
export interface PublicProjectDetailPayload {
  schemaVersion: 1; locale: 'vi'; project: PublicProjectDetail
  content: { rendered: false; reason: string }
  canonical: { url: string; sources: Record<string, string> }
  parity: Record<string, { included: false; reason: string }>
}
const slugPattern = /^[a-z0-9]+(?:-[a-z0-9]+)*$/
function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function text(value: unknown, allowEmpty = false): value is string { return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value) }
function id(value: unknown): value is number { return typeof value === 'number' && Number.isSafeInteger(value) && value > 0 }
function media(value: unknown, prefix: '/upload/news/' | '/upload/product/', allowEmpty = false): value is string { return typeof value === 'string' && (allowEmpty || value !== '') && value.startsWith(prefix) && !value.includes('..') && !/[<>"'`\s]/.test(value) }
function category(value: unknown): value is PublicProjectCategory { return record(value) && id(value.id) && text(value.name) && isProjectSlug(value.slug) && value.url === `/danh-muc-du-an/${value.slug}/` }
function equipment(value: unknown): value is PublicProjectEquipment { return record(value) && id(value.id) && text(value.name) && isProjectSlug(value.slug) && value.url === `/san-pham/${value.slug}/` && text(value.code, true) && media(value.image, '/upload/product/', true) }
function gallery(value: unknown): value is PublicProjectGalleryItem { return record(value) && (value.type === 'image' || value.type === 'video') && media(value.src, '/upload/news/', true) && text(value.caption, true) && (value.type === 'image' || (text(value.href) && /^https?:\/\//.test(value.href))) }
export function isProjectSlug(value: unknown): value is string { return typeof value === 'string' && slugPattern.test(value) }
export function isPublicProjectDetailPayload(value: unknown): value is PublicProjectDetailPayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || !record(value.project) || !record(value.content) || value.content.rendered !== false || !text(value.content.reason) || !record(value.canonical) || !text(value.canonical.url) || !record(value.parity)) return false
  const project = value.project
  if (!id(project.id) || !text(project.title) || !isProjectSlug(project.slug) || project.url !== `/${project.slug}/` || !text(project.summary, true) || !media(project.coverImage, '/upload/news/', true) || !(project.category === null || category(project.category)) || !text(project.client, true) || !text(project.address, true) || !text(project.startDate, true) || !text(project.endDate, true) || !Array.isArray(project.gallery) || !project.gallery.every(gallery) || !Array.isArray(project.relatedEquipment) || !project.relatedEquipment.every(equipment)) return false
  return Object.values(value.parity).every((row) => record(row) && row.included === false && text(row.reason))
}
