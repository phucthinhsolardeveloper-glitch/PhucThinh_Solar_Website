import { isPublicPolicyDetail } from '../../../shared/contracts/public-policy-detail'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const slug = getQuery(event).slug
  if (typeof slug !== 'string' || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) throw createError({ statusCode: 400, statusMessage: 'Invalid policy slug' })
  return fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_policy_detail.php',
    query: { slug },
    validate: isPublicPolicyDetail,
    invalidMessage: 'Invalid PHP policy data contract',
    unavailableMessage: 'PHP policy data source unavailable',
  })
})
