import type { PublicRouteResolveResult } from './contracts/public-route-resolve.ts'
import { bareSlugPublicPath, isRuntimeFlagEnabled } from './public-route-gate.ts'

export type BareSlugRouteFlags = {
  newsPublicRouteEnabled?: unknown
  newsDetailPublicRouteEnabled?: unknown
  solutionsPublicRouteEnabled?: unknown
  projectDetailPublicRouteEnabled?: unknown
  projectsPublicRouteEnabled?: unknown
  galleryDetailPublicRouteEnabled?: unknown
}

export type BareSlugRouteDisposition =
  | { mode: 'legacy'; path: string }
  | { mode: 'canonical'; path: string; statusCode: 301 }
  | { mode: 'render'; kind: 'news' | 'news-category' | 'solution-category' | 'project' | 'project-category' | 'gallery' }

export function publicBareSlugDisposition(
  result: PublicRouteResolveResult,
  flags: BareSlugRouteFlags,
): BareSlugRouteDisposition {
  const legacyPath = bareSlugPublicPath(result.slug)
  if (result.kind === 'legacy-only') return { mode: 'legacy', path: legacyPath }

  if (result.kind === 'pdp' || result.kind === 'category' || result.kind === 'brand' || result.kind === 'solution' || result.kind === 'policy') {
    return { mode: 'canonical', path: result.canonicalPath, statusCode: 301 }
  }

  if (result.kind === 'news') {
    return isRuntimeFlagEnabled(flags.newsDetailPublicRouteEnabled)
      ? { mode: 'render', kind: 'news' }
      : { mode: 'legacy', path: legacyPath }
  }
  if (result.kind === 'news-category') {
    return isRuntimeFlagEnabled(flags.newsPublicRouteEnabled)
      ? { mode: 'render', kind: 'news-category' }
      : { mode: 'legacy', path: legacyPath }
  }
  if (result.kind === 'solution-category') {
    return isRuntimeFlagEnabled(flags.solutionsPublicRouteEnabled)
      ? { mode: 'render', kind: 'solution-category' }
      : { mode: 'legacy', path: legacyPath }
  }
  if (result.kind === 'project-category') {
    return isRuntimeFlagEnabled(flags.projectsPublicRouteEnabled)
      ? { mode: 'render', kind: 'project-category' }
      : { mode: 'legacy', path: legacyPath }
  }
  if (result.kind === 'project') {
    return isRuntimeFlagEnabled(flags.projectDetailPublicRouteEnabled)
      ? { mode: 'render', kind: 'project' }
      : { mode: 'legacy', path: legacyPath }
  }
  if (result.kind === 'gallery') {
    return isRuntimeFlagEnabled(flags.galleryDetailPublicRouteEnabled)
      ? { mode: 'render', kind: 'gallery' }
      : { mode: 'legacy', path: legacyPath }
  }

  return { mode: 'legacy', path: legacyPath }
}
