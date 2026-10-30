import { isPublicHomePayload } from '../../../shared/contracts/public-home'
import { normalizePublicHomeHero } from '../../../shared/contracts/home-hero'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_home.php',
    validate: isPublicHomePayload,
    invalidMessage: 'Invalid PHP home data contract',
    unavailableMessage: 'PHP home data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  const hero = normalizePublicHomeHero(payload.hero)
  return { ...payload, hero, summary: { ...payload.summary, hero: hero.length } }
})
