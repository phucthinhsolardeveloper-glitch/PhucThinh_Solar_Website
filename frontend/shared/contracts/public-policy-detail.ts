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
  content: PublicSanitizedRichText
  seo: { title: string; description: string; keywords: string; canonical: string }
}
function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function text(value: unknown, allowEmpty = false): value is string { return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value) }
const SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/

export function isPublicPolicyDetail(value: unknown): value is PublicPolicyDetail {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi'
    || typeof value.id !== 'number' || !Number.isSafeInteger(value.id) || value.id <= 0
    || !text(value.title) || typeof value.slug !== 'string' || !SLUG.test(value.slug)
    || value.canonical !== `/ho-tro/${value.slug}/`
    || !text(value.description, true) || !text(value.date, true) || !isPublicSanitizedRichText(value.content)
    || !record(value.seo) || !text(value.seo.title) || !text(value.seo.description, true) || !text(value.seo.keywords, true)
    || value.seo.canonical !== value.canonical) return false
  return true
}
