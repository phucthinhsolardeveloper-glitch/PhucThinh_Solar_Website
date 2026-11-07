import {
  SOLUTIONS_PUBLIC_PATH,
} from './public-route-gate.ts'
import { isPublicSeoIndexable } from './public-seo.ts'

export const POLICY_PUBLIC_ROOT = '/ho-tro/'
const EDITORIAL_PUBLIC_ROOTS = [SOLUTIONS_PUBLIC_PATH, POLICY_PUBLIC_ROOT] as const
const SAFE_SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/
const LEGACY_SOLUTION_REDIRECTS: Readonly<Record<string, string>> = {
  'thiet-bi-den-nang-luong-mat-troi-quy-mo-ho-gia-dinh': '/giai-phap/chieu-sang-nang-luong-mat-troi/',
}

export function isSafeEditorialSlug(value: unknown): value is string {
  return typeof value === 'string' && SAFE_SLUG.test(value)
}

export function editorialPublicRouteRobots(isPublicRoute: unknown, indexable: unknown, hasContent: unknown = true, hasFilters: unknown = false): string {
  if (isPublicRoute !== true || !isPublicSeoIndexable(indexable, hasContent, hasFilters)) return 'noindex, nofollow, noarchive'
  return 'index, follow'
}

export function editorialPublicResponseStatus(isPublicRoute: unknown, status: unknown, invalidPayload: unknown = false): 404 | 502 | undefined {
  if (isPublicRoute !== true) return undefined
  if (status === 404) return 404
  return status === 502 || invalidPayload === true ? 502 : undefined
}

export function editorialPublicHubResponseStatus(isPublicRoute: unknown, status: unknown, invalidPayload: unknown): 404 | 502 | undefined {
  if (isPublicRoute !== true) return undefined
  if (status === 404) return 404
  return status === 502 || invalidPayload === true ? 502 : undefined
}

export function editorialDetailPublicPath(root: string, slug: unknown): string {
  if (!(EDITORIAL_PUBLIC_ROOTS as readonly string[]).includes(root) || !isSafeEditorialSlug(slug)) {
    throw new Error('Invalid editorial public route')
  }
  return `${root}${slug}/`
}

export function solutionOldPathRedirect(slug: unknown): string | undefined {
  if (!isSafeEditorialSlug(slug)) return undefined
  return LEGACY_SOLUTION_REDIRECTS[slug]
}
