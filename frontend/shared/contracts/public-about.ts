import { isPositiveId, isPublicImage, isPublicSeoMetadata, isPublicUrl, isRecord, isText as isCoreText, type PublicSeoMetadata } from './core.ts'

export interface PublicAboutImageItem { image: string }
export interface PublicAboutTextItem extends PublicAboutImageItem { title: string; description: string }
export interface PublicAboutTimelineItem { year: string; title: string; description: string }
export interface PublicAboutPayload {
  schemaVersion: 2; locale: 'vi'; path: '/gioi-thieu/'; title: string
  seo?: PublicSeoMetadata
  story: { eyebrow: string; title: string; intro: string; body: string; image: string }
  history: { title: string; image: string; items: PublicAboutTimelineItem[] }
  people: { eyebrow: string; title: string; intro: string; body: string; image: string; principles: PublicAboutTextItem[] }
  trust: { title: string; items: PublicAboutTextItem[] }
  compass: { title: string; items: PublicAboutTextItem[] }
  certificates: { title: string; intro: string; items: Array<{ id: number; name: string; description: string; image: string; href: string | null; featured: boolean }> }
  cta: { title: string; description: string; image: string; href: string | null; buttonLabel: string; hotline: { label: string; text: string; tel: string } | null; zaloUrl: string | null }
  labels: { story: string; history: string; people: string; compass: string; certificates: string; timeline: string; certLabel: string; certAll: string; certDetail: string }
}

function isImage(value: unknown): value is string { return isPublicImage(value, ['/upload/news/', '/upload/photo/']) }
function isText(value: unknown, allowEmpty = true): value is string { return isCoreText(value, allowEmpty) }
function isImageItem(value: unknown): value is PublicAboutImageItem { return isRecord(value) && isImage(value.image) }
function isTextItem(value: unknown): value is PublicAboutTextItem { return isRecord(value) && isImage(value.image) && isText(value.title) && isText(value.description) }
function isTimelineItem(value: unknown): value is PublicAboutTimelineItem { return isRecord(value) && typeof value.year === 'string' && /^(?:19|20)\d{2}$/.test(value.year) && isText(value.title) && isText(value.description) }
function isSafeHref(value: unknown): value is string { return isPublicUrl(value) }
function isZaloUrl(value: unknown): value is string {
  if (typeof value !== 'string') return false
  try {
    const url = new URL(value)
    return url.protocol === 'https:' && url.hostname === 'zalo.me' && url.port === ''
      && url.username === '' && url.password === '' && /^\/[0-9]{1,20}$/.test(url.pathname)
      && url.search === '' && url.hash === ''
  } catch {
    return false
  }
}

export function isPublicAboutPayload(value: unknown): value is PublicAboutPayload {
  if (!isRecord(value) || value.schemaVersion !== 2 || value.locale !== 'vi' || value.path !== '/gioi-thieu/' || !isText(value.title, false)) return false
  if (value.seo !== undefined && !isPublicSeoMetadata(value.seo, ['/upload/seopage/'])) return false
  const story = value.story; if (!isRecord(story) || !['eyebrow', 'title', 'intro', 'body'].every((key) => isText(story[key])) || !isImage(story.image)) return false
  const history = value.history; if (!isRecord(history) || !isText(history.title) || !isImage(history.image) || !Array.isArray(history.items) || !history.items.every(isTimelineItem)) return false
  const people = value.people; if (!isRecord(people) || !['eyebrow', 'title', 'intro', 'body'].every((key) => isText(people[key])) || !isImage(people.image) || !Array.isArray(people.principles) || !people.principles.every(isTextItem)) return false
  for (const key of ['trust', 'compass']) { const section = value[key]; if (!isRecord(section) || !isText(section.title) || !Array.isArray(section.items) || !section.items.every(isTextItem)) return false }
  const certificates = value.certificates; if (!isRecord(certificates) || !isText(certificates.title) || !isText(certificates.intro) || !Array.isArray(certificates.items)) return false
  if (!certificates.items.every((item) => isRecord(item) && isPositiveId(item.id) && isText(item.name, true) && isText(item.description, true) && isImage(item.image) && typeof item.featured === 'boolean' && (item.href === null || item.href === item.image && isSafeHref(item.href)))) return false
  const cta = value.cta; if (!isRecord(cta) || !['title', 'description', 'buttonLabel'].every((key) => isText(cta[key])) || !isImage(cta.image) || (cta.href !== null && !isSafeHref(cta.href))) return false
  if (cta.hotline !== null && (!isRecord(cta.hotline) || !isText(cta.hotline.label) || !isText(cta.hotline.text) || !isText(cta.hotline.tel))) return false
  if (cta.zaloUrl !== null && !isZaloUrl(cta.zaloUrl)) return false
  const labels = value.labels; return isRecord(labels) && ['story', 'history', 'people', 'compass', 'certificates', 'timeline', 'certLabel', 'certAll', 'certDetail'].every((key) => isText(labels[key], false))
}
