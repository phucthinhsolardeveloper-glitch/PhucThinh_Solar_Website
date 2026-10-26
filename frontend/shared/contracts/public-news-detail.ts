export interface PublicNewsDetailMedia { gallery: { image: string; alt: string }[]; videos: { url: string; title: string }[] }
export interface PublicNewsDetailRelated { id: number; title: string; slug: string; canonical: string; date: string; description: string; image: string }
export interface PublicNewsDetailPayload {
  article: { schemaVersion: 1; locale: 'vi'; id: number; title: string; slug: string; canonical: string; date: string; author: { name: string }; description: string; cover: string; content: { format: 'plain-text'; value: string; richTextDeferred: true }; category: { id: number; name: string; slug: string; url: string } | Record<string, never>; media: PublicNewsDetailMedia; seo: { title: string; description: string; keywords: string; canonical: string }; readOnly: true; legacyViewMutation: false }
  related: PublicNewsDetailRelated[]
}

const slugRe = /^[a-z0-9]+(?:-[a-z0-9]+)*$/
const imageRe = /^\/upload\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i
const text = (v: unknown, empty = false): v is string => typeof v === 'string' && (empty || v.trim() !== '') && !/[<>\u0000-\u001F\u007F]/.test(v)
export const isNewsDetailSlug = (v: unknown): v is string => typeof v === 'string' && slugRe.test(v)
export function isPublicNewsDetailPayload(value: unknown): value is PublicNewsDetailPayload {
  if (!value || typeof value !== 'object' || Array.isArray(value)) return false
  const a = (value as any).article
  if (!a || a.schemaVersion !== 1 || a.locale !== 'vi' || !Number.isSafeInteger(a.id) || a.id < 1 || !text(a.title) || !isNewsDetailSlug(a.slug) || a.canonical !== `/${a.slug}/` || !text(a.date, true) || !text(a.author?.name) || !text(a.description, true) || !image(a.cover)) return false
  if (a.content?.format !== 'plain-text' || !text(a.content.value, true) || a.content.richTextDeferred !== true || !a.media || !Array.isArray(a.media.gallery) || !Array.isArray(a.media.videos) || !a.seo || a.seo.canonical !== a.canonical || !text(a.seo.title) || !text(a.seo.description, true) || !text(a.seo.keywords, true) || a.readOnly !== true || a.legacyViewMutation !== false) return false
  if (a.media.gallery.some((m: any) => !image(m?.image) || !text(m?.alt, true)) || a.media.videos.some((m: any) => !/^https:\/\/(?:www\.)?(?:youtube\.com|youtu\.be|vimeo\.com)(?:\/|$)/i.test(m?.url || '') || !text(m?.title, true))) return false
  if (!Array.isArray((value as any).related)) return false
  return (value as any).related.every((r: any) => Number.isSafeInteger(r?.id) && r.id > 0 && text(r.title) && isNewsDetailSlug(r.slug) && r.canonical === `/${r.slug}/` && text(r.date, true) && text(r.description, true) && image(r.image))
}
function image(v: unknown): v is string { return typeof v === 'string' && (v === '' || imageRe.test(v)) }
