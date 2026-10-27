import { isPositiveId, isPublicImage, isRecord, isRelativePublicUrl, isText } from './core.ts'

export interface PublicContactVisual { name: string; description: string; href: string; image: string }
export interface PublicContactLocation { id: number; name: string; address: string; mapUrl: string }
export interface PublicContactPayload {
  schemaVersion: 1
  locale: 'vi'
  path: '/lien-he/'
  seo: { title: string; description: string }
  title: string
  intro: string
  visual: PublicContactVisual | null
  contact: { company: string; hotline: string; email: string; address: string; workingHours: string; technicalHotline?: string }
  locations: PublicContactLocation[]
  labels: { locations: string; formCta: string; formNote: string }
}

function isPath(value: unknown, allowEmpty = false): value is string { return isRelativePublicUrl(value, allowEmpty) }
function isImage(value: unknown): value is string { return isPublicImage(value, ['/upload/photo/', '/assets/images/site-visuals/'], false, ['jpe?g', 'png', 'gif', 'webp']) }
function isMapUrl(value: unknown): value is string {
  if (!isText(value, true) || value === '') return value === ''
  try {
    const url = new URL(value)
    const host = url.hostname.toLowerCase()
    return url.protocol === 'https:'
      && url.port === ''
      && url.username === ''
      && url.password === ''
      && ['google.com', 'www.google.com', 'google.com.vn', 'www.google.com.vn', 'maps.google.com'].includes(host)
      && url.pathname.startsWith('/maps')
  } catch {
    return false
  }
}

export function isPublicContactPayload(value: unknown): value is PublicContactPayload {
  if (!isRecord(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || value.path !== '/lien-he/' || !isRecord(value.seo) || !isText(value.seo.title, true) || !isText(value.seo.description, true) || !isText(value.title) || !isText(value.intro, true) || !isRecord(value.contact) || !isText(value.contact.company, true) || !isText(value.contact.hotline, true) || !isText(value.contact.email, true) || !isText(value.contact.address, true) || !isText(value.contact.workingHours, true) || (value.contact.technicalHotline !== undefined && !isText(value.contact.technicalHotline)) || !Array.isArray(value.locations) || !isRecord(value.labels) || !isText(value.labels.locations) || !isText(value.labels.formCta) || !isText(value.labels.formNote)) return false
  if (value.visual !== null && (!isRecord(value.visual) || !isText(value.visual.name) || !isText(value.visual.description, true) || !isPath(value.visual.href) || !isImage(value.visual.image))) return false
  return value.locations.every((row: unknown) => isRecord(row) && isPositiveId(row.id) && isText(row.name) && isText(row.address) && isMapUrl(row.mapUrl))
}
