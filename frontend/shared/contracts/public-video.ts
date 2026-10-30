export interface PublicVideoItem {
  id: number
  title: string
  youtubeId: string
  embedUrl: string
  thumbnail: string
}

export interface PublicVideoPayload {
  schemaVersion: 1
  locale: 'vi'
  path: '/video/'
  title: string
  seo: { title: string; description: string; keywords: string; canonical: '/video/'; image: string }
  videos: PublicVideoItem[]
  pagination: { page: number; perPage: 10; total: number }
}

const YOUTUBE_ID = /^[A-Za-z0-9_-]{11}$/
const SEO_IMAGE = /^\/upload\/seopage\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i
function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function text(value: unknown, allowEmpty = false): value is string { return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value) }
function integer(value: unknown, min = 0): value is number { return typeof value === 'number' && Number.isSafeInteger(value) && value >= min }

export function isPublicVideoItem(value: unknown): value is PublicVideoItem {
  if (!record(value) || !integer(value.id, 1) || !text(value.title) || typeof value.youtubeId !== 'string' || !YOUTUBE_ID.test(value.youtubeId)) return false
  return value.embedUrl === 'https://www.youtube-nocookie.com/embed/' + value.youtubeId
    && value.thumbnail === 'https://img.youtube.com/vi/' + value.youtubeId + '/0.jpg'
}
export function isPublicVideoPayload(value: unknown): value is PublicVideoPayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || value.path !== '/video/' || !text(value.title)
    || !record(value.seo) || !text(value.seo.title) || !text(value.seo.description, true) || !text(value.seo.keywords, true)
    || value.seo.canonical !== '/video/' || typeof value.seo.image !== 'string' || !(value.seo.image === '' || SEO_IMAGE.test(value.seo.image))
    || !Array.isArray(value.videos) || value.videos.length > 10 || !value.videos.every(isPublicVideoItem)
    || !record(value.pagination)) return false
  return integer(value.pagination.page, 1) && value.pagination.perPage === 10 && integer(value.pagination.total, 0)
}
