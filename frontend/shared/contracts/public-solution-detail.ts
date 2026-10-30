import { isPublicSanitizedRichText, type PublicSanitizedRichText } from './editorial-rich-text.ts'
import { isPublicSeoMetadata, type PublicSeoMetadata } from './core.ts'

export interface PublicSolutionDetail { id: number; title: string; slug: string; url: string; summary: string; image: string; content: PublicSanitizedRichText; extraContent: PublicSanitizedRichText; gallery: string[] }
export interface PublicSolutionDetailNav { title: string; slug: string; url: string; image: string; active: boolean }
export interface PublicSolutionDetailCampaignCta {
  title: string; description: string; buttonLabel: string; image: string
  links: { consult: { label: string; href: '#ptsContactModal' } }
  hotline: { label: string; text: string; tel: string } | null
}
export interface PublicSolutionDetailPayload {
  schemaVersion: 1; locale: 'vi'; path: string; seo?: PublicSeoMetadata; solution: PublicSolutionDetail
  navigation: { allUrl: '/giai-phap/'; items: PublicSolutionDetailNav[] }
  equipment: { name: string; description: string; url: string; image: string }[]
  cta: { label: string; href: '/lien-he/#yeu-cau-tu-van' }
  campaignCta: PublicSolutionDetailCampaignCta | null
  /**
   * Trước khi tích hợp sanitizer, `editorial` bao quát cả phần HTML lẫn mục
   * lục (TOC). Sau tích hợp, `solution.content`/`extraContent` đã là HTML thật
   * qua pts_public_richtext.php — `editorial` giờ CHỈ còn nói về mục lục tự
   * sinh từ heading, vẫn chưa làm vì cần phân tích cấu trúc heading riêng,
   * không chỉ sanitize phẳng.
   */
  editorial: { rendered: false; reason: string }
  parity: Record<string, { included: false; reason: string }>
}

const record = (value: unknown): value is Record<string, unknown> => value !== null && typeof value === 'object' && !Array.isArray(value)
const text = (value: unknown, allowEmpty = false): value is string => typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\x00-\x1f\x7f<>]/.test(value)
const slug = (value: unknown): value is string => typeof value === 'string' && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(value)
const image = (value: unknown): value is string => text(value, true) && (value === '' || /^\/(?:upload\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)|assets\/images\/site-visuals\/(?:solution-home-solar-v2|solution-bess-v2|solution-solar-pump-v2|catalog-street-lighting)\.jpg)$/i.test(value))
const solutionUrl = (value: unknown, expected?: string): value is string => typeof value === 'string' && (!expected || value === `/giai-phap/${expected}/`) && /^\/giai-phap\/[a-z0-9]+(?:-[a-z0-9]+)*\/$/.test(value)
const publicUrl = (value: unknown): value is string => typeof value === 'string' && /^\/(?:danh-muc-san-pham\/[^/?#]+|san-pham)\/?$/.test(value)
function campaignCta(value: unknown): value is PublicSolutionDetailCampaignCta {
  if (!record(value) || !text(value.title) || !text(value.description) || !text(value.buttonLabel) || !image(value.image)) return false
  if (!record(value.links) || !record(value.links.consult) || !text(value.links.consult.label) || value.links.consult.href !== '#ptsContactModal') return false
  const hotline = value.hotline
  return hotline === null || (record(hotline) && text(hotline.label) && text(hotline.text) && typeof hotline.tel === 'string' && /^\+?[0-9]+$/.test(hotline.tel))
}

export function isSolutionSlug(value: unknown): value is string { return slug(value) }
export function isPublicSolutionDetailPayload(value: unknown): value is PublicSolutionDetailPayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || !text(value.path) || !solutionUrl(value.path, value.solution && record(value.solution) ? value.solution.slug as string : undefined) || !record(value.solution)) return false
  if (value.seo !== undefined && !isPublicSeoMetadata(value.seo, ['/upload/news/'])) return false
  const solution = value.solution
  if (typeof solution.id !== 'number' || !Number.isSafeInteger(solution.id) || solution.id < 1 || !text(solution.title) || !slug(solution.slug) || !solutionUrl(solution.url, solution.slug) || !text(solution.summary, true) || !image(solution.image) || !isPublicSanitizedRichText(solution.content) || !isPublicSanitizedRichText(solution.extraContent) || !Array.isArray(solution.gallery) || !solution.gallery.every(image)) return false
  if (!record(value.navigation) || value.navigation.allUrl !== '/giai-phap/' || !Array.isArray(value.navigation.items) || !value.navigation.items.every((item) => record(item) && text(item.title) && slug(item.slug) && solutionUrl(item.url, item.slug) && image(item.image) && typeof item.active === 'boolean')) return false
  if (!Array.isArray(value.equipment) || value.equipment.length > 4 || !value.equipment.every((item) => record(item) && text(item.name) && text(item.description, true) && publicUrl(item.url) && image(item.image))) return false
  if (!record(value.cta) || value.cta.label !== 'Nhận tư vấn giải pháp' || value.cta.href !== '/lien-he/#yeu-cau-tu-van') return false
  if (!(value.campaignCta === null || campaignCta(value.campaignCta))) return false
  if (!record(value.editorial) || value.editorial.rendered !== false || !text(value.editorial.reason)) return false
  return record(value.parity) && Object.values(value.parity).every((item) => record(item) && item.included === false && text(item.reason))
}
