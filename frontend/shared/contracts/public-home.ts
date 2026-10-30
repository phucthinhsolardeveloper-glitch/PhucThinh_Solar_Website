export interface PublicHomeImage { image: string; name?: string; description?: string; href: string }
export interface PublicHomeSlide extends PublicHomeImage { key: string }
export interface PublicHomeCategory { id: number; name: string; slug: string; url: string; major: boolean; image: string }
export interface PublicHomeSolution extends PublicHomeImage { label: string; name: string; description: string }
export interface PublicHomeProcess { number: string; name: string; description: string }
export interface PublicHomeAboutPoint { label: string; description: string; image: string }
export interface PublicHomeProduct { id: number; name: string; slug: string; href: string; image: string }
export interface PublicHomeProductGroup { categoryId: number; categoryName: string; categoryUrl: string; products: PublicHomeProduct[] }
export interface PublicHomePayload {
  schemaVersion: 1
  locale: 'vi'
  path: '/'
  seo: { title: string; description: string; keywords?: string; image?: string }
  hero: PublicHomeSlide[]
  categories: PublicHomeCategory[]
  campaigns: PublicHomeImage[]
  solutions: PublicHomeSolution[]
  process: PublicHomeProcess[]
  dealer: { title: string; description: string; href: string }
  about: { configured: boolean; visible: boolean; title: string; description: string; href: string; action: string; points?: PublicHomeAboutPoint[] }
  contact: { hotline: string; email: string; address: string; workingHours: string }
  productGroups: PublicHomeProductGroup[]
  summary: Record<string, number>
}

function record(value: unknown): value is Record<string, unknown> { return !!value && typeof value === 'object' && !Array.isArray(value) }
function text(value: unknown, empty = false): value is string { return typeof value === 'string' && (empty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value) }
function url(value: unknown, empty = false): value is string { return text(value, empty) && (value === '' || value.startsWith('/') || /^https?:\/\//i.test(value)) && !/[\u0000-\u0020<>"'`]/.test(value) && !/(?:^|\/)\.\.(?:\/|$)/.test(value) }
function image(value: unknown): value is string { return url(value) && value.startsWith('/') && /\.(?:png|jpe?g|gif|webp)$/i.test(value) }
function seoImage(value: unknown): value is string { return value === undefined || value === '' || typeof value === 'string' && /^\/upload\/photo\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:png|jpe?g|gif|webp|avif)$/i.test(value) }

function isAboutPoint(value: unknown): value is PublicHomeAboutPoint {
  return record(value)
    && text(value.label, true)
    && text(value.description, true)
    && (value.image === '' || image(value.image))
}

export function isPublicHomePayload(value: unknown): value is PublicHomePayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || value.path !== '/' || !record(value.seo) || !text(value.seo.title, true) || !text(value.seo.description, true)) return false
  if (value.seo.keywords !== undefined && !text(value.seo.keywords, true) || !seoImage(value.seo.image)) return false
  const lists = ['hero', 'categories', 'campaigns', 'solutions', 'process', 'productGroups', 'summary']
  if (!lists.every((key) => key in value)) return false
  if (!Array.isArray(value.hero) || !value.hero.every((item) => record(item) && text(item.key) && text(item.name) && text(item.description, true) && image(item.image) && url(item.href))) return false
  if (!Array.isArray(value.categories) || !value.categories.every((item) => record(item) && typeof item.id === 'number' && Number.isSafeInteger(item.id) && item.id > 0 && text(item.name) && text(item.slug) && url(item.url) && item.url.startsWith('/danh-muc-san-pham/') && typeof item.major === 'boolean' && (item.image === '' || image(item.image)))) return false
  if (!Array.isArray(value.campaigns) || !value.campaigns.every((item) => record(item) && text(item.name) && text(item.description, true) && image(item.image) && url(item.href))) return false
  if (!Array.isArray(value.solutions) || !value.solutions.every((item) => record(item) && text(item.label) && text(item.name) && text(item.description) && image(item.image) && url(item.href))) return false
  if (!Array.isArray(value.process) || !value.process.every((item) => record(item) && text(item.number) && text(item.name) && text(item.description))) return false
  if (!record(value.dealer) || !text(value.dealer.title) || !text(value.dealer.description) || !url(value.dealer.href)) return false
  if (!record(value.about) || typeof value.about.configured !== 'boolean' || typeof value.about.visible !== 'boolean' || (value.about.visible && !value.about.configured) || !text(value.about.title) || !text(value.about.description) || !text(value.about.action) || !url(value.about.href)) return false
  if (value.about.points !== undefined && (!Array.isArray(value.about.points) || value.about.points.length > 4 || !value.about.points.every(isAboutPoint))) return false
  if (!record(value.contact) || !text(value.contact.hotline, true) || !text(value.contact.email, true) || !text(value.contact.address, true) || !text(value.contact.workingHours, true)) return false
  if (!Array.isArray(value.productGroups) || !value.productGroups.every((group) => record(group) && typeof group.categoryId === 'number' && Number.isSafeInteger(group.categoryId) && group.categoryId > 0 && text(group.categoryName) && url(group.categoryUrl) && Array.isArray(group.products) && group.products.every((product) => record(product) && typeof product.id === 'number' && Number.isSafeInteger(product.id) && product.id > 0 && text(product.name) && text(product.slug) && url(product.href) && image(product.image)))) return false
  return record(value.summary) && Object.values(value.summary).every((count) => typeof count === 'number' && Number.isSafeInteger(count) && count >= 0)
}
