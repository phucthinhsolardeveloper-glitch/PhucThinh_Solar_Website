import { isPublicSanitizedRichText, type PublicSanitizedRichText } from './editorial-rich-text.ts'
import { isPublicSeoMetadata, type PublicSeoMetadata } from './core.ts'

export interface PublicDealerProduct { title: string; image: string; href: string }
export interface PublicDealerItem { title: string; description: string; descriptionContent: PublicSanitizedRichText; image: string; quote?: string }
export interface PublicDealerPayload { schemaVersion: 1; locale: 'vi'; path: '/dai-ly/'; title: string; seo?: PublicSeoMetadata; hero: { title: string; image: string } | null; intro: PublicSanitizedRichText; overview: { title: string; description: string; descriptionContent: PublicSanitizedRichText; products: PublicDealerProduct[] }; support: { title: string; description: string; descriptionContent: PublicSanitizedRichText; items: PublicDealerItem[] }; testimonials: PublicDealerItem[]; stats: { label: string; value: string }[]; form: { action: '/dai-ly/#dang-ky-dai-ly'; method: 'POST'; title: string; note: string }; hotline: { text: string; tel: string } | null; labels: { overview: string; support: string; testimonials: string; stats: string; empty: string } }
const record = (v: unknown): v is Record<string, unknown> => v !== null && typeof v === 'object' && !Array.isArray(v)
const text = (v: unknown): v is string => typeof v === 'string' && !/[\u0000-\u001f\u007f]/.test(v)
const image = (v: unknown): v is string => text(v) && (v === '' || /^(?:\/upload\/(?:photo|news)\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)|\/assets\/[A-Za-z0-9/_-]+\.(?:jpe?g|png|gif|webp|avif))$/i.test(v))
const link = (v: unknown): v is string => text(v) && (v === '' || (v.startsWith('/') && !v.includes('..') && !/[<>"'`\s]/.test(v)) || /^https?:\/\//i.test(v))
const item = (v: unknown): v is PublicDealerItem => record(v) && text(v.title) && text(v.description) && isPublicSanitizedRichText(v.descriptionContent) && image(v.image) && (v.quote === undefined || text(v.quote))
export function isPublicDealerPayload(v: unknown): v is PublicDealerPayload {
  if (!record(v) || v.schemaVersion !== 1 || v.locale !== 'vi' || v.path !== '/dai-ly/' || !text(v.title)) return false
  if (v.seo !== undefined && !isPublicSeoMetadata(v.seo, ['/upload/seopage/'])) return false
  if (v.hero !== null && (!record(v.hero) || !text(v.hero.title) || !image(v.hero.image))) return false
  if (!isPublicSanitizedRichText(v.intro)) return false
  if (!record(v.overview) || !text(v.overview.title) || !text(v.overview.description) || !isPublicSanitizedRichText(v.overview.descriptionContent) || !Array.isArray(v.overview.products) || !v.overview.products.every((x) => record(x) && text(x.title) && image(x.image) && link(x.href))) return false
  if (!record(v.support) || !text(v.support.title) || !text(v.support.description) || !isPublicSanitizedRichText(v.support.descriptionContent) || !Array.isArray(v.support.items) || !v.support.items.every(item)) return false
  if (!Array.isArray(v.testimonials) || !v.testimonials.every(item) || !Array.isArray(v.stats) || !v.stats.every((x) => record(x) && text(x.label) && /^[0-9][0-9.,]*$/.test(String(x.value)))) return false
  if (!record(v.form) || v.form.action !== '/dai-ly/#dang-ky-dai-ly' || v.form.method !== 'POST' || !text(v.form.title) || !text(v.form.note)) return false
  if (v.hotline !== null && (!record(v.hotline) || !text(v.hotline.text) || !text(v.hotline.tel) || !/^[0-9+ -]+$/.test(v.hotline.tel))) return false
  if (!record(v.labels)) return false
  const labels = v.labels
  return ['overview','support','testimonials','stats','empty'].every((k) => text(labels[k]))
}
