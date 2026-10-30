import { isPagination, isPositiveId, isPublicImage, isPublicSeoMetadata, isRecord, isSlug, isText, type PublicSeoMetadata } from './core.ts'

export interface PublicProjectCategory { id: number; name: string; description: string; slug: string; url: string; canonicalPath: string; image: string; seo?: PublicSeoMetadata }
export interface PublicProject { id: number; title: string; slug: string; url: string; description: string; address: string; image: string; category: PublicProjectCategory | null; featured: boolean }
export interface PublicProjectIntro { visible: boolean; title: string; description: string }
export interface PublicProjectBenefit { label: string; image: string }
export interface PublicProjectsCtaTrust { label: string; image: string; icon: 'headset' | 'cogs' | 'shield' }
export interface PublicProjectsCta { eyebrow?: string; title: string; description: string; buttonLabel: string; image: string; trust?: PublicProjectsCtaTrust[]; hotline: { label: string; text: string; tel: string } | null }
export interface PublicProjectsPayload {
  schemaVersion: 1; locale: 'vi'; path: '/du-an-da-thuc-hien/'; title: string
  seo?: PublicSeoMetadata
  intro: PublicProjectIntro; featuredBenefits: PublicProjectBenefit[]
  category: PublicProjectCategory | null; categories: PublicProjectCategory[]; featured: PublicProject | null; projects: PublicProject[]
  pagination: { page: number; perPage: number; total: number }; cta: PublicProjectsCta | null
  labels: { all: string; filters: string; featured: string; view: string; empty: string; cta: string }
}

function isImage(value: unknown): value is string { return isPublicImage(value, ['/upload/news/']) }
function isCategory(value: unknown): value is PublicProjectCategory { return isRecord(value) && isPositiveId(value.id) && isText(value.name, true) && isText(value.description, true) && isSlug(value.slug) && value.url === `/danh-muc-du-an/${value.slug}/` && value.canonicalPath === `/${value.slug}` && isImage(value.image) && (value.seo === undefined || isPublicSeoMetadata(value.seo, ['/upload/news/'])) }
function isProject(value: unknown): value is PublicProject { return isRecord(value) && isPositiveId(value.id) && isText(value.title, true) && isSlug(value.slug) && value.url === `/${value.slug}/` && isText(value.description, true) && isText(value.address, true) && isImage(value.image) && typeof value.featured === 'boolean' && (value.category === null || isCategory(value.category)) }

export function isPublicProjectsPayload(value: unknown): value is PublicProjectsPayload {
  if (!isRecord(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || value.path !== '/du-an-da-thuc-hien/' || !isText(value.title, true)) return false
  if (value.seo !== undefined && !isPublicSeoMetadata(value.seo, ['/upload/seopage/'])) return false
  if (value.category !== null && !isCategory(value.category)) return false
  if (!Array.isArray(value.categories) || !value.categories.every(isCategory)) return false
  if (!isRecord(value.intro) || typeof value.intro.visible !== 'boolean' || !isText(value.intro.title, true) || !isText(value.intro.description, true)) return false
  if (!Array.isArray(value.featuredBenefits) || value.featuredBenefits.length > 3 || !value.featuredBenefits.every((item) => isRecord(item) && isText(item.label) && isImage(item.image))) return false
  if (value.featured !== null && !isProject(value.featured)) return false
  if (!Array.isArray(value.projects) || !value.projects.every(isProject)) return false
  if (!isPagination(value.pagination)) return false
  if (value.cta !== null && (!isRecord(value.cta) || (value.cta.eyebrow !== undefined && !isText(value.cta.eyebrow, true)) || !isText(value.cta.title, true) || !isText(value.cta.description, true) || !isText(value.cta.buttonLabel, true) || !isImage(value.cta.image) || (value.cta.trust !== undefined && (!Array.isArray(value.cta.trust) || value.cta.trust.length > 3 || !value.cta.trust.every((item) => isRecord(item) && isText(item.label) && isImage(item.image) && ['headset', 'cogs', 'shield'].includes(String(item.icon))))) || (value.cta.hotline !== null && (!isRecord(value.cta.hotline) || !isText(value.cta.hotline.label, true) || !isText(value.cta.hotline.text, true) || !isText(value.cta.hotline.tel, true))))) return false
  if (!isRecord(value.labels)) return false
  const labels = value.labels
  return ['all', 'filters', 'featured', 'view', 'empty', 'cta'].every((key) => isText(labels[key], true))
}
