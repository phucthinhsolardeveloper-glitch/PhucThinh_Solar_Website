import { isPublicRouteResolveResult, isPublicRouteResolveSlug } from './contracts/public-route-resolve.ts'

export type ProductAliasDisposition =
  | { mode: 'canonical'; path: string; statusCode: 301 }
  | { mode: 'error'; statusCode: 404 | 502 }

export function publicProductAliasDisposition(
  slug: string,
  resolution: unknown,
  errorStatus: unknown,
): ProductAliasDisposition {
  if (!isPublicRouteResolveSlug(slug)) return { mode: 'error', statusCode: 404 }
  if (errorStatus !== undefined && errorStatus !== null) {
    return { mode: 'error', statusCode: errorStatus === 404 ? 404 : 502 }
  }
  if (!isPublicRouteResolveResult(resolution) || resolution.slug !== slug) {
    return { mode: 'error', statusCode: 502 }
  }
  if (resolution.kind !== 'pdp') return { mode: 'error', statusCode: 404 }

  return { mode: 'canonical', path: resolution.canonicalPath, statusCode: 301 }
}
