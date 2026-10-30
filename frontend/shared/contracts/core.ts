export type PublicPagination = { page: number; perPage: number; total: number }
export type PublicSeoMetadata = { title: string; description: string; keywords?: string; image?: string }

const unsafeTextCharacters = /[\p{Cc}\p{Cf}\p{Zl}\p{Zp}]/u
const relativeUrlCharacters = /^[A-Za-z0-9][A-Za-z0-9/_?&=.#%~-]*$/
const fragmentUrlCharacters = /^#[A-Za-z0-9][A-Za-z0-9/_?&=.#%~-]*$/
const defaultImageExtensions = ['jpe?g', 'png', 'gif', 'webp', 'avif']

export function isRecord(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === 'object' && !Array.isArray(value)
}

export function isText(value: unknown, allowEmpty = false): value is string {
  return typeof value === 'string'
    && (allowEmpty || value.trim() !== '')
    && !unsafeTextCharacters.test(value)
}

export function isPositiveId(value: unknown): value is number {
  return typeof value === 'number' && Number.isSafeInteger(value) && value > 0
}

export function isSlug(value: unknown): value is string {
  return typeof value === 'string' && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(value)
}

export function isRelativePublicUrl(value: unknown, allowEmpty = false, allowFragmentOnly = true): boolean {
  if (!isText(value, allowEmpty)) return false
  if (value === '') return allowEmpty
  if (value.includes('..') || value.startsWith('//')) return false
  if (value.startsWith('#')) return allowFragmentOnly && fragmentUrlCharacters.test(value)
  return value.startsWith('/') && relativeUrlCharacters.test(value.slice(1))
}

export function isPublicUrl(value: unknown, allowEmpty = false): value is string {
  if (!isText(value, allowEmpty)) return false
  const stringValue = value
  if (stringValue === '') return allowEmpty
  if (isRelativePublicUrl(stringValue, false, false)) return true
  if (stringValue.includes('..')) return false

  try {
    const url = new URL(stringValue)
    return (url.protocol === 'http:' || url.protocol === 'https:')
      && url.hostname !== ''
      && url.username === ''
      && url.password === ''
  } catch {
    return false
  }
}

export function isPublicImage(
  value: unknown,
  allowedPrefixes: readonly string[],
  allowEmpty = true,
  extensions = defaultImageExtensions,
): value is string {
  if (!isText(value, allowEmpty)) return false
  if (value === '') return allowEmpty

  const prefix = allowedPrefixes.find((candidate) => value.startsWith(candidate))
  if (!prefix || value.includes('..')) return false

  const filename = value.slice(prefix.length)
  const extensionPattern = extensions.join('|')
  return new RegExp(`^[A-Za-z0-9][A-Za-z0-9._-]*\\.(?:${extensionPattern})$`, 'i').test(filename)
}

export function isPublicSeoMetadata(value: unknown, imagePrefixes: readonly string[]): value is PublicSeoMetadata {
  return isRecord(value)
    && isText(value.title, true)
    && isText(value.description, true)
    && (value.keywords === undefined || isText(value.keywords, true))
    && (value.image === undefined || isPublicImage(value.image, imagePrefixes))
}

export function isPagination(value: unknown): value is PublicPagination {
  return isRecord(value)
    && typeof value.page === 'number'
    && Number.isSafeInteger(value.page)
    && value.page >= 1
    && typeof value.perPage === 'number'
    && Number.isSafeInteger(value.perPage)
    && value.perPage >= 1
    && typeof value.total === 'number'
    && Number.isSafeInteger(value.total)
    && value.total >= 0
}
