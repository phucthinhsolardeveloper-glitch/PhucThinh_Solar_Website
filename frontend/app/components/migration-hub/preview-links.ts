import { MIGRATION_PREVIEW_ROUTES, type MigrationPreviewRouteKey } from '../../../shared/contracts/migration-preview-routes.ts'

const LOCAL_PHP_ORIGIN = /^http:\/\/(?:localhost|127\.0\.0\.1)(?::\d{2,5})?$/
const SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/

function routeFor(key: MigrationPreviewRouteKey) {
  const route = MIGRATION_PREVIEW_ROUTES.find((item) => item.key === key)
  if (!route) throw new Error('Unknown migration preview route')
  return route
}

export function previewHref(key: MigrationPreviewRouteKey, query: Record<string, string> = {}) {
  const route = routeFor(key)
  const params = new URLSearchParams()
  for (const [name, value] of Object.entries(query)) {
    if (name !== 'slug' || !SLUG.test(value)) throw new Error('Unsupported preview query')
    params.set(name, value)
  }
  return `${route.previewPath}${params.size ? `?${params}` : ''}`
}

export function phpHref(key: MigrationPreviewRouteKey, origin = 'http://localhost:8088') {
  const route = routeFor(key)
  if (!LOCAL_PHP_ORIGIN.test(origin)) throw new Error('PHP fallback must be local')
  return `${origin}${route.phpPath}`
}

export function resolvePreviewLink(key: MigrationPreviewRouteKey, mode: 'nuxt' | 'php' = 'nuxt', query: Record<string, string> = {}) {
  if (mode === 'php') return phpHref(key)
  if (mode !== 'nuxt') throw new Error('Unsupported preview link mode')
  return previewHref(key, query)
}

