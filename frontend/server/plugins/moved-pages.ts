import { fetchBackendPayload } from '../utils/backend-api'

type Moved = { location: string }

function isMoved(value: unknown): value is Moved {
  return value !== null && typeof value === 'object' && !Array.isArray(value)
    && typeof (value as Moved).location === 'string'
    && /^\/(?!\/)[^\s?#\\]*$/.test((value as Moved).location)
}

// A page that answers 404 may have been renamed: the backend keeps "old address => new address"
// (written when a slug changes), so shared and indexed links land on the page with a 301.
export default defineNitroPlugin((nitroApp) => {
  nitroApp.hooks.hook('render:response', async (response, { event }) => {
    if (response.statusCode !== 404 || event.method !== 'GET') return
    const path = event.path.split('?')[0] || '/'
    if (path.startsWith('/_') || path.startsWith('/api/') || path.length > 500) return
    try {
      const moved = await fetchBackendPayload(event, {
        endpoint: '/api/v1/redirect',
        query: { path },
        validate: isMoved,
        invalidMessage: 'Invalid redirect',
        unavailableMessage: 'redirect lookup unavailable',
        passThroughStatuses: [404],
      })
      const query = event.path.includes('?') ? event.path.slice(event.path.indexOf('?')) : ''
      response.statusCode = 301
      response.statusMessage = 'Moved Permanently'
      response.headers = { ...response.headers, location: moved.location + query, 'cache-control': 'public, max-age=3600' }
      response.body = ''
    } catch {
      // Not moved, or the lookup failed: keep the normal 404 page.
    }
  })
})
