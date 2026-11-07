import { isPublicHomePayload } from '../../../shared/contracts/public-home'
import { normalizePublicHomeHero } from '../../../shared/contracts/home-hero'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireGet(event)
  const payload = await fetchBackendPayload(event, {
    endpoint: '/api/v1/home',
    validate: isPublicHomePayload,
    invalidMessage: 'Invalid home data contract',
    unavailableMessage: 'home data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  const hero = normalizePublicHomeHero(payload.hero)
  return { ...payload, hero, summary: { ...payload.summary, hero: hero.length } }
})
