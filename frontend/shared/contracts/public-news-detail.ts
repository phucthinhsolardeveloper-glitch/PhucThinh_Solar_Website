import { isPublicSanitizedRichText, type PublicSanitizedRichText } from './editorial-rich-text.ts'

export interface PublicNewsDetailMedia { gallery: { image: string; alt: string }[]; videos: { url: string; title: string }[] }
export interface PublicNewsDetailRelated { id: number; title: string; slug: string; canonical: string; date: string; author: string; description: string; image: string }
export interface PublicNewsDetailCta { title: string; description: string; buttonLabel: string; image: string; hotline: { label: string; text: string; tel: string } | null }
export interface PublicNewsDetailPayload {
  article: { schemaVersion: 1; locale: 'vi'; id: number; title: string; slug: string; canonical: string; date: string; datePublished: string; dateModified: string; author: { name: string }; description: string; cover: string; showCover: boolean; content: PublicSanitizedRichText; quote?: PublicSanitizedRichText | null; category: { id: number; name: string; slug: string; url: string } | Record<string, never>; media: PublicNewsDetailMedia; seo: { title: string; description: string; keywords: string; canonical: string }; readOnly: true; legacyViewMutation: false }
  cta: PublicNewsDetailCta | null
  related: PublicNewsDetailRelated[]
}

const slugRe = /^[a-z0-9]+(?:-[a-z0-9]+)*$/
const imageRe = /^\/upload\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i
const text = (v: unknown, empty = false): v is string => typeof v === 'string' && (empty || v.trim() !== '') && !/[<>\x00-\x1f\x7f]/.test(v)
export const isNewsDetailSlug = (v: unknown): v is string => typeof v === 'string' && slugRe.test(v)
export function isPublicNewsDetailPayload(value: unknown): value is PublicNewsDetailPayload {
  if (!value || typeof value !== 'object' || Array.isArray(value)) return false
  const a = (value as any).article
  const isDate = (v: unknown) => {
    if (v === '') return true
    if (typeof v !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(v)) return false
    const date = new Date(`${v}T00:00:00.000Z`)
    return !Number.isNaN(date.valueOf()) && date.toISOString().slice(0, 10) === v
  }
  if (!a || a.schemaVersion !== 1 || a.locale !== 'vi' || !Number.isSafeInteger(a.id) || a.id < 1 || !text(a.title) || !isNewsDetailSlug(a.slug) || a.canonical !== `/${a.slug}/` || !isDate(a.date) || a.date !== a.datePublished || !isDate(a.datePublished) || !isDate(a.dateModified) || typeof a.showCover !== 'boolean' || !text(a.author?.name) || !text(a.description, true) || !image(a.cover)) return false
  // content đi qua envelope sanitized-html (RICHTEXT-CONTRACT.md): hàng rào
  // isPublicSanitizedRichText() là lớp phòng vệ thứ hai ở Nuxt, không thay
  // thế sanitizer PHP — chỉ chặn payload giả mạo format/metadata.
  if (!isPublicSanitizedRichText(a.content) || !a.media || !Array.isArray(a.media.gallery) || !Array.isArray(a.media.videos) || !a.seo || a.seo.canonical !== a.canonical || !text(a.seo.title) || !text(a.seo.description, true) || !text(a.seo.keywords, true) || a.readOnly !== true || a.legacyViewMutation !== false) return false
  if (a.quote !== undefined && a.quote !== null && !isPublicSanitizedRichText(a.quote)) return false
  if (a.media.gallery.some((m: any) => !image(m?.image) || !text(m?.alt, true)) || a.media.videos.some((m: any) => !/^https:\/\/(?:www\.)?(?:youtube\.com|youtu\.be|vimeo\.com)(?:\/|$)/i.test(m?.url || '') || !text(m?.title, true))) return false
  const cta = (value as any).cta
  if (cta !== null && (!cta || typeof cta !== 'object' || Array.isArray(cta) || !text(cta.title) || !text(cta.description, true) || !text(cta.buttonLabel, true) || !image(cta.image) || !(cta.hotline === null || cta.hotline && typeof cta.hotline === 'object' && !Array.isArray(cta.hotline) && text(cta.hotline.label) && text(cta.hotline.text) && text(cta.hotline.tel, true)))) return false
  if (!Array.isArray((value as any).related)) return false
  return (value as any).related.every((r: any) => Number.isSafeInteger(r?.id) && r.id > 0 && text(r.title) && isNewsDetailSlug(r.slug) && r.canonical === `/${r.slug}/` && text(r.date, true) && text(r.author) && text(r.description, true) && image(r.image))
}
function image(v: unknown): v is string { return typeof v === 'string' && (v === '' || imageRe.test(v)) }
