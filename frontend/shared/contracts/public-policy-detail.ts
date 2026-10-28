import { isPublicSanitizedRichText, type PublicSanitizedRichText } from './editorial-rich-text.ts'

export interface PublicPolicyDetail {
  schemaVersion: 1
  locale: 'vi'
  id: number
  title: string
  slug: string
  canonical: string
  description: string
  date: string
  author: { name: string }
  content: PublicSanitizedRichText
  related: { id: number; title: string; slug: string; canonical: string; date: string; description: string; image: string }[]
  seo: { title: string; description: string; keywords: string; canonical: string }
}
function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function text(value: unknown, allowEmpty = false): value is string { return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value) }
const SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/
const NEWS_IMAGE = /^\/upload\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i

export function isPublicPolicyDetail(value: unknown): value is PublicPolicyDetail {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi'
    || typeof value.id !== 'number' || !Number.isSafeInteger(value.id) || value.id <= 0
    || !text(value.title) || typeof value.slug !== 'string' || !SLUG.test(value.slug)
    || value.canonical !== `/ho-tro/${value.slug}/`
    || !text(value.description, true) || !text(value.date, true) || !record(value.author) || !text(value.author.name)
    || !isPublicSanitizedRichText(value.content) || !Array.isArray(value.related)
    || !record(value.seo) || !text(value.seo.title) || !text(value.seo.description, true) || !text(value.seo.keywords, true)
    || value.seo.canonical !== value.canonical) return false
  return value.related.every((item: unknown) => record(item)
    && typeof item.id === 'number' && Number.isSafeInteger(item.id) && item.id > 0
    && text(item.title) && typeof item.slug === 'string' && SLUG.test(item.slug)
    && item.canonical === `/${item.slug}/` && text(item.date, true)
    && text(item.description, true)
    && typeof item.image === 'string' && (item.image === '' || NEWS_IMAGE.test(item.image)))
}
