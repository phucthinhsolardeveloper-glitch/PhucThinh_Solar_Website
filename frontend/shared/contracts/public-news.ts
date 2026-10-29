export interface PublicNewsCategory { id: number; name: string; description: string; slug: string; url: string; image: string; type?: 'tin-tuc'; level?: 'list' | 'cat' | 'item' | 'sub'; path?: string }
export interface PublicNewsArticle { id: number; title: string; slug: string; url: string; description: string; date: string; image: string; readingMinutes: number; category: PublicNewsCategory | null; featured: boolean }
export interface PublicNewsIntro { title: string; description: string }
export interface PublicNewsCta { title: string; description: string; buttonLabel: string; image: string; hotline: { label: string; text: string; tel: string } | null }
export interface PublicNewsPayload {
  schemaVersion: 1; locale: 'vi'; path: '/tin-tuc/'; title: string; description: string
  category: PublicNewsCategory | null; categories: PublicNewsCategory[]; featured: PublicNewsArticle[]; articles: PublicNewsArticle[]
  pagination: { page: number; perPage: 6; total: number }; intro: PublicNewsIntro | null; cta: PublicNewsCta | null
  labels: { all: string; search: string; featured: string; results: string; empty: string; reset: string; read: string; pages: string; readMore: string; ctaPhone: string }
}

function isRecord(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function isText(value: unknown, allowEmpty = false): value is string { return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[<>\u0000-\u001F\u007F]/.test(value) }
function isSlug(value: unknown): value is string { return typeof value === 'string' && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(value) }
function isImage(value: unknown): value is string { return typeof value === 'string' && (value === '' || /^\/upload\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(value)) }
function isId(value: unknown): value is number { return typeof value === 'number' && Number.isSafeInteger(value) && value > 0 }
function isCategory(value: unknown): value is PublicNewsCategory {
  if (!isRecord(value) || !isId(value.id) || !isText(value.name) || !isText(value.description, true) || !isSlug(value.slug) || value.url !== `/${value.slug}/` || !isImage(value.image)) return false
  const hasHierarchy = value.type !== undefined || value.level !== undefined || value.path !== undefined
  if (!hasHierarchy) return true
  if (value.type !== 'tin-tuc' || !['list', 'cat', 'item', 'sub'].includes(String(value.level)) || typeof value.path !== 'string') return false
  const levels = value.path.split('/')
  const expectedDepth = ({ list: 1, cat: 2, item: 3, sub: 4 } as Record<string, number>)[String(value.level)]
  return levels.length === expectedDepth && levels.every(isSlug) && levels.at(-1) === value.slug
}
function isArticle(value: unknown): value is PublicNewsArticle {
  return isRecord(value) && isId(value.id) && isText(value.title) && isSlug(value.slug) && value.url === `/${value.slug}/` && isText(value.description, true) && (value.date === '' || typeof value.date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value.date)) && isImage(value.image) && typeof value.readingMinutes === 'number' && Number.isSafeInteger(value.readingMinutes) && value.readingMinutes >= 0 && typeof value.featured === 'boolean' && (value.category === null || isCategory(value.category))
}
function isCta(value: unknown): value is PublicNewsCta {
  return isRecord(value) && isText(value.title) && isText(value.description, true) && isText(value.buttonLabel, true) && isImage(value.image) && (value.hotline === null || isRecord(value.hotline) && isText(value.hotline.label) && isText(value.hotline.text) && isText(value.hotline.tel, true))
}

export function isPublicNewsPayload(value: unknown): value is PublicNewsPayload {
  if(!isRecord(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || value.path !== '/tin-tuc/' || !isText(value.title) || !isText(value.description, true)) return false
  if(value.category !== null && !isCategory(value.category)) return false
  if(!Array.isArray(value.categories) || !value.categories.every(isCategory) || !Array.isArray(value.featured) || !value.featured.every(isArticle) || !Array.isArray(value.articles) || !value.articles.every(isArticle)) return false
  if(!isRecord(value.pagination) || value.pagination.page !== Math.floor(value.pagination.page as number) || typeof value.pagination.page !== 'number' || value.pagination.page < 1 || value.pagination.perPage !== 6 || typeof value.pagination.total !== 'number' || !Number.isSafeInteger(value.pagination.total) || value.pagination.total < 0) return false
  if(value.intro !== null && (!isRecord(value.intro) || !isText(value.intro.title) || !isText(value.intro.description, true))) return false
  if(value.cta !== null && !isCta(value.cta)) return false
  if(!isRecord(value.labels)) return false
  const labels = value.labels
  return ['all', 'search', 'featured', 'results', 'empty', 'reset', 'read', 'pages', 'readMore', 'ctaPhone'].every((key) => isText(labels[key]))
}
