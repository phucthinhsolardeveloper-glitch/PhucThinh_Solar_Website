import { isPublicSanitizedRichText, type PublicSanitizedRichText } from './editorial-rich-text.ts'

export interface PublicProjectGalleryItem { type: 'image' | 'video'; src: string; href?: string; caption: string }
export interface PublicProjectCategory { id: number; name: string; slug: string; url: string }
export interface PublicProjectEquipment { id: number; name: string; slug: string; url: string; code: string; image: string }
export interface PublicRelatedProject { id: number; title: string; slug: string; url: string; address: string; image: string }
export interface PublicProjectDetailTrust { label: string; icon: string; faIcon: string }
export interface PublicProjectDetailCta { eyebrow: string; title: string; description: string; buttonLabel: string; image: string; hotline: string; trust: PublicProjectDetailTrust[] }
/** Figures of the finished installation; null when Admin left them all empty. */
export interface PublicProjectSystem { type: string | null; typeLabel: string | null; kwp: number | null; storageKwh: number | null; outputKwhMonth: number | null; savingMonth: number | null; buildDays: number | null }
export interface PublicProjectDetailSeo { title: string; description: string; keywords: string }
export interface PublicProjectDetail {
  id: number; title: string; slug: string; url: string; summary: string; coverImage: string
  category: PublicProjectCategory | null; client: string; address: string; startDate: string; endDate: string
  gallery: PublicProjectGalleryItem[]; relatedEquipment: PublicProjectEquipment[]
  relatedProjects: PublicRelatedProject[]; cta: PublicProjectDetailCta | null
  system?: PublicProjectSystem | null
}
export interface PublicProjectDetailPayload {
  schemaVersion: 1; locale: 'vi'; project: PublicProjectDetail
  content: PublicSanitizedRichText
  seo: PublicProjectDetailSeo
  canonical: { url: string }
}
const slugPattern = /^[a-z0-9]+(?:-[a-z0-9]+)*$/
function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function text(value: unknown, allowEmpty = false): value is string { return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\x00-\x1f\x7f]/.test(value) }
function id(value: unknown): value is number { return typeof value === 'number' && Number.isSafeInteger(value) && value > 0 }
function media(value: unknown, prefix: '/upload/news/' | '/upload/product/', allowEmpty = false): value is string { return typeof value === 'string' && (value === '' ? allowEmpty : value.startsWith(prefix) && !value.includes('..') && !/[<>"'`\s]/.test(value)) }
function category(value: unknown): value is PublicProjectCategory { return record(value) && id(value.id) && text(value.name) && isProjectSlug(value.slug) && value.url === `/danh-muc-du-an/${value.slug}/` }
function equipment(value: unknown): value is PublicProjectEquipment { return record(value) && id(value.id) && text(value.name) && isProjectSlug(value.slug) && value.url === `/san-pham/${value.slug}` && text(value.code, true) && media(value.image, '/upload/product/', true) }
function relatedProject(value: unknown): value is PublicRelatedProject { return record(value) && id(value.id) && text(value.title) && isProjectSlug(value.slug) && value.url === `/${value.slug}/` && text(value.address, true) && media(value.image, '/upload/news/', true) }
function projectCta(value: unknown): value is PublicProjectDetailCta { return record(value) && text(value.eyebrow, true) && text(value.title) && text(value.description, true) && text(value.buttonLabel) && media(value.image, '/upload/news/', true) && text(value.hotline, true) && Array.isArray(value.trust) && value.trust.length <= 3 && value.trust.every((item) => record(item) && text(item.label) && media(item.icon, '/upload/news/', true) && ['fa-check', 'fa-headset', 'fa-cogs', 'fa-shield-alt'].includes(item.faIcon as string)) }
function gallery(value: unknown): value is PublicProjectGalleryItem { return record(value) && (value.type === 'image' || value.type === 'video') && media(value.src, '/upload/news/', true) && text(value.caption, true) && (value.type === 'image' || (text(value.href) && /^https?:\/\//.test(value.href))) }
function figure(value: unknown): boolean { return value === null || (typeof value === 'number' && Number.isFinite(value) && value > 0) }
function system(value: unknown): value is PublicProjectSystem {
  return record(value) && (value.type === null || (typeof value.type === 'string' && /^[a-z-]{2,20}$/.test(value.type))) && (value.typeLabel === null || text(value.typeLabel))
    && ['kwp', 'storageKwh', 'outputKwhMonth', 'savingMonth', 'buildDays'].every((key) => figure(value[key]))
}
export function isProjectSlug(value: unknown): value is string { return typeof value === 'string' && slugPattern.test(value) }
export function isPublicProjectDetailPayload(value: unknown): value is PublicProjectDetailPayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || !record(value.project) || !isPublicSanitizedRichText(value.content) || !record(value.seo) || !text(value.seo.title) || !text(value.seo.description, true) || !text(value.seo.keywords, true) || !record(value.canonical) || !text(value.canonical.url)) return false
  const project = value.project
  if (!id(project.id) || !text(project.title) || !isProjectSlug(project.slug) || project.url !== `/${project.slug}/` || value.canonical.url !== `/${project.slug}` || !text(project.summary, true) || !media(project.coverImage, '/upload/news/', true) || !(project.category === null || category(project.category)) || !text(project.client, true) || !text(project.address, true) || !text(project.startDate, true) || !text(project.endDate, true) || !Array.isArray(project.gallery) || !project.gallery.every(gallery) || !Array.isArray(project.relatedEquipment) || !project.relatedEquipment.every(equipment) || !Array.isArray(project.relatedProjects) || !project.relatedProjects.every(relatedProject)) return false
  if (project.system !== undefined && project.system !== null && !system(project.system)) return false
  return project.cta === null || projectCta(project.cta)
}
