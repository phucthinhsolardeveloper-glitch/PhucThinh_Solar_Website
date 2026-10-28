export const ABOUT_PUBLIC_PATH = '/gioi-thieu/'

export function isRuntimeFlagEnabled(value: unknown): boolean {
  return value === true || value === '1' || value === 'true'
}

export function phpPublicRouteUrl(originValue: unknown, pathname: string): string {
  if (pathname !== ABOUT_PUBLIC_PATH) throw new Error('Unsupported public route fallback')
  if (typeof originValue !== 'string' || !originValue.trim()) throw new Error('PHP public origin is required')

  const origin = new URL(originValue)
  if (!['http:', 'https:'].includes(origin.protocol) || origin.username || origin.password || origin.pathname !== '/' || origin.search || origin.hash) {
    throw new Error('Untrusted PHP public origin')
  }
  return new URL(pathname, origin).toString()
}
