import { isPublicAboutPayload } from '../../../shared/contracts/public-about'
import { normalizePublicHomeHero } from '../../../shared/contracts/home-hero'
import { isPublicHomePayload } from '../../../shared/contracts/public-home'
import { layoutDraftQuery } from '../../../shared/page-layout'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

// Home or About with a layout draft, from a signed Admin link. Never cached or indexed.
export default defineEventHandler(async (event) => {
  requireGet(event)
  const query = getQuery(event)
  const preview = Object.keys(query).every((key) => ['layout', 'expires', 'signature'].includes(key)) ? layoutDraftQuery(query.layout, query) : null
  if (!preview) throw createError({ statusCode: 400, statusMessage: 'Invalid preview link' })
  const payload = await fetchBackendPayload(event, {
    endpoint: `/api/v1/page-layout-preview/${preview.layout}`,
    query: { expires: preview.expires, signature: preview.signature },
    validate: (value: unknown) => isPublicHomePayload(value) || isPublicAboutPayload(value),
    invalidMessage: 'Invalid layout preview data',
    unavailableMessage: 'Layout preview unavailable',
    passThroughStatuses: [403, 404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  setHeader(event, 'X-Robots-Tag', 'noindex, nofollow, noarchive')
  setHeader(event, 'Referrer-Policy', 'no-referrer')
  if (isPublicHomePayload(payload)) {
    const hero = normalizePublicHomeHero(payload.hero)
    return { ...payload, hero, summary: { ...payload.summary, hero: hero.length } }
  }
  return payload
})
