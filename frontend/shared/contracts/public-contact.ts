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
  contact: { company: string; hotline: string; email: string; address: string; workingHours: string }
  locations: PublicContactLocation[]
  labels: { locations: string; formCta: string; formNote: string }
}

function isRecord(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function isText(value: unknown, allowEmpty = false): value is string { return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value) }
function isPath(value: unknown, allowEmpty = false): value is string { return isText(value, allowEmpty) && (value === '' || ((value.startsWith('/') || value.startsWith('#')) && !value.includes('..') && !/[<>"'`\s]/.test(value))) }
function isMapUrl(value: unknown): value is string { return isText(value, true) && (value === '' || /^https:\/\/(?:www\.)?google\.com(?:\.vn)?\/maps(?:[/?#]|$)/i.test(value) || /^https:\/\/maps\.google\.com(?:[/?#]|$)/i.test(value)) }
function isId(value: unknown): value is number { return typeof value === 'number' && Number.isSafeInteger(value) && value > 0 }

export function isPublicContactPayload(value: unknown): value is PublicContactPayload {
  if (!isRecord(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || value.path !== '/lien-he/' || !isRecord(value.seo) || !isText(value.seo.title, true) || !isText(value.seo.description, true) || !isText(value.title) || !isText(value.intro, true) || !isRecord(value.contact) || !isText(value.contact.company, true) || !isText(value.contact.hotline, true) || !isText(value.contact.email, true) || !isText(value.contact.address, true) || !isText(value.contact.workingHours, true) || !Array.isArray(value.locations) || !isRecord(value.labels) || !isText(value.labels.locations) || !isText(value.labels.formCta) || !isText(value.labels.formNote)) return false
  if (value.visual !== null && (!isRecord(value.visual) || !isText(value.visual.name) || !isText(value.visual.description, true) || !isPath(value.visual.href) || !isPath(value.visual.image))) return false
  return value.locations.every((row: unknown) => isRecord(row) && isId(row.id) && isText(row.name) && isText(row.address) && isMapUrl(row.mapUrl))
}
