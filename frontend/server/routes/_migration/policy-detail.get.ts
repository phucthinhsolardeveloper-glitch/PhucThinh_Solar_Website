import { isPublicPolicyDetail } from '../../../shared/contracts/public-policy-detail'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug')) throw createError({ statusCode: 400, statusMessage: 'Invalid policy query' })
  const slug = query.slug
  if (typeof slug !== 'string' || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) throw createError({ statusCode: 400, statusMessage: 'Invalid policy slug' })
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_policy_detail.php',
    query: { slug },
    validate: isPublicPolicyDetail,
    invalidMessage: 'Invalid PHP policy data contract',
    unavailableMessage: 'PHP policy data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
