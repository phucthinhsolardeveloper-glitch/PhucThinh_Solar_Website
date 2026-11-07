import type { PublicRouteResolveResult } from './contracts/public-route-resolve.ts'

export type BareSlugRouteDisposition =
  | { mode: 'canonical'; path: string; statusCode: 301 }
  | { mode: 'render'; kind: 'news' | 'news-category' | 'solution-category' | 'project' | 'project-category' | 'gallery' }

export function publicBareSlugDisposition(
  result: PublicRouteResolveResult,
): BareSlugRouteDisposition {
  if (result.kind === 'pdp' || result.kind === 'category' || result.kind === 'brand' || result.kind === 'solution' || result.kind === 'policy') {
    return { mode: 'canonical', path: result.canonicalPath, statusCode: 301 }
  }

  if (result.kind === 'news') {
    return { mode: 'render', kind: 'news' }
  }
  if (result.kind === 'news-category') {
    return { mode: 'render', kind: 'news-category' }
  }
  if (result.kind === 'solution-category') {
    return { mode: 'render', kind: 'solution-category' }
  }
  if (result.kind === 'project-category') {
    return { mode: 'render', kind: 'project-category' }
  }
  if (result.kind === 'project') {
    return { mode: 'render', kind: 'project' }
  }
  return { mode: 'render', kind: 'gallery' }
}
