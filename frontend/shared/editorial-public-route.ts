import {
  NEWS_PUBLIC_PATH,
  SOLUTIONS_PUBLIC_PATH,
  isRuntimeFlagEnabled,
  phpPublicRouteUrl,
} from './public-route-gate.ts'

export const POLICY_PUBLIC_ROOT = '/ho-tro/'
const EDITORIAL_PUBLIC_ROOTS = [SOLUTIONS_PUBLIC_PATH, POLICY_PUBLIC_ROOT] as const
const SAFE_SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/

export function isSafeEditorialSlug(value: unknown): value is string {
  return typeof value === 'string' && SAFE_SLUG.test(value)
}

export function editorialPublicRouteRobots(enabled: unknown, indexable: unknown, hasContent: unknown = true, hasFilters: unknown = false): string {
  if (hasContent !== true || hasFilters === true) return 'noindex, nofollow, noarchive'
  return editorialPublicRouteMode(enabled, indexable).robots
}

export function editorialPublicResponseStatus(isPublicRoute: unknown, status: unknown, invalidPayload: unknown = false): 404 | 502 | undefined {
  if (!isRuntimeFlagEnabled(isPublicRoute)) return undefined
  if (status === 404) return 404
  return status === 502 || invalidPayload === true ? 502 : undefined
}

export function editorialPublicHubResponseStatus(isPublicRoute: unknown, status: unknown, invalidPayload: unknown): 404 | 502 | undefined {
  if (!isRuntimeFlagEnabled(isPublicRoute)) return undefined
  if (status === 404) return 404
  return status === 502 || invalidPayload === true ? 502 : undefined
}

export function editorialPublicRouteMode(enabled: unknown, indexable: unknown): { renderNuxt: boolean; robots: string } {
  const renderNuxt = isRuntimeFlagEnabled(enabled)
  return {
    renderNuxt,
    robots: renderNuxt && isRuntimeFlagEnabled(indexable) ? 'index, follow' : 'noindex, nofollow, noarchive',
  }
}

export function editorialDetailPublicPath(root: string, slug: unknown): string {
  if (!(EDITORIAL_PUBLIC_ROOTS as readonly string[]).includes(root) || !isSafeEditorialSlug(slug)) {
    throw new Error('Invalid editorial public route')
  }
  return `${root}${slug}/`
}

export function phpEditorialDetailUrl(origin: unknown, root: string, slug: unknown): string {
  const path = editorialDetailPublicPath(root, slug)
  // phpPublicRouteUrl validates that this is a configured, trusted PHP origin.
  // The final editorial path is assembled only after the slug has passed the
  // strict lowercase slug check above.
  const validatedBase = phpPublicRouteUrl(origin, root === SOLUTIONS_PUBLIC_PATH ? SOLUTIONS_PUBLIC_PATH : NEWS_PUBLIC_PATH)
  const target = new URL(validatedBase)
  target.pathname = path
  return target.toString()
}
