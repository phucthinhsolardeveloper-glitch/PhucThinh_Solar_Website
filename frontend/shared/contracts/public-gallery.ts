export interface PublicGallerySeo {
  title: string
  description: string
  keywords: string
  canonical: string
  image?: string
}

export interface PublicGalleryAlbum {
  id: number
  title: string
  slug: string
  url: string
  description: string
  image: string
}

export interface PublicGalleryImage {
  id: number
  image: string
  caption: string
}

export interface PublicGalleryPayload {
  schemaVersion: 1
  locale: 'vi'
  path: '/thu-vien-anh/'
  title: string
  seo: PublicGallerySeo
  albums: PublicGalleryAlbum[]
  pagination: { page: number; perPage: 20; total: number }
}

export interface PublicGalleryDetailPayload {
  schemaVersion: 1
  locale: 'vi'
  album: PublicGalleryAlbum
  images: PublicGalleryImage[]
  seo: PublicGallerySeo
}

const SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/
const IMAGE = /^\/upload\/product\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i
const SEO_IMAGE = /^\/upload\/seopage\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i

function record(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === 'object' && !Array.isArray(value)
}
function text(value: unknown, allowEmpty = false): value is string {
  return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value)
}
function integer(value: unknown, min = 0): value is number {
  return typeof value === 'number' && Number.isSafeInteger(value) && value >= min
}
function image(value: unknown): value is string {
  return value === '' || typeof value === 'string' && IMAGE.test(value)
}
function seoImage(value: unknown): value is string {
  return value === '' || typeof value === 'string' && SEO_IMAGE.test(value)
}
function seo(value: unknown, canonical: string): value is PublicGallerySeo {
  return record(value) && text(value.title) && text(value.description, true) && text(value.keywords, true) && value.canonical === canonical
    && (!('image' in value) || seoImage(value.image))
}
export function isPublicGalleryAlbum(value: unknown): value is PublicGalleryAlbum {
  if (!record(value) || !integer(value.id, 1) || !text(value.title) || typeof value.slug !== 'string' || !SLUG.test(value.slug)) return false
  return value.url === '/' + value.slug + '/' && text(value.description, true) && image(value.image)
}
function galleryImage(value: unknown): value is PublicGalleryImage {
  return record(value) && integer(value.id, 0) && typeof value.image === 'string' && IMAGE.test(value.image) && text(value.caption, true)
}
function pagination(value: unknown, perPage: number): boolean {
  return record(value) && integer(value.page, 1) && value.perPage === perPage && integer(value.total, 0)
}
export function isPublicGalleryPayload(value: unknown): value is PublicGalleryPayload {
  return record(value) && value.schemaVersion === 1 && value.locale === 'vi' && value.path === '/thu-vien-anh/'
    && text(value.title) && seo(value.seo, '/thu-vien-anh/') && Array.isArray(value.albums)
    && value.albums.length <= 20 && value.albums.every(isPublicGalleryAlbum) && pagination(value.pagination, 20)
}
export function isPublicGalleryDetailPayload(value: unknown): value is PublicGalleryDetailPayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || !isPublicGalleryAlbum(value.album)
    || !Array.isArray(value.images) || value.images.length > 120 || !value.images.every(galleryImage)) return false
  return seo(value.seo, value.album.url)
}
